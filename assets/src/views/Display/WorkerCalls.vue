<template>
  <div class="worker-calls-display" :class="{ 'is-loading': loading }">
    <!-- Header -->
    <div class="display-header">
      <div class="header-content">
        <div class="logo-section">
          <div class="logo-icon-wrapper">
            <CIcon icon="cil-bell" class="logo-icon" />
          </div>
          <div class="title-group">
            <h1 class="display-title">Worker Calls Display</h1>
            <p class="display-subtitle">Real-time Staff Call Management System</p>
          </div>
        </div>
        <div class="header-info">
          <div class="date-time-group">
            <div class="display-date">
              <CIcon icon="cil-calendar" class="info-icon" />
              {{ currentDate }}
            </div>
            <div class="display-time">
              <CIcon icon="cil-clock" class="info-icon" />
              {{ currentTime }}
            </div>
          </div>
          <button 
            class="fullscreen-btn" 
            @click="toggleFullscreen"
            :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'"
          >
            <CIcon :icon="isFullscreen ? 'cil-fullscreen-exit' : 'cil-fullscreen'" />
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="display-content">
      <div v-if="displayCalls.length" class="calls-grid">
        <div
          v-for="call in displayCalls"
          :key="call.id"
          class="call-card"
          :class="getStatusClass(call.status)"
          :style="{ borderLeft: `8px solid ${getStatusColor(call.status)}` }"
        >
          <div class="card-header">
            <div class="staff-info">
              <h2 class="staff-name">{{ call.staff_name || 'Staff' }}</h2>
              <div class="chair-badge" :class="{ 'no-chair': !call.staff_chair_number || call.staff_chair_number === 0 }">
                <CIcon icon="cil-chair" class="chair-icon" />
                <span>كرسي: {{ (!call.staff_chair_number || call.staff_chair_number === 0) ? 'غير محدد' : call.staff_chair_number }}</span>
              </div>
            </div>
            <div class="status-icon-wrapper" :class="call.status">
              <CIcon :icon="getStatusIcon(call.status)" class="status-icon" />
            </div>
          </div>

          <div class="card-body">
            <div class="customer-section">
              <p class="label">العميلة:</p>
              <h3 class="customer-name">{{ call.customer_name || 'Customer' }}</h3>
            </div>

            <div class="services-section" v-if="call.service_name">
              <p class="label">الخدمات:</p>
              <div class="services-list">
                <span class="service-badge">{{ call.service_name }}</span>
              </div>
            </div>

            <div class="ticket-info" v-if="call.ticket_number">
              <CIcon icon="cil-ticket" class="ticket-icon" />
              <span>{{ call.ticket_number }}</span>
            </div>
          </div>

          <div class="card-footer">
            <span class="status-badge" :class="call.status">
              {{ getStatusText(call.status) }}
            </span>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon-large">
          <CIcon icon="cil-bell" />
        </div>
        <p class="empty-text">لا توجد نداءات حالياً</p>
        <p class="empty-subtext">في انتظار نداءات جديدة</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { CIcon } from '@coreui/icons-vue';
import api from '@/utils/api';

const activeCalls = ref([]);
const stickyCalls = ref(new Map()); // Map<callId, hideTimeout>
const lastCallIds = ref(new Set());
const loading = ref(false);
const currentTime = ref('');
const currentDate = ref('');
const isFullscreen = ref(false);

let abortController = null;
let retryCount = 0;
const MAX_RETRIES = 3;
let refreshInterval = null;
let timeInterval = null;

const updateTime = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
  currentDate.value = now.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const today = () => {
  const d = new Date();
  return d.toISOString().slice(0, 10);
};

const playNotificationSound = () => {
  try {
    // Try to play notification sound
    const audio = new Audio('/wp-content/plugins/asmasalonv1/assets/sounds/ding.mp3');
    audio.volume = 0.5;
    audio.play().catch(e => {
      console.log('Audio play failed (may need user interaction):', e);
    });
  } catch (error) {
    console.log('Audio not available:', error);
  }
};

const loadCalls = async () => {
  // Cancel previous request
  if (abortController) {
    abortController.abort();
  }

  abortController = new AbortController();
  const timeoutId = setTimeout(() => abortController.abort(), 5000);

  loading.value = true;
  try {
    const response = await api.get('/worker-calls', {
      params: {
        status: 'active',
        per_page: 50,
        date: today(),
      },
      signal: abortController.signal,
      noCache: true, // Disable cache for real-time updates
    });

    clearTimeout(timeoutId);
    retryCount = 0;

    const newCalls = response.data?.data?.items || [];
    
    // Check for new calls
    const newCallIds = new Set(newCalls.map(c => c.id));
    const oldCallIds = lastCallIds.value;
    
    // Find new calls
    const newCall = newCalls.find(c => !oldCallIds.has(c.id));
    if (newCall) {
      playNotificationSound();
    }
    
    // Check for status changes (pending -> staff_called)
    newCalls.forEach(newCall => {
      const oldCall = activeCalls.value.find(c => c.id === newCall.id);
      if (oldCall && oldCall.status === 'pending' && newCall.status === 'staff_called') {
        playNotificationSound();
      }
    });

    activeCalls.value = newCalls;
    lastCallIds.value = newCallIds;
  } catch (error) {
    clearTimeout(timeoutId);
    if (error.name !== 'AbortError' && retryCount < MAX_RETRIES) {
      retryCount++;
      setTimeout(loadCalls, Math.pow(2, retryCount) * 1000); // Exponential backoff
    } else {
      console.error('Error loading worker calls:', error);
    }
  } finally {
    loading.value = false;
  }
};

// Watch for calls that become accepted/serving (sticky calls)
watch(activeCalls, (newCalls) => {
  newCalls.forEach(call => {
    if (['accepted', 'serving'].includes(call.status) && !stickyCalls.value.has(call.id)) {
      // Keep call visible for 8 seconds
      const timeout = setTimeout(() => {
        stickyCalls.value.delete(call.id);
      }, 8000);
      stickyCalls.value.set(call.id, timeout);
    }
  });
}, { deep: true });

const displayCalls = computed(() => {
  const calls = [...activeCalls.value];
  
  // Add sticky calls that are no longer in activeCalls
  stickyCalls.value.forEach((timeout, callId) => {
    if (!calls.find(c => c.id === callId)) {
      // Find the call in previous state (we'll keep showing it)
      const stickyCall = activeCalls.value.find(c => c.id === callId);
      if (stickyCall) {
        calls.push({ ...stickyCall, isSticky: true });
      }
    }
  });

  return calls;
});

const getStatusClass = (status) => {
  const statusMap = {
    pending: 'status-pending',
    staff_called: 'status-staff-called',
    customer_called: 'status-customer-called',
    accepted: 'status-accepted',
    serving: 'status-serving',
  };
  return statusMap[status] || 'status-default';
};

const getStatusColor = (status) => {
  const colorMap = {
    pending: '#f59e0b', // Yellow/Orange
    staff_called: '#3b82f6', // Blue
    customer_called: '#10b981', // Green
    accepted: '#8b5cf6', // Purple
    serving: '#8b5cf6', // Purple
  };
  return colorMap[status] || '#BBA07A';
};

const getStatusIcon = (status) => {
  const iconMap = {
    pending: 'cil-clock',
    staff_called: 'cil-bell',
    customer_called: 'cil-user',
    accepted: 'cil-check-circle',
    serving: 'cil-check-circle',
  };
  return iconMap[status] || 'cil-info';
};

const getStatusText = (status) => {
  const textMap = {
    pending: 'في الانتظار',
    staff_called: 'تم استدعاء الموظفة',
    customer_called: 'تم نداء العميلة',
    accepted: 'قيد التنفيذ',
    serving: 'قيد التنفيذ',
  };
  return textMap[status] || status;
};

const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().then(() => {
      isFullscreen.value = true;
    }).catch(err => {
      console.log('Error attempting to enable fullscreen:', err);
    });
  } else {
    document.exitFullscreen().then(() => {
      isFullscreen.value = false;
    });
  }
};

// Handle fullscreen change events
const handleFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement;
};

onMounted(() => {
  updateTime();
  timeInterval = setInterval(updateTime, 1000);
  loadCalls();
  
  // Auto-refresh every 3 seconds
  refreshInterval = setInterval(() => {
    if (!loading.value) {
      loadCalls();
    }
  }, 3000);

  // Listen for fullscreen changes
  document.addEventListener('fullscreenchange', handleFullscreenChange);
  document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
  document.addEventListener('mozfullscreenchange', handleFullscreenChange);
  document.addEventListener('MSFullscreenChange', handleFullscreenChange);

  // Keyboard shortcut for fullscreen (F11)
  document.addEventListener('keydown', (e) => {
    if (e.key === 'F11') {
      e.preventDefault();
      toggleFullscreen();
    }
  });
});

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval);
  if (refreshInterval) clearInterval(refreshInterval);
  if (abortController) abortController.abort();
  
  // Clear sticky call timeouts
  stickyCalls.value.forEach(timeout => clearTimeout(timeout));
  stickyCalls.value.clear();

  document.removeEventListener('fullscreenchange', handleFullscreenChange);
  document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
  document.removeEventListener('mozfullscreenchange', handleFullscreenChange);
  document.removeEventListener('MSFullscreenChange', handleFullscreenChange);
});
</script>

<style scoped>
.worker-calls-display {
  width: 100%;
  min-height: 100vh;
  background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a2f4a 100%);
  color: #ffffff;
  display: flex;
  flex-direction: column;
  padding: 2.5rem;
  font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
  overflow: hidden;
  position: relative;
}

.worker-calls-display::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 15% 40%, rgba(187, 160, 122, 0.12) 0%, transparent 50%),
    radial-gradient(circle at 85% 70%, rgba(187, 160, 122, 0.08) 0%, transparent 50%),
    linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
  pointer-events: none;
  z-index: 0;
}

.worker-calls-display > * {
  position: relative;
  z-index: 1;
}

/* Header */
.display-header {
  margin-bottom: 2.5rem;
  border-bottom: 2px solid rgba(187, 160, 122, 0.3);
  padding-bottom: 2rem;
  animation: fadeInDown 0.6s ease-out;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 2rem;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.logo-icon-wrapper {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  background: linear-gradient(135deg, #BBA07A 0%, rgba(187, 160, 122, 0.8) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(187, 160, 122, 0.4);
  animation: pulse 3s ease-in-out infinite;
}

.logo-icon {
  width: 40px;
  height: 40px;
  color: white;
}

.title-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.display-title {
  font-size: 3.5rem;
  font-weight: 800;
  margin: 0;
  color: #BBA07A;
  text-shadow: 0 4px 16px rgba(187, 160, 122, 0.5);
  letter-spacing: -1px;
  line-height: 1.1;
}

.display-subtitle {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.6);
  margin: 0;
  font-weight: 500;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.header-info {
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: flex-end;
}

.date-time-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  align-items: flex-end;
}

.display-date {
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.display-time {
  font-size: 2.75rem;
  color: #BBA07A;
  font-weight: 700;
  font-family: 'Courier New', 'SF Mono', monospace;
  text-shadow: 0 2px 12px rgba(187, 160, 122, 0.4);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.info-icon {
  width: 20px;
  height: 20px;
  color: #BBA07A;
  opacity: 0.8;
}

.fullscreen-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(187, 160, 122, 0.2);
  border: 2px solid rgba(187, 160, 122, 0.4);
  color: #BBA07A;
  cursor: pointer;
  transition: all 0.3s;
  margin-top: 0.5rem;
}

.fullscreen-btn:hover {
  background: rgba(187, 160, 122, 0.3);
  border-color: #BBA07A;
  transform: translateY(-2px);
}

.fullscreen-btn CIcon {
  width: 24px;
  height: 24px;
}

/* Main Content */
.display-content {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 2rem;
}

.calls-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 2rem;
  padding: 1rem 0;
}

/* Call Card */
.call-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  padding: 2.5rem;
  backdrop-filter: blur(20px);
  border: 1px solid rgba(187, 160, 122, 0.2);
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  animation: fadeInUp 0.8s ease-out;
  transition: all 0.3s;
  position: relative;
  overflow: hidden;
}

.call-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, transparent 0%, rgba(187, 160, 122, 0.5) 50%, transparent 100%);
}

.call-card:hover {
  border-color: rgba(187, 160, 122, 0.4);
  box-shadow: 
    0 12px 40px rgba(187, 160, 122, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
  transform: translateY(-4px);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.staff-info {
  flex: 1;
}

.staff-name {
  font-size: 2.5rem;
  font-weight: 900;
  color: #ffffff;
  margin: 0 0 1rem 0;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  letter-spacing: -0.5px;
  line-height: 1.2;
}

.chair-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #BBA07A 0%, rgba(187, 160, 122, 0.8) 100%);
  color: white;
  border-radius: 12px;
  font-size: 1.5rem;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

.chair-badge.no-chair {
  background: linear-gradient(135deg, rgba(107, 114, 128, 0.8) 0%, rgba(107, 114, 128, 0.6) 100%);
  box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
}

.chair-icon {
  width: 24px;
  height: 24px;
}

.status-icon-wrapper {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.status-icon-wrapper.pending {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.1) 100%);
  border: 2px solid rgba(245, 158, 11, 0.3);
}

.status-icon-wrapper.staff_called {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%);
  border: 2px solid rgba(59, 130, 246, 0.3);
}

.status-icon-wrapper.customer_called {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%);
  border: 2px solid rgba(16, 185, 129, 0.3);
}

.status-icon-wrapper.accepted,
.status-icon-wrapper.serving {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%);
  border: 2px solid rgba(139, 92, 246, 0.3);
}

.status-icon {
  width: 32px;
  height: 32px;
  color: #BBA07A;
}

.status-icon-wrapper.pending .status-icon {
  color: #f59e0b;
}

.status-icon-wrapper.staff_called .status-icon {
  color: #3b82f6;
}

.status-icon-wrapper.customer_called .status-icon {
  color: #10b981;
}

.status-icon-wrapper.accepted .status-icon,
.status-icon-wrapper.serving .status-icon {
  color: #8b5cf6;
}

.card-body {
  margin-bottom: 1.5rem;
}

.customer-section {
  margin-bottom: 1.5rem;
}

.label {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.6);
  margin: 0 0 0.5rem 0;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.customer-name {
  font-size: 2rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.services-section {
  margin-bottom: 1rem;
}

.services-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.service-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  font-size: 1.125rem;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 500;
}

.ticket-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.7);
  font-size: 1rem;
  font-weight: 500;
}

.ticket-icon {
  width: 18px;
  height: 18px;
}

.card-footer {
  display: flex;
  justify-content: flex-end;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.pending {
  background: linear-gradient(135deg, #f59e0b 0%, rgba(245, 158, 11, 0.8) 100%);
  color: white;
}

.status-badge.staff_called {
  background: linear-gradient(135deg, #3b82f6 0%, rgba(59, 130, 246, 0.8) 100%);
  color: white;
}

.status-badge.customer_called {
  background: linear-gradient(135deg, #10b981 0%, rgba(16, 185, 129, 0.8) 100%);
  color: white;
}

.status-badge.accepted,
.status-badge.serving {
  background: linear-gradient(135deg, #8b5cf6 0%, rgba(139, 92, 246, 0.8) 100%);
  color: white;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 6rem 2rem;
  color: rgba(255, 255, 255, 0.5);
}

.empty-icon-large {
  width: 120px;
  height: 120px;
  margin: 0 auto 1.5rem;
  color: rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-icon-large CIcon {
  width: 80px;
  height: 80px;
}

.empty-text {
  font-size: 2rem;
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.6);
}

.empty-subtext {
  font-size: 1.25rem;
  margin: 0;
  color: rgba(255, 255, 255, 0.4);
  font-weight: 400;
}

/* Animations */
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 8px 24px rgba(187, 160, 122, 0.5);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 12px 32px rgba(187, 160, 122, 0.6);
  }
}

/* Loading State */
.worker-calls-display.is-loading {
  opacity: 0.7;
}

.worker-calls-display.is-loading::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 60px;
  height: 60px;
  margin: -30px 0 0 -30px;
  border: 4px solid rgba(187, 160, 122, 0.3);
  border-top-color: #BBA07A;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  z-index: 10;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 1400px) {
  .display-title {
    font-size: 3rem;
  }

  .staff-name {
    font-size: 2rem;
  }

  .calls-grid {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  }
}

@media (max-width: 1200px) {
  .calls-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .worker-calls-display {
    padding: 1.5rem;
  }

  .display-title {
    font-size: 2rem;
  }

  .logo-icon-wrapper {
    width: 60px;
    height: 60px;
  }

  .logo-icon {
    width: 30px;
    height: 30px;
  }

  .display-time {
    font-size: 2rem;
  }

  .display-date {
    font-size: 1rem;
  }

  .calls-grid {
    grid-template-columns: 1fr;
  }

  .staff-name {
    font-size: 1.75rem;
  }

  .customer-name {
    font-size: 1.5rem;
  }
}

/* Scrollbar Styling */
.display-content::-webkit-scrollbar {
  width: 10px;
}

.display-content::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 5px;
}

.display-content::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #BBA07A 0%, rgba(187, 160, 122, 0.8) 100%);
  border-radius: 5px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.display-content::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.9) 0%, #BBA07A 100%);
}
</style>

