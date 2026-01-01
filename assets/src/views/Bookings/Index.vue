<template>
    <div class="booking-page p-4">
        <!-- Modern Header -->
        <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
            <div class="header-left d-flex align-items-center gap-3">
                <h2 class="mb-0 fw-bold text-primary">{{ t('booking.title') }}</h2>
                <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
                    <CIcon icon="cil-calendar" class="me-1" />
                    {{ bookingStore.appointments.items.length }} {{ t('booking.totalAppointments') }}
                </CBadge>
            </div>
            <div class="header-right d-flex gap-3">
                <CButton color="primary" class="nano-btn" @click="goToAddBooking">
                    <CIcon icon="cil-plus" class="me-2" />
                    {{ t('booking.addBooking') || t('booking.addAppointment') }}
                </CButton>
                <router-link to="/bookings/settings" class="btn btn-secondary nano-btn-secondary">
                    <CIcon icon="cil-settings" class="me-2" />
                    {{ t('booking.settingsButton') }}
                </router-link>
                <router-link to="/bookings/appearance" class="btn btn-secondary nano-btn-secondary">
                    <CIcon icon="cil-paint-roller" class="me-2" />
                    {{ t('booking.appearanceButton') }}
                </router-link>
            </div>
        </div>

        <!-- Quick Stats Bar (Nano Banana Style) -->
        <div class="nano-stats-bar mb-4">
            <div class="stat-card-nano">
                <div class="stat-icon-bg services"><CIcon icon="cil-spreadsheet" /></div>
                <div class="stat-info">
                    <div class="stat-value">{{ bookingStore.services.items.length }}</div>
                    <div class="stat-label">{{ t('booking.totalServices') }}</div>
                </div>
            </div>
            <div class="stat-card-nano">
                <div class="stat-icon-bg staff"><CIcon icon="cil-user" /></div>
                <div class="stat-info">
                    <div class="stat-value">{{ bookingStore.staff.items.length }}</div>
                    <div class="stat-label">{{ t('booking.totalStaff') }}</div>
                </div>
            </div>
            <div class="stat-card-nano">
                <div class="stat-icon-bg approved"><CIcon icon="cil-check-circle" /></div>
                <div class="stat-info">
                    <div class="stat-value text-success">{{ appointmentSummary.approved }}</div>
                    <div class="stat-label">{{ t('booking.approved') }}</div>
                </div>
            </div>
            <div class="stat-card-nano">
                <div class="stat-icon-bg pending"><CIcon icon="cil-clock" /></div>
                <div class="stat-info">
                    <div class="stat-value text-warning">{{ appointmentSummary.pending }}</div>
                    <div class="stat-label">{{ t('booking.pending') }}</div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation (Modern Pills) -->
        <CNav variant="pills" class="nano-tabs mb-4">
            <CNavItem v-for="tab in tabs" :key="tab.id">
                <CNavLink 
                    :active="activeTab === tab.id" 
                    @click="activeTab = tab.id"
                    class="nano-tab-link"
                >
                    <CIcon :icon="getTabIcon(tab.id)" class="me-2" />
                    {{ tab.label }}
                    <CBadge v-if="tab.count !== undefined" color="primary" shape="rounded-pill" class="ms-2">
                        {{ tab.count }}
                    </CBadge>
                </CNavLink>
            </CNavItem>
        </CNav>

        <!-- Tab Content -->
        <div class="booking-content-wrapper">
            <!-- Services Tab -->
            <div v-show="activeTab === 'services'" class="nano-panel">
                <div class="panel-header d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold">{{ t('booking.servicesTab') }}</h4>
                    <CButton color="primary" variant="ghost" @click="openServiceModal()">
                        <CIcon icon="cil-plus" class="me-1" />
                        {{ t('booking.addService') }}
                    </CButton>
                </div>
                
                <div v-if="bookingStore.services.loading" class="text-center p-5">
                    <CSpinner color="primary" />
                </div>
                <div v-else class="nano-grid">
                    <div v-for="service in bookingStore.services.items" :key="service.id" class="nano-item-card" @click="viewService(service)">
                        <div class="item-badge">{{ service.duration }} {{ t('booking.min') }}</div>
                        <div class="item-icon-circle"><CIcon icon="cil-spreadsheet" /></div>
                        <h5 class="item-title">{{ service.title }}</h5>
                        <div class="item-price-tag">{{ formatPrice(service.price) }}</div>
                        <div class="item-actions-hover">
                            <CButton size="sm" color="info" variant="ghost" @click.stop="openServiceModal(service)">
                                <CIcon icon="cil-pencil" />
                            </CButton>
                            <CButton size="sm" color="danger" variant="ghost" @click.stop="deleteService(service.id)">
                                <CIcon icon="cil-trash" />
                            </CButton>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Tab -->
            <div v-show="activeTab === 'staff'" class="nano-panel">
                <div class="panel-header d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold">{{ t('booking.staffTab') }}</h4>
                    <CButton color="primary" variant="ghost" @click="openStaffModal()">
                        <CIcon icon="cil-plus" class="me-1" />
                        {{ t('booking.addStaff') }}
                    </CButton>
                </div>
                
                <div v-if="bookingStore.staff.loading" class="text-center p-5">
                    <CSpinner color="primary" />
                </div>
                <div v-else class="nano-grid staff-grid">
                    <div v-for="member in bookingStore.staff.items" :key="member.id" class="nano-item-card staff-card">
                        <div class="staff-avatar-large">{{ member.full_name?.charAt(0) }}</div>
                        <h5 class="item-title mt-3">{{ member.full_name }}</h5>
                        <p class="text-muted small">{{ member.email }}</p>
                        <div class="staff-badges mt-2">
                            <CBadge color="success" shape="rounded-pill">Available</CBadge>
                        </div>
                        <div class="item-actions-hover">
                            <CButton size="sm" color="info" variant="ghost" @click.stop="openStaffModal(member)">
                                <CIcon icon="cil-pencil" />
                            </CButton>
                            <CButton size="sm" color="danger" variant="ghost" @click.stop="deleteStaff(member.id)">
                                <CIcon icon="cil-trash" />
                            </CButton>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments Tab -->
            <div v-show="activeTab === 'appointments'" class="nano-panel">
                <div class="panel-header mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold">{{ t('booking.appointmentsTab') }}</h4>
                        <div class="view-switcher">
                            <CButtonGroup>
                                <CButton 
                                    :color="appointmentViewMode === 'calendar' ? 'primary' : 'secondary'" 
                                    variant="outline"
                                    @click="appointmentViewMode = 'calendar'"
                                >
                                    <CIcon icon="cil-calendar" class="me-1" />{{ t('booking.calendarView') }}
                                </CButton>
                                <CButton 
                                    :color="appointmentViewMode === 'list' ? 'primary' : 'secondary'" 
                                    variant="outline"
                                    @click="appointmentViewMode = 'list'"
                                >
                                    <CIcon icon="cil-list" class="me-1" />{{ t('booking.listView') }}
                                </CButton>
                            </CButtonGroup>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="nano-filters-bar p-3 bg-tertiary rounded-4 mb-4">
                        <CRow class="g-3">
                            <CCol md="3">
                                <label class="small fw-bold text-muted mb-1">From</label>
                                <CFormInput type="date" v-model="appointmentFilters.date_from" />
                            </CCol>
                            <CCol md="3">
                                <label class="small fw-bold text-muted mb-1">To</label>
                                <CFormInput type="date" v-model="appointmentFilters.date_to" />
                            </CCol>
                            <CCol md="3">
                                <label class="small fw-bold text-muted mb-1">Status</label>
                                <CFormSelect v-model="appointmentFilters.status">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </CFormSelect>
                            </CCol>
                            <CCol md="3" class="d-flex align-items-end">
                                <CButton color="primary" class="w-100" @click="loadAppointments">
                                    <CIcon icon="cil-filter" class="me-1" />Filter
                                </CButton>
                            </CCol>
                        </CRow>
                    </div>
                </div>

                <div v-if="appointmentViewMode === 'calendar'" class="calendar-container">
                    <BookingCalendar />
                </div>

                <div v-else class="appointments-list-view">
                    <div v-if="bookingStore.appointments.loading" class="text-center p-5">
                        <CSpinner color="primary" />
                    </div>
                    <div v-else class="appointments-list d-flex flex-column gap-3">
                        <div v-for="appointment in bookingStore.appointments.items" :key="appointment.id" class="nano-appointment-card">
                            <div class="appt-date-box">
                                <div class="appt-day">{{ formatDate(appointment.start_date, 'day') }}</div>
                                <div class="appt-month">{{ formatDate(appointment.start_date, 'month') }}</div>
                                <div class="appt-time">{{ formatDate(appointment.start_date, 'time') }}</div>
                            </div>
                            <div class="appt-main-info flex-grow-1 ms-3">
                                <div class="appt-service fw-bold fs-5 text-primary">{{ getServiceName(appointment.service_id) }}</div>
                                <div class="appt-staff small text-muted">
                                    <CIcon icon="cil-user" class="me-1" />
                                    {{ t('booking.with') }}: {{ getStaffName(appointment.staff_id) }}
                                </div>
                                <div class="appt-customer small text-muted" v-if="appointment.customer_name">
                                    <CIcon icon="cil-people" class="me-1" />
                                    {{ appointment.customer_name }}
                                </div>
                            </div>
                            <div class="appt-status-box px-3">
                                <CBadge :color="getStatusColor(appointment.status)" shape="rounded-pill" class="px-3 py-2">
                                    {{ t(`booking.${appointment.status}`) }}
                                </CBadge>
                            </div>
                            <div class="appt-actions d-flex gap-2">
                                <CButton 
                                    v-if="['completed', 'in_progress'].includes(appointment.status)"
                                    color="success" 
                                    variant="ghost" 
                                    @click="checkoutBooking(appointment)"
                                    title="Checkout"
                                >
                                    <CIcon icon="cil-cart" />
                                </CButton>
                                <CButton color="info" variant="ghost" @click="openAppointmentModal(appointment)">
                                    <CIcon icon="cil-pencil" />
                                </CButton>
                                <CButton color="danger" variant="ghost" @click="deleteAppointment(appointment.id)">
                                    <CIcon icon="cil-trash" />
                                </CButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div v-show="activeTab === 'analytics'" class="nano-panel">
                <div class="panel-header mb-4">
                    <h4 class="fw-bold">{{ t('booking.analyticsTab') }}</h4>
                </div>
                
                <div class="nano-table-container">
                    <table class="nano-table w-100">
                        <thead>
                            <tr>
                                <th class="text-start">{{ t('booking.analyticsService') }}</th>
                                <th>{{ t('booking.analyticsDuration') }}</th>
                                <th>{{ t('booking.analyticsPrice') }}</th>
                                <th>{{ t('booking.analyticsAppointments') }}</th>
                                <th class="text-end">{{ t('booking.analyticsRevenue') }}</th>
                                <th>{{ t('booking.analyticsAvgDuration') }}</th>
                                <th>{{ t('booking.analyticsActions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in appointmentAnalyticsRows" :key="row.key" class="nano-table-row">
                                <td class="text-start fw-bold">{{ row.serviceName }}</td>
                                <td>{{ getServiceDuration(row.serviceId) }} {{ t('booking.min') }}</td>
                                <td>{{ formatPrice(getServicePrice(row.serviceId)) }}</td>
                                <td>
                                    <CBadge color="primary" shape="rounded-pill">{{ row.total }}</CBadge>
                                </td>
                                <td class="text-end text-success fw-bold">{{ formatPrice(row.revenue) }}</td>
                                <td>{{ row.total > 0 ? (row.total * getServiceDuration(row.serviceId) / row.total).toFixed(0) : 0 }} {{ t('booking.min') }}</td>
                                <td>
                                    <CButton size="sm" color="primary" variant="ghost" @click="viewServiceDetails(row.serviceId)">
                                        <CIcon icon="cil-external-link" />
                                    </CButton>
                                </td>
                            </tr>
                            <tr class="nano-table-footer border-top mt-3 bg-light">
                                <td colspan="3" class="text-start fw-bold p-3">{{ t('booking.analyticsTotal') }}</td>
                                <td class="fw-bold p-3">{{ appointmentAnalyticsTotals.total }}</td>
                                <td class="text-end text-success fw-bold p-3">{{ formatPrice(appointmentAnalyticsTotals.revenue) }}</td>
                                <td colspan="2" class="p-3"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <!-- View Service Modal -->
        <CModal :visible="showViewServiceModal" @close="closeViewServiceModal" size="lg" alignment="center">
            <CModalHeader>
                <CModalTitle><CIcon icon="cil-spreadsheet" class="me-2" />{{ t('booking.serviceDetails') }}</CModalTitle>
            </CModalHeader>
            <CModalBody v-if="viewingService">
                <div class="nano-modal-details p-3">
                    <div class="d-flex align-items-center gap-4 mb-4">
                        <div class="item-icon-circle large bg-primary text-white"><CIcon icon="cil-spreadsheet" size="xl" /></div>
                        <div>
                            <h3 class="fw-bold mb-1">{{ viewingService.title }}</h3>
                            <CBadge color="gold" shape="rounded-pill" class="px-3">{{ viewingService.category_id || 'General' }}</CBadge>
                        </div>
                    </div>
                    <div class="row g-3">
                        <CCol md="4">
                            <div class="stat-card-nano mini p-3 border rounded-4 text-center">
                                <label class="text-muted small">{{ t('booking.duration') }}</label>
                                <div class="fw-bold fs-5">{{ viewingService.duration }} {{ t('booking.minutes') }}</div>
                            </div>
                        </CCol>
                        <CCol md="4">
                            <div class="stat-card-nano mini p-3 border rounded-4 text-center">
                                <label class="text-muted small">{{ t('booking.price') }}</label>
                                <div class="fw-bold fs-5 text-success">{{ formatPrice(viewingService.price) }}</div>
                            </div>
                        </CCol>
                        <CCol md="4">
                            <div class="stat-card-nano mini p-3 border rounded-4 text-center">
                                <label class="text-muted small">{{ t('booking.analyticsAppointments') }}</label>
                                <div class="fw-bold fs-5 text-primary">{{ getServiceAppointmentsCount(viewingService.id) }}</div>
                            </div>
                        </CCol>
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" variant="ghost" @click="closeViewServiceModal">{{ t('booking.close') }}</CButton>
                <CButton color="primary" class="nano-btn" @click="() => { closeViewServiceModal(); openServiceModal(viewingService); }">
                    <CIcon icon="cil-pencil" class="me-2" />{{ t('booking.edit') }}
                </CButton>
            </CModalFooter>
        </CModal>

        <!-- Service Modal -->
        <CModal :visible="showServiceModal" @close="closeServiceModal" size="lg" alignment="center">
            <CModalHeader>
                <CModalTitle>{{ editingService ? t('booking.editService') : t('booking.addService') }}</CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="p-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ t('booking.serviceName') }} *</label>
                        <CFormInput v-model="serviceForm.title" required />
                    </div>
                    <CRow class="g-3">
                        <CCol md="6">
                            <label class="form-label fw-bold">{{ t('booking.duration') }} ({{ t('booking.minutes') }}) *</label>
                            <CFormInput v-model.number="serviceForm.duration" type="number" min="1" required />
                        </CCol>
                        <CCol md="6">
                            <label class="form-label fw-bold">{{ t('booking.price') }} *</label>
                            <CFormInput v-model.number="serviceForm.price" type="number" step="0.01" min="0" required />
                        </CCol>
                    </CRow>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" variant="ghost" @click="closeServiceModal">{{ t('booking.cancel') }}</CButton>
                <CButton color="primary" class="nano-btn" @click="saveService" :disabled="saving">
                    {{ saving ? t('booking.saving') : t('booking.save') }}
                </CButton>
            </CModalFooter>
        </CModal>

        <!-- Staff Modal -->
        <CModal :visible="showStaffModal" @close="closeStaffModal" size="lg" alignment="center">
            <CModalHeader>
                <CModalTitle>{{ editingStaff ? t('booking.editStaff') : t('booking.addStaff') }}</CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="p-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ t('booking.fullName') }} *</label>
                        <CFormInput v-model="staffForm.full_name" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ t('booking.email') }} *</label>
                        <CFormInput v-model="staffForm.email" type="email" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ t('booking.phone') }}</label>
                        <CFormInput v-model="staffForm.phone" type="tel" />
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" variant="ghost" @click="closeStaffModal">{{ t('booking.cancel') }}</CButton>
                <CButton color="primary" class="nano-btn" @click="saveStaff" :disabled="saving">
                    {{ saving ? t('booking.saving') : t('booking.save') }}
                </CButton>
            </CModalFooter>
        </CModal>

        <!-- Appointment Modal -->
        <CModal :visible="showAppointmentModal" @close="closeAppointmentModal" size="lg" alignment="center">
            <CModalHeader>
                <CModalTitle>{{ editingAppointment ? 'Edit Booking' : 'Add Booking' }}</CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="p-3">
                    <CRow class="g-3 mb-3">
                        <CCol md="8">
                            <label class="form-label fw-bold">{{ t('invoices.customer') || 'Customer' }} *</label>
                            <CFormInput
                                v-model="customerSearch"
                                type="text"
                                :placeholder="t('common.search') || 'Search...'"
                                @input="debounceCustomerSearch"
                                class="mb-2"
                            />
                            <CFormSelect v-model.number="appointmentForm.customer_id" required>
                                <option :value="0" disabled>{{ t('common.select') || 'Select...' }}</option>
                                <option v-if="customersLoading" :value="0" disabled>Loading...</option>
                                <option
                                    v-for="c in customersOptions"
                                    :key="c.id"
                                    :value="Number(c.id)"
                                >
                                    {{ c.name || '—' }}{{ c.phone ? ` (${c.phone})` : '' }}
                                </option>
                            </CFormSelect>
                        </CCol>
                        <CCol md="4">
                            <label class="form-label fw-bold">Status</label>
                            <CFormSelect v-model="appointmentForm.status">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </CFormSelect>
                        </CCol>
                    </CRow>

                    <CRow class="g-3 mb-3">
                        <CCol md="6">
                            <label class="form-label fw-bold">Service *</label>
                            <CFormSelect v-model.number="appointmentForm.service_id" required>
                                <option :value="0" disabled>Select service...</option>
                                <option v-for="s in bookingStore.services.items" :key="s.id" :value="Number(s.id)">
                                    {{ s.title }}
                                </option>
                            </CFormSelect>
                        </CCol>
                        <CCol md="6">
                            <label class="form-label fw-bold">Staff</label>
                            <CFormSelect v-model.number="appointmentForm.staff_id">
                                <option :value="0">Any</option>
                                <option v-for="st in bookingStore.staff.items" :key="st.id" :value="Number(st.id)">
                                    {{ st.full_name }}
                                </option>
                            </CFormSelect>
                        </CCol>
                    </CRow>

                    <CRow class="g-3 mb-3">
                        <CCol md="6">
                            <label class="form-label fw-bold">Date *</label>
                            <CFormInput v-model="appointmentForm.booking_date" type="date" required />
                        </CCol>
                        <CCol md="6">
                            <label class="form-label fw-bold">Time *</label>
                            <CFormInput v-model="appointmentForm.booking_time" type="time" required />
                        </CCol>
                    </CRow>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <CFormTextarea v-model="appointmentForm.notes" rows="3" placeholder="Optional notes..."></CFormTextarea>
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" variant="ghost" @click="closeAppointmentModal">{{ t('booking.cancel') }}</CButton>
                <CButton color="primary" class="nano-btn" @click="saveAppointment" :disabled="saving">
                    {{ saving ? t('booking.saving') : t('booking.save') }}
                </CButton>
            </CModalFooter>
        </CModal>
        
        <!-- FAQ/Help Section (Rule #7) -->
        <HelpSection page-key="booking" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useUIStore } from '../../stores/ui.js';
import { useBookingsStore } from '../../stores/bookings.js';
import { useTranslation } from '../../composables/useTranslation.js';
import api from '@/utils/api';
import BookingCalendar from './BookingCalendar.vue';
import HelpSection from '@/components/Common/HelpSection.vue';
import { CIcon } from '@coreui/icons-vue';
import { 
    CBadge, CButton, CNav, CNavItem, CNavLink, 
    CRow, CCol, CButtonGroup, CFormInput, CFormSelect, 
    CModal, CModalHeader, CModalTitle, CModalBody, CModalFooter,
    CFormTextarea, CSpinner
} from '@coreui/vue';
import { useToast } from '@/composables/useToast';

const { t } = useTranslation();
const uiStore = useUIStore();
const bookingStore = useBookingsStore();
const route = useRoute();
const router = useRouter();
const toast = useToast();

const activeTab = ref('services');
const appointmentViewMode = ref('calendar');
const showServiceModal = ref(false);
const showStaffModal = ref(false);
const showAppointmentModal = ref(false);
const showViewServiceModal = ref(false);
const viewingService = ref(null);
const editingService = ref(null);
const editingStaff = ref(null);
const editingAppointment = ref(null);
const saving = ref(false);

const tabs = computed(() => [
    { id: 'services', label: t('booking.servicesTab'), count: bookingStore.services.items.length },
    { id: 'staff', label: t('booking.staffTab'), count: bookingStore.staff.items.length },
    { id: 'appointments', label: t('booking.appointmentsTab'), count: bookingStore.appointments.items.length },
    { id: 'analytics', label: t('booking.analyticsTab') },
]);

const getTabIcon = (id) => {
    switch (id) {
        case 'services': return 'cil-spreadsheet';
        case 'staff': return 'cil-user';
        case 'appointments': return 'cil-calendar';
        case 'analytics': return 'cil-chart-line';
        default: return 'cil-list';
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
};

const appointmentFilters = ref({
    date_from: '',
    date_to: '',
    status: '',
});

const serviceForm = ref({
    title: '',
    duration: 60,
    price: 0,
});

const staffForm = ref({
    full_name: '',
    email: '',
    phone: '',
});

const appointmentForm = ref({
    customer_id: 0,
    staff_id: 0,
    service_id: 0,
    booking_date: '',
    booking_time: '',
    status: 'pending',
    notes: '',
});

// Customers lookup (for Add Booking modal)
const customersOptions = ref([]);
const customersLoading = ref(false);
const customerSearch = ref('');
let customerSearchTimer = null;

const fetchCustomers = async (search = '') => {
    customersLoading.value = true;
    try {
        const res = await api.get('/customers', {
            params: {
                page: 1,
                per_page: 50,
                search: search || undefined,
                status: 'active',
            },
            noCache: true,
        });
        const payload = res.data?.data ?? res.data ?? {};
        customersOptions.value = payload.items || [];
    } catch (e) {
        console.error('Failed to fetch customers:', e);
        customersOptions.value = [];
    } finally {
        customersLoading.value = false;
    }
};

const ensureSelectedCustomerLoaded = async (customerId) => {
    const id = Number(customerId || 0);
    if (!id) return;
    const exists = (customersOptions.value || []).some((c) => Number(c.id) === id);
    if (exists) return;
    try {
        const res = await api.get(`/customers/${id}`, { noCache: true });
        const customer = res.data?.data ?? res.data ?? null;
        if (customer && customer.id) {
            customersOptions.value = [customer, ...(customersOptions.value || [])];
        }
    } catch (e) {
        // ignore
    }
};

const debounceCustomerSearch = () => {
    if (customerSearchTimer) clearTimeout(customerSearchTimer);
    customerSearchTimer = setTimeout(() => {
        fetchCustomers(customerSearch.value);
    }, 350);
};

const appointmentSummary = computed(() => {
    const items = bookingStore.appointments.items || [];
    let approved = 0;
    let pending = 0;
    let total = items.length;

    items.forEach((a) => {
        if (a.status === 'confirmed' || a.status === 'approved') {
            approved++;
        } else if (a.status === 'pending') {
            pending++;
        }
    });

    const revenue = 0;
    return { approved, pending, total, revenue };
});

const appointmentAnalyticsRows = computed(() => {
    const items = bookingStore.appointments.items || [];
    const rowsMap = new Map();
    const seenCustomers = new Set();

    items.forEach((a) => {
        const key = `${a.staff_id || 0}-${a.service_id || 0}`;
        if (!rowsMap.has(key)) {
            rowsMap.set(key, {
                key,
                staffId: a.staff_id,
                serviceId: a.service_id,
                employeeName: getStaffName(a.staff_id),
                serviceName: getServiceName(a.service_id),
                total: 0,
                approved: 0,
                pending: 0,
                rejected: 0,
                cancelled: 0,
                customersSet: new Set(),
                newCustomers: 0,
                revenue: 0,
            });
        }

        const row = rowsMap.get(key);
        row.total++;

        if (a.status === 'confirmed' || a.status === 'approved') {
            row.approved++;
        } else if (a.status === 'pending') {
            row.pending++;
        } else if (a.status === 'rejected') {
            row.rejected++;
        } else if (a.status === 'cancelled') {
            row.cancelled++;
        }

        if (a.customer_id) {
            row.customersSet.add(a.customer_id);
            const customerKey = String(a.customer_id);
            if (!seenCustomers.has(customerKey)) {
                row.newCustomers++;
                seenCustomers.add(customerKey);
            }
        }
    });

    return Array.from(rowsMap.values()).map((row) => ({
        ...row,
        customersTotal: row.customersSet.size,
    }));
});

const appointmentAnalyticsTotals = computed(() => {
    const rows = appointmentAnalyticsRows.value;
    return rows.reduce(
        (acc, row) => {
            acc.total += row.total;
            acc.approved += row.approved;
            acc.pending += row.pending;
            acc.rejected += row.rejected;
            acc.cancelled += row.cancelled;
            acc.customersTotal += row.customersTotal;
            acc.newCustomers += row.newCustomers;
            acc.revenue += row.revenue;
            return acc;
        },
        { total: 0, approved: 0, pending: 0, rejected: 0, cancelled: 0, customersTotal: 0, newCustomers: 0, revenue: 0 }
    );
});

// Service Helpers
const getServiceName = (id) => {
    const s = bookingStore.services.items.find((s) => Number(s.id) === Number(id));
    return s ? s.title : '—';
};
const getServicePrice = (id) => {
    const s = bookingStore.services.items.find((s) => Number(s.id) === Number(id));
    return s ? s.price : 0;
};
const getServiceDuration = (id) => {
    const s = bookingStore.services.items.find((s) => Number(s.id) === Number(id));
    return s ? s.duration : 0;
};
const getServiceAppointmentsCount = (id) => {
    return bookingStore.appointments.items.filter((a) => Number(a.service_id) === Number(id)).length;
};

// Staff Helpers
const getStaffName = (id) => {
    const st = bookingStore.staff.items.find((st) => Number(st.id) === Number(id));
    return st ? st.full_name : '—';
};

// Date Format Helper
const formatDate = (dateStr, type = 'full') => {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;

    if (type === 'day') return date.getDate();
    if (type === 'month') return date.toLocaleString('default', { month: 'short' });
    if (type === 'time') return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    return date.toLocaleDateString();
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-KW', {
        style: 'currency',
        currency: 'KWD',
        minimumFractionDigits: 3,
    }).format(price || 0);
};

// Actions
const viewService = (service) => {
    viewingService.value = service;
    showViewServiceModal.value = true;
};
const closeViewServiceModal = () => {
    showViewServiceModal.value = false;
    viewingService.value = null;
};
const viewServiceDetails = (serviceId) => {
    const service = bookingStore.services.items.find((s) => Number(s.id) === Number(serviceId));
    if (service) viewService(service);
};

const openServiceModal = (service = null) => {
    if (service) {
        editingService.value = service;
        serviceForm.value = {
            title: service.title,
            duration: service.duration,
            price: service.price,
        };
    } else {
        editingService.value = null;
        serviceForm.value = { title: '', duration: 60, price: 0 };
    }
    showServiceModal.value = true;
};
const closeServiceModal = () => {
    showServiceModal.value = false;
    editingService.value = null;
};
const saveService = async () => {
    saving.value = true;
    try {
        if (editingService.value) {
            await bookingStore.updateService(editingService.value.id, serviceForm.value);
            toast.success(t('booking.serviceUpdated') || 'Service updated');
        } else {
            await bookingStore.createService(serviceForm.value);
            toast.success(t('booking.serviceAdded') || 'Service added');
        }
        closeServiceModal();
    } catch (e) {
        toast.error('Failed to save service');
    } finally {
        saving.value = false;
    }
};
const deleteService = async (id) => {
    if (!confirm(t('common.confirmDelete'))) return;
    try {
        await bookingStore.deleteService(id);
        toast.success(t('booking.serviceDeleted'));
    } catch (e) {
        toast.error('Failed to delete service');
    }
};

const openStaffModal = (member = null) => {
    if (member) {
        editingStaff.value = member;
        staffForm.value = {
            full_name: member.full_name,
            email: member.email,
            phone: member.phone,
        };
    } else {
        editingStaff.value = null;
        staffForm.value = { full_name: '', email: '', phone: '' };
    }
    showStaffModal.value = true;
};
const closeStaffModal = () => {
    showStaffModal.value = false;
    editingStaff.value = null;
};
const saveStaff = async () => {
    saving.value = true;
    try {
        if (editingStaff.value) {
            await bookingStore.updateStaff(editingStaff.value.id, staffForm.value);
            toast.success(t('booking.staffUpdated'));
        } else {
            await bookingStore.createStaff(staffForm.value);
            toast.success(t('booking.staffAdded'));
        }
        closeStaffModal();
    } catch (e) {
        toast.error('Failed to save staff');
    } finally {
        saving.value = false;
    }
};
const deleteStaff = async (id) => {
    if (!confirm(t('common.confirmDelete'))) return;
    try {
        await bookingStore.deleteStaff(id);
        toast.success(t('booking.staffDeleted'));
    } catch (e) {
        toast.error('Failed to delete staff');
    }
};

const goToAddBooking = () => {
    openAppointmentModal();
};

const openAppointmentModal = async (appointment = null) => {
    fetchCustomers(); // Initial customers load
    if (appointment) {
        editingAppointment.value = appointment;
        const start = new Date(appointment.start_date);
        appointmentForm.value = {
            customer_id: Number(appointment.customer_id),
            staff_id: Number(appointment.staff_id),
            service_id: Number(appointment.service_id),
            booking_date: start.toISOString().split('T')[0],
            booking_time: start.toTimeString().split(' ')[0].slice(0, 5),
            status: appointment.status,
            notes: appointment.notes || '',
        };
        await ensureSelectedCustomerLoaded(appointment.customer_id);
    } else {
        editingAppointment.value = null;
        const now = new Date();
        appointmentForm.value = {
            customer_id: 0,
            staff_id: 0,
            service_id: 0,
            booking_date: now.toISOString().split('T')[0],
            booking_time: now.toTimeString().split(' ')[0].slice(0, 5),
            status: 'pending',
            notes: '',
        };
    }
    showAppointmentModal.value = true;
};
const closeAppointmentModal = () => {
    showAppointmentModal.value = false;
    editingAppointment.value = null;
};
const saveAppointment = async () => {
    if (!appointmentForm.value.customer_id || !appointmentForm.value.service_id) {
        toast.error('Please select customer and service');
        return;
    }
    saving.value = true;
    try {
        const payload = {
            ...appointmentForm.value,
            start_date: `${appointmentForm.value.booking_date} ${appointmentForm.value.booking_time}:00`,
        };
        if (editingAppointment.value) {
            await bookingStore.updateAppointment(editingAppointment.value.id, payload);
            toast.success('Booking updated');
        } else {
            await bookingStore.createAppointment(payload);
            toast.success('Booking added');
        }
        closeAppointmentModal();
    } catch (e) {
        toast.error('Failed to save booking');
    } finally {
        saving.value = false;
    }
};
const deleteAppointment = async (id) => {
    if (!confirm(t('common.confirmDelete'))) return;
    try {
        await bookingStore.deleteAppointment(id);
        toast.success('Booking deleted');
    } catch (e) {
        toast.error('Failed to delete booking');
    }
};

const loadAppointments = () => {
    bookingStore.fetchAppointments(appointmentFilters.value);
};

const checkoutBooking = (appointment) => {
    router.push({
        path: '/pos',
        query: {
            customer_id: appointment.customer_id,
            booking_id: appointment.id,
            service_id: appointment.service_id,
            staff_id: appointment.staff_id
        }
    });
};

onMounted(() => {
    bookingStore.prefetch();
    if (route.query.tab) {
        activeTab.value = route.query.tab;
    }
});
</script>

<style scoped>
.booking-page {
    font-family: 'Cairo', sans-serif;
    background: var(--bg-primary);
    min-height: 100vh;
}

/* Nano Banana Components */
.nano-btn {
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 700;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}
.nano-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(187, 160, 122, 0.4);
}

.nano-btn-secondary {
    border-radius: 12px;
    padding: 0.75rem 1.25rem;
    font-weight: 600;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.nano-stats-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
    border: 1px solid transparent;
}
.stat-card-nano:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
    border-color: var(--asmaa-primary);
}

.stat-icon-bg {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}
.stat-icon-bg.services { background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); }
.stat-icon-bg.staff { background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); }
.stat-icon-bg.approved { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.pending { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.stat-value {
    font-size: 1.75rem;
    font-weight: 800;
    line-height: 1;
}
.stat-label {
    font-size: 0.875rem;
    color: var(--text-muted);
    font-weight: 600;
    margin-top: 0.25rem;
}

.nano-tabs {
    background: var(--bg-tertiary);
    padding: 0.5rem;
    border-radius: 16px;
    gap: 0.5rem;
}
.nano-tab-link {
    border-radius: 12px !important;
    font-weight: 700 !important;
    padding: 0.75rem 1.5rem !important;
    color: var(--text-muted) !important;
    border: none !important;
    transition: all 0.3s !important;
}
.nano-tab-link.active {
    background: var(--asmaa-primary) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}

.nano-panel {
    background: var(--bg-secondary);
    border-radius: 24px;
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.nano-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.5rem;
}

.nano-item-card {
    background: var(--bg-tertiary);
    border-radius: 20px;
    padding: 1.5rem;
    text-align: center;
    position: relative;
    border: 1px solid var(--border-color);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}
.nano-item-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-md);
    border-color: var(--asmaa-primary);
}

.item-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(187, 160, 122, 0.1);
    color: var(--asmaa-primary);
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
}

.item-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: var(--bg-secondary);
    color: var(--asmaa-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    font-size: 1.75rem;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
}

.item-title {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.item-price-tag {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--asmaa-primary);
}

.item-actions-hover {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%) translateY(10px);
    opacity: 0;
    transition: all 0.3s;
    display: flex;
    gap: 0.5rem;
}
.nano-item-card:hover .item-actions-hover {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.staff-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, #d4b996 100%);
    color: white;
    font-size: 2.5rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 8px 16px rgba(187, 160, 122, 0.3);
}

.nano-appointment-card {
    display: flex;
    align-items: center;
    background: var(--bg-tertiary);
    border-radius: 20px;
    padding: 1.25rem;
    border: 1px solid var(--border-color);
    transition: all 0.3s;
}
.nano-appointment-card:hover {
    transform: translateX(5px);
    border-color: var(--asmaa-primary);
    box-shadow: var(--shadow-sm);
}

.appt-date-box {
    width: 90px;
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, #d4b996 100%);
    border-radius: 16px;
    padding: 0.75rem;
    color: white;
    text-align: center;
    box-shadow: 0 4px 10px rgba(187, 160, 122, 0.3);
}
.appt-day { font-size: 1.75rem; font-weight: 800; line-height: 1; }
.appt-month { font-size: 0.875rem; font-weight: 600; text-transform: uppercase; }
.appt-time { font-size: 0.75rem; opacity: 0.9; margin-top: 4px; }

.nano-table-container {
    overflow-x: auto;
}
.nano-table {
    border-collapse: separate;
    border-spacing: 0 0.75rem;
}
.nano-table th {
    padding: 1rem;
    color: var(--text-muted);
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 1px;
    border-bottom: 2px solid var(--border-color);
    text-align: center;
}
.nano-table-row {
    background: var(--bg-tertiary);
    transition: all 0.3s;
}
.nano-table-row td {
    padding: 1.25rem 1rem;
    vertical-align: middle;
    text-align: center;
}
.nano-table-row:hover {
    transform: scale(1.01);
    box-shadow: var(--shadow-sm);
}
.nano-table-row td:first-child { border-radius: 16px 0 0 16px; }
.nano-table-row td:last-child { border-radius: 0 16px 16px 0; }

[dir="rtl"] .nano-table-row td:first-child { border-radius: 0 16px 16px 0; }
[dir="rtl"] .nano-table-row td:last-child { border-radius: 16px 0 0 16px; }

@media (max-width: 768px) {
    .nano-stats-bar { grid-template-columns: 1fr; }
    .nano-appointment-card { flex-direction: column; text-align: center; gap: 1rem; }
    .appt-main-info { margin: 0 !important; }
}
</style>
