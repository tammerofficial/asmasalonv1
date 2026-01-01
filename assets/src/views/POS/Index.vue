<template>
  <div class="pos-page">
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-3">
      <div class="header-left d-flex align-items-center gap-3">
        <h4 class="mb-0 fw-bold text-primary">{{ t('pos.title') }}</h4>
        <CBadge color="success" shape="rounded-pill" v-if="posStore.openSession">
          {{ t('pos.sessionOpen') || 'الجلسة مفتوحة' }} (#{{ posStore.openSession.id }})
        </CBadge>
      </div>
      <div class="header-right d-flex align-items-center gap-3">
        <CButton color="info" variant="ghost" @click="showAnalyticsModal = true">
          <CIcon icon="cil-chart-line" />
        </CButton>
        <NotificationsBell />
        <CButton color="secondary" variant="ghost" @click="showQuickSettings = true">
          <CIcon icon="cil-settings" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Header) -->
    <QuickStatsBar 
      :session="posStore.openSession" 
      :activeCustomersCount="posStore.activeCustomers.length"
    />

    <!-- Main POS Layout (3 Columns) -->
    <div class="pos-layout">
      
      <!-- Column 1: Operations (Customers, Bookings, Queue) -->
      <div class="pos-column pos-operations">
        <Card class="operations-card h-100">
          <template #header>
            <div class="d-flex justify-content-between align-items-center w-100">
              <span class="fw-bold">{{ t('pos.operations') || 'العمليات' }}</span>
              <CButton size="sm" color="primary" variant="ghost" @click="posStore.fetchAllData">
                <CIcon icon="cil-reload" />
              </CButton>
            </div>
          </template>

          <CNav variant="pills" class="flex-column operations-nav">
            <!-- Arrived Hub (The Arrival Button) -->
            <div class="operation-section mb-3">
              <div class="section-title d-flex justify-content-between align-items-center mb-2">
                <span>
                  <CIcon icon="cil-room" class="me-2 text-primary" />
                  {{ t('pos.arrivedToday') || 'وصلوا الآن' }}
                </span>
                <CBadge color="primary" shape="rounded-pill">{{ posStore.bookings.filter(b => b.status === 'confirmed').length }}</CBadge>
              </div>
              <div class="arrived-hub bg-tertiary rounded-4 p-2 border-dashed border-primary">
                <div v-if="posStore.bookings.filter(b => b.status === 'confirmed').length === 0" class="small text-muted text-center py-2">
                  {{ t('pos.noArrivals') || 'لا يوجد حجوزات منتظرة' }}
                </div>
                <div v-else class="d-flex flex-column gap-2">
                  <div v-for="b in posStore.bookings.filter(b => b.status === 'confirmed').slice(0,3)" :key="b.id" 
                       class="arrival-quick-card d-flex justify-content-between align-items-center p-2 bg-secondary rounded-3 shadow-sm border-start border-4 border-success"
                       style="cursor: pointer;"
                       @click="handleQuickArrive(b)">
                    <div class="d-flex align-items-center gap-2">
                      <div class="mini-avatar-box">{{ b.customer_name?.charAt(0) }}</div>
                      <div class="info">
                        <div class="name fw-bold small truncate" style="max-width: 80px;">{{ b.customer_name }}</div>
                        <div class="time tiny text-muted">{{ b.booking_time }}</div>
                      </div>
                    </div>
                    <CButton color="success" size="sm" variant="ghost" class="p-1">
                      <CIcon icon="cil-check-circle" />
                    </CButton>
                  </div>
                </div>
              </div>
            </div>

            <!-- Active Customers Section -->
            <div class="operation-section">
              <div class="section-title d-flex justify-content-between align-items-center">
                <span>
                  <CIcon icon="cil-people" class="me-2" />
                  {{ t('pos.activeCustomers') }}
                </span>
                <CButton size="sm" color="success" variant="ghost" @click="showAddCustomerModal = true">
                  <CIcon icon="cil-plus" />
                </CButton>
              </div>
              <div class="operation-list">
                <div 
                  v-for="customer in posStore.activeCustomers" 
                  :key="customer.id"
                  class="operation-item"
                  :class="{ 'selected': Number(posStore.selectedCustomerId) === Number(customer.id) }"
                  @click="selectActiveCustomer(customer)"
                >
                  <div class="item-avatar">{{ customer.name?.charAt(0) || 'C' }}</div>
                  <div class="item-info">
                    <div class="item-name">{{ customer.name }}</div>
                    <div class="item-meta">
                      <CBadge :color="customer.type === 'queue' ? 'info' : 'primary'" size="sm">
                        {{ customer.type === 'queue' ? '#' + customer.ticket_number : t('bookings.title') }}
                      </CBadge>
                      <span class="ms-2">{{ customer.current_service || 'N/A' }}</span>
                    </div>
                  </div>
                </div>
                <div v-if="posStore.activeCustomers.length === 0" class="empty-state-mini">
                  {{ t('pos.noActiveCustomers') }}
                </div>
              </div>
            </div>

            <!-- Bookings Section -->
            <div class="operation-section mt-3">
              <div class="section-title d-flex justify-content-between">
                <span>
                  <CIcon icon="cil-calendar" class="me-2" />
                  {{ t('bookings.title') }}
                </span>
                <router-link to="/bookings" class="text-primary text-decoration-none small">{{ t('common.viewAll') }}</router-link>
              </div>
              <div class="operation-list">
                <BookingCard 
                  v-for="booking in posStore.bookings.slice(0, 3)" 
                  :key="booking.id" 
                  :booking="booking"
                  @process="handleBookingArrive"
                  @arrive="handleBookingArrive"
                />
              </div>
            </div>

            <!-- Queue Section -->
            <div class="operation-section mt-3">
              <div class="section-title d-flex justify-content-between">
                <span>
                  <CIcon icon="cil-list" class="me-2" />
                  {{ t('queue.title') }}
                </span>
                <CButton size="sm" color="primary" variant="ghost" @click="callNext">{{ t('queue.next') }}</CButton>
              </div>
              <div class="operation-list">
                <QueueTicketCard 
                  v-for="ticket in posStore.queueTickets.filter(t => t.status !== 'completed').slice(0, 3)" 
                  :key="ticket.id" 
                  :ticket="ticket"
                  @call="handleCallTicket"
                  @serve="handleServeTicket"
                  @arrive="handleQueueArrive"
                />
              </div>
            </div>

            <!-- Staff Status Section -->
            <StaffStatusWidget :staff="posStore.staff" @call="handleCallStaff" />
          </CNav>
        </Card>
      </div>

      <!-- Column 2: Catalog (Services & Products) -->
      <div class="pos-column pos-catalog">
        <Card class="catalog-card h-100">
          <template #header>
            <!-- VIP / Alert Banner (The Receptionist's eye) -->
            <div v-if="posStore.selectedCustomer && posStore.customerAlerts.length > 0" class="customer-alert-banner bg-danger text-white p-2 rounded-3 mb-2 d-flex align-items-center gap-3 pulse-red shadow-sm">
              <CIcon icon="cil-warning" />
              <div class="alert-content flex-grow-1">
                <div class="small fw-bold">{{ posStore.customerAlerts[0] }}</div>
              </div>
              <CButton color="light" size="sm" variant="ghost" class="py-0 px-1" @click="posStore.customerAlerts = []">X</CButton>
            </div>

            <div class="catalog-header-actions w-100">
              <CInputGroup>
                <CInputGroupText><CIcon icon="cil-magnifying-glass" /></CInputGroupText>
                <CFormInput 
                  v-model="searchQuery" 
                  :placeholder="t('common.search') + '...'"
                  class="border-start-0"
                />
              </CInputGroup>
              
              <CNav variant="pills" class="catalog-tabs mt-3">
                <CNavItem>
                  <CNavLink :active="activeTab === 'services'" @click="activeTab = 'services'">
                    <CIcon icon="cil-spreadsheet" class="me-2" />{{ t('pos.services') }}
                  </CNavLink>
                </CNavItem>
                <CNavItem>
                  <CNavLink :active="activeTab === 'products'" @click="activeTab = 'products'">
                    <CIcon icon="cil-basket" class="me-2" />{{ t('pos.products') }}
                  </CNavLink>
                </CNavItem>
                <CNavItem>
                  <CNavLink :active="activeTab === 'memberships'" @click="activeTab = 'memberships'">
                    <CIcon icon="cil-gem" class="me-2" />{{ t('memberships.title') || 'العضويات' }}
                  </CNavLink>
                </CNavItem>
                <CNavItem>
                  <CNavLink :active="activeTab === 'favorites'" @click="activeTab = 'favorites'">
                    <CIcon icon="cil-star" class="me-2" />{{ t('common.favorites') || 'المفضلة' }}
                  </CNavLink>
                </CNavItem>
              </CNav>
            </div>
          </template>

          <!-- Smart Upsell Recommendations (Receptionist Magic) -->
          <div v-if="posStore.cart.length > 0 && activeTab === 'services'" class="smart-upsell-bar px-3 py-2 border-bottom bg-light d-flex align-items-center gap-3 overflow-auto">
            <div class="upsell-title small fw-bold text-nowrap text-primary">
              <CIcon icon="cil-lightbulb" class="me-1" />
              {{ t('pos.smartUpsellTitle') || 'هل فكرتِ في هذا؟' }}
            </div>
            <div class="upsell-items d-flex gap-2">
              <CBadge 
                v-for="item in upsellRecommendations" 
                :key="item.id" 
                color="info" 
                shape="rounded-pill" 
                variant="outline"
                class="upsell-badge"
                style="cursor: pointer"
                @click="handleItemClick(item)"
              >
                + {{ item.name_ar || item.name }}
              </CBadge>
            </div>
          </div>

          <div class="catalog-grid-wrapper">
            <LoadingSpinner v-if="posStore.loading.products || posStore.loading.services" />
            
            <div v-else class="catalog-grid">
              <div 
                v-for="item in filteredItems" 
                :key="item.id"
                class="catalog-item-card"
                :class="{ 'out-of-stock': activeTab === 'products' && (item.stock_quantity || 0) <= 0 }"
                @click="handleItemClick(item)"
              >
                <div class="item-category-badge" v-if="item.category">{{ item.category }}</div>
                <div class="item-name">{{ item.name_ar || item.name }}</div>
                <div class="item-price">{{ formatCurrency(item.price || item.selling_price || 0) }}</div>
                <div class="item-stock" v-if="activeTab === 'products'">
                  {{ t('products.stock') }}: {{ item.stock_quantity || 0 }}
                </div>
                <div class="quick-add">
                  <CIcon icon="cil-plus" />
                </div>
              </div>
            </div>

            <EmptyState 
              v-if="!filteredItems.length" 
              :title="t('common.noResults')" 
              icon="cil-search" 
            />
          </div>
        </Card>
      </div>

      <!-- Column 3: Checkout (Cart, Loyalty, Payment) -->
      <div class="pos-column pos-checkout">
        <Card class="checkout-card h-100">
          <template #header>
            <div class="fw-bold">{{ t('pos.cart') }}</div>
          </template>

          <!-- Selected Customer Info -->
          <div class="checkout-customer-section">
            <CustomerQuickView 
              v-if="posStore.selectedCustomer" 
              :customer="posStore.selectedCustomer"
              :customer-alerts="posStore.customerAlerts"
              :last-visit="posStore.lastVisitDetails"
              @view-profile="goToCustomerProfile"
              @view-history="showHistoryModal = true"
              @send-wallet-pass="handleSendWalletPass"
            />
            
            <div v-else class="customer-selection-empty">
              <CFormSelect v-model="posStore.selectedCustomerId" @change="onCustomerSelect">
                <option :value="null">{{ t('pos.walkInCustomer') }}</option>
                <option v-for="c in posStore.customers" :key="c.id" :value="c.id">
                  {{ c.name }} - {{ c.phone }}
                </option>
              </CFormSelect>
            </div>

            <!-- Last Visited Services (Insight for Receptionist) -->
            <div v-if="posStore.selectedCustomer && posStore.lastServices.length > 0" class="last-services-box mt-3 p-3 bg-tertiary rounded-4 border">
              <div class="small fw-bold text-muted mb-2 d-flex align-items-center">
                <CIcon icon="cil-history" class="me-2" /> {{ t('pos.lastVisited') || 'آخر خدمات' }}
              </div>
              <div class="d-flex flex-wrap gap-2">
                <CBadge v-for="s in posStore.lastServices" :key="s.id" color="secondary" shape="rounded-pill" class="fw-normal" style="cursor: pointer;" @click="handleItemClick(s)">
                  {{ s.name }}
                </CBadge>
              </div>
            </div>
          </div>

          <!-- Cart Items -->
          <div class="cart-items-section">
            <div v-if="posStore.cart.length === 0" class="empty-cart-display">
              <CIcon icon="cil-cart" class="mb-2" size="xl" />
              <div>{{ t('pos.emptyCartDesc') }}</div>
            </div>
            
            <div v-else class="cart-list">
              <div v-for="(item, index) in posStore.cart" :key="index" class="cart-item-wrapper">
                <div class="cart-item">
                  <div class="item-info">
                    <div class="item-name">{{ item.name }}</div>
                    <div class="item-staff" v-if="item.staff_name">
                      <CIcon icon="cil-user" class="me-1" />{{ item.staff_name }}
                    </div>
                  </div>
                  <div class="item-controls">
                    <div class="quantity-picker">
                      <button @click="updateQty(index, -1)">-</button>
                      <span>{{ item.quantity }}</span>
                      <button @click="updateQty(index, 1)">+</button>
                    </div>
                    <div class="item-total">{{ formatCurrency(item.unit_price * item.quantity) }}</div>
                    <CButton size="sm" color="danger" variant="ghost" @click="posStore.removeFromCart(index)">
                      <CIcon icon="cil-trash" />
                    </CButton>
                  </div>
                </div>
                <!-- Cart Item Note (Receptionist Secret) -->
                <div class="cart-item-note">
                  <CFormInput 
                    size="sm" 
                    v-model="item.note" 
                    :placeholder="t('pos.itemNote') || 'ملاحظة خاصة بالخدمة...'"
                    class="border-0 bg-transparent py-0 px-2 tiny"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Checkout Summary -->
          <div class="checkout-footer">
            <div class="summary-details">
              <div class="summary-line">
                <span>{{ t('pos.subtotal') }}</span>
                <span>{{ formatCurrency(posStore.subtotal) }}</span>
              </div>
              <div class="summary-line" v-if="posStore.prepaidAmount > 0">
                <span>{{ t('pos.prepaid') }}</span>
                <span class="text-success">-{{ formatCurrency(posStore.prepaidAmount) }}</span>
              </div>
              <div class="summary-line discount-line">
                <span>{{ t('pos.discount') }}</span>
                <CFormInput v-model.number="posStore.discount" type="number" step="0.5" size="sm" class="summary-input" />
              </div>
              <div class="summary-line total-line">
                <span>{{ t('pos.total') }}</span>
                <span class="total-amount">{{ formatCurrency(posStore.total) }}</span>
              </div>
            </div>

            <!-- Loyalty Points Preview (Receptionist Insight) -->
            <div v-if="posStore.selectedCustomer && posStore.potentialPoints > 0" class="loyalty-preview-banner mt-2 p-2 rounded-3 text-center small fw-bold">
              <CIcon icon="cil-star" class="text-warning me-1" />
              {{ t('pos.pointsEarnedPreview', { points: posStore.potentialPoints }) || `ستكسبين ${posStore.potentialPoints} نقطة من هذا الطلب` }}
            </div>

            <!-- Payment Methods -->
            <div class="payment-section mt-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small fw-bold text-muted uppercase">{{ t('pos.paymentMethod') }}</div>
                <CButton color="primary" variant="ghost" size="sm" @click="showSplitPayment = !showSplitPayment">
                  {{ showSplitPayment ? t('pos.singlePayment') : t('pos.splitPayment') || 'دفع متعدد' }}
                </CButton>
              </div>

              <div v-if="showSplitPayment" class="split-payment-grid bg-tertiary p-3 rounded-4 border mb-3">
                <div v-for="pay in posStore.splitPayments" :key="pay.method" class="mb-2 d-flex align-items-center gap-2">
                  <CIcon :icon="getPaymentIcon(pay.method)" :class="getPaymentColor(pay.method)" />
                  <CFormInput 
                    :value="pay.amount" 
                    @input="e => posStore.setSplitPaymentAmount(pay.method, e.target.value)"
                    type="number" 
                    size="sm" 
                    :placeholder="t(`pos.payment_${pay.method}`) || pay.method" 
                  />
                </div>
                <div class="remaining-balance tiny text-center fw-bold" :class="remainingSplitBalance === 0 ? 'text-success' : 'text-danger'">
                  {{ t('pos.remaining') || 'المتبقي' }}: {{ formatCurrency(remainingSplitBalance) }}
                </div>
              </div>

              <PaymentMethodsGrid v-else v-model="posStore.paymentMethod" />
            </div>

            <!-- Final Actions -->
            <div class="checkout-actions">
              <CButton color="primary" class="w-100 checkout-btn py-3" :disabled="!posStore.cart.length || isProcessing" @click="handleCheckout">
                <CIcon v-if="isProcessing" icon="cil-reload" class="spinning me-2" />
                <CIcon v-else icon="cil-check-circle" class="me-2" />
                {{ isProcessing ? t('pos.processing') : t('pos.processOrder') }}
              </CButton>
              <CButton color="secondary" variant="ghost" class="w-100 mt-2" @click="posStore.clearCart">
                {{ t('pos.clearCart') }}
              </CButton>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Modals -->
    <CModal :visible="showAnalyticsModal" @close="showAnalyticsModal = false" size="lg" alignment="center">
      <CModalHeader>
        <CModalTitle>{{ t('analytics.title') || 'التحليلات الحية' }}</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <LiveAnalyticsDashboard :session="posStore.openSession" />
      </CModalBody>
    </CModal>

    <CModal :visible="showHistoryModal" @close="showHistoryModal = false" size="xl" alignment="center">
      <CModalHeader>
        <CModalTitle>{{ t('common.history') || 'السجل' }} - {{ posStore.selectedCustomer?.name }}</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CNav variant="tabs" class="mb-3">
          <CNavItem><CNavLink :active="historyTab === 'orders'" @click="historyTab = 'orders'">{{ t('orders.title') }}</CNavLink></CNavItem>
          <CNavItem><CNavLink :active="historyTab === 'invoices'" @click="historyTab = 'invoices'">{{ t('invoices.title') }}</CNavLink></CNavItem>
          <CNavItem><CNavLink :active="historyTab === 'payments'" @click="historyTab = 'payments'">{{ t('payments.title') }}</CNavLink></CNavItem>
        </CNav>

        <div v-if="historyTab === 'orders'" class="history-list">
          <div v-for="order in posStore.orders" :key="order.id" class="history-item border rounded p-3 mb-2">
            <div class="d-flex justify-content-between">
              <span class="fw-bold">#{{ order.order_number }}</span>
              <span class="text-primary fw-bold">{{ formatCurrency(order.total) }}</span>
            </div>
            <div class="d-flex justify-content-between mt-1">
              <span class="small text-muted">{{ order.created_at }}</span>
              <CBadge :color="order.status === 'completed' ? 'success' : 'warning'">{{ order.status }}</CBadge>
            </div>
          </div>
          <EmptyState v-if="!posStore.orders.length" :title="t('orders.noOrders')" icon="cil-cart" />
        </div>

        <div v-if="historyTab === 'invoices'" class="history-list">
          <div v-for="invoice in posStore.invoices" :key="invoice.id" class="history-item border rounded p-3 mb-2">
            <div class="d-flex justify-content-between">
              <span class="fw-bold">#{{ invoice.invoice_number }}</span>
              <span class="text-primary fw-bold">{{ formatCurrency(invoice.total_amount) }}</span>
            </div>
            <div class="d-flex justify-content-between mt-1">
              <span class="small text-muted">{{ invoice.created_at }}</span>
              <CBadge :color="invoice.status === 'paid' ? 'success' : 'danger'">{{ invoice.status }}</CBadge>
            </div>
          </div>
          <EmptyState v-if="!posStore.invoices.length" :title="t('invoices.noInvoices')" icon="cil-file" />
        </div>

        <div v-if="historyTab === 'payments'" class="history-list">
          <div v-for="payment in posStore.payments" :key="payment.id" class="history-item border rounded p-3 mb-2">
            <div class="d-flex justify-content-between">
              <span class="fw-bold">{{ payment.payment_method }}</span>
              <span class="text-success fw-bold">{{ formatCurrency(payment.amount) }}</span>
            </div>
            <div class="d-flex justify-content-between mt-1">
              <span class="small text-muted">{{ payment.created_at }}</span>
              <CBadge color="info">#{{ payment.id }}</CBadge>
            </div>
          </div>
          <EmptyState v-if="!posStore.payments.length" :title="t('payments.noPayments')" icon="cil-money" />
        </div>
      </CModalBody>
    </CModal>

    <CModal :visible="showItemModal" @close="showItemModal = false" size="lg" alignment="center">
      <CModalHeader>
        <CModalTitle>{{ activeTab === 'services' ? t('pos.addService') : t('pos.addProduct') }}</CModalTitle>
      </CModalHeader>
      <CModalBody v-if="selectedItem">
        <CRow>
          <CCol md="6">
            <div class="item-preview-card">
              <div class="item-name h4">{{ selectedItem.name_ar || selectedItem.name }}</div>
              <div class="item-price h3 text-primary">{{ formatCurrency(selectedItem.price || selectedItem.selling_price || 0) }}</div>
              <hr />
              <div class="mb-3">
                <label class="form-label small fw-bold text-muted">{{ t('pos.staff') }}</label>
                <CFormSelect v-model="selectedStaffId">
                  <option value="">{{ t('pos.selectStaff') }}</option>
                  <option v-for="s in posStore.staff" :key="s.id" :value="s.id">
                    {{ s.name }} ({{ getStaffCurrentLoad(s.id) }} {{ t('pos.activeCustomers') }})
                    {{ Number(s.id) === Number(bestStaffId) ? ' ✨ ' + (t('pos.bestStaffMatch') || 'الأكثر توافراً') : '' }}
                  </option>
                </CFormSelect>
                <div v-if="estimatedCommission > 0" class="mt-2 small text-success fw-bold">
                  <CIcon icon="cil-money" class="me-1" />
                  {{ t('commissions.estimated') || 'العمولة المقدرة' }}: {{ formatCurrency(estimatedCommission) }}
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold text-muted">{{ t('pos.quantity') }}</label>
                <div class="quantity-input-large">
                  <CButton color="secondary" variant="outline" @click="modalQty > 1 ? modalQty-- : null">-</CButton>
                  <CFormInput v-model.number="modalQty" type="number" min="1" class="text-center" />
                  <CButton color="secondary" variant="outline" @click="modalQty++">+</CButton>
                </div>
              </div>
            </div>
          </CCol>
          <CCol md="6">
            <!-- Loyalty & Offers placeholder -->
            <div class="offers-section">
              <div class="section-title small fw-bold text-muted mb-2 uppercase">{{ t('loyalty.offers') || 'العروض المتاحة' }}</div>
              <div class="empty-state-mini border rounded p-3 text-center">
                {{ t('loyalty.noOffers') || 'لا توجد عروض لهذه الخدمة حالياً' }}
              </div>
            </div>
          </CCol>
        </CRow>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" variant="ghost" @click="showItemModal = false">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" @click="confirmAddItem">{{ t('pos.addToCart') }}</CButton>
      </CModalFooter>
    </CModal>

    <!-- Quick Add Customer Modal -->
    <CModal :visible="showAddCustomerModal" @close="showAddCustomerModal = false" alignment="center">
      <CModalHeader>
        <CModalTitle>{{ t('customers.add') || 'إضافة عميلة جديدة' }}</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <form @submit.prevent="handleAddCustomer">
          <div class="mb-3">
            <label class="form-label">{{ t('customers.name') }}</label>
            <CFormInput v-model="newCustomer.name" required />
          </div>
          <div class="mb-3">
            <label class="form-label">{{ t('customers.phone') }}</label>
            <CFormInput v-model="newCustomer.phone" required />
          </div>
          <div class="mb-3">
            <label class="form-label">{{ t('customers.email') }}</label>
            <CFormInput v-model="newCustomer.email" type="email" />
          </div>
          <CButton type="submit" color="primary" class="w-100" :disabled="isSavingCustomer">
            <CIcon v-if="isSavingCustomer" icon="cil-reload" class="spinning me-2" />
            {{ t('common.save') }}
          </CButton>
        </form>
      </CModalBody>
    </CModal>

    <!-- Invoice Preview Modal -->
    <CModal :visible="showInvoicePreview" @close="showInvoicePreview = false" size="lg" alignment="center">
      <CModalHeader>
        <CModalTitle>{{ t('pos.invoicePreview') || 'معاينة الفاتورة' }}</CModalTitle>
      </CModalHeader>
      <CModalBody v-if="lastProcessedOrder" class="p-0">
        <div class="invoice-container p-4" id="printable-invoice">
          <div class="invoice-header text-center mb-4">
            <h3 class="fw-bold">SALON ASMAA</h3>
            <div>{{ t('pos.receipt') }} #{{ lastProcessedOrder.order_number }}</div>
            <div class="small">{{ new Date().toLocaleString() }}</div>
          </div>
          <hr />
          <div class="invoice-items mb-4">
            <div v-for="item in lastProcessedOrder.items" :key="item.id" class="d-flex justify-content-between mb-2">
              <span>{{ item.name }} x {{ item.quantity }}</span>
              <span>{{ formatCurrency(item.total) }}</span>
            </div>
          </div>
          <hr />
          <div class="invoice-summary">
            <div class="d-flex justify-content-between">
              <span>{{ t('pos.subtotal') }}</span>
              <span>{{ formatCurrency(lastProcessedOrder.subtotal) }}</span>
            </div>
            <div class="d-flex justify-content-between text-danger" v-if="lastProcessedOrder.discount > 0">
              <span>{{ t('pos.discount') }}</span>
              <span>-{{ formatCurrency(lastProcessedOrder.discount) }}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold h4 mt-2">
              <span>{{ t('pos.total') }}</span>
              <span>{{ formatCurrency(lastProcessedOrder.total) }}</span>
            </div>
          </div>
          <div class="invoice-footer text-center mt-5">
            <p>{{ t('pos.thankYou') || 'شكراً لزيارتكم' }}</p>
          </div>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="showInvoicePreview = false">{{ t('common.close') }}</CButton>
        <CButton color="primary" @click="window.print()">
          <CIcon icon="cil-print" class="me-2" />
          {{ t('common.print') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Quick Settings Modal -->
    <CModal :visible="showQuickSettings" @close="showQuickSettings = false" alignment="center">
      <CModalHeader>
        <CModalTitle>{{ t('settings.title') || 'الإعدادات السريعة' }}</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <div class="settings-list">
          <div class="setting-item d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
            <div>
              <div class="fw-bold">{{ t('pos.autoPrint') || 'طباعة تلقائية' }}</div>
              <div class="small text-muted">{{ t('pos.autoPrintDesc') || 'طباعة الفاتورة فور إتمام الطلب' }}</div>
            </div>
            <CFormSwitch />
          </div>
          <div class="setting-item d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
            <div>
              <div class="fw-bold">{{ t('pos.soundNotifications') || 'تنبيهات صوتية' }}</div>
              <div class="small text-muted">{{ t('pos.soundNotificationsDesc') || 'تفعيل الصوت عند استدعاء الطابور' }}</div>
            </div>
            <CFormSwitch :checked="true" />
          </div>
          <hr />
          <CButton color="danger" variant="outline" class="w-100" @click="handleCloseSession">
            <CIcon icon="cil-lock-locked" class="me-2" />
            {{ t('pos.closeSession') || 'إغلاق الجلسة' }}
          </CButton>
        </div>
      </CModalBody>
    </CModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { 
  CButton, CBadge, CFormInput, CFormSelect, CInputGroup, CInputGroupText, 
  CModal, CModalHeader, CModalTitle, CModalBody, CModalFooter,
  CNav, CNavItem, CNavLink, CRow, CCol 
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import { usePOSStore } from '@/stores/posStore';
import { usePOSIntegration } from '@/composables/usePOSIntegration';

// Components
import PageHeader from '@/components/UI/PageHeader.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import NotificationsBell from '@/components/Notifications/NotificationsBell.vue';
import QuickStatsBar from './Components/QuickStatsBar.vue';
import CustomerQuickView from './Components/CustomerQuickView.vue';
import BookingCard from './Components/BookingCard.vue';
import QueueTicketCard from './Components/QueueTicketCard.vue';
import PaymentMethodsGrid from './Components/PaymentMethodsGrid.vue';
import StaffStatusWidget from './Components/StaffStatusWidget.vue';
import LiveAnalyticsDashboard from './Components/LiveAnalyticsDashboard.vue';

const { t } = useTranslation();
const toast = useToast();
const router = useRouter();
const posStore = usePOSStore();
const { 
  isProcessing, 
  selectActiveCustomer, 
  processCheckout, 
  formatCurrency,
  processBookingArrival,
  processQueueTicketArrival,
  callNextInQueue,
  callSpecificTicket,
  serveTicket
} = usePOSIntegration();

// Local State
const activeTab = ref('services');
const searchQuery = ref('');
const showItemModal = ref(false);
const showHistoryModal = ref(false);
const showAddCustomerModal = ref(false);
const showQuickSettings = ref(false);
const showAnalyticsModal = ref(false);
const showInvoicePreview = ref(false);
const showSplitPayment = ref(false); // New receptionist feature
const lastProcessedOrder = ref(null);
const isSavingCustomer = ref(false);
const newCustomer = ref({ name: '', phone: '', email: '' });
const historyTab = ref('orders');
const selectedItem = ref(null);
const selectedStaffId = ref('');
const modalQty = ref(1);

// Computed
const remainingSplitBalance = computed(() => {
  const paid = posStore.splitPayments.reduce((sum, p) => sum + (Number(p.amount) || 0), 0);
  return posStore.total - paid;
});

const getPaymentIcon = (method) => {
  switch (method) {
    case 'cash': return 'cil-money';
    case 'knet': return 'cil-credit-card';
    case 'credit_card': return 'cil-bank';
    case 'wallet': return 'cil-wallet';
    default: return 'cil-money';
  }
};

const getPaymentColor = (method) => {
  switch (method) {
    case 'cash': return 'text-success';
    case 'knet': return 'text-info';
    case 'credit_card': return 'text-primary';
    case 'wallet': return 'text-warning';
    default: return '';
  }
};

const bestStaffId = computed(() => {
  if (!posStore.staff.length) return null;
  // Sort staff by current load and pick the first one
  const sorted = [...posStore.staff].sort((a, b) => getStaffCurrentLoad(a.id) - getStaffCurrentLoad(b.id));
  return sorted[0].id;
});

const estimatedCommission = computed(() => {
  if (!selectedStaffId.value || !selectedItem.value) return 0;
  const staffMember = posStore.staff.find(s => Number(s.id) === Number(selectedStaffId.value));
  if (!staffMember) return 0;
  
  const price = parseFloat(selectedItem.value.price || selectedItem.value.selling_price || 0);
  const rate = parseFloat(staffMember.commission_rate || 10); // Default 10%
  return (price * modalQty.value) * (rate / 100);
});

const filteredItems = computed(() => {
  let list = [];
  if (activeTab.value === 'services') list = posStore.services;
  else if (activeTab.value === 'products') list = posStore.products;
  else if (activeTab.value === 'memberships') list = posStore.memberships;
  
  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(item => 
    (item.name_ar || item.name || '').toLowerCase().includes(q) ||
    (item.category || '').toLowerCase().includes(q) ||
    (item.sku || '').toLowerCase().includes(q)
  );
});

// Methods
const upsellRecommendations = computed(() => {
  if (posStore.cart.length === 0) return [];
  
  // Logic: Find items not in cart that are 'popular' or complementary
  const inCartIds = posStore.cart.map(i => i.service_id || i.product_id);
  const pool = activeTab.value === 'services' ? posStore.services : posStore.products;
  
  return pool
    .filter(item => !inCartIds.includes(item.id))
    .slice(0, 3); // Just pick first 3 available for now
});

const handleItemClick = (item) => {
  if (activeTab.value === 'products' && (item.stock_quantity || 0) <= 0) {
    toast.error(t('pos.outOfStock') || 'المنتج غير متوفر في المخزون');
    return;
  }
  selectedItem.value = item;
  selectedStaffId.value = '';
  modalQty.value = 1;
  showItemModal.value = true;
};

// Receptionist Special: Arrival Flows
const handleBookingArrive = async (booking) => {
  const result = await processBookingArrival(booking);
  if (result) {
    activeTab.value = 'services';
    // Load last visit and alerts for the customer
    await fetchCustomerReceptionistData(booking.customer_id);
  }
};

const handleQueueArrive = async (ticket) => {
  const result = await processQueueTicketArrival(ticket);
  if (result) {
    activeTab.value = 'services';
    if (ticket.customer_id) {
      await fetchCustomerReceptionistData(ticket.customer_id);
    }
  }
};

const fetchCustomerReceptionistData = async (customerId) => {
  if (!customerId) return;
  try {
    // 1. Fetch Alerts/Notes
    const response = await api.get(`/customers/${customerId}`);
    const customer = response.data?.data || response.data;
    if (customer?.notes) {
      posStore.clearCustomerAlerts();
      posStore.addCustomerAlert(customer.notes);
    }

    // 2. Fetch Last Visit
    const lastVisitRes = await api.get(`/customers/${customerId}/last-visit`);
    if (lastVisitRes.data?.data) {
      posStore.setLastVisitDetails(lastVisitRes.data.data);
    }
  } catch (error) {
    console.error('Error fetching receptionist data:', error);
  }
};

const handleQuickArrive = handleBookingArrive; // Backward compatibility

const confirmAddItem = () => {
  const staffMember = posStore.staff.find(s => Number(s.id) === Number(selectedStaffId.value));
  const type = activeTab.value === 'services' ? 'service' : (activeTab.value === 'products' ? 'product' : 'membership');
  const itemToAdd = {
    type: type,
    service_id: type === 'service' ? selectedItem.value.id : null,
    product_id: type === 'product' ? selectedItem.value.id : null,
    membership_id: type === 'membership' ? selectedItem.value.id : null,
    name: selectedItem.value.name_ar || selectedItem.value.name,
    quantity: modalQty.value,
    unit_price: parseFloat(selectedItem.value.price || selectedItem.value.selling_price || 0),
    staff_id: selectedStaffId.value || null,
    staff_name: staffMember ? staffMember.name : ''
  };
  posStore.addToCart(itemToAdd);
  showItemModal.value = false;
  toast.success(t('pos.addedToCart') || 'تمت الإضافة للسلة');
};

const updateQty = (index, delta) => {
  const item = posStore.cart[index];
  if (item.quantity + delta > 0) {
    item.quantity += delta;
  }
};

const onCustomerSelect = async () => {
  if (posStore.selectedCustomerId) {
    const customer = posStore.customers.find(c => Number(c.id) === Number(posStore.selectedCustomerId));
    if (customer) {
      await selectActiveCustomer(customer);
      await fetchCustomerReceptionistData(customer.id);
    }
  }
};

const handleCheckout = async () => {
  const clientSideId = 'pos_' + Date.now();
  const result = await processCheckout(clientSideId);
  if (result) {
    lastProcessedOrder.value = result;
    showInvoicePreview.value = true;
  }
};

const goToCustomerProfile = (id) => {
  router.push(`/customers/${id}`);
};

const handleSendWalletPass = async (id) => {
  try {
    const response = await api.post(`/loyalty/apple-wallet/send/${id}`);
    if (response.data?.success) {
      toast.success(t('loyalty.passSent') || 'تم إرسال بطاقة المحفظة بنجاح');
    }
  } catch (error) {
    console.error('Error sending wallet pass:', error);
    toast.error(t('loyalty.errorSendingPass') || 'خطأ في إرسال بطاقة المحفظة');
  }
};

const handleAddCustomer = async () => {
  isSavingCustomer.value = true;
  try {
    const response = await api.post('/customers', newCustomer.value);
    if (response.data?.success) {
      toast.success(t('customers.added') || 'تمت إضافة العميلة بنجاح');
      await posStore.fetchAllData();
      selectActiveCustomer(response.data.data);
      showAddCustomerModal.value = false;
      newCustomer.value = { name: '', phone: '', email: '' };
    }
  } catch (error) {
    console.error('Error adding customer:', error);
    toast.error(t('customers.errorAdding') || 'خطأ في إضافة العميلة');
  } finally {
    isSavingCustomer.value = false;
  }
};

const callNext = async () => {
  await callNextInQueue();
};

const handleCallTicket = async (ticket) => {
  await callSpecificTicket(ticket.id);
};

const handleServeTicket = async (ticket) => {
  await serveTicket(ticket.id);
  selectActiveCustomer({ 
    id: ticket.customer_id, 
    name: ticket.customer_name, 
    type: 'queue', 
    ticket_number: ticket.ticket_number 
  });
};

const handleCallStaff = async (staff) => {
  if (staff.status !== 'available') {
    toast.warning(t('workerCalls.staffNotAvailable') || 'الموظفة مشغولة حالياً');
    return;
  }
  
  try {
    const response = await api.post('/worker-calls', {
      staff_id: staff.id,
      customer_id: posStore.selectedCustomerId,
      reason: 'POS Service Request'
    });
    
    if (response.data?.success) {
      toast.success(t('workerCalls.callSent') || 'تم إرسال النداء للموظفة: ' + staff.name);
      await posStore.fetchAllData();
    }
  } catch (error) {
    console.error('Error calling staff:', error);
    toast.error(t('workerCalls.errorCalling') || 'خطأ في إرسال النداء');
  }
};

const handleCloseSession = async () => {
  if (!confirm(t('pos.confirmCloseSession') || 'هل أنت متأكد من إغلاق الجلسة؟')) return;
  
  try {
    const response = await api.post(`/pos/session/${posStore.openSession.id}/close`);
    if (response.data?.success) {
      toast.success(t('pos.sessionClosed') || 'تم إغلاق الجلسة بنجاح');
      showQuickSettings.value = false;
      await posStore.fetchAllData();
    }
  } catch (error) {
    console.error('Error closing session:', error);
    toast.error(t('pos.errorClosingSession'));
  }
};

onMounted(() => {
  posStore.fetchAllData();
});
</script>

<style scoped>
.pos-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 100px);
  gap: 1.25rem;
  padding: 1.25rem;
  background: var(--bg-primary);
  font-family: 'Cairo', sans-serif;
}

.pos-layout {
  display: grid;
  grid-template-columns: 340px 1fr 420px;
  gap: 1.5rem;
  flex: 1;
  overflow: hidden;
}

.pos-column {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Operations Column */
.operations-card {
  border-radius: 24px;
  border: none;
  box-shadow: var(--shadow-md);
  background: var(--bg-secondary);
}

.operation-section .section-title {
  font-size: 0.8125rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--asmaa-primary);
  margin-bottom: 1rem;
  letter-spacing: 1px;
  display: flex;
  align-items: center;
  opacity: 0.8;
}

.operation-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.operation-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-tertiary);
  border-radius: 16px;
  border: 1px solid var(--border-color);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.operation-item:hover {
  border-color: var(--asmaa-primary);
  background: var(--bg-secondary);
  transform: translateX(-5px);
  box-shadow: var(--shadow-sm);
}

.operation-item.selected {
  border-color: var(--asmaa-primary);
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.15) 0%, rgba(187, 160, 122, 0.05) 100%);
  box-shadow: 0 4px 15px rgba(187, 160, 122, 0.1);
}

.item-avatar {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, #d4b996 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  box-shadow: 0 4px 10px rgba(187, 160, 122, 0.3);
}

/* Catalog Column */
.catalog-card {
  border-radius: 24px;
  border: none;
  box-shadow: var(--shadow-lg);
}

.catalog-header-actions .nav-pills .nav-link {
  border-radius: 12px;
  padding: 0.6rem 1.25rem;
  font-weight: 600;
  color: var(--text-secondary);
  transition: all 0.3s;
}

.catalog-header-actions .nav-pills .nav-link.active {
  background: var(--asmaa-primary);
  color: white;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}

.catalog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1.25rem;
  padding: 1rem;
}

.catalog-item-card {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 1.5rem;
  position: relative;
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  min-height: 160px;
}

.catalog-item-card:hover {
  transform: scale(1.05);
  border-color: var(--asmaa-primary);
  background: var(--bg-secondary);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
}

.item-category-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  font-size: 0.65rem;
  font-weight: 800;
  background: white;
  color: var(--asmaa-primary);
  padding: 4px 10px;
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
}

.catalog-item-card .item-name {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
  color: var(--text-primary);
}

.catalog-item-card .item-price {
  font-weight: 900;
  font-size: 1.25rem;
  color: var(--asmaa-primary);
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, #8a6d3b 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Checkout Column */
.checkout-card {
  border-radius: 24px;
  border: none;
  box-shadow: var(--shadow-xl);
  background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
}

.checkout-footer {
  padding: 1.5rem;
  background: white;
  border-radius: 32px 32px 0 0;
  box-shadow: 0 -10px 30px rgba(0,0,0,0.05);
  border-top: 1px solid var(--border-color);
}

.total-line {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px dashed var(--border-color);
  font-weight: 900;
  font-size: 1.5rem;
}

.checkout-btn {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, #8a6d3b 100%) !important;
  border: none !important;
  font-weight: 800;
  letter-spacing: 0.5px;
  height: 60px;
  border-radius: 18px;
  transition: all 0.3s;
}

.checkout-btn:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(187, 160, 122, 0.4) !important;
}

/* Receptionist Features CSS */
.arrival-quick-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.arrival-quick-card:hover {
  transform: scale(1.02) translateX(5px);
  background: var(--bg-primary) !important;
}
.mini-avatar-box {
  width: 32px;
  height: 32px;
  background: var(--asmaa-primary);
  color: white;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.8rem;
}
.tiny { font-size: 0.65rem; }

.customer-alert-banner {
  border-left: 5px solid rgba(255,255,255,0.3);
}
.pulse-red {
  animation: pulse-red-bg 2s infinite;
}
@keyframes pulse-red-bg {
  0% { box-shadow: 0 0 0 0 rgba(229, 83, 83, 0.4); }
  70% { box-shadow: 0 0 0 15px rgba(229, 83, 83, 0); }
  100% { box-shadow: 0 0 0 0 rgba(229, 83, 83, 0); }
}

.split-payment-grid {
  background: rgba(187, 160, 122, 0.05);
}
.remaining-balance {
  letter-spacing: 1px;
  padding-top: 8px;
  border-top: 1px dashed var(--border-color);
}

.last-services-box .badge {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
  background: white;
  color: var(--text-primary);
  border: 1px solid var(--border-color);
  transition: all 0.2s;
}
.last-services-box .badge:hover {
  background: var(--asmaa-primary);
  color: white;
  border-color: var(--asmaa-primary);
}

/* Modal Styling */
.item-preview-card {
  padding: 1.5rem;
  background: var(--bg-tertiary);
  border-radius: 20px;
  height: 100%;
}

.quantity-input-large {
  display: flex;
  gap: 0.5rem;
}

/* Scrollbar Styling */
.catalog-grid-wrapper::-webkit-scrollbar,
.cart-items-section::-webkit-scrollbar {
  width: 4px;
}

.catalog-grid-wrapper::-webkit-scrollbar-thumb,
.cart-items-section::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 10px;
}

/* Responsive */
@media (max-width: 1400px) {
  .pos-layout {
    grid-template-columns: 300px 1fr 380px;
    gap: 1rem;
  }
}

@media (max-width: 1200px) {
  .pos-layout {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto 1fr;
  }
  .pos-operations { 
    grid-column: 1 / 2;
    grid-row: 1 / 2;
  }
  .pos-checkout { 
    grid-column: 2 / 3;
    grid-row: 1 / 3;
  }
  .pos-catalog {
    grid-column: 1 / 2;
    grid-row: 2 / 3;
  }
}

@media (max-width: 992px) {
  .pos-layout {
    grid-template-columns: 1fr;
    grid-template-rows: auto auto 1fr;
    overflow-y: auto;
  }
  .pos-page {
    height: auto;
    overflow-y: auto;
  }
  .pos-operations, .pos-catalog, .pos-checkout {
    grid-column: 1 / 2;
    grid-row: auto;
    height: auto;
    max-height: none;
  }
  .checkout-card {
    position: sticky;
    bottom: 0;
    z-index: 100;
    border-radius: 24px 24px 0 0;
  }
}

@media (max-width: 576px) {
  .pos-page {
    padding: 0.5rem;
  }
  .catalog-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  }
  .quick-stats-bar {
    padding: 0.5rem;
  }
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Receptionist Superpowers CSS */
.cart-item-wrapper {
  margin-bottom: 0.75rem;
  background: var(--bg-tertiary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.cart-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
}

.cart-item-note {
  border-top: 1px dashed var(--border-color);
  padding: 0.25rem 0.5rem;
  background: rgba(187, 160, 122, 0.05);
}

.cart-item-note .tiny {
  font-size: 0.7rem;
}

.loyalty-preview-banner {
  background: linear-gradient(135deg, rgba(249, 177, 21, 0.1), rgba(187, 160, 122, 0.1));
  color: var(--asmaa-primary);
  border: 1px solid rgba(249, 177, 21, 0.2);
}

.smart-upsell-bar {
  scrollbar-width: none;
}

.smart-upsell-bar::-webkit-scrollbar {
  display: none;
}

.upsell-badge:hover {
  background-color: var(--asmaa-primary) !important;
  color: white !important;
  border-color: var(--asmaa-primary) !important;
}
</style>
