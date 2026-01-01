<template>
  <div class="apple-wallet-settings-page">
    <PageHeader 
      :title="t('settings.appleWallet.title') || 'Apple Wallet Settings'"
      :subtitle="t('settings.appleWallet.subtitle') || 'Configure Apple Wallet pass generation'"
    >
      <template #icon>
        <CIcon icon="cil-wallet" />
      </template>
    </PageHeader>

    <LoadingSpinner v-if="loading" :text="t('common.loading')" />

    <div v-else>
      <Card :title="t('settings.appleWallet.configuration') || 'Configuration'" icon="cil-settings" class="mb-4">
        <div class="read-only-settings">
          <CRow class="g-3">
            <CCol :md="6">
              <label class="form-label">
                {{ t('settings.appleWallet.teamId') || 'Apple Team ID' }}
                <span class="text-danger">*</span>
              </label>
              <CFormInput
                :value="settings.team_id"
                :placeholder="t('settings.appleWallet.teamIdPlaceholder') || 'ABC123DEF4'"
                readonly
                disabled
              />
              <small class="text-muted">
                {{ t('settings.appleWallet.teamIdHelp') || 'Your Apple Developer Team ID' }}
              </small>
            </CCol>

            <CCol :md="6">
              <label class="form-label">
                {{ t('settings.appleWallet.passTypeId') || 'Pass Type ID' }}
                <span class="text-danger">*</span>
              </label>
              <CFormInput
                :value="settings.pass_type_id"
                :placeholder="t('settings.appleWallet.passTypeIdPlaceholder') || 'pass.com.asmaasalon.loyalty'"
                readonly
                disabled
              />
              <small class="text-muted">
                {{ t('settings.appleWallet.passTypeIdHelp') || 'Pass Type Identifier from Apple Developer' }}
              </small>
            </CCol>

            <CCol :md="12">
              <label class="form-label">
                {{ t('settings.appleWallet.certificatePath') || 'Certificate File Path' }}
                <span class="text-danger">*</span>
              </label>
              <CFormInput
                v-model="form.certificate_path"
                :placeholder="t('settings.appleWallet.certificatePathPlaceholder') || 'certificate.p12'"
                required
              />
              <small class="text-muted">
                {{ t('settings.appleWallet.certificatePathHelp') || 'Certificate file name (must be uploaded to certs directory)' }}
              </small>
              <div v-if="settings.certificate_exists" class="mt-2">
                <CBadge color="success">
                  <CIcon icon="cil-check-circle" class="me-1" />
                  {{ t('settings.appleWallet.certificateFound') || 'Certificate found' }}
                </CBadge>
              </div>
              <div v-else-if="form.certificate_path" class="mt-2">
                <CBadge color="warning">
                  <CIcon icon="cil-warning" class="me-1" />
                  {{ t('settings.appleWallet.certificateNotFound') || 'Certificate not found' }}
                </CBadge>
              </div>
            </CCol>

            <CCol :md="12">
              <label class="form-label">
                {{ t('settings.appleWallet.certificatePassword') || 'Certificate Password' }}
                <span class="text-danger">*</span>
              </label>
              <CFormInput
                v-model="form.certificate_password"
                type="password"
                :placeholder="t('settings.appleWallet.certificatePasswordPlaceholder') || 'Enter certificate password'"
                required
              />
              <small class="text-muted">
                {{ t('settings.appleWallet.certificatePasswordHelp') || 'Password for the .p12 certificate file' }}
              </small>
            </CCol>

            <CCol :md="12">
              <label class="form-label">
                {{ t('settings.appleWallet.wwdrCertificatePath') || 'WWDR Certificate Path' }}
              </label>
              <CFormInput
                :value="settings?.wwdr_certificate_path"
                :placeholder="t('settings.appleWallet.wwdrCertificatePathPlaceholder') || 'AppleWWDRCAG3.cer'"
                readonly
                disabled
              />
              <small class="text-muted">
                {{ t('settings.appleWallet.wwdrCertificatePathHelp') || 'Apple Worldwide Developer Relations Certificate (optional but recommended)' }}
              </small>
              <div v-if="settings.wwdr_certificate_exists" class="mt-2">
                <CBadge color="success">
                  <CIcon icon="cil-check-circle" class="me-1" />
                  {{ t('settings.appleWallet.wwdrCertificateFound') || 'WWDR Certificate found' }}
                </CBadge>
              </div>
            </CCol>

            <CCol :md="12">
              <CFormCheck
                :checked="settings?.sandbox_mode"
                :label="t('settings.appleWallet.sandboxMode') || 'Use Sandbox Mode (for testing)'"
                disabled
              />
              <small class="text-muted d-block">
                {{ t('settings.appleWallet.sandboxModeHelp') || 'Enable sandbox mode for testing passes before production' }}
              </small>
            </CCol>

            <CCol :md="12">
              <div class="alert alert-info">
                <CIcon icon="cil-info" class="me-2" />
                <strong>{{ t('settings.appleWallet.uploadNote') || 'How to upload certificates:' }}</strong>
                <ol class="mb-0 mt-2">
                  <li>{{ t('settings.appleWallet.uploadNote1') || 'Upload your .p12 certificate file to:' }} <code>{{ settings.certs_directory || '/wp-content/uploads/asmaa-salon/certs/' }}</code></li>
                  <li>{{ t('settings.appleWallet.uploadNote2') || 'Enter only the filename (e.g., certificate.p12) in the Certificate Path field' }}</li>
                  <li>{{ t('settings.appleWallet.uploadNote3') || 'Download WWDR certificate from Apple and upload it to the same directory' }}</li>
                </ol>
              </div>
            </CCol>

            <CCol :md="12">
              <div class="alert alert-warning">
                <CIcon icon="cil-warning" class="me-2" />
                <strong>ملاحظة:</strong> الإعدادات محددة في ملف الثوابت المركزي: <code>includes/Config/Apple_Wallet_Config.php</code>
                <br>
                للتعديل، قم بتحرير الملف مباشرة ثم أعد تحميل الصفحة.
              </div>
            </CCol>
          </CRow>
        </div>
      </Card>

      <Card :title="t('settings.appleWallet.status') || 'Status'" icon="cil-info" v-if="settings">
        <CRow class="g-3">
          <CCol :md="6">
            <div class="d-flex align-items-center">
              <CBadge 
                :color="settings.certificate_exists ? 'success' : 'danger'" 
                class="me-3"
              >
                <CIcon :icon="settings.certificate_exists ? 'cil-check-circle' : 'cil-x-circle'" class="me-1" />
                {{ settings.certificate_exists 
                  ? (t('settings.appleWallet.certificateReady') || 'Certificate Ready') 
                  : (t('settings.appleWallet.certificateMissing') || 'Certificate Missing') }}
              </CBadge>
            </div>
          </CCol>
          <CCol :md="6">
            <div class="d-flex align-items-center">
              <CBadge 
                :color="settings.wwdr_certificate_exists ? 'success' : 'warning'" 
                class="me-3"
              >
                <CIcon :icon="settings.wwdr_certificate_exists ? 'cil-check-circle' : 'cil-warning'" class="me-1" />
                {{ settings.wwdr_certificate_exists 
                  ? (t('settings.appleWallet.wwdrReady') || 'WWDR Ready') 
                  : (t('settings.appleWallet.wwdrMissing') || 'WWDR Missing (Optional)') }}
              </CBadge>
            </div>
          </CCol>
        </CRow>
      </Card>
    </div>
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
  CFormInput,
  CFormCheck,
  CSpinner,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import PageHeader from '@/components/UI/PageHeader.vue';
import Card from '@/components/UI/Card.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();

const loading = ref(true);
const settings = ref(null);

const loadSettings = async () => {
  try {
    loading.value = true;
    const response = await api.get('/settings/apple-wallet');
    if (response.data?.success) {
      settings.value = response.data.data || {};
    }
  } catch (error) {
    console.error('Error loading Apple Wallet settings:', error);
    toast.error(t('common.errorLoading') || 'Error loading settings');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadSettings();
});
</script>

<style scoped>
.apple-wallet-settings-page {
  padding: var(--spacing-lg);
}

.alert {
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
}

.alert ol {
  padding-left: var(--spacing-lg);
}

code {
  background: var(--bg-secondary);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
}
</style>

