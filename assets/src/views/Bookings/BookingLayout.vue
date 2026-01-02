<template>
    <div class="booking-page">
        <!-- Header -->
        <div class="booking-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="header-text">
                        <h1 class="header-title">{{ t('booking.title') }}</h1>
                        <p class="header-subtitle">{{ t('booking.subtitle') }}</p>
                    </div>
                </div>
                <div class="header-stats">
                    <div class="stat-card">
                        <div class="stat-value">{{ bookingStore.services.items.length }}</div>
                        <div class="stat-label">{{ t('booking.totalServices') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ bookingStore.staff.items.length }}</div>
                        <div class="stat-label">{{ t('booking.totalStaff') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ bookingStore.appointments.items.length }}</div>
                        <div class="stat-label">{{ t('booking.totalAppointments') }}</div>
                    </div>
                </div>
                <div class="header-actions">
                    <router-link to="/bookings/settings" class="btn btn-secondary">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        {{ t('booking.settingsButton') }}
                    </router-link>
                    <router-link to="/bookings/appearance" class="btn btn-secondary">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                        {{ t('booking.appearanceButton') }}
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="booking-tabs-nav">
            <div class="tabs-container">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="['tab-button', { active: activeTab === tab.id }]"
                >
                    <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"></path>
                    </svg>
                    <span class="tab-text">{{ tab.label }}</span>
                    <span v-if="tab.count !== undefined" class="tab-badge">{{ tab.count }}</span>
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="booking-content">
            <!-- Services Tab -->
            <div v-show="activeTab === 'services'" class="tab-panel">
                <div class="panel-header">
                    <h2 class="panel-title">{{ t('booking.services') }}</h2>
                    <div class="header-actions">
                        <router-link to="/bookings/categories" class="btn btn-secondary">
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            {{ t('booking.manageCategories') }}
                        </router-link>
                        <button class="btn btn-primary" @click="openServiceModal()">
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            {{ t('booking.addService') }}
                        </button>
                    </div>
                </div>

                <div v-if="bookingStore.services.loading" class="loading-state">
                    <div class="spinner"></div>
                    <p>{{ t('booking.loadingServices', uiStore.locale) }}</p>
                </div>

                <div v-else-if="bookingStore.services.items.length === 0" class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <p>{{ t('booking.noServicesFound', uiStore.locale) }}</p>
                    <button class="btn btn-primary" @click="openServiceModal()">
                        {{ t('booking.addFirstService', uiStore.locale) }}
                    </button>
                </div>

                <div v-else>
                    <!-- Services Analytics Table -->
                    <div class="analytics-card">
                        <div class="analytics-header">
                            <div>
                                <h3 class="analytics-title">{{ t('booking.servicesAnalyticsTitle', uiStore.locale) }}</h3>
                                <p class="analytics-subtitle">{{ t('booking.servicesAnalyticsSubtitle', uiStore.locale) }}</p>
                            </div>
                        </div>

                        <div class="analytics-table-wrapper">
                            <table class="analytics-table services-analytics-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('booking.serviceName', uiStore.locale) }}</th>
                                        <th>{{ t('booking.duration', uiStore.locale) }}</th>
                                        <th>{{ t('booking.price', uiStore.locale) }}</th>
                                        <th>{{ t('booking.analyticsAppointments', uiStore.locale) }}</th>
                                        <th>{{ t('booking.analyticsRevenue', uiStore.locale) }}</th>
                                        <th>{{ t('booking.averageAppointmentDuration', uiStore.locale) }}</th>
                                        <th>{{ t('booking.actions', uiStore.locale) }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="servicesAnalyticsRows.length === 0">
                                        <td class="no-data" colspan="7">
                                            {{ t('booking.analyticsNoData', uiStore.locale) }}
                                        </td>
                                    </tr>
                                    <tr v-for="row in servicesAnalyticsRows" :key="row.serviceId">
                                        <td>
                                            <div class="service-name-cell">
                                                <strong>{{ row.serviceName }}</strong>
                                            </div>
                                        </td>
                                        <td>{{ row.duration }} {{ t('booking.minutes', uiStore.locale) }}</td>
                                        <td>{{ formatPrice(row.price) }}</td>
                                        <td>
                                            <span class="count-badge">{{ row.appointmentsCount }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ formatPrice(row.revenue) }}</strong>
                                        </td>
                                        <td>{{ row.avgDuration }} {{ t('booking.minutes', uiStore.locale) }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="action-btn edit" @click="openServiceModal(row.service)" :title="t('booking.edit', uiStore.locale)">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button class="action-btn delete" @click="deleteService(row.serviceId)" :title="t('booking.delete', uiStore.locale)">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="servicesAnalyticsRows.length > 0" class="analytics-total-row">
                                        <td colspan="3"><strong>{{ t('booking.analyticsTotalLabel', uiStore.locale) }}</strong></td>
                                        <td><strong>{{ servicesAnalyticsTotals.appointmentsCount }}</strong></td>
                                        <td><strong>{{ formatPrice(servicesAnalyticsTotals.revenue) }}</strong></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Services Grid View -->
                    <div class="services-grid">
                        <div v-for="service in bookingStore.services.items" :key="service.id" class="service-card">
                            <div class="service-header">
                                <h3 class="service-title">{{ service.title }}</h3>
                                <div class="service-actions">
                                    <button class="action-btn edit" @click="openServiceModal(service)" :title="t('booking.edit', uiStore.locale)">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button class="action-btn delete" @click="deleteService(service.id)" :title="t('booking.delete', uiStore.locale)">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="service-body">
                                <div class="service-info">
                                    <div class="info-item">
                                        <span class="info-label">{{ t('booking.duration', uiStore.locale) }}:</span>
                                        <span class="info-value">{{ service.duration }} {{ t('booking.minutes', uiStore.locale) }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">{{ t('booking.price', uiStore.locale) }}:</span>
                                        <span class="info-value">{{ formatPrice(service.price) }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">{{ t('booking.appointmentsBooked', uiStore.locale) }}:</span>
                                        <span class="info-value highlight">{{ getServiceAppointmentsCount(service.id) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Tab -->
            <div v-show="activeTab === 'staff'" class="tab-panel">
                <div class="panel-header">
                    <h2 class="panel-title">{{ t('booking.staff', uiStore.locale) }}</h2>
                    <button class="btn btn-primary" @click="openStaffModal()">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ t('booking.addStaff', uiStore.locale) }}
                    </button>
                </div>

                <div v-if="bookingStore.staff.loading" class="loading-state">
                    <div class="spinner"></div>
                    <p>{{ t('booking.loadingStaff', uiStore.locale) }}</p>
                </div>

                <div v-else-if="bookingStore.staff.items.length === 0" class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p>{{ t('booking.noStaffFound', uiStore.locale) }}</p>
                    <button class="btn btn-primary" @click="openStaffModal()">
                        {{ t('booking.addFirstStaff', uiStore.locale) }}
                    </button>
                </div>

                <div v-else class="staff-grid">
                    <div v-for="member in bookingStore.staff.items" :key="member.id" class="staff-card">
                        <div class="staff-avatar">
                            <span>{{ getInitials(member.full_name) }}</span>
                        </div>
                        <div class="staff-info">
                            <h3 class="staff-name">{{ member.full_name }}</h3>
                            <div class="staff-details">
                                <div class="detail-item">
                                    <svg class="detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>{{ member.email }}</span>
                                </div>
                                <div v-if="member.phone" class="detail-item">
                                    <svg class="detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span>{{ member.phone }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="staff-actions">
                            <button class="action-btn edit" @click="openStaffModal(member)" :title="t('booking.edit', uiStore.locale)">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button class="action-btn delete" @click="deleteStaff(member.id)" :title="t('booking.delete', uiStore.locale)">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments Tab -->
            <div v-show="activeTab === 'appointments'" class="tab-panel">
                <div class="panel-header">
                    <h2 class="panel-title">{{ t('booking.appointments', uiStore.locale) }}</h2>
                    <button class="btn btn-primary" @click="openAppointmentModal()">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ t('booking.addAppointment', uiStore.locale) }}
                    </button>
                </div>

                <!-- Calendar Component -->
                <BookingCalendar
                    :appointments="bookingStore.appointments.items"
                    :services="bookingStore.services.items"
                    :staff="bookingStore.staff.items"
                    v-model:viewMode="appointmentViewMode"
                    :locale="uiStore.locale"
                    @eventClick="openAppointmentModal"
                    @dateClick="handleCalendarDateClick"
                />

                <!-- List View (shown when viewMode is 'list') -->
                <div v-show="appointmentViewMode === 'list'" class="appointments-list-view">
                    <div class="header-filters">
                        <input
                            v-model="appointmentFilters.date_from"
                            type="date"
                            class="filter-input"
                            :placeholder="t('booking.dateFrom', uiStore.locale)"
                        />
                        <input
                            v-model="appointmentFilters.date_to"
                            type="date"
                            class="filter-input"
                            :placeholder="t('booking.dateTo', uiStore.locale)"
                        />
                        <select v-model="appointmentFilters.status" class="filter-select">
                            <option value="">{{ t('booking.allStatuses', uiStore.locale) }}</option>
                            <option value="pending">{{ t('booking.pending', uiStore.locale) }}</option>
                            <option value="approved">{{ t('booking.approved', uiStore.locale) }}</option>
                            <option value="cancelled">{{ t('booking.cancelled', uiStore.locale) }}</option>
                            <option value="completed">{{ t('booking.completed', uiStore.locale) }}</option>
                        </select>
                        <button class="btn btn-secondary" @click="applyAppointmentFilters">
                            {{ t('booking.applyFilters', uiStore.locale) }}
                        </button>
                    </div>
                </div>

                <div v-if="bookingStore.appointments.loading" class="loading-state">
                    <div class="spinner"></div>
                    <p>{{ t('booking.loadingAppointments', uiStore.locale) }}</p>
                </div>

                <div v-else-if="bookingStore.appointments.items.length === 0" class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p>{{ t('booking.noAppointmentsFound', uiStore.locale) }}</p>
                </div>

                <div v-else>
                    <!-- Overview cards like Booking analytics -->
                    <div class="appointments-overview">
                        <div class="overview-card">
                            <div class="overview-label">{{ t('booking.approvedAppointments', uiStore.locale) }}</div>
                            <div class="overview-value">{{ appointmentSummary.approved }}</div>
                        </div>
                        <div class="overview-card">
                            <div class="overview-label">{{ t('booking.pendingAppointments', uiStore.locale) }}</div>
                            <div class="overview-value">{{ appointmentSummary.pending }}</div>
                        </div>
                        <div class="overview-card">
                            <div class="overview-label">{{ t('booking.totalAppointmentsLabel', uiStore.locale) }}</div>
                            <div class="overview-value">{{ appointmentSummary.total }}</div>
                        </div>
                        <div class="overview-card revenue">
                            <div class="overview-label">{{ t('booking.revenue', uiStore.locale) }}</div>
                            <div class="overview-value">{{ formatPrice(appointmentSummary.revenue) }}</div>
                        </div>
                    </div>

                    <!-- Analytics table (employee / service breakdown) -->
                    <div class="analytics-card">
                        <div class="analytics-header">
                            <div>
                                <h3 class="analytics-title">{{ t('booking.analyticsTitle', uiStore.locale) }}</h3>
                                <p class="analytics-subtitle">{{ t('booking.analyticsSubtitle', uiStore.locale) }}</p>
                            </div>
                        </div>

                        <div class="analytics-table-wrapper">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2">{{ t('booking.analyticsEmployee', uiStore.locale) }}</th>
                                        <th rowspan="2">{{ t('booking.analyticsService', uiStore.locale) }}</th>
                                        <th colspan="5">{{ t('booking.analyticsAppointments', uiStore.locale) }}</th>
                                        <th colspan="2">{{ t('booking.analyticsCustomers', uiStore.locale) }}</th>
                                        <th rowspan="2">{{ t('booking.analyticsRevenue', uiStore.locale) }}</th>
                                    </tr>
                                    <tr>
                                        <th>{{ t('booking.total', uiStore.locale) }}</th>
                                        <th>{{ t('booking.approved', uiStore.locale) }}</th>
                                        <th>{{ t('booking.pending', uiStore.locale) }}</th>
                                        <th>{{ t('booking.rejected', uiStore.locale) }}</th>
                                        <th>{{ t('booking.cancelled', uiStore.locale) }}</th>
                                        <th>{{ t('booking.total', uiStore.locale) }}</th>
                                        <th>{{ t('booking.new', uiStore.locale) }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="appointmentAnalyticsRows.length === 0">
                                        <td class="no-data" colspan="10">
                                            {{ t('booking.analyticsNoData', uiStore.locale) }}
                                        </td>
                                    </tr>
                                    <tr v-for="row in appointmentAnalyticsRows" :key="row.key">
                                        <td>{{ row.employeeName }}</td>
                                        <td>{{ row.serviceName }}</td>
                                        <td>{{ row.total }}</td>
                                        <td>{{ row.approved }}</td>
                                        <td>{{ row.pending }}</td>
                                        <td>{{ row.rejected }}</td>
                                        <td>{{ row.cancelled }}</td>
                                        <td>{{ row.customersTotal }}</td>
                                        <td>{{ row.newCustomers }}</td>
                                        <td>{{ formatPrice(row.revenue) }}</td>
                                    </tr>
                                    <tr v-if="appointmentAnalyticsRows.length > 0" class="analytics-total-row">
                                        <td colspan="2">{{ t('booking.analyticsTotalLabel', uiStore.locale) }}</td>
                                        <td>{{ appointmentAnalyticsTotals.total }}</td>
                                        <td>{{ appointmentAnalyticsTotals.approved }}</td>
                                        <td>{{ appointmentAnalyticsTotals.pending }}</td>
                                        <td>{{ appointmentAnalyticsTotals.rejected }}</td>
                                        <td>{{ appointmentAnalyticsTotals.cancelled }}</td>
                                        <td>{{ appointmentAnalyticsTotals.customersTotal }}</td>
                                        <td>{{ appointmentAnalyticsTotals.newCustomers }}</td>
                                        <td>{{ formatPrice(appointmentAnalyticsTotals.revenue) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="analytics-note">
                                {{ t('booking.analyticsNote', uiStore.locale) }}
                            </p>
                        </div>
                    </div>

                    <!-- Existing appointments list -->
                    <div class="appointments-list">
                        <div
                            v-for="appointment in bookingStore.appointments.items"
                            :key="appointment.id"
                            class="appointment-card"
                        >
                            <div class="appointment-date">
                                <div class="date-day">{{ formatDate(appointment.start_date, 'day') }}</div>
                                <div class="date-month">{{ formatDate(appointment.start_date, 'month') }}</div>
                                <div class="date-time">{{ formatDate(appointment.start_date, 'time') }}</div>
                            </div>
                            <div class="appointment-details">
                                <div class="appointment-service">
                                    <strong>{{ getServiceName(appointment.service_id) }}</strong>
                                </div>
                                <div class="appointment-staff">
                                    {{ t('booking.with', uiStore.locale) }}: {{ getStaffName(appointment.staff_id) }}
                                </div>
                                <div class="appointment-status">
                                    <span :class="['status-badge', appointment.status]">
                                        {{ t(`booking.${appointment.status}`, uiStore.locale) }}
                                    </span>
                                </div>
                            </div>
                            <div class="appointment-actions">
                                <button
                                    class="action-btn edit"
                                    @click="openAppointmentModal(appointment)"
                                    :title="t('booking.edit', uiStore.locale)"
                                >
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        ></path>
                                    </svg>
                                </button>
                                <button
                                    class="action-btn delete"
                                    @click="deleteAppointment(appointment.id)"
                                    :title="t('booking.delete', uiStore.locale)"
                                >
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Modal -->
        <div v-if="showServiceModal" class="modal-overlay" @click="closeServiceModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>{{ editingService ? t('booking.editService', uiStore.locale) : t('booking.addService', uiStore.locale) }}</h3>
                    <button class="modal-close" @click="closeServiceModal">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ t('booking.serviceName', uiStore.locale) }} *</label>
                        <input v-model="serviceForm.title" type="text" class="form-input" required />
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>{{ t('booking.duration', uiStore.locale) }} ({{ t('booking.minutes', uiStore.locale) }}) *</label>
                            <input v-model.number="serviceForm.duration" type="number" class="form-input" min="1" required />
                        </div>
                        <div class="form-group">
                            <label>{{ t('booking.price', uiStore.locale) }} *</label>
                            <input v-model.number="serviceForm.price" type="number" class="form-input" step="0.01" min="0" required />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeServiceModal">{{ t('booking.cancel', uiStore.locale) }}</button>
                    <button class="btn btn-primary" @click="saveService" :disabled="saving">
                        {{ saving ? t('booking.saving', uiStore.locale) : t('booking.save', uiStore.locale) }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Staff Modal -->
        <div v-if="showStaffModal" class="modal-overlay" @click="closeStaffModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>{{ editingStaff ? t('booking.editStaff', uiStore.locale) : t('booking.addStaff', uiStore.locale) }}</h3>
                    <button class="modal-close" @click="closeStaffModal">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ t('booking.fullName', uiStore.locale) }} *</label>
                        <input v-model="staffForm.full_name" type="text" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>{{ t('booking.email', uiStore.locale) }} *</label>
                        <input v-model="staffForm.email" type="email" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>{{ t('booking.phone', uiStore.locale) }}</label>
                        <input v-model="staffForm.phone" type="tel" class="form-input" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeStaffModal">{{ t('booking.cancel', uiStore.locale) }}</button>
                    <button class="btn btn-primary" @click="saveStaff" :disabled="saving">
                        {{ saving ? t('booking.saving', uiStore.locale) : t('booking.save', uiStore.locale) }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useUIStore } from '../../stores/ui.js';
import { useBookingsStore } from '../../stores/bookings.js';
import { useTranslation } from '../../composables/useTranslation.js';
import BookingCalendar from './BookingCalendar.vue';

const uiStore = useUIStore();
const bookingStore = useBookingsStore();

const activeTab = ref('services');
const appointmentViewMode = ref('calendar');
const showServiceModal = ref(false);
const showStaffModal = ref(false);
const editingService = ref(null);
const editingStaff = ref(null);
const saving = ref(false);

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

const appointmentSummary = computed(() => {
    const items = bookingStore.appointments.items || [];
    let approved = 0;
    let pending = 0;
    let total = items.length;

    items.forEach((a) => {
        if (a.status === 'approved') {
            approved++;
        } else if (a.status === 'pending') {
            pending++;
        }
    });

    // Revenue is currently 0 because appointments endpoint does not expose payment totals yet.
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

        if (a.status === 'approved') {
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

        // If in the future appointment revenue is added to the endpoint, accumulate here:
        // row.revenue += a.revenue || 0;
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
        {
            total: 0,
            approved: 0,
            pending: 0,
            rejected: 0,
            cancelled: 0,
            customersTotal: 0,
            newCustomers: 0,
            revenue: 0,
        }
    );
});

const tabs = computed(() => [
    {
        id: 'services',
        label: t('booking.services', uiStore.locale),
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        count: bookingStore.services.items.length,
    },
    {
        id: 'staff',
        label: t('booking.staff', uiStore.locale),
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        count: bookingStore.staff.items.length,
    },
    {
        id: 'appointments',
        label: t('booking.appointments', uiStore.locale),
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        count: bookingStore.appointments.items.length,
    },
]);

onMounted(() => {
    bookingStore.fetchServices();
    bookingStore.fetchStaff();
    bookingStore.fetchAppointments();
});

const openServiceModal = (service = null) => {
    editingService.value = service;
    if (service) {
        serviceForm.title = service.title;
        serviceForm.category_id = service.category_id || '';
        serviceForm.duration = service.duration;
        serviceForm.price = service.price;
    } else {
        serviceForm.title = '';
        serviceForm.category_id = '';
        serviceForm.duration = 60;
        serviceForm.price = 0;
    }
    showServiceModal.value = true;
};

const closeServiceModal = () => {
    showServiceModal.value = false;
    editingService.value = null;
    serviceForm.title = '';
    serviceForm.category_id = '';
    serviceForm.duration = 60;
    serviceForm.price = 0;
};

const saveService = async () => {
    saving.value = true;
    try {
        const formData = {
            title: serviceForm.title,
            category_id: serviceForm.category_id,
            duration: serviceForm.duration,
            price: serviceForm.price,
        };
        
        if (editingService.value) {
            await bookingStore.updateService(editingService.value.id, formData);
        } else {
            await bookingStore.createService(formData);
        }
        await bookingStore.fetchServices();
        closeServiceModal();
    } catch (error) {
        console.error('Failed to save service:', error);
        alert(t('booking.failedToSave', uiStore.locale));
    } finally {
        saving.value = false;
    }
};

const deleteService = async (id) => {
    if (confirm(t('booking.deleteServiceConfirm', uiStore.locale))) {
        try {
            await bookingStore.deleteService(id);
            await bookingStore.fetchServices();
        } catch (error) {
            console.error('Failed to delete service:', error);
            alert(t('booking.failedToDelete', uiStore.locale));
        }
    }
};

const openStaffModal = (member = null) => {
    editingStaff.value = member;
    if (member) {
        staffForm.value = {
            full_name: member.full_name,
            email: member.email,
            phone: member.phone || '',
        };
    } else {
        staffForm.value = {
            full_name: '',
            email: '',
            phone: '',
        };
    }
    showStaffModal.value = true;
};

const closeStaffModal = () => {
    showStaffModal.value = false;
    editingStaff.value = null;
    staffForm.value = {
        full_name: '',
        email: '',
        phone: '',
    };
};

const saveStaff = async () => {
    saving.value = true;
    try {
        if (editingStaff.value) {
            await bookingStore.updateStaff(editingStaff.value.id, staffForm.value);
        } else {
            await bookingStore.createStaff(staffForm.value);
        }
        await bookingStore.fetchStaff();
        closeStaffModal();
    } catch (error) {
        console.error('Failed to save staff:', error);
        alert(t('booking.failedToSave', uiStore.locale));
    } finally {
        saving.value = false;
    }
};

const deleteStaff = async (id) => {
    if (confirm(t('booking.deleteStaffConfirm', uiStore.locale))) {
        try {
            await bookingStore.deleteStaff(id);
            await bookingStore.fetchStaff();
        } catch (error) {
            console.error('Failed to delete staff:', error);
            alert(t('booking.failedToDelete', uiStore.locale));
        }
    }
};

const applyAppointmentFilters = () => {
    bookingStore.appointments.filters = { ...appointmentFilters.value };
    bookingStore.fetchAppointments();
};

const deleteAppointment = async (id) => {
    if (confirm(t('booking.deleteAppointmentConfirm', uiStore.locale))) {
        try {
            await bookingStore.deleteAppointment(id);
            await bookingStore.fetchAppointments();
        } catch (error) {
            console.error('Failed to delete appointment:', error);
            alert(t('booking.failedToDelete', uiStore.locale));
        }
    }
};

const openAppointmentModal = (appointment) => {
    // TODO: Implement appointment edit modal
    console.log('Edit appointment:', appointment);
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(price);
};

const formatDate = (dateString, part) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (part === 'day') {
        return date.getDate();
    } else if (part === 'month') {
        return date.toLocaleDateString('en-US', { month: 'short' });
    } else if (part === 'time') {
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }
    return date.toLocaleDateString();
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const getServiceName = (serviceId) => {
    const service = bookingStore.services.items.find(s => s.id === serviceId);
    return service ? service.title : `Service #${serviceId}`;
};

const getStaffName = (staffId) => {
    const staff = bookingStore.staff.items.find(s => s.id === staffId);
    return staff ? staff.full_name : `Staff #${staffId}`;
};

const getServiceAppointmentsCount = (serviceId) => {
    const items = bookingStore.appointments.items || [];
    return items.filter(a => a.service_id === serviceId).length;
};

const servicesAnalyticsRows = computed(() => {
    const services = bookingStore.services.items || [];
    const appointments = bookingStore.appointments.items || [];
    
    return services.map(service => {
        const serviceAppointments = appointments.filter(a => a.service_id === service.id);
        const appointmentsCount = serviceAppointments.length;
        
        // Calculate average duration (currently same as service duration, but could be calculated from actual appointments)
        const avgDuration = service.duration;
        
        // Revenue is currently 0 because appointments endpoint doesn't expose payment totals
        const revenue = 0;
        
        return {
            serviceId: service.id,
            serviceName: service.title,
            duration: service.duration,
            price: service.price,
            appointmentsCount,
            revenue,
            avgDuration,
            service, // Keep reference for editing
        };
    });
});

const servicesAnalyticsTotals = computed(() => {
    const rows = servicesAnalyticsRows.value;
    return rows.reduce(
        (acc, row) => {
            acc.appointmentsCount += row.appointmentsCount;
            acc.revenue += row.revenue;
            return acc;
        },
        {
            appointmentsCount: 0,
            revenue: 0,
        }
    );
});
</script>

<style scoped>
.booking-page {
    min-height: 100vh;
    background: #f5f7fa;
}

.booking-header {
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
    padding: 2rem;
    color: white;
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.header-content .header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    margin-top: 1rem;
    width: 100%;
    justify-content: flex-end;
}

.header-main {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.header-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-icon svg {
    width: 32px;
    height: 32px;
}

.header-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
}

.header-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
}

.header-stats {
    display: flex;
    gap: 1rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 1rem 1.5rem;
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.booking-tabs-nav {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 2rem;
}

.tabs-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    gap: 0.5rem;
}

.tab-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: #64748b;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.tab-button:hover {
    color: var(--asmaa-primary);
    background: rgba(102, 126, 234, 0.05);
}

.tab-button.active {
    color: var(--asmaa-primary);
    border-bottom-color: var(--asmaa-primary);
    font-weight: 600;
}

.tab-icon {
    width: 20px;
    height: 20px;
}

.tab-badge {
    background: var(--asmaa-primary);
    color: white;
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
}

.booking-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.tab-panel {
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.panel-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.header-filters {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.filter-input,
.filter-select {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--asmaa-primary);
    color: white;
}

.btn-primary:hover {
    background: #5568d3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

.btn-icon {
    width: 18px;
    height: 18px;
}

.loading-state,
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.spinner {
    width: 40px;
    height: 40px;
    margin: 0 auto 1rem;
    border: 4px solid #e2e8f0;
    border-top-color: var(--asmaa-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    color: #cbd5e0;
}

.services-grid,
.staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.service-card,
.staff-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
}

.service-card:hover,
.staff-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.service-header,
.staff-card {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.service-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.service-actions,
.staff-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn svg {
    width: 16px;
    height: 16px;
}

.action-btn.edit {
    background: #dbeafe;
    color: #1e40af;
}

.action-btn.edit:hover {
    background: #3b82f6;
    color: white;
}

.action-btn.delete {
    background: #fee2e2;
    color: #991b1b;
}

.action-btn.delete:hover {
    background: #ef4444;
    color: white;
}

.service-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
}

.info-label {
    color: #64748b;
}

.info-value {
    color: #1e293b;
    font-weight: 600;
}

.info-value.highlight {
    color: var(--asmaa-primary);
    font-weight: 700;
}

.staff-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.staff-info {
    flex: 1;
    margin-left: 1rem;
}

.staff-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.5rem;
}

.staff-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
}

.detail-icon {
    width: 16px;
    height: 16px;
}

.appointments-list-view {
    margin-top: 2rem;
}

.appointments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.appointments-overview {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.overview-card {
    background: white;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
}

.overview-card.revenue {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    border-color: transparent;
}

.overview-label {
    font-size: 0.875rem;
    color: #64748b;
}

.overview-card.revenue .overview-label {
    color: rgba(255, 255, 255, 0.9);
}

.overview-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-top: 0.25rem;
    color: #0f172a;
}

.overview-card.revenue .overview-value {
    color: white;
}

.analytics-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.analytics-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.analytics-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0.25rem 0 0;
}

.analytics-table-wrapper {
    overflow-x: auto;
}

.analytics-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.analytics-table thead {
    background: #f8fafc;
}

.analytics-table th,
.analytics-table td {
    padding: 0.75rem 0.75rem;
    border: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}

.analytics-table th {
    font-weight: 600;
    color: #475569;
}

.analytics-total-row {
    background: #f1f5f9;
    font-weight: 600;
}

.no-data {
    text-align: center;
    color: #94a3b8;
}

.analytics-note {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.75rem;
}

.services-analytics-table .service-name-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 0.75rem;
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
    color: #fff;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 8px;
}

.table-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.appointment-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
}

.appointment-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateX(4px);
}

.appointment-date {
    width: 80px;
    text-align: center;
    padding: 1rem;
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
    border-radius: 12px;
    color: white;
    flex-shrink: 0;
}

.date-day {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.date-month {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-top: 0.25rem;
}

.date-time {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 0.5rem;
}

.appointment-details {
    flex: 1;
}

.appointment-service {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.appointment-staff {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.approved {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge.completed {
    background: #dbeafe;
    color: #1e40af;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.2s;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: #f1f5f9;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #e2e8f0;
}

.modal-close svg {
    width: 20px;
    height: 20px;
}

.modal-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--asmaa-primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-stats {
        width: 100%;
        justify-content: space-between;
    }

    .tabs-container {
        overflow-x: auto;
    }

    .services-grid,
    .staff-grid {
        grid-template-columns: 1fr;
    }

    .appointment-card {
        flex-direction: column;
        align-items: flex-start;
    }

    .appointments-overview {
        grid-template-columns: 1fr 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

