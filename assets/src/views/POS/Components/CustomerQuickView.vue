<template>
  <div class="customer-quick-view" v-if="customer">
    <div class="customer-header">
      <div class="customer-avatar">
        <CIcon icon="cil-user" />
      </div>
      <div class="customer-main-info">
        <div class="customer-name">{{ customer.name || customer.customer_name }}</div>
        <div class="customer-phone">{{ customer.phone || customer.customer_phone }}</div>
      </div>
      <div class="customer-actions">
        <CButton size="sm" color="info" variant="ghost" class="me-1" @click="$emit('view-history', customer.id)">
          <CIcon icon="cil-history" />
        </CButton>
        <CButton size="sm" color="primary" variant="ghost" @click="$emit('view-profile', customer.id)">
          <CIcon icon="cil-external-link" />
        </CButton>
      </div>
    </div>

    <div class="customer-stats">
      <div class="stat-box loyalty">
        <div class="stat-icon"><CIcon icon="cil-star" /></div>
        <div class="stat-info">
          <div class="stat-label">{{ t('loyalty.points') }}</div>
          <div class="stat-value">{{ loyaltyPoints }}</div>
        </div>
      </div>
      <div class="stat-box visits">
        <div class="stat-icon"><CIcon icon="cil-calendar-check" /></div>
        <div class="stat-info">
          <div class="stat-label">{{ t('customers.totalVisits') || 'الزيارات' }}</div>
          <div class="stat-value">{{ totalVisits }}</div>
        </div>
      </div>
      <div class="stat-box spent">
        <div class="stat-icon"><CIcon icon="cil-money" /></div>
        <div class="stat-info">
          <div class="stat-label">{{ t('customers.totalSpent') || 'الإنفاق' }}</div>
          <div class="stat-value text-nowrap">{{ formatCurrency(totalSpent) }}</div>
        </div>
      </div>
    </div>

    <div class="customer-badges" v-if="hasActiveMembership || customer.apple_wallet_status || isBirthday">
      <CBadge v-if="isBirthday" color="danger" shape="rounded-pill" class="pulse-birthday me-1">
        <CIcon icon="cil-birthday-cake" class="me-1" />
        {{ t('customers.birthdayToday') || 'يوم ميلاد سعيد! 🎉' }}
      </CBadge>
      <CBadge v-if="hasActiveMembership" color="warning" shape="rounded-pill" class="me-1">
        <CIcon icon="cil-gem" class="me-1" />
        {{ t('memberships.active') || 'عضوية نشطة' }}
      </CBadge>
      <CBadge v-if="customer.apple_wallet_status === 'active'" color="success" shape="rounded-pill">
        <CIcon icon="cil-wallet" class="me-1" />
        {{ t('loyalty.walletLinked') || 'محفظة نشطة' }}
      </CBadge>
      <CButton 
        v-else-if="customer"
        size="sm" 
        color="info" 
        variant="outline" 
        class="badge-btn ms-auto"
        @click="$emit('send-wallet-pass', customer.id)"
      >
        <CIcon icon="cil-send" class="me-1" />
        {{ t('loyalty.sendWalletPass') || 'إرسال بطاقة المحفظة' }}
      </CButton>
    </div>

    <!-- Advanced Receptionist Features: Preference Tags -->
    <div class="customer-preferences mt-2 d-flex flex-wrap gap-1" v-if="preferenceTags.length > 0">
      <CBadge v-for="tag in preferenceTags" :key="tag" color="light" variant="outline" shape="rounded-pill" class="preference-tag">
        {{ tag }}
      </CBadge>
    </div>

    <!-- Advanced Receptionist Features: Alerts & Last Visit -->
    <div class="customer-alerts mt-3" v-if="customerAlerts && customerAlerts.length > 0">
      <div v-for="(alert, index) in customerAlerts" :key="index" class="alert-item">
        <CIcon icon="cil-warning" class="me-2 text-danger" />
        {{ alert }}
      </div>
    </div>

    <div class="last-visit-box mt-3" v-if="lastVisit && lastVisit.date">
      <div class="last-visit-header">
        <span class="last-visit-label">{{ t('customers.lastVisit') || 'آخر زيارة' }}</span>
        <span class="last-visit-date">{{ formatDate(lastVisit.date) }}</span>
      </div>
      <div class="last-visit-content">
        <div class="last-visit-services">
          {{ lastVisit.services.join(' + ') }}
        </div>
        <div class="last-visit-footer">
          <span class="staff-name"><CIcon icon="cil-user" class="me-1" /> {{ lastVisit.staff_name }}</span>
          <span class="total-spent">{{ formatCurrency(lastVisit.total) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { CIcon } from '@coreui/icons-vue';
import { CButton, CBadge } from '@coreui/vue';

const { t } = useTranslation();

const props = defineProps({
  customer: {
    type: Object,
    default: null
  },
  customerAlerts: {
    type: Array,
    default: () => []
  },
  lastVisit: {
    type: Object,
    default: () => ({})
  }
});

const loyaltyPoints = computed(() => props.customer?.loyalty_points || 0);
const totalVisits = computed(() => props.customer?.total_visits || 0);
const totalSpent = computed(() => props.customer?.total_spent || 0);
const hasActiveMembership = computed(() => props.customer?.has_active_membership);

const isBirthday = computed(() => {
  if (!props.customer?.date_of_birth) return false;
  const today = new Date();
  const birthDate = new Date(props.customer.date_of_birth);
  return today.getDate() === birthDate.getDate() && today.getMonth() === birthDate.getMonth();
});

const preferenceTags = computed(() => {
  if (!props.customer?.preferences) return [];
  try {
    const prefs = props.customer.preferences;
    if (typeof prefs === 'string') {
      return prefs.split(',').map(t => t.trim()).filter(Boolean);
    }
    return Array.isArray(prefs) ? prefs : [];
  } catch (e) {
    return [];
  }
});

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('ar-KW', { day: 'numeric', month: 'short' });
}
</script>

<style scoped>
.customer-quick-view {
  background: var(--bg-tertiary);
  border-radius: 16px;
  padding: 1.25rem;
  border: 1px solid var(--border-color);
  margin-bottom: 1rem;
}

.customer-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.customer-avatar {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: var(--asmaa-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  box-shadow: 0 4px 12px rgba(142, 126, 120, 0.3);
}

.customer-main-info {
  flex: 1;
}

.customer-name {
  font-weight: 700;
  font-size: 1.125rem;
  color: var(--text-primary);
}

.customer-phone {
  font-size: 0.875rem;
  color: var(--text-muted);
}

.customer-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.stat-box {
  padding: 0.75rem;
  background: var(--bg-secondary);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  border: 1px solid var(--border-color);
}

.stat-icon {
  font-size: 1rem;
  margin-bottom: 0.25rem;
}

.loyalty .stat-icon { color: var(--asmaa-warning); }
.visits .stat-icon { color: var(--asmaa-info); }
.spent .stat-icon { color: var(--asmaa-success); }

.stat-label {
  font-size: 0.6875rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--text-primary);
}

.customer-badges {
  margin-top: 1rem;
  display: flex;
  gap: 0.5rem;
}

.customer-alerts {
  background: var(--asmaa-danger-soft);
  padding: 0.75rem;
  border-radius: 10px;
  border: 1px solid var(--asmaa-danger-soft-border);
}

.alert-item {
  color: var(--asmaa-danger);
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
}

.last-visit-box {
  background: var(--bg-secondary);
  padding: 0.75rem;
  border-radius: 10px;
  border: 1px solid var(--border-color);
}

.last-visit-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.last-visit-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 600;
}

.last-visit-date {
  font-size: 0.75rem;
  color: var(--asmaa-primary);
  font-weight: 700;
}

.last-visit-services {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.last-visit-footer {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: var(--text-muted);
}

.total-spent {
  color: var(--asmaa-success);
  font-weight: 700;
}

.preference-tag {
  font-size: 0.65rem;
  font-weight: 600;
  color: var(--text-secondary);
  border-color: var(--border-color);
}

.pulse-birthday {
  animation: birthday-glow 1.5s infinite alternate;
}

@keyframes birthday-glow {
  from { box-shadow: 0 0 5px var(--asmaa-danger); transform: scale(1); }
  to { box-shadow: 0 0 15px var(--asmaa-danger); transform: scale(1.05); }
}
</style>

