<template>
    <div class="settings-tab">
        <div class="tab-header">
            <h2>{{ t('booking.urlSettings', uiStore.locale) }}</h2>
            <p class="tab-description">{{ t('booking.urlSettingsDesc', uiStore.locale) }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="settings-form">
            <div class="form-group">
                <label>{{ t('booking.bookingUrlLabel', uiStore.locale) }}</label>
                <input
                    type="text"
                    v-model="formData.booking_url"
                    class="form-control"
                    placeholder="/booking"
                />
                <small class="form-text">{{ t('booking.bookingUrlHint', uiStore.locale) }}</small>
            </div>

            <div class="form-group">
                <label>{{ t('booking.redirectAfterBookingLabel', uiStore.locale) }}</label>
                <input
                    type="text"
                    v-model="formData.redirect_after_booking"
                    class="form-control"
                    placeholder="/thank-you"
                />
                <small class="form-text">{{ t('booking.redirectAfterBookingHint', uiStore.locale) }}</small>
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
    booking_url: '',
    redirect_after_booking: '',
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
        await emit('save', 'url', formData.value);
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
@import './SettingsTabStyles.css';
</style>

