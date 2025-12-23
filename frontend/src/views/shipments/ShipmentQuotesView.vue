<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useShipmentsStore } from '@/stores/shipments'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, Star, FileText, ChevronDown, ChevronUp, Package, Fuel, Home, MapPin, Shield, FileCheck, Scale, Zap, DollarSign, Award } from 'lucide-vue-next'
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const iconStrokeWidth = 1.2
const { t, locale } = useI18n()

const route = useRoute()
const router = useRouter()
const shipmentsStore = useShipmentsStore()

const sortBy = ref('price')
const filterTransport = ref('')
const expandedQuote = ref(null)

onMounted(async () => {
  try {
    await shipmentsStore.fetchShipment(route.params.id)
    await shipmentsStore.fetchQuotes(route.params.id)
  } catch (e) {
    console.error('Error loading quotes:', e)
  }
})

const shipment = computed(() => shipmentsStore.currentShipment)
const quotes = computed(() => shipmentsStore.quotes)

// Find cheapest quote
const cheapestQuoteId = computed(() => {
  if (!quotes.value.length) return null
  const cheapest = [...quotes.value].sort((a, b) => a.price - b.price)[0]
  return cheapest?.id
})

// Find fastest quote
const fastestQuoteId = computed(() => {
  if (!quotes.value.length) return null
  const fastest = [...quotes.value].sort((a, b) => a.delivery_days - b.delivery_days)[0]
  return fastest?.id
})

// Find best value (optimal price/time ratio)
const bestValueQuoteId = computed(() => {
  if (!quotes.value.length) return null
  // Calculate score: lower is better (normalized price + normalized time)
  const quotesWithScore = quotes.value.map(q => {
    const maxPrice = Math.max(...quotes.value.map(x => x.price))
    const maxDays = Math.max(...quotes.value.map(x => x.delivery_days))
    const priceScore = maxPrice > 0 ? q.price / maxPrice : 0
    const daysScore = maxDays > 0 ? q.delivery_days / maxDays : 0
    return { ...q, score: priceScore + daysScore }
  })
  const best = quotesWithScore.sort((a, b) => a.score - b.score)[0]
  return best?.id
})

const sortedQuotes = computed(() => {
  let result = [...quotes.value]

  if (filterTransport.value) {
    result = result.filter((q) => q.transport_type === filterTransport.value)
  }

  if (sortBy.value === 'price') {
    result.sort((a, b) => a.price - b.price)
  } else if (sortBy.value === 'delivery') {
    result.sort((a, b) => a.delivery_days - b.delivery_days)
  } else if (sortBy.value === 'rating') {
    result.sort((a, b) => (b.carrier?.company?.rating || 0) - (a.carrier?.company?.rating || 0))
  }

  return result
})

const transportTypeLabels = computed(() => ({
  air: t('shipmentQuotes.transportTypes.air'),
  sea: t('shipmentQuotes.transportTypes.sea'),
  rail: t('shipmentQuotes.transportTypes.rail'),
  road: t('shipmentQuotes.transportTypes.road')
}))

const serviceLabels = computed(() => ({
  door_pickup: t('shipmentQuotes.services.doorPickup'),
  customs: t('shipmentQuotes.services.customs'),
  customs_clearance: t('shipmentQuotes.services.customsClearance'),
  insurance: t('shipmentQuotes.services.insurance'),
  door_delivery: t('shipmentQuotes.services.doorDelivery')
}))

const surchargeIcons = {
  fuel: Fuel,
  residential: Home,
  remote_area: MapPin,
  default: Package
}

function formatDate(dateString) {
  if (!dateString) return '-'
  const localeMap = { ru: 'ru-RU', en: 'en-US', kk: 'kk-KZ' }
  return new Date(dateString).toLocaleDateString(localeMap[locale.value] || 'ru-RU')
}

function formatPrice(value) {
  if (!value && value !== 0) return '-'
  return Number(value).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function togglePriceBreakdown(quoteId) {
  expandedQuote.value = expandedQuote.value === quoteId ? null : quoteId
}

function selectQuote(quote) {
  router.push(`/orders/create/${quote.id}`)
}

function getSurchargeIcon(type) {
  return surchargeIcons[type] || surchargeIcons.default
}
</script>

<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <div class="header-content">
          <div class="header-left">
            <RouterLink :to="`/shipments/${route.params.id}`" class="back-link">
              <ArrowLeft :size="20" :stroke-width="iconStrokeWidth" />
            </RouterLink>
            <div>
              <h1>{{ t('shipmentQuotes.title') }}</h1>
              <p v-if="shipment" class="route-summary">
                {{ shipment.origin_city }}, {{ shipment.origin_country }}
                <span class="arrow">→</span>
                {{ shipment.destination_city }}, {{ shipment.destination_country }}
                <span class="weight-info" v-if="shipment.total_weight">
                  · {{ shipment.total_weight }} {{ t('shipment.kg') }}
                </span>
              </p>
            </div>
          </div>
          <div class="header-right">
            <ThemeToggle />
            <LanguageSwitcher />
          </div>
        </div>
      </div>
    </header>

    <main class="page-content">
      <div class="container">
        <div v-if="shipmentsStore.loading" class="loading">
          <div class="spinner"></div>
          <span>{{ t('shipmentQuotes.loading') }}</span>
        </div>

        <template v-else>
          <div class="filters-bar">
            <div class="filter-group">
              <label>{{ t('shipmentQuotes.sorting') }}</label>
              <select v-model="sortBy">
                <option value="price">{{ t('shipmentQuotes.sortByPrice') }}</option>
                <option value="delivery">{{ t('shipmentQuotes.sortByDelivery') }}</option>
                <option value="rating">{{ t('shipmentQuotes.sortByRating') }}</option>
              </select>
            </div>

            <div class="filter-group">
              <label>{{ t('shipmentQuotes.transportType') }}</label>
              <select v-model="filterTransport">
                <option value="">{{ t('shipmentQuotes.allTypes') }}</option>
                <option value="air">{{ t('shipmentQuotes.transportTypes.air') }}</option>
                <option value="sea">{{ t('shipmentQuotes.transportTypes.sea') }}</option>
                <option value="rail">{{ t('shipmentQuotes.transportTypes.rail') }}</option>
                <option value="road">{{ t('shipmentQuotes.transportTypes.road') }}</option>
              </select>
            </div>

            <div class="results-count">
              {{ t('shipmentQuotes.found') }}: <strong>{{ sortedQuotes.length }}</strong> {{ t('shipmentQuotes.offers') }}
            </div>
          </div>

          <div v-if="sortedQuotes.length === 0" class="empty-state">
            <div class="empty-icon">
              <FileText :size="32" :stroke-width="iconStrokeWidth" />
            </div>
            <h3>{{ t('shipmentQuotes.noQuotes') }}</h3>
            <p>{{ t('shipmentQuotes.noQuotesText') }}</p>
          </div>

          <div v-else class="quotes-list">
            <div v-for="quote in sortedQuotes" :key="quote.id" class="quote-card" :class="{ expanded: expandedQuote === quote.id }">
              <!-- Quote badges -->
              <div class="quote-badges">
                <div v-if="quote.id === cheapestQuoteId" class="badge badge-cheapest">
                  <DollarSign :size="12" :stroke-width="iconStrokeWidth" />
                  {{ t('quotes.cheapest') }}
                </div>
                <div v-if="quote.id === fastestQuoteId" class="badge badge-fastest">
                  <Zap :size="12" :stroke-width="iconStrokeWidth" />
                  {{ t('quotes.fastest') }}
                </div>
                <div v-if="quote.id === bestValueQuoteId && quote.id !== cheapestQuoteId && quote.id !== fastestQuoteId" class="badge badge-best">
                  <Award :size="12" :stroke-width="iconStrokeWidth" />
                  {{ t('quotes.bestValue') }}
                </div>
              </div>

              <div class="quote-main">
                <div class="carrier-section">
                  <div class="carrier-logo">
                    <span>{{ quote.carrier?.company?.name?.charAt(0) || '?' }}</span>
                  </div>
                  <div class="carrier-info">
                    <span class="carrier-name">{{ quote.carrier?.company?.name || t('shipmentQuotes.carrier') }}</span>
                    <div class="carrier-rating" v-if="quote.carrier?.company?.rating">
                      <Star :size="14" :stroke-width="iconStrokeWidth" />
                      <span>{{ Number(quote.carrier.company.rating).toFixed(1) }}</span>
                    </div>
                  </div>
                </div>

                <div class="quote-details">
                  <div class="detail-item">
                    <span class="detail-label">{{ t('shipmentQuotes.type') }}</span>
                    <span class="detail-value transport-tag" :class="`transport-${quote.transport_type}`">
                      {{ transportTypeLabels[quote.transport_type] || quote.transport_type }}
                    </span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">{{ t('shipmentQuotes.term') }}</span>
                    <span class="detail-value">
                      {{ quote.delivery_days_min && quote.delivery_days_min !== quote.delivery_days
                         ? `${quote.delivery_days_min}-${quote.delivery_days}`
                         : quote.delivery_days }} {{ t('shipmentQuotes.days') }}
                    </span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">{{ t('shipmentQuotes.delivery') }}</span>
                    <span class="detail-value">{{ formatDate(quote.estimated_delivery_date) }}</span>
                  </div>
                  <div class="detail-item" v-if="quote.billable_weight">
                    <span class="detail-label">{{ t('shipmentQuotes.billableWeight') }}</span>
                    <span class="detail-value">{{ quote.billable_weight }} {{ t('shipment.kg') }}</span>
                  </div>
                </div>

                <div class="quote-price-section">
                  <div class="quote-price">
                    <span class="price-value">{{ formatPrice(quote.price) }}</span>
                    <span class="price-currency">{{ quote.currency || 'USD' }}</span>
                  </div>
                  <button
                    class="breakdown-toggle"
                    @click="togglePriceBreakdown(quote.id)"
                    :class="{ active: expandedQuote === quote.id }"
                  >
                    <span>{{ t('shipmentQuotes.priceDetails') }}</span>
                    <ChevronDown v-if="expandedQuote !== quote.id" :size="16" :stroke-width="iconStrokeWidth" />
                    <ChevronUp v-else :size="16" :stroke-width="iconStrokeWidth" />
                  </button>
                </div>
              </div>

              <!-- Price Breakdown Panel -->
              <div class="price-breakdown" v-if="expandedQuote === quote.id">
                <div class="breakdown-grid">
                  <div class="breakdown-item">
                    <div class="breakdown-icon">
                      <Package :size="18" :stroke-width="iconStrokeWidth" />
                    </div>
                    <div class="breakdown-details">
                      <span class="breakdown-label">{{ t('shipmentQuotes.baseRate') }}</span>
                      <span class="breakdown-desc">{{ t('shipmentQuotes.cargoTransportation') }}</span>
                    </div>
                    <span class="breakdown-value">{{ formatPrice(quote.base_rate) }} {{ quote.currency }}</span>
                  </div>

                  <!-- Surcharges -->
                  <template v-if="quote.surcharges?.items?.length">
                    <div
                      class="breakdown-item"
                      v-for="surcharge in quote.surcharges.items"
                      :key="surcharge.type"
                    >
                      <div class="breakdown-icon surcharge">
                        <component :is="getSurchargeIcon(surcharge.type)" :size="18" :stroke-width="iconStrokeWidth" />
                      </div>
                      <div class="breakdown-details">
                        <span class="breakdown-label">{{ surcharge.name }}</span>
                        <span class="breakdown-desc">{{ t('shipmentQuotes.surcharge') }}</span>
                      </div>
                      <span class="breakdown-value">+{{ formatPrice(surcharge.amount) }} {{ quote.currency }}</span>
                    </div>
                  </template>

                  <!-- Insurance -->
                  <div class="breakdown-item" v-if="quote.insurance_cost > 0">
                    <div class="breakdown-icon insurance">
                      <Shield :size="18" :stroke-width="iconStrokeWidth" />
                    </div>
                    <div class="breakdown-details">
                      <span class="breakdown-label">{{ t('shipmentQuotes.cargoInsurance') }}</span>
                      <span class="breakdown-desc">{{ t('shipmentQuotes.insurancePercent') }}</span>
                    </div>
                    <span class="breakdown-value">+{{ formatPrice(quote.insurance_cost) }} {{ quote.currency }}</span>
                  </div>

                  <!-- Customs -->
                  <div class="breakdown-item" v-if="quote.customs_fee > 0">
                    <div class="breakdown-icon customs">
                      <FileCheck :size="18" :stroke-width="iconStrokeWidth" />
                    </div>
                    <div class="breakdown-details">
                      <span class="breakdown-label">{{ t('shipmentQuotes.customsClearance') }}</span>
                      <span class="breakdown-desc">{{ t('shipmentQuotes.fixedFee') }}</span>
                    </div>
                    <span class="breakdown-value">+{{ formatPrice(quote.customs_fee) }} {{ quote.currency }}</span>
                  </div>

                  <!-- Weight info -->
                  <div class="breakdown-item weight-info-row" v-if="quote.billable_weight">
                    <div class="breakdown-icon weight">
                      <Scale :size="18" :stroke-width="iconStrokeWidth" />
                    </div>
                    <div class="breakdown-details">
                      <span class="breakdown-label">{{ t('shipmentQuotes.billableWeightLabel') }}</span>
                      <span class="breakdown-desc">{{ t('shipmentQuotes.maxOfActualAndVolumetric') }}</span>
                    </div>
                    <span class="breakdown-value">{{ quote.billable_weight }} {{ t('shipment.kg') }}</span>
                  </div>
                </div>

                <div class="breakdown-total">
                  <span>{{ t('shipmentQuotes.totalPayable') }}</span>
                  <span class="total-value">{{ formatPrice(quote.price) }} {{ quote.currency }}</span>
                </div>
              </div>

              <div class="quote-footer">
                <div class="services-list" v-if="quote.services_included?.length">
                  <span v-for="service in quote.services_included" :key="service" class="service-tag">
                    {{ serviceLabels[service] || service }}
                  </span>
                </div>
                <div class="quote-actions">
                  <span class="valid-until" v-if="quote.valid_until">
                    {{ t('shipmentQuotes.validUntil') }} {{ formatDate(quote.valid_until) }}
                  </span>
                  <button
                    @click="selectQuote(quote)"
                    class="btn btn-primary"
                  >
                    {{ t('shipmentQuotes.select') }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>
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
  padding: 0 $container-padding;
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-lg 0;
}

.header-left {
  display: flex;
  align-items: flex-start;
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
  margin-top: 4px;

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
  margin: 0 0 $spacing-xs;
}

.route-summary {
  color: $text-secondary;
  font-size: $font-size-sm;
  margin: 0;

  .arrow {
    color: $color-primary;
    margin: 0 $spacing-xs;
  }

  .weight-info {
    color: $text-muted;
  }
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

.filters-bar {
  display: flex;
  align-items: center;
  gap: $spacing-lg;
  padding: $spacing-md $spacing-lg;
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  margin-bottom: $spacing-lg;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;

  label {
    font-size: $font-size-xs;
    color: $text-muted;
    font-weight: 500;
  }

  select {
    padding: $spacing-xs 32px $spacing-xs $spacing-md;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    background: $bg-white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
    min-width: 140px;
    appearance: none;
    cursor: pointer;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

.results-count {
  margin-left: auto;
  color: $text-secondary;
  font-size: $font-size-sm;
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
  margin: 0;
}

.quotes-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.quote-card {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  overflow: hidden;
  transition: all $transition-base;
  position: relative;

  &:hover {
    border-color: rgba($color-primary, 0.3);
    box-shadow: $shadow;
  }

  &.expanded {
    border-color: $color-primary;
  }
}

// Quote badges
.quote-badges {
  position: absolute;
  top: 0;
  right: $spacing-md;
  display: flex;
  gap: $spacing-xs;
  z-index: 1;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: $spacing-xs $spacing-sm;
  font-size: $font-size-xs;
  font-weight: 600;
  border-radius: 0 0 $radius-md $radius-md;

  svg {
    width: 12px;
    height: 12px;
  }
}

.badge-cheapest {
  background: $color-success;
  color: $text-white;
}

.badge-fastest {
  background: #8b5cf6;
  color: $text-white;
}

.badge-best {
  background: $color-warning;
  color: #1f2937;
}

.quote-main {
  display: grid;
  grid-template-columns: 200px 1fr auto;
  gap: $spacing-xl;
  align-items: center;
  padding: $spacing-lg;
}

.carrier-section {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.carrier-logo {
  width: 48px;
  height: 48px;
  background-color: $color-primary;
  color: $text-white;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: $font-size-xl;
  font-weight: 700;
}

.carrier-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.carrier-name {
  font-weight: 600;
  color: $text-primary;
}

.carrier-rating {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: $font-size-sm;
  color: $text-secondary;

  svg {
    width: 14px;
    height: 14px;
    color: $color-warning;
  }
}

.quote-details {
  display: flex;
  gap: $spacing-xl;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.detail-label {
  font-size: $font-size-xs;
  color: $text-muted;
}

.detail-value {
  font-weight: 500;
  color: $text-primary;
}

.transport-tag {
  display: inline-block;
  padding: 2px $spacing-sm;
  border-radius: $radius-md;
  font-size: $font-size-xs;
  background: $bg-light;
}

.transport-air { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.transport-sea { background: rgba(6, 182, 212, 0.1); color: #06b6d4; }
.transport-rail { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.transport-road { background: rgba(34, 197, 94, 0.1); color: #22c55e; }

.quote-price-section {
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.quote-price {
  display: flex;
  align-items: baseline;
  justify-content: flex-end;
  gap: $spacing-xs;
}

.price-value {
  font-size: $font-size-2xl;
  font-weight: 700;
  color: $text-primary;
}

.price-currency {
  font-size: $font-size-sm;
  color: $text-secondary;
}

.breakdown-toggle {
  display: inline-flex;
  align-items: center;
  gap: $spacing-xs;
  padding: $spacing-xs $spacing-sm;
  background: transparent;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-xs;
  color: $text-secondary;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover, &.active {
    background: $bg-light;
    color: $color-primary;
    border-color: $color-primary;
  }

  svg {
    width: 16px;
    height: 16px;
  }
}

// Price Breakdown Panel
.price-breakdown {
  padding: $spacing-lg;
  background: $bg-light;
  border-top: 1px solid $border-color;
}

.breakdown-grid {
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
}

.breakdown-item {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-sm $spacing-md;
  background: $bg-white;
  border-radius: $radius-md;
  border: 1px solid $border-color;
}

.breakdown-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: $radius-md;
  background: rgba($color-primary, 0.1);
  color: $color-primary;

  &.surcharge {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
  }

  &.insurance {
    background: rgba($color-info, 0.1);
    color: $color-info;
  }

  &.customs {
    background: rgba(139, 92, 246, 0.1);
    color: #8b5cf6;
  }

  &.weight {
    background: rgba($text-secondary, 0.1);
    color: $text-secondary;
  }

  svg {
    width: 18px;
    height: 18px;
  }
}

.breakdown-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.breakdown-label {
  font-weight: 500;
  font-size: $font-size-sm;
  color: $text-primary;
}

.breakdown-desc {
  font-size: $font-size-xs;
  color: $text-muted;
}

.breakdown-value {
  font-weight: 600;
  font-size: $font-size-sm;
  color: $text-primary;
}

.weight-info-row {
  background: $bg-light;
  border-style: dashed;
}

.breakdown-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: $spacing-md;
  padding-top: $spacing-md;
  border-top: 2px solid $border-color;
  font-weight: 600;
  color: $text-primary;

  .total-value {
    font-size: $font-size-xl;
    color: $color-primary;
  }
}

.quote-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md $spacing-lg;
  background: $bg-white;
  border-top: 1px solid $border-color;
}

.services-list {
  display: flex;
  gap: $spacing-sm;
  flex-wrap: wrap;
}

.service-tag {
  display: inline-block;
  padding: $spacing-xs $spacing-sm;
  background: rgba($color-success, 0.1);
  color: $color-success;
  font-size: $font-size-xs;
  font-weight: 500;
  border-radius: $radius-full;
}

.quote-actions {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.valid-until {
  font-size: $font-size-xs;
  color: $text-muted;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: $spacing-sm $spacing-lg;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-base;
  text-decoration: none;
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

@media (max-width: $breakpoint-md) {
  .quote-main {
    grid-template-columns: 1fr;
    gap: $spacing-md;
  }

  .quote-price-section {
    text-align: left;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .filters-bar {
    flex-wrap: wrap;
  }

  .quote-footer {
    flex-direction: column;
    gap: $spacing-md;
    align-items: flex-start;
  }

  .quote-actions {
    width: 100%;
    justify-content: space-between;
  }

  .quote-details {
    flex-wrap: wrap;
    gap: $spacing-md;
  }

  .breakdown-item {
    flex-wrap: wrap;
  }

  .breakdown-value {
    width: 100%;
    text-align: right;
    margin-top: $spacing-xs;
  }
}
</style>

<style lang="scss">
// Dark theme overrides (non-scoped for [data-theme="dark"] selector)
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

  .route-summary {
    color: #999999 !important;

    .arrow {
      color: #f97316 !important;
    }

    .weight-info {
      color: #666666 !important;
    }
  }

  .back-link {
    color: #999999 !important;

    &:hover {
      background: #252525 !important;
      color: #f5f5f5 !important;
    }
  }

  .loading {
    color: #999999 !important;
  }

  .spinner {
    border-color: #2a2a2a !important;
    border-top-color: #f97316 !important;
  }

  .filters-bar {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;
  }

  .filter-group {
    label {
      color: #666666 !important;
    }

    select {
      background-color: #1a1a1a !important;
      border-color: #2a2a2a !important;
      color: #f5f5f5 !important;

      &:focus {
        border-color: #f97316 !important;
      }
    }
  }

  .results-count {
    color: #999999 !important;
  }

  .empty-state {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;
  }

  .empty-icon {
    background: #252525 !important;

    svg {
      color: #f97316 !important;
    }
  }

  .empty-state h3 {
    color: #f5f5f5 !important;
  }

  .empty-state p {
    color: #999999 !important;
  }

  .quote-card {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    &:hover {
      border-color: rgba(249, 115, 22, 0.3) !important;
    }

    &.expanded {
      border-color: #f97316 !important;
    }
  }

  .carrier-logo {
    background-color: #f97316 !important;
  }

  .carrier-name {
    color: #f5f5f5 !important;
  }

  .carrier-rating {
    color: #999999 !important;
  }

  .detail-label {
    color: #666666 !important;
  }

  .detail-value {
    color: #f5f5f5 !important;
  }

  .transport-tag {
    background: #252525 !important;
  }

  .transport-air {
    background: rgba(59, 130, 246, 0.15) !important;
    color: #3b82f6 !important;
  }

  .transport-sea {
    background: rgba(6, 182, 212, 0.15) !important;
    color: #06b6d4 !important;
  }

  .transport-rail {
    background: rgba(139, 92, 246, 0.15) !important;
    color: #8b5cf6 !important;
  }

  .transport-road {
    background: rgba(34, 197, 94, 0.15) !important;
    color: #22c55e !important;
  }

  .price-value {
    color: #f5f5f5 !important;
  }

  .price-currency {
    color: #999999 !important;
  }

  .breakdown-toggle {
    border-color: #2a2a2a !important;
    color: #999999 !important;

    &:hover, &.active {
      background: #252525 !important;
      color: #f97316 !important;
      border-color: #f97316 !important;
    }
  }

  .price-breakdown {
    background: #1a1a1a !important;
    border-top-color: #2a2a2a !important;
  }

  .breakdown-item {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;
  }

  .breakdown-icon {
    background: rgba(249, 115, 22, 0.15) !important;
    color: #f97316 !important;

    &.surcharge {
      background: rgba(251, 191, 36, 0.15) !important;
      color: #fbbf24 !important;
    }

    &.insurance {
      background: rgba(59, 130, 246, 0.15) !important;
      color: #3b82f6 !important;
    }

    &.customs {
      background: rgba(139, 92, 246, 0.15) !important;
      color: #8b5cf6 !important;
    }

    &.weight {
      background: rgba(102, 102, 102, 0.15) !important;
      color: #999999 !important;
    }
  }

  .breakdown-label {
    color: #f5f5f5 !important;
  }

  .breakdown-desc {
    color: #666666 !important;
  }

  .breakdown-value {
    color: #f5f5f5 !important;
  }

  .weight-info-row {
    background: #1a1a1a !important;
  }

  .breakdown-total {
    border-top-color: #2a2a2a !important;
    color: #f5f5f5 !important;

    .total-value {
      color: #f97316 !important;
    }
  }

  .quote-footer {
    background: #0f0f0f !important;
    border-top-color: #2a2a2a !important;
  }

  .services-list .service-tag {
    background: rgba(34, 197, 94, 0.15) !important;
    color: #22c55e !important;
  }

  .valid-until {
    color: #666666 !important;
  }

  .btn-primary {
    background: #f97316 !important;

    &:hover:not(:disabled) {
      background: #ea580c !important;
    }
  }

  .badge-cheapest {
    background: #22c55e !important;
  }

  .badge-fastest {
    background: #8b5cf6 !important;
  }

  .badge-best {
    background: #fbbf24 !important;
  }
}
</style>
