<template>
    <div class="settings-tab">
        <div class="tab-header">
            <h2>{{ t('booking.paymentsSettings', uiStore.locale) }}</h2>
            <p class="tab-description">{{ t('booking.paymentsSettingsDesc', uiStore.locale) }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="settings-form">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" v-model="formData.enabled" class="form-checkbox" />
                    <span>{{ t('booking.enablePaymentsLabel', uiStore.locale) }}</span>
                </label>
            </div>

            <div class="form-group">
                <label>{{ t('booking.paymentGatewaysLabel', uiStore.locale) }}</label>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.gateways" value="local" />
                        <span>{{ uiStore.locale === 'ar' ? 'محلي' : 'Local' }}</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.gateways" value="stripe" />
                        <span>Stripe</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.gateways" value="paypal" />
                        <span>PayPal</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>{{ t('booking.currencyLabel', uiStore.locale) }}</label>
                <select v-model="formData.currency" class="form-control">
                    <option value="USD">USD - {{ uiStore.locale === 'ar' ? 'دولار أمريكي' : 'US Dollar' }}</option>
                    <option value="EUR">EUR - {{ uiStore.locale === 'ar' ? 'يورو' : 'Euro' }}</option>
                    <option value="GBP">GBP - {{ uiStore.locale === 'ar' ? 'جنيه إسترليني' : 'British Pound' }}</option>
                    <option value="KWD">KWD - {{ uiStore.locale === 'ar' ? 'دينار كويتي' : 'Kuwaiti Dinar' }}</option>
                    <option value="SAR">SAR - {{ uiStore.locale === 'ar' ? 'ريال سعودي' : 'Saudi Riyal' }}</option>
                    <option value="AED">AED - {{ uiStore.locale === 'ar' ? 'درهم إماراتي' : 'UAE Dirham' }}</option>
                </select>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" v-model="formData.payment_required" class="form-checkbox" />
                    <span>{{ t('booking.paymentRequiredLabel', uiStore.locale) }}</span>
                </label>
                <small class="form-text">{{ uiStore.locale === 'ar' ? 'يتطلب الدفع لإكمال الحجز' : 'Payment required to complete booking' }}</small>
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
    enabled: false,
    gateways: [],
    currency: 'USD',
    payment_required: false,
});

const saving = ref(false);

watch(() => props.settings, (newSettings) => {
    if (newSettings && Object.keys(newSettings).length > 0) {
        formData.value = {
            enabled: newSettings.enabled || false,
            gateways: Array.isArray(newSettings.gateways) ? [...newSettings.gateways] : [],
            currency: newSettings.currency || 'USD',
            payment_required: newSettings.payment_required || false,
        };
    }
}, { immediate: true, deep: true });

const handleSubmit = async () => {
    saving.value = true;
    try {
        await emit('save', 'payments', formData.value);
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
@import './SettingsTabStyles.css';

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
</style>

