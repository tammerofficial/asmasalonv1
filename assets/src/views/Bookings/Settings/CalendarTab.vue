<template>
    <div class="settings-tab">
        <div class="tab-header">
            <h2>{{ t('booking.calendarSettings', uiStore.locale) }}</h2>
            <p class="tab-description">{{ t('booking.calendarSettingsDesc', uiStore.locale) }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="settings-form">
            <div class="form-group">
                <label>{{ t('booking.firstDayOfWeekLabel', uiStore.locale) }}</label>
                <select v-model="formData.first_day_of_week" class="form-control">
                    <option :value="0">{{ t('booking.daysOfWeek.sunday', uiStore.locale) }}</option>
                    <option :value="1">{{ t('booking.daysOfWeek.monday', uiStore.locale) }}</option>
                    <option :value="2">{{ t('booking.daysOfWeek.tuesday', uiStore.locale) }}</option>
                    <option :value="3">{{ t('booking.daysOfWeek.wednesday', uiStore.locale) }}</option>
                    <option :value="4">{{ t('booking.daysOfWeek.thursday', uiStore.locale) }}</option>
                    <option :value="5">{{ t('booking.daysOfWeek.friday', uiStore.locale) }}</option>
                    <option :value="6">{{ t('booking.daysOfWeek.saturday', uiStore.locale) }}</option>
                </select>
            </div>

            <div class="form-group">
                <label>{{ t('booking.timeFormatLabel', uiStore.locale) }}</label>
                <select v-model="formData.time_format" class="form-control">
                    <option value="12">{{ t('booking.timeFormat12', uiStore.locale) }}</option>
                    <option value="24">{{ t('booking.timeFormat24', uiStore.locale) }}</option>
                </select>
            </div>

            <div class="form-group">
                <label>{{ t('booking.dateFormatLabel', uiStore.locale) }}</label>
                <input
                    type="text"
                    v-model="formData.date_format"
                    class="form-control"
                    placeholder="Y-m-d"
                />
                <small class="form-text">{{ uiStore.locale === 'ar' ? 'مثال: Y-m-d (2025-12-09) أو d/m/Y (09/12/2025)' : 'Example: Y-m-d (2025-12-09) or d/m/Y (09/12/2025)' }}</small>
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
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['save']);

const formData = ref({
    first_day_of_week: 1,
    time_format: '12',
    date_format: 'Y-m-d',
});

const saving = ref(false);

watch(() => props.settings, (newSettings) => {
    if (newSettings && Object.keys(newSettings).length > 0) {
        formData.value = { ...formData.value, ...newSettings };
    }
}, { immediate: true, deep: true });

const handleSubmit = async () => {
    saving.value = true;
    try {
        await emit('save', 'calendar', formData.value);
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
@import './SettingsTabStyles.css';
</style>

