<template>
    <div class="booking-calendar-wrapper">
        <!-- View Switcher -->
        <div class="view-controls">
            <div class="view-switcher">
                <button 
                    class="view-btn" 
                    :class="{ active: viewMode === 'calendar' }"
                    @click="$emit('update:viewMode', 'calendar')"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{ t('booking.calendarView', locale) }}
                </button>
                <button 
                    class="view-btn" 
                    :class="{ active: viewMode === 'list' }"
                    @click="$emit('update:viewMode', 'list')"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    {{ t('booking.listView', locale) }}
                </button>
            </div>
        </div>

        <!-- Calendar -->
        <div v-show="viewMode === 'calendar'" class="calendar-container">
            <FullCalendar ref="fullCalendar" :options="calendarOptions" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { useTranslation } from '@/composables/useTranslation.js';

const { t } = useTranslation();

const props = defineProps({
    appointments: {
        type: Array,
        default: () => []
    },
    services: {
        type: Array,
        default: () => []
    },
    staff: {
        type: Array,
        default: () => []
    },
    viewMode: {
        type: String,
        default: 'calendar'
    },
    locale: {
        type: String,
        default: 'en'
    }
});

const emit = defineEmits(['update:viewMode', 'eventClick', 'dateClick']);

const fullCalendar = ref(null);

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    locale: props.locale === 'ar' ? 'ar' : 'en',
    direction: props.locale === 'ar' ? 'rtl' : 'ltr',
    events: calendarEvents.value,
    eventClick: handleEventClick,
    dateClick: handleDateClick,
    editable: false,
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    weekends: true,
    eventTimeFormat: {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    },
    height: 'auto',
    buttonText: {
        today: t('booking.today', props.locale),
        month: t('booking.month', props.locale),
        week: t('booking.week', props.locale),
        day: t('booking.day', props.locale),
    }
}));

const calendarEvents = computed(() => {
    return props.appointments.map(appointment => {
        const service = props.services.find(s => s.id === appointment.service_id);
        const staff = props.staff.find(s => s.id === appointment.staff_id);
        
        return {
            id: appointment.id,
            title: `${service?.title || 'Service'} - ${staff?.full_name || 'Staff'}`,
            start: appointment.start_date,
            end: appointment.end_date,
            backgroundColor: getStatusColor(appointment.status),
            borderColor: getStatusColor(appointment.status),
            extendedProps: {
                appointmentData: appointment
            }
        };
    });
});

const getStatusColor = (status) => {
    // Use theme variables so events stay readable in light/dark
    const colors = {
        pending: 'var(--asmaa-warning)',
        approved: 'var(--asmaa-success)',
        cancelled: 'var(--asmaa-danger)',
        completed: 'var(--asmaa-primary)',
        rejected: 'var(--asmaa-danger)',
    };
    return colors[status] || 'var(--asmaa-primary)';
};

const handleEventClick = (info) => {
    const appointment = info.event.extendedProps.appointmentData;
    emit('eventClick', appointment);
};

const handleDateClick = (info) => {
    emit('dateClick', info.dateStr);
};

// Watch locale changes to update calendar
watch(() => props.locale, () => {
    if (fullCalendar.value) {
        const calendarApi = fullCalendar.value.getApi();
        calendarApi.setOption('locale', props.locale === 'ar' ? 'ar' : 'en');
        calendarApi.setOption('direction', props.locale === 'ar' ? 'rtl' : 'ltr');
    }
});
</script>

<style scoped>
.booking-calendar-wrapper {
    background: var(--bg-primary);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.view-controls {
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: flex-end;
}

.view-switcher {
    display: flex;
    background: var(--bg-secondary);
    border-radius: 8px;
    padding: 4px;
    gap: 4px;
    border: 1px solid var(--border-color);
}

.view-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.view-btn svg {
    width: 16px;
    height: 16px;
}

.view-btn.active {
    background: var(--bg-primary);
    color: var(--asmaa-primary);
    box-shadow: var(--shadow-sm);
}

.view-btn:hover:not(.active) {
    color: var(--text-primary);
}

.calendar-container {
    min-height: 600px;
}

/* FullCalendar Custom Styles */
.calendar-container :deep(.fc) {
    font-family: inherit;
}

.calendar-container :deep(.fc-toolbar-title) {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
}

.calendar-container :deep(.fc-button) {
    background: var(--asmaa-primary);
    border-color: var(--asmaa-primary);
    text-transform: capitalize;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.10);
}

.calendar-container :deep(.fc-button:hover) {
    background: var(--asmaa-primary-600);
    border-color: var(--asmaa-primary-600);
}

.calendar-container :deep(.fc-button-active) {
    background: var(--asmaa-primary-700) !important;
    border-color: var(--asmaa-primary-700) !important;
}

.calendar-container :deep(.fc-button:focus) {
    box-shadow: 0 0 0 0.2rem var(--asmaa-primary-soft);
}

.calendar-container :deep(.fc-daygrid-day-number) {
    color: var(--text-secondary);
    font-weight: 600;
    padding: 0.5rem;
}

.calendar-container :deep(.fc-daygrid-day.fc-day-today) {
    background: var(--asmaa-primary-soft) !important;
}

.calendar-container :deep(.fc-event) {
    border-radius: 4px;
    padding: 2px 4px;
    margin: 1px 0;
    font-size: 0.75rem;
    cursor: pointer;
}

.calendar-container :deep(.fc-event:hover) {
    opacity: 0.8;
}

.calendar-container :deep(.fc-col-header-cell) {
    background: var(--bg-secondary);
    font-weight: 600;
    color: var(--text-secondary);
    padding: 1rem 0.5rem;
}

.calendar-container :deep(.fc-scrollgrid) {
    border-color: var(--border-color) !important;
}

.calendar-container :deep(.fc-theme-standard td),
.calendar-container :deep(.fc-theme-standard th) {
    border-color: var(--border-color);
}

/* FullCalendar small polish */
.calendar-container :deep(.fc-toolbar-chunk) {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.calendar-container :deep(.fc-button-group) {
    gap: 0.5rem;
}

.calendar-container :deep(.fc-button-group > .fc-button) {
    border-radius: 8px;
}

.calendar-container :deep(.fc-daygrid-day:hover) {
    background: var(--bg-tertiary);
}

.calendar-container :deep(.fc-event-title),
.calendar-container :deep(.fc-event-time) {
    color: #fff;
    font-weight: 700;
}
</style>


