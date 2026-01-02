<template>
    <div class="booking-form-preview" :style="previewStyles">
        <!-- Progress Tracker -->
        <div v-if="settings.show_progress_tracker && activeSteps" :class="['progress-tracker', `position-${settings.progress_tracker_position}`]">
            <div
                v-for="(step, index) in activeSteps"
                :key="index"
                :class="['step', { active: currentStep === index, completed: currentStep > index }]"
            >
                <span class="step-number">{{ index + 1 }}</span>
                <span class="step-name">{{ step.name }}</span>
            </div>
        </div>

        <!-- Step Content -->
        <div class="form-content">
            <!-- Service Step -->
            <div v-show="currentStep === 0" class="step-content">
                <h3 class="step-title">{{ t('booking.selectService') }}</h3>
                <div class="service-selection">
                    <div class="category-section" v-if="settings.show_category_info">
                        <label class="form-label">{{ uiStore.locale === 'ar' ? 'الفئة' : 'Category' }}</label>
                        <select class="form-select">
                            <option>{{ uiStore.locale === 'ar' ? 'اختر الفئة' : 'Select category' }}</option>
                            <option>{{ uiStore.locale === 'ar' ? 'فئة 1' : 'Category 1' }}</option>
                            <option>{{ uiStore.locale === 'ar' ? 'فئة 2' : 'Category 2' }}</option>
                        </select>
                    </div>
                    <div class="service-section">
                        <label class="form-label">{{ t('booking.serviceLabel') }}</label>
                        <div class="service-options">
                            <div class="service-option">
                                <input type="radio" name="service" id="service1" />
                                <label for="service1" class="service-label">
                                    <span class="service-name">{{ uiStore.locale === 'ar' ? 'خدمة 1' : 'Service 1' }}</span>
                                    <span v-if="settings.show_service_duration" class="service-duration">30 {{ t('booking.minutesShort') }}</span>
                                    <span v-if="settings.show_service_price" class="service-price">50 {{ t('booking.currencyShort') }}</span>
                                </label>
                            </div>
                            <div class="service-option">
                                <input type="radio" name="service" id="service2" />
                                <label for="service2" class="service-label">
                                    <span class="service-name">{{ uiStore.locale === 'ar' ? 'خدمة 2' : 'Service 2' }}</span>
                                    <span v-if="settings.show_service_duration" class="service-duration">60 {{ t('booking.minutesShort') }}</span>
                                    <span v-if="settings.show_service_price" class="service-price">100 {{ t('booking.currencyShort') }}</span>
                                </label>
                            </div>
                        </div>
                        <div v-if="settings.show_service_info && settings.service_info_text" class="service-info">
                            {{ settings.service_info_text || '{service_info}' }}
                        </div>
                    </div>
                    <div class="employee-section" v-if="settings.show_staff_info">
                        <label class="form-label">
                            {{ uiStore.locale === 'ar' ? 'الموظف' : 'Employee' }}
                            <span v-if="settings.required_employee" class="required">*</span>
                        </label>
                        <select class="form-select" :required="settings.required_employee">
                            <option>{{ uiStore.locale === 'ar' ? 'أي موظف' : 'Any' }}</option>
                            <option>{{ uiStore.locale === 'ar' ? 'موظف 1' : 'Employee 1' }}</option>
                            <option>{{ uiStore.locale === 'ar' ? 'موظف 2' : 'Employee 2' }}</option>
                        </select>
                        <div v-if="settings.staff_info_text" class="staff-info">
                            {{ settings.staff_info_text }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time Step -->
            <div v-show="currentStep === 1" class="step-content">
                <h3 class="step-title">{{ uiStore.locale === 'ar' ? 'اختر الوقت' : 'Select Time' }}</h3>
                <div class="time-selection">
                    <div class="date-picker-preview" :class="{ inverted: settings.invert_datepicker_colors }">
                        <div class="calendar-header">
                            <button class="calendar-nav">‹</button>
                            <span class="calendar-month">{{ uiStore.locale === 'ar' ? 'ديسمبر 2025' : 'December 2025' }}</span>
                            <button class="calendar-nav">›</button>
                        </div>
                        <div class="calendar-grid">
                            <div class="calendar-day" v-for="day in 7" :key="day">
                                <span class="day-label">{{ uiStore.locale === 'ar' ? 'س' : 'S' }}</span>
                            </div>
                            <div class="calendar-day" v-for="date in 28" :key="date">
                                <span :class="['date-number', { active: date === 9 }]">{{ date }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="time-slots">
                        <div class="time-slot">8:00 {{ uiStore.locale === 'ar' ? 'صباحاً' : 'AM' }}</div>
                        <div class="time-slot">9:00 {{ uiStore.locale === 'ar' ? 'صباحاً' : 'AM' }}</div>
                        <div class="time-slot active">10:00 {{ uiStore.locale === 'ar' ? 'صباحاً' : 'AM' }}</div>
                        <div class="time-slot">11:00 {{ uiStore.locale === 'ar' ? 'صباحاً' : 'AM' }}</div>
                    </div>
                </div>
            </div>

            <!-- Details Step -->
            <div v-show="currentStep === 2" class="step-content">
                <h3 class="step-title">{{ t('booking.stepDetails') }}</h3>
                <div class="details-form">
                    <div class="form-group">
                        <label class="form-label">{{ t('booking.name') }} <span class="required">*</span></label>
                        <input type="text" class="form-input" :placeholder="uiStore.locale === 'ar' ? 'أدخل اسمك' : 'Enter your name'" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ t('booking.email') }} <span class="required">*</span></label>
                        <input type="email" class="form-input" placeholder="example@email.com" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ t('booking.phone') }}</label>
                        <input type="tel" class="form-input" placeholder="+965 12345678" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ uiStore.locale === 'ar' ? 'ملاحظات' : 'Notes' }}</label>
                        <textarea class="form-input" rows="3" :placeholder="uiStore.locale === 'ar' ? 'ملاحظات إضافية...' : 'Additional notes...'"></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Step -->
            <div v-show="currentStep === 3" class="step-content">
                <h3 class="step-title">{{ t('booking.stepPayment') }}</h3>
                <div class="payment-summary">
                    <div class="summary-row">
                        <span>{{ t('booking.serviceLabel') }}:</span>
                        <span>{{ uiStore.locale === 'ar' ? 'خدمة 1' : 'Service 1' }}</span>
                    </div>
                    <div class="summary-row">
                        <span>{{ uiStore.locale === 'ar' ? 'المدة' : 'Duration' }}:</span>
                        <span>30 {{ t('booking.minutesShort') }}</span>
                    </div>
                    <div class="summary-row total">
                        <span>{{ uiStore.locale === 'ar' ? 'المجموع' : 'Total' }}:</span>
                        <span>50 {{ t('booking.currencyShort') }}</span>
                    </div>
                </div>
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" name="payment" id="payment1" />
                        <label for="payment1">{{ uiStore.locale === 'ar' ? 'الدفع عند الاستلام' : 'Cash on delivery' }}</label>
                    </div>
                </div>
            </div>

            <!-- Done Step -->
            <div v-show="currentStep === 4" class="step-content">
                <div class="done-message">
                    <svg class="success-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3>{{ uiStore.locale === 'ar' ? 'تم الحجز بنجاح!' : 'Booking successful!' }}</h3>
                    <p>{{ uiStore.locale === 'ar' ? 'شكراً لك. سيتم تأكيد الحجز قريباً.' : 'Thank you. Your booking will be confirmed shortly.' }}</p>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="form-actions" :class="{ 'align-left': settings.align_buttons_left }">
            <button
                v-if="currentStep > 0"
                @click="prevStep"
                class="btn btn-secondary"
                :style="buttonStyles"
            >
                {{ settings.labels?.previous || t('booking.previousButtonPlaceholder') }}
            </button>
            <button
                v-if="activeSteps && currentStep < activeSteps.length - 1"
                @click="nextStep"
                class="btn btn-primary"
                :style="buttonStyles"
            >
                {{ settings.labels?.next || t('booking.nextButtonPlaceholder') }}
            </button>
            <button
                v-if="activeSteps && currentStep === activeSteps.length - 1"
                class="btn btn-primary"
                :style="buttonStyles"
            >
                {{ settings.labels?.save || t('booking.saveButtonPlaceholder') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useUIStore } from '../../stores/ui.js';
import { useTranslation } from '../../composables/useTranslation.js';

const uiStore = useUIStore();
const { t } = useTranslation();

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const currentStep = ref(0);

const activeSteps = computed(() => [
    { name: t('booking.stepService'), id: 'service' },
    { name: t('booking.stepTime'), id: 'time' },
    { name: t('booking.stepDetails'), id: 'details' },
    { name: t('booking.stepPayment'), id: 'payment' },
    { name: t('booking.stepDone'), id: 'done' },
]);

const previewStyles = computed(() => ({
    '--primary-color': props.settings.primary_color || 'var(--asmaa-primary)',
    '--secondary-color': props.settings.secondary_color || 'var(--asmaa-primary-dark)',
    '--text-color': props.settings.text_color || '#1e293b',
    '--background-color': props.settings.background_color || '#ffffff',
    '--button-color': props.settings.button_color || 'var(--asmaa-primary)',
    backgroundColor: props.settings.background_color || '#ffffff',
    color: props.settings.text_color || '#1e293b',
}));

const buttonStyles = computed(() => ({
    backgroundColor: props.settings.button_color || 'var(--asmaa-primary)',
    borderColor: props.settings.button_color || 'var(--asmaa-primary)',
}));

const nextStep = () => {
    const steps = activeSteps.value;
    if (steps && currentStep.value < steps.length - 1) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};
</script>

<style scoped>
.booking-form-preview {
    width: 100%;
    max-width: 500px;
    background: var(--background-color);
    color: var(--text-color);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.progress-tracker {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.progress-tracker.position-bottom {
    order: 2;
    margin-top: 2rem;
    margin-bottom: 0;
    padding-top: 1rem;
    padding-bottom: 0;
    border-top: 2px solid #e2e8f0;
    border-bottom: none;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    opacity: 0.5;
    transition: all 0.3s;
}

.step.active {
    opacity: 1;
}

.step.completed {
    opacity: 0.8;
}

.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s;
}

.step.active .step-number {
    background: var(--primary-color);
    color: white;
}

.step.completed .step-number {
    background: #22c55e;
    color: white;
}

.step-name {
    font-size: 0.75rem;
    font-weight: 500;
    text-align: center;
}

.form-content {
    min-height: 400px;
    margin-bottom: 2rem;
}

.step-content {
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

.step-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 1.5rem;
    color: var(--text-color);
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-color);
    font-size: 0.9375rem;
}

.required {
    color: #ef4444;
}

.form-select,
.form-input {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: all 0.2s;
    background: white;
    color: var(--text-color);
}

.form-select:focus,
.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.service-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.service-option {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.2s;
}

.service-option:hover {
    border-color: var(--primary-color);
}

.service-label {
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
    width: 100%;
}

.service-name {
    flex: 1;
    font-weight: 600;
}

.service-duration,
.service-price {
    font-size: 0.875rem;
    color: #64748b;
}

.service-info,
.staff-info {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 6px;
    font-size: 0.875rem;
    color: #64748b;
}

.date-picker-preview {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.date-picker-preview.inverted {
    background: #1e293b;
    color: white;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.calendar-month {
    font-weight: 600;
}

.calendar-nav {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.25rem;
    color: inherit;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.day-label {
    font-size: 0.75rem;
    font-weight: 600;
    opacity: 0.6;
}

.date-number {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.date-number:hover {
    background: rgba(102, 126, 234, 0.1);
}

.date-number.active {
    background: var(--primary-color);
    color: white;
}

.time-slots {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.time-slot {
    padding: 0.75rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.time-slot:hover {
    border-color: var(--primary-color);
}

.time-slot.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.details-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.payment-summary {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.summary-row.total {
    font-weight: 700;
    font-size: 1.125rem;
    border-bottom: none;
    padding-top: 1rem;
    margin-top: 0.5rem;
    border-top: 2px solid #e2e8f0;
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.payment-method {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
}

.done-message {
    text-align: center;
    padding: 2rem;
}

.success-icon {
    width: 64px;
    height: 64px;
    color: #22c55e;
    margin: 0 auto 1rem;
}

.done-message h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    color: var(--text-color);
}

.done-message p {
    color: #64748b;
    margin: 0;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 1.5rem;
    border-top: 2px solid #e2e8f0;
}

.form-actions.align-left {
    justify-content: flex-start;
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
    background: var(--button-color);
    color: white;
}

.btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e0;
}
</style>

