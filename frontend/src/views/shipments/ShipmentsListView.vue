<script setup>
import { onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useShipmentsStore } from '@/stores/shipments'
import { ArrowLeft, Plus, ArrowRight, FileText, Truck, Scale, Calendar } from 'lucide-vue-next'
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const { t, locale } = useI18n()

const iconStrokeWidth = 1.2

const shipmentsStore = useShipmentsStore()

onMounted(() => {
  shipmentsStore.fetchShipments()
})

function formatDate(dateString) {
  const localeMap = { ru: 'ru-RU', en: 'en-US', kk: 'kk-KZ' }
  return new Date(dateString).toLocaleDateString(localeMap[locale.value] || 'ru-RU')
}

const statusLabels = computed(() => ({
  draft: t('shipmentsList.statuses.draft'),
  calculating: t('shipmentsList.statuses.calculating'),
  quoted: t('shipmentsList.statuses.quoted'),
  ordered: t('shipmentsList.statuses.ordered'),
  expired: t('shipmentsList.statuses.expired')
}))

const transportLabels = computed(() => ({
  air: t('shipmentsList.transport.air'),
  sea: t('shipmentsList.transport.sea'),
  rail: t('shipmentsList.transport.rail'),
  road: t('shipmentsList.transport.road'),
  multimodal: t('shipmentsList.transport.multimodal')
}))
</script>

<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <div class="header-content">
          <div class="header-left">
            <RouterLink to="/dashboard" class="back-link">
              <ArrowLeft :size="20" :stroke-width="iconStrokeWidth" />
            </RouterLink>
            <h1>{{ t('shipmentsList.title') }}</h1>
          </div>
          <div class="header-right">
            <ThemeToggle />
            <LanguageSwitcher />
            <RouterLink to="/shipments/new" class="btn btn-primary">
              <Plus :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('shipmentsList.newShipment') }}
            </RouterLink>
          </div>
        </div>
      </div>
    </header>

    <main class="page-content">
      <div class="container">
        <div v-if="shipmentsStore.loading" class="loading">
          <div class="spinner"></div>
          <span>{{ t('shipmentsList.loading') }}</span>
        </div>

        <div v-else-if="shipmentsStore.shipments.length === 0" class="empty-state">
          <div class="empty-icon">
            <FileText :size="32" :stroke-width="iconStrokeWidth" />
          </div>
          <h3>{{ t('shipmentsList.noShipments') }}</h3>
          <p>{{ t('shipmentsList.createFirst') }}</p>
          <RouterLink to="/shipments/new" class="btn btn-primary">{{ t('shipmentsList.createShipment') }}</RouterLink>
        </div>

        <div v-else class="shipments-list">
          <div
            v-for="shipment in shipmentsStore.shipments"
            :key="shipment.id"
            class="shipment-card"
          >
            <div class="shipment-header">
              <div class="shipment-route">
                <div class="route-point">
                  <span class="point-label">{{ t('shipmentsList.from') }}</span>
                  <span class="point-value">{{ shipment.origin_city }}, {{ shipment.origin_country }}</span>
                </div>
                <div class="route-arrow">
                  <ArrowRight :size="18" :stroke-width="iconStrokeWidth" />
                </div>
                <div class="route-point">
                  <span class="point-label">{{ t('shipmentsList.to') }}</span>
                  <span class="point-value">{{ shipment.destination_city }}, {{ shipment.destination_country }}</span>
                </div>
              </div>
              <span :class="['status', `status-${shipment.status}`]">
                {{ statusLabels[shipment.status] }}
              </span>
            </div>

            <div class="shipment-details">
              <div class="detail-item">
                <Truck :size="16" :stroke-width="iconStrokeWidth" />
                <span>{{ transportLabels[shipment.transport_type] || t('shipmentsList.anyType') }}</span>
              </div>
              <div class="detail-item">
                <Scale :size="16" :stroke-width="iconStrokeWidth" />
                <span>{{ shipment.total_weight || '-' }} {{ t('shipmentsList.kg') }}</span>
              </div>
              <div class="detail-item">
                <Calendar :size="16" :stroke-width="iconStrokeWidth" />
                <span>{{ formatDate(shipment.created_at) }}</span>
              </div>
            </div>

            <div class="shipment-footer">
              <div class="actions">
                <RouterLink
                  v-if="shipment.status === 'quoted'"
                  :to="`/shipments/${shipment.id}/quotes`"
                  class="btn btn-primary btn-sm"
                >
                  {{ t('shipmentsList.viewQuotes') }}
                </RouterLink>
                <RouterLink :to="`/shipments/${shipment.id}`" class="btn btn-outline btn-sm">
                  {{ t('shipmentsList.details') }}
                </RouterLink>
              </div>
            </div>
          </div>
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
  justify-content: space-between;
  align-items: center;
  height: 64px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.header-right {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.back-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: $radius-md;
  color: $text-secondary;
  transition: all $transition-fast;

  svg {
    width: 20px;
    height: 20px;
  }

  &:hover {
    background: $bg-hover;
    color: $text-primary;
  }
}

h1 {
  font-size: $font-size-xl;
  font-weight: 600;
  color: $text-primary;
  margin: 0;
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

.btn-sm {
  padding: $spacing-xs $spacing-md;
  font-size: $font-size-sm;
}

.page-content {
  padding: $spacing-xl 0;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: $spacing-2xl;
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

.shipments-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.shipment-card {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-lg;
  transition: all $transition-base;

  &:hover {
    border-color: rgba($color-primary, 0.3);
    box-shadow: $shadow;
  }
}

.shipment-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: $spacing-md;
}

.shipment-route {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.route-point {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.point-label {
  font-size: $font-size-xs;
  color: $text-muted;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.point-value {
  font-size: $font-size-base;
  font-weight: 500;
  color: $text-primary;
}

.route-arrow {
  width: 32px;
  height: 32px;
  background: rgba($color-primary, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  svg {
    width: 18px;
    height: 18px;
    color: $color-primary;
  }
}

.status {
  display: inline-block;
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;
}

.status-draft {
  background: $bg-light;
  color: $text-secondary;
}

.status-calculating {
  background: rgba($color-warning, 0.1);
  color: $color-warning;
}

.status-quoted {
  background: rgba($color-primary, 0.1);
  color: $color-primary;
}

.status-ordered {
  background: rgba($color-success, 0.1);
  color: $color-success;
}

.status-expired {
  background: rgba($color-danger, 0.1);
  color: $color-danger;
}

.shipment-details {
  display: flex;
  gap: $spacing-lg;
  padding: $spacing-md 0;
  border-top: 1px solid $border-color;
  border-bottom: 1px solid $border-color;
  margin-bottom: $spacing-md;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  color: $text-secondary;
  font-size: $font-size-sm;

  svg {
    width: 16px;
    height: 16px;
    color: $text-muted;
  }
}

.shipment-footer {
  display: flex;
  justify-content: flex-end;
}

.actions {
  display: flex;
  gap: $spacing-sm;
}

@media (max-width: $breakpoint-md) {
  .shipment-route {
    flex-direction: column;
    align-items: flex-start;
    gap: $spacing-sm;
  }

  .route-arrow {
    transform: rotate(90deg);
  }

  .shipment-details {
    flex-wrap: wrap;
    gap: $spacing-md;
  }

  .header-content {
    flex-direction: column;
    height: auto;
    padding: $spacing-md 0;
    gap: $spacing-md;
    align-items: flex-start;
  }
}

// Dark theme
[data-theme="dark"] {
  .page {
    background: #1a1a1a !important;
  }

  .page-header {
    background: #0f0f0f !important;
    border-bottom-color: #2a2a2a !important;
  }

  h1 {
    color: #f5f5f5 !important;
  }

  .back-link {
    color: #999999 !important;

    &:hover {
      background: #252525 !important;
      color: #f5f5f5 !important;
    }
  }

  .shipment-card {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    &:hover {
      border-color: rgba(249, 115, 22, 0.3) !important;
    }
  }

  .point-label {
    color: #999999 !important;
  }

  .point-value {
    color: #f5f5f5 !important;
  }

  .route-arrow {
    background: rgba(249, 115, 22, 0.15) !important;

    svg {
      color: #f97316 !important;
    }
  }

  .status-quoted {
    background: rgba(249, 115, 22, 0.15) !important;
    color: #f97316 !important;
  }

  .status-draft {
    background: #252525 !important;
    color: #999999 !important;
  }

  .status-ordered {
    background: rgba(34, 197, 94, 0.15) !important;
    color: #22c55e !important;
  }

  .status-expired {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #ef4444 !important;
  }

  .status-calculating {
    background: rgba(251, 191, 36, 0.15) !important;
    color: #fbbf24 !important;
  }

  .shipment-details {
    border-top-color: #2a2a2a !important;
    border-bottom-color: #2a2a2a !important;
  }

  .detail-item {
    color: #999999 !important;

    svg {
      color: #f97316 !important;
    }
  }

  .btn-outline {
    border-color: #f97316 !important;
    color: #f97316 !important;

    &:hover {
      background: #f97316 !important;
      color: #ffffff !important;
    }
  }

  .empty-state {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    h3 {
      color: #f5f5f5 !important;
    }

    p {
      color: #999999 !important;
    }
  }

  .empty-icon {
    background: #252525 !important;

    svg {
      color: #f97316 !important;
    }
  }

  .loading {
    color: #999999 !important;
  }

  .spinner {
    border-color: #2a2a2a !important;
    border-top-color: #f97316 !important;
  }
}
</style>
