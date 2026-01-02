import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';

export const usePOSStore = defineStore('pos', () => {
  // State
  const products = ref([]);
  const services = ref([]);
  const staff = ref([]);
  const customers = ref([]);
  const memberships = ref([]); // New: Memberships Catalog
  const activeCustomers = ref([]); // Combined from bookings and queue
  const bookings = ref([]);
  const queueTickets = ref([]);
  const orders = ref([]);
  const invoices = ref([]);
  const payments = ref([]);
  const commissions = ref([]);
  const workerCalls = ref([]);
  const openSession = ref(null);
  
  // Cart State
  const cart = ref([]);
  const selectedCustomerId = ref(null);
  const discount = ref(0);
  const paymentMethod = ref('cash');
  const prepaidAmount = ref(0);
  const discountReason = ref('');
  
  // Advanced Receptionist States
  const splitPayments = ref([
    { method: 'cash', amount: 0 },
    { method: 'knet', amount: 0 },
    { method: 'credit_card', amount: 0 },
    { method: 'wallet', amount: 0 }
  ]);
  const customerAlerts = ref([]);
  const lastVisitDetails = ref({
    date: null,
    services: [],
    total: 0,
    staff_name: ''
  });
  const popularity = ref({ services: [], products: [] });
  const lastServices = ref([]);
  
  // Loading States
  const loading = ref({
    products: false,
    services: false,
    staff: false,
    customers: false,
    activeCustomers: false,
    session: false,
    loyalty: false,
    finance: false
  });

  // Computed
  const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
  });

  const total = computed(() => {
    return Math.max(0, subtotal.value - discount.value - prepaidAmount.value);
  });

  const potentialPoints = computed(() => {
    // Basic calculation: 1 point per 1 KWD, can be refined based on settings
    return Math.floor(total.value);
  });

  const selectedCustomer = computed(() => {
    if (!selectedCustomerId.value) return null;
    return customers.value.find(c => Number(c.id) === Number(selectedCustomerId.value)) || 
           activeCustomers.value.find(c => Number(c.id) === Number(selectedCustomerId.value));
  });

  // Actions
  function setSplitPaymentAmount(method, amount) {
    const item = splitPayments.value.find(p => p.method === method);
    if (item) {
      item.amount = Number(amount);
    }
  }

  function clearSplitPayments() {
    splitPayments.value = [
      { method: 'cash', amount: 0 },
      { method: 'knet', amount: 0 },
      { method: 'credit_card', amount: 0 },
      { method: 'wallet', amount: 0 }
    ];
  }

  function addCustomerAlert(alert) {
    customerAlerts.value.push(alert);
  }

  function clearCustomerAlerts() {
    customerAlerts.value = [];
  }

  function setLastVisitDetails(details) {
    lastVisitDetails.value = details;
  }

  /**
   * Generic prefetch for Rule #4
   */
  async function prefetch() {
    return await fetchAllData();
  }

  async function fetchAllData() {
    loading.value.products = true;
    loading.value.services = true;
    loading.value.staff = true;
    loading.value.customers = true;
    loading.value.activeCustomers = true;
    loading.value.session = true;

    try {
      const [
        productsRes, 
        servicesRes, 
        staffRes, 
        customersRes, 
        membershipsRes,
        posRes, 
        sessionRes
      ] = await Promise.all([
        api.get('/products', { params: { per_page: 1000, is_active: 1 } }),
        api.get('/services', { params: { per_page: 1000, is_active: 1 } }),
        api.get('/staff', { params: { per_page: 1000, status: 'active' } }),
        api.get('/customers', { params: { per_page: 1000 } }),
        api.get('/memberships/plans', { params: { is_active: 1 } }), // New
        api.get('/pos'),
        api.get('/pos/session')
      ]);

      products.value = productsRes.data?.data?.items || productsRes.data?.items || [];
      services.value = servicesRes.data?.data?.items || servicesRes.data?.items || [];
      staff.value = staffRes.data?.data?.items || staffRes.data?.items || [];
      customers.value = customersRes.data?.data?.items || customersRes.data?.items || [];
      memberships.value = membershipsRes.data?.data?.items || membershipsRes.data?.items || [];
      
      const posData = posRes.data?.data || posRes.data || {};
      activeCustomers.value = posData.active_customers || [];
      bookings.value = posData.bookings || [];
      queueTickets.value = posData.queue_tickets || [];
      popularity.value = posData.popularity || { services: [], products: [] };
      
      openSession.value = sessionRes.data?.data || sessionRes.data || null;
    } catch (error) {
      console.error('Error fetching POS data:', error);
    } finally {
      Object.keys(loading.value).forEach(key => loading.value[key] = false);
    }
  }

  async function fetchLoyaltyData(customerId) {
    if (!customerId) return null;
    loading.value.loyalty = true;
    try {
      const response = await api.get(`/customers/${customerId}/loyalty`);
      return response.data?.data || response.data;
    } catch (error) {
      console.error('Error fetching loyalty data:', error);
      return null;
    } finally {
      loading.value.loyalty = false;
    }
  }

  async function fetchCustomerFinancials(customerId) {
    if (!customerId) return;
    loading.value.finance = true;
    try {
      const [invoicesRes, paymentsRes, prepaidRes] = await Promise.all([
        api.get('/invoices', { params: { customer_id: customerId, limit: 5 } }),
        api.get('/payments', { params: { customer_id: customerId, limit: 5 } }),
        api.get(`/pos/prepaid/${customerId}`)
      ]);
      
      invoices.value = invoicesRes.data?.data?.items || invoicesRes.data?.items || [];
      payments.value = paymentsRes.data?.data?.items || paymentsRes.data?.items || [];
      prepaidAmount.value = prepaidRes.data?.data?.total_prepaid || 0;
    } catch (error) {
      console.error('Error fetching financial data:', error);
    } finally {
      loading.value.finance = false;
    }
  }

  function addToCart(item) {
    // Check if item already exists in cart with same type, id, staff, AND note
    const existingIndex = cart.value.findIndex(i => 
      i.type === item.type && 
      (item.type === 'product' ? i.product_id === item.product_id : i.service_id === item.service_id) &&
      i.staff_id === item.staff_id &&
      (i.note || '') === (item.note || '')
    );

    if (existingIndex > -1 && !item.is_custom) {
      cart.value[existingIndex].quantity += item.quantity || 1;
    } else {
      cart.value.push({
        ...item,
        quantity: item.quantity || 1,
        note: item.note || ''
      });
    }
  }

  function removeFromCart(index) {
    cart.value.splice(index, 1);
  }

  function clearCart() {
    cart.value = [];
    discount.value = 0;
    discountReason.value = '';
    prepaidAmount.value = 0;
  }

  function repeatLastVisit() {
    if (!lastVisitDetails.value || !lastVisitDetails.value.services) return;
    
    lastVisitDetails.value.services.forEach(serviceName => {
      // Find service in catalog by name
      const service = services.value.find(s => (s.name_ar || s.name) === serviceName);
      if (service) {
        addToCart({
          type: 'service',
          service_id: service.id,
          name: service.name_ar || service.name,
          unit_price: parseFloat(service.price || 0),
          quantity: 1,
          staff_id: null, // Receptionist might want to reassign
          staff_name: ''
        });
      }
    });
  }

  return {
    // State
    products,
    services,
    staff,
    customers,
    activeCustomers,
    bookings,
    queueTickets,
    orders,
    invoices,
    payments,
    commissions,
    workerCalls,
    openSession,
    cart,
    selectedCustomerId,
    discount,
    discountReason,
    paymentMethod,
    prepaidAmount,
    loading,
    popularity,
    lastServices,
    
    // Computed
    subtotal,
    total,
    potentialPoints,
    selectedCustomer,
    splitPayments,
    customerAlerts,
    lastVisitDetails,
    
    // Actions
    fetchAllData,
    fetchLoyaltyData,
    fetchCustomerFinancials,
    addToCart,
    removeFromCart,
    clearCart,
    repeatLastVisit,
    setSplitPaymentAmount,
    clearSplitPayments,
    addCustomerAlert,
    clearCustomerAlerts,
    setLastVisitDetails
  };
});

