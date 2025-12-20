<template>
  <CModal
    :visible="modelValue"
    size="lg"
    @close="$emit('update:modelValue', false)"
  >
    <CModalHeader>
      <CModalTitle>
        {{ user ? t('users.editUser') : t('users.addNew') }}
      </CModalTitle>
    </CModalHeader>
    <CModalBody>
      <CForm @submit.prevent="handleSubmit">
        <div class="row g-3">
          <!-- Username -->
          <div class="col-md-6">
            <CFormLabel for="username">{{ t('users.username') }} *</CFormLabel>
            <CFormInput
              id="username"
              v-model="form.username"
              type="text"
              :placeholder="t('users.usernamePlaceholder')"
              :disabled="!!user"
              required
            />
          </div>

          <!-- Email -->
          <div class="col-md-6">
            <CFormLabel for="email">{{ t('users.email') }} *</CFormLabel>
            <CFormInput
              id="email"
              v-model="form.email"
              type="email"
              :placeholder="t('users.emailPlaceholder')"
              required
            />
          </div>

          <!-- First Name -->
          <div class="col-md-6">
            <CFormLabel for="first_name">{{ t('users.firstName') }}</CFormLabel>
            <CFormInput
              id="first_name"
              v-model="form.first_name"
              type="text"
              :placeholder="t('users.firstNamePlaceholder')"
            />
          </div>

          <!-- Last Name -->
          <div class="col-md-6">
            <CFormLabel for="last_name">{{ t('users.lastName') }}</CFormLabel>
            <CFormInput
              id="last_name"
              v-model="form.last_name"
              type="text"
              :placeholder="t('users.lastNamePlaceholder')"
            />
          </div>

          <!-- Role -->
          <div class="col-md-12">
            <CFormLabel for="role">{{ t('users.role') }} *</CFormLabel>
            <CFormSelect id="role" v-model="form.role" required>
              <option v-for="role in roles" :key="role.key" :value="role.key">
                {{ role.name }}
              </option>
            </CFormSelect>
          </div>

          <!-- Password -->
          <div class="col-md-12">
            <CFormLabel for="password">
              {{ user ? t('users.newPassword') : t('users.password') }}
              {{ !user ? '*' : '' }}
            </CFormLabel>
            <CFormInput
              id="password"
              v-model="form.password"
              type="password"
              :placeholder="t('users.passwordPlaceholder')"
              :required="!user"
            />
            <small v-if="user" class="text-muted">
              {{ t('users.leaveBlankToKeep') }}
            </small>
          </div>
        </div>
      </CForm>
    </CModalBody>
    <CModalFooter>
      <CButton color="secondary" @click="$emit('update:modelValue', false)">
        {{ t('common.cancel') }}
      </CButton>
      <CButton color="primary" @click="handleSubmit">
        <CIcon icon="cil-save" class="me-1" />
        {{ t('common.save') }}
      </CButton>
    </CModalFooter>
  </CModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import {
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CButton,
  CForm,
  CFormLabel,
  CFormInput,
  CFormSelect,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();

const props = defineProps({
  modelValue: Boolean,
  user: Object,
  roles: Array,
});

const emit = defineEmits(['update:modelValue', 'save']);

const form = ref({
  username: '',
  email: '',
  first_name: '',
  last_name: '',
  role: 'asmaa_staff',
  password: '',
});

watch(() => props.user, (newUser) => {
  if (newUser) {
    form.value = {
      username: newUser.username || '',
      email: newUser.email || '',
      first_name: newUser.first_name || '',
      last_name: newUser.last_name || '',
      role: newUser.role || 'asmaa_staff',
      password: '',
    };
  } else {
    resetForm();
  }
});

const resetForm = () => {
  form.value = {
    username: '',
    email: '',
    first_name: '',
    last_name: '',
    role: 'asmaa_staff',
    password: '',
  };
};

const handleSubmit = () => {
  const data = { ...form.value };
  
  // Remove empty password if editing
  if (props.user && !data.password) {
    delete data.password;
  }

  emit('save', data);
};
</script>

<style scoped>
.text-muted {
  color: var(--text-muted);
  font-size: 12px;
}
</style>

