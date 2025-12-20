<template>
    <div class="settings-tab">
        <div class="tab-header">
            <h2>{{ t('booking.companyInfo', uiStore.locale) }}</h2>
            <p class="tab-description">{{ t('booking.companyInfoDesc', uiStore.locale) }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="settings-form">
            <div class="form-group">
                <label>{{ t('booking.companyNameLabel', uiStore.locale) }}</label>
                <input
                    type="text"
                    v-model="formData.name"
                    class="form-control"
                    :placeholder="t('booking.companyNameLabel', uiStore.locale)"
                />
            </div>

            <div class="form-group">
                <label>{{ t('booking.addressLabel', uiStore.locale) }}</label>
                <textarea
                    v-model="formData.address"
                    class="form-control"
                    rows="3"
                    :placeholder="t('booking.addressLabel', uiStore.locale)"
                ></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ t('booking.phoneLabel', uiStore.locale) }}</label>
                    <input
                        type="tel"
                        v-model="formData.phone"
                        class="form-control"
                        placeholder="+965 12345678"
                    />
                </div>

                <div class="form-group">
                    <label>{{ t('booking.emailLabel', uiStore.locale) }}</label>
                    <input
                        type="email"
                        v-model="formData.email"
                        class="form-control"
                        :class="{ 'is-invalid': errors.email }"
                        placeholder="info@example.com"
                    />
                    <small v-if="errors.email" class="form-error">{{ errors.email }}</small>
                </div>
            </div>

            <div class="form-group">
                <label>{{ t('booking.websiteLabel', uiStore.locale) }}</label>
                <input
                    type="url"
                    v-model="formData.website"
                    class="form-control"
                    :class="{ 'is-invalid': errors.website }"
                    placeholder="https://example.com"
                />
                <small v-if="errors.website" class="form-error">{{ errors.website }}</small>
            </div>

            <div class="form-group">
                <label>{{ t('booking.logoLabel', uiStore.locale) }}</label>
                <input
                    type="number"
                    v-model.number="formData.logo"
                    class="form-control"
                    :placeholder="uiStore.locale === 'ar' ? 'معرف الصورة' : 'Image ID'"
                />
                <small class="form-text">{{ uiStore.locale === 'ar' ? 'أدخل معرف الصورة من مكتبة الوسائط' : 'Enter image ID from media library' }}</small>
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
    name: '',
    address: '',
    phone: '',
    email: '',
    website: '',
    logo: '',
});

const saving = ref(false);
const errors = ref({});

watch(() => props.settings, (newSettings) => {
    if (newSettings && Object.keys(newSettings).length > 0) {
        formData.value = { ...formData.value, ...newSettings };
    }
}, { immediate: true, deep: true });

const validateForm = () => {
    errors.value = {};
    
    if (formData.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
        errors.value.email = uiStore.locale === 'ar' ? 'البريد الإلكتروني غير صالح' : 'Invalid email address';
    }
    
    if (formData.value.website && !/^https?:\/\/.+/.test(formData.value.website)) {
        errors.value.website = uiStore.locale === 'ar' ? 'الرابط يجب أن يبدأ بـ http:// أو https://' : 'URL must start with http:// or https://';
    }
    
    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validateForm()) {
        return;
    }
    
    saving.value = true;
    try {
        await emit('save', 'company', formData.value);
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

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}
</style>

