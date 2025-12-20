<template>
  <div class="queue-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('queue.title')"
      :subtitle="t('queue.subtitle')"
    >
      <template #icon>
        <CIcon icon="cil-list" />
      </template>
      
      <template #actions>
        <CButton 
          color="secondary" 
          variant="outline" 
          @click="toggleAutoRefresh"
          :class="{ 'active': autoRefreshEnabled }"
          class="me-2"
        >
          <CIcon :icon="autoRefreshEnabled ? 'cil-reload' : 'cil-media-pause'" class="me-2" />
          {{ autoRefreshEnabled ? t('queue.autoRefresh') : t('common.pause') }}
        </CButton>
        <CButton color="secondary" variant="outline" @click="exportData" class="me-2">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="secondary" variant="outline" @click="loadTickets" :disabled="loading">
          <CIcon icon="cil-reload" class="me-2" :class="{ 'spinning': loading }" />
          {{ t('common.refresh') }}
        </CButton>
        <CButton 
          color="secondary" 
          variant="outline" 
          @click="openDisplayMode"
          class="me-2"
        >
          <CIcon icon="cil-screen-desktop" class="me-2" />
          {{ t('queue.displayMode') || 'Display Mode' }}
        </CButton>
        <CButton 
          color="primary" 
          size="lg" 
          @click="openAddQueueModal"
          class="ms-2 btn-primary-custom"
        >
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('queue.addQueue') || 'Add Queue' }}
        </CButton>
        <CButton 
          color="primary" 
          size="lg" 
          @click="callNext" 
          :disabled="waitingCount === 0 || loading"
          class="ms-2 btn-primary-custom"
        >
          <CIcon icon="cil-bell" class="me-2" />
          {{ t('queue.next') }}
          <CBadge v-if="waitingCount > 0" color="warning" class="ms-2">
            {{ waitingCount }}
          </CBadge>
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('queue.waiting')"
        :value="waitingCount"
        :badge="waitingCount + ' ' + t('queue.waiting')"
        badge-variant="warning"
        color="gold"
        :clickable="true"
        @click="filterByStatus('waiting')"
      >
        <template #icon>
          <CIcon icon="cil-clock" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('queue.inProgress')"
        :value="servingCount"
        :badge="servingCount + ' ' + t('queue.inProgress')"
        badge-variant="info"
        color="gold"
        :clickable="true"
        @click="filterByStatus('serving')"
      >
        <template #icon>
          <CIcon icon="cil-user" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('bookings.completed')"
        :value="completedCount"
        :badge="completedCount + ' ' + t('bookings.completed')"
        badge-variant="success"
        color="gold"
        :clickable="true"
        @click="filterByStatus('completed')"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('queue.totalToday')"
        :value="totalTodayCount"
        :badge="t('queue.totalToday')"
        badge-variant="info"
        color="gold"
        :clickable="true"
        @click="filterByStatus(null)"
      >
        <template #icon>
          <CIcon icon="cil-list" />
        </template>
      </StatCard>
    </div>

    <!-- Filters -->
    <Card :title="t('common.filter')" icon="cil-filter" class="filters-card">
      <CRow class="g-3">
        <CCol :md="4">
          <CInputGroup class="search-input-group">
            <CInputGroupText class="search-icon-wrapper">
              <CIcon icon="cil-magnifying-glass" />
            </CInputGroupText>
            <CFormInput
              v-model="filters.search"
              :placeholder="t('queue.searchPlaceholder')"
              @input="debounceSearch"
              class="filter-input search-input"
            />
          </CInputGroup>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.status" @change="loadTickets" class="filter-select">
            <option value="">{{ t('queue.allStatuses') }}</option>
            <option value="waiting">{{ t('queue.waiting') }}</option>
            <option value="called">{{ t('queue.called') }}</option>
            <option value="serving">{{ t('queue.inProgress') }}</option>
            <option value="completed">{{ t('bookings.completed') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.date_filter" @change="loadTickets" class="filter-select">
            <option value="">{{ t('common.date') || 'Date' }}</option>
            <option value="today">{{ t('common.today') || 'Today' }}</option>
            <option value="week">{{ t('common.thisWeek') || 'This Week' }}</option>
            <option value="month">{{ t('common.thisMonth') || 'This Month' }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="2">
          <CButton 
            color="secondary" 
            variant="outline" 
            @click="resetFilters" 
            class="w-100 reset-btn"
          >
            <CIcon icon="cil-reload" class="me-1" />
            {{ t('common.reset') }}
          </CButton>
        </CCol>
      </CRow>
    </Card>

    <!-- Table -->
    <Card :title="t('queue.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="filteredTickets.length === 0"
        :title="t('queue.noTickets')"
        :description="t('queue.noTicketsDesc')"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" class="btn-primary-custom" @click="showCreateModal = true">
            <CIcon icon="cil-plus" class="me-2" />
            {{ t('queue.addTicket') }}
          </CButton>
        </template>
      </EmptyState>

      <div v-else class="table-wrapper">
        <CTable hover responsive class="table-modern queue-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-id">#</th>
              <th class="th-ticket">{{ t('queue.ticketNumber') }}</th>
              <th class="th-customer">{{ t('queue.customer') }}</th>
              <th class="th-service">{{ t('queue.service') }}</th>
              <th class="th-staff">{{ t('queue.staff') }}</th>
              <th class="th-status">{{ t('queue.status') }}</th>
              <th class="th-time">{{ t('queue.checkInTime') }}</th>
              <th class="th-actions">{{ t('queue.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="ticket in filteredTickets" 
              :key="ticket.id" 
              class="table-row queue-row"
              :class="{ 
                'highlight-row': ticket.status === 'waiting', 
                'serving-row': ticket.status === 'serving',
                'completed-row': ticket.status === 'completed'
              }"
            >
              <td class="td-id">
                <span class="ticket-id-badge">#{{ ticket.id }}</span>
              </td>
              <td class="td-ticket">
                <strong class="ticket-number">
                  {{ ticket.ticket_number || `T-${String(ticket.id).padStart(4, '0')}` }}
                </strong>
              </td>
              <td class="td-customer">
                <div class="queue-customer-cell">
                  <strong class="customer-name">{{ ticket.customer_name || '-' }}</strong>
                  <small class="text-muted d-block" v-if="ticket.customer_phone">
                    <CIcon icon="cil-phone" class="me-1" />
                    {{ ticket.customer_phone }}
                  </small>
                </div>
              </td>
              <td class="td-service">
                <div class="queue-service-cell">
                  <CIcon icon="cil-spreadsheet" class="me-1" />
                  {{ ticket.service_name || '-' }}
                </div>
              </td>
              <td class="td-staff">
                <CBadge color="info" variant="outline" class="staff-badge">
                  <CIcon icon="cil-user" class="me-1" />
                  {{ ticket.staff_name || '-' }}
                </CBadge>
              </td>
              <td class="td-status">
                <CBadge 
                  class="unified-badge status-badge"
                  :class="getStatusBadgeClass(ticket.status)"
                >
                  <CIcon :icon="getStatusIcon(ticket.status)" class="badge-icon" />
                  <span>{{ getStatusText(ticket.status) }}</span>
                </CBadge>
              </td>
              <td class="td-time">
                <div class="time-cell">
                  <CIcon icon="cil-clock" class="me-1" />
                  {{ formatTime(ticket.check_in_at) }}
                </div>
              </td>
              <td class="td-actions">
                <div class="actions-group">
                  <button 
                    v-if="ticket.status === 'waiting'"
                    class="action-btn" 
                    @click="callTicket(ticket)"
                    :title="t('queue.call')"
                    :disabled="loading"
                  >
                    <CIcon icon="cil-bell" />
                  </button>
                  <button 
                    v-if="ticket.status === 'called'"
                    class="action-btn" 
                    @click="startServing(ticket)"
                    :title="t('queue.startService')"
                    :disabled="loading"
                  >
                    <CIcon icon="cil-play" />
                  </button>
                  <button 
                    v-if="ticket.status === 'serving'"
                    class="action-btn" 
                    @click="completeTicket(ticket)"
                    :title="t('queue.complete')"
                    :disabled="loading"
                  >
                    <CIcon icon="cil-check" />
                  </button>
                  <button 
                    class="action-btn" 
                    @click="viewTicket(ticket)" 
                    :title="t('common.view')"
                  >
                    <CIcon icon="cil-info" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </CTable>
      </div>

      <!-- Pagination -->
      <template #footer>
        <div v-if="pagination.total_pages > 1" class="d-flex justify-content-between align-items-center">
          <div class="text-muted">
            {{ t('common.view') }} {{ (pagination.current_page - 1) * pagination.per_page + 1 }} 
            {{ t('common.to') }} 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
            {{ t('common.of') }} 
            {{ pagination.total }}
          </div>
          <CPagination
            :pages="pagination.total_pages"
            :active-page="pagination.current_page"
            @update:active-page="changePage"
          />
        </div>
      </template>

      <!-- Auto-refresh indicator -->
      <div v-if="autoRefreshEnabled && !loading" class="auto-refresh-indicator">
        <CIcon icon="cil-reload" class="spinning-slow me-2" />
        <small class="text-muted">{{ t('queue.refreshInterval') }}</small>
      </div>
    </Card>

    <!-- View Ticket Modal -->
    <CModal :visible="showViewModal" @close="closeViewModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-list" class="me-2" />
          {{ t('queue.ticketDetails') || 'Ticket Details' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody v-if="viewingTicket">
        <div class="ticket-details-view">
          <!-- Ticket Header -->
          <div class="ticket-header">
            <div class="ticket-avatar">
              <CIcon icon="cil-list" />
            </div>
            <div class="ticket-header-info">
              <h4 class="ticket-number-large">{{ viewingTicket.ticket_number || `T-${String(viewingTicket.id).padStart(4, '0')}` }}</h4>
              <CBadge 
                class="unified-badge status-badge-large"
                :class="getStatusBadgeClass(viewingTicket.status)"
              >
                <CIcon :icon="getStatusIcon(viewingTicket.status)" class="me-1" />
                {{ getStatusText(viewingTicket.status) }}
              </CBadge>
            </div>
          </div>

          <!-- Ticket Stats -->
          <div class="ticket-stats-grid">
            <div class="stat-item">
              <CIcon icon="cil-clock" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('queue.checkInTime') }}</div>
                <div class="stat-value">{{ formatTime(viewingTicket.check_in_at) }}</div>
              </div>
            </div>
            <div class="stat-item" v-if="viewingTicket.called_at">
              <CIcon icon="cil-bell" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('queue.called') }}</div>
                <div class="stat-value">{{ formatTime(viewingTicket.called_at) }}</div>
              </div>
            </div>
            <div class="stat-item" v-if="viewingTicket.started_at">
              <CIcon icon="cil-play" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('queue.serviceStarted') }}</div>
                <div class="stat-value">{{ formatTime(viewingTicket.started_at) }}</div>
              </div>
            </div>
            <div class="stat-item" v-if="viewingTicket.completed_at">
              <CIcon icon="cil-check-circle" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('queue.complete') }}</div>
                <div class="stat-value">{{ formatTime(viewingTicket.completed_at) }}</div>
              </div>
            </div>
          </div>

          <!-- Ticket Information -->
          <div class="ticket-info-grid">
            <div class="info-row" v-if="viewingTicket.customer_name">
              <div class="info-label">
                <CIcon icon="cil-user" class="me-2" />
                {{ t('queue.customer') }}
              </div>
              <div class="info-value">{{ viewingTicket.customer_name }}</div>
            </div>
            <div class="info-row" v-if="viewingTicket.customer_phone">
              <div class="info-label">
                <CIcon icon="cil-phone" class="me-2" />
                {{ t('customers.phone') }}
              </div>
              <div class="info-value">
                <a :href="`tel:${viewingTicket.customer_phone}`" class="info-link">
                  {{ viewingTicket.customer_phone }}
                </a>
              </div>
            </div>
            <div class="info-row" v-if="viewingTicket.service_name">
              <div class="info-label">
                <CIcon icon="cil-spreadsheet" class="me-2" />
                {{ t('queue.service') }}
              </div>
              <div class="info-value">{{ viewingTicket.service_name }}</div>
            </div>
            <div class="info-row" v-if="viewingTicket.staff_name">
              <div class="info-label">
                <CIcon icon="cil-user" class="me-2" />
                {{ t('queue.staff') }}
              </div>
              <div class="info-value">{{ viewingTicket.staff_name }}</div>
            </div>
            <div class="info-row" v-if="viewingTicket.notes">
              <div class="info-label">
                <CIcon icon="cil-notes" class="me-2" />
                {{ t('common.notes') || 'Notes' }}
              </div>
              <div class="info-value">{{ viewingTicket.notes }}</div>
            </div>
            <div class="info-row" v-if="viewingTicket.created_at">
              <div class="info-label">
                <CIcon icon="cil-calendar" class="me-2" />
                {{ t('common.createdAt') || 'Created At' }}
              </div>
              <div class="info-value">
                {{ viewingTicket.created_at ? new Date(viewingTicket.created_at).toLocaleString() : '-' }}
              </div>
            </div>
          </div>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeViewModal">{{ t('common.close') || 'Close' }}</CButton>
        <CButton 
          v-if="viewingTicket && viewingTicket.status === 'waiting'"
          color="primary" 
          class="btn-primary-custom"
          @click="() => { closeViewModal(); callTicket(viewingTicket); }"
        >
          <CIcon icon="cil-bell" class="me-2" />
          {{ t('queue.call') }}
        </CButton>
        <CButton 
          v-if="viewingTicket && viewingTicket.status === 'called'"
          color="primary" 
          class="btn-primary-custom"
          @click="() => { closeViewModal(); startServing(viewingTicket); }"
        >
          <CIcon icon="cil-play" class="me-2" />
          {{ t('queue.startService') }}
        </CButton>
        <CButton 
          v-if="viewingTicket && viewingTicket.status === 'serving'"
          color="primary" 
          class="btn-primary-custom"
          @click="() => { closeViewModal(); completeTicket(viewingTicket); }"
        >
          <CIcon icon="cil-check" class="me-2" />
          {{ t('queue.complete') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Add Queue Modal - Simple Form -->
    <CModal 
      :visible="showAddQueueModal" 
      @close="closeAddQueueModal"
      size="lg"
      backdrop="static"
    >
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('queue.createTicket') || 'Create Queue Ticket' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <div class="create-ticket-form">
          <p class="form-description">
            <CIcon icon="cil-info" class="me-2" />
            {{ t('queue.createTicketDesc') || 'Issue a ticket for a walk-in customer.' }}
          </p>

          <div class="form-group">
            <label class="form-label">
              {{ t('bookings.customer') }} <span class="text-muted">({{ t('common.optional') || 'optional' }})</span>
            </label>
            <CFormSelect 
              v-model="newTicketForm.customer_id"
              class="form-select-custom"
            >
              <option :value="null">{{ t('queue.walkIn') || 'Walk-in' }}</option>
              <option 
                v-for="customer in customers" 
                :key="customer.id" 
                :value="customer.id"
              >
                {{ customer.name }}{{ customer.phone ? ' - ' + customer.phone : '' }}
              </option>
            </CFormSelect>
          </div>

          <div class="form-group">
            <label class="form-label">
              {{ t('bookings.service') }} <span class="text-danger">*</span>
            </label>
            <CFormSelect 
              v-model="newTicketForm.service_id"
              class="form-select-custom"
              required
              @change="onServiceChange"
            >
              <option :value="null">{{ t('queue.selectService') || 'Select service' }}</option>
              <option 
                v-for="service in services" 
                :key="service.id" 
                :value="service.id"
              >
                {{ service.name || service.name_ar || service.title }}
              </option>
            </CFormSelect>
          </div>

          <div class="form-group">
            <label class="form-label">
              {{ t('bookings.staff') }} <span class="text-muted">({{ t('common.optional') || 'optional' }})</span>
            </label>
            <CFormSelect 
              v-model="newTicketForm.staff_id"
              class="form-select-custom staff-select"
              :disabled="!newTicketForm.service_id"
            >
              <option :value="null">{{ t('queue.anyAvailableStaff') || 'Any Available Staff' }}</option>
              <option 
                v-for="staff in availableStaffForService" 
                :key="staff.id" 
                :value="staff.id"
                :class="staff.isBusy ? 'staff-busy-option' : 'staff-available-option'"
              >
                {{ getStaffDisplayText(staff) }}
              </option>
            </CFormSelect>
            <small v-if="!newTicketForm.service_id" class="text-muted d-block mt-1">
              {{ t('queue.selectServiceFirst') || 'Please select a service first' }}
            </small>
            <div v-if="newTicketForm.service_id && availableStaffForService.length > 0" class="staff-status-legend mt-2">
              <small class="text-muted d-flex align-items-center gap-2">
                <span class="status-indicator available"></span>
                {{ t('workerCalls.available') }}
                <span class="status-indicator busy ms-3"></span>
                {{ t('workerCalls.busy') }}
              </small>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">
              {{ t('common.notes') || 'Notes' }}
            </label>
            <CFormTextarea
              v-model="newTicketForm.notes"
              :placeholder="t('queue.notesPlaceholder') || 'Optional notes for reception...'"
              rows="3"
              class="form-textarea-custom"
            />
          </div>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeAddQueueModal">
          {{ t('common.cancel') || 'Cancel' }}
        </CButton>
        <CButton 
          color="primary" 
          class="btn-primary-custom"
          @click="createTicket"
          :disabled="!newTicketForm.service_id || creatingTicket"
        >
          <CIcon icon="cil-plus" class="me-2" />
          {{ creatingTicket ? (t('common.creating') || 'Creating...') : (t('queue.createTicket') || 'Create Ticket') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Upcoming Bookings Section -->
    <Card 
      v-if="upcomingBookings.length > 0"
      :title="t('queue.upcomingBookings') || 'Upcoming Bookings'" 
      icon="cil-calendar"
      class="upcoming-bookings-card"
    >
      <div class="upcoming-bookings-list">
        <div
          v-for="booking in upcomingBookings"
          :key="booking.id"
          class="upcoming-booking-item"
          :class="{ 'has-queue': booking.queue_ticket_id }"
        >
          <div class="booking-time-display">
            <CIcon icon="cil-clock" class="me-2" />
            <strong>{{ booking.booking_time }}</strong>
          </div>
          <div class="booking-details">
            <div class="booking-customer-name">
              <CIcon icon="cil-user" class="me-1" />
              {{ booking.customer_name }}
            </div>
            <div class="booking-service-name">
              <CIcon icon="cil-spreadsheet" class="me-1" />
              {{ booking.service_name }}
            </div>
            <div class="booking-staff-name" v-if="booking.staff_name">
              <CIcon icon="cil-user-female" class="me-1" />
              {{ booking.staff_name }}
            </div>
          </div>
          <div class="booking-action">
            <CBadge 
              v-if="booking.queue_ticket_id" 
              color="success"
            >
              <CIcon icon="cil-check-circle" class="me-1" />
              {{ t('queue.inQueue') || 'In Queue' }}
            </CBadge>
            <CButton
              v-else
              color="primary"
              size="sm"
              @click="addBookingToQueue(booking)"
              :disabled="addingToQueue === booking.id"
            >
              <CIcon icon="cil-plus" class="me-1" />
              {{ t('queue.addToQueue') || 'Add to Queue' }}
            </CButton>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import {
  CButton,
  CTable,
  CBadge,
  CButtonGroup,
  CPagination,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CInputGroup,
  CInputGroupText,
  CFormInput,
  CFormSelect,
  CFormTextarea,
  CRow,
  CCol,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import { useRouter } from 'vue-router';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const router = useRouter();

const tickets = ref([]);
const loading = ref(false);
const showViewModal = ref(false);
const viewingTicket = ref(null);
const autoRefreshEnabled = ref(true);
let autoRefreshInterval = null;

// Add Queue Modal
const showAddQueueModal = ref(false);
const upcomingBookings = ref([]);
const loadingBookings = ref(false);
const bookingSearch = ref('');
const addingToQueue = ref(null);
const creatingTicket = ref(false);

// Form data
const newTicketForm = ref({
  customer_id: null,
  service_id: null,
  staff_id: null,
  notes: '',
});

// Options
const customers = ref([]);
const services = ref([]);
const staffList = ref([]);
const workerCallsForStatus = ref([]);
const loadingCustomers = ref(false);
const loadingServices = ref(false);
const loadingStaff = ref(false);

// Computed: Available staff for selected service with busy status
const availableStaffForService = computed(() => {
  if (!newTicketForm.value.service_id) {
    return [];
  }

  return staffList.value
    .filter(staff => {
      // Only active staff
      if (!staff.is_active) return false;

      // Parse service_ids
      let serviceIds = [];
      if (staff.service_ids) {
        if (Array.isArray(staff.service_ids)) {
          serviceIds = staff.service_ids;
        } else if (typeof staff.service_ids === 'string') {
          try {
            serviceIds = JSON.parse(staff.service_ids);
          } catch (e) {
            serviceIds = [];
          }
        }
      }

      // If staff has no service_ids assigned, they can do any service
      if (!serviceIds || serviceIds.length === 0) {
        return true;
      }

      // Check if staff can perform this service
      return serviceIds.includes(newTicketForm.value.service_id);
    })
    .map(staff => {
      // Check if staff is busy
      const currentCall = workerCallsForStatus.value.find(
        (c) =>
          c.staff_id === staff.id &&
          ['pending', 'customer_called', 'staff_called'].includes(c.status)
      );

      const isBusy =
        currentCall &&
        currentCall.queue_status &&
        currentCall.queue_status !== 'completed';

      return {
        ...staff,
        isBusy,
        currentCall,
      };
    })
    .sort((a, b) => {
      // Available staff first, then busy
      if (a.isBusy === b.isBusy) return 0;
      return a.isBusy ? 1 : -1;
    });
});

const getStaffDisplayText = (staff) => {
  const statusText = staff.isBusy 
    ? `[${t('workerCalls.busy')}]` 
    : `[${t('workerCalls.available')}]`;
  const name = staff.name;
  const position = staff.position ? ` - ${staff.position}` : '';
  return `${statusText} ${name}${position}`;
};

const filters = ref({
  search: '',
  status: '',
  date_filter: '',
});

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
});

const waitingCount = computed(() => tickets.value.filter(t => t.status === 'waiting').length);
const servingCount = computed(() => tickets.value.filter(t => t.status === 'serving').length);
const completedCount = computed(() => tickets.value.filter(t => t.status === 'completed').length);
const totalTodayCount = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  return tickets.value.filter(t => {
    const ticketDate = t.created_at ? new Date(t.created_at).toISOString().split('T')[0] : null;
    return ticketDate === today;
  }).length;
});

const filteredTickets = computed(() => {
  let filtered = [...tickets.value];

  // Filter by status
  if (filters.value.status) {
    filtered = filtered.filter(t => t.status === filters.value.status);
  }

  // Filter by search query
  if (filters.value.search) {
    const query = filters.value.search.toLowerCase();
    filtered = filtered.filter(t => {
      const ticketNum = (t.ticket_number || `T-${t.id}`).toLowerCase();
      const customerName = (t.customer_name || '').toLowerCase();
      const serviceName = (t.service_name || '').toLowerCase();
      const staffName = (t.staff_name || '').toLowerCase();
      
      return ticketNum.includes(query) || 
             customerName.includes(query) || 
             serviceName.includes(query) ||
             staffName.includes(query);
    });
  }

  // Filter by date
  if (filters.value.date_filter) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    
    filtered = filtered.filter(t => {
      if (!t.created_at) return false;
      const ticketDate = new Date(t.created_at);
      
      if (filters.value.date_filter === 'today') {
        return ticketDate >= today;
      } else if (filters.value.date_filter === 'week') {
        const weekAgo = new Date(today);
        weekAgo.setDate(weekAgo.getDate() - 7);
        return ticketDate >= weekAgo;
      } else if (filters.value.date_filter === 'month') {
        const monthAgo = new Date(today);
        monthAgo.setMonth(monthAgo.getMonth() - 1);
        return ticketDate >= monthAgo;
      }
      return true;
    });
  }

  // Sort: waiting first, then by created_at
  return filtered.sort((a, b) => {
    const statusOrder = { waiting: 1, called: 2, serving: 3, completed: 4 };
    const aOrder = statusOrder[a.status] || 5;
    const bOrder = statusOrder[b.status] || 5;
    
    if (aOrder !== bOrder) {
      return aOrder - bOrder;
    }
    
    return new Date(b.created_at) - new Date(a.created_at);
  });
});

const getStatusBadgeClass = (status) => {
  const classes = {
    waiting: 'status-waiting',
    called: 'status-called',
    serving: 'status-serving',
    completed: 'status-completed',
  };
  return classes[status] || '';
};

const getStatusText = (status) => {
  const texts = {
    waiting: t('queue.waiting'),
    called: t('queue.called'),
    serving: t('queue.inProgress'),
    completed: t('bookings.completed'),
    cancelled: t('bookings.cancelled'),
  };
  return texts[status] || status;
};

const getStatusIcon = (status) => {
  const icons = {
    waiting: 'cil-clock',
    called: 'cil-bell',
    serving: 'cil-user',
    completed: 'cil-check-circle',
    cancelled: 'cil-x-circle',
  };
  return icons[status] || 'cil-info';
};

const formatTime = (datetime) => {
  if (!datetime) return '-';
  return new Date(datetime).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const loadTickets = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
      ...filters.value,
    };

    // Remove empty filters
    Object.keys(params).forEach(key => {
      if (params[key] === '') delete params[key];
    });

    const response = await api.get('/queue', { params });
    const data = response.data?.data || response.data || {};
    
    tickets.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
  } catch (error) {
    console.error('Error loading tickets:', error);
    tickets.value = [];
    toast.error(t('common.errorLoading'));
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  pagination.value.current_page = page;
  loadTickets();
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    loadTickets();
  }, 500);
};

const resetFilters = () => {
  filters.value = { search: '', status: '', date_filter: '' };
  pagination.value.current_page = 1;
  loadTickets();
};

const filterByStatus = (status) => {
  filters.value.status = status || '';
  pagination.value.current_page = 1;
  loadTickets();
};

const callNext = async () => {
  if (waitingCount.value === 0) {
    toast.warning(t('queue.noWaitingTickets'));
    return;
  }

  try {
    const response = await api.post('/queue/call-next');
    if (response.data?.data) {
      await callTicket(response.data.data);
    } else {
      toast.warning(t('queue.noWaitingTickets'));
    }
  } catch (error) {
    console.error('Error calling next:', error);
    toast.error(t('queue.errorCallingTicket'));
  }
};

const callTicket = async (ticket) => {
  try {
    await api.post(`/queue/${ticket.id}/call`);
    toast.success(`${t('queue.ticketCalled')}: ${ticket.ticket_number || ticket.id}`);
    await loadTickets();
  } catch (error) {
    console.error('Error calling ticket:', error);
    toast.error(t('queue.errorCallingTicket'));
  }
};

const startServing = async (ticket) => {
  try {
    await api.post(`/queue/${ticket.id}/start`);
    toast.success(`${t('queue.serviceStarted')}: ${ticket.ticket_number || ticket.id}`);
    await loadTickets();
  } catch (error) {
    console.error('Error starting service:', error);
    toast.error(t('queue.errorStartingService'));
  }
};

const completeTicket = async (ticket) => {
  try {
    await api.post(`/queue/${ticket.id}/complete`);
    toast.success(`${t('queue.ticketCompleted')}: ${ticket.ticket_number || ticket.id}`);
    await loadTickets();
  } catch (error) {
    console.error('Error completing ticket:', error);
    toast.error(t('queue.errorCompletingTicket'));
  }
};

const viewTicket = async (ticket) => {
  try {
    const response = await api.get(`/queue/${ticket.id}`);
    viewingTicket.value = response.data?.data || response.data || ticket;
    showViewModal.value = true;
  } catch (error) {
    console.error('Error loading ticket details:', error);
    viewingTicket.value = ticket;
    showViewModal.value = true;
  }
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewingTicket.value = null;
};

const exportData = () => {
  const payload = {
    exported_at: new Date().toISOString(),
    tickets: tickets.value,
    stats: {
      waiting: waitingCount.value,
      serving: servingCount.value,
      completed: completedCount.value,
      totalToday: totalTodayCount.value,
    },
  };

  const json = JSON.stringify(payload, null, 2);
  const blob = new Blob([json], { type: 'application/json;charset=utf-8' });
  const url = URL.createObjectURL(blob);

  const a = document.createElement('a');
  a.href = url;
  a.download = `queue-export-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')}.json`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);

  toast.success('✅ ' + t('common.export') + ' ' + t('queue.title'));
};

const toggleAutoRefresh = () => {
  autoRefreshEnabled.value = !autoRefreshEnabled.value;
  
  if (autoRefreshEnabled.value) {
    startAutoRefresh();
  } else {
    stopAutoRefresh();
  }
};

const startAutoRefresh = () => {
  stopAutoRefresh();
  autoRefreshInterval = setInterval(() => {
    if (!loading.value) {
      loadTickets();
    }
  }, 5000);
};

const stopAutoRefresh = () => {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval);
    autoRefreshInterval = null;
  }
};

const openDisplayMode = () => {
  const currentUrl = window.location.href.split('#')[0];
  const displayUrl = `${currentUrl}#/display/queue`;
  window.open(displayUrl, '_blank', 'fullscreen=yes,width=1920,height=1080');
};

// Load Customers and Services
const loadCustomers = async () => {
  loadingCustomers.value = true;
  try {
    const response = await api.get('/customers', {
      params: { per_page: 100, is_active: 1 },
    });
    customers.value = response.data?.data?.items || response.data?.items || [];
  } catch (error) {
    console.error('Error loading customers:', error);
    customers.value = [];
  } finally {
    loadingCustomers.value = false;
  }
};

const loadServices = async () => {
  loadingServices.value = true;
  try {
    const response = await api.get('/services', {
      params: { per_page: 100, is_active: 1 },
    });
    services.value = response.data?.data?.items || response.data?.items || [];
  } catch (error) {
    console.error('Error loading services:', error);
    services.value = [];
  } finally {
    loadingServices.value = false;
  }
};

const loadStaff = async () => {
  loadingStaff.value = true;
  try {
    const [staffResponse, callsResponse] = await Promise.all([
      api.get('/staff', {
        params: { per_page: 100, is_active: 1 },
      }),
      api.get('/worker-calls', {
        params: { per_page: 200 },
      }),
    ]);
    
    const staffData = staffResponse.data?.data?.items || staffResponse.data?.items || [];
    workerCallsForStatus.value = callsResponse.data?.data?.items || callsResponse.data?.items || [];
    
    // Parse service_ids for each staff
    staffList.value = staffData.map(staff => {
      let serviceIds = [];
      if (staff.service_ids) {
        if (Array.isArray(staff.service_ids)) {
          serviceIds = staff.service_ids;
        } else if (typeof staff.service_ids === 'string') {
          try {
            serviceIds = JSON.parse(staff.service_ids);
          } catch (e) {
            serviceIds = [];
          }
        }
      }
      return {
        ...staff,
        service_ids: serviceIds,
      };
    });
  } catch (error) {
    console.error('Error loading staff:', error);
    staffList.value = [];
    workerCallsForStatus.value = [];
  } finally {
    loadingStaff.value = false;
  }
};

const onServiceChange = () => {
  // Reset staff selection when service changes
  newTicketForm.value.staff_id = null;
};

// Add Queue Modal Functions
const openAddQueueModal = async () => {
  showAddQueueModal.value = true;
  newTicketForm.value = {
    customer_id: null,
    service_id: null,
    staff_id: null,
    notes: '',
  };
  await Promise.all([loadCustomers(), loadServices(), loadStaff()]);
};

const closeAddQueueModal = () => {
  showAddQueueModal.value = false;
  newTicketForm.value = {
    customer_id: null,
    service_id: null,
    staff_id: null,
    notes: '',
  };
};

const createTicket = async () => {
  if (!newTicketForm.value.service_id) {
    toast.error(t('queue.serviceRequired') || 'Service is required');
    return;
  }

  creatingTicket.value = true;
  try {
    const payload = {
      customer_id: newTicketForm.value.customer_id || null,
      service_id: newTicketForm.value.service_id,
      staff_id: newTicketForm.value.staff_id || null,
      notes: newTicketForm.value.notes || '',
    };

    const response = await api.post('/queue', payload);
    
    if (response.data?.data) {
      toast.success(t('queue.ticketCreated') || 'Ticket created successfully');
      closeAddQueueModal();
      await loadTickets();
      await loadUpcomingBookings();
    } else {
      toast.error(t('queue.errorCreatingTicket') || 'Error creating ticket');
    }
  } catch (error) {
    console.error('Error creating ticket:', error);
    toast.error(t('queue.errorCreatingTicket') || 'Error creating ticket');
  } finally {
    creatingTicket.value = false;
  }
};

const loadUpcomingBookings = async () => {
  loadingBookings.value = true;
  try {
    const today = new Date().toISOString().split('T')[0];
    const response = await api.get('/bookings', {
      params: {
        booking_date: today,
        status: 'pending,confirmed',
        per_page: 100,
      },
    });
    
    const bookings = response.data?.data?.items || response.data?.items || [];
    // Filter upcoming bookings (today and future)
    upcomingBookings.value = bookings.filter(booking => {
      if (!booking.booking_date) return false;
      const bookingDate = new Date(booking.booking_date);
      const todayDate = new Date();
      todayDate.setHours(0, 0, 0, 0);
      return bookingDate >= todayDate;
    }).sort((a, b) => {
      // Sort by date and time
      const dateA = new Date(`${a.booking_date} ${a.booking_time}`);
      const dateB = new Date(`${b.booking_date} ${b.booking_time}`);
      return dateA - dateB;
    });
  } catch (error) {
    console.error('Error loading upcoming bookings:', error);
    upcomingBookings.value = [];
  } finally {
    loadingBookings.value = false;
  }
};

const filteredUpcomingBookings = computed(() => {
  if (!bookingSearch.value) {
    return upcomingBookings.value;
  }
  
  const query = bookingSearch.value.toLowerCase();
  return upcomingBookings.value.filter(booking => {
    const customerName = (booking.customer_name || '').toLowerCase();
    const serviceName = (booking.service_name || '').toLowerCase();
    const staffName = (booking.staff_name || '').toLowerCase();
    const bookingTime = (booking.booking_time || '').toLowerCase();
    
    return customerName.includes(query) || 
           serviceName.includes(query) ||
           staffName.includes(query) ||
           bookingTime.includes(query);
  });
});

const filterBookings = () => {
  // Reactive filtering is handled by computed property
};

const addBookingToQueue = async (booking) => {
  addingToQueue.value = booking.id;
  
  try {
    const payload = {
      customer_id: booking.customer_id,
      service_id: booking.service_id,
      staff_id: booking.staff_id || null,
      booking_id: booking.id,
      notes: `From booking: ${booking.booking_date} ${booking.booking_time}`,
    };
    
    const response = await api.post('/queue', payload);
    
    if (response.data?.data) {
      toast.success(t('queue.bookingAddedToQueue') || 'Booking added to queue successfully');
      await loadTickets();
      await loadUpcomingBookings(); // Refresh to show queue_ticket_id
    } else {
      toast.error(t('queue.errorAddingToQueue') || 'Error adding booking to queue');
    }
  } catch (error) {
    console.error('Error adding booking to queue:', error);
    toast.error(t('queue.errorAddingToQueue') || 'Error adding booking to queue');
  } finally {
    addingToQueue.value = null;
  }
};

const getBookingStatusColor = (status) => {
  const colorMap = {
    pending: 'warning',
    confirmed: 'info',
    completed: 'success',
    cancelled: 'danger',
  };
  return colorMap[status] || 'secondary';
};

const formatBookingDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

onMounted(() => {
  loadTickets();
  loadUpcomingBookings();
  if (autoRefreshEnabled.value) {
    startAutoRefresh();
  }
  
  // Auto-refresh upcoming bookings every 30 seconds
  setInterval(() => {
    if (!loadingBookings.value) {
      loadUpcomingBookings();
    }
  }, 30000);
});

onUnmounted(() => {
  stopAutoRefresh();
});
</script>

<style scoped>
.queue-page {
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

/* Filters */
.filters-card {
  border: 1px solid var(--border-color);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.search-input-group {
  position: relative;
}

.search-icon-wrapper {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  color: white;
  border-color: var(--asmaa-primary);
}

.filter-input,
.filter-select {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid var(--border-color);
}

.filter-input:focus,
.filter-select:focus {
  border-color: var(--asmaa-primary);
  box-shadow: 0 0 0 3px rgba(187, 160, 122, 0.15);
  outline: none;
}

.search-input:focus {
  border-left: none;
}

.reset-btn {
  transition: all 0.3s;
}

.reset-btn:hover {
  background: var(--asmaa-primary);
  color: white;
  border-color: var(--asmaa-primary);
  transform: translateY(-1px);
}

/* Primary Button */
.btn-primary-custom {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  border: none;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
  transition: all 0.3s;
}

.btn-primary-custom:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
  box-shadow: 0 6px 16px rgba(187, 160, 122, 0.4);
  transform: translateY(-2px);
}

.btn-primary-custom:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Table Wrapper */
.table-wrapper {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
}

.queue-table {
  margin: 0;
  border-collapse: separate;
  border-spacing: 0;
}

.table-header-row {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  border-bottom: 2px solid var(--asmaa-primary);
}

.table-header-row th {
  padding: 1rem 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
  border-bottom: none;
  white-space: nowrap;
}

.th-id {
  width: 80px;
  text-align: center;
}

.th-ticket {
  min-width: 150px;
}

.th-customer {
  min-width: 200px;
}

.th-service {
  min-width: 180px;
}

.th-staff {
  min-width: 150px;
}

.th-status {
  width: 140px;
  text-align: center;
}

.th-time {
  width: 130px;
  text-align: center;
}

.th-actions {
  width: 160px;
  text-align: center;
}

/* Table Rows */
.queue-row {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-bottom: 1px solid var(--border-color);
}

.queue-row:last-child {
  border-bottom: none;
}

.queue-row:hover {
  background: linear-gradient(90deg, rgba(187, 160, 122, 0.05) 0%, rgba(187, 160, 122, 0.02) 100%);
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(187, 160, 122, 0.1);
}

[dir="rtl"] .queue-row:hover {
  transform: translateX(-4px);
}

.queue-row td {
  padding: 1rem 1.25rem;
  vertical-align: middle;
  border-bottom: 1px solid var(--border-color);
}

.highlight-row {
  background-color: rgba(255, 193, 7, 0.08);
  border-left: 3px solid #f59e0b;
}

[dir="rtl"] .highlight-row {
  border-left: none;
  border-right: 3px solid #f59e0b;
}

.serving-row {
  background-color: rgba(59, 130, 246, 0.08);
  border-left: 3px solid #3b82f6;
}

[dir="rtl"] .serving-row {
  border-left: none;
  border-right: 3px solid #3b82f6;
}

.completed-row {
  background-color: rgba(16, 185, 129, 0.05);
  opacity: 0.85;
}

/* Ticket ID */
.ticket-id-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  color: white;
  font-weight: 800;
  font-size: 0.875rem;
  box-shadow: 0 2px 8px rgba(187, 160, 122, 0.3);
  transition: all 0.3s;
}

.queue-row:hover .ticket-id-badge {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

.ticket-number {
  color: var(--asmaa-primary);
  font-size: 1rem;
  font-weight: 700;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.queue-customer-cell strong {
  display: block;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.queue-customer-cell small {
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  color: var(--text-muted);
}

.queue-service-cell {
  display: flex;
  align-items: center;
  color: var(--text-secondary);
  font-weight: 500;
}

.staff-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.875rem;
}

.time-cell {
  display: flex;
  align-items: center;
  color: var(--text-secondary);
  font-size: 0.875rem;
  font-weight: 500;
}

/* Unified Badges */
.unified-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 600;
  transition: all 0.3s;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.15) 0%, rgba(187, 160, 122, 0.1) 100%);
  color: var(--asmaa-primary);
  border: 1px solid rgba(187, 160, 122, 0.3);
}

.unified-badge .badge-icon {
  width: 14px;
  height: 14px;
  color: var(--asmaa-primary);
}

.unified-badge:hover {
  transform: translateY(-2px);
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.25) 0%, rgba(187, 160, 122, 0.15) 100%);
  box-shadow: 0 4px 8px rgba(187, 160, 122, 0.2);
  border-color: var(--asmaa-primary);
}

.status-badge.status-waiting {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 193, 7, 0.15) 100%);
  color: #f59e0b;
  border-color: rgba(255, 193, 7, 0.4);
}

.status-badge.status-waiting .badge-icon {
  color: #f59e0b;
}

.status-badge.status-called {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.15) 100%);
  color: #3b82f6;
  border-color: rgba(59, 130, 246, 0.4);
}

.status-badge.status-called .badge-icon {
  color: #3b82f6;
}

.status-badge.status-serving {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.25) 0%, rgba(187, 160, 122, 0.2) 100%);
  color: var(--asmaa-primary);
  border-color: rgba(187, 160, 122, 0.5);
}

.status-badge.status-completed {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.15) 100%);
  color: #10b981;
  border-color: rgba(16, 185, 129, 0.4);
}

.status-badge.status-completed .badge-icon {
  color: #10b981;
}

.badge-icon {
  width: 14px;
  height: 14px;
}

/* Actions */
.actions-group {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  color: white;
  box-shadow: 0 2px 6px rgba(187, 160, 122, 0.3);
}

.action-btn::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: translate(-50%, -50%);
  transition: width 0.3s, height 0.3s;
}

.action-btn:hover::before {
  width: 100px;
  height: 100px;
}

.action-btn:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

.action-btn CIcon {
  position: relative;
  z-index: 1;
  width: 18px;
  height: 18px;
  transition: all 0.3s;
}

.action-btn:active {
  transform: translateY(0) scale(0.95);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.auto-refresh-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem;
  margin-top: 1rem;
  border-top: 1px solid var(--border-color);
}

.spinning {
  animation: spin 1s linear infinite;
}

.spinning-slow {
  animation: spin 2s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Ticket Details Modal */
.ticket-details-view {
  padding: 0.5rem 0;
}

.ticket-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(187, 160, 122, 0.2);
}

.ticket-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}

.ticket-avatar CIcon {
  width: 40px;
  height: 40px;
}

.ticket-header-info {
  flex: 1;
}

.ticket-number-large {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.status-badge-large {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
}

.ticket-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.08) 0%, rgba(187, 160, 122, 0.03) 100%);
  border-radius: 10px;
  border: 1px solid rgba(187, 160, 122, 0.15);
  transition: all 0.3s;
}

.stat-item:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.12) 0%, rgba(187, 160, 122, 0.06) 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(187, 160, 122, 0.15);
}

.stat-icon {
  width: 32px;
  height: 32px;
  color: var(--asmaa-primary);
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-primary);
}

.ticket-info-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: 8px;
  border: 1px solid var(--border-color);
  transition: all 0.3s;
}

.info-row:hover {
  background: rgba(187, 160, 122, 0.05);
  border-color: rgba(187, 160, 122, 0.3);
}

.info-label {
  min-width: 140px;
  font-weight: 600;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  font-size: 0.875rem;
}

.info-label CIcon {
  width: 18px;
  height: 18px;
  color: var(--asmaa-primary);
}

.info-value {
  flex: 1;
  color: var(--text-primary);
  font-size: 0.9375rem;
}

.info-link {
  color: var(--asmaa-primary);
  text-decoration: none;
  transition: all 0.3s;
}

.info-link:hover {
  color: rgba(187, 160, 122, 0.8);
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Add Queue Modal Styles */
.bookings-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 60vh;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.booking-item-card {
  border: 2px solid var(--border-color);
  border-radius: 12px;
  padding: 1.25rem;
  background: var(--bg-primary);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.booking-item-card:hover {
  border-color: var(--asmaa-primary);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.15);
  transform: translateY(-2px);
}

.booking-item-card.has-queue {
  border-color: rgba(16, 185, 129, 0.4);
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, var(--bg-primary) 100%);
}

.booking-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--border-color);
}

.booking-time-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  color: white;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.9375rem;
  font-family: 'Courier New', monospace;
  box-shadow: 0 2px 8px rgba(187, 160, 122, 0.3);
}

.booking-status-badge {
  font-weight: 600;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
}

.booking-item-content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.booking-info-row {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.info-icon {
  width: 20px;
  height: 20px;
  color: var(--asmaa-primary);
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.info-content {
  flex: 1;
  font-size: 0.9375rem;
}

.info-content strong {
  font-weight: 600;
  color: var(--text-primary);
  margin-right: 0.5rem;
}

.info-content span {
  color: var(--text-secondary);
}

.booking-item-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding-top: 0.75rem;
  border-top: 1px solid var(--border-color);
}

.queue-exists-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  font-weight: 600;
}

.add-queue-btn {
  min-width: 140px;
}

.bookings-list::-webkit-scrollbar {
  width: 8px;
}

.bookings-list::-webkit-scrollbar-track {
  background: var(--bg-secondary);
  border-radius: 4px;
}

.bookings-list::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  border-radius: 4px;
}

.bookings-list::-webkit-scrollbar-thumb:hover {
  background: var(--asmaa-primary);
}

/* Create Ticket Form */
.create-ticket-form {
  padding: 0.5rem 0;
}

.form-description {
  color: var(--text-secondary);
  margin-bottom: 1.5rem;
  padding: 0.75rem;
  background: rgba(187, 160, 122, 0.05);
  border-radius: 8px;
  border-left: 3px solid var(--asmaa-primary);
  display: flex;
  align-items: center;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-primary);
}

.form-select-custom,
.form-textarea-custom {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background: var(--bg-primary);
  color: var(--text-primary);
  transition: all 0.3s;
}

.form-select-custom:focus,
.form-textarea-custom:focus {
  outline: none;
  border-color: var(--asmaa-primary);
  box-shadow: 0 0 0 3px rgba(187, 160, 122, 0.1);
}

.form-textarea-custom {
  resize: vertical;
  min-height: 80px;
}

/* Staff Select with Status */
.staff-select {
  font-weight: 500;
}

.staff-select option.staff-available-option {
  color: #10b981;
  font-weight: 600;
}

.staff-select option.staff-busy-option {
  color: #ef4444;
  font-weight: 600;
}

.staff-status-legend {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-indicator {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.status-indicator.available {
  background: #10b981;
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

.status-indicator.busy {
  background: #ef4444;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}

/* Upcoming Bookings Card */
.upcoming-bookings-card {
  margin-top: 1.5rem;
}

.upcoming-bookings-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.upcoming-booking-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border: 2px solid var(--border-color);
  border-radius: 12px;
  transition: all 0.3s;
}

.upcoming-booking-item:hover {
  border-color: var(--asmaa-primary);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.15);
  transform: translateX(4px);
}

.upcoming-booking-item.has-queue {
  border-color: rgba(16, 185, 129, 0.4);
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, var(--bg-secondary) 100%);
}

.booking-time-display {
  min-width: 100px;
  display: flex;
  align-items: center;
  font-family: 'Courier New', monospace;
  font-weight: 700;
  color: var(--asmaa-primary);
  font-size: 1rem;
}

.booking-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.booking-customer-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9375rem;
}

.booking-service-name,
.booking-staff-name {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.booking-action {
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .queue-table {
    font-size: 0.8125rem;
  }

  .table-header-row th,
  .queue-row td {
    padding: 0.75rem 0.5rem;
  }

  .ticket-id-badge {
    width: 40px;
    height: 40px;
  }

  .booking-item-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .booking-item-actions {
    justify-content: stretch;
  }

  .add-queue-btn {
    width: 100%;
  }

  .upcoming-booking-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .booking-time-display {
    width: 100%;
    margin-bottom: 0.5rem;
  }

  .booking-action {
    width: 100%;
  }

  .booking-action button {
    width: 100%;
  }

  .ticket-id-badge {
    font-size: 0.75rem;
  }

  .action-btn {
    width: 32px;
    height: 32px;
  }

  .action-btn CIcon {
    width: 16px;
    height: 16px;
  }

  .ticket-stats-grid {
    grid-template-columns: 1fr;
  }

  .ticket-header {
    flex-direction: column;
    text-align: center;
  }

  .info-row {
    flex-direction: column;
    gap: 0.5rem;
  }

  .info-label {
    min-width: auto;
  }
}

@media (max-width: 576px) {
  .actions-group {
    flex-direction: column;
    gap: 0.25rem;
  }

  .action-btn {
    width: 100%;
    height: 32px;
  }
}
</style>
