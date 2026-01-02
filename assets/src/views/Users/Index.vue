<template>
  <div class="users-page">
    <PageHeader :title="t('users.title')" :subtitle="t('users.subtitle')">
      <template #icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      </template>
      <template #actions>
        <div class="header-actions">
          <CButton class="btn-refresh" color="secondary" variant="outline" @click="refresh">
            <CIcon icon="cil-reload" class="me-1" />
            {{ t('common.refresh') }}
          </CButton>
          <CButton class="btn-add-user" color="primary" @click="openCreateModal">
            <CIcon icon="cil-plus" class="me-1" />
            {{ t('users.addNew') }}
          </CButton>
        </div>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard :label="t('users.totalUsers')" :value="counts.total" color="gold">
        <template #icon><CIcon icon="cil-people" /></template>
      </StatCard>
      <StatCard :label="t('users.staffUsers')" :value="counts.staff" color="gold">
        <template #icon><CIcon icon="cil-user" /></template>
      </StatCard>
      <StatCard :label="t('users.customersUsers')" :value="counts.customers" color="gold">
        <template #icon><CIcon icon="cil-user-follow" /></template>
      </StatCard>
    </div>

    <!-- Filters -->
    <CCard class="filters-card mb-4">
      <CCardBody>
        <div class="filters-row">
          <CButtonGroup class="scope-group">
            <CButton
              :color="filters.scope === 'all' ? 'primary' : 'light'"
              @click="setScope('all')"
            >
              {{ t('users.scopeAll') }}
            </CButton>
            <CButton
              :color="filters.scope === 'staff' ? 'primary' : 'light'"
              @click="setScope('staff')"
            >
              {{ t('users.scopeStaff') }}
            </CButton>
            <CButton
              :color="filters.scope === 'customers' ? 'primary' : 'light'"
              @click="setScope('customers')"
            >
              {{ t('users.scopeCustomers') }}
            </CButton>
          </CButtonGroup>

          <div class="search-box">
            <CIcon icon="cil-search" class="search-icon" />
            <input
              v-model="filters.search"
              type="text"
              :placeholder="t('users.searchPlaceholder')"
              class="search-input"
              @input="debouncedSearch"
            />
          </div>

          <div class="filter-select">
            <CFormSelect v-model="filters.role" @change="onRoleChange">
              <option value="">{{ t('users.allRoles') }}</option>
              <option v-for="role in roles" :key="role.key" :value="role.key">
                {{ role.name }}
              </option>
            </CFormSelect>
          </div>
        </div>
      </CCardBody>
    </CCard>

    <!-- Users Table -->
    <CCard>
      <CCardBody>
        <LoadingSpinner v-if="loading" />

        <div v-else-if="users.length === 0" class="text-center py-5">
          <EmptyState
            :title="t('users.noUsers')"
            :description="t('users.noUsersDescription')"
            icon="cil-people"
          />
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ t('users.user') }}</th>
                <th>{{ t('users.email') }}</th>
                <th>{{ t('users.role') }}</th>
                <th>{{ t('users.registered') }}</th>
                <th class="text-end">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id">
                <td>
                  <div class="user-cell">
                    <img :src="user.avatar_url" :alt="user.display_name" class="user-avatar" />
                    <div>
                      <div class="user-name">{{ user.display_name }}</div>
                      <div class="user-username">@{{ user.username }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="text-muted">{{ user.email }}</span>
                </td>
                <td>
                  <CBadge :color="getRoleBadgeColor(user.role)">
                    {{ user.role_name }}
                  </CBadge>
                </td>
                <td>
                  <span class="text-muted">{{ formatDate(user.registered) }}</span>
                </td>
                <td>
                  <div class="actions-cell">
                    <CButton
                      color="primary"
                      variant="ghost"
                      size="sm"
                      @click="openEditModal(user)"
                    >
                      <CIcon icon="cil-pencil" />
                    </CButton>
                    <CButton
                      color="info"
                      variant="ghost"
                      size="sm"
                      @click="openRoleModal(user)"
                    >
                      <CIcon icon="cil-shield-alt" />
                    </CButton>
                    <CButton v-if="user.id !== currentUserId" color="danger" variant="ghost" size="sm" @click="openDeleteModal(user)">
                      <CIcon icon="cil-trash" />
                    </CButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total_pages > 1" class="pagination-wrapper">
          <CPagination
            :pages="pagination.total_pages"
            :active-page="pagination.page"
            @update:activePage="changePage"
          />
        </div>
      </CCardBody>
    </CCard>

    <!-- Create/Edit Modal -->
    <UserModal
      v-model="showUserModal"
      :user="selectedUser"
      :roles="roles"
      @save="handleSaveUser"
    />

    <!-- Role Assignment Modal -->
    <RoleModal
      v-model="showRoleModal"
      :user="selectedUser"
      :roles="roles"
      @save="handleAssignRole"
    />

    <!-- Delete Confirmation Modal -->
    <CModal :visible="showDeleteModal" @close="closeDeleteModal">
      <CModalHeader>
        <CModalTitle>{{ t('users.deleteUserTitle') }}</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <p class="mb-0">
          {{ t('users.confirmDelete', { name: deletingUser?.display_name || '' }) }}
        </p>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeDeleteModal">{{ t('common.cancel') }}</CButton>
        <CButton color="danger" :disabled="deleting" @click="confirmDelete">
          <span v-if="deleting">{{ t('common.deleting') || t('common.loading') }}</span>
          <span v-else>{{ t('common.delete') }}</span>
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { CButton, CCard, CCardBody, CBadge, CFormSelect, CPagination, CButtonGroup, CModal, CModalHeader, CModalTitle, CModalBody, CModalFooter } from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import api from '@/utils/api';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import UserModal from './UserModal.vue';
import RoleModal from './RoleModal.vue';

const { t } = useTranslation();
const toast = useToast();

// State
const loading = ref(false);
const users = ref([]);
const roles = ref([]);
const selectedUser = ref(null);
const showUserModal = ref(false);
const showRoleModal = ref(false);
const currentUserId = ref(window.AsmaaSalonConfig?.currentUserId || 0);

const filters = reactive({
  search: '',
  role: '',
  scope: 'all', // all | staff | customers
});

const pagination = reactive({
  page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
});

const counts = reactive({
  total: 0,
  staff: 0,
  customers: 0,
});

// Methods
const loadUsers = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.page,
      per_page: pagination.per_page,
    };

    if (filters.search) {
      params.search = filters.search;
    }

    if (filters.scope && filters.scope !== 'all') {
      params.scope = filters.scope;
    }

    if (filters.role) {
      params.role = filters.role;
    }

    const response = await api.get('users', { params });
    
    if (response.data.success) {
      users.value = response.data.data;
      Object.assign(pagination, response.data.pagination);
    }
  } catch (error) {
    console.error('Error loading users:', error);
    toast.error(t('users.errorLoading'));
  } finally {
    loading.value = false;
  }
};

const loadRoles = async () => {
  try {
    const response = await api.get('users/roles', { params: { scope: 'all' }, noCache: true });
    if (response.data.success) {
      roles.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading roles:', error);
  }
};

const loadCounts = async () => {
  try {
    const [allRes, staffRes, customersRes] = await Promise.all([
      api.get('users', { params: { page: 1, per_page: 1 }, noCache: true }),
      api.get('users', { params: { page: 1, per_page: 1, scope: 'staff' }, noCache: true }),
      api.get('users', { params: { page: 1, per_page: 1, scope: 'customers' }, noCache: true }),
    ]);

    counts.total = allRes.data?.pagination?.total ?? 0;
    counts.staff = staffRes.data?.pagination?.total ?? 0;
    counts.customers = customersRes.data?.pagination?.total ?? 0;
  } catch (e) {
    // silent
  }
};

const refresh = async () => {
  await Promise.all([loadUsers(), loadCounts(), loadRoles()]);
};

let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.page = 1;
    loadUsers();
  }, 500);
};

const setScope = (scope) => {
  filters.scope = scope;
  filters.role = '';
  pagination.page = 1;
  loadUsers();
};

const onRoleChange = () => {
  filters.scope = 'all';
  pagination.page = 1;
  loadUsers();
};

const openCreateModal = () => {
  selectedUser.value = null;
  showUserModal.value = true;
};

const openEditModal = (user) => {
  selectedUser.value = { ...user };
  showUserModal.value = true;
};

const openRoleModal = (user) => {
  selectedUser.value = { ...user };
  showRoleModal.value = true;
};

const handleSaveUser = async (userData) => {
  try {
    if (selectedUser.value?.id) {
      // Update existing user
      await api.put(`users/${selectedUser.value.id}`, userData);
      toast.success(t('users.updatedSuccessfully'));
    } else {
      // Create new user
      await api.post('users', userData);
      toast.success(t('users.createdSuccessfully'));
    }
    
    showUserModal.value = false;
    refresh();
  } catch (error) {
    console.error('Error saving user:', error);
    const message = error.response?.data?.message || t('users.errorSaving');
    toast.error(message);
  }
};

const handleAssignRole = async (roleData) => {
  try {
    await api.put(`users/${selectedUser.value.id}/role`, roleData);
    toast.success(t('users.roleAssignedSuccessfully'));
    showRoleModal.value = false;
    refresh();
  } catch (error) {
    console.error('Error assigning role:', error);
    const message = error.response?.data?.message || t('users.errorAssigningRole');
    toast.error(message);
  }
};

const showDeleteModal = ref(false);
const deletingUser = ref(null);
const deleting = ref(false);

const openDeleteModal = (user) => {
  deletingUser.value = user;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  deletingUser.value = null;
  deleting.value = false;
};

const confirmDelete = async () => {
  if (!deletingUser.value) return;
  deleting.value = true;
  try {
    await api.delete(`users/${deletingUser.value.id}`);
    toast.success(t('users.deletedSuccessfully'));
    closeDeleteModal();
    refresh();
  } catch (error) {
    console.error('Error deleting user:', error);
    const message = error.response?.data?.message || t('users.errorDeleting');
    toast.error(message);
  } finally {
    deleting.value = false;
  }
};

const changePage = (page) => {
  pagination.page = page;
  loadUsers();
};

const getRoleBadgeColor = (role) => {
  if (role === 'administrator' || role === 'asmaa_super_admin') return 'danger';
  if (role === 'asmaa_admin') return 'warning';
  if (role === 'asmaa_manager') return 'primary';
  if (role === 'asmaa_accountant') return 'success';
  return 'secondary';
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('ar-KW', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Lifecycle
onMounted(() => {
  refresh();
});
</script>

<style scoped>
.users-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.header-actions {
  display: inline-flex;
  gap: 10px;
  align-items: center;
}

.btn-refresh {
  border-color: var(--asmaa-primary) !important;
  color: var(--asmaa-primary) !important;
}

.btn-refresh:hover,
.btn-refresh:focus {
  background: rgba(142, 126, 120, 0.12) !important;
  border-color: var(--asmaa-primary) !important;
  color: var(--asmaa-primary) !important;
}

.btn-add-user {
  background: var(--asmaa-primary) !important;
  border-color: var(--asmaa-primary) !important;
  color: #fff !important;
}

.btn-add-user:hover,
.btn-add-user:focus {
  background: rgba(142, 126, 120, 0.9) !important;
  border-color: rgba(142, 126, 120, 0.9) !important;
  color: #fff !important;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.filters-card {
  border: 1px solid var(--border-color);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.filters-row {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.scope-group :deep(.btn) {
  font-weight: 800;
}

.search-box {
  flex: 1;
  position: relative;
  min-width: 260px;
}

.search-icon {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  width: 18px;
  height: 18px;
}

[dir="ltr"] .search-icon {
  left: 12px;
}

[dir="rtl"] .search-icon {
  right: 12px;
}

.search-input {
  width: 100%;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background: var(--bg-primary);
  color: var(--text-primary);
  font-size: 14px;
  transition: all 0.2s;
}

[dir="ltr"] .search-input {
  padding: 10px 12px 10px 40px;
}

[dir="rtl"] .search-input {
  padding: 10px 40px 10px 12px;
}

.search-input:focus {
  outline: none;
  border-color: var(--asmaa-primary);
  box-shadow: 0 0 0 3px rgba(142, 126, 120, 0.1);
}

.filter-select {
  min-width: 200px;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--border-color);
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
}

.user-username {
  font-size: 12px;
  color: var(--text-muted);
}

.actions-cell {
  display: flex;
  gap: 4px;
  justify-content: flex-end;
}

.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 1.5rem;
}

.table {
  color: var(--text-primary);
}

.table thead th {
  border-bottom: 2px solid var(--border-color);
  font-weight: 700;
  color: var(--text-secondary);
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px;
}

.table tbody td {
  padding: 12px;
  vertical-align: middle;
  border-bottom: 1px solid var(--border-color);
}

.table tbody tr:hover {
  background: var(--bg-tertiary);
}
</style>

