<script setup>
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useShipmentsStore } from '@/stores/shipments'
import { ArrowLeft, Plus, ArrowRight, FileText, Truck, Scale, Calendar } from 'lucide-vue-next'

const iconStrokeWidth = 1.2

const shipmentsStore = useShipmentsStore()

onMounted(() => {
  shipmentsStore.fetchShipments()
})

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('ru-RU')
}

const statusLabels = {
  draft: 'Черновик',
  calculating: 'Расчет...',
  quoted: 'Есть предложения',
  ordered: 'Заказано',
  expired: 'Истекло'
}

const transportLabels = {
  air: 'Авиа',
  sea: 'Морской',
  rail: 'ЖД',
  road: 'Авто',
  multimodal: 'Мультимодальный'
}
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
            <h1>Мои заявки</h1>
          </div>
          <RouterLink to="/shipments/new" class="btn btn-primary">
            <Plus :size="18" :stroke-width="iconStrokeWidth" />
            Новая заявка
          </RouterLink>
        </div>
      </div>
    </header>

    <main class="page-content">
      <div class="container">
        <div v-if="shipmentsStore.loading" class="loading">
          <div class="spinner"></div>
          <span>Загрузка заявок...</span>
        </div>

        <div v-else-if="shipmentsStore.shipments.length === 0" class="empty-state">
          <div class="empty-icon">
            <FileText :size="32" :stroke-width="iconStrokeWidth" />
          </div>
          <h3>Нет заявок</h3>
          <p>Создайте первую заявку для расчета стоимости доставки</p>
          <RouterLink to="/shipments/new" class="btn btn-primary">Создать заявку</RouterLink>
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
                  <span class="point-label">Откуда</span>
                  <span class="point-value">{{ shipment.origin_city }}, {{ shipment.origin_country }}</span>
                </div>
                <div class="route-arrow">
                  <ArrowRight :size="18" :stroke-width="iconStrokeWidth" />
                </div>
                <div class="route-point">
                  <span class="point-label">Куда</span>
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
                <span>{{ transportLabels[shipment.transport_type] || 'Любой' }}</span>
              </div>
              <div class="detail-item">
                <Scale :size="16" :stroke-width="iconStrokeWidth" />
                <span>{{ shipment.total_weight || '-' }} кг</span>
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
                  Смотреть предложения
                </RouterLink>
                <RouterLink :to="`/shipments/${shipment.id}`" class="btn btn-outline btn-sm">
                  Подробнее
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
</style>
