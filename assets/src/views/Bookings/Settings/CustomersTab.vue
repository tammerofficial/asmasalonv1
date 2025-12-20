<template>
    <div class="settings-tab">
        <div class="tab-header">
            <h2>{{ t('booking.customersSettings', uiStore.locale) }}</h2>
            <p class="tab-description">{{ t('booking.customersSettingsDesc', uiStore.locale) }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="settings-form">
            <div class="form-section">
                <h3 class="section-title">{{ t('booking.requiredFieldsLabel', uiStore.locale) }}</h3>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.required_fields" value="name" />
                        <span>{{ t('booking.name', uiStore.locale) }}</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.required_fields" value="email" />
                        <span>{{ t('booking.email', uiStore.locale) }}</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.required_fields" value="phone" />
                        <span>{{ t('booking.phone', uiStore.locale) }}</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">{{ t('booking.showFieldsLabel', uiStore.locale) }}</h3>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.show_fields" value="name" />
                        <span>{{ t('booking.name', uiStore.locale) }}</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.show_fields" value="email" />
                        <span>{{ t('booking.email', uiStore.locale) }}</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.show_fields" value="phone" />
                        <span>{{ t('booking.phone', uiStore.locale) }}</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.show_fields" value="notes" />
                        <span>{{ uiStore.locale === 'ar' ? 'ملاحظات' : 'Notes' }}</span>
                    </label>
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
    required_fields: [],
    show_fields: [],
    custom_fields: [],
});

const saving = ref(false);

watch(() => props.settings, (newSettings) => {
    if (newSettings && Object.keys(newSettings).length > 0) {
        formData.value = {
            required_fields: Array.isArray(newSettings.required_fields) ? [...newSettings.required_fields] : [],
            show_fields: Array.isArray(newSettings.show_fields) ? [...newSettings.show_fields] : [],
            custom_fields: Array.isArray(newSettings.custom_fields) ? [...newSettings.custom_fields] : [],
        };
    }
}, { immediate: true, deep: true });

const handleSubmit = async () => {
    saving.value = true;
    try {
        await emit('save', 'customers', formData.value);
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

