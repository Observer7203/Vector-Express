<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useOrdersStore } from '@/stores/orders'
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const route = useRoute()
const ordersStore = useOrdersStore()
const { t, locale } = useI18n()

const order = ref(null)

onMounted(async () => {
  await ordersStore.fetchOrder(route.params.id)
  order.value = ordersStore.currentOrder
})

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString(locale.value)
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString(locale.value)
}

const statusLabels = computed(() => ({
  pending: t('orderDetail.statuses.pending'),
  confirmed: t('orderDetail.statuses.confirmed'),
  pickup_scheduled: t('orderDetail.statuses.pickupScheduled'),
  picked_up: t('orderDetail.statuses.pickedUp'),
  in_transit: t('orderDetail.statuses.inTransit'),
  customs: t('orderDetail.statuses.customs'),
  out_for_delivery: t('orderDetail.statuses.outForDelivery'),
  delivered: t('orderDetail.statuses.delivered'),
  cancelled: t('orderDetail.statuses.cancelled')
}))

const statusColors = {
  pending: 'warning',
  confirmed: 'info',
  pickup_scheduled: 'info',
  picked_up: 'primary',
  in_transit: 'primary',
  customs: 'warning',
  out_for_delivery: 'primary',
  delivered: 'success',
  cancelled: 'danger'
}

const statusSteps = [
  'pending',
  'confirmed',
  'pickup_scheduled',
  'picked_up',
  'in_transit',
  'customs',
  'out_for_delivery',
  'delivered'
]

function getStatusIndex(status) {
  return statusSteps.indexOf(status)
}

async function cancelOrder() {
  if (confirm(t('orderDetail.confirmCancel'))) {
    await ordersStore.cancelOrder(order.value.id)
    order.value = ordersStore.currentOrder
  }
}
</script>

<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <div class="header-top">
          <RouterLink to="/orders" class="back-link">← {{ t('orderDetail.backToOrders') }}</RouterLink>
          <div class="header-controls">
            <ThemeToggle />
            <LanguageSwitcher />
          </div>
        </div>
        <div class="header-content">
          <div>
            <h1>{{ t('orderDetail.order') }} {{ order?.order_number }}</h1>
            <p v-if="order?.shipment" class="route">
              {{ order.shipment.origin_city }} → {{ order.shipment.destination_city }}
            </p>
          </div>
          <span v-if="order" :class="['status', `status-${statusColors[order.status]}`]">
            {{ statusLabels[order.status] }}
          </span>
        </div>
      </div>
    </header>

    <main class="container">
      <div v-if="ordersStore.loading" class="loading">{{ t('common.loading') }}</div>

      <div v-else-if="order" class="order-details">
        <!-- Progress -->
        <div class="progress-card" v-if="order.status !== 'cancelled'">
          <h3>{{ t('orderDetail.deliveryStatus') }}</h3>
          <div class="progress-bar">
            <div
              v-for="(step, index) in statusSteps"
              :key="step"
              :class="['progress-step', {
                active: getStatusIndex(order.status) >= index,
                current: order.status === step
              }]"
            >
              <div class="step-dot"></div>
              <span class="step-label">{{ statusLabels[step] }}</span>
            </div>
          </div>
        </div>

        <div class="details-grid">
          <!-- Order Info -->
          <div class="detail-card">
            <h3>{{ t('orderDetail.orderInfo') }}</h3>
            <div class="info-list">
              <div class="info-row">
                <span class="label">{{ t('orderDetail.orderNumber') }}</span>
                <span class="value">{{ order.order_number }}</span>
              </div>
              <div class="info-row">
                <span class="label">{{ t('orderDetail.createdAt') }}</span>
                <span class="value">{{ formatDateTime(order.created_at) }}</span>
              </div>
              <div class="info-row">
                <span class="label">{{ t('orderDetail.carrier') }}</span>
                <span class="value">{{ order.carrier?.name || '-' }}</span>
              </div>
              <div class="info-row" v-if="order.tracking_number">
                <span class="label">{{ t('orderDetail.trackingNumber') }}</span>
                <span class="value track">{{ order.tracking_number }}</span>
              </div>
              <div class="info-row" v-if="order.carrier_tracking_number">
                <span class="label">{{ t('orderDetail.carrierTrackingNumber') }}</span>
                <span class="value track">{{ order.carrier_tracking_number }}</span>
              </div>
            </div>
          </div>

          <!-- Financial -->
          <div class="detail-card">
            <h3>{{ t('orderDetail.cost') }}</h3>
            <div class="price-block">
              <span class="price-value">{{ order.total_amount?.toLocaleString() }}</span>
              <span class="price-currency">{{ order.currency }}</span>
            </div>
            <div class="info-list">
              <div class="info-row" v-if="order.commission_amount">
                <span class="label">{{ t('orderDetail.serviceCommission') }}</span>
                <span class="value">{{ order.commission_amount }} {{ order.currency }}</span>
              </div>
            </div>
          </div>

          <!-- Pickup -->
          <div class="detail-card">
            <h3>{{ t('orderDetail.pickup') }}</h3>
            <div class="info-list">
              <div class="info-row">
                <span class="label">{{ t('orderDetail.contactPerson') }}</span>
                <span class="value">{{ order.pickup_contact_name || order.contact_name || '-' }}</span>
              </div>
              <div class="info-row">
                <span class="label">{{ t('orderDetail.phone') }}</span>
                <span class="value">{{ order.pickup_contact_phone || order.contact_phone || '-' }}</span>
              </div>
              <div class="info-row">
                <span class="label">{{ t('orderDetail.address') }}</span>
                <span class="value">{{ order.pickup_address || order.shipment?.origin_address || '-' }}</span>
              </div>
              <div class="info-row" v-if="order.pickup_date">
                <span class="label">{{ t('orderDetail.pickupDate') }}</span>
                <span class="value">{{ formatDate(order.pickup_date) }}</span>
              </div>
            </div>
          </div>

          <!-- Delivery -->
          <div class="detail-card">
            <h3>{{ t('orderDetail.delivery') }}</h3>
            <div class="info-list">
              <div class="info-row">
                <span class="label">{{ t('orderDetail.contactPerson') }}</span>
                <span class="value">{{ order.delivery_contact_name || '-' }}</span>
              </div>
              <div class="info-row">
                <span class="label">{{ t('orderDetail.phone') }}</span>
                <span class="value">{{ order.delivery_contact_phone || '-' }}</span>
              </div>
              <div class="info-row">
                <span class="label">{{ t('orderDetail.address') }}</span>
                <span class="value">{{ order.delivery_address || order.shipment?.destination_address || '-' }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="timeline-card" v-if="order.tracking_events?.length">
          <h3>{{ t('orderDetail.deliveryHistory') }}</h3>
          <div class="timeline">
            <div
              v-for="event in order.tracking_events"
              :key="event.id"
              class="timeline-item"
            >
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <span class="time">{{ formatDateTime(event.event_time) }}</span>
                <span class="status">{{ event.status }}</span>
                <span class="location" v-if="event.location_city">
                  {{ event.location_city }}, {{ event.location_country }}
                </span>
                <span class="description" v-if="event.description">{{ event.description }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="notes-card" v-if="order.notes">
          <h3>{{ t('orderDetail.notes') }}</h3>
          <p>{{ order.notes }}</p>
        </div>

        <!-- Actions -->
        <div class="actions">
          <RouterLink
            v-if="['in_transit', 'out_for_delivery'].includes(order.status)"
            :to="`/orders/${order.id}/tracking`"
            class="btn btn-primary"
          >
            {{ t('orderDetail.trackOnMap') }}
          </RouterLink>
          <button
            v-if="['pending', 'confirmed'].includes(order.status)"
            @click="cancelOrder"
            class="btn btn-danger"
          >
            {{ t('orderDetail.cancelOrder') }}
          </button>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #f5f7fa;
}

.page-header {
  background: white;
  padding: 30px 0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0 20px;
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.header-controls {
  display: flex;
  gap: 12px;
  align-items: center;
}

.back-link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.9rem;
  display: inline-block;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

h1 {
  color: #333;
  margin-bottom: 5px;
}

.route {
  color: #666;
}

.status {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 500;
}

.status-warning { background: #fff3cd; color: #856404; }
.status-info { background: #cce5ff; color: #004085; }
.status-primary { background: #667eea20; color: #667eea; }
.status-success { background: #d4edda; color: #155724; }
.status-danger { background: #f8d7da; color: #721c24; }

.loading {
  text-align: center;
  padding: 60px;
  color: #666;
}

.order-details {
  padding: 30px 0;
}

.progress-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.progress-card h3 {
  margin-bottom: 20px;
  color: #333;
}

.progress-bar {
  display: flex;
  justify-content: space-between;
  position: relative;
}

.progress-bar::before {
  content: '';
  position: absolute;
  top: 10px;
  left: 0;
  right: 0;
  height: 2px;
  background: #e9ecef;
}

.progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 1;
}

.step-dot {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #e9ecef;
  margin-bottom: 8px;
}

.progress-step.active .step-dot {
  background: #667eea;
}

.progress-step.current .step-dot {
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.3);
}

.step-label {
  font-size: 0.7rem;
  color: #999;
  text-align: center;
  max-width: 80px;
}

.progress-step.active .step-label {
  color: #333;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.detail-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.detail-card h3 {
  margin-bottom: 20px;
  color: #333;
  font-size: 1.1rem;
}

.price-block {
  margin-bottom: 20px;
}

.price-value {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
}

.price-currency {
  font-size: 1rem;
  color: #666;
  margin-left: 8px;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: flex;
  justify-content: space-between;
}

.info-row .label {
  color: #666;
}

.info-row .value {
  font-weight: 500;
  color: #333;
}

.info-row .value.track {
  font-family: monospace;
  background: #f8f9fa;
  padding: 2px 8px;
  border-radius: 4px;
}

.timeline-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-top: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.timeline-card h3 {
  margin-bottom: 20px;
  color: #333;
}

.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e9ecef;
}

.timeline-item {
  position: relative;
  padding-bottom: 20px;
}

.timeline-dot {
  position: absolute;
  left: -26px;
  top: 0;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #667eea;
}

.timeline-content {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.timeline-content .time {
  font-size: 0.85rem;
  color: #666;
}

.timeline-content .status {
  font-weight: 600;
  color: #333;
}

.timeline-content .location {
  color: #667eea;
  font-size: 0.9rem;
}

.timeline-content .description {
  color: #666;
  font-size: 0.9rem;
}

.notes-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-top: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.notes-card h3 {
  margin-bottom: 10px;
  color: #333;
}

.notes-card p {
  color: #666;
}

.actions {
  margin-top: 30px;
  display: flex;
  gap: 15px;
}

.btn {
  padding: 14px 30px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s;
  border: none;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5a6fd6;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}

@media (max-width: 768px) {
  .details-grid {
    grid-template-columns: 1fr;
  }

  .progress-bar {
    flex-wrap: wrap;
    gap: 15px;
  }
}

</style>

<style>
/* Dark theme - global styles for OrderDetailView */
[data-theme="dark"] .page {
  background: #1a1a1a !important;
}

[data-theme="dark"] .page-header {
  background: #0f0f0f !important;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3) !important;
}

[data-theme="dark"] .page-header h1 {
  color: #f5f5f5 !important;
}

[data-theme="dark"] .page-header .route {
  color: #999999 !important;
}

[data-theme="dark"] .page-header .back-link {
  color: #f97316 !important;
}

[data-theme="dark"] .progress-card,
[data-theme="dark"] .detail-card,
[data-theme="dark"] .timeline-card,
[data-theme="dark"] .notes-card {
  background: #0f0f0f !important;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3) !important;
}

[data-theme="dark"] .progress-card h3,
[data-theme="dark"] .detail-card h3,
[data-theme="dark"] .timeline-card h3,
[data-theme="dark"] .notes-card h3 {
  color: #f5f5f5 !important;
}

[data-theme="dark"] .progress-bar::before {
  background: #2a2a2a !important;
}

[data-theme="dark"] .step-dot {
  background: #2a2a2a !important;
}

[data-theme="dark"] .progress-step.active .step-dot {
  background: #f97316 !important;
}

[data-theme="dark"] .progress-step.current .step-dot {
  box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.3) !important;
}

[data-theme="dark"] .step-label {
  color: #999999 !important;
}

[data-theme="dark"] .progress-step.active .step-label {
  color: #f5f5f5 !important;
}

[data-theme="dark"] .price-value {
  color: #f5f5f5 !important;
}

[data-theme="dark"] .price-currency {
  color: #999999 !important;
}

[data-theme="dark"] .info-row .label {
  color: #999999 !important;
}

[data-theme="dark"] .info-row .value {
  color: #f5f5f5 !important;
}

[data-theme="dark"] .info-row .value.track {
  background: #252525 !important;
  color: #f97316 !important;
}

[data-theme="dark"] .order-details .loading {
  color: #999999 !important;
}

[data-theme="dark"] .timeline::before {
  background: #2a2a2a !important;
}

[data-theme="dark"] .timeline-dot {
  background: #f97316 !important;
}

[data-theme="dark"] .timeline-content .time {
  color: #999999 !important;
}

[data-theme="dark"] .timeline-content .status {
  color: #f5f5f5 !important;
}

[data-theme="dark"] .timeline-content .location {
  color: #f97316 !important;
}

[data-theme="dark"] .timeline-content .description {
  color: #999999 !important;
}

[data-theme="dark"] .notes-card p {
  color: #999999 !important;
}

[data-theme="dark"] .status-warning {
  background: rgba(251, 191, 36, 0.15) !important;
  color: #fbbf24 !important;
}

[data-theme="dark"] .status-info {
  background: rgba(59, 130, 246, 0.15) !important;
  color: #3b82f6 !important;
}

[data-theme="dark"] .status-primary {
  background: rgba(249, 115, 22, 0.15) !important;
  color: #f97316 !important;
}

[data-theme="dark"] .status-success {
  background: rgba(34, 197, 94, 0.15) !important;
  color: #22c55e !important;
}

[data-theme="dark"] .status-danger {
  background: rgba(239, 68, 68, 0.15) !important;
  color: #ef4444 !important;
}

[data-theme="dark"] .order-details .btn-primary {
  background: #f97316 !important;
  color: #ffffff !important;
}

[data-theme="dark"] .order-details .btn-primary:hover {
  background: #ea580c !important;
}
</style>
