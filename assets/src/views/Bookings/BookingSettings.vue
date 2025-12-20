<template>
    <div class="booking-settings-page">
        <div class="settings-header">
            <!-- Logo Background -->
            <div class="header-logo-bg">
                <img 
                    src="https://asmaaljarallah.com/wp-content/uploads/2021/09/logo_light.png" 
                    alt="Asmaa Logo"
                    class="header-logo-image"
                />
            </div>
            <div class="header-content-wrapper">
                <button class="back-button" @click="goBack">
                    <CIcon icon="cil-arrow-left" />
                    <span>{{ t('common.back') || 'Back' }}</span>
                </button>
                <div class="header-text-content">
                    <h1>
                        <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        {{ t('booking.settingsTitle') }}
                    </h1>
                    <p class="header-subtitle">{{ t('booking.settingsSubtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="settings-container">
            <div class="settings-sidebar">
                <nav class="settings-nav">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="['nav-item', { active: activeTab === tab.id }]"
                    >
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"></path>
                        </svg>
                        <span class="nav-label">{{ t(tab.labelKey) }}</span>
                    </button>
                </nav>
            </div>

            <div class="settings-content">
                <div v-if="bookingStore.settings.loading" class="loading-state">
                    <div class="spinner"></div>
                    <p>{{ uiStore.locale === 'ar' ? 'جاري تحميل الإعدادات...' : 'Loading settings...' }}</p>
                </div>

                <div v-else class="tab-content">
                    <!-- General Tab -->
                    <GeneralTab
                        v-show="activeTab === 'general'"
                        :settings="bookingStore.settings.general"
                        @save="handleSave"
                    />

                    <!-- URL Tab -->
                    <URLTab
                        v-show="activeTab === 'url'"
                        :settings="bookingStore.settings.url"
                        @save="handleSave"
                    />

                    <!-- Calendar Tab -->
                    <CalendarTab
                        v-show="activeTab === 'calendar'"
                        :settings="bookingStore.settings.calendar"
                        @save="handleSave"
                    />

                    <!-- Company Tab -->
                    <CompanyTab
                        v-show="activeTab === 'company'"
                        :settings="bookingStore.settings.company"
                        @save="handleSave"
                    />

                    <!-- Customers Tab -->
                    <CustomersTab
                        v-show="activeTab === 'customers'"
                        :settings="bookingStore.settings.customers"
                        @save="handleSave"
                    />

                    <!-- Appointments Tab -->
                    <AppointmentsTab
                        v-show="activeTab === 'appointments'"
                        :settings="bookingStore.settings.appointments"
                        @save="handleSave"
                    />

                    <!-- Payments Tab -->
                    <PaymentsTab
                        v-show="activeTab === 'payments'"
                        :settings="bookingStore.settings.payments"
                        @save="handleSave"
                    />

                    <!-- Business Hours Tab -->
                    <BusinessHoursTab
                        v-show="activeTab === 'business-hours'"
                        :hours="bookingStore.settings.business_hours"
                        @save="(data) => handleSave('business-hours', data)"
                    />

                    <!-- Holidays Tab -->
                    <HolidaysTab
                        v-show="activeTab === 'holidays'"
                        :holidays="bookingStore.settings.holidays"
                        @save="handleSave"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { CIcon } from '@coreui/icons-vue';
import { useBookingsStore } from '../../stores/bookings.js';
import { useUIStore } from '../../stores/ui.js';
import { useToast } from '../../composables/useToast.js';
import { useTranslation } from '../../composables/useTranslation.js';
import GeneralTab from './Settings/GeneralTab.vue';
import URLTab from './Settings/URLTab.vue';
import CalendarTab from './Settings/CalendarTab.vue';
import CompanyTab from './Settings/CompanyTab.vue';
import CustomersTab from './Settings/CustomersTab.vue';
import AppointmentsTab from './Settings/AppointmentsTab.vue';
import PaymentsTab from './Settings/PaymentsTab.vue';
import BusinessHoursTab from './Settings/BusinessHoursTab.vue';
import HolidaysTab from './Settings/HolidaysTab.vue';

const router = useRouter();
const bookingStore = useBookingsStore();
const uiStore = useUIStore();
const toast = useToast();
const { t } = useTranslation();
const activeTab = ref('general');

const goBack = () => {
    router.push('/bookings');
};

const tabs = [
    {
        id: 'general',
        labelKey: 'booking.tabGeneral',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        id: 'url',
        labelKey: 'booking.tabURL',
        icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
    },
    {
        id: 'calendar',
        labelKey: 'booking.tabCalendar',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        id: 'company',
        labelKey: 'booking.tabCompany',
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
    {
        id: 'customers',
        labelKey: 'booking.tabCustomers',
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    },
    {
        id: 'appointments',
        labelKey: 'booking.tabAppointments',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        id: 'payments',
        labelKey: 'booking.tabPayments',
        icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    },
    {
        id: 'business-hours',
        labelKey: 'booking.tabBusinessHours',
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        id: 'holidays',
        labelKey: 'booking.tabHolidays',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
];

onMounted(async () => {
    try {
        await bookingStore.fetchAllSettings();
    } catch (error) {
        console.error('Failed to load settings:', error);
    }
});

const handleSave = async (section, data) => {
    try {
        await bookingStore.saveSettings(section, data);
        const successMsg = uiStore.locale === 'ar' ? '✅ تم حفظ الإعدادات بنجاح' : '✅ Settings saved successfully';
        toast.success(successMsg);
    } catch (error) {
        console.error('Failed to save settings:', error);
        const errorMsg = uiStore.locale === 'ar' 
            ? '❌ فشل حفظ الإعدادات: ' + (error.message || 'خطأ غير معروف')
            : '❌ Failed to save settings: ' + (error.message || 'Unknown error');
        toast.error(errorMsg);
    }
};
</script>

<style scoped>
.booking-settings-page {
    min-height: 100vh;
    background: #f5f7fa;
    padding: 2rem;
}

.settings-header {
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
    border-radius: 12px;
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

.header-content-wrapper {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
}

.back-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    color: white;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
}

.back-button:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateX(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

[dir="rtl"] .back-button:hover {
    transform: translateX(2px);
}

.back-button CIcon {
    width: 18px;
    height: 18px;
}

.header-text-content {
    flex: 1;
}

.settings-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 0 0 0.5rem;
}

.header-icon {
    width: 32px;
    height: 32px;
    color: white;
}

.header-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    margin: 0;
}

.settings-container {
    display: flex;
    gap: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.settings-sidebar {
    width: 250px;
    flex-shrink: 0;
}

.settings-nav {
    background: white;
    border-radius: 12px;
    padding: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.nav-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: none;
    background: none;
    border-radius: 8px;
    color: #64748b;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-align: right;
}

.nav-item:hover {
    background: rgba(187, 160, 122, 0.1);
    color: var(--asmaa-primary);
    transform: translateX(-2px);
}

[dir="rtl"] .nav-item:hover {
    transform: translateX(2px);
}

.nav-item.active {
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(187, 160, 122, 0.3);
}

.nav-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.nav-label {
    flex: 1;
}

.settings-content {
    flex: 1;
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    min-height: 600px;
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e2e8f0;
    border-top-color: var(--asmaa-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.tab-content {
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

@media (max-width: 768px) {
    .settings-container {
        flex-direction: column;
    }

    .settings-sidebar {
        width: 100%;
    }

    .settings-nav {
        display: flex;
        overflow-x: auto;
        gap: 0.5rem;
    }

    .nav-item {
        white-space: nowrap;
        min-width: fit-content;
    }
}
</style>

