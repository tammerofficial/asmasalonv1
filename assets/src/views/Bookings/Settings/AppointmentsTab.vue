<template>
    <div class="settings-tab">
        <div class="tab-header">
            <h2>{{ t('booking.appointmentsSettings', uiStore.locale) }}</h2>
            <p class="tab-description">{{ t('booking.appointmentsSettingsDesc', uiStore.locale) }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="settings-form">
            <div class="form-group">
                <label>{{ t('booking.defaultStatusLabel', uiStore.locale) }}</label>
                <select v-model="formData.default_status" class="form-control">
                    <option value="pending">{{ t('booking.pending', uiStore.locale) }}</option>
                    <option value="approved">{{ t('booking.approved', uiStore.locale) }}</option>
                    <option value="cancelled">{{ t('booking.cancelled', uiStore.locale) }}</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ t('booking.minCapacityLabel', uiStore.locale) }}</label>
                    <input
                        type="number"
                        v-model.number="formData.min_capacity"
                        class="form-control"
                        min="1"
                    />
                </div>

                <div class="form-group">
                    <label>{{ t('booking.maxCapacityLabel', uiStore.locale) }}</label>
                    <input
                        type="number"
                        v-model.number="formData.max_capacity"
                        class="form-control"
                        min="1"
                    />
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ uiStore.locale === 'ar' ? 'وقت الحشو قبل الموعد (بالدقائق)' : 'Padding before appointment (minutes)' }}</label>
                    <input
                        type="number"
                        v-model.number="formData.padding_before"
                        class="form-control"
                        min="0"
                    />
                </div>

                <div class="form-group">
                    <label>{{ uiStore.locale === 'ar' ? 'وقت الحشو بعد الموعد (بالدقائق)' : 'Padding after appointment (minutes)' }}</label>
                    <input
                        type="number"
                        v-model.number="formData.padding_after"
                        class="form-control"
                        min="0"
                    />
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
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['save']);

const formData = ref({
    default_status: 'pending',
    min_capacity: 1,
    max_capacity: 1,
    padding_before: 0,
    padding_after: 0,
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
        await emit('save', 'appointments', formData.value);
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
@import './SettingsTabStyles.css';

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
</style>

