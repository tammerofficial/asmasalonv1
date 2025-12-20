<template>
    <div class="categories-page">
        <div class="page-header">
            <div class="header-left">
                <h1 class="page-title">{{ t('booking.serviceCategories') }}</h1>
                <p class="page-subtitle">{{ t('booking.manageCategoriesSubtitle') }}</p>
            </div>
            <div class="header-right">
                <button class="btn btn-secondary" @click="$router.back()">
                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    {{ t('booking.back') }}
                </button>
                <button class="btn btn-primary" @click="openCategoryModal()">
                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ t('booking.addCategory') }}
                </button>
            </div>
        </div>

        <div class="categories-content">
            <div v-if="loading" class="loading-state">
                <div class="spinner"></div>
                <p>{{ t('booking.loadingCategories') }}</p>
            </div>

            <div v-else-if="categories.length === 0" class="empty-state">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <p>{{ t('booking.noCategoriesFound') }}</p>
                <button class="btn btn-primary" @click="openCategoryModal()">
                    {{ t('booking.addFirstCategory') }}
                </button>
            </div>

            <div v-else class="categories-grid">
                <div v-for="category in categories" :key="category.id" class="category-card">
                    <div class="category-header">
                        <div class="category-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </div>
                        <div class="category-info">
                            <h3 class="category-name">{{ category.name }}</h3>
                            <p class="category-description" v-if="category.description">{{ category.description }}</p>
                        </div>
                    </div>
                    <div class="category-footer">
                        <div class="category-meta">
                            <span class="meta-item">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                {{ category.services_count || 0 }} {{ t('booking.services') }}
                            </span>
                        </div>
                        <div class="category-actions">
                            <button class="action-btn edit" @click="openCategoryModal(category)" :title="t('booking.edit')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button class="action-btn delete" @click="deleteCategory(category.id)" :title="t('booking.delete')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Modal -->
        <div v-if="showCategoryModal" class="modal-overlay" @click="closeCategoryModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>{{ editingCategory ? t('booking.editCategory') : t('booking.addCategory') }}</h3>
                    <button class="modal-close" @click="closeCategoryModal">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ t('booking.categoryName') }} *</label>
                        <input v-model="categoryForm.name" type="text" class="form-input" required />
                    </div>
                    <div class="form-group">
                        <label>{{ t('booking.categoryDescription') }}</label>
                        <textarea v-model="categoryForm.description" class="form-input" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeCategoryModal">{{ t('booking.cancel') }}</button>
                    <button class="btn btn-primary" @click="saveCategory" :disabled="saving">
                        {{ saving ? t('booking.saving') : t('booking.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useUIStore } from '@/stores/ui.js';
import { useTranslation } from '@/composables/useTranslation.js';

const { t } = useTranslation();
const uiStore = useUIStore();

const loading = ref(false);
const categories = ref([
    { id: 1, name: 'Hair Services', description: 'All hair-related services', services_count: 5 },
    { id: 2, name: 'Spa & Massage', description: 'Relaxation and spa treatments', services_count: 3 },
    { id: 3, name: 'Nail Care', description: 'Manicure and pedicure services', services_count: 4 },
]);

const showCategoryModal = ref(false);
const editingCategory = ref(null);
const saving = ref(false);

const categoryForm = reactive({
    name: '',
    description: '',
});

const openCategoryModal = (category = null) => {
    editingCategory.value = category;
    if (category) {
        categoryForm.name = category.name;
        categoryForm.description = category.description || '';
    } else {
        categoryForm.name = '';
        categoryForm.description = '';
    }
    showCategoryModal.value = true;
};

const closeCategoryModal = () => {
    showCategoryModal.value = false;
    editingCategory.value = null;
    categoryForm.name = '';
    categoryForm.description = '';
};

const saveCategory = async () => {
    saving.value = true;
    try {
        if (editingCategory.value) {
            // Update existing category
            const index = categories.value.findIndex(c => c.id === editingCategory.value.id);
            if (index !== -1) {
                categories.value[index] = {
                    ...categories.value[index],
                    name: categoryForm.name,
                    description: categoryForm.description,
                };
            }
        } else {
            // Add new category
            const newId = Math.max(...categories.value.map(c => c.id), 0) + 1;
            categories.value.push({
                id: newId,
                name: categoryForm.name,
                description: categoryForm.description,
                services_count: 0,
            });
        }
        closeCategoryModal();
    } catch (error) {
        console.error('Failed to save category:', error);
        alert(t('booking.failedToSave'));
    } finally {
        saving.value = false;
    }
};

const deleteCategory = (id) => {
    if (confirm(t('booking.deleteCategoryConfirm'))) {
        categories.value = categories.value.filter(c => c.id !== id);
    }
};

onMounted(() => {
    // Load categories from API
    loading.value = false;
});
</script>

<style scoped>
.categories-page {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
}

.header-left {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem;
}

.page-subtitle {
    color: #64748b;
    margin: 0;
}

.header-right {
    display: flex;
    gap: 0.75rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon {
    width: 18px;
    height: 18px;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5568d3;
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

.categories-content {
    min-height: 400px;
}

.loading-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
}

.spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #e2e8f0;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-icon {
    width: 80px;
    height: 80px;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.category-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
}

.category-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.category-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.category-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.category-icon svg {
    width: 24px;
    height: 24px;
}

.category-info {
    flex: 1;
}

.category-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem;
}

.category-description {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

.category-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.category-meta {
    display: flex;
    gap: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: #64748b;
}

.meta-item svg {
    width: 16px;
    height: 16px;
}

.category-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn svg {
    width: 16px;
    height: 16px;
}

.action-btn.edit {
    background: #dbeafe;
    color: #1e40af;
}

.action-btn.edit:hover {
    background: #3b82f6;
    color: white;
}

.action-btn.delete {
    background: #fee2e2;
    color: #991b1b;
}

.action-btn.delete:hover {
    background: #ef4444;
    color: white;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
}

.modal-close {
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    color: #64748b;
    border-radius: 6px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.modal-close svg {
    width: 20px;
    height: 20px;
}

.modal-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #1e293b;
}

.form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

textarea.form-input {
    resize: vertical;
    min-height: 80px;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

/* Dark Mode */
body.dark-mode .page-title {
    color: #f1f5f9;
}

body.dark-mode .page-subtitle {
    color: #94a3b8;
}

body.dark-mode .category-card {
    background: #1e293b;
    border-color: #334155;
}

body.dark-mode .category-name {
    color: #f1f5f9;
}

body.dark-mode .category-description {
    color: #94a3b8;
}

body.dark-mode .modal-content {
    background: #1e293b;
}

body.dark-mode .modal-header,
body.dark-mode .modal-footer {
    border-color: #334155;
}

body.dark-mode .form-input {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}
</style>

