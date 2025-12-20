<template>
    <div class="settings-tab">
        <div class="tab-header">
            <h2>{{ t('booking.businessHoursSettings', uiStore.locale) }}</h2>
            <p class="tab-description">{{ t('booking.businessHoursSettingsDesc', uiStore.locale) }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="settings-form">
            <div class="business-hours-list">
                <div v-for="(day, index) in days" :key="index" class="day-schedule">
                    <div class="day-header">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="day.enabled" class="form-checkbox" />
                            <span class="day-name">{{ t(day.nameKey, uiStore.locale) }}</span>
                        </label>
                    </div>
                    <div v-if="day.enabled" class="day-times">
                        <div class="time-row">
                            <input
                                type="time"
                                v-model="day.start"
                                class="form-control"
                            />
                            <span class="time-separator">-</span>
                            <input
                                type="time"
                                v-model="day.end"
                                class="form-control"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    {{ saving ? t('booking.savingSettings', uiStore.locale) : t('booking.saveSettings', uiStore.locale) }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useUIStore } from '../../../stores/ui.js';
import { useTranslation } from '../../../composables/useTranslation.js';

const uiStore = useUIStore();
const { t } = useTranslation();

const props = defineProps({
    hours: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['save']);

const days = ref([
    { nameKey: 'booking.daysOfWeek.sunday', enabled: false, start: '09:00', end: '17:00' },
    { nameKey: 'booking.daysOfWeek.monday', enabled: true, start: '09:00', end: '17:00' },
    { nameKey: 'booking.daysOfWeek.tuesday', enabled: true, start: '09:00', end: '17:00' },
    { nameKey: 'booking.daysOfWeek.wednesday', enabled: true, start: '09:00', end: '17:00' },
    { nameKey: 'booking.daysOfWeek.thursday', enabled: true, start: '09:00', end: '17:00' },
    { nameKey: 'booking.daysOfWeek.friday', enabled: true, start: '09:00', end: '17:00' },
    { nameKey: 'booking.daysOfWeek.saturday', enabled: false, start: '09:00', end: '17:00' },
]);

const saving = ref(false);

watch(() => props.hours, (newHours) => {
    if (newHours && newHours.length > 0) {
        days.value = newHours;
    }
}, { immediate: true, deep: true });

const handleSubmit = async () => {
    saving.value = true;
    try {
        await emit('save', { hours: days.value });
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
@import './SettingsTabStyles.css';

.business-hours-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.day-schedule {
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.day-header {
    margin-bottom: 0.75rem;
}

.day-name {
    font-weight: 600;
    color: #1e293b;
}

.day-times {
    margin-top: 0.75rem;
}

.time-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.time-separator {
    color: #64748b;
    font-weight: 600;
}
</style>

