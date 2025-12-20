<template>
    <div class="settings-tab">
        <div class="tab-header">
            <h2>{{ t('booking.holidaysSettings', uiStore.locale) }}</h2>
            <p class="tab-description">{{ t('booking.holidaysSettingsDesc', uiStore.locale) }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="settings-form">
            <div class="holidays-list">
                <div v-for="(holiday, index) in holidays" :key="index" class="holiday-item">
                    <input
                        type="date"
                        v-model="holiday.date"
                        class="form-control"
                    />
                    <input
                        type="text"
                        v-model="holiday.name"
                        class="form-control"
                        :placeholder="t('booking.holidayNameLabel', uiStore.locale)"
                    />
                    <button
                        type="button"
                        @click="removeHoliday(index)"
                        class="btn btn-danger"
                    >
                        {{ t('booking.delete', uiStore.locale) }}
                    </button>
                </div>
            </div>

            <button
                type="button"
                @click="addHoliday"
                class="btn btn-secondary"
            >
                + {{ t('booking.addHoliday', uiStore.locale) }}
            </button>

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
    holidays: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['save']);

const holidays = ref([]);

const saving = ref(false);

watch(() => props.holidays, (newHolidays) => {
    if (newHolidays && newHolidays.length > 0) {
        holidays.value = newHolidays.map(h => ({ ...h }));
    } else {
        holidays.value = [];
    }
}, { immediate: true, deep: true });

const addHoliday = () => {
    holidays.value.push({
        date: '',
        name: '',
    });
};

const removeHoliday = (index) => {
    holidays.value.splice(index, 1);
};

const handleSubmit = async () => {
    saving.value = true;
    try {
        await emit('save', { holidays: holidays.value });
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
@import './SettingsTabStyles.css';

.holidays-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.holiday-item {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.holiday-item .form-control {
    flex: 1;
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
    margin-bottom: 1.5rem;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}
</style>

