<script setup>
import { computed, onMounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  Users,
  Building2,
  Package,
  DollarSign,
  TrendingUp,
  Clock,
  AlertTriangle,
  CheckCircle,
  Truck
} from 'lucide-vue-next'

const adminStore = useAdminStore()
const iconStrokeWidth = 1.5

onMounted(() => {
  adminStore.fetchDashboard()
})

const data = computed(() => adminStore.dashboardData)
const loading = computed(() => adminStore.dashboardLoading)

function formatCurrency(value) {
  return Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2 })
}
</script>

<template>
  <div class="dashboard">
    <h1>Дашборд</h1>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <span>Загрузка данных...</span>
    </div>

    <template v-else-if="data">
      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon users">
            <Users :size="24" :stroke-width="iconStrokeWidth" />
          </div>
          <div class="stat-content">
            <span class="stat-value">{{ data.users.total }}</span>
            <span class="stat-label">Пользователей</span>
            <span class="stat-change positive">+{{ data.users.new_this_week }} за неделю</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon companies">
            <Building2 :size="24" :stroke-width="iconStrokeWidth" />
          </div>
          <div class="stat-content">
            <span class="stat-value">{{ data.companies.total }}</span>
            <span class="stat-label">Компаний</span>
            <span class="stat-meta">{{ data.companies.shippers }} заказчиков · {{ data.companies.carriers }} перевозчиков</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon orders">
            <Package :size="24" :stroke-width="iconStrokeWidth" />
          </div>
          <div class="stat-content">
            <span class="stat-value">{{ data.orders.total }}</span>
            <span class="stat-label">Заказов</span>
            <span class="stat-meta">{{ data.orders.this_month }} за месяц</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon revenue">
            <DollarSign :size="24" :stroke-width="iconStrokeWidth" />
          </div>
          <div class="stat-content">
            <span class="stat-value">${{ formatCurrency(data.financial.total_revenue) }}</span>
            <span class="stat-label">Выручка</span>
            <span class="stat-change positive">Комиссия: ${{ formatCurrency(data.financial.total_commission) }}</span>
          </div>
        </div>
      </div>

      <!-- Secondary Stats -->
      <div class="secondary-stats">
        <div class="stat-item warning" v-if="data.companies.pending_verification > 0">
          <Clock :size="20" :stroke-width="iconStrokeWidth" />
          <span><strong>{{ data.companies.pending_verification }}</strong> компаний ожидают верификации</span>
        </div>
        <div class="stat-item danger" v-if="data.financial.overdue_payments > 0">
          <AlertTriangle :size="20" :stroke-width="iconStrokeWidth" />
          <span><strong>${{ formatCurrency(data.financial.overdue_payments) }}</strong> просроченных платежей</span>
        </div>
        <div class="stat-item info" v-if="data.financial.pending_payments > 0">
          <DollarSign :size="20" :stroke-width="iconStrokeWidth" />
          <span><strong>${{ formatCurrency(data.financial.pending_payments) }}</strong> ожидают оплаты</span>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="charts-row">
        <!-- Orders Trend -->
        <div class="chart-card">
          <h3>Заказы за неделю</h3>
          <div class="simple-chart">
            <div class="chart-bars">
              <div
                v-for="item in data.orders.trend"
                :key="item.date"
                class="chart-bar-wrapper"
              >
                <div
                  class="chart-bar"
                  :style="{ height: `${Math.max(item.count * 20, 4)}px` }"
                ></div>
                <span class="chart-label">{{ item.date }}</span>
                <span class="chart-value">{{ item.count }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders by Status -->
        <div class="chart-card">
          <h3>Статусы заказов</h3>
          <div class="status-list">
            <div class="status-item">
              <span class="status-dot pending"></span>
              <span class="status-name">Ожидают</span>
              <span class="status-count">{{ data.orders.by_status?.pending || 0 }}</span>
            </div>
            <div class="status-item">
              <span class="status-dot confirmed"></span>
              <span class="status-name">Подтверждены</span>
              <span class="status-count">{{ data.orders.by_status?.confirmed || 0 }}</span>
            </div>
            <div class="status-item">
              <span class="status-dot in_transit"></span>
              <span class="status-name">В пути</span>
              <span class="status-count">{{ data.orders.by_status?.in_transit || 0 }}</span>
            </div>
            <div class="status-item">
              <span class="status-dot delivered"></span>
              <span class="status-name">Доставлены</span>
              <span class="status-count">{{ data.orders.by_status?.delivered || 0 }}</span>
            </div>
            <div class="status-item">
              <span class="status-dot cancelled"></span>
              <span class="status-name">Отменены</span>
              <span class="status-count">{{ data.orders.by_status?.cancelled || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Top Carriers -->
        <div class="chart-card">
          <h3>Топ перевозчиков</h3>
          <div class="top-list">
            <div
              v-for="(carrier, index) in data.top_carriers"
              :key="index"
              class="top-item"
            >
              <span class="top-rank">{{ index + 1 }}</span>
              <div class="top-icon">
                <Truck :size="18" :stroke-width="iconStrokeWidth" />
              </div>
              <span class="top-name">{{ carrier.name }}</span>
              <span class="top-value">{{ carrier.orders }} заказов</span>
            </div>
            <div v-if="!data.top_carriers?.length" class="empty-list">
              Нет данных
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.dashboard {
  h1 {
    font-size: $font-size-2xl;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-lg;
  }
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $spacing-lg;
  margin-bottom: $spacing-lg;
}

.stat-card {
  background: $bg-white;
  border-radius: $radius-xl;
  padding: $spacing-lg;
  display: flex;
  gap: $spacing-md;
  border: 1px solid $border-color;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: $radius-lg;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  &.users {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.companies {
    background: rgba($color-info, 0.1);
    color: $color-info;
  }

  &.orders {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
  }

  &.revenue {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: $font-size-2xl;
  font-weight: 700;
  color: $text-primary;
  line-height: 1.2;
}

.stat-label {
  font-size: $font-size-sm;
  color: $text-secondary;
  margin-bottom: $spacing-xs;
}

.stat-change {
  font-size: $font-size-xs;

  &.positive {
    color: $color-success;
  }

  &.negative {
    color: $color-danger;
  }
}

.stat-meta {
  font-size: $font-size-xs;
  color: $text-muted;
}

.secondary-stats {
  display: flex;
  gap: $spacing-md;
  margin-bottom: $spacing-lg;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-sm $spacing-md;
  border-radius: $radius-lg;
  font-size: $font-size-sm;

  &.warning {
    background: rgba($color-warning, 0.1);
    color: darken($color-warning, 10%);
  }

  &.danger {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }

  &.info {
    background: rgba($color-info, 0.1);
    color: $color-info;
  }
}

.charts-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: $spacing-lg;
}

.chart-card {
  background: $bg-white;
  border-radius: $radius-xl;
  padding: $spacing-lg;
  border: 1px solid $border-color;

  h3 {
    font-size: $font-size-base;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-md;
  }
}

.simple-chart {
  height: 150px;
  display: flex;
  align-items: flex-end;
}

.chart-bars {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  width: 100%;
  height: 100%;
  gap: $spacing-sm;
}

.chart-bar-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $spacing-xs;
}

.chart-bar {
  width: 100%;
  max-width: 40px;
  background: $color-primary;
  border-radius: $radius-sm $radius-sm 0 0;
  min-height: 4px;
}

.chart-label {
  font-size: $font-size-xs;
  color: $text-muted;
}

.chart-value {
  font-size: $font-size-xs;
  font-weight: 600;
  color: $text-primary;
}

.status-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
}

.status-item {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-xs 0;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;

  &.pending { background: $color-warning; }
  &.confirmed { background: $color-info; }
  &.in_transit { background: $color-primary; }
  &.delivered { background: $color-success; }
  &.cancelled { background: $color-danger; }
}

.status-name {
  flex: 1;
  font-size: $font-size-sm;
  color: $text-secondary;
}

.status-count {
  font-size: $font-size-sm;
  font-weight: 600;
  color: $text-primary;
}

.top-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
}

.top-item {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-xs 0;
}

.top-rank {
  width: 20px;
  height: 20px;
  background: $bg-light;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: $font-size-xs;
  font-weight: 600;
  color: $text-secondary;
}

.top-icon {
  width: 32px;
  height: 32px;
  background: rgba($color-primary, 0.1);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  color: $color-primary;
}

.top-name {
  flex: 1;
  font-size: $font-size-sm;
  color: $text-primary;
}

.top-value {
  font-size: $font-size-xs;
  color: $text-muted;
}

.empty-list {
  text-align: center;
  padding: $spacing-lg;
  color: $text-muted;
  font-size: $font-size-sm;
}

@media (max-width: $breakpoint-xl) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .charts-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: $breakpoint-md) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
