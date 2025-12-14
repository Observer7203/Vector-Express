<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { useI18n } from 'vue-i18n'
import {
  Search,
  Eye,
  CheckCircle,
  X,
  Package,
  Truck,
  MapPin
} from 'lucide-vue-next'

const adminStore = useAdminStore()
const { t } = useI18n()
const iconStrokeWidth = 1.5

const search = ref('')
const filterStatus = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const showDetailsModal = ref(false)
const showStatusModal = ref(false)
const selectedOrder = ref(null)
const statusOrder = ref(null)
const newStatus = ref('')
const updatingStatus = ref(false)

const statusLabels = computed(() => ({
  pending: t('status.pending'),
  confirmed: t('status.confirmed'),
  pickup_scheduled: t('status.pickup_scheduled'),
  picked_up: t('status.picked_up'),
  in_transit: t('status.in_transit'),
  customs: t('status.customs'),
  out_for_delivery: t('status.out_for_delivery'),
  delivered: t('status.delivered'),
  cancelled: t('status.cancelled')
}))

const statusColors = {
  pending: 'warning',
  confirmed: 'info',
  pickup_scheduled: 'info',
  picked_up: 'primary',
  in_transit: 'primary',
  customs: 'warning',
  out_for_delivery: 'info',
  delivered: 'success',
  cancelled: 'danger'
}

onMounted(() => {
  adminStore.fetchOrders()
  adminStore.fetchOrderStatistics()
})

watch([search, filterStatus, dateFrom, dateTo], () => {
  adminStore.fetchOrders({
    search: search.value,
    status: filterStatus.value,
    date_from: dateFrom.value,
    date_to: dateTo.value
  })
})

const orders = computed(() => adminStore.orders)
const statistics = computed(() => adminStore.orderStatistics)
const loading = computed(() => adminStore.loading)
const pagination = computed(() => adminStore.ordersPagination)

function viewOrder(order) {
  selectedOrder.value = order
  showDetailsModal.value = true
}

function openStatusModal(order) {
  statusOrder.value = order
  newStatus.value = order.status
  showStatusModal.value = true
}

async function updateStatus() {
  updatingStatus.value = true
  try {
    await adminStore.updateOrderStatus(statusOrder.value.id, newStatus.value)
    showStatusModal.value = false
    adminStore.fetchOrderStatistics()
  } catch (e) {
    console.error('Error updating status:', e)
  } finally {
    updatingStatus.value = false
  }
}

function changePage(page) {
  adminStore.fetchOrders({
    page,
    search: search.value,
    status: filterStatus.value,
    date_from: dateFrom.value,
    date_to: dateTo.value
  })
}

function resetFilters() {
  search.value = ''
  filterStatus.value = ''
  dateFrom.value = ''
  dateTo.value = ''
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('ru-RU')
}

function formatDateTime(dateString) {
  return new Date(dateString).toLocaleString('ru-RU')
}

function formatPrice(amount, currency = 'USD') {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: currency
  }).format(amount || 0)
}
</script>

<template>
  <div class="orders-page">
    <div class="page-header">
      <div>
        <h1>{{ t('admin.orders') }}</h1>
        <p class="subtitle">{{ t('adminOrders.subtitle') }}</p>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon total">
          <Package :size="20" :stroke-width="iconStrokeWidth" />
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ statistics.total || 0 }}</span>
          <span class="stat-label">{{ t('adminOrders.stats.total') }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon pending">
          <Package :size="20" :stroke-width="iconStrokeWidth" />
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ statistics.pending || 0 }}</span>
          <span class="stat-label">{{ t('adminOrders.stats.pending') }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon in-progress">
          <Truck :size="20" :stroke-width="iconStrokeWidth" />
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ statistics.in_progress || 0 }}</span>
          <span class="stat-label">{{ t('adminOrders.stats.inProgress') }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon delivered">
          <CheckCircle :size="20" :stroke-width="iconStrokeWidth" />
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ statistics.delivered || 0 }}</span>
          <span class="stat-label">{{ t('adminOrders.stats.delivered') }}</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="search-box">
        <Search :size="18" :stroke-width="iconStrokeWidth" class="search-icon" />
        <input
          v-model="search"
          type="text"
          :placeholder="t('adminOrders.searchPlaceholder')"
        />
      </div>
      <div class="filter-group">
        <select v-model="filterStatus">
          <option value="">{{ t('status.allStatuses') }}</option>
          <option v-for="(label, key) in statusLabels" :key="key" :value="key">
            {{ label }}
          </option>
        </select>
      </div>
      <div class="filter-group">
        <input v-model="dateFrom" type="date" :placeholder="t('adminOrders.dateFrom')" />
      </div>
      <div class="filter-group">
        <input v-model="dateTo" type="date" :placeholder="t('adminOrders.dateTo')" />
      </div>
      <button @click="resetFilters" class="btn btn-sm btn-outline">
        {{ t('adminOrders.reset') }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <span>{{ t('common.loading') }}</span>
    </div>

    <!-- Table -->
    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ t('adminOrders.table.number') }}</th>
            <th>{{ t('adminOrders.table.customer') }}</th>
            <th>{{ t('adminOrders.table.carrier') }}</th>
            <th>{{ t('adminOrders.table.route') }}</th>
            <th>{{ t('adminOrders.table.amount') }}</th>
            <th>{{ t('adminOrders.table.status') }}</th>
            <th>{{ t('adminOrders.table.date') }}</th>
            <th>{{ t('adminOrders.table.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id">
            <td>
              <div class="order-number-cell">
                <span class="order-number">{{ order.order_number }}</span>
                <span class="tracking-number">{{ order.tracking_number }}</span>
              </div>
            </td>
            <td>
              <div class="customer-cell">
                <span class="customer-name">{{ order.user?.name || '-' }}</span>
                <span class="customer-company">{{ order.user?.company?.name || '' }}</span>
              </div>
            </td>
            <td>
              <span class="carrier-name">{{ order.carrier?.company?.name || '-' }}</span>
            </td>
            <td>
              <div class="route-cell">
                <div class="route-from">
                  <MapPin :size="12" :stroke-width="iconStrokeWidth" />
                  <span>{{ order.quote?.shipment?.origin_city }}, {{ order.quote?.shipment?.origin_country }}</span>
                </div>
                <div class="route-to">
                  <MapPin :size="12" :stroke-width="iconStrokeWidth" />
                  <span>{{ order.quote?.shipment?.destination_city }}, {{ order.quote?.shipment?.destination_country }}</span>
                </div>
              </div>
            </td>
            <td>
              <div class="price-cell">
                <span class="price">{{ formatPrice(order.total_amount, order.currency) }}</span>
                <span class="commission">{{ t('order.commission') }}: {{ formatPrice(order.commission_amount, order.currency) }}</span>
              </div>
            </td>
            <td>
              <span class="status-badge" :class="statusColors[order.status]">
                {{ statusLabels[order.status] }}
              </span>
            </td>
            <td>
              <span class="date">{{ formatDate(order.created_at) }}</span>
            </td>
            <td>
              <div class="actions">
                <button @click="viewOrder(order)" class="action-btn" :title="t('adminOrders.view')">
                  <Eye :size="16" :stroke-width="iconStrokeWidth" />
                </button>
                <button @click="openStatusModal(order)" class="action-btn success" :title="t('adminOrders.changeStatus')">
                  <CheckCircle :size="16" :stroke-width="iconStrokeWidth" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="orders.length === 0">
            <td colspan="8" class="empty-row">
              {{ t('adminOrders.noOrders') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.lastPage > 1" class="pagination">
      <button
        @click="changePage(pagination.currentPage - 1)"
        :disabled="pagination.currentPage === 1"
        class="btn btn-sm btn-outline"
      >
        {{ t('common.back') }}
      </button>
      <span class="page-info">
        {{ t('adminOrders.pageInfo', { current: pagination.currentPage, last: pagination.lastPage }) }}
      </span>
      <button
        @click="changePage(pagination.currentPage + 1)"
        :disabled="pagination.currentPage === pagination.lastPage"
        class="btn btn-sm btn-outline"
      >
        {{ t('common.next') }}
      </button>
    </div>

    <!-- Order Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="showDetailsModal = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>{{ t('adminOrders.orderNumber', { number: selectedOrder?.order_number }) }}</h2>
          <button @click="showDetailsModal = false" class="close-btn">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div v-if="selectedOrder" class="modal-body">
          <!-- Status -->
          <div class="order-status-header">
            <span class="status-badge large" :class="statusColors[selectedOrder.status]">
              {{ statusLabels[selectedOrder.status] }}
            </span>
            <span class="order-date">{{ formatDateTime(selectedOrder.created_at) }}</span>
          </div>

          <!-- Route Info -->
          <div class="detail-section">
            <h3>{{ t('order.route') }}</h3>
            <div class="route-details">
              <div class="route-point">
                <div class="route-label">{{ t('shipment.origin') }}</div>
                <div class="route-value">
                  {{ selectedOrder.quote?.shipment?.origin_city }}, {{ selectedOrder.quote?.shipment?.origin_country }}
                </div>
                <div class="route-address">{{ selectedOrder.quote?.shipment?.origin_address }}</div>
              </div>
              <div class="route-arrow">→</div>
              <div class="route-point">
                <div class="route-label">{{ t('shipment.destination') }}</div>
                <div class="route-value">
                  {{ selectedOrder.quote?.shipment?.destination_city }}, {{ selectedOrder.quote?.shipment?.destination_country }}
                </div>
                <div class="route-address">{{ selectedOrder.quote?.shipment?.destination_address }}</div>
              </div>
            </div>
          </div>

          <!-- Cargo Info -->
          <div class="detail-section">
            <h3>{{ t('order.cargo') }}</h3>
            <div class="cargo-grid">
              <div class="cargo-item">
                <span class="cargo-label">{{ t('shipment.weight') }}</span>
                <span class="cargo-value">{{ selectedOrder.quote?.shipment?.total_weight }} {{ t('shipment.kg') }}</span>
              </div>
              <div class="cargo-item">
                <span class="cargo-label">{{ t('shipment.volume') }}</span>
                <span class="cargo-value">{{ selectedOrder.quote?.shipment?.total_volume }} {{ t('shipment.cbm') }}</span>
              </div>
              <div class="cargo-item">
                <span class="cargo-label">{{ t('shipment.cargoType') }}</span>
                <span class="cargo-value">{{ selectedOrder.quote?.shipment?.cargo_type }}</span>
              </div>
            </div>
          </div>

          <!-- Parties -->
          <div class="detail-section parties-section">
            <div class="party-block">
              <h3>{{ t('order.customer') }}</h3>
              <div class="party-info">
                <div class="party-name">{{ selectedOrder.user?.name }}</div>
                <div class="party-detail">{{ selectedOrder.user?.email }}</div>
                <div class="party-detail">{{ selectedOrder.user?.company?.name }}</div>
              </div>
            </div>
            <div class="party-block">
              <h3>{{ t('quote.carrier') }}</h3>
              <div class="party-info">
                <div class="party-name">{{ selectedOrder.carrier?.company?.name }}</div>
                <div class="party-detail">{{ selectedOrder.carrier?.company?.phone }}</div>
              </div>
            </div>
          </div>

          <!-- Contacts -->
          <div class="detail-section">
            <h3>{{ t('adminOrders.deliveryContacts') }}</h3>
            <div class="contacts-grid">
              <div>
                <div class="contact-label">{{ t('order.contactPerson') }}</div>
                <div class="contact-value">{{ selectedOrder.contact_name }}</div>
                <div class="contact-detail">{{ selectedOrder.contact_phone }}</div>
                <div class="contact-detail">{{ selectedOrder.contact_email }}</div>
              </div>
              <div>
                <div class="contact-label">{{ t('order.deliveryAddress') }}</div>
                <div class="contact-value">{{ selectedOrder.delivery_contact_name }}</div>
                <div class="contact-detail">{{ selectedOrder.delivery_address }}</div>
              </div>
            </div>
          </div>

          <!-- Financial -->
          <div class="detail-section financial-section">
            <h3>{{ t('adminOrders.financial') }}</h3>
            <div class="financial-info">
              <div class="total-amount">
                {{ formatPrice(selectedOrder.total_amount, selectedOrder.currency) }}
              </div>
              <div class="commission-info">
                {{ t('order.commission') }}: {{ formatPrice(selectedOrder.commission_amount, selectedOrder.currency) }} (5%)
              </div>
            </div>
            <div class="tracking-info">
              <div class="tracking-label">{{ t('shipment.trackingNumber') }}</div>
              <code class="tracking-code">{{ selectedOrder.tracking_number }}</code>
              <code v-if="selectedOrder.carrier_tracking_number" class="tracking-code secondary">
                {{ selectedOrder.carrier_tracking_number }}
              </code>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Change Modal -->
    <div v-if="showStatusModal" class="modal-overlay" @click.self="showStatusModal = false">
      <div class="modal modal-sm">
        <div class="modal-header">
          <h2>{{ t('adminOrders.changeStatus') }}</h2>
          <button @click="showStatusModal = false" class="close-btn">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <p class="status-order-info">
            {{ t('adminOrders.orderLabel') }}: <strong>{{ statusOrder?.order_number }}</strong>
          </p>
          <div class="form-group">
            <label>{{ t('adminOrders.newStatus') }}</label>
            <select v-model="newStatus">
              <option v-for="(label, key) in statusLabels" :key="key" :value="key">
                {{ label }}
              </option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="showStatusModal = false" class="btn btn-outline">{{ t('common.cancel') }}</button>
          <button
            @click="updateStatus"
            class="btn btn-primary"
            :disabled="updatingStatus"
          >
            <span v-if="updatingStatus">{{ t('adminOrders.saving') }}</span>
            <span v-else>{{ t('common.save') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.orders-page {
  h1 {
    font-size: $font-size-2xl;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
  }

  .subtitle {
    font-size: $font-size-sm;
    color: $text-secondary;
    margin: $spacing-xs 0 0;
  }
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: $spacing-lg;
}

// Statistics
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $spacing-md;
  margin-bottom: $spacing-lg;
}

.stat-card {
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-xl;
  padding: $spacing-md;
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: $radius-lg;
  display: flex;
  align-items: center;
  justify-content: center;

  &.total {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.pending {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
  }

  &.in-progress {
    background: rgba(#3b82f6, 0.1);
    color: #3b82f6;
  }

  &.delivered {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: $font-size-2xl;
  font-weight: 700;
  color: $text-primary;
}

.stat-label {
  font-size: $font-size-xs;
  color: $text-secondary;
}

// Filters
.filters-bar {
  display: flex;
  gap: $spacing-md;
  margin-bottom: $spacing-lg;
  align-items: center;
}

.search-box {
  flex: 1;
  max-width: 250px;
  position: relative;

  .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: $text-muted;
  }

  input {
    width: 100%;
    padding: 10px 12px 10px 40px;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    height: 40px;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

.filter-group {
  select, input {
    padding: 10px 12px;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    height: 40px;
    min-width: 150px;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

// Loading
.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: $spacing-3xl;
  gap: $spacing-md;
  color: $text-secondary;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid $border-color;
  border-top-color: $color-primary;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

// Table
.table-container {
  background: $bg-white;
  border-radius: $radius-xl;
  border: 1px solid $border-color;
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;

  th, td {
    padding: $spacing-md;
    text-align: left;
    border-bottom: 1px solid $border-color;
  }

  th {
    background: $bg-light;
    font-weight: 600;
    font-size: $font-size-xs;
    color: $text-secondary;
    text-transform: uppercase;
  }

  td {
    font-size: $font-size-sm;
  }

  tbody tr:hover {
    background: $bg-hover;
  }

  tbody tr:last-child td {
    border-bottom: none;
  }
}

.order-number-cell {
  display: flex;
  flex-direction: column;
}

.order-number {
  font-weight: 600;
  color: $color-primary;
}

.tracking-number {
  font-size: $font-size-xs;
  color: $text-muted;
  font-family: monospace;
}

.customer-cell {
  display: flex;
  flex-direction: column;
}

.customer-name {
  font-weight: 500;
  color: $text-primary;
}

.customer-company {
  font-size: $font-size-xs;
  color: $text-muted;
}

.carrier-name {
  color: $text-secondary;
}

.route-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.route-from, .route-to {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: $font-size-xs;
  color: $text-secondary;

  svg {
    color: $text-muted;
    flex-shrink: 0;
  }
}

.price-cell {
  display: flex;
  flex-direction: column;
}

.price {
  font-weight: 600;
  color: $text-primary;
}

.commission {
  font-size: $font-size-xs;
  color: $text-muted;
}

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: $radius-md;
  font-size: $font-size-xs;
  font-weight: 500;

  &.warning {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
  }

  &.info {
    background: rgba(#3b82f6, 0.1);
    color: #3b82f6;
  }

  &.primary {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.success {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.danger {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }

  &.large {
    padding: 8px 16px;
    font-size: $font-size-sm;
  }
}

.date {
  color: $text-secondary;
  font-size: $font-size-xs;
}

.actions {
  display: flex;
  gap: $spacing-xs;
}

.action-btn {
  width: 32px;
  height: 32px;
  background: $bg-light;
  border: none;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: $text-secondary;
  transition: all $transition-fast;

  &:hover {
    background: $color-primary;
    color: white;
  }

  &.success:hover {
    background: $color-success;
  }
}

.empty-row {
  text-align: center;
  color: $text-muted;
  padding: $spacing-2xl !important;
}

// Pagination
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: $spacing-md;
  margin-top: $spacing-lg;
}

.page-info {
  font-size: $font-size-sm;
  color: $text-secondary;
}

// Modal styles
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

.modal {
  background: $bg-white;
  border-radius: $radius-xl;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;

  &.modal-sm {
    max-width: 400px;
  }

  &.modal-lg {
    max-width: 700px;
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-lg;
  border-bottom: 1px solid $border-color;

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    margin: 0;
  }
}

.close-btn {
  width: 32px;
  height: 32px;
  background: $bg-light;
  border: none;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: $text-secondary;

  &:hover {
    background: $border-color;
  }
}

.modal-body {
  padding: $spacing-lg;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: $spacing-sm;
  padding: $spacing-lg;
  border-top: 1px solid $border-color;
}

// Order Details Modal
.order-status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-lg;
}

.order-date {
  font-size: $font-size-sm;
  color: $text-secondary;
}

.detail-section {
  background: $bg-light;
  border-radius: $radius-lg;
  padding: $spacing-md;
  margin-bottom: $spacing-md;

  h3 {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-sm;
  }
}

.route-details {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.route-point {
  flex: 1;
}

.route-label {
  font-size: $font-size-xs;
  color: $text-muted;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.route-value {
  font-weight: 500;
  color: $text-primary;
}

.route-address {
  font-size: $font-size-xs;
  color: $text-secondary;
}

.route-arrow {
  font-size: $font-size-xl;
  color: $text-muted;
}

.cargo-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: $spacing-md;
}

.cargo-item {
  display: flex;
  flex-direction: column;
}

.cargo-label {
  font-size: $font-size-xs;
  color: $text-muted;
}

.cargo-value {
  font-weight: 500;
  color: $text-primary;
}

.parties-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: $spacing-md;
  background: transparent;
  padding: 0;
}

.party-block {
  background: $bg-light;
  border-radius: $radius-lg;
  padding: $spacing-md;

  h3 {
    margin-bottom: $spacing-sm;
  }
}

.party-name {
  font-weight: 500;
  color: $text-primary;
}

.party-detail {
  font-size: $font-size-xs;
  color: $text-secondary;
}

.contacts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: $spacing-md;
}

.contact-label {
  font-size: $font-size-xs;
  color: $text-muted;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.contact-value {
  font-weight: 500;
  color: $text-primary;
}

.contact-detail {
  font-size: $font-size-xs;
  color: $text-secondary;
}

.financial-section {
  background: rgba($color-primary, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;

  h3 {
    margin-bottom: $spacing-xs;
  }
}

.total-amount {
  font-size: $font-size-2xl;
  font-weight: 700;
  color: $text-primary;
}

.commission-info {
  font-size: $font-size-sm;
  color: $text-secondary;
}

.tracking-info {
  text-align: right;
}

.tracking-label {
  font-size: $font-size-xs;
  color: $text-muted;
  margin-bottom: 4px;
}

.tracking-code {
  display: block;
  font-family: monospace;
  font-size: $font-size-sm;
  color: $text-primary;

  &.secondary {
    font-size: $font-size-xs;
    color: $text-muted;
  }
}

// Status Modal
.status-order-info {
  font-size: $font-size-sm;
  color: $text-secondary;
  margin-bottom: $spacing-md;

  strong {
    color: $text-primary;
  }
}

.form-group {
  margin-bottom: $spacing-md;

  label {
    display: block;
    font-size: $font-size-sm;
    font-weight: 500;
    color: $text-primary;
    margin-bottom: $spacing-xs;
  }

  select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

// Buttons
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: 10px 20px;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-base;
  border: none;
  height: 40px;

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.btn-primary {
  background: $color-primary;
  color: $text-white;

  &:hover:not(:disabled) {
    background: $color-primary-dark;
  }
}

.btn-outline {
  background: $bg-white;
  color: $text-primary;
  border: 1px solid $border-color;

  &:hover:not(:disabled) {
    background: $bg-light;
  }
}

.btn-sm {
  padding: 6px 12px;
  height: 32px;
  font-size: $font-size-xs;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
