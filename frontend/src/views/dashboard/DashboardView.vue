<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useShipmentsStore } from '@/stores/shipments'
import { useOrdersStore } from '@/stores/orders'
import { FileText, Truck, Clock, CheckCircle, PlusSquare, MapPin } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'

const iconStrokeWidth = 1.2

const shipmentsStore = useShipmentsStore()
const ordersStore = useOrdersStore()

const stats = ref({
  shipments: 0,
  orders: 0,
  pending: 0,
  delivered: 0
})

onMounted(async () => {
  await Promise.all([shipmentsStore.fetchShipments(), ordersStore.fetchOrders()])

  stats.value.shipments = shipmentsStore.pagination.total
  stats.value.orders = ordersStore.pagination.total
  stats.value.pending = ordersStore.orders.filter((o) => o.status === 'pending').length
  stats.value.delivered = ordersStore.orders.filter((o) => o.status === 'delivered').length
})

const statusLabels = {
  pending: 'Ожидает',
  confirmed: 'Подтвержден',
  pickup_scheduled: 'Назначен забор',
  picked_up: 'Забран',
  in_transit: 'В пути',
  customs: 'На таможне',
  out_for_delivery: 'Доставляется',
  delivered: 'Доставлен',
  cancelled: 'Отменен'
}
</script>

<template>
  <div class="dashboard">
    <AppHeader />

    <main class="dashboard-main">
      <div class="container">
        <div class="page-header">
          <h1>Личный кабинет</h1>
          <RouterLink to="/shipments/new" class="btn btn-primary">
            <PlusSquare :size="18" :stroke-width="iconStrokeWidth" />
            Создать заявку
          </RouterLink>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <FileText :size="20" :stroke-width="iconStrokeWidth" />
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.shipments }}</div>
              <div class="stat-label">Заявки</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon stat-icon-orders">
              <Truck :size="20" :stroke-width="iconStrokeWidth" />
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.orders }}</div>
              <div class="stat-label">Заказы</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon stat-icon-pending">
              <Clock :size="20" :stroke-width="iconStrokeWidth" />
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.pending }}</div>
              <div class="stat-label">Ожидают</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon stat-icon-delivered">
              <CheckCircle :size="20" :stroke-width="iconStrokeWidth" />
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.delivered }}</div>
              <div class="stat-label">Доставлено</div>
            </div>
          </div>
        </div>

        <div class="quick-actions">
          <h2>Быстрые действия</h2>
          <div class="actions-grid">
            <RouterLink to="/shipments/new" class="action-card">
              <div class="action-icon">
                <PlusSquare :size="20" :stroke-width="iconStrokeWidth" />
              </div>
              <span class="action-label">Создать заявку</span>
              <span class="action-desc">Рассчитать стоимость доставки</span>
            </RouterLink>
            <RouterLink to="/shipments" class="action-card">
              <div class="action-icon">
                <FileText :size="20" :stroke-width="iconStrokeWidth" />
              </div>
              <span class="action-label">Мои заявки</span>
              <span class="action-desc">Просмотр и управление</span>
            </RouterLink>
            <RouterLink to="/orders" class="action-card">
              <div class="action-icon">
                <Truck :size="20" :stroke-width="iconStrokeWidth" />
              </div>
              <span class="action-label">Мои заказы</span>
              <span class="action-desc">История заказов</span>
            </RouterLink>
            <RouterLink to="/tracking" class="action-card">
              <div class="action-icon">
                <MapPin :size="20" :stroke-width="iconStrokeWidth" />
              </div>
              <span class="action-label">Отследить груз</span>
              <span class="action-desc">По номеру отслеживания</span>
            </RouterLink>
          </div>
        </div>

        <div class="recent-orders" v-if="ordersStore.orders.length">
          <div class="section-header">
            <h2>Последние заказы</h2>
            <RouterLink to="/orders" class="link-all">Все заказы</RouterLink>
          </div>
          <div class="orders-table">
            <table>
              <thead>
                <tr>
                  <th>Номер</th>
                  <th>Статус</th>
                  <th>Сумма</th>
                  <th>Дата</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in ordersStore.orders.slice(0, 5)" :key="order.id">
                  <td class="order-number">{{ order.order_number }}</td>
                  <td>
                    <span :class="['status', `status-${order.status}`]">
                      {{ statusLabels[order.status] || order.status }}
                    </span>
                  </td>
                  <td class="order-amount">{{ order.total_amount }} {{ order.currency }}</td>
                  <td class="order-date">{{ new Date(order.created_at).toLocaleDateString('ru-RU') }}</td>
                  <td>
                    <RouterLink :to="`/orders/${order.id}`" class="btn-view">Подробнее</RouterLink>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="empty-state" v-else>
          <div class="empty-icon">
            <Truck :size="32" :stroke-width="iconStrokeWidth" />
          </div>
          <h3>Пока нет заказов</h3>
          <p>Создайте заявку для расчета стоимости доставки</p>
          <RouterLink to="/shipments/new" class="btn btn-primary">Создать заявку</RouterLink>
        </div>
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.dashboard {
  min-height: 100vh;
  background: $bg-light;
}

.container {
  max-width: $container-max-width;
  margin: 0 auto;
  padding: 0 $container-padding;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: $spacing-sm $spacing-md;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-base;
  text-decoration: none;
  border: none;

  svg {
    width: 18px;
    height: 18px;
  }
}

.btn-primary {
  background: $color-primary;
  color: $text-white;

  &:hover {
    background: $color-primary-dark;
  }
}

.btn-outline {
  background: transparent;
  color: $color-primary;
  border: 1px solid $color-primary;

  &:hover {
    background: $color-primary;
    color: $text-white;
  }
}

.dashboard-main {
  padding: $spacing-xl 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-xl;

  h1 {
    font-size: $font-size-2xl;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
  }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: $spacing-md;
  margin-bottom: $spacing-xl;
}

.stat-card {
  background: $bg-white;
  padding: $spacing-lg;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.stat-icon {
  width: 48px;
  height: 48px;
  background: rgba($color-primary, 0.1);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;

  svg {
    width: 24px;
    height: 24px;
    color: $color-primary;
  }

  &.stat-icon-orders {
    background: rgba($color-secondary, 0.1);
    svg { color: $color-secondary; }
  }

  &.stat-icon-pending {
    background: rgba($color-warning, 0.1);
    svg { color: $color-warning; }
  }

  &.stat-icon-delivered {
    background: rgba($color-success, 0.1);
    svg { color: $color-success; }
  }
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: $font-size-2xl;
  font-weight: 600;
  color: $text-primary;
  line-height: 1;
}

.stat-label {
  color: $text-secondary;
  font-size: $font-size-sm;
  margin-top: $spacing-xs;
}

.quick-actions {
  margin-bottom: $spacing-xl;

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-md;
  }
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: $spacing-md;
}

.action-card {
  background: $bg-white;
  padding: $spacing-lg;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  text-decoration: none;
  transition: all $transition-base;
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;

  &:hover {
    border-color: $color-primary;
    box-shadow: $shadow;
  }
}

.action-icon {
  width: 40px;
  height: 40px;
  background: rgba($color-primary, 0.1);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: $spacing-xs;

  svg {
    width: 20px;
    height: 20px;
    color: $color-primary;
  }
}

.action-label {
  font-weight: 500;
  color: $text-primary;
}

.action-desc {
  font-size: $font-size-sm;
  color: $text-secondary;
}

.recent-orders {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  overflow: hidden;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md $spacing-lg;
  border-bottom: 1px solid $border-color;

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
  }
}

.link-all {
  color: $color-primary;
  text-decoration: none;
  font-size: $font-size-sm;
  font-weight: 500;

  &:hover {
    text-decoration: underline;
  }
}

.orders-table {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: $spacing-md $spacing-lg;
  text-align: left;
}

th {
  background: $bg-light;
  color: $text-secondary;
  font-size: $font-size-xs;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

td {
  border-top: 1px solid $border-color;
  font-size: $font-size-sm;
}

.order-number {
  font-weight: 500;
  color: $text-primary;
}

.order-amount {
  font-weight: 500;
}

.order-date {
  color: $text-secondary;
}

.btn-view {
  color: $color-primary;
  text-decoration: none;
  font-size: $font-size-sm;
  font-weight: 500;

  &:hover {
    text-decoration: underline;
  }
}

.status {
  display: inline-block;
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;
}

.status-pending {
  background: rgba($color-warning, 0.1);
  color: $color-warning;
}

.status-confirmed {
  background: rgba($color-primary, 0.1);
  color: $color-primary;
}

.status-pickup_scheduled,
.status-picked_up {
  background: rgba($color-secondary, 0.1);
  color: $color-secondary;
}

.status-in_transit,
.status-customs,
.status-out_for_delivery {
  background: rgba($color-primary, 0.1);
  color: $color-primary;
}

.status-delivered {
  background: rgba($color-success, 0.1);
  color: $color-success;
}

.status-cancelled {
  background: rgba($color-danger, 0.1);
  color: $color-danger;
}

.empty-state {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-2xl;
  text-align: center;
}

.empty-icon {
  width: 64px;
  height: 64px;
  background: $bg-light;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto $spacing-md;

  svg {
    width: 32px;
    height: 32px;
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
  margin: 0 0 $spacing-lg;
}

@media (max-width: $breakpoint-md) {
  .header-nav {
    display: none;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: $spacing-md;
  }
}
</style>
