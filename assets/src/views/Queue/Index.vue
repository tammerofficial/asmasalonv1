<template>
  <div class="queue-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left d-flex align-items-center gap-3">
        <h2 class="mb-0 fw-bold text-primary">{{ t('queue.title') }}</h2>
        <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
          <CIcon icon="cil-people" class="me-1" />
          {{ waitingCount }} {{ t('queue.waiting') }}
        </CBadge>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="primary" class="nano-btn" @click="openAddQueueModal">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('queue.addQueue') || 'إضافة للطابور' }}
        </CButton>
        <CButton color="success" class="nano-btn-success" @click="callNext" :disabled="waitingCount === 0 || loading">
          <CIcon icon="cil-bell" class="me-2" />
          {{ t('queue.next') }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="loadTickets" :disabled="loading">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
        <CButton color="secondary" variant="ghost" @click="openDisplayMode">
          <CIcon icon="cil-screen-desktop" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano" @click="filterByStatus('waiting')">
        <div class="stat-icon-bg waiting"><CIcon icon="cil-clock" /></div>
        <div class="stat-info">
          <div class="stat-value text-warning">{{ waitingCount }}</div>
          <div class="stat-label">{{ t('queue.waiting') }}</div>
        </div>
      </div>
      <div class="stat-card-nano" @click="filterByStatus('serving')">
        <div class="stat-icon-bg serving"><CIcon icon="cil-user" /></div>
        <div class="stat-info">
          <div class="stat-value text-info">{{ servingCount }}</div>
          <div class="stat-label">{{ t('queue.inProgress') }}</div>
        </div>
      </div>
      <div class="stat-card-nano" @click="filterByStatus('completed')">
        <div class="stat-icon-bg completed"><CIcon icon="cil-check-circle" /></div>
        <div class="stat-info">
          <div class="stat-value text-success">{{ completedCount }}</div>
          <div class="stat-label">{{ t('bookings.completed') }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg avg"><CIcon icon="cil-timer" /></div>
        <div class="stat-info">
          <div class="stat-value">12m</div>
          <div class="stat-label">{{ t('queue.avgWaitTime') || 'متوسط الانتظار' }}</div>
        </div>
      </div>
    </div>

    <!-- Modern Tickets Grid (Waitlist & Serving) -->
    <CRow class="g-4 mb-4">
      <!-- Waiting List Column -->
      <CCol lg="8">
        <div class="nano-panel h-100">
          <div class="panel-header d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ t('queue.waitingList') || 'قائمة الانتظار' }}</h4>
            <CFormInput v-model="searchQuery" :placeholder="t('common.search')" class="nano-search-input" />
          </div>

          <div v-if="loading" class="text-center p-5">
            <CSpinner color="primary" />
          </div>
          <div v-else-if="waitingTickets.length === 0" class="text-center p-5 text-muted opacity-50">
            <CIcon icon="cil-coffee" size="xl" class="mb-3" />
            <p>{{ t('queue.noTickets') || 'لا يوجد عميلات في الانتظار' }}</p>
          </div>
          <div v-else class="nano-ticket-list d-flex flex-column gap-3">
            <div v-for="ticket in waitingTickets" :key="ticket.id" class="nano-ticket-card waiting">
              <div class="ticket-number-box">
                <span class="number">#{{ ticket.ticket_number || ticket.id }}</span>
                <span class="label">{{ t('queue.ticket') }}</span>
              </div>
              <div class="ticket-info flex-grow-1 ms-3">
                <div class="customer-name fw-bold fs-5">{{ ticket.customer_name || 'Walk-in' }}</div>
                <div class="service-name small text-muted">
                  <CIcon icon="cil-spreadsheet" class="me-1" />{{ ticket.service_name }}
                </div>
                <div class="wait-time small text-warning fw-bold mt-1">
                  <CIcon icon="cil-clock" class="me-1" />{{ formatTimeElapsed(ticket.check_in_at) }}
                </div>
              </div>
              <div class="ticket-actions d-flex gap-2">
                <CButton color="primary" variant="ghost" @click="callTicket(ticket)" :title="t('queue.call')">
                  <CIcon icon="cil-bell" />
                </CButton>
                <CButton color="success" variant="ghost" @click="startServing(ticket)" :title="t('queue.startService')">
                  <CIcon icon="cil-play" />
                </CButton>
                <CButton color="secondary" variant="ghost" @click="viewTicket(ticket)">
                  <CIcon icon="cil-info" />
                </CButton>
              </div>
            </div>
          </div>
        </div>
      </CCol>

      <!-- Now Serving Column -->
      <CCol lg="4">
        <div class="nano-panel h-100 serving-panel">
          <h4 class="fw-bold mb-4 text-info">{{ t('queue.inProgress') }}</h4>
          
          <div v-if="servingTickets.length === 0" class="text-center p-5 text-muted opacity-50 border-dashed rounded-4">
            <CIcon icon="cil-smile" size="xl" class="mb-3" />
            <p>{{ t('queue.noActiveServices') || 'لا يوجد عميلات قيد الخدمة حالياً' }}</p>
          </div>
          <div v-else class="d-flex flex-column gap-3">
            <div v-for="ticket in servingTickets" :key="ticket.id" class="nano-ticket-card serving border-info shadow-sm">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <CBadge color="info" shape="rounded-pill" class="px-3 py-2">
                  <CIcon icon="cil-user" class="me-1" />{{ ticket.staff_name }}
                </CBadge>
                <span class="fw-bold text-info">#{{ ticket.ticket_number || ticket.id }}</span>
              </div>
              <h5 class="fw-bold mb-1">{{ ticket.customer_name || 'Walk-in' }}</h5>
              <div class="small text-muted mb-3">{{ ticket.service_name }}</div>
              
              <div class="d-flex gap-2">
                <CButton color="success" class="flex-grow-1 nano-btn-sm" @click="completeTicket(ticket)">
                  <CIcon icon="cil-check" class="me-1" />{{ t('queue.complete') }}
                </CButton>
                <CButton color="info" variant="ghost" @click="checkoutTicket(ticket)" :title="t('queue.checkout')">
                  <CIcon icon="cil-cart" />
                </CButton>
              </div>
            </div>
          </div>
        </div>
      </CCol>
    </CRow>

    <!-- Upcoming Bookings (Horizontal Scroll) -->
    <div v-if="upcomingBookings.length > 0" class="nano-panel mb-4">
      <h5 class="fw-bold mb-3 d-flex align-items-center">
        <CIcon icon="cil-calendar" class="me-2 text-primary" />
        {{ t('queue.upcomingBookings') }}
      </h5>
      <div class="upcoming-scroll d-flex gap-3 overflow-auto pb-2">
        <div v-for="booking in upcomingBookings" :key="booking.id" class="booking-mini-card p-3 rounded-4 border bg-tertiary">
          <div class="fw-bold text-primary mb-1">{{ booking.booking_time }}</div>
          <div class="fw-bold mb-1 truncate">{{ booking.customer_name }}</div>
          <div class="small text-muted truncate mb-2">{{ booking.service_name }}</div>
          <CButton size="sm" color="primary" variant="ghost" @click="addBookingToQueue(booking)" :disabled="booking.queue_ticket_id">
             {{ booking.queue_ticket_id ? 'In Queue' : t('queue.addToQueue') }}
          </CButton>
        </div>
      </div>
    </div>

    <!-- Modals (Standardized) -->
    <CModal :visible="showViewModal" @close="closeViewModal" size="lg" alignment="center">
      <CModalHeader>
        <CModalTitle><CIcon icon="cil-list" class="me-2" />{{ t('queue.ticketDetails') }}</CModalTitle>
      </CModalHeader>
      <CModalBody v-if="viewingTicket" class="p-4">
        <div class="d-flex align-items-center gap-4 mb-4">
          <div class="ticket-number-badge large">#{{ viewingTicket.ticket_number || viewingTicket.id }}</div>
          <div>
            <h3 class="fw-bold mb-1">{{ viewingTicket.customer_name || 'Walk-in Customer' }}</h3>
            <CBadge :color="getStatusBadgeColor(viewingTicket.status)" shape="rounded-pill" class="px-3 py-2">
              {{ getStatusText(viewingTicket.status) }}
            </CBadge>
          </div>
        </div>
        
        <div class="nano-stats-grid row g-3">
          <CCol md="6">
            <div class="p-3 bg-tertiary rounded-4">
              <label class="small text-muted fw-bold d-block mb-1">Service</label>
              <div class="fw-bold">{{ viewingTicket.service_name }}</div>
            </div>
          </CCol>
          <CCol md="6">
            <div class="p-3 bg-tertiary rounded-4">
              <label class="small text-muted fw-bold d-block mb-1">Staff</label>
              <div class="fw-bold">{{ viewingTicket.staff_name || 'Unassigned' }}</div>
            </div>
          </CCol>
          <CCol md="6">
            <div class="p-3 bg-tertiary rounded-4">
              <label class="small text-muted fw-bold d-block mb-1">Check-in</label>
              <div class="fw-bold">{{ formatTime(viewingTicket.check_in_at) }}</div>
            </div>
          </CCol>
          <CCol md="6">
            <div class="p-3 bg-tertiary rounded-4">
              <label class="small text-muted fw-bold d-block mb-1">Wait Time</label>
              <div class="fw-bold text-warning">{{ formatTimeElapsed(viewingTicket.check_in_at) }}</div>
            </div>
          </CCol>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" variant="ghost" @click="closeViewModal">{{ t('common.close') }}</CButton>
        <CButton v-if="viewingTicket.status === 'waiting'" color="primary" @click="() => { closeViewModal(); startServing(viewingTicket); }">
          {{ t('queue.startService') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Add Queue Ticket Modal -->
    <CModal :visible="showAddQueueModal" @close="closeAddQueueModal" size="lg" alignment="center">
      <CModalHeader>
        <CModalTitle><CIcon icon="cil-plus" class="me-2" />{{ t('queue.createTicket') }}</CModalTitle>
      </CModalHeader>
      <CModalBody class="p-4">
        <div class="mb-4">
          <label class="form-label fw-bold">{{ t('bookings.customer') }}</label>
          <CFormSelect v-model="newTicketForm.customer_id" class="rounded-3">
            <option :value="null">{{ t('queue.walkIn') }}</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
          </CFormSelect>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold">{{ t('bookings.service') }} *</label>
          <CFormSelect v-model="newTicketForm.service_id" @change="onServiceChange" class="rounded-3">
            <option :value="null">{{ t('queue.selectService') }}</option>
            <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name || s.title }}</option>
          </CFormSelect>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold">{{ t('bookings.staff') }}</label>
          <CFormSelect v-model="newTicketForm.staff_id" class="rounded-3">
            <option :value="null">{{ t('queue.anyAvailableStaff') }}</option>
            <option v-for="st in availableStaffForService" :key="st.id" :value="st.id">{{ st.name }}</option>
          </CFormSelect>
        </div>
        <div class="mb-2">
          <label class="form-label fw-bold">{{ t('common.notes') }}</label>
          <CFormTextarea v-model="newTicketForm.notes" rows="3" class="rounded-3" />
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" variant="ghost" @click="closeAddQueueModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="nano-btn" @click="createTicket" :disabled="!newTicketForm.service_id || creatingTicket">
           {{ creatingTicket ? t('common.creating') : t('queue.createTicket') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- FAQ Section -->
    <HelpSection page-key="queue" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import {
  CButton, CBadge, CModal, CModalHeader, CModalTitle, CModalBody, CModalFooter,
  CFormInput, CFormSelect, CFormTextarea, CRow, CCol, CSpinner
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import { useRouter } from 'vue-router';
import HelpSection from '@/components/Common/HelpSection.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const router = useRouter();

// State
const tickets = ref([]);
const customers = ref([]);
const services = ref([]);
const staff = ref([]);
const loading = ref(false);
const creatingTicket = ref(false);
const autoRefreshEnabled = ref(true);
const refreshTimer = ref(null);
const searchQuery = ref('');

const waitingCount = computed(() => tickets.value.filter(t => t.status === 'waiting' || t.status === 'called').length);
const servingCount = computed(() => tickets.value.filter(t => t.status === 'serving').length);
const completedCount = computed(() => tickets.value.filter(t => t.status === 'completed').length);

const waitingTickets = computed(() => 
  tickets.value.filter(t => t.status === 'waiting' || t.status === 'called')
    .sort((a, b) => new Date(a.check_in_at) - new Date(b.check_in_at))
);
const servingTickets = computed(() => tickets.value.filter(t => t.status === 'serving'));

const upcomingBookings = ref([]);

// Form State
const showAddQueueModal = ref(false);
const showViewModal = ref(false);
const viewingTicket = ref(null);
const newTicketForm = ref({
  customer_id: null,
  service_id: null,
  staff_id: null,
  notes: ''
});

// Methods
const loadTickets = async () => {
  loading.value = true;
  try {
    const [ticketsRes, bookingsRes] = await Promise.all([
      api.get('/queue/tickets', { params: { status_not: 'completed,cancelled' } }),
      api.get('/bookings', { params: { date: new Date().toISOString().split('T')[0], status: 'confirmed' } })
    ]);
    tickets.value = ticketsRes.data?.data?.items || ticketsRes.data?.items || [];
    upcomingBookings.value = (bookingsRes.data?.data?.items || bookingsRes.data?.items || [])
      .filter(b => !b.queue_ticket_id);
  } catch (e) {
    console.error('Failed to load queue:', e);
  } finally {
    loading.value = false;
  }
};

const loadFormData = async () => {
  try {
    const [custRes, servRes, staffRes] = await Promise.all([
      api.get('/customers', { params: { per_page: 1000 } }),
      api.get('/services', { params: { per_page: 1000 } }),
      api.get('/staff', { params: { per_page: 1000 } })
    ]);
    customers.value = custRes.data?.data?.items || custRes.data?.items || [];
    services.value = servRes.data?.data?.items || servRes.data?.items || [];
    staff.value = staffRes.data?.data?.items || staffRes.data?.items || [];
  } catch (e) {
    console.error('Failed to load form data:', e);
  }
};

const openAddQueueModal = () => {
  newTicketForm.value = { customer_id: null, service_id: null, staff_id: null, notes: '' };
  showAddQueueModal.value = true;
};
const closeAddQueueModal = () => showAddQueueModal.value = false;

const createTicket = async () => {
  creatingTicket.value = true;
  try {
    await api.post('/queue/tickets', newTicketForm.value);
    toast.success(t('queue.ticketCreated') || 'تم إنشاء التذكرة');
    closeAddQueueModal();
    loadTickets();
  } catch (e) {
    toast.error('Failed to create ticket');
  } finally {
    creatingTicket.value = false;
  }
};

const callTicket = async (ticket) => {
  try {
    await api.post(`/queue/tickets/${ticket.id}/call`);
    toast.info(`${t('queue.calling')}: ${ticket.ticket_number}`);
    loadTickets();
  } catch (e) {
    toast.error('Failed to call ticket');
  }
};

const callNext = () => {
  if (waitingTickets.value.length > 0) {
    callTicket(waitingTickets.value[0]);
  }
};

const startServing = async (ticket) => {
  try {
    await api.post(`/queue/tickets/${ticket.id}/serve`);
    toast.success(t('queue.serviceStarted'));
    loadTickets();
  } catch (e) {
    toast.error('Failed to start service');
  }
};

const completeTicket = async (ticket) => {
  try {
    await api.post(`/queue/tickets/${ticket.id}/complete`);
    toast.success(t('queue.complete'));
    loadTickets();
  } catch (e) {
    toast.error('Failed to complete ticket');
  }
};

const checkoutTicket = (ticket) => {
  router.push({
    path: '/pos',
    query: {
      customer_id: ticket.customer_id,
      queue_id: ticket.id,
      service_id: ticket.service_id,
      staff_id: ticket.staff_id
    }
  });
};

const viewTicket = (ticket) => {
  viewingTicket.value = ticket;
  showViewModal.value = true;
};
const closeViewModal = () => showViewModal.value = false;

const addBookingToQueue = async (booking) => {
  try {
    await api.post(`/queue/tickets`, {
      customer_id: booking.customer_id,
      service_id: booking.service_id,
      staff_id: booking.staff_id,
      booking_id: booking.id,
      notes: booking.notes
    });
    toast.success(t('queue.addedFromBooking'));
    loadTickets();
  } catch (e) {
    toast.error('Failed to add booking to queue');
  }
};

const formatTime = (timeStr) => {
  if (!timeStr) return '--:--';
  const date = new Date(timeStr);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatTimeElapsed = (startTime) => {
  if (!startTime) return '0m';
  const start = new Date(startTime);
  const diff = Math.floor((new Date() - start) / 60000);
  return `${diff}m`;
};

const getStatusText = (status) => {
  switch (status) {
    case 'waiting': return t('queue.waiting');
    case 'called': return t('queue.called');
    case 'serving': return t('queue.serving');
    case 'completed': return t('bookings.completed');
    default: return status;
  }
};

const getStatusBadgeColor = (status) => {
  switch (status) {
    case 'waiting': return 'warning';
    case 'called': return 'primary';
    case 'serving': return 'info';
    case 'completed': return 'success';
    default: return 'secondary';
  }
};

const openDisplayMode = () => {
  const url = router.resolve({ name: 'QueueDisplay' }).href;
  window.open(url, '_blank', 'width=1200,height=800');
};

const availableStaffForService = computed(() => {
  if (!newTicketForm.value.service_id) return staff.value;
  // In real app, filter staff who can do this service
  return staff.value;
});

const toggleAutoRefresh = () => {
  autoRefreshEnabled.value = !autoRefreshEnabled.value;
  if (autoRefreshEnabled.value) startTimer();
  else stopTimer();
};

const startTimer = () => {
  refreshTimer.value = setInterval(loadTickets, 30000);
};
const stopTimer = () => {
  if (refreshTimer.value) clearInterval(refreshTimer.value);
};

onMounted(() => {
  loadTickets();
  loadFormData();
  startTimer();
});

onUnmounted(() => {
  stopTimer();
});
</script>

<style scoped>
.queue-page {
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
.nano-btn-success {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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
  cursor: pointer;
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
.stat-icon-bg.waiting { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-icon-bg.serving { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
.stat-icon-bg.completed { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.avg { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }

.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; margin-top: 4px; }

.nano-panel {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 1.75rem;
  box-shadow: var(--shadow-sm);
}

.nano-search-input {
  max-width: 200px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background: var(--bg-tertiary);
}

.nano-ticket-card {
  display: flex;
  align-items: center;
  background: var(--bg-tertiary);
  border-radius: 20px;
  padding: 1.25rem;
  border: 1px solid var(--border-color);
  transition: all 0.3s;
}
.nano-ticket-card:hover {
  transform: translateX(5px);
  border-color: var(--asmaa-primary);
}

.ticket-number-box {
  width: 70px;
  height: 70px;
  background: var(--bg-secondary);
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border-color);
}
.ticket-number-box .number { font-size: 1.25rem; font-weight: 800; color: var(--asmaa-primary); }
.ticket-number-box .label { font-size: 0.625rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); }

.serving-panel {
  background: linear-gradient(180deg, var(--bg-secondary) 0%, rgba(14, 165, 233, 0.05) 100%);
  border: 1px solid rgba(14, 165, 233, 0.1);
}

.nano-btn-sm {
  border-radius: 10px;
  padding: 0.5rem 1rem;
  font-weight: 700;
  font-size: 0.8125rem;
}

.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 120px;
}

.ticket-number-badge.large {
  font-size: 2.5rem;
  font-weight: 900;
  color: var(--asmaa-primary);
  background: var(--bg-tertiary);
  padding: 1rem 2rem;
  border-radius: 24px;
  border: 2px dashed var(--asmaa-primary);
}

.booking-mini-card {
  min-width: 160px;
  transition: all 0.3s;
}
.booking-mini-card:hover {
  border-color: var(--asmaa-primary);
  transform: translateY(-5px);
}

.upcoming-scroll::-webkit-scrollbar {
  height: 4px;
}
.upcoming-scroll::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 10px;
}

@media (max-width: 992px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>
