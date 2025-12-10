<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useShipmentsStore } from '@/stores/shipments'
import { ArrowLeft, ArrowRight, Package, MapPin, Truck, Calendar, Shield, FileCheck, Home, Calculator, Eye } from 'lucide-vue-next'

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

const transportTypeLabels = {
  air: 'Авиа',
  sea: 'Морской',
  rail: 'Ж/Д',
  road: 'Автомобильный',
  multimodal: 'Мультимодальный'
}

const cargoTypeLabels = {
  general: 'Обычный',
  dangerous: 'Опасный',
  fragile: 'Хрупкий',
  perishable: 'Скоропортящийся'
}

const statusLabels = {
  draft: 'Черновик',
  calculating: 'Расчет...',
  quoted: 'Есть предложения',
  ordered: 'Заказано',
  expired: 'Истекло'
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU')
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
        <RouterLink to="/shipments" class="back-link">
          <ArrowLeft :size="16" :stroke-width="iconStrokeWidth" />
          Назад к заявкам
        </RouterLink>
        <h1>Заявка #{{ shipment?.id }}</h1>
      </div>
    </header>

    <main class="container">
      <div v-if="shipmentsStore.loading" class="loading">
        <div class="spinner"></div>
        <span>Загрузка...</span>
      </div>

      <div v-else-if="shipment" class="shipment-details">
        <div class="status-bar">
          <span :class="['status', `status-${shipment.status}`]">
            {{ statusLabels[shipment.status] }}
          </span>
          <span class="date">Создано: {{ formatDate(shipment.created_at) }}</span>
        </div>

        <div class="details-grid">
          <div class="detail-card">
            <h3>
              <MapPin :size="18" :stroke-width="iconStrokeWidth" />
              Маршрут
            </h3>
            <div class="route">
              <div class="location">
                <span class="label">Откуда</span>
                <span class="city">{{ shipment.origin_city }}, {{ shipment.origin_country }}</span>
                <span v-if="shipment.origin_address" class="address">{{ shipment.origin_address }}</span>
              </div>
              <div class="arrow">
                <ArrowRight :size="20" :stroke-width="iconStrokeWidth" />
              </div>
              <div class="location">
                <span class="label">Куда</span>
                <span class="city">{{ shipment.destination_city }}, {{ shipment.destination_country }}</span>
                <span v-if="shipment.destination_address" class="address">{{ shipment.destination_address }}</span>
              </div>
            </div>
          </div>

          <div class="detail-card">
            <h3>
              <Package :size="18" :stroke-width="iconStrokeWidth" />
              Параметры груза
            </h3>
            <div class="params-grid">
              <div class="param">
                <span class="label">Общий вес</span>
                <span class="value">{{ totalWeight }} кг</span>
              </div>
              <div class="param">
                <span class="label">Объем</span>
                <span class="value">{{ totalVolume }} м³</span>
              </div>
              <div class="param">
                <span class="label">Мест</span>
                <span class="value">{{ shipment.items?.length || 0 }}</span>
              </div>
              <div class="param">
                <span class="label">Тип груза</span>
                <span class="value">{{ cargoTypeLabels[shipment.cargo_type] || '-' }}</span>
              </div>
            </div>

            <div v-if="shipment.items?.length" class="items-list">
              <h4>Места</h4>
              <div v-for="(item, index) in shipment.items" :key="index" class="item">
                <span class="item-number">{{ index + 1 }}</span>
                <span class="item-dims">
                  {{ item.length }}×{{ item.width }}×{{ item.height }} см
                </span>
                <span class="item-weight">{{ item.weight }} кг</span>
                <span class="item-qty">× {{ item.quantity }}</span>
              </div>
            </div>
          </div>

          <div class="detail-card">
            <h3>
              <Truck :size="18" :stroke-width="iconStrokeWidth" />
              Параметры перевозки
            </h3>
            <div class="params-list">
              <div class="param-row">
                <span class="label">Тип перевозки</span>
                <span class="value">{{ transportTypeLabels[shipment.transport_type] || 'Любой' }}</span>
              </div>
              <div class="param-row">
                <span class="label">Объявленная стоимость</span>
                <span class="value">{{ shipment.declared_value ? `${shipment.declared_value} ${shipment.currency}` : '-' }}</span>
              </div>
              <div class="param-row">
                <span class="label">Дата забора</span>
                <span class="value">{{ formatDate(shipment.pickup_date) }}</span>
              </div>
            </div>
          </div>

          <div class="detail-card">
            <h3>
              <FileCheck :size="18" :stroke-width="iconStrokeWidth" />
              Дополнительные услуги
            </h3>
            <div class="services-list">
              <div :class="['service', { active: shipment.insurance_required }]">
                <Shield :size="16" :stroke-width="iconStrokeWidth" />
                Страхование груза
              </div>
              <div :class="['service', { active: shipment.customs_clearance }]">
                <FileCheck :size="16" :stroke-width="iconStrokeWidth" />
                Таможенное оформление
              </div>
              <div :class="['service', { active: shipment.door_to_door }]">
                <Home :size="16" :stroke-width="iconStrokeWidth" />
                Доставка до двери
              </div>
            </div>
          </div>
        </div>

        <div v-if="shipment.notes" class="notes-card">
          <h3>Комментарий</h3>
          <p>{{ shipment.notes }}</p>
        </div>

        <div class="actions">
          <RouterLink
            v-if="shipment.status === 'quoted'"
            :to="`/shipments/${shipment.id}/quotes`"
            class="btn btn-primary"
          >
            <Eye :size="18" :stroke-width="iconStrokeWidth" />
            Смотреть предложения
          </RouterLink>
          <button
            v-if="['draft', 'expired'].includes(shipment.status)"
            @click="recalculate"
            class="btn btn-primary"
            :disabled="shipmentsStore.calculating"
          >
            <Calculator :size="18" :stroke-width="iconStrokeWidth" />
            {{ shipmentsStore.calculating ? 'Расчет...' : 'Рассчитать стоимость' }}
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
</style>
