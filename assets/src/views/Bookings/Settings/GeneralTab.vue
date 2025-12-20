<template>
    <div class="settings-tab">
        <div class="tab-header">
            <h2>{{ t('booking.generalSettings', uiStore.locale) }}</h2>
            <p class="tab-description">{{ t('booking.generalSettingsDesc', uiStore.locale) }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="settings-form">
            <div class="form-section">
                <h3 class="section-title">{{ t('booking.timeSettings', uiStore.locale) }}</h3>

                <div class="form-group">
                    <label>{{ t('booking.timeSlotLengthLabel', uiStore.locale) }}</label>
                    <select v-model="formData.time_slot_length" class="form-control" :class="{ 'is-invalid': errors.time_slot_length }">
                        <option :value="5">5 {{ t('booking.minutes', uiStore.locale) }}</option>
                        <option :value="10">10 {{ t('booking.minutes', uiStore.locale) }}</option>
                        <option :value="15">15 {{ t('booking.minutes', uiStore.locale) }}</option>
                        <option :value="30">30 {{ t('booking.minutes', uiStore.locale) }}</option>
                        <option :value="45">45 {{ t('booking.minutes', uiStore.locale) }}</option>
                        <option :value="60">60 {{ t('booking.minutes', uiStore.locale) }}</option>
                    </select>
                    <small v-if="errors.time_slot_length" class="form-error">{{ errors.time_slot_length }}</small>
                    <small v-else class="form-text">{{ t('booking.timeSlotLengthHint', uiStore.locale) }}</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.slot_as_duration" class="form-checkbox" />
                        <span>{{ t('booking.slotAsDurationLabel', uiStore.locale) }}</span>
                    </label>
                    <small class="form-text">{{ t('booking.slotAsDurationHint', uiStore.locale) }}</small>
                </div>

                <div class="form-group">
                    <label>{{ t('booking.minTimeBookingLabel', uiStore.locale) }}</label>
                    <div class="input-group">
                        <input
                            type="number"
                            v-model.number="formData.min_time_booking"
                            class="form-control"
                            min="0"
                        />
                        <select v-model="formData.min_time_booking_unit" class="form-control" style="width: 120px;">
                            <option value="minutes">{{ t('booking.minutes', uiStore.locale) }}</option>
                            <option value="hours">{{ t('booking.hours', uiStore.locale) }}</option>
                            <option value="days">{{ t('booking.days', uiStore.locale) }}</option>
                        </select>
                    </div>
                    <small class="form-text">{{ t('booking.minTimeBookingHint', uiStore.locale) }}</small>
                </div>

                <div class="form-group">
                    <label>{{ t('booking.minTimeCancelLabel', uiStore.locale) }}</label>
                    <div class="input-group">
                        <input
                            type="number"
                            v-model.number="formData.min_time_cancel"
                            class="form-control"
                            min="0"
                        />
                        <select v-model="formData.min_time_cancel_unit" class="form-control" style="width: 120px;">
                            <option value="minutes">{{ t('booking.minutes', uiStore.locale) }}</option>
                            <option value="hours">{{ t('booking.hours', uiStore.locale) }}</option>
                            <option value="days">{{ t('booking.days', uiStore.locale) }}</option>
                        </select>
                    </div>
                    <small class="form-text">{{ t('booking.minTimeCancelHint', uiStore.locale) }}</small>
                </div>

                <div class="form-group">
                    <label>{{ t('booking.maxDaysBookingLabel', uiStore.locale) }}</label>
                    <input
                        type="number"
                        v-model.number="formData.max_days_booking"
                        class="form-control"
                        :class="{ 'is-invalid': errors.max_days_booking }"
                        min="1"
                        max="365"
                    />
                    <small v-if="errors.max_days_booking" class="form-error">{{ errors.max_days_booking }}</small>
                    <small v-else class="form-text">{{ t('booking.maxDaysBookingHint', uiStore.locale) }}</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.show_timeslots_client_timezone" class="form-checkbox" />
                        <span>{{ t('booking.showTimezoneSlotsLabel', uiStore.locale) }}</span>
                    </label>
                    <small class="form-text">{{ t('booking.showTimezoneSlotsHint', uiStore.locale) }}</small>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">{{ t('booking.staffSettings', uiStore.locale) }}</h3>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.allow_staff_edit_profile" class="form-checkbox" />
                        <span>{{ t('booking.allowStaffEditLabel', uiStore.locale) }}</span>
                    </label>
                    <small class="form-text">إذا تم تفعيل هذا الخيار، سيتمكن جميع الموظفين المرتبطين بمستخدمي WordPress من تعديل ملفاتهم الشخصية وخدماتهم وجدولهم</small>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">{{ t('booking.systemSettings', uiStore.locale) }}</h3>

                <div class="form-group">
                    <label>وضع تخزين الجلسة</label>
                    <select v-model="formData.session_mode" class="form-control">
                        <option value="PHP">PHP</option>
                        <option value="DB">قاعدة البيانات</option>
                    </select>
                    <small class="form-text">اختر مكان تخزين بيانات الجلسة</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.prevent_session_locking" class="form-checkbox" />
                        <span>منع قفل جلسة PHP</span>
                    </label>
                    <small class="form-text">تفعيل هذا الخيار لجعل Booking يغلق جلسة PHP بمجرد الانتهاء منها</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.prevent_caching" class="form-checkbox" />
                        <span>منع التخزين المؤقت للصفحات التي تحتوي على نموذج الحجز</span>
                    </label>
                    <small class="form-text">اختر "مفعّل" إذا كنت تريد أن يمنع Booking التخزين المؤقت بواسطة إضافات التخزين المؤقت</small>
                </div>

                <div class="form-group">
                    <label>بوابة البريد</label>
                    <select v-model="formData.mail_gateway" class="form-control">
                        <option value="wp">WordPress mail</option>
                        <option value="smtp">SMTP</option>
                    </select>
                    <small class="form-text">اختر بوابة البريد التي سيتم استخدامها لإرسال إشعارات البريد الإلكتروني</small>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">{{ t('booking.otherSettings', uiStore.locale) }}</h3>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.show_news_notifications" class="form-checkbox" />
                        <span>عرض إشعارات الأخبار</span>
                    </label>
                    <small class="form-text">إذا تم تفعيله، سيتم عرض أيقونة إشعار الأخبار</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="formData.powered_by_bookly" class="form-checkbox" />
                        <span>مدعوم بواسطة Booking</span>
                    </label>
                    <small class="form-text">السماح للإضافة بوضع إشعار "مدعوم بواسطة Booking" على عنصر واجهة الحجز</small>
                </div>

                <div class="form-group">
                    <label>بيانات Booking عند حذف عناصر Booking</label>
                    <select v-model="formData.data_on_delete" class="form-control">
                        <option value="don't_delete">لا تحذف</option>
                        <option value="delete">احذف</option>
                    </select>
                    <small class="form-text">إذا اخترت "احذف"، سيتم حذف جميع البيانات المرتبطة بـ Booking بشكل دائم عند حذف عناصر Booking</small>
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
import { ref, watch, onMounted } from 'vue';
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
    time_slot_length: 15,
    slot_as_duration: false,
    min_time_booking: 0,
    min_time_booking_unit: 'hours',
    min_time_cancel: 0,
    min_time_cancel_unit: 'hours',
    max_days_booking: 365,
    show_timeslots_client_timezone: false,
    allow_staff_edit_profile: true,
    prevent_caching: true,
    session_mode: 'PHP',
    prevent_session_locking: false,
    show_news_notifications: true,
    mail_gateway: 'wp',
    powered_by_bookly: false,
    data_on_delete: "don't_delete",
});

const saving = ref(false);
const errors = ref({});

watch(() => props.settings, (newSettings) => {
    if (newSettings && Object.keys(newSettings).length > 0) {
        formData.value = {
            ...formData.value,
            ...newSettings,
        };
    }
}, { immediate: true, deep: true });

const validateForm = () => {
    errors.value = {};
    
    if (formData.value.time_slot_length < 5 || formData.value.time_slot_length > 120) {
        errors.value.time_slot_length = 'يجب أن تكون الفترة الزمنية بين 5 و 120 دقيقة';
    }
    
    if (formData.value.max_days_booking < 1 || formData.value.max_days_booking > 365) {
        errors.value.max_days_booking = 'يجب أن يكون عدد الأيام بين 1 و 365';
    }
    
    if (formData.value.min_time_booking < 0) {
        errors.value.min_time_booking = 'يجب أن تكون القيمة 0 أو أكثر';
    }
    
    if (formData.value.min_time_cancel < 0) {
        errors.value.min_time_cancel = 'يجب أن تكون القيمة 0 أو أكثر';
    }
    
    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validateForm()) {
        return;
    }
    
    saving.value = true;
    try {
        await emit('save', 'general', formData.value);
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
.settings-tab {
    animation: fadeIn 0.3s;
}

.tab-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.tab-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem;
}

.tab-description {
    color: #64748b;
    margin: 0;
}

.settings-form {
    max-width: 800px;
}

.form-section {
    margin-bottom: 2.5rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
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
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.form-text {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.875rem;
    color: #64748b;
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
    background: #667eea;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #5568d3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.form-control.is-invalid {
    border-color: #ef4444;
}

.form-control.is-invalid:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-error {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.875rem;
    color: #ef4444;
    font-weight: 500;
}
</style>

