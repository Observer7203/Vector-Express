<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/client'
import AppHeader from '@/components/AppHeader.vue'
import {
  Search,
  Eye,
  CheckCircle,
  X,
  Package,
  Truck,
  MapPin,
  Clock,
  Phone,
  Mail,
  ChevronRight
} from 'lucide-vue-next'

const authStore = useAuthStore()
const iconStrokeWidth = 1.5

const search = ref('')
const filterStatus = ref('')
const orders = ref([])
const loading = ref(true)
const showDetailsModal = ref(false)
const selectedOrder = ref(null)
const updatingStatus = ref(false)

const statusLabels = {
  pending: 'Ожидает подтверждения',
  confirmed: 'Подтвержден',
  pickup_scheduled: 'Назначен забор',
  picked_up: 'Груз забран',
  in_transit: 'В пути',
  customs: 'На таможне',
  out_for_delivery: 'Доставляется',
  delivered: 'Доставлен',
  cancelled: 'Отменен'
}

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

// Next available status transitions for carrier
const nextStatusMap = {
  pending: 'confirmed',
  confirmed: 'pickup_scheduled',
  pickup_scheduled: 'picked_up',
  picked_up: 'in_transit',
  in_transit: 'out_for_delivery',
  out_for_delivery: 'delivered'
}

const nextStatusLabels = {
  confirmed: 'Подтвердить заказ',
  pickup_scheduled: 'Назначить забор',
  picked_up: 'Груз забран',
  in_transit: 'В пути',
  out_for_delivery: 'Передать в доставку',
  delivered: 'Доставлен'
}

onMounted(() => {
  fetchOrders()
})

watch([search, filterStatus], () => {
  fetchOrders()
})

async function fetchOrders() {
  loading.value = true
  try {
    const params = {}
    if (search.value) params.search = search.value
    if (filterStatus.value) params.status = filterStatus.value

    const response = await api.get('/carrier/orders', { params })
    // API returns { orders: { data: [...], ... }, stats: {...} }
    orders.value = response.data.orders?.data || response.data.data || response.data.orders || []
  } catch (error) {
    console.error('Failed to fetch orders:', error)
    orders.value = []
  } finally {
    loading.value = false
  }
}

async function viewOrder(order) {
  try {
    const response = await api.get(`/carrier/orders/${order.id}`)
    selectedOrder.value = response.data.order || response.data
    showDetailsModal.value = true
  } catch (error) {
    console.error('Failed to load order details:', error)
    selectedOrder.value = order
    showDetailsModal.value = true
  }
}

async function updateToNextStatus(order) {
  const nextStatus = nextStatusMap[order.status]
  if (!nextStatus) return

  updatingStatus.value = true
  try {
    await api.put(`/carrier/orders/${order.id}/status`, { status: nextStatus })
    await fetchOrders()
    if (selectedOrder.value?.id === order.id) {
      selectedOrder.value.status = nextStatus
    }
  } catch (error) {
    console.error('Failed to update status:', error)
    alert('Ошибка при обновлении статуса')
  } finally {
    updatingStatus.value = false
  }
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU')
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('ru-RU')
}

function formatPrice(amount, currency = 'USD') {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: currency
  }).format(amount || 0)
}

const stats = computed(() => {
  const all = orders.value
  return {
    total: all.length,
    pending: all.filter(o => o.status === 'pending').length,
    inProgress: all.filter(o => ['confirmed', 'pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery'].includes(o.status)).length,
    delivered: all.filter(o => o.status === 'delivered').length
  }
})
</script>

<template>
  <div class="carrier-orders-page">
    <AppHeader />

    <div class="page-container">
      <div class="page-header">
        <div>
          <h1>Мои заказы</h1>
          <p class="subtitle">Управление заказами на перевозку</p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon total">
            <Package :size="20" :stroke-width="iconStrokeWidth" />
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ stats.total }}</span>
            <span class="stat-label">Всего заказов</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon pending">
            <Clock :size="20" :stroke-width="iconStrokeWidth" />
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ stats.pending }}</span>
            <span class="stat-label">Ожидают подтверждения</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon in-progress">
            <Truck :size="20" :stroke-width="iconStrokeWidth" />
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ stats.inProgress }}</span>
            <span class="stat-label">В работе</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon delivered">
            <CheckCircle :size="20" :stroke-width="iconStrokeWidth" />
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ stats.delivered }}</span>
            <span class="stat-label">Доставлено</span>
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
            placeholder="Поиск по номеру заказа..."
          />
        </div>
        <div class="filter-group">
          <select v-model="filterStatus">
            <option value="">Все статусы</option>
            <option v-for="(label, key) in statusLabels" :key="key" :value="key">
              {{ label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <span>Загрузка заказов...</span>
      </div>

      <!-- Orders List -->
      <div v-else-if="orders.length > 0" class="orders-list">
        <div v-for="order in orders" :key="order.id" class="order-card">
          <div class="order-header">
            <div class="order-number">{{ order.order_number }}</div>
            <span class="status-badge" :class="statusColors[order.status]">
              {{ statusLabels[order.status] }}
            </span>
          </div>

          <div class="order-route">
            <div class="route-point">
              <MapPin :size="14" :stroke-width="iconStrokeWidth" />
              <span>{{ order.quote?.shipment?.origin_city || '-' }}, {{ order.quote?.shipment?.origin_country || '' }}</span>
            </div>
            <ChevronRight :size="16" :stroke-width="iconStrokeWidth" class="route-arrow" />
            <div class="route-point">
              <MapPin :size="14" :stroke-width="iconStrokeWidth" />
              <span>{{ order.quote?.shipment?.destination_city || '-' }}, {{ order.quote?.shipment?.destination_country || '' }}</span>
            </div>
          </div>

          <div class="order-details">
            <div class="detail-item">
              <span class="detail-label">Заказчик</span>
              <span class="detail-value">{{ order.user?.name || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Вес</span>
              <span class="detail-value">{{ order.quote?.shipment?.total_weight || '-' }} кг</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Сумма</span>
              <span class="detail-value price">{{ formatPrice(order.total_amount, order.currency) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Дата</span>
              <span class="detail-value">{{ formatDate(order.created_at) }}</span>
            </div>
          </div>

          <div class="order-actions">
            <button @click="viewOrder(order)" class="btn btn-outline btn-sm">
              <Eye :size="16" :stroke-width="iconStrokeWidth" />
              Подробнее
            </button>
            <button
              v-if="nextStatusMap[order.status]"
              @click="updateToNextStatus(order)"
              class="btn btn-primary btn-sm"
              :disabled="updatingStatus"
            >
              <CheckCircle :size="16" :stroke-width="iconStrokeWidth" />
              {{ nextStatusLabels[nextStatusMap[order.status]] }}
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <Package :size="48" :stroke-width="1" />
        <h3>Нет заказов</h3>
        <p>Заказы появятся когда клиенты выберут вашу компанию</p>
      </div>
    </div>

    <!-- Order Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="showDetailsModal = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>Заказ {{ selectedOrder?.order_number }}</h2>
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

          <!-- Quick Action -->
          <div v-if="nextStatusMap[selectedOrder.status]" class="quick-action">
            <button
              @click="updateToNextStatus(selectedOrder)"
              class="btn btn-primary btn-block"
              :disabled="updatingStatus"
            >
              <CheckCircle :size="18" :stroke-width="iconStrokeWidth" />
              {{ nextStatusLabels[nextStatusMap[selectedOrder.status]] }}
            </button>
          </div>

          <!-- Route Info -->
          <div class="detail-section">
            <h3>Маршрут</h3>
            <div class="route-details">
              <div class="route-point-detail">
                <div class="route-label">Откуда</div>
                <div class="route-value">
                  {{ selectedOrder.quote?.shipment?.origin_city }}, {{ selectedOrder.quote?.shipment?.origin_country }}
                </div>
                <div class="route-address">{{ selectedOrder.quote?.shipment?.origin_address }}</div>
              </div>
              <div class="route-arrow-lg">→</div>
              <div class="route-point-detail">
                <div class="route-label">Куда</div>
                <div class="route-value">
                  {{ selectedOrder.quote?.shipment?.destination_city }}, {{ selectedOrder.quote?.shipment?.destination_country }}
                </div>
                <div class="route-address">{{ selectedOrder.quote?.shipment?.destination_address }}</div>
              </div>
            </div>
          </div>

          <!-- Cargo Info -->
          <div class="detail-section">
            <h3>Груз</h3>
            <div class="cargo-grid">
              <div class="cargo-item">
                <span class="cargo-label">Вес</span>
                <span class="cargo-value">{{ selectedOrder.quote?.shipment?.total_weight }} кг</span>
              </div>
              <div class="cargo-item">
                <span class="cargo-label">Объем</span>
                <span class="cargo-value">{{ selectedOrder.quote?.shipment?.total_volume }} м³</span>
              </div>
              <div class="cargo-item">
                <span class="cargo-label">Тип груза</span>
                <span class="cargo-value">{{ selectedOrder.quote?.shipment?.cargo_type || '-' }}</span>
              </div>
            </div>
          </div>

          <!-- Customer Info -->
          <div class="detail-section">
            <h3>Заказчик</h3>
            <div class="customer-info">
              <div class="customer-name">{{ selectedOrder.user?.name }}</div>
              <div class="customer-contacts">
                <div class="contact-item">
                  <Mail :size="14" :stroke-width="iconStrokeWidth" />
                  {{ selectedOrder.user?.email }}
                </div>
                <div v-if="selectedOrder.contact_phone" class="contact-item">
                  <Phone :size="14" :stroke-width="iconStrokeWidth" />
                  {{ selectedOrder.contact_phone }}
                </div>
              </div>
            </div>
          </div>

          <!-- Delivery Contacts -->
          <div class="detail-section">
            <h3>Контакты для доставки</h3>
            <div class="contacts-grid">
              <div>
                <div class="contact-label">Контактное лицо</div>
                <div class="contact-value">{{ selectedOrder.delivery_contact_name || selectedOrder.contact_name }}</div>
                <div class="contact-detail">{{ selectedOrder.delivery_contact_phone || selectedOrder.contact_phone }}</div>
              </div>
              <div>
                <div class="contact-label">Адрес доставки</div>
                <div class="contact-value">{{ selectedOrder.delivery_address }}</div>
              </div>
            </div>
          </div>

          <!-- Financial -->
          <div class="detail-section financial-section">
            <h3>Оплата</h3>
            <div class="financial-info">
              <div class="total-amount">
                {{ formatPrice(selectedOrder.total_amount, selectedOrder.currency) }}
              </div>
              <div class="commission-note">
                Комиссия платформы: {{ formatPrice(selectedOrder.commission_amount, selectedOrder.currency) }}
              </div>
            </div>
            <div class="tracking-info">
              <div class="tracking-label">Трекинг номер</div>
              <code class="tracking-code">{{ selectedOrder.tracking_number }}</code>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.carrier-orders-page {
  min-height: 100vh;
  background: $bg-light;
}

.page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: $spacing-lg;
}

.page-header {
  margin-bottom: $spacing-lg;

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
}

.search-box {
  flex: 1;
  max-width: 300px;
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

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

.filter-group select {
  padding: 10px 12px;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  background: $bg-white;
  min-width: 180px;

  &:focus {
    outline: none;
    border-color: $color-primary;
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

// Orders List
.orders-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.order-card {
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-xl;
  padding: $spacing-lg;
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-md;
}

.order-number {
  font-size: $font-size-lg;
  font-weight: 600;
  color: $color-primary;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
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

.order-route {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-sm 0;
  margin-bottom: $spacing-md;
  border-bottom: 1px solid $border-color;
}

.route-point {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: $font-size-sm;
  color: $text-secondary;

  svg {
    color: $text-muted;
  }
}

.route-arrow {
  color: $text-muted;
}

.order-details {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $spacing-md;
  margin-bottom: $spacing-md;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-label {
  font-size: $font-size-xs;
  color: $text-muted;
}

.detail-value {
  font-size: $font-size-sm;
  color: $text-primary;

  &.price {
    font-weight: 600;
    color: $color-success;
  }
}

.order-actions {
  display: flex;
  gap: $spacing-sm;
  justify-content: flex-end;
}

// Empty State
.empty-state {
  text-align: center;
  padding: $spacing-3xl;
  color: $text-muted;

  svg {
    margin-bottom: $spacing-md;
  }

  h3 {
    font-size: $font-size-lg;
    color: $text-primary;
    margin: 0 0 $spacing-xs;
  }

  p {
    margin: 0;
    font-size: $font-size-sm;
  }
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
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;

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

.order-status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-md;
}

.order-date {
  font-size: $font-size-sm;
  color: $text-secondary;
}

.quick-action {
  margin-bottom: $spacing-lg;
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

.route-point-detail {
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

.route-arrow-lg {
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

.customer-info {
  .customer-name {
    font-weight: 500;
    color: $text-primary;
    margin-bottom: $spacing-xs;
  }
}

.customer-contacts {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: $font-size-sm;
  color: $text-secondary;

  svg {
    color: $text-muted;
  }
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
  background: rgba($color-success, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.total-amount {
  font-size: $font-size-2xl;
  font-weight: 700;
  color: $color-success;
}

.commission-note {
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
  font-family: monospace;
  font-size: $font-size-sm;
  color: $text-primary;
}

// Buttons
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-xs;
  padding: 8px 16px;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-base;
  border: none;

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
  font-size: $font-size-xs;
}

.btn-block {
  width: 100%;
  padding: 12px;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .order-details {
    grid-template-columns: repeat(2, 1fr);
  }

  .order-actions {
    flex-direction: column;
  }
}
</style>
