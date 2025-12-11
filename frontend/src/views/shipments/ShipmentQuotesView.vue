<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useShipmentsStore } from '@/stores/shipments'
import { useOrdersStore } from '@/stores/orders'
import { ArrowLeft, Star, FileText, ChevronDown, ChevronUp, Package, Fuel, Home, MapPin, Shield, FileCheck, Scale } from 'lucide-vue-next'

const iconStrokeWidth = 1.2

const route = useRoute()
const router = useRouter()
const shipmentsStore = useShipmentsStore()
const ordersStore = useOrdersStore()

const sortBy = ref('price')
const filterTransport = ref('')
const expandedQuote = ref(null)
const selectingQuote = ref(null)

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

const transportTypeLabels = {
  air: 'Авиа',
  sea: 'Морской',
  rail: 'Ж/Д',
  road: 'Авто'
}

const serviceLabels = {
  door_pickup: 'Забор груза',
  customs: 'Таможня',
  customs_clearance: 'Таможенное оформление',
  insurance: 'Страховка',
  door_delivery: 'Доставка до двери'
}

const surchargeIcons = {
  fuel: Fuel,
  residential: Home,
  remote_area: MapPin,
  default: Package
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU')
}

function formatPrice(value) {
  if (!value && value !== 0) return '-'
  return Number(value).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function togglePriceBreakdown(quoteId) {
  expandedQuote.value = expandedQuote.value === quoteId ? null : quoteId
}

async function selectQuote(quote) {
  selectingQuote.value = quote.id
  try {
    const order = await ordersStore.createOrder({
      quote_id: quote.id,
      contact_name: '',
      contact_phone: '',
      contact_email: ''
    })
    router.push(`/orders/${order.id}`)
  } catch (e) {
    console.error('Error creating order:', e)
  } finally {
    selectingQuote.value = null
  }
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
              <h1>Предложения перевозчиков</h1>
              <p v-if="shipment" class="route-summary">
                {{ shipment.origin_city }}, {{ shipment.origin_country }}
                <span class="arrow">→</span>
                {{ shipment.destination_city }}, {{ shipment.destination_country }}
                <span class="weight-info" v-if="shipment.total_weight">
                  · {{ shipment.total_weight }} кг
                </span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="page-content">
      <div class="container">
        <div v-if="shipmentsStore.loading" class="loading">
          <div class="spinner"></div>
          <span>Загрузка предложений...</span>
        </div>

        <template v-else>
          <div class="filters-bar">
            <div class="filter-group">
              <label>Сортировка</label>
              <select v-model="sortBy">
                <option value="price">По цене</option>
                <option value="delivery">По сроку</option>
                <option value="rating">По рейтингу</option>
              </select>
            </div>

            <div class="filter-group">
              <label>Тип перевозки</label>
              <select v-model="filterTransport">
                <option value="">Все типы</option>
                <option value="air">Авиа</option>
                <option value="sea">Морской</option>
                <option value="rail">Ж/Д</option>
                <option value="road">Автомобильный</option>
              </select>
            </div>

            <div class="results-count">
              Найдено: <strong>{{ sortedQuotes.length }}</strong> предложений
            </div>
          </div>

          <div v-if="sortedQuotes.length === 0" class="empty-state">
            <div class="empty-icon">
              <FileText :size="32" :stroke-width="iconStrokeWidth" />
            </div>
            <h3>Нет предложений</h3>
            <p>По выбранным критериям предложения не найдены</p>
          </div>

          <div v-else class="quotes-list">
            <div v-for="(quote, index) in sortedQuotes" :key="quote.id" class="quote-card" :class="{ expanded: expandedQuote === quote.id }">
              <div v-if="index === 0 && sortBy === 'price'" class="best-badge">Лучшая цена</div>

              <div class="quote-main">
                <div class="carrier-section">
                  <div class="carrier-logo">
                    <span>{{ quote.carrier?.company?.name?.charAt(0) || '?' }}</span>
                  </div>
                  <div class="carrier-info">
                    <span class="carrier-name">{{ quote.carrier?.company?.name || 'Перевозчик' }}</span>
                    <div class="carrier-rating" v-if="quote.carrier?.company?.rating">
                      <Star :size="14" :stroke-width="iconStrokeWidth" />
                      <span>{{ Number(quote.carrier.company.rating).toFixed(1) }}</span>
                    </div>
                  </div>
                </div>

                <div class="quote-details">
                  <div class="detail-item">
                    <span class="detail-label">Тип</span>
                    <span class="detail-value transport-tag" :class="`transport-${quote.transport_type}`">
                      {{ transportTypeLabels[quote.transport_type] || quote.transport_type }}
                    </span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Срок</span>
                    <span class="detail-value">
                      {{ quote.delivery_days_min && quote.delivery_days_min !== quote.delivery_days
                         ? `${quote.delivery_days_min}-${quote.delivery_days}`
                         : quote.delivery_days }} дн.
                    </span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Доставка</span>
                    <span class="detail-value">{{ formatDate(quote.estimated_delivery_date) }}</span>
                  </div>
                  <div class="detail-item" v-if="quote.billable_weight">
                    <span class="detail-label">Тариф. вес</span>
                    <span class="detail-value">{{ quote.billable_weight }} кг</span>
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
                    <span>Детали цены</span>
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
                      <span class="breakdown-label">Базовая ставка</span>
                      <span class="breakdown-desc">Перевозка груза</span>
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
                        <span class="breakdown-desc">Надбавка</span>
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
                      <span class="breakdown-label">Страхование груза</span>
                      <span class="breakdown-desc">0.5% от стоимости</span>
                    </div>
                    <span class="breakdown-value">+{{ formatPrice(quote.insurance_cost) }} {{ quote.currency }}</span>
                  </div>

                  <!-- Customs -->
                  <div class="breakdown-item" v-if="quote.customs_fee > 0">
                    <div class="breakdown-icon customs">
                      <FileCheck :size="18" :stroke-width="iconStrokeWidth" />
                    </div>
                    <div class="breakdown-details">
                      <span class="breakdown-label">Таможенное оформление</span>
                      <span class="breakdown-desc">Фиксированная плата</span>
                    </div>
                    <span class="breakdown-value">+{{ formatPrice(quote.customs_fee) }} {{ quote.currency }}</span>
                  </div>

                  <!-- Weight info -->
                  <div class="breakdown-item weight-info-row" v-if="quote.billable_weight">
                    <div class="breakdown-icon weight">
                      <Scale :size="18" :stroke-width="iconStrokeWidth" />
                    </div>
                    <div class="breakdown-details">
                      <span class="breakdown-label">Тарифицируемый вес</span>
                      <span class="breakdown-desc">Макс. из фактического и объемного</span>
                    </div>
                    <span class="breakdown-value">{{ quote.billable_weight }} кг</span>
                  </div>
                </div>

                <div class="breakdown-total">
                  <span>Итого к оплате</span>
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
                    Действует до {{ formatDate(quote.valid_until) }}
                  </span>
                  <button
                    @click="selectQuote(quote)"
                    class="btn btn-primary"
                    :disabled="selectingQuote === quote.id"
                  >
                    <span v-if="selectingQuote === quote.id">Оформление...</span>
                    <span v-else>Выбрать</span>
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
  padding: $spacing-lg 0;
}

.header-left {
  display: flex;
  align-items: flex-start;
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

.best-badge {
  position: absolute;
  top: 0;
  right: $spacing-lg;
  background: $color-success;
  color: $text-white;
  padding: $spacing-xs $spacing-sm;
  font-size: $font-size-xs;
  font-weight: 600;
  border-radius: 0 0 $radius-md $radius-md;
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
