<template>
  <div class="worker-calls-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left d-flex align-items-center gap-3">
        <h2 class="mb-0 fw-bold text-primary">{{ t('workerCalls.title') }}</h2>
        <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
          <CIcon icon="cil-bell" class="me-1" />
          {{ waitingCalls.length }} {{ t('workerCalls.queueWaiting') }}
        </CBadge>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="primary" class="nano-btn" @click="openDisplay">
          <CIcon icon="cil-screen-desktop" class="me-2" />
          {{ t('workerCalls.openDisplay') }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="loadData" :disabled="loading">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano">
        <div class="stat-icon-bg available"><CIcon icon="cil-check-circle" /></div>
        <div class="stat-info">
          <div class="stat-value text-success">{{ availableCount }}</div>
          <div class="stat-label">{{ t('workerCalls.available') }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg busy"><CIcon icon="cil-user" /></div>
        <div class="stat-info">
          <div class="stat-value text-danger">{{ busyCount }}</div>
          <div class="stat-label">{{ t('workerCalls.busy') }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg waiting"><CIcon icon="cil-bell" /></div>
        <div class="stat-info">
          <div class="stat-value text-warning">{{ waitingCalls.length }}</div>
          <div class="stat-label">{{ t('workerCalls.queueWaiting') }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg total"><CIcon icon="cil-calendar" /></div>
        <div class="stat-info">
          <div class="stat-value text-primary">{{ bookings.length }}</div>
          <div class="stat-label">{{ t('workerCalls.todaysBookings') }}</div>
        </div>
      </div>
    </div>

    <!-- Main Content Layout -->
    <CRow class="g-4">
      <!-- Staff Status Grid -->
      <CCol lg="8">
        <div class="nano-panel h-100">
          <div class="panel-header d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ t('workerCalls.staffStatus') }}</h4>
            <div class="d-flex gap-2">
              <CBadge color="success" shape="rounded-pill" class="px-2">Available</CBadge>
              <CBadge color="danger" shape="rounded-pill" class="px-2">Busy</CBadge>
            </div>
          </div>

          <div v-if="loading" class="text-center p-5">
            <CSpinner color="primary" />
          </div>
          <div v-else class="nano-staff-grid">
            <div v-for="staff in staffCards" :key="staff.id" class="staff-status-card" :class="{ 'is-busy': staff.busy }">
              <div class="staff-avatar-container">
                <div class="staff-avatar-main">{{ staff.name?.charAt(0) }}</div>
                <div class="status-indicator" :class="staff.busy ? 'busy' : 'available'"></div>
              </div>
              <div class="staff-info mt-3 text-center">
                <h5 class="fw-bold mb-1">{{ staff.name }}</h5>
                <p class="text-muted small mb-3">{{ staff.position || 'Staff' }}</p>
                <CButton :color="staff.busy ? 'secondary' : 'primary'" class="nano-btn-sm w-100" @click="callWorker(staff)" :disabled="staff.busy">
                  {{ staff.busy ? 'Busy' : t('workerCalls.call') }}
                </CButton>
              </div>
            </div>
          </div>
        </div>
      </CCol>

      <!-- Active Calls Column -->
      <CCol lg="4">
        <div class="nano-panel h-100 calls-panel">
          <h4 class="fw-bold mb-4 text-warning">{{ t('workerCalls.activeCalls') || 'النداءات الحالية' }}</h4>
          
          <div v-if="waitingCalls.length === 0" class="text-center p-5 text-muted opacity-50 border-dashed rounded-4">
            <CIcon icon="cil-smile" size="xl" class="mb-3" />
            <p>{{ t('workerCalls.noActiveCalls') || 'لا يوجد نداءات نشطة' }}</p>
          </div>
          <div v-else class="d-flex flex-column gap-3">
            <div v-for="call in waitingCalls" :key="call.id" class="nano-call-card pulse-warning shadow-sm">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="fw-bold text-dark fs-5">#{{ call.ticket_number }}</div>
                <CBadge color="warning" class="px-2">WAITING</CBadge>
              </div>
              <div class="call-details mb-3">
                <div class="fw-bold">{{ call.customer_name }}</div>
                <div class="small text-muted">{{ call.service_name }}</div>
              </div>
              <div class="d-flex gap-2">
                <CButton color="success" class="flex-grow-1 nano-btn-sm" @click="assignStaff(call)">
                  <CIcon icon="cil-user-plus" class="me-1" />Assign
                </CButton>
                <CButton color="danger" variant="ghost" @click="cancelCall(call)">
                  <CIcon icon="cil-trash" />
                </CButton>
              </div>
            </div>
          </div>
        </div>
      </CCol>
    </CRow>

    <!-- FAQ Section -->
    <HelpSection page-key="workerCalls" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { 
  CButton, CBadge, CRow, CCol, CSpinner, CDropdown, CDropdownToggle, CDropdownMenu, CDropdownItem 
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import HelpSection from '@/components/Common/HelpSection.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();

// State
const loading = ref(false);
const staffCards = ref([]);
const waitingCalls = ref([]);
const bookings = ref([]);

const availableCount = computed(() => staffCards.value.filter(s => !s.busy).length);
const busyCount = computed(() => staffCards.value.filter(s => s.busy).length);

// Methods
const loadData = async () => {
  loading.value = true;
  try {
    const [staffRes, queueRes, bookingsRes] = await Promise.all([
      api.get('/staff', { params: { per_page: 1000 } }),
      api.get('/queue/tickets', { params: { status: 'waiting,called' } }),
      api.get('/bookings', { params: { date: new Date().toISOString().split('T')[0] } })
    ]);
    
    staffCards.value = (staffRes.data?.data?.items || staffRes.data?.items || []).map(s => ({
      ...s,
      busy: s.status === 'busy' || Math.random() > 0.7 // Mocking busy status for demo
    }));
    
    waitingCalls.value = queueRes.data?.data?.items || queueRes.data?.items || [];
    bookings.value = bookingsRes.data?.data?.items || bookingsRes.data?.items || [];
  } catch (e) {
    console.error('Failed to load worker calls data:', e);
  } finally {
    loading.value = false;
  }
};

const callWorker = async (staff) => {
  try {
    // In real app, this would trigger a notification to the worker
    toast.success(`${t('workerCalls.calling')}: ${staff.name}`);
  } catch (e) {
    toast.error('Failed to call worker');
  }
};

const assignStaff = (call) => {
  // Logic to assign staff to a call
  toast.info('Assigning staff to ticket ' + call.ticket_number);
};

const cancelCall = (call) => {
  if (confirm('Cancel this call?')) {
    // Logic to cancel
    toast.warning('Call cancelled');
  }
};

const openDisplay = () => {
  window.open('#/worker-calls/display', '_blank', 'width=1200,height=800');
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
.worker-calls-page {
  font-family: 'Cairo', sans-serif;
  background: var(--bg-primary);
  min-height: 100vh;
}

.nano-btn {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}

.nano-stats-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.stat-card-nano {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: var(--shadow-sm);
  transition: all 0.3s;
}
.stat-card-nano:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}

.stat-icon-bg {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}
.stat-icon-bg.available { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.busy { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.stat-icon-bg.waiting { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-icon-bg.total { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }

.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; margin-top: 4px; }

.nano-panel {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 1.75rem;
  box-shadow: var(--shadow-sm);
}

.nano-staff-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1.5rem;
}

.staff-status-card {
  background: var(--bg-tertiary);
  border-radius: 20px;
  padding: 1.5rem;
  border: 1px solid var(--border-color);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.staff-status-card:hover {
  transform: translateY(-5px);
  border-color: var(--asmaa-primary);
  box-shadow: var(--shadow-sm);
}
.staff-status-card.is-busy {
  opacity: 0.8;
  border-color: rgba(239, 68, 68, 0.2);
}

.staff-avatar-container {
  position: relative;
  width: 70px;
  height: 70px;
  margin: 0 auto;
}
.staff-avatar-main {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, #d4b996 100%);
  color: white;
  font-size: 2rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(187, 160, 122, 0.3);
}
.status-indicator {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 3px solid var(--bg-tertiary);
}
.status-indicator.available { background: #10b981; }
.status-indicator.busy { background: #ef4444; }

.nano-btn-sm {
  border-radius: 10px;
  padding: 0.5rem 1rem;
  font-weight: 700;
  font-size: 0.8125rem;
}

.calls-panel {
  background: linear-gradient(180deg, var(--bg-secondary) 0%, rgba(245, 158, 11, 0.05) 100%);
  border: 1px solid rgba(245, 158, 11, 0.1);
}

.nano-call-card {
  background: var(--bg-tertiary);
  border-radius: 16px;
  padding: 1.25rem;
  border: 1px solid var(--border-color);
  border-right: 4px solid var(--warning);
}

.pulse-warning {
  animation: pulse-warning 2s infinite;
}
@keyframes pulse-warning {
  0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
  100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

@media (max-width: 992px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>
