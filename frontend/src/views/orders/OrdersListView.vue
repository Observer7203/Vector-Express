<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useOrdersStore } from '@/stores/orders'
import {
  Plus,
  Package,
  Truck,
  MapPin,
  Calendar,
  DollarSign,
  Clock,
  CheckCircle,
  XCircle,
  AlertCircle,
  ChevronRight,
  Search,
  Filter,
  FileText
} from 'lucide-vue-next'

const { t } = useI18n()

const iconStrokeWidth = 1.2

const ordersStore = useOrdersStore()
const searchQuery = ref('')
const statusFilter = ref('')

onMounted(() => {
  ordersStore.fetchOrders()
})

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

const statusConfig = {
  pending: { color: 'warning', icon: Clock },
  confirmed: { color: 'info', icon: CheckCircle },
  pickup_scheduled: { color: 'info', icon: Calendar },
  picked_up: { color: 'primary', icon: Package },
  in_transit: { color: 'primary', icon: Truck },
  customs: { color: 'warning', icon: FileText },
  out_for_delivery: { color: 'primary', icon: Truck },
  delivered: { color: 'success', icon: CheckCircle },
  cancelled: { color: 'danger', icon: XCircle }
}

const filteredOrders = computed(() => {
  let orders = ordersStore.orders

  if (statusFilter.value) {
    orders = orders.filter(o => o.status === statusFilter.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    orders = orders.filter(o =>
      o.order_number?.toLowerCase().includes(query) ||
      o.tracking_number?.toLowerCase().includes(query) ||
      o.shipment?.origin_city?.toLowerCase().includes(query) ||
      o.shipment?.destination_city?.toLowerCase().includes(query)
    )
  }

  return orders
})

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

function formatPrice(value, currency = 'USD') {
  if (!value && value !== 0) return '-'
  return Number(value).toLocaleString('ru-RU', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }) + ' ' + currency
}

function getStatusIcon(status) {
  return statusConfig[status]?.icon || AlertCircle
}
</script>

<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <div class="header-content">
          <div class="header-left">
            <h1>{{ t('ordersList.title') }}</h1>
            <p class="subtitle" v-if="ordersStore.orders.length > 0">
              {{ t('ordersList.totalOrders', { count: ordersStore.orders.length }) }}
            </p>
          </div>
          <RouterLink to="/shipments/new" class="btn btn-primary">
            <Plus :size="18" :stroke-width="iconStrokeWidth" />
            {{ t('ordersList.newRequest') }}
          </RouterLink>
        </div>
      </div>
    </header>

    <main class="page-content">
      <div class="container">
        <!-- Filters Bar -->
        <div class="filters-bar" v-if="ordersStore.orders.length > 0">
          <div class="search-box">
            <Search :size="18" :stroke-width="iconStrokeWidth" class="search-icon" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('ordersList.searchPlaceholder')"
            />
          </div>
          <div class="filter-group">
            <Filter :size="16" :stroke-width="iconStrokeWidth" />
            <select v-model="statusFilter">
              <option value="">{{ t('status.allStatuses') }}</option>
              <option v-for="(label, status) in statusLabels" :key="status" :value="status">
                {{ label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="ordersStore.loading" class="loading-state">
          <div class="spinner"></div>
          <span>{{ t('ordersList.loadingOrders') }}</span>
        </div>

        <!-- Empty State -->
        <div v-else-if="ordersStore.orders.length === 0" class="empty-state">
          <div class="empty-icon">
            <Package :size="48" :stroke-width="iconStrokeWidth" />
          </div>
          <h3>{{ t('ordersList.noOrdersYet') }}</h3>
          <p>{{ t('ordersList.createRequestPrompt') }}</p>
          <RouterLink to="/shipments" class="btn btn-primary">
            {{ t('ordersList.goToRequests') }}
          </RouterLink>
        </div>

        <!-- No Results -->
        <div v-else-if="filteredOrders.length === 0" class="empty-state">
          <div class="empty-icon">
            <Search :size="48" :stroke-width="iconStrokeWidth" />
          </div>
          <h3>{{ t('ordersList.nothingFound') }}</h3>
          <p>{{ t('ordersList.tryChangingFilters') }}</p>
          <button @click="searchQuery = ''; statusFilter = ''" class="btn btn-outline">
            {{ t('ordersList.resetFilters') }}
          </button>
        </div>

        <!-- Orders List -->
        <div v-else class="orders-list">
          <RouterLink
            v-for="order in filteredOrders"
            :key="order.id"
            :to="`/orders/${order.id}`"
            class="order-card"
          >
            <div class="order-header">
              <div class="order-info">
                <span class="order-number">{{ order.order_number || `#${order.id}` }}</span>
                <span class="order-date">{{ formatDate(order.created_at) }}</span>
              </div>
              <span :class="['status-badge', `status-${statusConfig[order.status]?.color}`]">
                <component :is="getStatusIcon(order.status)" :size="14" :stroke-width="iconStrokeWidth" />
                {{ statusLabels[order.status] }}
              </span>
            </div>

            <div class="order-route" v-if="order.shipment">
              <div class="route-point">
                <div class="point-marker origin"></div>
                <div class="point-details">
                  <span class="point-city">{{ order.shipment.origin_city }}</span>
                  <span class="point-country">{{ order.shipment.origin_country }}</span>
                </div>
              </div>
              <div class="route-line">
                <Truck :size="16" :stroke-width="iconStrokeWidth" />
              </div>
              <div class="route-point">
                <div class="point-marker destination"></div>
                <div class="point-details">
                  <span class="point-city">{{ order.shipment.destination_city }}</span>
                  <span class="point-country">{{ order.shipment.destination_country }}</span>
                </div>
              </div>
            </div>

            <div class="order-meta">
              <div class="meta-item" v-if="order.carrier">
                <Truck :size="16" :stroke-width="iconStrokeWidth" />
                <span>{{ order.carrier.name }}</span>
              </div>
              <div class="meta-item" v-if="order.total_amount">
                <DollarSign :size="16" :stroke-width="iconStrokeWidth" />
                <span class="price">{{ formatPrice(order.total_amount, order.currency) }}</span>
              </div>
              <div class="meta-item" v-if="order.tracking_number">
                <MapPin :size="16" :stroke-width="iconStrokeWidth" />
                <span class="tracking">{{ order.tracking_number }}</span>
              </div>
            </div>

            <div class="order-footer">
              <span class="view-details">
                {{ t('common.details') }}
                <ChevronRight :size="16" :stroke-width="iconStrokeWidth" />
              </span>
            </div>
          </RouterLink>
        </div>

        <!-- Pagination -->
        <div class="pagination" v-if="ordersStore.pagination?.lastPage > 1">
          <button
            class="btn btn-outline btn-sm"
            :disabled="ordersStore.pagination.currentPage <= 1"
            @click="ordersStore.fetchOrders(ordersStore.pagination.currentPage - 1)"
          >
            {{ t('common.back') }}
          </button>
          <span class="page-info">
            {{ t('ordersList.pageInfo', { current: ordersStore.pagination.currentPage, last: ordersStore.pagination.lastPage }) }}
          </span>
          <button
            class="btn btn-outline btn-sm"
            :disabled="ordersStore.pagination.currentPage >= ordersStore.pagination.lastPage"
            @click="ordersStore.fetchOrders(ordersStore.pagination.currentPage + 1)"
          >
            {{ t('common.next') }}
          </button>
        </div>
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.page {
  min-height: 100vh;
  background: $bg-light;
}

.page-header {
  background: $bg-white;
  border-bottom: 1px solid $border-color;
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0 $spacing-lg;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
}

.header-left {
  h1 {
    font-size: $font-size-2xl;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
    line-height: 1.3;
  }

  .subtitle {
    font-size: $font-size-sm;
    color: $text-secondary;
    margin: 4px 0 0;
  }
}

.page-content {
  padding: $spacing-lg 0 $spacing-2xl;
}

// Filters Bar
.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.search-box {
  flex: 1;
  min-width: 250px;
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
    background: $bg-white;
    height: 40px;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }

    &::placeholder {
      color: $text-muted;
    }
  }
}

.filter-group {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  color: $text-secondary;

  select {
    padding: 10px 12px;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    background: $bg-white;
    min-width: 180px;
    height: 40px;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

// Loading State
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: $spacing-3xl $spacing-lg;
  gap: 12px;
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

// Empty State
.empty-state {
  background: $bg-white;
  border-radius: $radius-xl;
  border: 1px solid $border-color;
  padding: $spacing-2xl $spacing-lg;
  text-align: center;
}

.empty-icon {
  width: 72px;
  height: 72px;
  background: $bg-light;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;

  svg {
    color: $text-muted;
  }
}

.empty-state h3 {
  font-size: $font-size-lg;
  font-weight: 600;
  color: $text-primary;
  margin: 0 0 $spacing-xs;
}

.empty-state p {
  color: $text-secondary;
  font-size: $font-size-sm;
  margin: 0 0 20px;
  line-height: 1.5;
}

// Orders List
.orders-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.order-card {
  background: $bg-white;
  border-radius: $radius-xl;
  border: 1px solid $border-color;
  padding: $card-padding;
  text-decoration: none;
  transition: all $transition-base;
  display: block;

  &:hover {
    border-color: rgba($color-primary, 0.3);
    box-shadow: $shadow;
  }
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.order-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.order-number {
  font-weight: 600;
  font-size: 15px;
  color: $text-primary;
}

.order-date {
  font-size: $font-size-xs;
  color: $text-muted;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;

  svg {
    flex-shrink: 0;
  }
}

.status-warning {
  background: rgba($color-warning, 0.1);
  color: darken($color-warning, 15%);
}

.status-info {
  background: rgba($color-info, 0.1);
  color: darken($color-info, 10%);
}

.status-primary {
  background: rgba($color-primary, 0.1);
  color: $color-primary;
}

.status-success {
  background: rgba($color-success, 0.1);
  color: darken($color-success, 10%);
}

.status-danger {
  background: rgba($color-danger, 0.1);
  color: $color-danger;
}

// Route Display
.order-route {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid $border-color;
  margin-bottom: 12px;
}

.route-point {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.point-marker {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;

  &.origin {
    background: $color-primary;
  }

  &.destination {
    background: $color-success;
  }
}

.point-details {
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.point-city {
  font-weight: 600;
  font-size: $font-size-sm;
  color: $text-primary;
  line-height: 1.3;
}

.point-country {
  font-size: $font-size-xs;
  color: $text-muted;
  line-height: 1.3;
}

.route-line {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: $bg-light;
  border-radius: 50%;
  color: $color-primary;
  flex-shrink: 0;
}

// Order Meta
.order-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px 24px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: $font-size-sm;
  color: $text-secondary;

  svg {
    color: $text-muted;
    flex-shrink: 0;
  }

  .price {
    font-weight: 600;
    color: $text-primary;
  }

  .tracking {
    font-family: monospace;
    background: $bg-light;
    padding: 2px 6px;
    border-radius: $radius-sm;
    font-size: $font-size-xs;
  }
}

// Order Footer
.order-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid $border-color;
}

.view-details {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: $font-size-sm;
  color: $color-primary;
  font-weight: 500;

  svg {
    transition: transform $transition-fast;
  }

  .order-card:hover & svg {
    transform: translateX(3px);
  }
}

// Pagination
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  margin-top: $spacing-xl;
}

.page-info {
  font-size: $font-size-sm;
  color: $text-secondary;
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
  text-decoration: none;
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
  color: $color-primary;
  border: 1px solid $color-primary;

  &:hover:not(:disabled) {
    background: rgba($color-primary, 0.05);
  }
}

.btn-sm {
  padding: 8px 14px;
  font-size: $font-size-xs;
  height: 34px;
}

// Responsive
@media (max-width: $breakpoint-md) {
  .container {
    padding: 0 $spacing-md;
  }

  .header-content {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }

  .filters-bar {
    flex-direction: column;
    gap: 8px;
  }

  .search-box {
    width: 100%;
  }

  .filter-group {
    width: 100%;

    select {
      flex: 1;
    }
  }

  .order-card {
    padding: $spacing-md;
  }

  .order-route {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .route-line {
    transform: rotate(90deg);
    margin: 4px 0;
    width: 28px;
    height: 28px;
  }

  .order-meta {
    flex-direction: column;
    gap: 8px;
  }
}
</style>
