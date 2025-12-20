<template>
    <div class="booking-page">
        <!-- Header -->
        <div class="booking-header">
            <!-- Logo Background -->
            <div class="header-logo-bg">
                <img 
                    src="https://asmaaljarallah.com/wp-content/uploads/2021/09/logo_light.png" 
                    alt="Asmaa Logo"
                    class="header-logo-image"
                />
            </div>
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
                    <StatCard 
                        :label="t('booking.totalServices')"
                        :value="bookingStore.services.items.length"
                        color="gold"
                    >
                        <template #icon>
                            <CIcon icon="cil-spreadsheet" />
                        </template>
                    </StatCard>
                    <StatCard 
                        :label="t('booking.totalStaff')"
                        :value="bookingStore.staff.items.length"
                        color="gold"
                    >
                        <template #icon>
                            <CIcon icon="cil-user" />
                        </template>
                    </StatCard>
                    <StatCard 
                        :label="t('booking.totalAppointments')"
                        :value="bookingStore.appointments.items.length"
                        color="gold"
                    >
                        <template #icon>
                            <CIcon icon="cil-calendar" />
                        </template>
                    </StatCard>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" @click="goToAddBooking">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ t('booking.addBooking') || t('booking.addAppointment') }}
                    </button>
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
                    <p>{{ t('booking.loadingServices') }}</p>
                </div>

                <div v-else-if="bookingStore.services.items.length === 0" class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <p>{{ t('booking.noServicesFound') }}</p>
                    <button class="btn btn-primary" @click="openServiceModal()">
                        {{ t('booking.addFirstService') }}
                    </button>
                </div>

                <div v-else>
                    <!-- Services Analytics Table -->
                    <div class="analytics-card">
                        <div class="analytics-header">
                            <div>
                                <h3 class="analytics-title">{{ t('booking.servicesAnalyticsTitle') }}</h3>
                                <p class="analytics-subtitle">{{ t('booking.servicesAnalyticsSubtitle') }}</p>
                            </div>
                        </div>

                        <div class="analytics-table-wrapper">
                            <table class="analytics-table services-analytics-table">
                                <thead>
                                    <tr class="table-header-row">
                                        <th class="th-service-name">{{ t('booking.serviceName') }}</th>
                                        <th class="th-duration">{{ t('booking.duration') }}</th>
                                        <th class="th-price">{{ t('booking.price') }}</th>
                                        <th class="th-appointments">{{ t('booking.analyticsAppointments') }}</th>
                                        <th class="th-revenue">{{ t('booking.analyticsRevenue') }}</th>
                                        <th class="th-avg-duration">{{ t('booking.averageAppointmentDuration') }}</th>
                                        <th class="th-actions">{{ t('booking.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="servicesAnalyticsRows.length === 0">
                                        <td class="no-data" colspan="7">
                                            {{ t('booking.analyticsNoData') }}
                                        </td>
                                    </tr>
                                    <tr v-for="row in servicesAnalyticsRows" :key="row.serviceId" class="service-analytics-row">
                                        <td class="td-service-name">
                                            <div class="service-name-cell">
                                                <strong class="service-name-text">{{ row.serviceName }}</strong>
                                            </div>
                                        </td>
                                        <td class="td-duration">
                                            <CBadge class="unified-badge duration-badge">
                                                <CIcon icon="cil-clock" class="badge-icon" />
                                                <span>{{ row.duration }} {{ t('booking.minutes') }}</span>
                                            </CBadge>
                                        </td>
                                        <td class="td-price">
                                            <strong class="unified-amount price-amount">
                                                <CIcon icon="cil-money" class="money-icon" />
                                                {{ formatPrice(row.price) }}
                                            </strong>
                                        </td>
                                        <td class="td-appointments">
                                            <CBadge class="unified-badge appointments-badge">
                                                <CIcon icon="cil-calendar" class="badge-icon" />
                                                <span>{{ row.appointmentsCount }}</span>
                                            </CBadge>
                                        </td>
                                        <td class="td-revenue">
                                            <strong class="unified-amount revenue-amount">
                                                <CIcon icon="cil-dollar" class="money-icon" />
                                                {{ formatPrice(row.revenue) }}
                                            </strong>
                                        </td>
                                        <td class="td-avg-duration">
                                            <span class="avg-duration-text">{{ row.avgDuration }} {{ t('booking.minutes') }}</span>
                                        </td>
                                        <td class="td-actions">
                                            <div class="table-actions">
                                                <button class="action-btn" @click="viewService(row.service)" :title="t('common.view')">
                                                    <CIcon icon="cil-info" />
                                                </button>
                                                <button class="action-btn" @click="openServiceModal(row.service)" :title="t('booking.edit')">
                                                    <CIcon icon="cil-pencil" />
                                                </button>
                                                <button class="action-btn" @click="deleteService(row.serviceId)" :title="t('booking.delete')">
                                                    <CIcon icon="cil-trash" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="servicesAnalyticsRows.length > 0" class="analytics-total-row">
                                        <td colspan="3"><strong>{{ t('booking.analyticsTotalLabel') }}</strong></td>
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
                                    <button class="action-btn-card" @click="viewService(service)" :title="t('common.view')">
                                        <CIcon icon="cil-info" />
                                    </button>
                                    <button class="action-btn-card" @click="openServiceModal(service)" :title="t('booking.edit')">
                                        <CIcon icon="cil-pencil" />
                                    </button>
                                    <button class="action-btn-card" @click="deleteService(service.id)" :title="t('booking.delete')">
                                        <CIcon icon="cil-trash" />
                                    </button>
                                </div>
                            </div>
                            <div class="service-body">
                                <div class="service-info">
                                    <div class="info-item">
                                        <span class="info-label">{{ t('booking.duration') }}:</span>
                                        <span class="info-value">{{ service.duration }} {{ t('booking.minutes') }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">{{ t('booking.price') }}:</span>
                                        <span class="info-value">{{ formatPrice(service.price) }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">{{ t('booking.appointmentsBooked') }}:</span>
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
                    <h2 class="panel-title">{{ t('booking.staff') }}</h2>
                    <button class="btn btn-primary" @click="openStaffModal()">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ t('booking.addStaff') }}
                    </button>
                </div>

                <div v-if="bookingStore.staff.loading" class="loading-state">
                    <div class="spinner"></div>
                    <p>{{ t('booking.loadingStaff') }}</p>
                </div>

                <div v-else-if="bookingStore.staff.items.length === 0" class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p>{{ t('booking.noStaffFound') }}</p>
                    <button class="btn btn-primary" @click="openStaffModal()">
                        {{ t('booking.addFirstStaff') }}
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
                            <button class="action-btn edit" @click="openStaffModal(member)" :title="t('booking.edit')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button class="action-btn delete" @click="deleteStaff(member.id)" :title="t('booking.delete')">
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
                    <h2 class="panel-title">{{ t('booking.appointments') }}</h2>
                    <button class="btn btn-primary" @click="openAppointmentModal()">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ t('booking.addAppointment') }}
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
                            :placeholder="t('booking.dateFrom')"
                        />
                        <input
                            v-model="appointmentFilters.date_to"
                            type="date"
                            class="filter-input"
                            :placeholder="t('booking.dateTo')"
                        />
                        <select v-model="appointmentFilters.status" class="filter-select">
                            <option value="">{{ t('booking.allStatuses') }}</option>
                            <option value="pending">{{ t('booking.pending') }}</option>
                            <option value="approved">{{ t('booking.approved') }}</option>
                            <option value="cancelled">{{ t('booking.cancelled') }}</option>
                            <option value="completed">{{ t('booking.completed') }}</option>
                        </select>
                        <button class="btn btn-secondary" @click="applyAppointmentFilters">
                            {{ t('booking.applyFilters') }}
                        </button>
                    </div>
                </div>

                <div v-if="bookingStore.appointments.loading" class="loading-state">
                    <div class="spinner"></div>
                    <p>{{ t('booking.loadingAppointments') }}</p>
                </div>

                <div v-else-if="bookingStore.appointments.items.length === 0" class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p>{{ t('booking.noAppointmentsFound') }}</p>
                </div>

                <div v-else>
                    <!-- Overview cards like Booking analytics -->
                    <div class="appointments-overview">
                        <StatCard 
                            :label="t('booking.approvedAppointments')"
                            :value="appointmentSummary.approved"
                            color="gold"
                        >
                            <template #icon>
                                <CIcon icon="cil-check-circle" />
                            </template>
                        </StatCard>
                        <StatCard 
                            :label="t('booking.pendingAppointments')"
                            :value="appointmentSummary.pending"
                            color="gold"
                        >
                            <template #icon>
                                <CIcon icon="cil-clock" />
                            </template>
                        </StatCard>
                        <StatCard 
                            :label="t('booking.totalAppointmentsLabel')"
                            :value="appointmentSummary.total"
                            color="gold"
                        >
                            <template #icon>
                                <CIcon icon="cil-calendar" />
                            </template>
                        </StatCard>
                        <StatCard 
                            :label="t('booking.revenue')"
                            :value="formatPrice(appointmentSummary.revenue)"
                            color="gold"
                        >
                            <template #icon>
                                <CIcon icon="cil-dollar" />
                            </template>
                        </StatCard>
                    </div>

                    <!-- Analytics table (employee / service breakdown) -->
                    <div class="analytics-card">
                        <div class="analytics-header">
                            <div>
                                <h3 class="analytics-title">{{ t('booking.analyticsTitle') }}</h3>
                                <p class="analytics-subtitle">{{ t('booking.analyticsSubtitle') }}</p>
                            </div>
                        </div>

                        <div class="analytics-table-wrapper">
                            <table class="analytics-table appointments-analytics-table">
                                <thead>
                                    <tr class="table-header-row">
                                        <th rowspan="2" class="th-employee">{{ t('booking.analyticsEmployee') }}</th>
                                        <th rowspan="2" class="th-service">{{ t('booking.analyticsService') }}</th>
                                        <th colspan="5" class="th-appointments-header">{{ t('booking.analyticsAppointments') }}</th>
                                        <th colspan="2" class="th-customers-header">{{ t('booking.analyticsCustomers') }}</th>
                                        <th rowspan="2" class="th-revenue">{{ t('booking.analyticsRevenue') }}</th>
                                    </tr>
                                    <tr class="table-header-row">
                                        <th class="th-total">{{ t('booking.total') }}</th>
                                        <th class="th-approved">{{ t('booking.approved') }}</th>
                                        <th class="th-pending">{{ t('booking.pending') }}</th>
                                        <th class="th-rejected">{{ t('booking.rejected') }}</th>
                                        <th class="th-cancelled">{{ t('booking.cancelled') }}</th>
                                        <th class="th-customers-total">{{ t('booking.total') }}</th>
                                        <th class="th-new-customers">{{ t('booking.new') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="appointmentAnalyticsRows.length === 0">
                                        <td class="no-data" colspan="10">
                                            {{ t('booking.analyticsNoData') }}
                                        </td>
                                    </tr>
                                    <tr v-for="row in appointmentAnalyticsRows" :key="row.key" class="appointment-analytics-row">
                                        <td class="td-employee">{{ row.employeeName }}</td>
                                        <td class="td-service">{{ row.serviceName }}</td>
                                        <td class="td-total">
                                            <CBadge class="unified-badge">{{ row.total }}</CBadge>
                                        </td>
                                        <td class="td-approved">
                                            <CBadge class="unified-badge">{{ row.approved }}</CBadge>
                                        </td>
                                        <td class="td-pending">
                                            <CBadge class="unified-badge">{{ row.pending }}</CBadge>
                                        </td>
                                        <td class="td-rejected">
                                            <CBadge class="unified-badge">{{ row.rejected }}</CBadge>
                                        </td>
                                        <td class="td-cancelled">
                                            <CBadge class="unified-badge">{{ row.cancelled }}</CBadge>
                                        </td>
                                        <td class="td-customers-total">
                                            <CBadge class="unified-badge">{{ row.customersTotal }}</CBadge>
                                        </td>
                                        <td class="td-new-customers">
                                            <CBadge class="unified-badge">{{ row.newCustomers }}</CBadge>
                                        </td>
                                        <td class="td-revenue">
                                            <strong class="unified-amount">{{ formatPrice(row.revenue) }}</strong>
                                        </td>
                                    </tr>
                                    <tr v-if="appointmentAnalyticsRows.length > 0" class="analytics-total-row">
                                        <td colspan="2"><strong>{{ t('booking.analyticsTotalLabel') }}</strong></td>
                                        <td><strong>{{ appointmentAnalyticsTotals.total }}</strong></td>
                                        <td><strong>{{ appointmentAnalyticsTotals.approved }}</strong></td>
                                        <td><strong>{{ appointmentAnalyticsTotals.pending }}</strong></td>
                                        <td><strong>{{ appointmentAnalyticsTotals.rejected }}</strong></td>
                                        <td><strong>{{ appointmentAnalyticsTotals.cancelled }}</strong></td>
                                        <td><strong>{{ appointmentAnalyticsTotals.customersTotal }}</strong></td>
                                        <td><strong>{{ appointmentAnalyticsTotals.newCustomers }}</strong></td>
                                        <td><strong>{{ formatPrice(appointmentAnalyticsTotals.revenue) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="analytics-note">
                                {{ t('booking.analyticsNote') }}
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
                                    {{ t('booking.with') }}: {{ getStaffName(appointment.staff_id) }}
                                </div>
                                <div class="appointment-status">
                                    <span :class="['status-badge', appointment.status]">
                                        {{ t(`booking.${appointment.status}`) }}
                                    </span>
                                </div>
                            </div>
                            <div class="appointment-actions">
                                <button
                                    class="action-btn"
                                    @click="openAppointmentModal(appointment)"
                                    :title="t('booking.edit')"
                                >
                                    <CIcon icon="cil-pencil" />
                                </button>
                                <button
                                    class="action-btn"
                                    @click="deleteAppointment(appointment.id)"
                                    :title="t('booking.delete')"
                                >
                                    <CIcon icon="cil-trash" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Service Modal -->
        <div v-if="showViewServiceModal" class="modal-overlay" @click="closeViewServiceModal">
            <div class="modal-content modal-content-large" @click.stop>
                <div class="modal-header">
                    <h3>
                        <CIcon icon="cil-spreadsheet" class="me-2" />
                        {{ t('booking.serviceDetails') || 'Service Details' }}
                    </h3>
                    <button class="modal-close" @click="closeViewServiceModal">
                        <CIcon icon="cil-x" />
                    </button>
                </div>
                <div class="modal-body" v-if="viewingService">
                    <div class="service-details-view">
                        <!-- Service Header -->
                        <div class="service-header">
                            <div class="service-avatar">
                                <CIcon icon="cil-spreadsheet" />
                            </div>
                            <div class="service-header-info">
                                <h4 class="service-name-large">{{ viewingService.title }}</h4>
                            </div>
                        </div>

                        <!-- Service Stats -->
                        <div class="service-stats-grid">
                            <div class="stat-item">
                                <CIcon icon="cil-clock" class="stat-icon" />
                                <div class="stat-content">
                                    <div class="stat-label">{{ t('booking.duration') }}</div>
                                    <div class="stat-value">{{ viewingService.duration }} {{ t('booking.minutes') }}</div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <CIcon icon="cil-money" class="stat-icon" />
                                <div class="stat-content">
                                    <div class="stat-label">{{ t('booking.price') }}</div>
                                    <div class="stat-value">{{ formatPrice(viewingService.price) }}</div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <CIcon icon="cil-calendar" class="stat-icon" />
                                <div class="stat-content">
                                    <div class="stat-label">{{ t('booking.analyticsAppointments') }}</div>
                                    <div class="stat-value">{{ getServiceAppointmentsCount(viewingService.id) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Service Information -->
                        <div class="service-info-grid">
                            <div class="info-row" v-if="viewingService.category_id">
                                <div class="info-label">
                                    <CIcon icon="cil-tag" class="me-2" />
                                    {{ t('booking.category') }}
                                </div>
                                <div class="info-value">{{ viewingService.category_id }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeViewServiceModal">{{ t('booking.close') || 'Close' }}</button>
                    <button class="btn btn-primary" @click="() => { closeViewServiceModal(); openServiceModal(viewingService); }">
                        <CIcon icon="cil-pencil" class="me-2" />
                        {{ t('booking.edit') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Service Modal -->
        <div v-if="showServiceModal" class="modal-overlay" @click="closeServiceModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>{{ editingService ? t('booking.editService') : t('booking.addService') }}</h3>
                    <button class="modal-close" @click="closeServiceModal">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ t('booking.serviceName') }} *</label>
                        <input v-model="serviceForm.title" type="text" class="form-input" required />
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>{{ t('booking.duration') }} ({{ t('booking.minutes') }}) *</label>
                            <input v-model.number="serviceForm.duration" type="number" class="form-input" min="1" required />
                        </div>
                        <div class="form-group">
                            <label>{{ t('booking.price') }} *</label>
                            <input v-model.number="serviceForm.price" type="number" class="form-input" step="0.01" min="0" required />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeServiceModal">{{ t('booking.cancel') }}</button>
                    <button class="btn btn-primary" @click="saveService" :disabled="saving">
                        {{ saving ? t('booking.saving') : t('booking.save') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Staff Modal -->
        <div v-if="showStaffModal" class="modal-overlay" @click="closeStaffModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>{{ editingStaff ? t('booking.editStaff') : t('booking.addStaff') }}</h3>
                    <button class="modal-close" @click="closeStaffModal">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ t('booking.fullName') }} *</label>
                        <input v-model="staffForm.full_name" type="text" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>{{ t('booking.email') }} *</label>
                        <input v-model="staffForm.email" type="email" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>{{ t('booking.phone') }}</label>
                        <input v-model="staffForm.phone" type="tel" class="form-input" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeStaffModal">{{ t('booking.cancel') }}</button>
                    <button class="btn btn-primary" @click="saveStaff" :disabled="saving">
                        {{ saving ? t('booking.saving') : t('booking.save') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Appointment Modal -->
        <div v-if="showAppointmentModal" class="modal-overlay" @click="closeAppointmentModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>{{ editingAppointment ? 'Edit Booking' : 'Add Booking' }}</h3>
                    <button class="modal-close" @click="closeAppointmentModal">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>{{ t('invoices.customer') || 'Customer' }} *</label>
                            <input
                                v-model="customerSearch"
                                type="text"
                                class="form-input"
                                :placeholder="t('common.search') || 'Search...'"
                                @input="debounceCustomerSearch"
                            />
                            <select v-model.number="appointmentForm.customer_id" class="form-input" required>
                                <option :value="0" disabled>{{ t('common.select') || 'Select...' }}</option>
                                <option v-if="customersLoading" :value="0" disabled>Loading...</option>
                                <option
                                    v-for="c in customersOptions"
                                    :key="c.id"
                                    :value="Number(c.id)"
                                >
                                    {{ c.name || '—' }}{{ c.phone ? ` (${c.phone})` : '' }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select v-model="appointmentForm.status" class="form-input">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Service *</label>
                            <select v-model.number="appointmentForm.service_id" class="form-input" required>
                                <option :value="0" disabled>Select service...</option>
                                <option v-for="s in bookingStore.services.items" :key="s.id" :value="Number(s.id)">
                                    {{ s.title }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Staff</label>
                            <select v-model.number="appointmentForm.staff_id" class="form-input">
                                <option :value="0">Any</option>
                                <option v-for="st in bookingStore.staff.items" :key="st.id" :value="Number(st.id)">
                                    {{ st.full_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date *</label>
                            <input v-model="appointmentForm.booking_date" type="date" class="form-input" required />
                        </div>
                        <div class="form-group">
                            <label>Time *</label>
                            <input v-model="appointmentForm.booking_time" type="time" class="form-input" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea v-model="appointmentForm.notes" class="form-input" rows="3" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeAppointmentModal">{{ t('booking.cancel') }}</button>
                    <button class="btn btn-primary" @click="saveAppointment" :disabled="saving">
                        {{ saving ? t('booking.saving') : t('booking.save') }}
                    </button>
                </div>
            </div>
        </div>
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
import StatCard from '@/components/UI/StatCard.vue';
import { CIcon } from '@coreui/icons-vue';
import { CBadge } from '@coreui/vue';

const { t } = useTranslation();
const uiStore = useUIStore();
const bookingStore = useBookingsStore();
const route = useRoute();
const router = useRouter();

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
        label: t('booking.services'),
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        count: bookingStore.services.items.length,
    },
    {
        id: 'staff',
        label: t('booking.staff'),
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        count: bookingStore.staff.items.length,
    },
    {
        id: 'appointments',
        label: t('booking.appointments'),
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        count: bookingStore.appointments.items.length,
    },
]);

onMounted(() => {
    bookingStore.fetchServices();
    bookingStore.fetchStaff();
    bookingStore.fetchAppointments();

    // Deep link: /bookings?action=create
    if (route.query?.action === 'create') {
        activeTab.value = 'appointments';
        openAppointmentModal(null);
        const nextQuery = { ...route.query };
        delete nextQuery.action;
        router.replace({ query: nextQuery });
    }
});

const viewService = (service) => {
    viewingService.value = service;
    showViewServiceModal.value = true;
};

const closeViewServiceModal = () => {
    showViewServiceModal.value = false;
    viewingService.value = null;
};

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
        alert(t('booking.failedToSave'));
    } finally {
        saving.value = false;
    }
};

const deleteService = async (id) => {
    if (confirm(t('booking.deleteServiceConfirm'))) {
        try {
            await bookingStore.deleteService(id);
            await bookingStore.fetchServices();
        } catch (error) {
            console.error('Failed to delete service:', error);
            alert(t('booking.failedToDelete'));
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
        alert(t('booking.failedToSave'));
    } finally {
        saving.value = false;
    }
};

const deleteStaff = async (id) => {
    if (confirm(t('booking.deleteStaffConfirm'))) {
        try {
            await bookingStore.deleteStaff(id);
            await bookingStore.fetchStaff();
        } catch (error) {
            console.error('Failed to delete staff:', error);
            alert(t('booking.failedToDelete'));
        }
    }
};

const applyAppointmentFilters = () => {
    bookingStore.appointments.filters = { ...appointmentFilters.value };
    bookingStore.fetchAppointments();
};

const deleteAppointment = async (id) => {
    if (confirm(t('booking.deleteAppointmentConfirm'))) {
        try {
            await bookingStore.deleteAppointment(id);
            await bookingStore.fetchAppointments();
        } catch (error) {
            console.error('Failed to delete appointment:', error);
            alert(t('booking.failedToDelete'));
        }
    }
};

const goToAddBooking = () => {
    activeTab.value = 'appointments';
    appointmentViewMode.value = 'calendar';

    // avoid opening an empty form when services are not ready
    if (!bookingStore.services.items || bookingStore.services.items.length === 0) {
        activeTab.value = 'services';
        alert(t('booking.noServicesFound'));
        return;
    }

    openAppointmentModal(null);
};

const openAppointmentModal = (appointment) => {
    editingAppointment.value = appointment || null;

    if (appointment) {
        appointmentForm.value = {
            customer_id: Number(appointment.customer_id || 0),
            staff_id: Number(appointment.staff_id || 0),
            service_id: Number(appointment.service_id || 0),
            booking_date: appointment.booking_date || '',
            booking_time: appointment.booking_time || '',
            status: appointment.status || 'pending',
            notes: appointment.notes || '',
        };
    } else {
        appointmentForm.value = {
            customer_id: 0,
            staff_id: 0,
            service_id: Number(bookingStore.services.items?.[0]?.id || 0),
            booking_date: new Date().toISOString().slice(0, 10),
            booking_time: '10:00',
            status: 'pending',
            notes: '',
        };
    }

    showAppointmentModal.value = true;

    // Load customers list for dropdown
    if (!customersOptions.value || customersOptions.value.length === 0) {
        fetchCustomers('');
    }
    ensureSelectedCustomerLoaded(appointmentForm.value.customer_id);
};

const closeAppointmentModal = () => {
    showAppointmentModal.value = false;
    editingAppointment.value = null;
};

const saveAppointment = async () => {
    if (!appointmentForm.value.customer_id || !appointmentForm.value.service_id || !appointmentForm.value.booking_date || !appointmentForm.value.booking_time) {
        alert('Customer, service, date and time are required.');
        return;
    }

    saving.value = true;
    try {
        const payload = {
            customer_id: Number(appointmentForm.value.customer_id),
            service_id: Number(appointmentForm.value.service_id),
            staff_id: appointmentForm.value.staff_id ? Number(appointmentForm.value.staff_id) : null,
            booking_date: appointmentForm.value.booking_date,
            booking_time: appointmentForm.value.booking_time,
            status: appointmentForm.value.status,
            notes: appointmentForm.value.notes,
        };

        if (editingAppointment.value?.id) {
            await bookingStore.updateAppointment(editingAppointment.value.id, payload);
        } else {
            await bookingStore.createAppointment(payload);
        }

        await bookingStore.fetchAppointments();
        closeAppointmentModal();
    } catch (error) {
        console.error('Failed to save appointment:', error);
        alert('Failed to save booking');
    } finally {
        saving.value = false;
    }
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
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
    padding: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
}

.header-logo-bg {
    position: absolute;
    top: 50%;
    right: 2%;
    width: auto;
    height: 70%;
    max-height: 250px;
    opacity: 0.08;
    pointer-events: none;
    overflow: visible;
    z-index: 0;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-logo-image {
    width: auto;
    height: 100%;
    max-width: 350px;
    object-fit: contain;
    filter: drop-shadow(0 1px 4px rgba(0, 0, 0, 0.05));
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
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
    gap: 0.75rem;
    flex-wrap: wrap;
}

.header-stats :deep(.stat-card) {
    padding: 1rem 1.25rem;
    flex: 1;
    min-width: 150px;
    max-width: 220px;
}

.header-stats :deep(.stat-card-value) {
    font-size: 1.5rem;
}

.header-stats :deep(.stat-card-label) {
    font-size: 0.7rem;
}

.header-stats :deep(.stat-card-icon) {
    width: 40px;
    height: 40px;
}

.header-stats :deep(.stat-card-icon svg) {
    width: 20px;
    height: 20px;
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
    background: rgba(187, 160, 122, 0.05);
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
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(187, 160, 122, 0.4);
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
    border-top-color: #667eea;
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

/* Service Details Modal Styles */
.service-details-view {
    padding: 0.5rem 0;
}

.service-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(187, 160, 122, 0.2);
}

.service-avatar {
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

.service-avatar CIcon {
    width: 40px;
    height: 40px;
}

.service-header-info {
    flex: 1;
}

.service-name-large {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
}

.service-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
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

.service-info-grid {
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
    color: #667eea;
    font-weight: 700;
}

.staff-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.appointments-overview :deep(.stat-card) {
    padding: 1rem 1.25rem;
}

.appointments-overview :deep(.stat-card-value) {
    font-size: 1.5rem;
}

.appointments-overview :deep(.stat-card-label) {
    font-size: 0.7rem;
}

.appointments-overview :deep(.stat-card-icon) {
    width: 40px;
    height: 40px;
}

.appointments-overview :deep(.stat-card-icon svg) {
    width: 20px;
    height: 20px;
}

.analytics-card {
    background: var(--bg-primary);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--border-color);
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
    color: var(--text-primary);
    margin: 0;
}

.analytics-subtitle {
    font-size: 0.875rem;
    color: var(--text-muted);
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
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
}

.table-header-row {
    border-bottom: 2px solid var(--asmaa-primary);
}

.analytics-table th,
.analytics-table td {
    padding: 1rem 1.25rem;
    border: 1px solid var(--border-color);
    text-align: left;
    white-space: nowrap;
}

.analytics-table th {
    font-weight: 700;
    color: var(--text-primary);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.th-service-name {
    min-width: 200px;
}

.th-duration,
.th-price,
.th-appointments,
.th-revenue,
.th-avg-duration {
    width: 140px;
    text-align: center;
}

.th-revenue {
    text-align: right;
}

.th-actions {
    width: 140px;
    text-align: center;
}

.analytics-total-row {
    background: var(--bg-tertiary);
    font-weight: 600;
}

.no-data {
    text-align: center;
    color: var(--text-muted);
}

.analytics-note {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.75rem;
}

.service-analytics-row {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-bottom: 1px solid var(--border-color);
}

.service-analytics-row:hover {
    background: linear-gradient(90deg, rgba(187, 160, 122, 0.05) 0%, rgba(187, 160, 122, 0.02) 100%);
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(187, 160, 122, 0.1);
}

[dir="rtl"] .service-analytics-row:hover {
    transform: translateX(-4px);
}

.services-analytics-table .service-name-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.service-name-text {
    color: var(--text-primary);
    font-weight: 700;
    font-size: 0.9375rem;
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

.badge-icon {
    width: 14px;
    height: 14px;
}

/* Unified Amount */
.unified-amount {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--asmaa-primary);
    font-weight: 700;
    font-size: 0.9375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
    transition: all 0.3s;
}

.unified-amount:hover {
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.15) 0%, rgba(187, 160, 122, 0.1) 100%);
    transform: translateY(-1px);
}

.money-icon {
    width: 16px;
    height: 16px;
    color: var(--asmaa-primary);
}

.avg-duration-text {
    color: var(--text-primary);
    font-size: 0.875rem;
}

.table-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    align-items: center;
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

.action-btn-card {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
    color: white;
    box-shadow: 0 2px 4px rgba(187, 160, 122, 0.2);
}

.action-btn-card:hover {
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
    transform: translateY(-2px) scale(1.1);
    box-shadow: 0 4px 8px rgba(187, 160, 122, 0.3);
}

.action-btn-card CIcon {
    width: 16px;
    height: 16px;
}

.appointment-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border-color);
}

.appointment-card:hover {
    box-shadow: 0 4px 12px rgba(187, 160, 122, 0.2);
    transform: translateX(4px);
    border-color: rgba(187, 160, 122, 0.4);
}

[dir="rtl"] .appointment-card:hover {
    transform: translateX(-4px);
}

.appointment-date {
    width: 80px;
    text-align: center;
    padding: 1rem;
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
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

.status-badge {
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

.status-badge.pending {
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.2) 0%, rgba(187, 160, 122, 0.15) 100%);
    color: var(--asmaa-primary);
    border-color: rgba(187, 160, 122, 0.4);
}

.status-badge.approved {
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.25) 0%, rgba(187, 160, 122, 0.2) 100%);
    color: var(--asmaa-primary);
    border-color: rgba(187, 160, 122, 0.5);
}

.status-badge.cancelled {
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.15) 0%, rgba(187, 160, 122, 0.1) 100%);
    color: var(--asmaa-primary);
    border-color: rgba(187, 160, 122, 0.3);
}

.status-badge.completed {
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.3) 0%, rgba(187, 160, 122, 0.25) 100%);
    color: var(--asmaa-primary);
    border-color: rgba(187, 160, 122, 0.6);
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

.modal-content-large {
    max-width: 700px;
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
    box-shadow: 0 0 0 3px rgba(187, 160, 122, 0.15);
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
    
    .appointments-overview {
        grid-template-columns: 1fr;
    }
    
    .header-logo-bg {
        width: 250px;
        height: 250px;
        right: -50px;
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
        grid-template-columns: 1fr;
    }
    
    .header-logo-bg {
        max-height: 150px;
        right: -20px;
        opacity: 0.06;
    }
    
    .header-logo-image {
        max-width: 200px;
    }
    
    .header-stats :deep(.stat-card) {
        min-width: 120px;
        max-width: 180px;
        padding: 0.875rem 1rem;
    }
    
    .header-stats :deep(.stat-card-value) {
        font-size: 1.25rem;
    }
    
    .appointments-overview :deep(.stat-card) {
        padding: 0.875rem 1rem;
    }
    
    .appointments-overview :deep(.stat-card-value) {
        font-size: 1.25rem;
    }
    
    .back-button {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .back-button span {
        display: none;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

