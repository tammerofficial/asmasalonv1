<template>
  <div class="reports-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('reports.title')"
      :subtitle="t('reports.subtitle') || 'Comprehensive statistics and overview'"
    >
      <template #icon>
        <CIcon icon="cil-chart-line" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" @click="exportReport" class="me-2 export-btn">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') || t('reports.export') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards (Overview tab) -->
    <div v-if="activeTab === 'overview'" class="stats-grid">
      <StatCard 
        :label="t('sales.orders') || 'Orders'"
        :value="overview?.summary?.orders ?? 0"
        :badge="overviewRangeLabel"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-cart" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('reports.revenue') || 'Revenue'"
        :value="formatCurrency(overview?.summary?.revenue ?? 0)"
        :badge="(t('invoices.paid') || 'Paid') + ': ' + formatCurrency(overview?.summary?.paid_revenue ?? 0)"
        badge-variant="success"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-money" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('reports.bookingsStatus') || t('reports.bookings')"
        :value="overview?.summary?.bookings ?? 0"
        :badge="(t('sales.averageOrder') || 'Average Order') + ': ' + formatCurrency(overview?.summary?.avg_order ?? 0)"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-calendar" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('commissions.title') || 'Commissions'"
        :value="formatCurrency(overview?.summary?.commissions_total ?? 0)"
        :badge="(t('loyalty.title') || 'Loyalty') + ': ' + (overview?.summary?.loyalty_earned ?? 0) + ' pts'"
        badge-variant="warning"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-dollar" />
        </template>
      </StatCard>
    </div>

    <!-- Stats Cards (Sales tab) -->
    <div v-else-if="activeTab === 'sales'" class="stats-grid">
      <StatCard 
        :label="t('common.total') || 'Total'"
        :value="salesSummary.days"
        :badge="salesRangeLabel"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-calendar" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('sales.orders') || 'Orders'"
        :value="salesSummary.totalOrders"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-cart" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('reports.revenue') || 'Revenue'"
        :value="formatCurrency(salesSummary.totalRevenue)"
        badge-variant="success"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-money" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('invoices.paid') || 'Paid'"
        :value="formatCurrency(salesSummary.totalPaid)"
        :badge="(t('sales.averageOrder') || 'Average Order') + ': ' + formatCurrency(salesSummary.avgOrder)"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>
    </div>

    <!-- Tabs -->
    <Card>
      <template #title>
        <CNav variant="tabs" class="reports-tabs">
          <CNavItem :active="activeTab === 'overview'" @click="setTab('overview')">
            {{ t('reports.overview') || 'Overview' }}
          </CNavItem>
          <CNavItem :active="activeTab === 'sales'" @click="setTab('sales')">
            {{ t('reports.sales') }}
          </CNavItem>
          <CNavItem :active="activeTab === 'bookings'" @click="setTab('bookings')">
            {{ t('reports.bookings') }}
          </CNavItem>
          <CNavItem :active="activeTab === 'customers'" @click="setTab('customers')">
            {{ t('reports.customers') }}
          </CNavItem>
          <CNavItem :active="activeTab === 'staff'" @click="setTab('staff')">
            {{ t('reports.staff') }}
          </CNavItem>
          <CNavItem :active="activeTab === 'daily'" @click="setTab('daily')">
            {{ t('reports.dailySales') || 'Daily Sales' }}
          </CNavItem>
          <CNavItem :active="activeTab === 'queue'" @click="setTab('queue')">
            {{ t('reports.queueStats') || 'Queue Stats' }}
          </CNavItem>
          <CNavItem :active="activeTab === 'commissions'" @click="setTab('commissions')">
            {{ t('commissions.title') || 'Commissions' }}
          </CNavItem>
        </CNav>
      </template>
      <template #default>
        <!-- Overview (All-in-one) -->
        <div v-if="activeTab === 'overview'">
          <Card :title="t('common.filter')" icon="cil-filter" class="mb-3 filters-card">
            <CRow class="g-3">
              <CCol :md="4">
                <CFormInput
                  v-model="overviewFilters.start_date"
                  type="date"
                  :label="t('reports.fromDate')"
                />
              </CCol>
              <CCol :md="4">
                <CFormInput
                  v-model="overviewFilters.end_date"
                  type="date"
                  :label="t('reports.toDate')"
                />
              </CCol>
              <CCol :md="4" class="d-flex align-items-end gap-2">
                <CButton color="secondary" variant="outline" @click="setOverviewRange('today')" class="w-100">
                  {{ t('common.today') || 'Today' }}
                </CButton>
                <CButton color="secondary" variant="outline" @click="setOverviewRange('last7')" class="w-100">
                  {{ t('common.last7Days') || 'Last 7 days' }}
                </CButton>
              </CCol>
              <CCol :md="4" class="d-flex align-items-end gap-2">
                <CButton color="secondary" variant="outline" @click="setOverviewRange('thisMonth')" class="w-100">
                  {{ t('common.thisMonth') || 'This month' }}
                </CButton>
                <CButton color="primary" class="btn-primary-custom w-100" @click="loadOverview">
                  {{ t('reports.generate') }}
                </CButton>
              </CCol>
            </CRow>
          </Card>

          <Card :title="t('reports.overview') || 'Overview'" icon="cil-chart-line" class="charts-card">
            <LoadingSpinner v-if="loadingOverview" :text="t('common.loading')" />

            <EmptyState
              v-else-if="!overview || !overview.charts"
              :title="t('common.noData')"
              :description="(t('reports.overview') || 'Overview') + ' - ' + t('common.noData')"
              icon-color="gray"
            />

            <div v-else class="charts-grid">
              <div class="chart-panel chart-panel-wide">
                <div class="chart-title">{{ t('reports.salesTrend') || 'Sales Trend' }}</div>
                <div class="chart-canvas">
                  <Line :data="salesTrendData" :options="salesTrendOptions" :key="chartsKey + '-sales'" />
                </div>
              </div>

              <div class="chart-panel">
                <div class="chart-title">{{ t('reports.bookingsStatus') || 'Bookings Status' }}</div>
                <div class="chart-canvas chart-canvas-sm">
                  <Doughnut :data="bookingsStatusData" :options="doughnutOptions" :key="chartsKey + '-b'" />
                </div>
              </div>

              <div class="chart-panel">
                <div class="chart-title">{{ t('reports.invoicesStatus') || 'Invoices Status' }}</div>
                <div class="chart-canvas chart-canvas-sm">
                  <Doughnut :data="invoicesStatusData" :options="doughnutOptions" :key="chartsKey + '-i'" />
                </div>
              </div>

              <div class="chart-panel">
                <div class="chart-title">{{ t('reports.paymentsMethods') || 'Payments Methods' }}</div>
                <div class="chart-canvas chart-canvas-sm">
                  <Doughnut :data="paymentsMethodsData" :options="doughnutOptions" :key="chartsKey + '-p'" />
                </div>
              </div>

              <div class="chart-panel">
                <div class="chart-title">{{ t('reports.queueStatus') || 'Queue Status' }}</div>
                <div class="chart-canvas chart-canvas-sm">
                  <Doughnut :data="queueStatusData" :options="doughnutOptions" :key="chartsKey + '-q'" />
                </div>
              </div>

              <div class="chart-panel">
                <div class="chart-title">{{ t('reports.topServices') || 'Top Services' }}</div>
                <div class="chart-canvas">
                  <Bar :data="topServicesData" :options="barOptions" :key="chartsKey + '-ts'" />
                </div>
              </div>

              <div class="chart-panel">
                <div class="chart-title">{{ t('reports.topProducts') || 'Top Products' }}</div>
                <div class="chart-canvas">
                  <Bar :data="topProductsData" :options="barOptions" :key="chartsKey + '-tp'" />
                </div>
              </div>

              <div class="chart-panel chart-panel-wide">
                <div class="chart-title">{{ t('reports.loyaltyTrend') || 'Loyalty Trend' }}</div>
                <div class="chart-canvas">
                  <Line :data="loyaltyTrendData" :options="loyaltyTrendOptions" :key="chartsKey + '-l'" />
                </div>
              </div>

              <div class="chart-panel">
                <div class="chart-title">{{ t('reports.commissionsByStaff') || 'Commissions by Staff' }}</div>
                <div class="chart-canvas">
                  <Bar :data="commissionsByStaffData" :options="barOptions" :key="chartsKey + '-c'" />
                </div>
              </div>

              <div class="chart-panel">
                <div class="chart-title">{{ t('reports.newCustomers') || 'New Customers' }}</div>
                <div class="chart-canvas">
                  <Line :data="newCustomersData" :options="simpleLineOptions" :key="chartsKey + '-nc'" />
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Sales Report -->
        <div v-else-if="activeTab === 'sales'">
          <Card :title="t('common.filter')" icon="cil-filter" class="mb-3 filters-card">
            <CRow class="g-3">
              <CCol :md="4">
                <CFormInput
                  v-model="salesFilters.start_date"
                  type="date"
                  :label="t('reports.fromDate')"
                />
              </CCol>
              <CCol :md="4">
                <CFormInput
                  v-model="salesFilters.end_date"
                  type="date"
                  :label="t('reports.toDate')"
                />
              </CCol>
              <CCol :md="4" class="d-flex align-items-end gap-2">
                <CButton color="secondary" variant="outline" @click="setSalesRange('today')" class="w-100">
                  {{ t('common.today') || 'Today' }}
                </CButton>
                <CButton color="secondary" variant="outline" @click="setSalesRange('last7')" class="w-100">
                  {{ t('common.last7Days') || 'Last 7 days' }}
                </CButton>
              </CCol>
              <CCol :md="4" class="d-flex align-items-end gap-2">
                <CButton color="secondary" variant="outline" @click="setSalesRange('thisMonth')" class="w-100">
                  {{ t('common.thisMonth') || 'This month' }}
                </CButton>
                <CButton color="primary" class="btn-primary-custom w-100" @click="loadSalesReport">
                  {{ t('reports.generate') }}
                </CButton>
              </CCol>
            </CRow>
          </Card>

          <Card :title="t('reports.sales')" icon="cil-chart-line">
            <LoadingSpinner v-if="loadingSales" :text="t('common.loading')" />
            
            <EmptyState 
              v-else-if="salesData.length === 0"
              :title="t('common.noData')"
              :description="(t('reports.sales') || 'Sales Report') + ' - ' + t('common.noData')"
              icon-color="gray"
            />

            <div v-else class="table-wrapper">
              <CTable hover responsive class="table-modern">
                <thead>
                  <tr class="table-header-row">
                    <th>{{ t('common.date') || 'Date' }}</th>
                    <th>{{ t('sales.orders') || 'Orders Count' }}</th>
                    <th>{{ t('reports.revenue') || 'Total Revenue' }}</th>
                    <th>{{ t('invoices.paid') || 'Paid' }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in salesData" :key="item.date" class="table-row">
                    <td>{{ item.date }}</td>
                    <td>
                      <CBadge class="unified-badge primary-badge">
                        <CIcon icon="cil-cart" class="badge-icon" />
                        <span>{{ item.total_orders || 0 }}</span>
                      </CBadge>
                    </td>
                    <td>
                      <strong class="unified-amount">
                        <CIcon icon="cil-money" class="money-icon" />
                        {{ formatCurrency(item.total_revenue || 0) }}
                      </strong>
                    </td>
                    <td>
                      <CBadge class="unified-badge success-badge">
                        <CIcon icon="cil-check-circle" class="badge-icon" />
                        <span>{{ formatCurrency(item.paid_amount || 0) }}</span>
                      </CBadge>
                    </td>
                  </tr>
                </tbody>
              </CTable>
            </div>
          </Card>
        </div>

        <!-- Bookings Report -->
        <div v-else-if="activeTab === 'bookings'">
          <Card :title="t('common.filter')" icon="cil-filter" class="mb-3">
            <CRow class="g-3">
              <CCol :md="4">
                <CFormInput
                  v-model="bookingsFilters.start_date"
                  type="date"
                  :label="t('reports.fromDate')"
                />
              </CCol>
              <CCol :md="4">
                <CFormInput
                  v-model="bookingsFilters.end_date"
                  type="date"
                  :label="t('reports.toDate')"
                />
              </CCol>
              <CCol :md="4" class="d-flex align-items-end">
                <CButton color="primary" @click="loadBookingsReport" class="w-100">
                  {{ t('reports.generate') }}
                </CButton>
              </CCol>
            </CRow>
          </Card>

          <Card :title="t('reports.bookings')" icon="cil-calendar">
            <LoadingSpinner v-if="loadingBookings" :text="t('common.loading')" />
            
            <EmptyState 
              v-else-if="bookingsData.length === 0"
              :title="t('common.noData')"
              :description="t('reports.bookings') + ' - ' + t('common.noData')"
              icon-color="gray"
            />

            <CTable v-else hover responsive class="table-modern">
              <thead>
                <tr>
                  <th>{{ t('common.status') }}</th>
                  <th>Count</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in bookingsData" :key="item.status" class="table-row">
                  <td>
                    <CBadge :color="getStatusColor(item.status)" class="status-badge-modern">
                      <CIcon :icon="getStatusIcon(item.status)" class="me-1" />
                      {{ getStatusText(item.status) }}
                    </CBadge>
                  </td>
                  <td>
                    <strong class="count-cell">{{ item.count || 0 }}</strong>
                  </td>
                </tr>
              </tbody>
            </CTable>
          </Card>
        </div>

        <!-- Customers Report -->
        <div v-else-if="activeTab === 'customers'">
          <Card :title="t('reports.customers')" icon="cil-user">
            <div class="mb-3">
              <CButton color="primary" @click="loadCustomersReport">
                {{ t('reports.generate') }}
              </CButton>
            </div>

            <LoadingSpinner v-if="loadingCustomers" :text="t('common.loading')" />
            
            <div v-else-if="customersStats">
              <div class="stats-grid mb-4">
                <StatCard 
                  label="Total Customers"
                  :value="customersStats.total || 0"
                  badge-variant="info"
                  color="blue"
                >
                  <template #icon>
                    <CIcon icon="cil-user" />
                  </template>
                </StatCard>

                <StatCard 
                  :label="t('status.active')"
                  :value="customersStats.active || 0"
                  badge-variant="success"
                  color="green"
                >
                  <template #icon>
                    <CIcon icon="cil-check-circle" />
                  </template>
                </StatCard>

                <StatCard 
                  label="New This Month"
                  :value="customersStats.new_this_month || 0"
                  badge-variant="info"
                  color="purple"
                >
                  <template #icon>
                    <CIcon icon="cil-plus" />
                  </template>
                </StatCard>
              </div>

              <CTable v-if="customersStats.top_customers && customersStats.top_customers.length > 0" hover responsive class="table-modern">
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Spending</th>
                    <th>Visits</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="customer in customersStats.top_customers" :key="customer.id" class="table-row">
                    <td>
                      <strong>{{ customer.name }}</strong>
                    </td>
                  <td>
                    <strong class="text-success spending-cell">
                      <CIcon icon="cil-money" class="me-1" />
                      {{ formatCurrency(customer.total_spent || 0) }}
                    </strong>
                  </td>
                  <td>
                    <CBadge color="info" class="visits-badge">
                      <CIcon icon="cil-calendar-check" class="me-1" />
                      {{ customer.total_visits || 0 }}
                    </CBadge>
                  </td>
                  </tr>
                </tbody>
              </CTable>
            </div>
          </Card>
        </div>

        <!-- Staff Report -->
        <div v-else-if="activeTab === 'staff'">
          <Card :title="t('reports.staff')" icon="cil-user">
            <div class="mb-3">
              <CButton color="primary" @click="loadStaffReport">
                {{ t('reports.generate') }}
              </CButton>
            </div>

            <LoadingSpinner v-if="loadingStaff" :text="t('common.loading')" />
            
            <EmptyState 
              v-else-if="staffData.length === 0"
              :title="t('common.noData')"
              :description="t('reports.staff') + ' - ' + t('common.noData')"
              icon-color="gray"
            />

            <CTable v-else hover responsive class="table-modern">
              <thead>
                <tr>
                  <th>{{ t('staff.title') }}</th>
                  <th>Services</th>
                  <th>Revenue</th>
                  <th>Rating</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="staff in staffData" :key="staff.id" class="table-row">
                  <td>
                    <strong>{{ staff.name }}</strong>
                  </td>
                  <td>
                    <CBadge color="info" class="services-badge">
                      <CIcon icon="cil-spreadsheet" class="me-1" />
                      {{ staff.total_services || 0 }}
                    </CBadge>
                  </td>
                  <td>
                    <strong class="text-success revenue-cell">
                      <CIcon icon="cil-money" class="me-1" />
                      {{ formatCurrency(staff.total_revenue || 0) }}
                    </strong>
                  </td>
                  <td>
                    <CBadge :color="getRatingColor(staff.rating || 0)" class="rating-badge">
                      <CIcon icon="cil-star" class="me-1" />
                      {{ (staff.rating || 0).toFixed(1) }}
                    </CBadge>
                  </td>
                </tr>
              </tbody>
            </CTable>
          </Card>
        </div>

        <!-- Daily Sales Report -->
        <div v-else-if="activeTab === 'daily'">
          <Card :title="t('common.filter')" icon="cil-filter" class="mb-3">
            <CRow class="g-3">
              <CCol :md="4">
                <CFormInput
                  v-model="dailyFilters.date"
                  type="date"
                  label="Date"
                />
              </CCol>
              <CCol :md="4" class="d-flex align-items-end">
                <CButton color="primary" @click="loadDailySales" class="w-100">
                  {{ t('reports.generate') }}
                </CButton>
              </CCol>
            </CRow>
          </Card>

          <Card title="Daily Sales Overview" icon="cil-chart-line">
            <LoadingSpinner v-if="loadingDaily" :text="t('common.loading')" />
            
            <div v-else-if="dailySalesData">
              <div class="stats-grid mb-4">
                <StatCard 
                  label="Total Orders"
                  :value="dailySalesData.total_orders || 0"
                  badge-variant="info"
                  color="blue"
                >
                  <template #icon>
                    <CIcon icon="cil-cart" />
                  </template>
                </StatCard>

                <StatCard 
                  label="Total Revenue"
                  :value="formatCurrency(dailySalesData.total_revenue || 0)"
                  badge-variant="success"
                  color="green"
                >
                  <template #icon>
                    <CIcon icon="cil-money" />
                  </template>
                </StatCard>

                <StatCard 
                  label="Average Order"
                  :value="formatCurrency(dailySalesData.avg_order_value || 0)"
                  badge-variant="info"
                  color="purple"
                >
                  <template #icon>
                    <CIcon icon="cil-chart" />
                  </template>
                </StatCard>
              </div>

              <div v-if="dailySalesData.comparison" class="comparison-cards mb-4">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="comparison-card">
                      <h6>Yesterday</h6>
                      <p class="mb-1">{{ formatCurrency(dailySalesData.comparison.yesterday.revenue) }}</p>
                      <CBadge :color="dailySalesData.comparison.yesterday.change_percent >= 0 ? 'success' : 'danger'">
                        {{ dailySalesData.comparison.yesterday.change_percent >= 0 ? '+' : '' }}{{ dailySalesData.comparison.yesterday.change_percent }}%
                      </CBadge>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="comparison-card">
                      <h6>Last Week</h6>
                      <p class="mb-1">{{ formatCurrency(dailySalesData.comparison.last_week.revenue) }}</p>
                      <CBadge :color="dailySalesData.comparison.last_week.change_percent >= 0 ? 'success' : 'danger'">
                        {{ dailySalesData.comparison.last_week.change_percent >= 0 ? '+' : '' }}{{ dailySalesData.comparison.last_week.change_percent }}%
                      </CBadge>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="dailySalesData.payment_methods && dailySalesData.payment_methods.length" class="payment-methods">
                <h5 class="mb-3">Payment Methods</h5>
                <CTable hover responsive>
                  <thead>
                    <tr>
                      <th>Method</th>
                      <th>Count</th>
                      <th>Total</th>
                      <th>Percentage</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="method in dailySalesData.payment_methods" :key="method.payment_method">
                      <td><strong>{{ method.payment_method || 'N/A' }}</strong></td>
                      <td>{{ method.count || 0 }}</td>
                      <td>{{ formatCurrency(method.total || 0) }}</td>
                      <td>
                        {{ dailySalesData.total_revenue > 0 ? Math.round((method.total / dailySalesData.total_revenue) * 100) : 0 }}%
                      </td>
                    </tr>
                  </tbody>
                </CTable>
              </div>
            </div>
          </Card>
        </div>

        <!-- Queue Stats Report -->
        <div v-else-if="activeTab === 'queue'">
          <Card :title="t('common.filter')" icon="cil-filter" class="mb-3">
            <CRow class="g-3">
              <CCol :md="4">
                <CFormInput
                  v-model="queueFilters.date"
                  type="date"
                  label="Date"
                />
              </CCol>
              <CCol :md="4" class="d-flex align-items-end">
                <CButton color="primary" @click="loadQueueStats" class="w-100">
                  {{ t('reports.generate') }}
                </CButton>
              </CCol>
            </CRow>
          </Card>

          <Card title="Queue Statistics" icon="cil-queue">
            <LoadingSpinner v-if="loadingQueue" :text="t('common.loading')" />
            
            <div v-else-if="queueStats" class="stats-grid">
              <StatCard 
                label="Total Tickets"
                :value="queueStats.total_tickets || 0"
                badge-variant="info"
                color="blue"
              >
                <template #icon>
                  <CIcon icon="cil-list" />
                </template>
              </StatCard>

              <StatCard 
                label="Completed"
                :value="queueStats.completed || 0"
                badge-variant="success"
                color="green"
              >
                <template #icon>
                  <CIcon icon="cil-check-circle" />
                </template>
              </StatCard>

              <StatCard 
                label="Cancelled"
                :value="queueStats.cancelled || 0"
                badge-variant="warning"
                color="yellow"
              >
                <template #icon>
                  <CIcon icon="cil-x-circle" />
                </template>
              </StatCard>

              <StatCard 
                label="Avg Waiting Time"
                :value="(queueStats.avg_waiting_time || 0) + ' min'"
                badge-variant="info"
                color="purple"
              >
                <template #icon>
                  <CIcon icon="cil-clock" />
                </template>
              </StatCard>

              <StatCard 
                label="Avg Service Time"
                :value="(queueStats.avg_service_time || 0) + ' min'"
                badge-variant="info"
                color="teal"
              >
                <template #icon>
                  <CIcon icon="cil-timer" />
                </template>
              </StatCard>
            </div>
          </Card>
        </div>

        <!-- Commissions Report -->
        <div v-else-if="activeTab === 'commissions'">
          <Card :title="t('common.filter')" icon="cil-filter" class="mb-3">
            <CRow class="g-3">
              <CCol :md="3">
                <CFormInput
                  v-model="commissionsFilters.start_date"
                  type="date"
                  :label="t('reports.fromDate')"
                />
              </CCol>
              <CCol :md="3">
                <CFormInput
                  v-model="commissionsFilters.end_date"
                  type="date"
                  :label="t('reports.toDate')"
                />
              </CCol>
              <CCol :md="3" class="d-flex align-items-end">
                <CButton color="primary" @click="loadCommissionsReport" class="w-100">
                  {{ t('reports.generate') }}
                </CButton>
              </CCol>
            </CRow>
          </Card>

          <Card title="Commissions Report" icon="cil-dollar">
            <LoadingSpinner v-if="loadingCommissions" :text="t('common.loading')" />
            
            <EmptyState 
              v-else-if="commissionsData.length === 0"
              :title="t('common.noData')"
              :description="'Commissions - ' + t('common.noData')"
              icon-color="gray"
            />

            <CTable v-else hover responsive class="table-modern">
              <thead>
                <tr>
                  <th>{{ t('staff.title') }}</th>
                  <th>Services</th>
                  <th>Revenue</th>
                  <th>Base Commission</th>
                  <th>Bonuses</th>
                  <th>Total Commission</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in commissionsData" :key="item.staff_id" class="table-row">
                  <td><strong>{{ item.staff_name }}</strong></td>
                  <td><CBadge color="info">{{ item.total_services || 0 }}</CBadge></td>
                  <td>{{ formatCurrency(item.total_revenue || 0) }}</td>
                  <td>{{ formatCurrency(item.base_commission || 0) }}</td>
                  <td>{{ formatCurrency(item.total_bonuses || 0) }}</td>
                  <td><strong class="text-success">{{ formatCurrency(item.total_commission || 0) }}</strong></td>
                </tr>
              </tbody>
            </CTable>
          </Card>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import {
  CButton,
  CTable,
  CBadge,
  CFormInput,
  CNav,
  CNavItem,
  CRow,
  CCol,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import { useUiStore } from '@/stores/ui';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import api from '@/utils/api';

import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Tooltip, Legend, Filler);

const { t } = useTranslation();
const toast = useToast();
const uiStore = useUiStore();

const activeTab = ref('overview');

const chartsKey = ref(String(Date.now()));
watch(
  () => uiStore.theme,
  () => {
    chartsKey.value = String(Date.now());
  }
);

const cssVar = (name, fallback) => {
  if (typeof window === 'undefined') return fallback;
  const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  return v || fallback;
};

const themeColors = computed(() => {
  return {
    primary: cssVar('--asmaa-primary', '#BBA07A'),
    primarySoft: cssVar('--asmaa-primary-soft', 'rgba(187,160,122,0.14)'),
    success: cssVar('--asmaa-success', '#22c55e'),
    warning: cssVar('--asmaa-warning', '#f59e0b'),
    danger: cssVar('--asmaa-danger', '#ef4444'),
    text: cssVar('--text-primary', '#111827'),
    muted: cssVar('--text-muted', '#6b7280'),
    border: cssVar('--border-color', '#e5e7eb'),
  };
});

// Overview
const overview = ref(null);
const loadingOverview = ref(false);
const overviewFilters = ref({
  start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
});

const overviewRangeLabel = computed(() => {
  const from = overviewFilters.value?.start_date;
  const to = overviewFilters.value?.end_date;
  if (!from || !to) return '';
  return `${from} → ${to}`;
});

const setOverviewRange = (preset) => {
  const today = new Date();
  const toYmd = (d) => d.toISOString().split('T')[0];

  if (preset === 'today') {
    const d = toYmd(today);
    overviewFilters.value.start_date = d;
    overviewFilters.value.end_date = d;
    loadOverview();
    return;
  }

  if (preset === 'last7') {
    const start = new Date(today);
    start.setDate(today.getDate() - 6);
    overviewFilters.value.start_date = toYmd(start);
    overviewFilters.value.end_date = toYmd(today);
    loadOverview();
    return;
  }

  // thisMonth
  const start = new Date(today.getFullYear(), today.getMonth(), 1);
  overviewFilters.value.start_date = toYmd(start);
  overviewFilters.value.end_date = toYmd(today);
  loadOverview();
};

const loadOverview = async () => {
  loadingOverview.value = true;
  try {
    const res = await api.get('/reports/overview', {
      params: overviewFilters.value,
      noCache: true,
    });
    overview.value = res.data?.data || res.data || null;
  } catch (e) {
    console.error('Error loading overview report:', e);
    overview.value = null;
    toast.error(t('common.errorLoading') || 'Error loading data');
  } finally {
    loadingOverview.value = false;
  }
};
const salesData = ref([]);
const bookingsData = ref([]);
const customersStats = ref(null);
const staffData = ref([]);
const dailySalesData = ref(null);
const queueStats = ref(null);
const commissionsData = ref([]);
const loadingSales = ref(false);
const loadingBookings = ref(false);
const loadingCustomers = ref(false);
const loadingStaff = ref(false);
const loadingDaily = ref(false);
const loadingQueue = ref(false);
const loadingCommissions = ref(false);

const salesFilters = ref({
  start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
});

const bookingsFilters = ref({
  start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
});

const dailyFilters = ref({
  date: new Date().toISOString().split('T')[0],
});

const queueFilters = ref({
  date: new Date().toISOString().split('T')[0],
});

const commissionsFilters = ref({
  start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
});

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
};

const salesSummary = computed(() => {
  const rows = Array.isArray(salesData.value) ? salesData.value : [];
  const result = rows.reduce(
    (acc, row) => {
      acc.totalOrders += Number(row?.total_orders || 0);
      acc.totalRevenue += Number(row?.total_revenue || 0);
      acc.totalPaid += Number(row?.paid_amount || 0);
      return acc;
    },
    { totalOrders: 0, totalRevenue: 0, totalPaid: 0 }
  );

  return {
    days: rows.length,
    totalOrders: result.totalOrders,
    totalRevenue: result.totalRevenue,
    totalPaid: result.totalPaid,
    avgOrder: result.totalOrders > 0 ? result.totalRevenue / result.totalOrders : 0,
  };
});

const salesRangeLabel = computed(() => {
  const from = salesFilters.value?.start_date;
  const to = salesFilters.value?.end_date;
  if (!from || !to) return '';
  return `${from} → ${to}`;
});

const setSalesRange = (preset) => {
  const today = new Date();
  const toYmd = (d) => d.toISOString().split('T')[0];

  if (preset === 'today') {
    const d = toYmd(today);
    salesFilters.value.start_date = d;
    salesFilters.value.end_date = d;
    loadSalesReport();
    return;
  }

  if (preset === 'last7') {
    const start = new Date(today);
    start.setDate(today.getDate() - 6);
    salesFilters.value.start_date = toYmd(start);
    salesFilters.value.end_date = toYmd(today);
    loadSalesReport();
    return;
  }

  // thisMonth
  const start = new Date(today.getFullYear(), today.getMonth(), 1);
  salesFilters.value.start_date = toYmd(start);
  salesFilters.value.end_date = toYmd(today);
  loadSalesReport();
};

const setTab = (tab) => {
  activeTab.value = tab;
  // Lazy load when switching
  if (tab === 'overview' && !overview.value) loadOverview();
  if (tab === 'sales' && salesData.value.length === 0) loadSalesReport();
  if (tab === 'daily' && !dailySalesData.value) loadDailySales();
  if (tab === 'queue' && !queueStats.value) loadQueueStats();
};

const getStatusText = (status) => {
  const texts = {
    pending: t('bookings.pending'),
    confirmed: t('bookings.confirmed'),
    completed: t('bookings.completed'),
    cancelled: t('bookings.cancelled'),
    no_show: 'No Show',
  };
  return texts[status] || status;
};

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    confirmed: 'info',
    completed: 'success',
    cancelled: 'secondary',
    no_show: 'danger',
  };
  return colors[status] || 'secondary';
};

const getRatingColor = (rating) => {
  if (rating >= 4.5) return 'success';
  if (rating >= 3.5) return 'info';
  if (rating >= 2.5) return 'warning';
  return 'danger';
};

const getStatusIcon = (status) => {
  const icons = {
    pending: 'cil-clock',
    confirmed: 'cil-check-circle',
    completed: 'cil-check',
    cancelled: 'cil-x-circle',
    no_show: 'cil-warning',
  };
  return icons[status] || 'cil-info';
};

const loadSalesReport = async () => {
  loadingSales.value = true;
  try {
    const response = await api.get('/reports/sales', {
      params: salesFilters.value,
      noCache: true,
    });
    const data = response.data?.data?.data || response.data?.data || [];
    
    salesData.value = data || [];
  } catch (error) {
    console.error('Error loading sales report:', error);
    salesData.value = [];
    toast.error(t('common.errorLoading') || 'Error loading data');
  } finally {
    loadingSales.value = false;
  }
};

const loadBookingsReport = async () => {
  loadingBookings.value = true;
  try {
    const response = await api.get('/reports/bookings', {
      params: bookingsFilters.value,
      noCache: true,
    });
    const data = response.data?.data?.data || response.data?.data || [];
    
    bookingsData.value = data || [];
  } catch (error) {
    console.error('Error loading bookings report:', error);
    bookingsData.value = [];
    toast.error(t('common.errorLoading') || 'Error loading data');
  } finally {
    loadingBookings.value = false;
  }
};

const loadCustomersReport = async () => {
  loadingCustomers.value = true;
  try {
    const response = await api.get('/reports/customers', { noCache: true });
    const data = response.data?.data || {};
    
    customersStats.value = data;
  } catch (error) {
    console.error('Error loading customers report:', error);
    customersStats.value = null;
    toast.error(t('common.errorLoading') || 'Error loading data');
  } finally {
    loadingCustomers.value = false;
  }
};

const loadStaffReport = async () => {
  loadingStaff.value = true;
  try {
    const response = await api.get('/reports/staff', { noCache: true });
    const data = response.data?.data || [];
    
    staffData.value = data || [];
  } catch (error) {
    console.error('Error loading staff report:', error);
    staffData.value = [];
    toast.error(t('common.errorLoading') || 'Error loading data');
  } finally {
    loadingStaff.value = false;
  }
};

const loadDailySales = async () => {
  loadingDaily.value = true;
  try {
    const response = await api.get('/reports/daily-sales', {
      params: dailyFilters.value,
      noCache: true,
    });
    dailySalesData.value = response.data?.data || response.data || null;
  } catch (error) {
    console.error('Error loading daily sales:', error);
    dailySalesData.value = null;
    toast.error(t('common.errorLoading') || 'Error loading data');
  } finally {
    loadingDaily.value = false;
  }
};

const loadQueueStats = async () => {
  loadingQueue.value = true;
  try {
    const response = await api.get('/reports/queue-stats', {
      params: queueFilters.value,
      noCache: true,
    });
    queueStats.value = response.data?.data || response.data || null;
  } catch (error) {
    console.error('Error loading queue stats:', error);
    queueStats.value = null;
    toast.error(t('common.errorLoading') || 'Error loading data');
  } finally {
    loadingQueue.value = false;
  }
};

const loadCommissionsReport = async () => {
  loadingCommissions.value = true;
  try {
    const response = await api.get('/reports/commissions', {
      params: commissionsFilters.value,
      noCache: true,
    });
    commissionsData.value = response.data?.data?.data || response.data?.data || [];
  } catch (error) {
    console.error('Error loading commissions report:', error);
    commissionsData.value = [];
    toast.error(t('common.errorLoading') || 'Error loading data');
  } finally {
    loadingCommissions.value = false;
  }
};

const exportReport = () => {
  // CSV export for current tab (start with Sales)
  try {
    if (activeTab.value !== 'sales') {
      toast.info(t('common.comingSoon') || 'Coming soon');
      return;
    }

    const rows = Array.isArray(salesData.value) ? salesData.value : [];
    const header = ['date', 'orders_count', 'total_revenue', 'paid_amount'];
    const csv = [
      header.join(','),
      ...rows.map((r) =>
        [
          r?.date ?? '',
          Number(r?.total_orders || 0),
          Number(r?.total_revenue || 0),
          Number(r?.paid_amount || 0),
        ].join(',')
      ),
    ].join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `sales-report_${salesFilters.value.start_date}_to_${salesFilters.value.end_date}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
    toast.success(t('common.export') || 'Exported');
  } catch (e) {
    console.error(e);
    toast.error(t('common.error') || 'Error');
  }
};

onMounted(() => {
  loadOverview();
});

// ---- Charts computed ----
const doughnutOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: themeColors.value.text,
        usePointStyle: true,
        boxWidth: 8,
      },
    },
  },
}));

const salesTrendData = computed(() => {
  const labels = overview.value?.charts?.sales?.labels || [];
  const revenue = overview.value?.charts?.sales?.revenue || [];
  const orders = overview.value?.charts?.sales?.orders || [];

  return {
    labels,
    datasets: [
      {
        label: t('reports.revenue') || 'Revenue',
        data: revenue,
        borderColor: themeColors.value.primary,
        backgroundColor: themeColors.value.primarySoft,
        tension: 0.35,
        fill: true,
        yAxisID: 'y',
      },
      {
        label: t('sales.orders') || 'Orders',
        data: orders,
        borderColor: themeColors.value.success,
        backgroundColor: 'transparent',
        tension: 0.35,
        fill: false,
        yAxisID: 'y1',
      },
    ],
  };
});

const salesTrendOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { labels: { color: themeColors.value.text } },
    tooltip: { mode: 'index', intersect: false },
  },
  interaction: { mode: 'index', intersect: false },
  scales: {
    x: { ticks: { color: themeColors.value.muted }, grid: { color: themeColors.value.border } },
    y: { ticks: { color: themeColors.value.muted }, grid: { color: themeColors.value.border } },
    y1: {
      position: 'right',
      ticks: { color: themeColors.value.muted },
      grid: { drawOnChartArea: false },
    },
  },
}));

const loyaltyTrendData = computed(() => {
  const labels = overview.value?.charts?.loyalty?.labels || [];
  const earned = overview.value?.charts?.loyalty?.earned || [];
  const redeemed = overview.value?.charts?.loyalty?.redeemed || [];
  return {
    labels,
    datasets: [
      {
        label: t('reports.loyaltyEarned') || 'Earned',
        data: earned,
        borderColor: themeColors.value.success,
        backgroundColor: cssVar('--asmaa-success-soft', 'rgba(34,197,94,0.14)'),
        tension: 0.35,
        fill: true,
      },
      {
        label: t('reports.loyaltyRedeemed') || 'Redeemed',
        data: redeemed,
        borderColor: themeColors.value.warning,
        backgroundColor: cssVar('--asmaa-warning-soft', 'rgba(245,158,11,0.14)'),
        tension: 0.35,
        fill: true,
      },
    ],
  };
});

const loyaltyTrendOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { labels: { color: themeColors.value.text } } },
  scales: {
    x: { ticks: { color: themeColors.value.muted }, grid: { color: themeColors.value.border } },
    y: { ticks: { color: themeColors.value.muted }, grid: { color: themeColors.value.border } },
  },
}));

const simpleLineOptions = loyaltyTrendOptions;

const bookingsStatusData = computed(() => {
  const obj = overview.value?.charts?.bookings_status || {};
  const labels = Object.keys(obj);
  const values = Object.values(obj);
  return {
    labels,
    datasets: [
      {
        data: values,
        backgroundColor: [
          cssVar('--asmaa-warning', '#f59e0b'),
          cssVar('--asmaa-info', '#3b82f6'),
          cssVar('--asmaa-success', '#22c55e'),
          cssVar('--asmaa-secondary', '#334155'),
        ],
        borderColor: cssVar('--bg-primary', '#fff'),
        borderWidth: 2,
      },
    ],
  };
});

const invoicesStatusData = computed(() => {
  const obj = overview.value?.charts?.invoices_status || {};
  const labels = Object.keys(obj);
  const values = Object.values(obj);
  return {
    labels,
    datasets: [
      {
        data: values,
        backgroundColor: [
          cssVar('--asmaa-primary', '#BBA07A'),
          cssVar('--asmaa-success', '#22c55e'),
          cssVar('--asmaa-warning', '#f59e0b'),
          cssVar('--asmaa-danger', '#ef4444'),
          cssVar('--asmaa-secondary', '#334155'),
        ],
        borderColor: cssVar('--bg-primary', '#fff'),
        borderWidth: 2,
      },
    ],
  };
});

const paymentsMethodsData = computed(() => {
  const labels = overview.value?.charts?.payments_methods?.labels || [];
  const totals = overview.value?.charts?.payments_methods?.totals || [];
  return {
    labels,
    datasets: [
      {
        data: totals,
        backgroundColor: [
          cssVar('--asmaa-primary', '#BBA07A'),
          cssVar('--asmaa-success', '#22c55e'),
          cssVar('--asmaa-warning', '#f59e0b'),
          cssVar('--asmaa-info', '#3b82f6'),
          cssVar('--asmaa-secondary', '#334155'),
        ],
        borderColor: cssVar('--bg-primary', '#fff'),
        borderWidth: 2,
      },
    ],
  };
});

const queueStatusData = computed(() => {
  const obj = overview.value?.charts?.queue_status || {};
  const labels = Object.keys(obj);
  const values = Object.values(obj);
  return {
    labels,
    datasets: [
      {
        data: values,
        backgroundColor: [
          cssVar('--asmaa-primary', '#BBA07A'),
          cssVar('--asmaa-info', '#3b82f6'),
          cssVar('--asmaa-warning', '#f59e0b'),
          cssVar('--asmaa-success', '#22c55e'),
          cssVar('--asmaa-danger', '#ef4444'),
          cssVar('--asmaa-secondary', '#334155'),
        ],
        borderColor: cssVar('--bg-primary', '#fff'),
        borderWidth: 2,
      },
    ],
  };
});

const barOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { ticks: { color: themeColors.value.muted }, grid: { color: themeColors.value.border } },
    y: { ticks: { color: themeColors.value.muted }, grid: { color: themeColors.value.border } },
  },
}));

const topServicesData = computed(() => {
  const labels = overview.value?.charts?.top_services?.labels || [];
  const revenue = overview.value?.charts?.top_services?.revenue || [];
  return {
    labels,
    datasets: [{ label: t('reports.revenue') || 'Revenue', data: revenue, backgroundColor: themeColors.value.primary }],
  };
});

const topProductsData = computed(() => {
  const labels = overview.value?.charts?.top_products?.labels || [];
  const revenue = overview.value?.charts?.top_products?.revenue || [];
  return {
    labels,
    datasets: [{ label: t('reports.revenue') || 'Revenue', data: revenue, backgroundColor: themeColors.value.warning }],
  };
});

const commissionsByStaffData = computed(() => {
  const labels = overview.value?.charts?.commissions_by_staff?.labels || [];
  const totals = overview.value?.charts?.commissions_by_staff?.totals || [];
  return {
    labels,
    datasets: [{ label: t('commissions.title') || 'Commissions', data: totals, backgroundColor: themeColors.value.success }],
  };
});

const newCustomersData = computed(() => {
  const labels = overview.value?.charts?.new_customers?.labels || [];
  const counts = overview.value?.charts?.new_customers?.counts || [];
  return {
    labels,
    datasets: [
      {
        label: t('reports.newCustomers') || 'New Customers',
        data: counts,
        borderColor: themeColors.value.primary,
        backgroundColor: themeColors.value.primarySoft,
        tension: 0.35,
        fill: true,
      },
    ],
  };
});
</script>

<style scoped>
.reports-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.btn-primary-custom {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  border: none;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.25);
  transition: all 0.25s ease;
}

.btn-primary-custom:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(187, 160, 122, 0.35);
}

.export-btn {
  border-color: var(--border-color);
}

.reports-tabs :deep(.nav-link) {
  color: var(--text-secondary);
}
.reports-tabs :deep(.nav-link.active),
.reports-tabs :deep(.nav-link:hover) {
  color: var(--asmaa-primary);
}

.filters-card {
  border: 1px solid var(--border-color);
}

.table-wrapper {
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow: hidden;
}

.table-modern {
  margin: 0;
}

.table-header-row th {
  background: var(--bg-secondary);
  color: var(--text-secondary);
  font-weight: 800;
  border-bottom: 1px solid var(--border-color);
}

.charts-card :deep(.card-body) {
  padding-top: 0.75rem;
}

.charts-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 1rem;
}

.chart-panel {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  padding: 1rem;
}

.chart-panel-wide {
  grid-column: span 2;
}

.chart-title {
  font-weight: 900;
  color: var(--text-primary);
  font-size: 0.9rem;
  margin-bottom: 0.75rem;
}

.chart-canvas {
  position: relative;
  height: 280px;
}

.chart-canvas-sm {
  height: 260px;
}

@media (max-width: 1200px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
  .chart-panel-wide {
    grid-column: span 1;
  }
}

.table-row {
  transition: all 0.2s;
}

.table-row:hover {
  background-color: var(--bg-tertiary);
}

.comparison-cards {
  margin-bottom: 1.5rem;
}

.comparison-card {
  background: var(--bg-secondary);
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid var(--border-color);
}

.comparison-card h6 {
  margin-bottom: 0.5rem;
  color: var(--text-muted);
  font-size: 0.875rem;
}

.comparison-card p {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.payment-methods {
  margin-top: 1.5rem;
}

.payment-methods h5 {
  color: var(--text-primary);
  margin-bottom: 1rem;
}

.unified-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-weight: 800;
  font-size: 0.85rem;
  padding: 0.35rem 0.6rem;
  border-radius: 999px;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.unified-badge.primary-badge {
  background: var(--asmaa-primary-soft);
  border-color: var(--asmaa-primary-soft-border);
  color: var(--asmaa-primary);
}

.unified-badge.success-badge {
  background: var(--asmaa-success-soft);
  border-color: var(--asmaa-success-soft-border);
  color: var(--asmaa-success);
}

.badge-icon {
  font-size: 0.95rem;
}

.unified-amount {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-weight: 900;
  color: var(--text-primary);
}

.money-icon {
  color: var(--asmaa-success);
}

.count-cell {
  font-size: 0.9375rem;
  color: var(--text-primary);
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
