<template>
  <div class="woocommerce-settings-page">
    <PageHeader 
      :title="t('settings.woocommerce.title') || 'WooCommerce Integration'"
      :subtitle="t('settings.woocommerce.subtitle') || 'Manage WooCommerce integration settings'"
    >
      <template #icon>
        <CIcon icon="cil-cart" />
      </template>
    </PageHeader>

    <Card :title="t('settings.woocommerce.status') || 'Integration Status'" icon="cil-info" class="mb-4">
      <CRow class="g-3">
        <CCol :md="12">
          <div class="d-flex align-items-center">
            <CBadge 
              :color="woocommerceActive ? 'success' : 'danger'" 
              class="me-3"
            >
              <CIcon :icon="woocommerceActive ? 'cil-check-circle' : 'cil-x-circle'" class="me-1" />
              {{ woocommerceActive ? (t('settings.woocommerce.active') || 'WooCommerce Active') : (t('settings.woocommerce.inactive') || 'WooCommerce Not Active') }}
            </CBadge>
            <span class="text-muted">
              {{ woocommerceActive 
                ? (t('settings.woocommerce.connected') || 'WooCommerce is installed and active') 
                : (t('settings.woocommerce.notConnected') || 'Please install and activate WooCommerce plugin') }}
            </span>
          </div>
        </CCol>
      </CRow>
    </Card>

    <Card :title="t('settings.woocommerce.info') || 'Information'" icon="cil-info" v-if="woocommerceActive">
      <div class="alert alert-info">
        <CIcon icon="cil-info" class="me-2" />
        <strong>{{ t('settings.woocommerce.note') || 'Note:' }}</strong>
        <ul class="mb-0 mt-2">
          <li>{{ t('settings.woocommerce.note1') || 'Products, Orders, and Customers are managed directly through WooCommerce and WordPress' }}</li>
          <li>{{ t('settings.woocommerce.note2') || 'All data is read directly from WooCommerce - no synchronization needed' }}</li>
        </ul>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import {
  CButton,
  CBadge,
  CRow,
  CCol,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import PageHeader from '@/components/UI/PageHeader.vue';
import Card from '@/components/UI/Card.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();

const woocommerceActive = ref(false);

const loadSettings = async () => {
  try {
    const response = await api.get('/settings/woocommerce');
    if (response.data?.success) {
      woocommerceActive.value = response.data.data?.woocommerce_active || false;
    }
  } catch (error) {
    console.error('Error loading WooCommerce settings:', error);
  }
};

onMounted(() => {
  loadSettings();
});
</script>

<style scoped>
.woocommerce-settings-page {
  padding: var(--spacing-lg);
}

.alert {
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
}

.alert ul {
  padding-left: var(--spacing-lg);
}
</style>

