<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useShipmentsStore } from '@/stores/shipments'
import { useOrdersStore } from '@/stores/orders'
import { ArrowLeft, Star, FileText } from 'lucide-vue-next'

const iconStrokeWidth = 1.2

const route = useRoute()
const router = useRouter()
const shipmentsStore = useShipmentsStore()
const ordersStore = useOrdersStore()

const sortBy = ref('price')
const filterTransport = ref('')

onMounted(async () => {
  await shipmentsStore.fetchShipment(route.params.id)
  await shipmentsStore.fetchQuotes(route.params.id)
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
    result.sort((a, b) => (b.carrier?.rating || 0) - (a.carrier?.rating || 0))
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
  door_pickup: 'Забор',
  customs: 'Таможня',
  insurance: 'Страховка',
  door_delivery: 'До двери'
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU')
}

async function selectQuote(quote) {
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
  }
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
            <div v-for="(quote, index) in sortedQuotes" :key="quote.id" class="quote-card">
              <div v-if="index === 0 && sortBy === 'price'" class="best-badge">Лучшая цена</div>

              <div class="quote-main">
                <div class="carrier-section">
                  <div class="carrier-logo">
                    <span>{{ quote.carrier?.name?.charAt(0) || '?' }}</span>
                  </div>
                  <div class="carrier-info">
                    <span class="carrier-name">{{ quote.carrier?.name || 'Перевозчик' }}</span>
                    <div class="carrier-rating" v-if="quote.carrier?.rating">
                      <Star :size="14" :stroke-width="iconStrokeWidth" />
                      <span>{{ quote.carrier.rating.toFixed(1) }}</span>
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
                    <span class="detail-value">{{ quote.delivery_days }} дн.</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Доставка</span>
                    <span class="detail-value">{{ formatDate(quote.estimated_delivery_date) }}</span>
                  </div>
                </div>

                <div class="quote-price">
                  <span class="price-value">{{ quote.price?.toLocaleString() }}</span>
                  <span class="price-currency">{{ quote.currency || 'USD' }}</span>
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
                  <button @click="selectQuote(quote)" class="btn btn-primary">
                    Выбрать
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
    padding: $spacing-xs $spacing-md;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    background: $bg-white;
    min-width: 140px;

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

.quote-price {
  text-align: right;
}

.price-value {
  font-size: $font-size-2xl;
  font-weight: 700;
  color: $text-primary;
}

.price-currency {
  font-size: $font-size-sm;
  color: $text-secondary;
  margin-left: $spacing-xs;
}

.quote-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md $spacing-lg;
  background: $bg-light;
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
}

.btn-primary {
  background: $color-primary;
  color: $text-white;

  &:hover {
    background: $color-primary-dark;
  }
}

@media (max-width: $breakpoint-md) {
  .quote-main {
    grid-template-columns: 1fr;
    gap: $spacing-md;
  }

  .quote-price {
    text-align: left;
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
}
</style>
