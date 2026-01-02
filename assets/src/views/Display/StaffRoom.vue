<template>
  <div class="staff-room-display" :class="{ 'is-loading': loading }">
    <!-- Header -->
    <div class="display-header">
      <div class="header-content">
        <div class="logo-section">
          <div class="logo-icon-wrapper">
            <CIcon icon="cil-people" class="logo-icon" />
          </div>
          <div class="title-group">
            <h1 class="display-title">Staff Room Display</h1>
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
          <button class="refresh-btn" @click="loadData" :disabled="loading">
            <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
            <span>Refresh</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="display-content">
      <!-- Staff Status Grid -->
      <div class="display-section staff-grid-section">
        <div class="section-header">
          <div class="section-title-wrapper">
            <div class="section-icon-wrapper staff-icon">
              <CIcon icon="cil-people" class="section-icon" />
            </div>
            <h2 class="section-title">
              Staff Status
              <span class="staff-count-badge">{{ staffCards.length }}</span>
            </h2>
          </div>
        </div>
        <div v-if="staffCards.length" class="staff-grid-container">
          <div
            v-for="staff in staffCards"
            :key="staff.id"
            class="staff-card-display"
            :class="{ 'busy': staff.busy, 'available': !staff.busy }"
          >
            <div class="staff-card-glow"></div>
            <div class="staff-avatar-large">
              <div class="avatar-ring"></div>
              <CIcon :icon="staff.busy ? 'cil-user' : 'cil-check-circle'" class="avatar-icon" />
            </div>
            <div class="staff-info-display">
              <div class="staff-name-large">{{ staff.name }}</div>
              <div class="staff-role-display">{{ staff.position || 'Staff' }}</div>
              <div class="staff-status-badge" :class="{ 'busy': staff.busy, 'available': !staff.busy }">
                <CIcon :icon="staff.busy ? 'cil-user' : 'cil-check-circle'" class="status-icon" />
                {{ staff.busy ? 'Busy' : 'Available' }}
              </div>
              <div v-if="staff.currentCustomer" class="current-info">
                <div class="current-customer">
                  <CIcon icon="cil-user" class="info-icon-xs" />
                  <span>{{ staff.currentCustomer }}</span>
                </div>
                <div v-if="staff.currentService" class="current-service">
                  <CIcon icon="cil-spreadsheet" class="info-icon-xs" />
                  <span>{{ staff.currentService }}</span>
                </div>
              </div>
              <div v-else class="idle-state">
                <CIcon icon="cil-pause" class="idle-icon" />
                <span>Ready for next customer</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="empty-staff">
          <div class="empty-icon-large">
            <CIcon icon="cil-people" />
          </div>
          <p class="empty-text">No staff available</p>
          <p class="empty-subtext">Please add staff members</p>
        </div>
      </div>

      <!-- Waiting Calls Section -->
      <div class="display-section waiting-calls-section">
        <div class="section-header">
          <div class="section-title-wrapper">
            <div class="section-icon-wrapper waiting-icon">
              <CIcon icon="cil-bell" class="section-icon" />
            </div>
            <h2 class="section-title">
              Waiting Calls
              <span class="waiting-count-badge" v-if="waitingCalls.length > 0">{{ waitingCalls.length }}</span>
            </h2>
          </div>
        </div>
        <div v-if="waitingCalls.length" class="waiting-calls-container">
          <div
            v-for="(call, index) in waitingCalls"
            :key="call.id"
            class="waiting-call-item"
            :class="{ 'urgent': index === 0 }"
          >
            <div class="call-number-badge">{{ index + 1 }}</div>
            <div class="call-info">
              <div class="call-ticket-number">
                <CIcon icon="cil-ticket" class="ticket-icon" />
                #{{ call.ticket_number || call.ticket_id }}
              </div>
              <div class="call-customer-name">
                <CIcon icon="cil-user" class="info-icon-xs" />
                {{ call.customer_name || 'Customer' }}
              </div>
              <div class="call-service-name" v-if="call.service_name">
                <CIcon icon="cil-spreadsheet" class="info-icon-xs" />
                {{ call.service_name }}
              </div>
              <div class="call-staff-name" v-if="call.staff_name">
                <CIcon icon="cil-user-female" class="info-icon-xs" />
                Staff: {{ call.staff_name }}
              </div>
            </div>
          </div>
        </div>
        <div v-else class="empty-waiting">
          <div class="empty-icon-medium">
            <CIcon icon="cil-check-circle" />
          </div>
          <p class="empty-text">No calls waiting</p>
          <p class="empty-subtext">All staff are available</p>
        </div>
      </div>

      <!-- Today's Bookings Section -->
      <div class="display-section bookings-section">
        <div class="section-header">
          <div class="section-title-wrapper">
            <div class="section-icon-wrapper bookings-icon">
              <CIcon icon="cil-calendar" class="section-icon" />
            </div>
            <h2 class="section-title">Today's Bookings</h2>
          </div>
        </div>
        <div v-if="bookings.length" class="bookings-container">
          <div
            v-for="booking in bookings"
            :key="booking.id"
            class="booking-item"
          >
            <div class="booking-time">
              <CIcon icon="cil-clock" class="time-icon" />
              {{ booking.booking_time }}
            </div>
            <div class="booking-info">
              <div class="booking-customer">{{ booking.customer_name }}</div>
              <div class="booking-service">{{ booking.service_name }}</div>
              <div class="booking-staff">{{ booking.staff_name }}</div>
            </div>
            <div class="booking-status">
              <span class="status-badge" :class="getStatusClass(booking.status)">
                {{ booking.status }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="empty-bookings">
          <div class="empty-icon-medium">
            <CIcon icon="cil-calendar" />
          </div>
          <p class="empty-text">No bookings today</p>
          <p class="empty-subtext">Schedule is clear</p>
        </div>
      </div>
    </div>

    <!-- Footer Stats -->
    <div class="display-footer">
      <div class="footer-stat">
        <div class="stat-icon-wrapper available-stat">
          <CIcon icon="cil-check-circle" />
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ availableCount }}</div>
          <div class="stat-label">Available</div>
        </div>
      </div>
      <div class="footer-stat">
        <div class="stat-icon-wrapper busy-stat">
          <CIcon icon="cil-user" />
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ busyCount }}</div>
          <div class="stat-label">Busy</div>
        </div>
      </div>
      <div class="footer-stat">
        <div class="stat-icon-wrapper waiting-stat">
          <CIcon icon="cil-bell" />
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ waitingCalls.length }}</div>
          <div class="stat-label">Waiting Calls</div>
        </div>
      </div>
      <div class="footer-stat">
        <div class="stat-icon-wrapper bookings-stat">
          <CIcon icon="cil-calendar" />
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ bookings.length }}</div>
          <div class="stat-label">Today's Bookings</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { CIcon } from '@coreui/icons-vue';
import api from '@/utils/api';

const staff = ref([]);
const workerCalls = ref([]);
const bookings = ref([]);
const loading = ref(false);
const currentTime = ref('');
const currentDate = ref('');

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

const loadStaffAndCalls = async () => {
  loading.value = true;
  try {
    const [staffRes, callsRes] = await Promise.all([
      api.get('/staff', { params: { per_page: 100 } }),
      api.get('/worker-calls', { params: { per_page: 200 } }),
    ]);

    staff.value = staffRes.data?.data?.items || [];
    workerCalls.value = callsRes.data?.data?.items || [];
  } catch (error) {
    console.error('Error loading staff/calls', error);
    staff.value = [];
    workerCalls.value = [];
  } finally {
    loading.value = false;
  }
};

const loadBookings = async () => {
  try {
    const res = await api.get('/bookings', {
      params: {
        booking_date: today(),
        per_page: 50,
      },
    });
    bookings.value = res.data?.data?.items || [];
  } catch (error) {
    console.error('Error loading bookings', error);
    bookings.value = [];
  }
};

const loadData = async () => {
  await Promise.all([loadStaffAndCalls(), loadBookings()]);
};

const staffCards = computed(() => {
  return staff.value.map((s) => {
    const currentCall = workerCalls.value.find(
      (c) =>
        c.staff_id === s.id &&
        ['pending', 'customer_called', 'staff_called'].includes(c.status)
    );

    const busy =
      currentCall &&
      currentCall.queue_status &&
      currentCall.queue_status !== 'completed';

    return {
      id: s.id,
      name: s.name,
      position: s.position,
      busy,
      currentService: currentCall?.service_name || null,
      currentCustomer: currentCall?.customer_name || null,
      currentCall,
    };
  });
});

const waitingCalls = computed(() =>
  workerCalls.value.filter(
    (c) => c.queue_status === 'waiting' || c.status === 'pending'
  )
);

const availableCount = computed(() => staffCards.value.filter(s => !s.busy).length);
const busyCount = computed(() => staffCards.value.filter(s => s.busy).length);

const getStatusClass = (status) => {
  const statusMap = {
    pending: 'pending',
    confirmed: 'confirmed',
    completed: 'completed',
    cancelled: 'cancelled',
  };
  return statusMap[status] || 'pending';
};

let timeInterval = null;
let refreshInterval = null;

onMounted(() => {
  updateTime();
  timeInterval = setInterval(updateTime, 1000);
  loadData();
  
  // Auto-refresh every 10 seconds
  refreshInterval = setInterval(() => {
    if (!loading.value) {
      loadData();
    }
  }, 10000);
});

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval);
  if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<style scoped>
.staff-room-display {
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

.staff-room-display::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 15% 40%, rgba(142, 126, 120, 0.12) 0%, transparent 50%),
    radial-gradient(circle at 85% 70%, rgba(142, 126, 120, 0.08) 0%, transparent 50%),
    linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
  pointer-events: none;
  z-index: 0;
}

.staff-room-display > * {
  position: relative;
  z-index: 1;
}

/* Header */
.display-header {
  margin-bottom: 2.5rem;
  border-bottom: 2px solid rgba(142, 126, 120, 0.3);
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
  background: linear-gradient(135deg, #8E7E78 0%, rgba(142, 126, 120, 0.8) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(142, 126, 120, 0.4);
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
  color: #8E7E78;
  text-shadow: 0 4px 16px rgba(142, 126, 120, 0.5);
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
  color: #8E7E78;
  font-weight: 700;
  font-family: 'Courier New', 'SF Mono', monospace;
  text-shadow: 0 2px 12px rgba(142, 126, 120, 0.4);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.info-icon {
  width: 20px;
  height: 20px;
  color: #8E7E78;
  opacity: 0.8;
}

.refresh-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.75rem;
  background: linear-gradient(135deg, rgba(142, 126, 120, 0.2) 0%, rgba(142, 126, 120, 0.15) 100%);
  border: 2px solid rgba(142, 126, 120, 0.4);
  border-radius: 12px;
  color: #8E7E78;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  margin-top: 0.5rem;
  backdrop-filter: blur(10px);
}

.refresh-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, rgba(142, 126, 120, 0.3) 0%, rgba(142, 126, 120, 0.25) 100%);
  border-color: #8E7E78;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(142, 126, 120, 0.4);
}

.refresh-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.refresh-btn CIcon {
  width: 20px;
  height: 20px;
}

.refresh-btn .spinning {
  animation: spin 1s linear infinite;
}

/* Main Content */
.display-content {
  flex: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: auto 1fr;
  gap: 2rem;
  overflow: hidden;
  margin-bottom: 2rem;
}

.display-section {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  padding: 2rem;
  backdrop-filter: blur(20px);
  border: 1px solid rgba(142, 126, 120, 0.2);
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
  background: linear-gradient(90deg, transparent 0%, rgba(142, 126, 120, 0.5) 50%, transparent 100%);
}

.display-section:hover {
  border-color: rgba(142, 126, 120, 0.4);
  box-shadow: 
    0 12px 40px rgba(142, 126, 120, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
  transform: translateY(-2px);
}

.staff-grid-section {
  grid-column: 1 / -1;
  animation-delay: 0.1s;
}

.waiting-calls-section {
  grid-column: 1;
  overflow-y: auto;
  animation-delay: 0.2s;
}

.bookings-section {
  grid-column: 2;
  overflow-y: auto;
  animation-delay: 0.3s;
}

.section-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(142, 126, 120, 0.2);
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

.staff-icon {
  background: linear-gradient(135deg, rgba(142, 126, 120, 0.2) 0%, rgba(142, 126, 120, 0.1) 100%);
  border: 2px solid rgba(142, 126, 120, 0.3);
}

.waiting-icon {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 193, 7, 0.1) 100%);
  border: 2px solid rgba(255, 193, 7, 0.3);
}

.bookings-icon {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%);
  border: 2px solid rgba(59, 130, 246, 0.3);
}

.section-icon {
  width: 28px;
  height: 28px;
  color: #8E7E78;
}

.waiting-icon .section-icon {
  color: #f59e0b;
}

.bookings-icon .section-icon {
  color: #3b82f6;
}

.section-title {
  font-size: 1.75rem;
  margin: 0;
  color: #8E7E78;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  letter-spacing: -0.5px;
}

.waiting-calls-section .section-title {
  color: #f59e0b;
}

.bookings-section .section-title {
  color: #3b82f6;
}

.staff-count-badge,
.waiting-count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  padding: 0 0.875rem;
  background: linear-gradient(135deg, #8E7E78 0%, rgba(142, 126, 120, 0.8) 100%);
  color: white;
  border-radius: 18px;
  font-size: 1rem;
  font-weight: 800;
  margin-left: 0.5rem;
  box-shadow: 0 4px 12px rgba(142, 126, 120, 0.4);
}

.waiting-count-badge {
  background: linear-gradient(135deg, #f59e0b 0%, rgba(245, 158, 11, 0.8) 100%);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

/* Staff Grid */
.staff-grid-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
  max-height: 100%;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.staff-card-display {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2.5rem 2rem;
  background: rgba(255, 255, 255, 0.04);
  border-radius: 24px;
  border: 2px solid transparent;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(20px);
  animation: scaleIn 0.6s ease-out;
  overflow: hidden;
}

.staff-card-glow {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(142, 126, 120, 0.1) 0%, transparent 70%);
  opacity: 0;
  transition: opacity 0.4s;
  pointer-events: none;
}

.staff-card-display:hover .staff-card-glow {
  opacity: 1;
}

.staff-card-display.available {
  border-color: rgba(16, 185, 129, 0.5);
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(16, 185, 129, 0.06) 50%, rgba(255, 255, 255, 0.02) 100%);
  box-shadow: 
    0 8px 32px rgba(16, 185, 129, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.staff-card-display.busy {
  border-color: rgba(239, 68, 68, 0.5);
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(239, 68, 68, 0.06) 50%, rgba(255, 255, 255, 0.02) 100%);
  box-shadow: 
    0 8px 32px rgba(239, 68, 68, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.staff-card-display:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 
    0 16px 48px rgba(142, 126, 120, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.staff-card-display.available:hover {
  box-shadow: 
    0 16px 48px rgba(16, 185, 129, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.staff-card-display.busy:hover {
  box-shadow: 
    0 16px 48px rgba(239, 68, 68, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.staff-avatar-large {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: linear-gradient(135deg, #8E7E78 0%, rgba(142, 126, 120, 0.9) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  box-shadow: 
    0 8px 24px rgba(142, 126, 120, 0.5),
    inset 0 2px 4px rgba(255, 255, 255, 0.2);
  transition: all 0.4s;
  z-index: 1;
}

.staff-card-display.available .staff-avatar-large {
  background: linear-gradient(135deg, #10b981 0%, rgba(16, 185, 129, 0.95) 100%);
  box-shadow: 
    0 8px 24px rgba(16, 185, 129, 0.5),
    inset 0 2px 4px rgba(255, 255, 255, 0.2);
  animation: pulse 2s ease-in-out infinite;
}

.staff-card-display.busy .staff-avatar-large {
  background: linear-gradient(135deg, #ef4444 0%, rgba(239, 68, 68, 0.95) 100%);
  box-shadow: 
    0 8px 24px rgba(239, 68, 68, 0.5),
    inset 0 2px 4px rgba(255, 255, 255, 0.2);
}

.staff-card-display:hover .staff-avatar-large {
  transform: scale(1.1) rotate(5deg);
}

.avatar-ring {
  position: absolute;
  top: -8px;
  left: -8px;
  right: -8px;
  bottom: -8px;
  border-radius: 50%;
  border: 3px solid rgba(255, 255, 255, 0.3);
  animation: rotate 3s linear infinite;
}

.staff-card-display.available .avatar-ring {
  border-color: rgba(16, 185, 129, 0.5);
}

.staff-card-display.busy .avatar-ring {
  border-color: rgba(239, 68, 68, 0.5);
}

.avatar-icon {
  width: 60px;
  height: 60px;
  color: white;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

.staff-info-display {
  text-align: center;
  width: 100%;
  position: relative;
  z-index: 1;
}

.staff-name-large {
  font-size: 2rem;
  font-weight: 900;
  color: #ffffff;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  letter-spacing: -0.5px;
  line-height: 1.2;
}

.staff-role-display {
  font-size: 1.125rem;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 1.25rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.staff-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.625rem 1.25rem;
  border-radius: 25px;
  font-weight: 800;
  font-size: 0.9375rem;
  margin-bottom: 1.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.staff-status-badge.available {
  background: linear-gradient(135deg, #10b981 0%, rgba(16, 185, 129, 0.9) 100%);
  color: white;
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}

.staff-status-badge.busy {
  background: linear-gradient(135deg, #ef4444 0%, rgba(239, 68, 68, 0.9) 100%);
  color: white;
  box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
}

.status-icon {
  width: 18px;
  height: 18px;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

.current-info {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.current-customer,
.current-service {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  font-size: 0.9375rem;
  color: rgba(255, 255, 255, 0.9);
  margin-top: 0.625rem;
  font-weight: 500;
}

.current-customer {
  font-weight: 600;
  font-size: 1rem;
}

.idle-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.875rem;
  font-style: italic;
}

.idle-icon {
  width: 24px;
  height: 24px;
  opacity: 0.5;
}

.info-icon-xs {
  width: 16px;
  height: 16px;
  color: rgba(255, 255, 255, 0.6);
}

/* Waiting Calls */
.waiting-calls-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 100%;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.waiting-call-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.25rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 14px;
  border: 2px solid transparent;
  transition: all 0.3s;
  backdrop-filter: blur(10px);
  animation: slideInRight 0.5s ease-out;
}

.waiting-call-item.urgent {
  border-color: rgba(255, 193, 7, 0.5);
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 193, 7, 0.08) 100%);
  box-shadow: 0 4px 16px rgba(255, 193, 7, 0.3);
}

.waiting-call-item:hover {
  transform: translateX(-8px);
  border-color: rgba(255, 193, 7, 0.4);
  box-shadow: 0 4px 16px rgba(255, 193, 7, 0.2);
}

.call-number-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 56px;
  height: 56px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.4) 0%, rgba(255, 193, 7, 0.3) 100%);
  color: #f59e0b;
  font-weight: 800;
  font-size: 1.5rem;
  border: 2px solid rgba(255, 193, 7, 0.5);
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.call-info {
  flex: 1;
  min-width: 0;
}

.call-ticket-number {
  font-weight: 800;
  color: #f59e0b;
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
  color: #f59e0b;
  opacity: 0.8;
}

.call-customer-name {
  color: #ffffff;
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.375rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.call-service-name,
.call-staff-name {
  color: rgba(255, 255, 255, 0.7);
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-weight: 500;
  margin-top: 0.25rem;
}

/* Bookings */
.bookings-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 100%;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.booking-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.25rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 14px;
  border: 2px solid rgba(59, 130, 246, 0.2);
  transition: all 0.3s;
  backdrop-filter: blur(10px);
  animation: slideInLeft 0.5s ease-out;
}

.booking-item:hover {
  transform: translateX(-8px);
  border-color: rgba(59, 130, 246, 0.4);
  box-shadow: 0 4px 16px rgba(59, 130, 246, 0.2);
}

.booking-time {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 120px;
  font-size: 1.25rem;
  font-weight: 700;
  color: #3b82f6;
  font-family: 'Courier New', 'SF Mono', monospace;
}

.time-icon {
  width: 20px;
  height: 20px;
  color: #3b82f6;
}

.booking-info {
  flex: 1;
  min-width: 0;
}

.booking-customer {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 0.375rem;
}

.booking-service {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 0.25rem;
}

.booking-staff {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.6);
}

.booking-status {
  flex-shrink: 0;
}

.status-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.875rem;
  text-transform: capitalize;
}

.status-badge.pending {
  background: linear-gradient(135deg, #f59e0b 0%, rgba(245, 158, 11, 0.8) 100%);
  color: white;
}

.status-badge.confirmed {
  background: linear-gradient(135deg, #3b82f6 0%, rgba(59, 130, 246, 0.8) 100%);
  color: white;
}

.status-badge.completed {
  background: linear-gradient(135deg, #10b981 0%, rgba(16, 185, 129, 0.8) 100%);
  color: white;
}

.status-badge.cancelled {
  background: linear-gradient(135deg, #ef4444 0%, rgba(239, 68, 68, 0.8) 100%);
  color: white;
}

/* Empty States */
.empty-staff,
.empty-waiting,
.empty-bookings {
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

/* Footer Stats */
.display-footer {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
  padding-top: 1.5rem;
  border-top: 2px solid rgba(142, 126, 120, 0.2);
  animation: fadeInUp 1s ease-out;
}

.footer-stat {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 16px;
  border: 1px solid rgba(142, 126, 120, 0.15);
  transition: all 0.3s;
  backdrop-filter: blur(10px);
}

.footer-stat:hover {
  background: rgba(142, 126, 120, 0.08);
  border-color: rgba(142, 126, 120, 0.3);
  transform: translateY(-4px);
  box-shadow: 0 6px 20px rgba(142, 126, 120, 0.2);
}

.stat-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.available-stat {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%);
  border: 2px solid rgba(16, 185, 129, 0.3);
}

.busy-stat {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1) 100%);
  border: 2px solid rgba(239, 68, 68, 0.3);
}

.waiting-stat {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 193, 7, 0.1) 100%);
  border: 2px solid rgba(255, 193, 7, 0.3);
}

.bookings-stat {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%);
  border: 2px solid rgba(59, 130, 246, 0.3);
}

.stat-icon-wrapper CIcon {
  width: 28px;
  height: 28px;
  color: #8E7E78;
}

.available-stat CIcon {
  color: #10b981;
}

.busy-stat CIcon {
  color: #ef4444;
}

.waiting-stat CIcon {
  color: #f59e0b;
}

.bookings-stat CIcon {
  color: #3b82f6;
}

.stat-content {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 800;
  color: #8E7E78;
  margin-bottom: 0.375rem;
  text-shadow: 0 2px 8px rgba(142, 126, 120, 0.3);
  line-height: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.7);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Scrollbar Styling */
.staff-grid-container::-webkit-scrollbar,
.waiting-calls-container::-webkit-scrollbar,
.bookings-container::-webkit-scrollbar {
  width: 10px;
}

.staff-grid-container::-webkit-scrollbar-track,
.waiting-calls-container::-webkit-scrollbar-track,
.bookings-container::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 5px;
}

.staff-grid-container::-webkit-scrollbar-thumb,
.waiting-calls-container::-webkit-scrollbar-thumb,
.bookings-container::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #8E7E78 0%, rgba(142, 126, 120, 0.8) 100%);
  border-radius: 5px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.staff-grid-container::-webkit-scrollbar-thumb:hover,
.waiting-calls-container::-webkit-scrollbar-thumb:hover,
.bookings-container::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, rgba(142, 126, 120, 0.9) 0%, #8E7E78 100%);
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
    box-shadow: 
      0 8px 24px rgba(16, 185, 129, 0.5),
      inset 0 2px 4px rgba(255, 255, 255, 0.2);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 
      0 12px 32px rgba(16, 185, 129, 0.6),
      inset 0 2px 4px rgba(255, 255, 255, 0.25);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Loading State */
.staff-room-display.is-loading {
  opacity: 0.7;
}

.staff-room-display.is-loading::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 60px;
  height: 60px;
  margin: -30px 0 0 -30px;
  border: 4px solid rgba(142, 126, 120, 0.3);
  border-top-color: #8E7E78;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  z-index: 10;
}

/* Responsive */
@media (max-width: 1400px) {
  .display-title {
    font-size: 3rem;
  }

  .staff-name-large {
    font-size: 1.5rem;
  }
}

@media (max-width: 1200px) {
  .display-content {
    grid-template-columns: 1fr;
  }

  .waiting-calls-section,
  .bookings-section {
    grid-column: 1;
  }

  .display-footer {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .staff-room-display {
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

  .section-title {
    font-size: 1.5rem;
  }

  .display-footer {
    grid-template-columns: 1fr;
  }

  .footer-stat {
    flex-direction: column;
    text-align: center;
  }

  .staff-grid-container {
    grid-template-columns: 1fr;
  }
}
</style>
