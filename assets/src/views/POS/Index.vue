<template>
  <div class="pos-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('pos.title')"
      :subtitle="t('pos.subtitle')"
    >
      <template #icon>
        <CIcon icon="cil-cart" />
      </template>
      <template #actions>
        <CButton v-if="openSession" color="primary" variant="outline" @click="showSessionModal = true" class="me-2">
          <CIcon icon="cil-info" class="me-2" />
          {{ t('pos.sessionInfo') }}
        </CButton>
        <CButton color="primary" variant="outline" @click="refreshData" class="me-2">
          <CIcon icon="cil-reload" class="me-2" />
          {{ t('pos.refresh') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Main POS Layout (3 Columns) -->
    <div class="pos-layout">
      <!-- Column 1: Active Customers -->
      <div class="pos-column pos-column-1">
        <Card :title="t('pos.activeCustomers')" icon="cil-people" class="h-100">
          <LoadingSpinner v-if="loadingActiveCustomers" :text="t('common.loading')" />
          
          <EmptyState 
            v-else-if="activeCustomers.length === 0"
            :title="t('pos.noActiveCustomers')"
            :description="t('pos.noActiveCustomersDesc')"
            icon-color="gray"
          />

          <div v-else class="active-customers-list">
            <div 
              v-for="customer in activeCustomers" 
              :key="customer.id"
              class="active-customer-item"
              :class="{ 'selected': Number(selectedCustomerId) === Number(customer.id) }"
              @click="selectCustomer(customer.id)"
            >
              <div class="customer-avatar">
                <CIcon icon="cil-user" />
              </div>
              <div class="customer-info">
                <div class="customer-name">{{ customer.name }}</div>
                <div class="customer-meta">
                  <CBadge color="primary" class="me-2">
                    {{ customer.type === 'queue' ? (customer.ticket_number ? `#${customer.ticket_number}` : t('queue.title')) : t('bookings.title') }}
                  </CBadge>
                  <span class="customer-service">{{ customer.current_service || 'N/A' }}</span>
                </div>
                <div class="customer-staff" v-if="customer.staff_name && customer.staff_name !== 'Unassigned'">
                  <CIcon icon="cil-user" class="me-1" />
                  {{ customer.staff_name }}
                </div>
                <div class="customer-time" v-if="customer.booking_start_at || customer.booking_time">
                  <CIcon icon="cil-clock" class="me-1" />
                  {{ formatBookingTime(customer) }}
                </div>
              </div>
            </div>
          </div>

          <!-- All Customers Dropdown -->
          <template #footer>
            <CFormSelect 
              v-model.number="selectedCustomerId" 
              :placeholder="t('pos.selectCustomer')"
              @change="onCustomerSelect"
            >
              <option value="">{{ t('pos.selectCustomer') }}</option>
              <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                {{ customer.name_ar || customer.name }} - {{ customer.phone }}
              </option>
            </CFormSelect>
          </template>
        </Card>
      </div>

      <!-- Column 2: Services/Products -->
      <div class="pos-column pos-column-2">
        <!-- Tabs -->
        <Card>
          <CNav variant="tabs" role="tablist">
            <CNavItem>
              <CNavLink 
                :active="activeTab === 'services'"
                @click="activeTab = 'services'"
                role="tab"
              >
                <CIcon icon="cil-spreadsheet" class="me-2" />
                {{ t('pos.services') }}
              </CNavLink>
            </CNavItem>
            <CNavItem>
              <CNavLink 
                :active="activeTab === 'products'"
                @click="activeTab = 'products'"
                role="tab"
              >
                <CIcon icon="cil-basket" class="me-2" />
                {{ t('pos.products') }}
              </CNavLink>
            </CNavItem>
          </CNav>
        </Card>

        <!-- Search -->
        <Card>
          <CInputGroup>
            <CInputGroupText>
              <CIcon icon="cil-magnifying-glass" />
            </CInputGroupText>
            <CFormInput
              v-model="searchQuery"
              :placeholder="activeTab === 'services' ? t('pos.searchService') : t('pos.searchProduct')"
              @input="filterItems"
            />
          </CInputGroup>
        </Card>

        <!-- Services Grid -->
        <Card v-if="activeTab === 'services'" :title="t('pos.services')" icon="cil-spreadsheet">
          <LoadingSpinner v-if="loadingServices" :text="t('common.loading')" />
          
          <EmptyState 
            v-else-if="filteredServices.length === 0"
            :title="t('pos.noServices')"
            :description="t('pos.noServices')"
            icon-color="gray"
          />

          <div v-else class="items-grid">
            <div 
              v-for="service in filteredServices" 
              :key="service.id"
              class="item-card"
              @click="handleServiceClick(service, $event)"
            >
              <div class="item-name">{{ service.name_ar || service.name }}</div>
              <div class="item-price">{{ formatCurrency(service.price || 0) }}</div>
              <div class="item-category" v-if="service.category">
                <CIcon icon="cil-tag" class="me-1" />
                {{ service.category }}
              </div>
            </div>
          </div>
        </Card>

        <!-- Products Grid -->
        <Card v-if="activeTab === 'products'" :title="t('pos.products')" icon="cil-basket">
          <LoadingSpinner v-if="loadingProducts" :text="t('common.loading')" />
          
          <EmptyState 
            v-else-if="filteredProducts.length === 0"
            :title="t('pos.noProducts')"
            :description="t('pos.noProducts')"
            icon-color="gray"
          />

          <div v-else class="items-grid">
            <div 
              v-for="product in filteredProducts" 
              :key="product.id"
              class="item-card"
              :class="{ 'out-of-stock': (product.stock_quantity || 0) <= 0 }"
              @click="handleProductClick(product, $event)"
            >
              <div class="item-name">{{ product.name_ar || product.name }}</div>
              <div class="item-price">{{ formatCurrency(product.selling_price || product.price || 0) }}</div>
              <div class="item-stock" v-if="product.stock_quantity !== undefined">
                <CBadge color="primary">
                  {{ t('products.stock') }}: {{ product.stock_quantity }}
                </CBadge>
              </div>
              <div class="item-sku" v-if="product.sku">
                <small>SKU: {{ product.sku }}</small>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Column 3: Cart -->
      <div class="pos-column pos-column-3">
        <Card :title="t('pos.cart')" icon="cil-cart" class="h-100">
          <!-- Customer Selection -->
          <div class="cart-customer mb-3">
            <label class="form-label">{{ t('pos.customer') }}</label>
            <CFormSelect 
              v-model.number="selectedCustomerId" 
              @change="onCustomerSelect"
            >
              <option value="">{{ t('pos.walkInCustomer') }}</option>
              <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                {{ customer.name_ar || customer.name }}
              </option>
            </CFormSelect>
          </div>

          <!-- Cart Items -->
          <div class="cart-items">
            <EmptyState 
              v-if="cart.length === 0"
              :title="t('pos.emptyCart')"
              :description="t('pos.emptyCartDesc')"
              icon-color="gray"
            />

            <div v-else class="cart-items-list">
              <div 
                v-for="(item, index) in cart" 
                :key="index"
                class="cart-item"
              >
                <div class="cart-item-info">
                  <div class="cart-item-name">{{ item.name }}</div>
                  <div class="cart-item-meta" v-if="item.staff_name">
                    <CIcon icon="cil-user" class="me-1" />
                    {{ item.staff_name }}
                  </div>
                </div>
                <div class="cart-item-actions">
                  <div class="cart-item-quantity">
                    <CButton 
                      color="primary" 
                      size="sm" 
                      variant="outline"
                      @click="decreaseQuantity(index)"
                    >
                      <CIcon icon="cil-minus" />
                    </CButton>
                    <span class="quantity-value">{{ item.quantity }}</span>
                    <CButton 
                      color="primary" 
                      size="sm" 
                      variant="outline"
                      @click="increaseQuantity(index)"
                    >
                      <CIcon icon="cil-plus" />
                    </CButton>
                  </div>
                  <div class="cart-item-price">{{ formatCurrency(item.unit_price * item.quantity) }}</div>
                  <CButton 
                    color="primary" 
                    size="sm" 
                    variant="ghost"
                    @click="removeFromCart(index)"
                  >
                    <CIcon icon="cil-trash" />
                  </CButton>
                </div>
              </div>
            </div>
          </div>

          <!-- Cart Summary -->
          <div class="cart-summary">
            <div class="summary-row">
              <span>{{ t('pos.subtotal') }}:</span>
              <strong>{{ formatCurrency(subtotal) }}</strong>
            </div>
            <div class="summary-row">
              <span>{{ t('pos.discount') }}:</span>
              <CFormInput
                v-model.number="discount"
                type="number"
                step="0.001"
                min="0"
                :max="subtotal"
                @input="calculateTotal"
                class="discount-input"
              />
            </div>
            <div class="summary-row total-row">
              <span>{{ t('pos.total') }}:</span>
              <strong class="total-amount">{{ formatCurrency(total) }}</strong>
            </div>
          </div>

          <!-- Payment Method -->
          <div class="payment-method mb-3">
            <label class="form-label">{{ t('pos.paymentMethod') }}</label>
            <CFormSelect v-model="paymentMethod">
              <option value="cash">💵 {{ t('pos.cash') }}</option>
              <option value="card">💳 {{ t('pos.card') }}</option>
              <option value="knet">🏧 {{ t('pos.knet') }}</option>
            </CFormSelect>
          </div>

          <!-- Actions -->
          <div class="cart-actions">
            <CButton 
              color="primary" 
              variant="outline" 
              @click="clearCart"
              class="w-100 mb-2"
              :disabled="cart.length === 0"
            >
              <CIcon icon="cil-trash" class="me-2" />
              {{ t('pos.clearCart') }}
            </CButton>
            <CButton 
              color="primary" 
              @click="processOrder"
              class="w-100"
              :disabled="cart.length === 0 || processing"
            >
              <CIcon icon="cil-check-circle" class="me-2" />
              {{ processing ? t('pos.processing') : t('pos.processOrder') }}
            </CButton>
          </div>
        </Card>
      </div>
    </div>

    <!-- Service Modal -->
    <CModal 
      v-model:visible="showServiceModal" 
      :title="t('pos.addService')"
      size="lg"
    >
      <template #body>
        <div v-if="selectedService">
          <div class="mb-3">
            <label class="form-label">{{ t('pos.service') }}</label>
            <CFormInput :value="selectedService.name_ar || selectedService.name" disabled />
          </div>
          <div class="mb-3">
            <label class="form-label">{{ t('pos.price') }}</label>
            <CFormInput :value="formatCurrency(selectedService.price || 0)" disabled />
          </div>
          <div class="mb-3">
            <label class="form-label">{{ t('pos.staff') }}</label>
            <CFormSelect v-model="selectedStaffId">
              <option value="">{{ t('pos.selectStaff') }}</option>
              <option v-for="staffMember in staff" :key="staffMember.id" :value="staffMember.id">
                {{ staffMember.name }}
              </option>
            </CFormSelect>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ t('pos.quantity') }}</label>
            <CFormInput 
              v-model.number="serviceQuantity" 
              type="number" 
              min="1"
            />
          </div>
        </div>
      </template>
      <template #footer>
        <CButton color="primary" variant="outline" @click="showServiceModal = false">{{ t('pos.cancel') }}</CButton>
        <CButton color="primary" @click="addServiceToCart">{{ t('pos.addToCart') }}</CButton>
      </template>
    </CModal>

    <!-- Product Modal -->
    <CModal 
      v-model:visible="showProductModal" 
      :title="t('pos.addProduct')"
      size="lg"
    >
      <template #body>
        <div v-if="selectedProduct">
          <div class="mb-3">
            <label class="form-label">{{ t('products.title') }}</label>
            <CFormInput :value="selectedProduct.name_ar || selectedProduct.name" disabled />
          </div>
          <div class="mb-3">
            <label class="form-label">{{ t('pos.price') }}</label>
            <CFormInput :value="formatCurrency(selectedProduct.selling_price || selectedProduct.price || 0)" disabled />
          </div>
          <div class="mb-3">
            <label class="form-label">{{ t('pos.availableStock') }}</label>
            <CFormInput :value="selectedProduct.stock_quantity || 0" disabled />
          </div>
          <div class="mb-3">
            <label class="form-label">{{ t('pos.staff') }}</label>
            <CFormSelect v-model="selectedProductStaffId">
              <option value="">{{ t('pos.selectStaff') }}</option>
              <option v-for="staffMember in staff" :key="staffMember.id" :value="staffMember.id">
                {{ staffMember.name }}
              </option>
            </CFormSelect>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ t('pos.quantity') }}</label>
            <CFormInput 
              v-model.number="productQuantity" 
              type="number" 
              min="1"
              :max="selectedProduct.stock_quantity || 999"
            />
          </div>
        </div>
      </template>
      <template #footer>
        <CButton color="primary" variant="outline" @click="showProductModal = false">{{ t('pos.cancel') }}</CButton>
        <CButton 
          color="primary" 
          @click="addProductToCart"
          :disabled="productQuantity > (selectedProduct?.stock_quantity || 0)"
        >
          {{ t('pos.addToCart') }}
        </CButton>
      </template>
    </CModal>

    <!-- Session Modal -->
    <CModal 
      v-model:visible="showSessionModal" 
      :title="t('pos.sessionInfo')"
    >
      <template #body>
        <div v-if="openSession">
          <div class="mb-3">
            <strong>{{ t('pos.openedAt') }}:</strong> {{ formatDateTime(openSession.opened_at) }}
          </div>
          <div class="mb-3">
            <strong>{{ t('pos.openingCash') }}:</strong> {{ formatCurrency(openSession.opening_cash || 0) }}
          </div>
          <div class="mb-3">
            <strong>{{ t('pos.totalTransactions') }}:</strong> {{ openSession.total_transactions || 0 }}
          </div>
          <div class="mb-3">
            <strong>{{ t('pos.totalSales') }}:</strong> {{ formatCurrency(openSession.total_sales || 0) }}
          </div>
        </div>
      </template>
      <template #footer>
        <CButton color="primary" @click="showSessionModal = false">{{ t('pos.close') }}</CButton>
      </template>
    </CModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import {
  CButton,
  CBadge,
  CFormInput,
  CFormSelect,
  CInputGroup,
  CInputGroupText,
  CModal,
  CNav,
  CNavItem,
  CNavLink,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import PageHeader from '@/components/UI/PageHeader.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import api, { clearCache } from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();

// Data
const activeTab = ref('services');
const searchQuery = ref('');
const selectedCustomerId = ref(null);
const discount = ref(0);
const paymentMethod = ref('cash');
const processing = ref(false);

// Products & Services
const products = ref([]);
const services = ref([]);
const staff = ref([]);
const customers = ref([]);
const activeCustomers = ref([]);
const openSession = ref(null);

// Loading states
const loadingProducts = ref(false);
const loadingServices = ref(false);
const loadingActiveCustomers = ref(false);

// Cart
const cart = ref([]);

// Modals
const showServiceModal = ref(false);
const showProductModal = ref(false);
const showSessionModal = ref(false);
const selectedService = ref(null);
const selectedProduct = ref(null);
const selectedStaffId = ref('');
const selectedProductStaffId = ref('');
const serviceQuantity = ref(1);
const productQuantity = ref(1);

// Computed
const filteredServices = computed(() => {
  if (!searchQuery.value) return services.value;
  const query = searchQuery.value.toLowerCase();
  return services.value.filter(s => 
    (s.name_ar || s.name || '').toLowerCase().includes(query) ||
    (s.category || '').toLowerCase().includes(query)
  );
});

const filteredProducts = computed(() => {
  if (!searchQuery.value) return products.value.filter(p => (p.stock_quantity || 0) > 0);
  const query = searchQuery.value.toLowerCase();
  return products.value.filter(p => 
    ((p.name_ar || p.name || '').toLowerCase().includes(query) ||
    (p.sku || '').toLowerCase().includes(query) ||
    (p.barcode || '').toLowerCase().includes(query)) &&
    (p.stock_quantity || 0) > 0
  );
});

const subtotal = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
});

const total = computed(() => {
  return Math.max(0, subtotal.value - discount.value);
});

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
};

const formatDateTime = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleString('en-US');
};

const formatTime = (dateTime) => {
  if (!dateTime) return '';
  const date = new Date(dateTime);
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const formatBookingTime = (customer) => {
  // Prefer a combined datetime string from API
  if (customer?.booking_start_at) {
    return formatTime(customer.booking_start_at);
  }

  // Fallback: booking_time might be a TIME string (e.g. 14:30:00)
  if (customer?.booking_time && typeof customer.booking_time === 'string') {
    return customer.booking_time.slice(0, 5); // HH:MM
  }

  return '';
};

const filterItems = () => {
  // Reactive filtering via computed properties
};

const selectCustomer = (customerId) => {
  selectedCustomerId.value = customerId ? Number(customerId) : null;
  // Scroll to cart section if on mobile
  if (window.innerWidth < 1200) {
    const cartSection = document.querySelector('.pos-column-3');
    if (cartSection) {
      cartSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
};

const onCustomerSelect = () => {
  // Customer selected from dropdown
  if (selectedCustomerId.value) {
    const customer = customers.value.find(c => Number(c.id) === Number(selectedCustomerId.value));
    if (customer) {
      // Customer is now selected, ready to add items
    }
  }
};

const openServiceModal = (service) => {
  selectedService.value = service;
  selectedStaffId.value = '';
  serviceQuantity.value = 1;
  showServiceModal.value = true;
};

const openProductModal = (product) => {
  if ((product.stock_quantity || 0) <= 0) {
    toast.error(t('pos.noProducts'));
    return;
  }
  selectedProduct.value = product;
  selectedProductStaffId.value = '';
  productQuantity.value = 1;
  showProductModal.value = true;
};

const requireCustomerSelected = () => {
  if (!selectedCustomerId.value) {
    toast.error(t('pos.selectCustomer'));
    return false;
  }
  return true;
};

// Click behavior:
// - Normal click: quick-add with qty=1
// - Shift-click: open modal (choose staff/quantity)
const handleServiceClick = (service, event) => {
  if (!requireCustomerSelected()) return;

  if (event?.shiftKey) {
    openServiceModal(service);
    return;
  }

  cart.value.push({
    type: 'service',
    service_id: service.id,
    name: service.name_ar || service.name,
    quantity: 1,
    unit_price: parseFloat(service.price || 0),
    staff_id: null,
    staff_name: '',
  });
};

const handleProductClick = (product, event) => {
  if (!requireCustomerSelected()) return;

  if ((product.stock_quantity || 0) <= 0) {
    toast.error(t('pos.noProducts'));
    return;
  }

  if (event?.shiftKey) {
    openProductModal(product);
    return;
  }

  cart.value.push({
    type: 'product',
    product_id: product.id,
    name: product.name_ar || product.name,
    quantity: 1,
    unit_price: parseFloat(product.selling_price || product.price || 0),
    staff_id: null,
    staff_name: '',
  });
};

const addServiceToCart = () => {
  if (!selectedService.value) return;
  
  const staffMember = staff.value.find(s => s.id == selectedStaffId.value);
  const staffName = staffMember ? staffMember.name : '';
  
  cart.value.push({
    type: 'service',
    service_id: selectedService.value.id,
    name: selectedService.value.name_ar || selectedService.value.name + (staffName ? ` (${staffName})` : ''),
    quantity: serviceQuantity.value,
    unit_price: parseFloat(selectedService.value.price || 0),
    staff_id: selectedStaffId.value || null,
    staff_name: staffName,
  });
  
  calculateTotal();
  showServiceModal.value = false;
};

const addProductToCart = () => {
  if (!selectedProduct.value) return;
  
  if (productQuantity.value > (selectedProduct.value.stock_quantity || 0)) {
    toast.error(t('pos.noProducts'));
    return;
  }
  
  const staffMember = staff.value.find(s => s.id == selectedProductStaffId.value);
  const staffName = staffMember ? staffMember.name : '';
  
  cart.value.push({
    type: 'product',
    product_id: selectedProduct.value.id,
    name: selectedProduct.value.name_ar || selectedProduct.value.name + (staffName ? ` (${staffName})` : ''),
    quantity: productQuantity.value,
    unit_price: parseFloat(selectedProduct.value.selling_price || selectedProduct.value.price || 0),
    staff_id: selectedProductStaffId.value || null,
    staff_name: staffName,
  });
  
  calculateTotal();
  showProductModal.value = false;
};

const increaseQuantity = (index) => {
  cart.value[index].quantity++;
  calculateTotal();
};

const decreaseQuantity = (index) => {
  if (cart.value[index].quantity > 1) {
    cart.value[index].quantity--;
    calculateTotal();
  }
};

const removeFromCart = (index) => {
  cart.value.splice(index, 1);
  calculateTotal();
};

const clearCart = () => {
  if (confirm(t('pos.clearCart') + '?')) {
    cart.value = [];
    discount.value = 0;
    calculateTotal();
  }
};

const calculateTotal = () => {
  // Computed property handles this
};

const processOrder = async () => {
  if (cart.value.length === 0) {
    toast.error(t('pos.emptyCart'));
    return;
  }

  processing.value = true;
  try {
    const payload = {
      customer_id: selectedCustomerId.value ? Number(selectedCustomerId.value) : null,
      items: cart.value.map(item => ({
        service_id: item.service_id || null,
        product_id: item.product_id || null,
        staff_id: item.staff_id || null,
        quantity: item.quantity,
        unit_price: item.unit_price,
        name: item.name,
      })),
      payment_method: paymentMethod.value,
      discount: discount.value || 0,
    };

    const response = await api.post('/pos/process', payload);
    
    if (response.data?.success) {
      const responsePayload = response.data?.data || {};
      const orderNo = responsePayload.order_number || 'N/A';
      const invNo = responsePayload.invoice_number || responsePayload.invoice_id || 'N/A';
      
      // ✅ Backend now creates Payment automatically - no need for frontend workaround
      toast.success(`${t('pos.orderProcessed')} ${orderNo} | Invoice: ${invNo}`);
      
      // Clear cart
      cart.value = [];
      discount.value = 0;
      calculateTotal();

      // Stay on POS, just refresh data so invoice appears in invoices and payments pages
      clearCache('/invoices');
      clearCache('/payments');
      await refreshData();
    } else {
      toast.error(response.data?.message || t('pos.errorProcessing'));
    }
  } catch (error) {
    console.error('Error processing order:', error);
    toast.error(error.response?.data?.message || t('pos.errorProcessing'));
  } finally {
    processing.value = false;
  }
};

const loadProducts = async () => {
  loadingProducts.value = true;
  try {
    const response = await api.get('/products', {
      params: { per_page: 1000, is_active: 1 },
    });
    products.value = response.data?.data?.items || response.data?.items || [];
  } catch (error) {
    console.error('Error loading products:', error);
    products.value = [];
  } finally {
    loadingProducts.value = false;
  }
};

const loadServices = async () => {
  loadingServices.value = true;
  try {
    const response = await api.get('/services', {
      params: { per_page: 1000, is_active: 1 },
    });
    services.value = response.data?.data?.items || response.data?.items || [];
  } catch (error) {
    console.error('Error loading services:', error);
    services.value = [];
  } finally {
    loadingServices.value = false;
  }
};

const loadStaff = async () => {
  try {
    const response = await api.get('/staff', {
      params: { per_page: 1000, status: 'active' },
    });
    staff.value = response.data?.data?.items || response.data?.items || [];
  } catch (error) {
    console.error('Error loading staff:', error);
    staff.value = [];
  }
};

const loadCustomers = async () => {
  try {
    const response = await api.get('/customers', {
      params: { per_page: 1000 },
    });
    customers.value = response.data?.data?.items || response.data?.items || [];
  } catch (error) {
    console.error('Error loading customers:', error);
    customers.value = [];
  }
};

const loadActiveCustomers = async () => {
  loadingActiveCustomers.value = true;
  try {
    const response = await api.get('/pos');
    const data = response.data?.data || response.data || {};
    activeCustomers.value = data.active_customers || [];
  } catch (error) {
    console.error('Error loading active customers:', error);
    activeCustomers.value = [];
  } finally {
    loadingActiveCustomers.value = false;
  }
};

const loadSession = async () => {
  try {
    const response = await api.get('/pos/session');
    openSession.value = response.data?.data || response.data || null;
  } catch (error) {
    console.error('Error loading session:', error);
    openSession.value = null;
  }
};

const refreshData = async () => {
  await Promise.all([
    loadProducts(),
    loadServices(),
    loadStaff(),
    loadCustomers(),
    loadActiveCustomers(),
    loadSession(),
  ]);
  toast.success(t('pos.refresh') + ' - ' + t('common.refresh'));
};

onMounted(() => {
  refreshData();
  
  // Auto-refresh active customers every 10 seconds
  setInterval(() => {
    loadActiveCustomers();
  }, 10000);
});
</script>

<style scoped>
.pos-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-height: 100vh;
  padding-bottom: 2rem;
}

.pos-layout {
  display: grid;
  grid-template-columns: 320px 1fr 420px;
  gap: 1.5rem;
  min-height: calc(100vh - 180px);
  align-items: start;
}

.pos-column {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 600px;
}

.pos-column-1 {
  min-width: 320px;
}

.pos-column-3 {
  min-width: 420px;
}

/* Active Customers */
.active-customers-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  max-height: calc(100vh - 350px);
  padding-right: 0.5rem;
}

.active-customers-list::-webkit-scrollbar {
  width: 6px;
}

.active-customers-list::-webkit-scrollbar-track {
  background: var(--bg-secondary);
  border-radius: 3px;
}

.active-customers-list::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 3px;
}

.active-customers-list::-webkit-scrollbar-thumb:hover {
  background: var(--asmaa-primary);
}

.active-customer-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: 12px;
  border: 2px solid var(--border-color);
  cursor: pointer;
  transition: all 0.2s;
}

.active-customer-item:hover {
  border-color: var(--asmaa-primary);
  background: var(--bg-tertiary);
}

.active-customer-item.selected {
  border-color: var(--asmaa-primary);
  background: rgba(187, 160, 122, 0.1);
}

.customer-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--asmaa-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.customer-info {
  flex: 1;
  min-width: 0;
}

.customer-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.customer-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-bottom: 0.25rem;
}

.customer-service {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.customer-staff,
.customer-time {
  font-size: 0.8125rem;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  margin-top: 0.25rem;
}

/* Items Grid */
.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 1rem;
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  max-height: calc(100vh - 450px);
  padding-right: 0.5rem;
  padding-bottom: 1rem;
}

.items-grid::-webkit-scrollbar {
  width: 6px;
}

.items-grid::-webkit-scrollbar-track {
  background: var(--bg-secondary);
  border-radius: 3px;
}

.items-grid::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 3px;
}

.items-grid::-webkit-scrollbar-thumb:hover {
  background: var(--asmaa-primary);
}

.item-card {
  padding: 1.25rem;
  background: var(--bg-secondary);
  border-radius: 12px;
  border: 2px solid var(--border-color);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  text-align: center;
  position: relative;
  overflow: hidden;
}

.item-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--asmaa-primary);
  transform: scaleX(0);
  transition: transform 0.2s;
}

.item-card:hover {
  border-color: var(--asmaa-primary);
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(187, 160, 122, 0.2);
}

.item-card:hover::before {
  transform: scaleX(1);
}

.item-card.out-of-stock {
  opacity: 0.5;
  cursor: not-allowed;
}

.item-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.item-price {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--asmaa-primary);
  margin-bottom: 0.5rem;
}

.item-category,
.item-stock,
.item-sku {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 0.25rem;
}

/* Cart */
.cart-items {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  margin-bottom: 1rem;
  max-height: calc(100vh - 650px);
  min-height: 200px;
  padding-right: 0.5rem;
}

.cart-items::-webkit-scrollbar {
  width: 6px;
}

.cart-items::-webkit-scrollbar-track {
  background: var(--bg-secondary);
  border-radius: 3px;
}

.cart-items::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 3px;
}

.cart-items::-webkit-scrollbar-thumb:hover {
  background: var(--asmaa-primary);
}

.cart-items-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.cart-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  transition: all 0.2s;
}

.cart-item:hover {
  background: var(--bg-tertiary);
  border-color: var(--asmaa-primary);
  box-shadow: 0 2px 8px rgba(187, 160, 122, 0.1);
}

.cart-item-info {
  flex: 1;
  min-width: 0;
}

.cart-item-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.cart-item-meta {
  font-size: 0.8125rem;
  color: var(--text-muted);
  display: flex;
  align-items: center;
}

.cart-item-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.cart-item-quantity {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.quantity-value {
  min-width: 30px;
  text-align: center;
  font-weight: 600;
}

.cart-item-price {
  font-weight: 600;
  color: var(--asmaa-primary);
  min-width: 80px;
  text-align: right;
}

.cart-summary {
  border-top: 2px solid var(--border-color);
  padding-top: 1rem;
  margin-bottom: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.summary-row.total-row {
  border-top: 1px solid var(--border-color);
  padding-top: 0.75rem;
  margin-top: 0.75rem;
}

.total-amount {
  font-size: 1.25rem;
  color: var(--asmaa-primary);
}

.discount-input {
  max-width: 120px;
}

.cart-actions {
  margin-top: 1rem;
}

/* Force primary color on all buttons */
.pos-page .btn-primary,
.pos-page .btn-primary:hover,
.pos-page .btn-primary:focus,
.pos-page .btn-primary:active,
.pos-page .btn-primary.active {
  background-color: var(--asmaa-primary) !important;
  border-color: var(--asmaa-primary) !important;
  color: white !important;
}

.pos-page .btn-outline-primary,
.pos-page .btn-outline-primary:hover,
.pos-page .btn-outline-primary:focus,
.pos-page .btn-outline-primary:active {
  color: var(--asmaa-primary) !important;
  border-color: var(--asmaa-primary) !important;
  background-color: transparent !important;
}

.pos-page .btn-outline-primary:hover {
  background-color: rgba(187, 160, 122, 0.1) !important;
}

.pos-page .btn-ghost-primary,
.pos-page .btn-ghost-primary:hover,
.pos-page .btn-ghost-primary:focus {
  color: var(--asmaa-primary) !important;
  background-color: transparent !important;
  border-color: transparent !important;
}

.pos-page .btn-ghost-primary:hover {
  background-color: rgba(187, 160, 122, 0.1) !important;
}

/* Badge primary color */
.pos-page .badge-primary {
  background-color: var(--asmaa-primary) !important;
  color: white !important;
}

/* Tabs styling with primary color */
.pos-column-2 .nav-tabs {
  border-bottom: 2px solid var(--border-color);
}

.pos-column-2 .nav-tabs .nav-link {
  color: var(--text-secondary);
  border: none;
  border-bottom: 2px solid transparent;
  padding: 0.75rem 1rem;
  transition: all 0.2s;
}

.pos-column-2 .nav-tabs .nav-link:hover {
  color: var(--asmaa-primary);
  border-bottom-color: var(--asmaa-primary);
  background: rgba(187, 160, 122, 0.05);
}

.pos-column-2 .nav-tabs .nav-link.active {
  color: var(--asmaa-primary);
  border-bottom-color: var(--asmaa-primary);
  background: rgba(187, 160, 122, 0.1);
  font-weight: 600;
}

/* Card improvements */
.pos-column .card {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.pos-column .card-body {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* Better spacing */
.pos-column-2 .card {
  margin-bottom: 1rem;
}

.pos-column-2 .card:last-child {
  margin-bottom: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.pos-column-2 .card:last-child .card-body {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* Responsive */
@media (max-width: 1600px) {
  .pos-layout {
    grid-template-columns: 300px 1fr 380px;
  }
}

@media (max-width: 1400px) {
  .pos-layout {
    grid-template-columns: 280px 1fr 360px;
  }
  
  .items-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  }
}

@media (max-width: 1200px) {
  .pos-layout {
    grid-template-columns: 1fr;
    min-height: auto;
  }
  
  .pos-column {
    min-width: 100%;
    min-height: auto;
  }
  
  .active-customers-list,
  .items-grid,
  .cart-items {
    max-height: 500px;
  }
}

@media (max-width: 768px) {
  .pos-page {
    gap: 1rem;
  }
  
  .pos-layout {
    gap: 1rem;
  }
  
  .items-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 0.75rem;
  }
  
  .active-customer-item {
    padding: 0.75rem;
  }
  
  .cart-item {
    padding: 0.75rem;
    flex-wrap: wrap;
  }
}
</style>
