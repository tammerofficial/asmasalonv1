<template>
  <div class="worker-calls-page">
    <!-- Header -->
    <PageHeader
      :title="t('workerCalls.title')"
      :subtitle="t('workerCalls.subtitle')"
    >
      <template #icon>
        <CIcon icon="cil-bell" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" class="me-2" @click="openDisplay">
          <CIcon icon="cil-screen-desktop" class="me-2" />
          {{ t('workerCalls.openDisplay') }}
        </CButton>
        <CButton color="primary" @click="loadData">
          <CIcon icon="cil-reload" class="me-2" />
          {{ t('workerCalls.refresh') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('workerCalls.available')"
        :value="availableCount"
        badge-variant="success"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('workerCalls.busy')"
        :value="busyCount"
        badge-variant="danger"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-user" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('workerCalls.queueWaiting')"
        :value="waitingCalls.length"
        badge-variant="warning"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-bell" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('workerCalls.todaysBookings')"
        :value="bookings.length"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-calendar" />
        </template>
      </StatCard>
    </div>

    <div class="content-grid">
      <!-- Staff Status -->
      <Card :title="t('workerCalls.staffStatus')" icon="cil-people" class="staff-card-section">
        <LoadingSpinner v-if="loading" :text="t('common.loading')" />

        <template v-else>
          <div v-if="!staffCards.length" class="empty-wrapper">
            <EmptyState
              :title="t('common.noData')"
              :description="t('staff.title')"
            />
          </div>

          <div v-else class="staff-grid">
            <div
              v-for="card in staffCards"
              :key="card.id"
              class="staff-card"
              :class="{ 'busy': card.busy, 'available': !card.busy }"
            >
              <div class="staff-card-header">
                <div class="staff-info-header">
                  <div class="staff-avatar">
                    <CIcon :icon="card.busy ? 'cil-user' : 'cil-check-circle'" class="avatar-icon" />
                  </div>
                  <div>
                    <h5 class="staff-name">{{ card.name }}</h5>
                    <p class="staff-role">{{ card.position || 'Staff' }}</p>
                  </div>
                </div>
                <CBadge :color="card.busy ? 'danger' : 'success'" class="status-badge-modern">
                  <CIcon :icon="card.busy ? 'cil-user' : 'cil-check-circle'" class="me-1" />
                  {{ card.busy ? t('workerCalls.busy') : t('workerCalls.available') }}
                </CBadge>
              </div>

              <div class="staff-card-body">
                <div class="meta-row" v-if="card.currentService">
                  <CIcon icon="cil-spreadsheet" class="meta-icon" />
                  <span><strong>{{ t('bookings.service') }}:</strong> {{ card.currentService }}</span>
                </div>
                <div class="meta-row" v-if="card.currentCustomer">
                  <CIcon icon="cil-user" class="meta-icon" />
                  <span><strong>{{ t('bookings.customer') }}:</strong> {{ card.currentCustomer }}</span>
                </div>
                <div class="meta-row">
                  <CIcon icon="cil-star" class="meta-icon" />
                  <span><strong>{{ Number(card.rating || 0).toFixed(1) }}</strong> rating</span>
                </div>
                <div class="meta-row">
                  <CIcon icon="cil-people" class="meta-icon" />
                  <span><strong>{{ card.totalServices || 0 }}</strong> customers</span>
                </div>
              </div>

              <div class="staff-card-actions" v-if="card.currentCall">
                <CButton
                  color="primary"
                  size="sm"
                  class="me-2 action-btn-primary"
                  @click="callCustomer(card.currentCall)"
                >
                  <CIcon icon="cil-bell" class="me-1" />
                  {{ t('workerCalls.callCustomer') }}
                </CButton>
                <CButton
                  color="secondary"
                  size="sm"
                  variant="outline"
                  class="action-btn-secondary"
                  @click="callStaff(card.currentCall)"
                >
                  <CIcon icon="cil-user-female" class="me-1" />
                  {{ t('workerCalls.callStaff') }}
                </CButton>
              </div>
              <div class="staff-card-actions" v-if="card.busy">
                <CButton
                  color="success"
                  size="sm"
                  class="mark-available-btn"
                  @click="markStaffAsAvailable(card)"
                >
                  <CIcon icon="cil-check-circle" class="me-1" />
                  {{ t('workerCalls.markAsAvailable') || 'Mark as Available' }}
                </CButton>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Queue waiting -->
      <Card :title="t('workerCalls.queueWaiting')" icon="cil-queue" class="queue-card-section">
        <LoadingSpinner v-if="loading" :text="t('common.loading')" />

        <div v-else>
          <div v-if="!waitingCalls.length" class="empty-wrapper">
            <EmptyState
              :title="t('queue.noWaiting')"
              :description="t('workerCalls.queueWaiting')"
            />
          </div>
          <div v-else class="queue-list-container">
            <div
              v-for="call in waitingCalls"
              :key="call.id"
              class="queue-item"
              :class="{ 'urgent': waitingCalls.indexOf(call) === 0 }"
            >
              <div class="queue-item-content">
                <div class="queue-ticket-info">
                  <div class="ticket-badge">
                    <CIcon icon="cil-ticket" class="ticket-icon" />
                    <strong>#{{ call.ticket_number || call.ticket_id }}</strong>
                  </div>
                  <div class="queue-customer">
                    <CIcon icon="cil-user" class="customer-icon" />
                    {{ call.customer_name || 'Customer' }}
                  </div>
                  <div class="queue-service" v-if="call.service_name">
                    <CIcon icon="cil-spreadsheet" class="service-icon" />
                    {{ call.service_name }}
                  </div>
                  <div class="queue-staff" v-if="call.staff_name">
                    <CIcon icon="cil-user-female" class="staff-icon" />
                    {{ call.staff_name }}
                  </div>
                </div>
                <div class="queue-actions">
                  <CButton
                    v-if="call.staff_id && getAvailableStaffForCall(call.staff_id)"
                    color="primary"
                    size="sm"
                    class="call-staff-btn"
                    @click="callAvailableStaff(call)"
                  >
                    <CIcon icon="cil-bell" class="me-1" />
                    {{ t('workerCalls.callStaff') }}
                  </CButton>
                  <CButton
                    v-else-if="getFirstAvailableStaff(call.service_id)"
                    color="primary"
                    size="sm"
                    class="call-staff-btn"
                    @click="callAvailableStaff(call, getFirstAvailableStaff(call.service_id).id)"
                  >
                    <CIcon icon="cil-bell" class="me-1" />
                    {{ t('workerCalls.callAvailableStaff') || 'Call Available Staff' }}
                  </CButton>
                  <CButton
                    color="secondary"
                    size="sm"
                    variant="outline"
                    class="call-customer-btn"
                    @click="callCustomer(call)"
                  >
                    <CIcon icon="cil-user" class="me-1" />
                    {{ t('workerCalls.callCustomer') }}
                  </CButton>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- All Tickets -->
    <Card :title="t('workerCalls.allTickets') || 'All Tickets'" icon="cil-ticket" class="tickets-card-section">
      <LoadingSpinner v-if="loadingTickets" :text="t('common.loading')" />

      <div v-else>
        <div v-if="!allTickets.length" class="empty-wrapper">
          <EmptyState
            :title="t('queue.noTickets') || 'No tickets found'"
            :description="t('workerCalls.allTickets') || 'All Tickets'"
          />
        </div>
        <div v-else class="tickets-table-wrapper modern-table-container">
          <CTable small responsive hover class="modern-table">
            <thead>
              <tr>
                <th class="table-header">
                  <CIcon icon="cil-ticket" class="header-icon" />
                  {{ t('queue.ticketNumber') || 'Ticket' }}
                </th>
                <th class="table-header">
                  <CIcon icon="cil-user" class="header-icon" />
                  {{ t('bookings.customer') }}
                </th>
                <th class="table-header">
                  <CIcon icon="cil-spreadsheet" class="header-icon" />
                  {{ t('bookings.service') }}
                </th>
                <th class="table-header">
                  <CIcon icon="cil-user-female" class="header-icon" />
                  {{ t('bookings.staff') }}
                </th>
                <th class="table-header">
                  <CIcon icon="cil-info" class="header-icon" />
                  {{ t('queue.status') || 'Status' }}
                </th>
                <th class="table-header actions-header">
                  <CIcon icon="cil-options" class="header-icon" />
                  {{ t('common.actions') || 'Actions' }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ticket in allTickets" :key="ticket.id" class="ticket-row modern-table-row">
                <td class="ticket-number-cell">
                  <div class="cell-content">
                    <CIcon icon="cil-ticket" class="cell-icon" />
                    <span class="cell-text">{{ ticket.ticket_number || `T-${String(ticket.id).padStart(4, '0')}` }}</span>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <div class="ticket-customer-name">{{ ticket.customer_name || 'Customer' }}</div>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <CIcon icon="cil-spreadsheet" class="cell-icon" />
                    <span class="cell-text">{{ ticket.service_name || '-' }}</span>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <CIcon icon="cil-user-female" class="cell-icon" />
                    <span class="cell-text">{{ ticket.staff_name || '-' }}</span>
                  </div>
                </td>
                <td>
                  <CBadge :class="getTicketStatusBadgeClass(ticket.status)" class="status-badge-modern">
                    <CIcon :icon="getTicketStatusIcon(ticket.status)" class="status-icon" />
                    <span class="status-text">{{ getTicketStatusText(ticket.status) }}</span>
                  </CBadge>
                </td>
                <td>
                  <div class="ticket-actions-modern">
                    <button
                      v-if="ticket.status === 'serving'"
                      class="action-icon-btn complete-btn-icon"
                      :title="t('queue.complete') || 'Complete'"
                      @click="completeTicket(ticket)"
                    >
                      <CIcon icon="cil-check" />
                    </button>
                    <button
                      v-if="ticket.status === 'waiting' || ticket.status === 'called'"
                      class="action-icon-btn start-btn-icon"
                      :title="t('queue.startServing') || 'Start Serving'"
                      @click="startServing(ticket)"
                    >
                      <CIcon icon="cil-play" />
                    </button>
                    <button
                      v-if="ticket.status === 'completed'"
                      class="action-icon-btn view-btn-icon"
                      :title="t('common.view') || 'View'"
                      @click="viewTicket(ticket)"
                    >
                      <CIcon icon="cil-eye" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </CTable>
        </div>
      </div>
    </Card>

    <!-- Today's bookings -->
    <Card :title="t('workerCalls.todaysBookings')" icon="cil-calendar" class="bookings-card-section">
      <LoadingSpinner v-if="loadingBookings" :text="t('common.loading')" />

      <div v-else>
        <div v-if="!bookings.length" class="empty-wrapper">
          <EmptyState
            :title="t('bookings.noBookingsFound')"
            :description="t('dashboard.todaysBookings')"
          />
        </div>
        <div v-else class="bookings-table-wrapper modern-table-container">
          <CTable small responsive hover class="modern-table">
            <thead>
              <tr>
                <th class="table-header">
                  <CIcon icon="cil-clock" class="header-icon" />
                  {{ t('bookings.time') || 'Time' }}
                </th>
                <th class="table-header">
                  <CIcon icon="cil-user" class="header-icon" />
                  {{ t('bookings.customer') }}
                </th>
                <th class="table-header">
                  <CIcon icon="cil-spreadsheet" class="header-icon" />
                  {{ t('bookings.service') }}
                </th>
                <th class="table-header">
                  <CIcon icon="cil-user-female" class="header-icon" />
                  {{ t('bookings.staff') }}
                </th>
                <th class="table-header">
                  <CIcon icon="cil-info" class="header-icon" />
                  {{ t('bookings.status') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in bookings" :key="booking.id" class="booking-row modern-table-row">
                <td class="booking-time-cell">
                  <div class="cell-content">
                    <CIcon icon="cil-clock" class="cell-icon" />
                    <span class="cell-text">{{ booking.booking_time }}</span>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <div class="booking-customer-name">{{ booking.customer_name }}</div>
                    <div v-if="booking.customer_phone" class="sub-meta">
                      <CIcon icon="cil-phone" class="me-1" />
                      {{ booking.customer_phone }}
                    </div>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <CIcon icon="cil-spreadsheet" class="cell-icon" />
                    <span class="cell-text">{{ booking.service_name }}</span>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <CIcon icon="cil-user-female" class="cell-icon" />
                    <span class="cell-text">{{ booking.staff_name }}</span>
                  </div>
                </td>
                <td>
                  <CBadge :class="getBookingStatusBadgeClass(booking.status)" class="status-badge-modern">
                    <CIcon :icon="getBookingStatusIcon(booking.status)" class="status-icon" />
                    <span class="status-text">{{ getBookingStatusText(booking.status) }}</span>
                  </CBadge>
                </td>
              </tr>
            </tbody>
          </CTable>
        </div>
        
        <!-- Pagination -->
        <div v-if="bookingsPagination.total_pages > 1" class="bookings-pagination-wrapper">
          <div class="pagination-info">
            <span class="pagination-text">
              {{ t('common.showing') || 'Showing' }} 
              <strong>{{ (bookingsPagination.current_page - 1) * bookingsPagination.per_page + 1 }}</strong>
              {{ t('common.to') || 'to' }} 
              <strong>{{ Math.min(bookingsPagination.current_page * bookingsPagination.per_page, bookingsPagination.total) }}</strong>
              {{ t('common.of') || 'of' }} 
              <strong>{{ bookingsPagination.total }}</strong>
              {{ t('common.results') || 'results' }}
            </span>
          </div>
          <CPagination
            :pages="bookingsPagination.total_pages"
            :active-page="bookingsPagination.current_page"
            @update:active-page="changeBookingsPage"
            class="bookings-pagination"
          />
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  CButton,
  CBadge,
  CTable,
  CPagination,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import api from '@/utils/api';
import PageHeader from '@/components/UI/PageHeader.vue';
import Card from '@/components/UI/Card.vue';
import StatCard from '@/components/UI/StatCard.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';

const { t } = useTranslation();
const router = useRouter();
const toast = useToast();

const staff = ref([]);
const workerCalls = ref([]);
const bookings = ref([]);
const allTickets = ref([]);
const loading = ref(false);
const loadingBookings = ref(false);
const loadingTickets = ref(false);

const bookingsPagination = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  total_pages: 0,
});

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
  } finally {
    loading.value = false;
  }
};

const loadBookings = async (page = 1) => {
  loadingBookings.value = true;
  try {
    const res = await api.get('/bookings', {
      params: {
        booking_date: today(),
        page: page,
        per_page: bookingsPagination.value.per_page,
      },
    });
    
    const data = res.data?.data || {};
    bookings.value = data.items || [];
    
    // Update pagination info
    if (data.pagination) {
      bookingsPagination.value = {
        current_page: data.pagination.current_page || page,
        per_page: data.pagination.per_page || bookingsPagination.value.per_page,
        total: data.pagination.total || 0,
        total_pages: data.pagination.total_pages || 1,
      };
    }
  } catch (error) {
    console.error('Error loading bookings', error);
    bookings.value = [];
  } finally {
    loadingBookings.value = false;
  }
};

const changeBookingsPage = (page) => {
  bookingsPagination.value.current_page = page;
  loadBookings(page);
};

const loadTickets = async () => {
  loadingTickets.value = true;
  try {
    const res = await api.get('/queue', {
      params: {
        per_page: 100,
      },
    });
    allTickets.value = res.data?.data?.items || [];
  } catch (error) {
    console.error('Error loading tickets', error);
    allTickets.value = [];
  } finally {
    loadingTickets.value = false;
  }
};

const loadData = async () => {
  await Promise.all([loadStaffAndCalls(), loadBookings(), loadTickets()]);
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

    // Parse service_ids
    let serviceIds = [];
    if (s.service_ids) {
      if (Array.isArray(s.service_ids)) {
        serviceIds = s.service_ids;
      } else if (typeof s.service_ids === 'string') {
        try {
          serviceIds = JSON.parse(s.service_ids);
        } catch (e) {
          serviceIds = [];
        }
      }
    }

    return {
      id: s.id,
      name: s.name,
      position: s.position,
      rating: s.rating,
      totalServices: s.total_services,
      busy,
      service_ids: serviceIds,
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

const getAvailableStaffForCall = (staffId) => {
  return staffCards.value.find(s => s.id === staffId && !s.busy);
};

const getFirstAvailableStaff = (serviceId = null) => {
  if (!serviceId) {
    return staffCards.value.find(s => !s.busy);
  }
  
  // Find available staff that can perform this service
  return staffCards.value.find(s => {
    if (s.busy) return false;
    
    // If staff has no service_ids assigned, they can do any service
    if (!s.service_ids || s.service_ids.length === 0) {
      return true;
    }
    
    // Check if staff can perform this service
    return s.service_ids.includes(serviceId);
  });
};

const getStatusColor = (status) => {
  const colorMap = {
    pending: 'warning',
    confirmed: 'info',
    completed: 'success',
    cancelled: 'danger',
  };
  return colorMap[status] || 'secondary';
};

const getTicketStatusColor = (status) => {
  const colorMap = {
    waiting: 'warning',
    called: 'info',
    serving: 'primary',
    completed: 'success',
    cancelled: 'danger',
  };
  return colorMap[status] || 'secondary';
};

const getTicketStatusBadgeClass = (status) => {
  const classMap = {
    waiting: 'status-waiting',
    called: 'status-called',
    serving: 'status-serving',
    completed: 'status-completed',
    cancelled: 'status-cancelled',
  };
  return classMap[status] || 'status-default';
};

const getTicketStatusIcon = (status) => {
  const iconMap = {
    waiting: 'cil-clock',
    called: 'cil-bell',
    serving: 'cil-user',
    completed: 'cil-check-circle',
    cancelled: 'cil-x-circle',
  };
  return iconMap[status] || 'cil-info';
};

const getTicketStatusText = (status) => {
  const textMap = {
    waiting: t('queue.waiting') || 'Waiting',
    called: t('queue.called') || 'Called',
    serving: t('queue.serving') || 'Serving',
    completed: t('queue.completed') || 'Completed',
    cancelled: t('queue.cancelled') || 'Cancelled',
  };
  return textMap[status] || status;
};

const getBookingStatusBadgeClass = (status) => {
  const classMap = {
    pending: 'status-pending',
    confirmed: 'status-confirmed',
    completed: 'status-completed',
    cancelled: 'status-cancelled',
  };
  return classMap[status] || 'status-default';
};

const getBookingStatusIcon = (status) => {
  const iconMap = {
    pending: 'cil-clock',
    confirmed: 'cil-check',
    completed: 'cil-check-circle',
    cancelled: 'cil-x-circle',
  };
  return iconMap[status] || 'cil-info';
};

const getBookingStatusText = (status) => {
  const textMap = {
    pending: t('bookings.pending') || 'Pending',
    confirmed: t('bookings.confirmed') || 'Confirmed',
    completed: t('bookings.completed') || 'Completed',
    cancelled: t('bookings.cancelled') || 'Cancelled',
  };
  return textMap[status] || status;
};

const viewTicket = (ticket) => {
  // Navigate to ticket details or show modal
  router.push(`/queue/${ticket.id}`);
};

// Play notification sound
const playNotificationSound = () => {
  try {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    oscillator.frequency.value = 800;
    oscillator.type = 'sine';

    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.5);
  } catch (error) {
    console.warn('Could not play notification sound:', error);
    try {
      const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIGWi77+efTQ8MUKfj8LZjHAY4kdfyzHksBSR3x/DdkEAKFF606euoVRQKRp/g8r5sIQUrgc7y2Yk2CBlou+/nn00PDFCn4/C2YxwGOJHX8sx5LAUkd8fw3ZBAC');
      audio.volume = 0.3;
      audio.play().catch(() => {});
    } catch (e) {}
  }
};

const callCustomer = async (call) => {
  try {
    await api.post(`/worker-calls/${call.id}/call-customer`);
    playNotificationSound();
    toast.success(t('workerCalls.customerCalled') || 'Customer called successfully');
    await loadStaffAndCalls();
  } catch (error) {
    console.error('Error calling customer', error);
    toast.error(t('workerCalls.errorCallingCustomer') || 'Error calling customer');
  }
};

const callStaff = async (call) => {
  try {
    await api.post(`/worker-calls/${call.id}/call-staff`);
    playNotificationSound();
    toast.success(t('workerCalls.staffCalled') || 'Staff called successfully');
    await loadStaffAndCalls();
  } catch (error) {
    console.error('Error calling staff', error);
    toast.error(t('workerCalls.errorCallingStaff') || 'Error calling staff');
  }
};

const callAvailableStaff = async (call, staffId = null) => {
  try {
    // If staffId is provided, use it; otherwise use call.staff_id
    const targetStaffId = staffId || call.staff_id;
    
    if (!targetStaffId) {
      toast.error(t('workerCalls.noStaffAvailable') || 'No staff available');
      return;
    }

    // Find or create worker call for this staff
    let workerCall = workerCalls.value.find(
      wc => wc.ticket_id === call.id && wc.staff_id === targetStaffId
    );

    if (!workerCall) {
      // Create new worker call
      const response = await api.post('/worker-calls', {
        ticket_id: call.id,
        staff_id: targetStaffId,
      });
      workerCall = response.data?.data || response.data;
    }

    await api.post(`/worker-calls/${workerCall.id}/call-staff`);
    playNotificationSound();
    toast.success(t('workerCalls.staffCalled') || 'Staff called successfully');
    await loadStaffAndCalls();
  } catch (error) {
    console.error('Error calling available staff', error);
    toast.error(t('workerCalls.errorCallingStaff') || 'Error calling staff');
  }
};

const markStaffAsAvailable = async (staffCard) => {
  if (!staffCard.busy || !staffCard.currentCall) return;
  
  try {
    // Complete the current worker call
    await api.post(`/worker-calls/${staffCard.currentCall.id}/complete`);
    
    // If there's a queue ticket, complete it too
    if (staffCard.currentCall.ticket_id) {
      await api.post(`/queue/${staffCard.currentCall.ticket_id}/complete`);
    }
    
    toast.success(t('workerCalls.staffMarkedAvailable') || 'Staff marked as available');
    await loadData();
  } catch (error) {
    console.error('Error marking staff as available', error);
    toast.error(t('workerCalls.errorMarkingAvailable') || 'Error marking staff as available');
  }
};

const completeTicket = async (ticket) => {
  try {
    await api.post(`/queue/${ticket.id}/complete`);
    toast.success(t('queue.ticketCompleted') || 'Ticket completed successfully');
    await loadData();
  } catch (error) {
    console.error('Error completing ticket', error);
    toast.error(t('queue.errorCompletingTicket') || 'Error completing ticket');
  }
};

const startServing = async (ticket) => {
  try {
    await api.post(`/queue/${ticket.id}/start`);
    toast.success(t('queue.serviceStarted') || 'Service started successfully');
    await loadData();
  } catch (error) {
    console.error('Error starting service', error);
    toast.error(t('queue.errorStartingService') || 'Error starting service');
  }
};

const openDisplay = () => {
  const currentUrl = window.location.href.split('#')[0];
  const displayUrl = `${currentUrl}#/display/staff-room`;
  window.open(displayUrl, '_blank', 'fullscreen=yes,width=1920,height=1080');
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
.worker-calls-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

/* Staff Cards */
.staff-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.staff-card {
  border-radius: 16px;
  padding: 1.25rem;
  background: var(--bg-primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 2px solid var(--border-color);
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.staff-card.available {
  border-color: rgba(16, 185, 129, 0.3);
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, var(--bg-primary) 100%);
}

.staff-card.busy {
  border-color: rgba(239, 68, 68, 0.3);
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, var(--bg-primary) 100%);
}

.staff-card:hover {
  border-color: var(--asmaa-primary);
  box-shadow: 0 8px 24px rgba(187, 160, 122, 0.2);
  transform: translateY(-4px);
}

.staff-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.staff-info-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.staff-avatar {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.staff-card.available .staff-avatar {
  background: linear-gradient(135deg, #10b981 0%, rgba(16, 185, 129, 0.8) 100%);
}

.staff-card.busy .staff-avatar {
  background: linear-gradient(135deg, #ef4444 0%, rgba(239, 68, 68, 0.8) 100%);
}

.avatar-icon {
  width: 24px;
  height: 24px;
  color: white;
}

.staff-name {
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-bold);
  font-family: var(--font-family-base);
  margin: 0;
  color: var(--text-primary);
  line-height: var(--line-height-normal);
}

.staff-role {
  font-size: var(--font-size-sm);
  font-family: var(--font-family-base);
  color: var(--text-muted);
  margin: 0;
  line-height: var(--line-height-normal);
}

.staff-card-body {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  font-size: var(--font-size-sm);
  font-family: var(--font-family-base);
  color: var(--text-secondary);
  line-height: var(--line-height-normal);
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.meta-icon {
  width: 16px;
  height: 16px;
  color: var(--asmaa-primary);
  flex-shrink: 0;
}

.meta-row strong {
  font-weight: var(--font-weight-semibold);
  font-family: var(--font-family-base);
  color: var(--text-primary);
}

.status-badge-modern {
  display: inline-flex;
  align-items: center;
  font-size: var(--font-size-sm);
  font-family: var(--font-family-base);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-full);
  font-weight: var(--font-weight-semibold);
  line-height: var(--line-height-normal);
}

.staff-card-actions {
  margin-top: 0.5rem;
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.action-btn-primary {
  flex: 1;
  min-width: 120px;
}

.action-btn-secondary {
  flex: 1;
  min-width: 120px;
}

.mark-available-btn {
  width: 100%;
  margin-top: 0.5rem;
}

.ticket-number-cell {
  font-weight: 600;
  font-family: 'Courier New', monospace;
  color: var(--asmaa-primary);
}

.ticket-customer-name {
  font-weight: 600;
  color: var(--text-primary);
}

/* Modern Status Badges */
.status-badge-modern {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.875rem;
  border: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.status-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.status-text {
  text-transform: capitalize;
}

/* Ticket Status Colors */
.status-waiting {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.status-called {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.status-serving {
  background: linear-gradient(135deg, #A68B5B 0%, #8B6F47 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(166, 139, 91, 0.3);
}

.status-completed {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.status-cancelled {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.status-pending {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.status-confirmed {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.status-default {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(107, 114, 128, 0.3);
}

/* Modern Action Icons */
.ticket-actions-modern {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  justify-content: center;
}

.action-icon-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.action-icon-btn CIcon {
  width: 18px;
  height: 18px;
}

.action-icon-btn::before {
  content: attr(title);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  margin-bottom: 0.5rem;
  padding: 0.375rem 0.75rem;
  background: rgba(0, 0, 0, 0.9);
  color: white;
  border-radius: 6px;
  font-size: 0.75rem;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s;
  z-index: 1000;
}

.action-icon-btn:hover::before {
  opacity: 1;
}

.complete-btn-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.complete-btn-icon:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.start-btn-icon {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.start-btn-icon:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.view-btn-icon {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(107, 114, 128, 0.3);
}

.view-btn-icon:hover {
  background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
}

.tickets-table-wrapper {
  overflow-x: auto;
}

.ticket-row {
  transition: all 0.2s;
}

.ticket-row:hover {
  background: var(--bg-secondary);
}

.ticket-number-cell {
  font-weight: var(--font-weight-bold);
  font-family: var(--font-family-mono);
  color: var(--asmaa-primary);
  font-size: var(--font-size-base);
  line-height: var(--line-height-normal);
}

.ticket-customer-name {
  font-weight: var(--font-weight-semibold);
  font-family: var(--font-family-base);
  color: var(--text-primary);
  font-size: var(--font-size-base);
  line-height: var(--line-height-normal);
}

/* Queue List */
.queue-list-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.queue-item {
  border-radius: var(--radius-xl);
  padding: var(--spacing-base);
  background: var(--bg-secondary);
  border: 2px solid var(--border-color);
  transition: all 0.3s;
  font-family: var(--font-family-base);
}

.queue-item.urgent {
  border-color: rgba(255, 193, 7, 0.5);
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, var(--bg-secondary) 100%);
  box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
}

.queue-item:hover {
  border-color: var(--asmaa-primary);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.15);
  transform: translateX(4px);
}

.queue-item-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.queue-ticket-info {
  flex: 1;
  min-width: 0;
}

.ticket-badge {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-sm);
}

.ticket-icon {
  width: 18px;
  height: 18px;
  color: var(--asmaa-primary);
}

.ticket-badge strong {
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-bold);
  font-family: var(--font-family-mono);
  color: var(--text-primary);
  line-height: var(--line-height-normal);
}

.queue-customer {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.375rem;
}

.customer-icon {
  width: 16px;
  height: 16px;
  color: var(--asmaa-primary);
}

.queue-service,
.queue-staff {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.service-icon,
.staff-icon {
  width: 14px;
  height: 14px;
  color: var(--text-muted);
}

.queue-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex-shrink: 0;
}

.call-staff-btn,
.call-customer-btn {
  min-width: 140px;
  white-space: nowrap;
}

/* Bookings Table */
.bookings-table-wrapper {
  overflow-x: auto;
}

.booking-row {
  transition: all 0.2s;
}

.booking-row:hover {
  background: var(--bg-secondary);
}

.booking-time-cell {
  font-weight: 600;
  font-family: 'Courier New', monospace;
  color: var(--asmaa-primary);
}

.booking-customer-name {
  font-weight: 600;
  color: var(--text-primary);
}

.sub-meta {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.status-badge {
  font-weight: 600;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
}

/* Enhanced Table Styles */
.modern-table-container {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  background: var(--bg-primary);
}

.modern-table {
  border-collapse: separate;
  border-spacing: 0;
  width: 100%;
  margin: 0;
}

.modern-table thead th.table-header {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.12) 0%, rgba(187, 160, 122, 0.06) 100%);
  color: var(--text-primary);
  font-weight: 700;
  font-size: 0.8125rem;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 1.25rem 1rem;
  border-bottom: 2px solid rgba(187, 160, 122, 0.25);
  position: sticky;
  top: 0;
  z-index: 10;
  white-space: nowrap;
}

.modern-table thead th.table-header.actions-header {
  text-align: center;
}

.header-icon {
  width: 16px;
  height: 16px;
  margin-right: 0.5rem;
  color: var(--asmaa-primary);
  opacity: 0.8;
}

.modern-table tbody td {
  padding: 1.25rem 1rem;
  border-bottom: 1px solid rgba(187, 160, 122, 0.1);
  vertical-align: middle;
  transition: all 0.2s ease;
}

.modern-table-row {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modern-table-row:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.08) 0%, rgba(187, 160, 122, 0.03) 100%);
  transform: translateX(4px);
  box-shadow: inset 4px 0 0 var(--asmaa-primary);
}

.modern-table-row:last-child td {
  border-bottom: none;
}

/* Pagination Styles */
.bookings-pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-top: 2px solid rgba(187, 160, 122, 0.1);
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.03) 0%, transparent 100%);
  margin-top: 1rem;
  border-radius: 0 0 12px 12px;
}

.pagination-info {
  display: flex;
  align-items: center;
}

.pagination-text {
  color: var(--text-secondary);
  font-size: 0.875rem;
  font-weight: 500;
}

.pagination-text strong {
  color: var(--text-primary);
  font-weight: 700;
}

.bookings-pagination {
  display: flex;
  gap: 0.5rem;
}

.bookings-pagination :deep(.page-link) {
  min-width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 2px solid rgba(187, 160, 122, 0.2);
  background: var(--bg-primary);
  color: var(--text-primary);
  font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.bookings-pagination :deep(.page-link:hover) {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  border-color: var(--asmaa-primary);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.2);
}

.bookings-pagination :deep(.page-item.active .page-link) {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  border-color: var(--asmaa-primary);
  color: white;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}

.bookings-pagination :deep(.page-item.disabled .page-link) {
  opacity: 0.5;
  cursor: not-allowed;
  background: var(--bg-secondary);
}

.bookings-pagination :deep(.page-item.disabled .page-link:hover) {
  transform: none;
  box-shadow: none;
}

@media (max-width: 768px) {
  .bookings-pagination-wrapper {
    flex-direction: column;
    gap: 1rem;
  }
  
  .pagination-info {
    width: 100%;
    justify-content: center;
  }
  
  .bookings-pagination {
    width: 100%;
    justify-content: center;
    flex-wrap: wrap;
  }
}

.cell-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.cell-icon {
  width: 18px;
  height: 18px;
  color: var(--asmaa-primary);
  opacity: 0.7;
  flex-shrink: 0;
}

.cell-text {
  color: var(--text-primary);
  font-weight: 500;
}

.empty-wrapper {
  padding: 2rem 0;
}

@media (max-width: 1200px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .staff-grid {
    grid-template-columns: 1fr;
  }

  .queue-item-content {
    flex-direction: column;
  }

  .queue-actions {
    width: 100%;
  }

  .call-staff-btn,
  .call-customer-btn {
    width: 100%;
  }
}
</style>
