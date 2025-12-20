<template>
  <CModal
    :visible="modelValue"
    @close="$emit('update:modelValue', false)"
  >
    <CModalHeader>
      <CModalTitle>
        {{ t('users.assignRole') }}
      </CModalTitle>
    </CModalHeader>
    <CModalBody>
      <div v-if="user" class="mb-3">
        <div class="user-info">
          <img :src="user.avatar_url" :alt="user.display_name" class="user-avatar" />
          <div>
            <div class="user-name">{{ user.display_name }}</div>
            <div class="user-email">{{ user.email }}</div>
          </div>
        </div>
      </div>

      <CFormLabel>{{ t('users.selectRole') }}</CFormLabel>
      <CFormSelect v-model="selectedRole">
        <option v-for="role in roles" :key="role.key" :value="role.key">
          {{ role.name }}
        </option>
      </CFormSelect>

      <div v-if="selectedRoleInfo" class="mt-3">
        <small class="text-muted">
          {{ t('users.capabilities') }}: {{ selectedRoleInfo.capabilities_count }}
        </small>
      </div>
    </CModalBody>
    <CModalFooter>
      <CButton color="secondary" @click="$emit('update:modelValue', false)">
        {{ t('common.cancel') }}
      </CButton>
      <CButton color="primary" @click="handleAssign">
        <CIcon icon="cil-shield-alt" class="me-1" />
        {{ t('users.assign') }}
      </CButton>
    </CModalFooter>
  </CModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import {
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CButton,
  CFormLabel,
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

const selectedRole = ref('');

watch(() => props.user, (newUser) => {
  if (newUser) {
    selectedRole.value = newUser.role || '';
  }
}, { immediate: true });

const selectedRoleInfo = computed(() => {
  return props.roles?.find(r => r.key === selectedRole.value);
});

const handleAssign = () => {
  emit('save', { role: selectedRole.value });
};
</script>

<style scoped>
.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: var(--bg-secondary);
  border-radius: 8px;
}

.user-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--border-color);
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
}

.user-email {
  font-size: 13px;
  color: var(--text-muted);
}

.text-muted {
  color: var(--text-muted);
}
</style>

