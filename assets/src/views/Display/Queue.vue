<template>
  <div class="queue-display" :class="{ 'is-loading': loading }">
    <!-- Header -->
    <div class="display-header">
      <div class="header-content">
        <div class="logo-section">
          <div class="logo-icon-wrapper">
            <CIcon icon="cil-list" class="logo-icon" />
          </div>
          <div class="title-group">
            <h1 class="display-title">Queue Display</h1>
            <p class="display-subtitle">Real-time Queue Management System</p>
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
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="display-content">
      <!-- Currently Serving Section -->
      <div class="display-section serving-section">
        <div v-if="displayTicket" class="serving-card-simple" :class="{ 'next-customer': displayTicket.status === 'called' }">
          <div class="customer-name-display">
            {{ displayTicket.customer_name || 'Customer' }}
          </div>
        </div>
        <div v-else class="empty-serving">
          <div class="empty-icon-large">
            <CIcon icon="cil-pause" />
          </div>
          <p class="empty-text">No service currently</p>
          <p class="empty-subtext">Waiting for next customer</p>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { CIcon } from '@coreui/icons-vue';
import api from '@/utils/api';

const tickets = ref([]);
const loading = ref(false);
const completing = ref(false);
const isCalling = ref(false);
const currentTime = ref('');
const currentDate = ref('');
const lastCalledTicket = ref(null);

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

const loadTickets = async () => {
  loading.value = true;
  try {
    const response = await api.get('/queue', { params: { per_page: 100 } });
    const data = response.data?.data || response.data || {};
    tickets.value = data.items || [];
  } catch (error) {
    console.error('Error loading tickets:', error);
    tickets.value = [];
  } finally {
    loading.value = false;
  }
};

const currentlyServing = computed(() => {
  return tickets.value.find(t => t.status === 'serving') || null;
});

const nextTicket = computed(() => {
  return tickets.value.find(t => t.status === 'called') || null;
});

const displayTicket = computed(() => {
  // Show currently serving first, then called ticket
  return currentlyServing.value || nextTicket.value || null;
});

const waitingList = computed(() => {
  const servingId = currentlyServing.value?.id;
  return tickets.value
    .filter(t => {
      // Show all non-completed tickets except the one currently being served
      return t.status !== 'completed' && t.id !== servingId;
    })
    .sort((a, b) => {
      // Sort by priority: serving > called > waiting, then by created_at
      const priority = { serving: 3, called: 2, waiting: 1 };
      const priorityDiff = (priority[b.status] || 0) - (priority[a.status] || 0);
      if (priorityDiff !== 0) return priorityDiff;
      return new Date(a.created_at) - new Date(b.created_at);
    })
    .map((t, idx) => ({
      ...t,
      is_you: false, // Could be set based on customer_id from URL params
    }));
});

const waitingCount = computed(() => {
  // Count all non-completed tickets (waiting, called, serving)
  return tickets.value.filter(t => t.status !== 'completed').length;
});
const servingCount = computed(() => tickets.value.filter(t => t.status === 'serving').length);
const completedTodayCount = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  return tickets.value.filter(t => {
    if (t.status !== 'completed') return false;
    const ticketDate = t.completed_at ? new Date(t.completed_at).toISOString().split('T')[0] : null;
    return ticketDate === today;
  }).length;
});
const totalTodayCount = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  return tickets.value.filter(t => {
    const ticketDate = t.created_at ? new Date(t.created_at).toISOString().split('T')[0] : null;
    return ticketDate === today;
  }).length;
});

let timeInterval = null;
let refreshInterval = null;
let callInterval = null;
let callCount = 0;
const MAX_CALLS = 3;
const CALL_INTERVAL = 20000; // 20 seconds

const callCustomerName = (ticket = null) => {
  const targetTicket = ticket || displayTicket.value;
  if (!targetTicket) return;
  
  const customerName = targetTicket.customer_name || 'Customer';
  speakText(`العميل ${customerName}`);
};

const speakText = (text) => {
  if (!('speechSynthesis' in window)) {
    console.warn('Text-to-speech is not supported in your browser');
    return;
  }
  
  // Cancel any ongoing speech
  window.speechSynthesis.cancel();
  
  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang = 'ar-SA'; // Arabic (Saudi Arabia)
  utterance.rate = 0.9;
  utterance.pitch = 1;
  utterance.volume = 1;
  
  utterance.onend = () => {
    isCalling.value = false;
  };
  
  utterance.onerror = () => {
    isCalling.value = false;
  };
  
  isCalling.value = true;
  window.speechSynthesis.speak(utterance);
};

const startAutoCall = (ticket) => {
  // Clear any existing interval
  if (callInterval) {
    clearInterval(callInterval);
    callInterval = null;
  }
  
  callCount = 0;
  
  // Call immediately
  callCustomerName(ticket);
  callCount++;
  
  // Then repeat every 20 seconds for 3 times total
  callInterval = setInterval(() => {
    if (callCount < MAX_CALLS) {
      callCustomerName(ticket);
      callCount++;
    } else {
      // Stop after 3 calls
      clearInterval(callInterval);
      callInterval = null;
      callCount = 0;
    }
  }, CALL_INTERVAL);
};

const stopAutoCall = () => {
  if (callInterval) {
    clearInterval(callInterval);
    callInterval = null;
  }
  callCount = 0;
  window.speechSynthesis.cancel();
  isCalling.value = false;
};

// Watch for called tickets (when staff calls a customer)
watch(nextTicket, (newTicket, oldTicket) => {
  // If a new ticket is called (status changed to 'called')
  if (newTicket && newTicket.id !== oldTicket?.id && newTicket.status === 'called') {
    stopAutoCall(); // Stop any previous calls
    // Start auto-calling this customer
    setTimeout(() => {
      startAutoCall(newTicket);
    }, 500);
  } else if (!newTicket && oldTicket) {
    // If called ticket is gone (maybe started serving or completed)
    stopAutoCall();
  }
}, { immediate: false });

// Watch for currently serving (when service starts)
watch(currentlyServing, (newTicket, oldTicket) => {
  if (newTicket && newTicket.id !== oldTicket?.id) {
    // Stop auto-calling when service starts
    stopAutoCall();
  } else if (!newTicket && oldTicket) {
    // Service ended, check if there's a called ticket
    if (nextTicket.value && nextTicket.value.status === 'called') {
      setTimeout(() => {
        startAutoCall(nextTicket.value);
      }, 500);
    }
  }
}, { immediate: false });

onMounted(() => {
  updateTime();
  timeInterval = setInterval(updateTime, 1000);
  loadTickets();
  // Auto-refresh every 3 seconds to detect new calls quickly
  refreshInterval = setInterval(loadTickets, 3000);
});

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval);
  if (refreshInterval) clearInterval(refreshInterval);
  stopAutoCall();
});
</script>

<style scoped>
.queue-display {
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

.queue-display::before {
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

.queue-display > * {
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


/* Main Content */
.display-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2rem;
  overflow: hidden;
  margin-bottom: 2rem;
  align-items: center;
  justify-content: center;
}

.display-section {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  padding: 2rem;
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

.display-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, transparent 0%, rgba(187, 160, 122, 0.5) 50%, transparent 100%);
}

.display-section:hover {
  border-color: rgba(187, 160, 122, 0.4);
  box-shadow: 
    0 12px 40px rgba(187, 160, 122, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
  transform: translateY(-2px);
}

.serving-section {
  grid-column: 1 / -1;
  animation-delay: 0.1s;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
}

.section-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(187, 160, 122, 0.2);
}

.section-title-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
  justify-content: center;
}

.section-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.serving-icon {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%);
  border: 2px solid rgba(16, 185, 129, 0.3);
}

.next-icon {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%);
  border: 2px solid rgba(59, 130, 246, 0.3);
}

.waiting-icon {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.2) 0%, rgba(187, 160, 122, 0.1) 100%);
  border: 2px solid rgba(187, 160, 122, 0.3);
}

.section-icon {
  width: 28px;
  height: 28px;
  color: #BBA07A;
}

.serving-icon .section-icon {
  color: #10b981;
}

.next-icon .section-icon {
  color: #3b82f6;
}

.section-title {
  font-size: 1.75rem;
  margin: 0;
  color: #BBA07A;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  letter-spacing: -0.5px;
}

.serving-section .section-title {
  color: #10b981;
}

.next-section .section-title {
  color: #3b82f6;
}

.waiting-count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  padding: 0 0.875rem;
  background: linear-gradient(135deg, #BBA07A 0%, rgba(187, 160, 122, 0.8) 100%);
  color: white;
  border-radius: 18px;
  font-size: 1rem;
  font-weight: 800;
  margin-left: 0.5rem;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

/* Serving Card Simple */
.serving-card-simple {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3rem;
  padding: 4rem;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.08) 100%);
  border-radius: 24px;
  border: 3px solid rgba(16, 185, 129, 0.5);
  box-shadow: 
    0 12px 32px rgba(16, 185, 129, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  animation: scaleIn 0.5s ease-out;
  position: relative;
  overflow: hidden;
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}

.serving-card-simple.next-customer {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%);
  border-color: rgba(59, 130, 246, 0.5);
  box-shadow: 
    0 12px 32px rgba(59, 130, 246, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.customer-name-display {
  font-size: 6rem;
  font-weight: 900;
  color: #ffffff;
  text-align: center;
  text-shadow: 
    0 4px 20px rgba(0, 0, 0, 0.5),
    0 2px 10px rgba(16, 185, 129, 0.3);
  letter-spacing: 2px;
  line-height: 1.2;
  margin-bottom: 1rem;
  animation: fadeInScale 0.6s ease-out;
}

.next-customer .customer-name-display {
  text-shadow: 
    0 4px 20px rgba(0, 0, 0, 0.5),
    0 2px 10px rgba(59, 130, 246, 0.3);
}


/* Serving Card */
.serving-card {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 3rem;
  padding: 3rem;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.08) 100%);
  border-radius: 20px;
  border: 2px solid rgba(16, 185, 129, 0.4);
  box-shadow: 
    0 8px 24px rgba(16, 185, 129, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  animation: scaleIn 0.5s ease-out;
  position: relative;
  overflow: hidden;
}

.serving-card::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
  animation: rotate 20s linear infinite;
}

.ticket-badge-large {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  background: linear-gradient(135deg, #10b981 0%, rgba(16, 185, 129, 0.9) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 
    0 8px 24px rgba(16, 185, 129, 0.5),
    inset 0 2px 4px rgba(255, 255, 255, 0.2);
  animation: pulse 2s ease-in-out infinite;
  position: relative;
  z-index: 1;
}

.badge-icon-large {
  width: 70px;
  height: 70px;
  color: white;
}

.ticket-info-large {
  text-align: center;
  flex: 1;
  position: relative;
  z-index: 1;
}

.ticket-number-large {
  font-size: 4.5rem;
  font-weight: 900;
  color: #ffffff;
  margin-bottom: 1rem;
  font-family: 'Courier New', 'SF Mono', monospace;
  text-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
  letter-spacing: 4px;
  line-height: 1;
}

.customer-name-large {
  font-size: 2.5rem;
  color: #ffffff;
  font-weight: 700;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.info-icon-small {
  width: 24px;
  height: 24px;
  color: rgba(255, 255, 255, 0.8);
}

.service-info-large,
.staff-info-large {
  font-size: 1.5rem;
  color: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin-top: 0.75rem;
  font-weight: 500;
}

/* Next Card */
.next-card {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2rem;
  padding: 2rem;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%);
  border-radius: 16px;
  border: 2px solid rgba(59, 130, 246, 0.4);
  box-shadow: 
    0 6px 20px rgba(59, 130, 246, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  animation: slideInLeft 0.6s ease-out;
}

.ticket-badge-medium {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, rgba(59, 130, 246, 0.9) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 
    0 6px 20px rgba(59, 130, 246, 0.4),
    inset 0 2px 4px rgba(255, 255, 255, 0.2);
}

.badge-icon-medium {
  width: 50px;
  height: 50px;
  color: white;
}

.ticket-info-medium {
  text-align: center;
  flex: 1;
}

.ticket-number-medium {
  font-size: 2.75rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 0.75rem;
  font-family: 'Courier New', 'SF Mono', monospace;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
  letter-spacing: 2px;
}

.customer-name-medium {
  font-size: 1.75rem;
  color: #ffffff;
  font-weight: 600;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.service-info-medium {
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 500;
}

.info-icon-xs {
  width: 18px;
  height: 18px;
  color: rgba(255, 255, 255, 0.7);
}

/* Empty States */
.empty-serving,
.empty-next,
.empty-waiting {
  text-align: center;
  padding: 3rem;
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

.empty-icon-medium {
  width: 80px;
  height: 80px;
  margin: 0 auto 1rem;
  color: rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-icon-medium CIcon {
  width: 50px;
  height: 50px;
}

.empty-text {
  font-size: 1.5rem;
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.6);
}

.empty-subtext {
  font-size: 1rem;
  margin: 0;
  color: rgba(255, 255, 255, 0.4);
  font-weight: 400;
}

/* Waiting List */
.waiting-list-container {
  max-height: 100%;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.waiting-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.waiting-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.25rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 14px;
  font-size: 1.125rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 2px solid transparent;
  animation: slideInRight 0.5s ease-out;
  backdrop-filter: blur(10px);
}

.waiting-item:nth-child(1) {
  animation-delay: 0.1s;
}

.waiting-item:nth-child(2) {
  animation-delay: 0.2s;
}

.waiting-item:nth-child(3) {
  animation-delay: 0.3s;
}

.waiting-item:hover {
  background: rgba(187, 160, 122, 0.1);
  transform: translateX(-8px);
  border-color: rgba(187, 160, 122, 0.3);
  box-shadow: 0 4px 16px rgba(187, 160, 122, 0.15);
}

.waiting-item.is-you {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.25) 0%, rgba(187, 160, 122, 0.15) 100%);
  border: 2px solid #BBA07A;
  box-shadow: 0 4px 16px rgba(187, 160, 122, 0.3);
}

.waiting-item.is-next {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%);
  border: 2px solid rgba(59, 130, 246, 0.4);
}

.waiting-item.is-called {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.12) 100%);
  border: 2px solid rgba(59, 130, 246, 0.5);
  box-shadow: 0 4px 16px rgba(59, 130, 246, 0.2);
}

.waiting-item.is-serving {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.12) 100%);
  border: 2px solid rgba(16, 185, 129, 0.5);
  box-shadow: 0 4px 16px rgba(16, 185, 129, 0.2);
}

.waiting-number-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 56px;
  height: 56px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.3) 0%, rgba(187, 160, 122, 0.2) 100%);
  color: #BBA07A;
  font-weight: 800;
  font-size: 1.5rem;
  border: 2px solid rgba(187, 160, 122, 0.4);
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.2);
}

.waiting-item.is-next .waiting-number-badge {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.4) 0%, rgba(59, 130, 246, 0.3) 100%);
  border-color: rgba(59, 130, 246, 0.6);
  color: #3b82f6;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.waiting-ticket-info {
  flex: 1;
  min-width: 0;
}

.waiting-ticket-number {
  font-weight: 800;
  color: #BBA07A;
  font-size: 1.5rem;
  font-family: 'Courier New', 'SF Mono', monospace;
  margin-bottom: 0.375rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.ticket-icon {
  width: 20px;
  height: 20px;
  color: #BBA07A;
  opacity: 0.8;
}

.waiting-customer-name {
  color: #ffffff;
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.375rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.waiting-service-name {
  color: rgba(255, 255, 255, 0.7);
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-weight: 500;
}

.waiting-status {
  flex-shrink: 0;
}

.you-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #BBA07A 0%, rgba(187, 160, 122, 0.8) 100%);
  color: white;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.875rem;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

.you-badge CIcon {
  width: 14px;
  height: 14px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.875rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.status-badge CIcon {
  width: 14px;
  height: 14px;
}

.called-badge {
  background: linear-gradient(135deg, #3b82f6 0%, rgba(59, 130, 246, 0.8) 100%);
  color: white;
}

.serving-badge {
  background: linear-gradient(135deg, #10b981 0%, rgba(16, 185, 129, 0.8) 100%);
  color: white;
}

.waiting-badge {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.9) 0%, rgba(255, 193, 7, 0.7) 100%);
  color: #000;
}

.complete-service-btn-wrapper {
  margin-top: 2rem;
  display: flex;
  justify-content: center;
}

.complete-service-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #10b981 0%, rgba(16, 185, 129, 0.9) 100%);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 16px;
  color: white;
  font-weight: 700;
  font-size: 1.125rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 
    0 6px 20px rgba(16, 185, 129, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.complete-service-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.95) 0%, #10b981 100%);
  transform: translateY(-2px);
  box-shadow: 
    0 8px 24px rgba(16, 185, 129, 0.5),
    inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.complete-service-btn:active:not(:disabled) {
  transform: translateY(0);
}

.complete-service-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.complete-service-btn .btn-icon {
  width: 20px;
  height: 20px;
}


/* Scrollbar Styling */
.waiting-list-container::-webkit-scrollbar {
  width: 10px;
}

.waiting-list-container::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 5px;
}

.waiting-list-container::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #BBA07A 0%, rgba(187, 160, 122, 0.8) 100%);
  border-radius: 5px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.waiting-list-container::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.9) 0%, #BBA07A 100%);
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

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
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

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Loading State */
.queue-display.is-loading {
  opacity: 0.7;
}

.queue-display.is-loading::after {
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

/* Responsive */
@media (max-width: 1400px) {
  .display-title {
    font-size: 3rem;
  }

  .ticket-number-large {
    font-size: 3.5rem;
  }

  .customer-name-large {
    font-size: 2rem;
  }
}

@media (max-width: 1200px) {
  .display-content {
    grid-template-columns: 1fr;
  }

  .next-section,
  .waiting-section {
    grid-column: 1;
  }

}

@media (max-width: 768px) {
  .queue-display {
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

  .ticket-number-large {
    font-size: 2.5rem;
  }

  .customer-name-large {
    font-size: 1.5rem;
  }

  .customer-name-display {
    font-size: 3.5rem;
  }

  .section-title {
    font-size: 1.5rem;
  }

}

/* RTL Support */
[dir="rtl"] .waiting-item:hover {
  transform: translateX(8px);
}

[dir="rtl"] .header-info {
  text-align: left;
  align-items: flex-start;
}

[dir="ltr"] .header-info {
  text-align: right;
  align-items: flex-end;
}
</style>
