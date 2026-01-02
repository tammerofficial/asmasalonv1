<template>
  <div class="apple-wallet-simulator-page">
    <PageHeader 
      :title="t('appleWallet.simulator.title') || 'Apple Wallet Simulator'"
      :subtitle="t('appleWallet.simulator.subtitle') || 'Design and customize your Apple Wallet passes'"
    >
      <template #icon>
        <CIcon icon="cil-wallet" />
      </template>
      <template #actions>
        <CButton color="primary" @click="saveTemplate" :disabled="saving">
          <CSpinner v-if="saving" size="sm" class="me-2" />
          <CIcon v-else icon="cil-save" class="me-2" />
          {{ t('common.save') || 'Save Design' }}
        </CButton>
      </template>
    </PageHeader>

    <CRow class="g-4">
      <!-- Simulator Column -->
      <CCol :lg="5">
        <div class="simulator-sticky">
          <div class="iphone-mockup">
            <div class="wallet-pass" :style="{ backgroundColor: template.backgroundColor }">
              <!-- Pass Top -->
              <div class="pass-top">
                <div class="logo-area">
                  <div class="logo-placeholder">
                    <CIcon icon="cil-factory" size="xl" :style="{ color: template.labelColor }" />
                  </div>
                  <span class="logo-text" :style="{ color: template.foregroundColor }">{{ template.logoText }}</span>
                </div>
                <div class="header-fields">
                  <div v-for="field in template.headerFields" :key="field.key" class="header-field">
                    <div class="field-label" :style="{ color: template.labelColor }">{{ field.label }}</div>
                    <div class="field-value" :style="{ color: template.foregroundColor }">{{ field.value }}</div>
                  </div>
                </div>
              </div>

              <!-- Pass Body -->
              <div class="pass-body">
                <div class="primary-fields">
                  <div v-for="field in template.primaryFields" :key="field.key" class="primary-field">
                    <div class="field-label" :style="{ color: template.labelColor }">{{ field.label }}</div>
                    <div class="field-value" :style="{ color: template.foregroundColor }">{{ field.value }}</div>
                  </div>
                </div>

                <div class="secondary-fields">
                  <div v-for="field in template.secondaryFields" :key="field.key" class="secondary-field">
                    <div class="field-label" :style="{ color: template.labelColor }">{{ field.label }}</div>
                    <div class="field-value" :style="{ color: template.foregroundColor }">{{ field.value }}</div>
                  </div>
                </div>

                <!-- QR Code Section -->
                <div class="qr-section">
                  <div class="qr-placeholder">
                    <CIcon icon="cil-qr-code" size="4xl" :style="{ color: template.foregroundColor }" />
                  </div>
                  <div class="serial-text" :style="{ color: template.labelColor }">LOYALTY-12345-67890</div>
                </div>
              </div>
            </div>
            
            <!-- Side Controls (Flip to back) -->
            <div class="pass-controls mt-3 d-flex justify-content-center">
              <CButtonGroup>
                <CButton :color="view === 'front' ? 'primary' : 'outline-primary'" @click="view = 'front'">الواجهة</CButton>
                <CButton :color="view === 'back' ? 'primary' : 'outline-primary'" @click="view = 'back'">الخلفية</CButton>
              </CButtonGroup>
            </div>
            
            <!-- Back View Modal/Div (Simplified) -->
            <div v-if="view === 'back'" class="wallet-pass-back mt-3 p-3 bg-white rounded shadow-sm border">
              <h6 class="border-bottom pb-2 mb-2">معلومات خلف البطاقة</h6>
              <div v-for="field in template.backFields" :key="field.key" class="mb-3">
                <label class="fw-bold d-block mb-1">{{ field.label }}</label>
                <p class="small text-muted mb-0">{{ field.value }}</p>
              </div>
            </div>
          </div>
        </div>
      </CCol>

      <!-- Editor Column -->
      <CCol :lg="7">
        <CCard class="mb-4">
          <CCardHeader>
            <strong>ألوان البطاقة</strong>
          </CCardHeader>
          <CCardBody>
            <CRow class="g-3">
              <CCol :md="4">
                <label class="form-label">لون الخلفية</label>
                <CFormInput type="color" v-model="template.backgroundColor" />
              </CCol>
              <CCol :md="4">
                <label class="form-label">لون النصوص</label>
                <CFormInput type="color" v-model="template.foregroundColor" />
              </CCol>
              <CCol :md="4">
                <label class="form-label">لون العناوين</label>
                <CFormInput type="color" v-model="template.labelColor" />
              </CCol>
            </CRow>
          </CCardBody>
        </CCard>

        <CCard class="mb-4">
          <CCardHeader>
            <strong>النصوص الأساسية</strong>
          </CCardHeader>
          <CCardBody>
            <div class="mb-3">
              <label class="form-label">إسم الصالون (Logo Text)</label>
              <CFormInput v-model="template.logoText" />
            </div>
            
            <h6 class="mt-4 border-bottom pb-2">الحقول العلوية (Header)</h6>
            <div v-for="(field, index) in template.headerFields" :key="index" class="mb-3 p-3 border rounded bg-light">
              <CRow class="g-3">
                <CCol :md="6">
                  <label class="form-label">العنوان</label>
                  <CFormInput v-model="field.label" />
                </CCol>
                <CCol :md="6">
                  <label class="form-label">القيمة الافتراضية</label>
                  <CFormInput v-model="field.value" />
                </CCol>
              </CRow>
            </div>

            <h6 class="mt-4 border-bottom pb-2">الحقول الأساسية (Primary)</h6>
            <div v-for="(field, index) in template.primaryFields" :key="index" class="mb-3 p-3 border rounded bg-light">
              <CRow class="g-3">
                <CCol :md="6">
                  <label class="form-label">العنوان</label>
                  <CFormInput v-model="field.label" />
                </CCol>
                <CCol :md="6">
                  <label class="form-label">القيمة الافتراضية</label>
                  <CFormInput v-model="field.value" />
                </CCol>
              </CRow>
            </div>

            <h6 class="mt-4 border-bottom pb-2">الحقول الثانوية (Secondary)</h6>
            <div v-for="(field, index) in template.secondaryFields" :key="index" class="mb-3 p-3 border rounded bg-light">
              <CRow class="g-3">
                <CCol :md="6">
                  <label class="form-label">العنوان</label>
                  <CFormInput v-model="field.label" />
                </CCol>
                <CCol :md="6">
                  <label class="form-label">القيمة الافتراضية</label>
                  <CFormInput v-model="field.value" />
                </CCol>
              </CRow>
            </div>

            <h6 class="mt-4 border-bottom pb-2">المعلومات الخلفية (Back Fields)</h6>
            <div v-for="(field, index) in template.backFields" :key="index" class="mb-3 p-3 border rounded bg-light">
              <CRow class="g-3">
                <CCol :md="12">
                  <label class="form-label">العنوان</label>
                  <CFormInput v-model="field.label" />
                </CCol>
                <CCol :md="12">
                  <label class="form-label">النص</label>
                  <CFormTextarea v-model="field.value" rows="3" />
                </CCol>
              </CRow>
            </div>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import api from '@/utils/api';
import PageHeader from '@/components/UI/PageHeader.vue';

const { t } = useTranslation();
const toast = useToast();

const loading = ref(true);
const saving = ref(false);
const view = ref('front');
const templateType = ref('loyalty');

const template = ref({
  backgroundColor: '#ffffff',
  foregroundColor: '#000000',
  labelColor: '#646464',
  logoText: 'Asmaa Salon',
  headerFields: [],
  primaryFields: [],
  secondaryFields: [],
  backFields: []
});

const loadTemplate = async () => {
  try {
    loading.value = true;
    const response = await api.get(`/apple-wallet/templates/${templateType.value}`);
    if (response.data?.success) {
      template.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading template:', error);
    toast.error('حدث خطأ في تحميل القالب');
  } finally {
    loading.value = false;
  }
};

const saveTemplate = async () => {
  try {
    saving.value = true;
    const response = await api.post(`/apple-wallet/templates/${templateType.value}`, template.value);
    if (response.data?.success) {
      toast.success('تم حفظ التصميم بنجاح');
      // In a real scenario, this would trigger background updates for existing passes
    }
  } catch (error) {
    console.error('Error saving template:', error);
    toast.error('حدث خطأ أثناء حفظ التصميم');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadTemplate();
});
</script>

<style scoped>
.apple-wallet-simulator-page {
  padding: var(--spacing-lg);
  font-family: var(--font-family-body);
}

.iphone-mockup {
  width: 100%;
  max-width: 380px;
  margin: 0 auto;
  background: #f0f0f0;
  padding: 60px 20px;
  border-radius: 40px;
  border: 12px solid #333;
  position: relative;
  box-shadow: var(--shadow-lg);
}

.iphone-mockup::before {
  content: '';
  position: absolute;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  width: 150px;
  height: 25px;
  background: #333;
  border-radius: 20px;
}

.wallet-pass {
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
}

.pass-top {
  padding: 15px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px dashed rgba(0,0,0,0.1);
}

.logo-area {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo-text {
  font-weight: bold;
  font-size: 1.1rem;
}

.header-fields {
  text-align: right;
}

.field-label {
  font-size: 0.65rem;
  text-transform: uppercase;
  margin-bottom: 2px;
}

.field-value {
  font-weight: bold;
  font-size: 0.9rem;
}

.pass-body {
  padding: 20px 15px;
  flex-grow: 1;
}

.primary-fields {
  margin-bottom: 25px;
}

.primary-field .field-value {
  font-size: 1.8rem;
  line-height: 1;
}

.secondary-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-bottom: 30px;
}

.qr-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: auto;
  padding: 20px;
  background: rgba(255,255,255,0.8);
  border-radius: 8px;
}

.serial-text {
  font-family: monospace;
  font-size: 0.7rem;
  margin-top: 10px;
}

.simulator-sticky {
  position: sticky;
  top: 20px;
}

.pass-controls {
  margin-top: 20px;
}

:deep(.card) {
  border-radius: 20px;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  box-shadow: var(--shadow-sm);
}

:deep(.card-header) {
  background: var(--bg-tertiary);
  border-bottom: 1px solid var(--border-color);
  font-weight: 800;
  padding: 1.25rem;
}

:deep(.form-control) {
  border-radius: var(--border-radius-md);
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
  color: var(--text-primary);
}

:deep(.form-control:focus) {
  border-color: var(--asmaa-primary);
  box-shadow: 0 0 0 3px var(--asmaa-primary-soft);
}
</style>

