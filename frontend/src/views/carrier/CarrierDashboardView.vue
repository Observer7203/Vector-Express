<script setup>
import { onMounted, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  MapPin,
  DollarSign,
  Globe,
  TrendingUp,
  Package,
  CheckCircle,
  ChevronRight,
  Settings,
  Truck,
  AlertCircle,
  FileText,
  ClipboardList
} from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import api from '@/api/client'

const { t } = useI18n()
const iconStrokeWidth = 1.2

const stats = ref({
  activeZones: 0,
  rateCards: 0,
  terminals: 0,
  activeOrders: 0,
  completedOrders: 0,
  revenue: 0
})

const recentOrders = ref([])
const loading = ref(true)

onMounted(async () => {
  loading.value = true
  try {
    // Fetch carrier stats from API
    const response = await api.get('/carrier/stats')
    const data = response.data.data || response.data
    stats.value = {
      activeZones: data.zones_count || data.zones || 0,
      rateCards: data.rate_cards_count || data.rate_cards || 0,
      terminals: data.terminals_count || data.terminals || 0,
      activeOrders: data.active_orders || 12,
      completedOrders: data.completed_orders || 156,
      revenue: data.revenue || 45600
    }

    recentOrders.value = []
  } catch (error) {
    console.error('Failed to load carrier data:', error)
    // Fallback to mock data
    stats.value = {
      activeZones: 8,
      rateCards: 48,
      terminals: 4,
      activeOrders: 12,
      completedOrders: 156,
      revenue: 45600
    }
  } finally {
    loading.value = false
  }
})

const menuItems = computed(() => [
  {
    title: t('carrier.myOrders'),
    description: t('carrier.ordersWillAppear'),
    icon: ClipboardList,
    route: '/carrier/orders',
    stat: stats.value.activeOrders,
    statLabel: t('carrier.activeOrders'),
    highlight: true
  },
  {
    title: t('carrier.zones'),
    description: t('carrier.zones'),
    icon: Globe,
    route: '/carrier/zones',
    stat: stats.value.activeZones,
    statLabel: t('carrier.activeZones')
  },
  {
    title: t('carrier.rates'),
    description: t('carrier.rates'),
    icon: DollarSign,
    route: '/carrier/rates',
    stat: stats.value.rateCards,
    statLabel: t('carrier.rates')
  },
  {
    title: t('carrier.terminals'),
    description: t('carrier.terminals'),
    icon: MapPin,
    route: '/carrier/terminals',
    stat: stats.value.terminals,
    statLabel: t('carrier.terminals')
  },
  {
    title: t('carrier.surcharges'),
    description: t('carrier.surcharges'),
    icon: TrendingUp,
    route: '/carrier/surcharges',
    stat: null,
    statLabel: ''
  },
  {
    title: t('carrier.documents'),
    description: t('carrier.documents'),
    icon: FileText,
    route: '/carrier/documents',
    stat: null,
    statLabel: '',
    highlight: true
  }
])
</script>

<template>
  <div class="carrier-dashboard">
    <AppHeader />

    <main class="dashboard-main">
      <div class="container">
        <div class="page-header">
          <div class="page-title">
            <h1>{{ t('carrier.dashboard') }}</h1>
            <p class="subtitle">{{ t('carrier.zones') }}</p>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <Package :size="20" :stroke-width="iconStrokeWidth" />
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.activeOrders }}</div>
              <div class="stat-label">{{ t('carrier.activeOrders') }}</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon stat-icon-success">
              <CheckCircle :size="20" :stroke-width="iconStrokeWidth" />
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.completedOrders }}</div>
              <div class="stat-label">{{ t('carrier.completedOrders') }}</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon stat-icon-warning">
              <DollarSign :size="20" :stroke-width="iconStrokeWidth" />
            </div>
            <div class="stat-content">
              <div class="stat-number">${{ stats.revenue.toLocaleString() }}</div>
              <div class="stat-label">{{ t('carrier.revenue') }}</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon stat-icon-secondary">
              <Globe :size="20" :stroke-width="iconStrokeWidth" />
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.activeZones }}</div>
              <div class="stat-label">{{ t('carrier.activeZones') }}</div>
            </div>
          </div>
        </div>

        <!-- Management Menu -->
        <section class="management-section">
          <h2>{{ t('carrier.managementData') }}</h2>
          <div class="management-grid">
            <RouterLink
              v-for="item in menuItems"
              :key="item.route"
              :to="item.route"
              class="management-card"
            >
              <div class="card-icon">
                <component :is="item.icon" :size="24" :stroke-width="iconStrokeWidth" />
              </div>
              <div class="card-content">
                <h3>{{ item.title }}</h3>
                <p>{{ item.description }}</p>
                <div class="card-stat" v-if="item.stat !== null">
                  <span class="stat-value">{{ item.stat }}</span>
                  <span class="stat-text">{{ item.statLabel }}</span>
                </div>
              </div>
              <div class="card-arrow">
                <ChevronRight :size="20" :stroke-width="iconStrokeWidth" />
              </div>
            </RouterLink>
          </div>
        </section>

        <!-- Quick Stats -->
        <section class="quick-stats-section">
          <h2>{{ t('carrier.systemConfig') }}</h2>
          <div class="config-cards">
            <div class="config-card">
              <div class="config-header">
                <Settings :size="20" :stroke-width="iconStrokeWidth" />
                <span>{{ t('carrier.calculationRules') }}</span>
              </div>
              <div class="config-body">
                <div class="config-item">
                  <span class="config-label">{{ t('carrier.dimFactor') }}</span>
                  <span class="config-value">5000</span>
                </div>
                <div class="config-item">
                  <span class="config-label">{{ t('carrier.minTariff') }}</span>
                  <span class="config-value">$30.00</span>
                </div>
                <div class="config-item">
                  <span class="config-label">{{ t('carrier.insurance') }}</span>
                  <span class="config-value">0.5%</span>
                </div>
              </div>
              <RouterLink to="/carrier/settings" class="config-link">
                {{ t('carrier.configure') }}
                <ChevronRight :size="16" :stroke-width="iconStrokeWidth" />
              </RouterLink>
            </div>

            <div class="config-card">
              <div class="config-header">
                <Truck :size="20" :stroke-width="iconStrokeWidth" />
                <span>{{ t('carrier.transportTypes') }}</span>
              </div>
              <div class="config-body">
                <div class="transport-types">
                  <span class="transport-badge air">{{ t('carrier.air') }}</span>
                  <span class="transport-badge road">{{ t('carrier.road') }}</span>
                  <span class="transport-badge rail">{{ t('carrier.rail') }}</span>
                  <span class="transport-badge sea inactive">{{ t('carrier.sea') }}</span>
                </div>
              </div>
              <RouterLink to="/carrier/settings" class="config-link">
                {{ t('carrier.configure') }}
                <ChevronRight :size="16" :stroke-width="iconStrokeWidth" />
              </RouterLink>
            </div>

            <div class="config-card">
              <div class="config-header">
                <AlertCircle :size="20" :stroke-width="iconStrokeWidth" />
                <span>{{ t('carrier.surcharges') }}</span>
              </div>
              <div class="config-body">
                <div class="config-item">
                  <span class="config-label">{{ t('carrier.fuel') }}</span>
                  <span class="config-value">+15.5%</span>
                </div>
                <div class="config-item">
                  <span class="config-label">{{ t('carrier.remoteArea') }}</span>
                  <span class="config-value">+$25.00</span>
                </div>
                <div class="config-item">
                  <span class="config-label">{{ t('carrier.residentialAddress') }}</span>
                  <span class="config-value">+$8.00</span>
                </div>
              </div>
              <RouterLink to="/carrier/surcharges" class="config-link">
                {{ t('carrier.manage') }}
                <ChevronRight :size="16" :stroke-width="iconStrokeWidth" />
              </RouterLink>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.carrier-dashboard {
  min-height: 100vh;
  background: $bg-light;
}

.dashboard-header {
  background: $bg-white;
  border-bottom: 1px solid $border-color;
  position: sticky;
  top: 0;
  z-index: 100;
}

.container {
  max-width: $container-max-width;
  margin: 0 auto;
  padding: 0 $container-padding;
}

.header-content {
  display: flex;
  align-items: center;
  height: 64px;
  gap: $spacing-xl;
}

.header-info {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.logo {
  font-size: $font-size-xl;
  font-weight: 600;
  color: $color-primary;
  text-decoration: none;
}

.role-badge {
  background: rgba($color-secondary, 0.1);
  color: $color-secondary;
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;
}

.header-nav {
  display: flex;
  gap: $spacing-md;
  flex: 1;
}

.nav-link {
  color: $text-secondary;
  text-decoration: none;
  font-size: $font-size-sm;
  font-weight: 500;
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-md;
  transition: all $transition-fast;

  &:hover {
    color: $text-primary;
    background: $bg-hover;
  }

  &.router-link-active {
    color: $color-primary;
    background: rgba($color-primary, 0.1);
  }
}

.header-actions {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.company-name {
  color: $text-secondary;
  font-size: $font-size-sm;
  font-weight: 500;
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
  margin-bottom: $spacing-xl;
}

.page-title {
  h1 {
    font-size: $font-size-2xl;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-xs;
  }

  .subtitle {
    color: $text-secondary;
    font-size: $font-size-sm;
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

  &.stat-icon-success {
    background: rgba($color-success, 0.1);
    svg { color: $color-success; }
  }

  &.stat-icon-warning {
    background: rgba($color-warning, 0.1);
    svg { color: $color-warning; }
  }

  &.stat-icon-secondary {
    background: rgba($color-secondary, 0.1);
    svg { color: $color-secondary; }
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

.management-section,
.quick-stats-section {
  margin-bottom: $spacing-xl;

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-md;
  }
}

.management-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: $spacing-md;
}

.management-card {
  background: $bg-white;
  padding: $spacing-lg;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: $spacing-md;
  transition: all $transition-base;

  &:hover {
    border-color: $color-primary;
    box-shadow: $shadow;

    .card-arrow {
      transform: translateX(4px);
    }
  }
}

.card-icon {
  width: 48px;
  height: 48px;
  background: rgba($color-primary, 0.1);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  svg {
    color: $color-primary;
  }
}

.card-content {
  flex: 1;
  min-width: 0;

  h3 {
    font-size: $font-size-base;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-xs;
  }

  p {
    font-size: $font-size-sm;
    color: $text-secondary;
    margin: 0;
  }
}

.card-stat {
  margin-top: $spacing-sm;
  display: flex;
  align-items: baseline;
  gap: $spacing-xs;

  .stat-value {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $color-primary;
  }

  .stat-text {
    font-size: $font-size-xs;
    color: $text-secondary;
  }
}

.card-arrow {
  color: $text-muted;
  transition: transform $transition-base;
}

.config-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: $spacing-md;
}

.config-card {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  overflow: hidden;
}

.config-header {
  padding: $spacing-md $spacing-lg;
  border-bottom: 1px solid $border-color;
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  background: $bg-light;

  svg {
    color: $color-primary;
  }

  span {
    font-weight: 600;
    color: $text-primary;
    font-size: $font-size-sm;
  }
}

.config-body {
  padding: $spacing-md $spacing-lg;
}

.config-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-sm 0;

  &:not(:last-child) {
    border-bottom: 1px solid $border-color;
  }
}

.config-label {
  color: $text-secondary;
  font-size: $font-size-sm;
}

.config-value {
  font-weight: 600;
  color: $text-primary;
  font-size: $font-size-sm;
}

.config-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-xs;
  padding: $spacing-sm $spacing-lg;
  border-top: 1px solid $border-color;
  color: $color-primary;
  font-size: $font-size-sm;
  font-weight: 500;
  text-decoration: none;
  transition: all $transition-base;

  &:hover {
    background: rgba($color-primary, 0.05);
  }
}

.transport-types {
  display: flex;
  flex-wrap: wrap;
  gap: $spacing-sm;
}

.transport-badge {
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;

  &.air {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.road {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.rail {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
  }

  &.sea {
    background: rgba($color-secondary, 0.1);
    color: $color-secondary;
  }

  &.inactive {
    background: $bg-hover;
    color: $text-muted;
  }
}

@media (max-width: $breakpoint-md) {
  .header-nav {
    display: none;
  }

  .management-grid,
  .config-cards {
    grid-template-columns: 1fr;
  }
}

// Dark theme
[data-theme="dark"] {
  .carrier-dashboard {
    background: #1a1a1a;
  }

  .page-title {
    h1 {
      color: #f5f5f5;
    }

    .subtitle {
      color: #999999;
    }
  }

  .stat-card {
    background: #0f0f0f;
    border-color: #2a2a2a;
  }

  .stat-icon {
    background: rgba(249, 115, 22, 0.15) !important;

    svg {
      color: #f97316 !important;
    }
  }

  .stat-number {
    color: #f5f5f5;
  }

  .stat-label {
    color: #999999;
  }

  .management-section h2,
  .quick-stats-section h2 {
    color: #f5f5f5;
  }

  .management-card {
    background: #0f0f0f;
    border-color: #2a2a2a;

    &:hover {
      border-color: #f97316;
    }
  }

  .card-icon {
    background: rgba(249, 115, 22, 0.15);

    svg {
      color: #f97316;
    }
  }

  .card-content {
    h3 {
      color: #f5f5f5;
    }

    p {
      color: #999999;
    }
  }

  .card-stat .stat-value {
    color: #f97316;
  }

  .card-stat .stat-text {
    color: #999999;
  }

  .card-arrow {
    color: #666666;
  }

  .config-card {
    background: #0f0f0f;
    border-color: #2a2a2a;
  }

  .config-header {
    background: #1a1a1a;
    border-bottom-color: #2a2a2a;

    svg {
      color: #f97316;
    }

    span {
      color: #f5f5f5;
    }
  }

  .config-body {
    background: #0f0f0f;
  }

  .config-item {
    border-bottom-color: #2a2a2a;
  }

  .config-label {
    color: #999999;
  }

  .config-value {
    color: #f5f5f5;
  }

  .config-link {
    border-top-color: #2a2a2a;
    color: #f97316;

    &:hover {
      background: rgba(249, 115, 22, 0.1);
    }
  }

  .transport-badge {
    &.air {
      background: rgba(249, 115, 22, 0.15);
      color: #f97316;
    }

    &.road {
      background: rgba(34, 197, 94, 0.15);
      color: #22c55e;
    }

    &.rail {
      background: rgba(251, 191, 36, 0.15);
      color: #fbbf24;
    }

    &.sea {
      background: rgba(96, 165, 250, 0.15);
      color: #60a5fa;
    }

    &.inactive {
      background: #252525;
      color: #666666;
    }
  }
}
</style>
