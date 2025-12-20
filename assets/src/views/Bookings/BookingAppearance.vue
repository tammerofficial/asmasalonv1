<template>
    <div class="booking-appearance-page">
        <div class="appearance-header">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                        {{ t('booking.appearanceTitle') }}
                    </h1>
                    <p class="header-subtitle">{{ t('booking.appearanceSubtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="appearance-container">
            <!-- Editor Panel -->
            <div class="editor-panel">
                <div class="panel-header">
                    <h2>{{ t('booking.appearanceSettings') }}</h2>
                </div>

                <div class="editor-content">
                    <div v-if="bookingStore.settings.loading" class="loading-overlay">
                        <div class="spinner"></div>
                        <p>{{ t('booking.loadingSettings') }}</p>
                    </div>

                    <div class="settings-sections" :class="{ 'loading': bookingStore.settings.loading }">
                        <!-- Color Scheme -->
                        <div class="settings-section">
                            <h3 class="section-title">{{ t('booking.colorScheme') }}</h3>
                            <div class="form-group">
                                <label>{{ t('booking.primaryColor') }}</label>
                                <div class="color-input-group">
                                    <input
                                        type="color"
                                        v-model="formData.primary_color"
                                        class="color-picker"
                                    />
                                    <input
                                        type="text"
                                        v-model="formData.primary_color"
                                        class="form-control color-text"
                                    />
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ t('booking.secondaryColor') }}</label>
                                <div class="color-input-group">
                                    <input
                                        type="color"
                                        v-model="formData.secondary_color"
                                        class="color-picker"
                                    />
                                    <input
                                        type="text"
                                        v-model="formData.secondary_color"
                                        class="form-control color-text"
                                    />
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ t('booking.textColor') }}</label>
                                <div class="color-input-group">
                                    <input
                                        type="color"
                                        v-model="formData.text_color"
                                        class="color-picker"
                                    />
                                    <input
                                        type="text"
                                        v-model="formData.text_color"
                                        class="form-control color-text"
                                    />
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ t('booking.backgroundColor') }}</label>
                                <div class="color-input-group">
                                    <input
                                        type="color"
                                        v-model="formData.background_color"
                                        class="color-picker"
                                    />
                                    <input
                                        type="text"
                                        v-model="formData.background_color"
                                        class="form-control color-text"
                                    />
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ t('booking.buttonColor') }}</label>
                                <div class="color-input-group">
                                    <input
                                        type="color"
                                        v-model="formData.button_color"
                                        class="color-picker"
                                    />
                                    <input
                                        type="text"
                                        v-model="formData.button_color"
                                        class="form-control color-text"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Form Options -->
                        <div class="settings-section">
                            <h3 class="section-title">{{ t('booking.formOptions') }}</h3>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="formData.show_progress_tracker" class="form-checkbox" />
                                    <span>{{ t('booking.showProgressTrackerLabel') }}</span>
                                </label>
                            </div>
                            <div v-if="formData.show_progress_tracker" class="form-group">
                                <label>{{ t('booking.progressTrackerPositionLabel') }}</label>
                                <select v-model="formData.progress_tracker_position" class="form-control">
                                    <option value="top">{{ t('booking.positionTop') }}</option>
                                    <option value="bottom">{{ t('booking.positionBottom') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="formData.align_buttons_left" class="form-checkbox" />
                                    <span>{{ t('booking.alignButtonsLeftLabel') }}</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="formData.invert_datepicker_colors" class="form-checkbox" />
                                    <span>{{ t('booking.invertDatepickerColorsLabel') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Service Step -->
                        <div class="settings-section">
                            <h3 class="section-title">{{ t('booking.serviceStepLabel') }}</h3>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="formData.show_category_info" class="form-checkbox" />
                                    <span>{{ t('booking.showCategoryInfoLabel') }}</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="formData.show_service_info" class="form-checkbox" />
                                    <span>{{ t('booking.showServiceInfoLabel') }}</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="formData.show_service_duration" class="form-checkbox" />
                                    <span>{{ t('booking.showServiceDurationLabel') }}</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="formData.show_service_price" class="form-checkbox" />
                                    <span>{{ t('booking.showServicePriceLabel') }}</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="formData.required_employee" class="form-checkbox" />
                                    <span>{{ t('booking.makeEmployeeRequiredLabel') }}</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label>{{ t('booking.customServiceInfoTextLabel') }}</label>
                                <textarea
                                    v-model="formData.service_info_text"
                                    class="form-control"
                                    rows="3"
                                    placeholder="{service_info}"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Labels -->
                        <div class="settings-section">
                            <h3 class="section-title">{{ t('booking.labelsTextLabel') }}</h3>
                            <div class="form-group">
                                <label>{{ t('booking.nextButtonTextLabel') }}</label>
                                <input
                                    type="text"
                                    v-model="formData.labels.next"
                                    class="form-control"
                                    :placeholder="t('booking.nextButtonPlaceholder')"
                                />
                            </div>
                            <div class="form-group">
                                <label>{{ t('booking.previousButtonTextLabel') }}</label>
                                <input
                                    type="text"
                                    v-model="formData.labels.previous"
                                    class="form-control"
                                    :placeholder="t('booking.previousButtonPlaceholder')"
                                />
                            </div>
                            <div class="form-group">
                                <label>{{ t('booking.saveButtonTextLabel') }}</label>
                                <input
                                    type="text"
                                    v-model="formData.labels.save"
                                    class="form-control"
                                    :placeholder="t('booking.saveButtonPlaceholder')"
                                />
                            </div>
                        </div>

                        <!-- Advanced -->
                        <div class="settings-section">
                            <h3 class="section-title">{{ t('booking.advancedLabel') }}</h3>
                            <div class="form-group">
                                <label>{{ t('booking.customCSSLabel') }}</label>
                                <textarea
                                    v-model="formData.custom_css"
                                    class="form-control code-textarea"
                                    rows="6"
                                    :placeholder="t('booking.customCSSPlaceholder')"
                                ></textarea>
                            </div>
                            <div class="form-group">
                                <label>{{ t('booking.customJSLabel') }}</label>
                                <textarea
                                    v-model="formData.custom_js"
                                    class="form-control code-textarea"
                                    rows="6"
                                    :placeholder="t('booking.customJSPlaceholder')"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="form-actions">
                            <button
                                @click="handleSave"
                                class="btn btn-primary"
                                :disabled="bookingStore.settings.saving"
                            >
                                {{ bookingStore.settings.saving ? t('booking.savingSettings') : t('booking.saveSettings') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Panel -->
            <div class="preview-panel">
                <div class="panel-header">
                    <h2>{{ t('booking.livePreview') }}</h2>
                </div>
                <div class="preview-content">
                    <BookingFormPreview :settings="computedSettings" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { CIcon } from '@coreui/icons-vue';
import { useBookingsStore } from '../../stores/bookings.js';
import { useUIStore } from '../../stores/ui.js';
import { useToast } from '../../composables/useToast.js';
import { useTranslation } from '../../composables/useTranslation.js';
import BookingFormPreview from './BookingFormPreview.vue';

const router = useRouter();
const bookingStore = useBookingsStore();
const uiStore = useUIStore();
const toast = useToast();
const { t } = useTranslation();

const goBack = () => {
    router.push('/bookings');
};

const computedSettings = computed(() => formData.value);

const formData = ref({
    primary_color: '#BBA07A',
    secondary_color: '#764ba2',
    text_color: '#1e293b',
    background_color: '#ffffff',
    button_color: '#BBA07A',
    show_progress_tracker: true,
    progress_tracker_position: 'top',
    align_buttons_left: false,
    invert_datepicker_colors: false,
    required_employee: false,
    show_service_duration: true,
    show_service_price: true,
    show_category_info: true,
    show_service_info: true,
    show_staff_info: true,
    service_info_text: '',
    staff_info_text: '',
    labels: {
        next: '',
        previous: '',
        save: '',
    },
    custom_css: '',
    custom_js: '',
});

onMounted(async () => {
    // Set default labels immediately
    formData.value.labels = {
        next: t('booking.nextButtonPlaceholder'),
        previous: t('booking.previousButtonPlaceholder'),
        save: t('booking.saveButtonPlaceholder'),
    };
    
    try {
        await bookingStore.fetchSettings('appearance');
        if (bookingStore.settings.appearance && Object.keys(bookingStore.settings.appearance).length > 0) {
            const settings = bookingStore.settings.appearance;
            formData.value = {
                ...formData.value,
                ...settings,
                labels: settings.labels || formData.value.labels,
            };
        }
    } catch (error) {
        console.error('Failed to load appearance settings:', error);
        // Keep default values - formData already has defaults
    }
});

const handleSave = async () => {
    try {
        await bookingStore.saveSettings('appearance', formData.value);
        const successMsg = uiStore.locale === 'ar' ? '✅ تم حفظ إعدادات المظهر بنجاح' : '✅ Appearance settings saved successfully';
        toast.success(successMsg);
    } catch (error) {
        console.error('Failed to save appearance settings:', error);
        const errorMsg = uiStore.locale === 'ar' 
            ? '❌ فشل حفظ الإعدادات: ' + (error.message || 'خطأ غير معروف')
            : '❌ Failed to save settings: ' + (error.message || 'Unknown error');
        toast.error(errorMsg);
    }
};
</script>

<style scoped>
.booking-appearance-page {
    min-height: 100vh;
    background: #f5f7fa;
    padding: 2rem;
}

.appearance-header {
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

.appearance-header h1 {
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

.appearance-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    max-width: 1600px;
    margin: 0 auto;
}

.editor-panel,
.preview-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.panel-header {
    padding: 1.5rem;
    border-bottom: 2px solid rgba(187, 160, 122, 0.2);
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.05) 0%, rgba(187, 160, 122, 0.02) 100%);
}

.panel-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.editor-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    max-height: calc(100vh - 200px);
    position: relative;
}

.preview-content {
    flex: 1;
    overflow-y: auto;
    padding: 2rem;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
    color: #64748b;
}

.settings-sections {
    position: relative;
}

.settings-sections.loading {
    opacity: 0.6;
    pointer-events: none;
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


.settings-section {
    padding-bottom: 2rem;
    border-bottom: 1px solid #e2e8f0;
}

.settings-section:last-child {
    border-bottom: none;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
    font-size: 0.9375rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.form-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-control {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: all 0.2s;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: var(--asmaa-primary);
    box-shadow: 0 0 0 3px rgba(var(--cui-primary-rgb), 0.15);
}

.color-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.color-picker {
    width: 60px;
    height: 40px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
}

.color-text {
    flex: 1;
    font-family: monospace;
}

.code-textarea {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e2e8f0;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}

.btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(187, 160, 122, 0.4);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 1200px) {
    .appearance-container {
        grid-template-columns: 1fr;
    }

    .preview-panel {
        order: -1;
    }
}
</style>

