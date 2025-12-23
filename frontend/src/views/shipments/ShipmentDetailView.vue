<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useShipmentsStore } from '@/stores/shipments'
import { ArrowLeft, ArrowRight, Package, MapPin, Truck, Calendar, Shield, FileCheck, Home, Calculator, Eye } from 'lucide-vue-next'
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const { t, locale } = useI18n()

const iconStrokeWidth = 1.2

const route = useRoute()
const router = useRouter()
const shipmentsStore = useShipmentsStore()

const shipment = ref(null)

onMounted(async () => {
  await shipmentsStore.fetchShipment(route.params.id)
  shipment.value = shipmentsStore.currentShipment
})

const totalWeight = computed(() => {
  if (!shipment.value?.items) return 0
  return shipment.value.items.reduce((sum, item) => sum + (item.weight || 0) * (item.quantity || 1), 0)
})

const totalVolume = computed(() => {
  if (!shipment.value?.items) return 0
  return shipment.value.items.reduce((sum, item) => {
    const volume = ((item.length || 0) * (item.width || 0) * (item.height || 0)) / 1000000
    return sum + volume * (item.quantity || 1)
  }, 0).toFixed(3)
})

const transportTypeLabels = computed(() => ({
  air: t('shipmentDetail.transportTypes.air'),
  sea: t('shipmentDetail.transportTypes.sea'),
  rail: t('shipmentDetail.transportTypes.rail'),
  road: t('shipmentDetail.transportTypes.road'),
  multimodal: t('shipmentDetail.transportTypes.multimodal')
}))

const cargoTypeLabels = computed(() => ({
  general: t('shipmentDetail.cargoTypes.general'),
  dangerous: t('shipmentDetail.cargoTypes.dangerous'),
  fragile: t('shipmentDetail.cargoTypes.fragile'),
  perishable: t('shipmentDetail.cargoTypes.perishable')
}))

const statusLabels = computed(() => ({
  draft: t('shipmentDetail.statuses.draft'),
  calculating: t('shipmentDetail.statuses.calculating'),
  quoted: t('shipmentDetail.statuses.quoted'),
  ordered: t('shipmentDetail.statuses.ordered'),
  expired: t('shipmentDetail.statuses.expired')
}))

function formatDate(dateString) {
  if (!dateString) return '-'
  const localeMap = { ru: 'ru-RU', en: 'en-US', kk: 'kk-KZ' }
  return new Date(dateString).toLocaleDateString(localeMap[locale.value] || 'ru-RU')
}

async function recalculate() {
  await shipmentsStore.calculateQuotes(shipment.value.id)
  router.push(`/shipments/${shipment.value.id}/quotes`)
}
</script>

<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <div class="header-content">
          <div class="header-left">
            <RouterLink to="/shipments" class="back-link">
              <ArrowLeft :size="16" :stroke-width="iconStrokeWidth" />
              {{ t('shipmentDetail.backToShipments') }}
            </RouterLink>
            <h1>{{ t('shipmentDetail.shipment') }} #{{ shipment?.id }}</h1>
          </div>
          <div class="header-right">
            <ThemeToggle />
            <LanguageSwitcher />
          </div>
        </div>
      </div>
    </header>

    <main class="container">
      <div v-if="shipmentsStore.loading" class="loading">
        <div class="spinner"></div>
        <span>{{ t('shipmentDetail.loading') }}</span>
      </div>

      <div v-else-if="shipment" class="shipment-details">
        <div class="status-bar">
          <span :class="['status', `status-${shipment.status}`]">
            {{ statusLabels[shipment.status] }}
          </span>
          <span class="date">{{ t('shipmentDetail.created') }}: {{ formatDate(shipment.created_at) }}</span>
        </div>

        <div class="details-grid">
          <div class="detail-card">
            <h3>
              <MapPin :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('shipmentDetail.route') }}
            </h3>
            <div class="route">
              <div class="location">
                <span class="label">{{ t('shipmentDetail.from') }}</span>
                <span class="city">{{ shipment.origin_city }}, {{ shipment.origin_country }}</span>
                <span v-if="shipment.origin_address" class="address">{{ shipment.origin_address }}</span>
              </div>
              <div class="arrow">
                <ArrowRight :size="20" :stroke-width="iconStrokeWidth" />
              </div>
              <div class="location">
                <span class="label">{{ t('shipmentDetail.to') }}</span>
                <span class="city">{{ shipment.destination_city }}, {{ shipment.destination_country }}</span>
                <span v-if="shipment.destination_address" class="address">{{ shipment.destination_address }}</span>
              </div>
            </div>
          </div>

          <div class="detail-card">
            <h3>
              <Package :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('shipmentDetail.cargoParams') }}
            </h3>
            <div class="params-grid">
              <div class="param">
                <span class="label">{{ t('shipmentDetail.totalWeight') }}</span>
                <span class="value">{{ totalWeight }} {{ t('shipmentDetail.kg') }}</span>
              </div>
              <div class="param">
                <span class="label">{{ t('shipmentDetail.volume') }}</span>
                <span class="value">{{ totalVolume }} {{ t('shipmentDetail.cbm') }}</span>
              </div>
              <div class="param">
                <span class="label">{{ t('shipmentDetail.pieces') }}</span>
                <span class="value">{{ shipment.items?.length || 0 }}</span>
              </div>
              <div class="param">
                <span class="label">{{ t('shipmentDetail.cargoType') }}</span>
                <span class="value">{{ cargoTypeLabels[shipment.cargo_type] || '-' }}</span>
              </div>
            </div>

            <div v-if="shipment.items?.length" class="items-list">
              <h4>{{ t('shipmentDetail.places') }}</h4>
              <div v-for="(item, index) in shipment.items" :key="index" class="item">
                <span class="item-number">{{ index + 1 }}</span>
                <span class="item-dims">
                  {{ item.length }}×{{ item.width }}×{{ item.height }} {{ t('shipmentDetail.cm') }}
                </span>
                <span class="item-weight">{{ item.weight }} {{ t('shipmentDetail.kg') }}</span>
                <span class="item-qty">× {{ item.quantity }}</span>
              </div>
            </div>
          </div>

          <div class="detail-card">
            <h3>
              <Truck :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('shipmentDetail.transportParams') }}
            </h3>
            <div class="params-list">
              <div class="param-row">
                <span class="label">{{ t('shipmentDetail.transportType') }}</span>
                <span class="value">{{ transportTypeLabels[shipment.transport_type] || t('shipmentDetail.anyType') }}</span>
              </div>
              <div class="param-row">
                <span class="label">{{ t('shipmentDetail.declaredValue') }}</span>
                <span class="value">{{ shipment.declared_value ? `${shipment.declared_value} ${shipment.currency}` : '-' }}</span>
              </div>
              <div class="param-row">
                <span class="label">{{ t('shipmentDetail.pickupDate') }}</span>
                <span class="value">{{ formatDate(shipment.pickup_date) }}</span>
              </div>
            </div>
          </div>

          <div class="detail-card">
            <h3>
              <FileCheck :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('shipmentDetail.additionalServices') }}
            </h3>
            <div class="services-list">
              <div :class="['service', { active: shipment.insurance_required }]">
                <Shield :size="16" :stroke-width="iconStrokeWidth" />
                {{ t('shipmentDetail.cargoInsurance') }}
              </div>
              <div :class="['service', { active: shipment.customs_clearance }]">
                <FileCheck :size="16" :stroke-width="iconStrokeWidth" />
                {{ t('shipmentDetail.customsClearance') }}
              </div>
              <div :class="['service', { active: shipment.door_to_door }]">
                <Home :size="16" :stroke-width="iconStrokeWidth" />
                {{ t('shipmentDetail.doorToDoor') }}
              </div>
            </div>
          </div>
        </div>

        <div v-if="shipment.notes" class="notes-card">
          <h3>{{ t('shipmentDetail.comment') }}</h3>
          <p>{{ shipment.notes }}</p>
        </div>

        <div class="actions">
          <RouterLink
            v-if="shipment.status === 'quoted'"
            :to="`/shipments/${shipment.id}/quotes`"
            class="btn btn-primary"
          >
            <Eye :size="18" :stroke-width="iconStrokeWidth" />
            {{ t('shipmentDetail.viewQuotes') }}
          </RouterLink>
          <button
            v-if="['draft', 'expired'].includes(shipment.status)"
            @click="recalculate"
            class="btn btn-primary"
            :disabled="shipmentsStore.calculating"
          >
            <Calculator :size="18" :stroke-width="iconStrokeWidth" />
            {{ shipmentsStore.calculating ? t('shipmentDetail.calculating') : t('shipmentDetail.calculateCost') }}
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
  padding: $spacing-lg 0;
  border-bottom: 1px solid $border-color;
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0 $container-padding;
}

.back-link {
  color: $color-primary;
  text-decoration: none;
  font-size: $font-size-sm;
  display: inline-flex;
  align-items: center;
  gap: $spacing-xs;
  margin-bottom: $spacing-sm;

  &:hover {
    color: $color-primary-dark;
  }
}

h1 {
  color: $text-primary;
  font-size: $font-size-2xl;
  font-weight: 600;
  margin: 0;
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

.shipment-details {
  padding: $spacing-xl 0;
}

.status-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-lg;
}

.status {
  padding: $spacing-xs $spacing-md;
  border-radius: $radius-full;
  font-size: $font-size-sm;
  font-weight: 500;
}

.status-draft { background: $bg-light; color: $text-secondary; }
.status-calculating { background: rgba($color-warning, 0.1); color: $color-warning; }
.status-quoted { background: rgba($color-primary, 0.1); color: $color-primary; }
.status-ordered { background: rgba($color-success, 0.1); color: $color-success; }
.status-expired { background: rgba($color-danger, 0.1); color: $color-danger; }

.date {
  color: $text-secondary;
  font-size: $font-size-sm;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-lg;
}

.detail-card {
  background: $bg-white;
  border-radius: $radius-lg;
  padding: $spacing-lg;
  border: 1px solid $border-color;
}

.detail-card h3 {
  color: $text-primary;
  margin-bottom: $spacing-md;
  font-size: $font-size-lg;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: $spacing-sm;

  svg {
    color: $color-primary;
  }
}

.route {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.location {
  flex: 1;
}

.location .label {
  display: block;
  font-size: $font-size-xs;
  color: $text-muted;
  margin-bottom: $spacing-xs;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.location .city {
  display: block;
  font-weight: 600;
  color: $text-primary;
}

.location .address {
  display: block;
  font-size: $font-size-sm;
  color: $text-secondary;
  margin-top: 2px;
}

.arrow {
  color: $color-primary;
  display: flex;
  align-items: center;
  justify-content: center;
}

.params-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-md;
}

.param .label {
  display: block;
  font-size: $font-size-xs;
  color: $text-muted;
  margin-bottom: $spacing-xs;
}

.param .value {
  display: block;
  font-size: $font-size-lg;
  font-weight: 600;
  color: $text-primary;
}

.items-list {
  margin-top: $spacing-md;
  padding-top: $spacing-md;
  border-top: 1px solid $border-color;
}

.items-list h4 {
  font-size: $font-size-sm;
  color: $text-secondary;
  margin-bottom: $spacing-sm;
  font-weight: 500;
}

.item {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-sm 0;
  border-bottom: 1px solid $bg-light;

  &:last-child {
    border-bottom: none;
  }
}

.item-number {
  width: 24px;
  height: 24px;
  background: $color-primary;
  color: $text-white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: $font-size-xs;
  font-weight: 500;
}

.item-dims, .item-weight {
  color: $text-primary;
  font-size: $font-size-sm;
}

.item-qty {
  color: $text-muted;
  font-size: $font-size-sm;
}

.params-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
}

.param-row {
  display: flex;
  justify-content: space-between;
  padding: $spacing-xs 0;
}

.param-row .label {
  color: $text-secondary;
  font-size: $font-size-sm;
}

.param-row .value {
  font-weight: 500;
  color: $text-primary;
  font-size: $font-size-sm;
}

.services-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
}

.service {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  color: $text-muted;
  font-size: $font-size-sm;
}

.service.active {
  color: $color-success;
}

.notes-card {
  background: $bg-white;
  border-radius: $radius-lg;
  padding: $spacing-lg;
  margin-top: $spacing-lg;
  border: 1px solid $border-color;
}

.notes-card h3 {
  margin-bottom: $spacing-sm;
  color: $text-primary;
  font-size: $font-size-base;
  font-weight: 600;
}

.notes-card p {
  color: $text-secondary;
  font-size: $font-size-sm;
  line-height: 1.6;
  margin: 0;
}

.actions {
  margin-top: $spacing-xl;
  display: flex;
  gap: $spacing-md;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-xs;
  padding: $spacing-sm $spacing-lg;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: all $transition-base;
  border: none;
}

.btn-primary {
  background: $color-primary;
  color: $text-white;

  &:hover:not(:disabled) {
    background: $color-primary-dark;
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.header-right {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

@media (max-width: $breakpoint-md) {
  .details-grid {
    grid-template-columns: 1fr;
  }

  .route {
    flex-direction: column;
    text-align: center;
  }

  .arrow {
    transform: rotate(90deg);
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
    color: #f97316 !important;

    &:hover {
      color: #fb923c !important;
    }
  }

  .status-bar .date {
    color: #999999 !important;
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

  .detail-card {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    h3 {
      color: #f5f5f5 !important;

      svg {
        color: #f97316 !important;
      }
    }
  }

  .location .label {
    color: #999999 !important;
  }

  .location .city {
    color: #f5f5f5 !important;
  }

  .location .address {
    color: #999999 !important;
  }

  .arrow {
    color: #f97316 !important;
  }

  .param .label {
    color: #999999 !important;
  }

  .param .value {
    color: #f5f5f5 !important;
  }

  .items-list {
    border-top-color: #2a2a2a !important;

    h4 {
      color: #999999 !important;
    }
  }

  .item {
    border-bottom-color: #2a2a2a !important;
  }

  .item-number {
    background: #f97316 !important;
    color: #ffffff !important;
  }

  .item-dims,
  .item-weight {
    color: #f5f5f5 !important;
  }

  .item-qty {
    color: #999999 !important;
  }

  .param-row .label {
    color: #999999 !important;
  }

  .param-row .value {
    color: #f5f5f5 !important;
  }

  .service {
    color: #666666 !important;

    &.active {
      color: #22c55e !important;
    }
  }

  .notes-card {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    h3 {
      color: #f5f5f5 !important;
    }

    p {
      color: #999999 !important;
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
