<script setup>
import { onMounted } from 'vue'
import { useOrdersStore } from '@/stores/orders'

const ordersStore = useOrdersStore()

onMounted(() => {
  ordersStore.fetchOrders()
})

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('ru-RU')
}

const statusLabels = {
  pending: 'Ожидает подтверждения',
  confirmed: 'Подтвержден',
  pickup_scheduled: 'Назначен забор',
  picked_up: 'Забран',
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
  out_for_delivery: 'primary',
  delivered: 'success',
  cancelled: 'danger'
}
</script>

<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <div class="header-content">
          <h1>Мои заказы</h1>
          <RouterLink to="/shipments/new" class="btn btn-primary">
            + Новая заявка
          </RouterLink>
        </div>
      </div>
    </header>

    <main class="container">
      <div v-if="ordersStore.loading" class="loading">Загрузка...</div>

      <div v-else-if="ordersStore.orders.length === 0" class="empty-state">
        <p>У вас пока нет заказов</p>
        <RouterLink to="/shipments" class="btn btn-primary">
          Перейти к заявкам
        </RouterLink>
      </div>

      <div v-else class="orders-list">
        <div v-for="order in ordersStore.orders" :key="order.id" class="order-card">
          <div class="order-header">
            <div class="order-number">
              <span class="label">Заказ</span>
              <span class="value">{{ order.order_number }}</span>
            </div>
            <span :class="['status', `status-${statusColors[order.status]}`]">
              {{ statusLabels[order.status] }}
            </span>
          </div>

          <div class="order-route" v-if="order.shipment">
            <span class="city">{{ order.shipment.origin_city }}</span>
            <span class="arrow">→</span>
            <span class="city">{{ order.shipment.destination_city }}</span>
          </div>

          <div class="order-details">
            <div class="detail">
              <span class="label">Перевозчик</span>
              <span class="value">{{ order.carrier?.name || '-' }}</span>
            </div>
            <div class="detail">
              <span class="label">Сумма</span>
              <span class="value">{{ order.total_amount?.toLocaleString() }} {{ order.currency }}</span>
            </div>
            <div class="detail">
              <span class="label">Дата заказа</span>
              <span class="value">{{ formatDate(order.created_at) }}</span>
            </div>
            <div class="detail" v-if="order.tracking_number">
              <span class="label">Трек-номер</span>
              <span class="value track">{{ order.tracking_number }}</span>
            </div>
          </div>

          <div class="order-actions">
            <RouterLink :to="`/orders/${order.id}`" class="btn btn-outline">
              Подробнее
            </RouterLink>
            <RouterLink
              v-if="['in_transit', 'out_for_delivery'].includes(order.status)"
              :to="`/orders/${order.id}/tracking`"
              class="btn btn-primary"
            >
              Отследить
            </RouterLink>
          </div>
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

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

h1 {
  color: #333;
}

.loading,
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.empty-state .btn {
  margin-top: 20px;
}

.orders-list {
  padding: 30px 0;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.order-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.order-number .label {
  display: block;
  font-size: 0.8rem;
  color: #666;
}

.order-number .value {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
}

.status {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-warning {
  background: #fff3cd;
  color: #856404;
}

.status-info {
  background: #cce5ff;
  color: #004085;
}

.status-primary {
  background: #667eea20;
  color: #667eea;
}

.status-success {
  background: #d4edda;
  color: #155724;
}

.status-danger {
  background: #f8d7da;
  color: #721c24;
}

.order-route {
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 20px;
}

.arrow {
  color: #667eea;
  margin: 0 10px;
}

.order-details {
  display: flex;
  gap: 40px;
  flex-wrap: wrap;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #eee;
}

.detail .label {
  display: block;
  font-size: 0.8rem;
  color: #666;
}

.detail .value {
  font-weight: 500;
  color: #333;
}

.detail .value.track {
  font-family: monospace;
  background: #f8f9fa;
  padding: 2px 8px;
  border-radius: 4px;
}

.order-actions {
  display: flex;
  gap: 10px;
}

.btn {
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background: #667eea;
  color: white;
  border: none;
}

.btn-primary:hover {
  background: #5a6fd6;
}

.btn-outline {
  background: transparent;
  color: #667eea;
  border: 2px solid #667eea;
}

.btn-outline:hover {
  background: #667eea;
  color: white;
}
</style>
