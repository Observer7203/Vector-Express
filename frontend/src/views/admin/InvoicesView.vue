<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  FileText,
  DollarSign,
  Clock,
  AlertCircle
} from 'lucide-vue-next'

const iconStrokeWidth = 1.5
const loading = ref(false)

// Placeholder data - API will be implemented later
const invoices = ref([])
const statistics = ref({
  total: 0,
  pending: 0,
  paid: 0,
  overdue: 0,
  totalAmount: 0
})

onMounted(() => {
  // TODO: Fetch invoices from API
  loading.value = false
})

function formatPrice(amount, currency = 'USD') {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: currency
  }).format(amount || 0)
}
</script>

<template>
  <div class="invoices-page">
    <div class="page-header">
      <div>
        <h1>Счета</h1>
        <p class="subtitle">Управление счетами и платежами</p>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon total">
          <FileText :size="20" :stroke-width="iconStrokeWidth" />
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ statistics.total }}</span>
          <span class="stat-label">Всего счетов</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon pending">
          <Clock :size="20" :stroke-width="iconStrokeWidth" />
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ statistics.pending }}</span>
          <span class="stat-label">Ожидают оплаты</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon success">
          <DollarSign :size="20" :stroke-width="iconStrokeWidth" />
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ statistics.paid }}</span>
          <span class="stat-label">Оплачено</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon danger">
          <AlertCircle :size="20" :stroke-width="iconStrokeWidth" />
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ statistics.overdue }}</span>
          <span class="stat-label">Просрочено</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div class="empty-state">
      <FileText :size="64" :stroke-width="1" />
      <h2>Счета пока отсутствуют</h2>
      <p>Счета будут создаваться автоматически при оформлении заказов.</p>
      <p class="note">Функционал управления счетами находится в разработке.</p>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.invoices-page {
  h1 {
    font-size: $font-size-2xl;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
  }

  .subtitle {
    font-size: $font-size-sm;
    color: $text-secondary;
    margin: $spacing-xs 0 0;
  }
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: $spacing-lg;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $spacing-md;
  margin-bottom: $spacing-lg;
}

.stat-card {
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-xl;
  padding: $spacing-md;
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: $radius-lg;
  display: flex;
  align-items: center;
  justify-content: center;

  &.total {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.pending {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
  }

  &.success {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.danger {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: $font-size-2xl;
  font-weight: 700;
  color: $text-primary;
}

.stat-label {
  font-size: $font-size-xs;
  color: $text-secondary;
}

.empty-state {
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-xl;
  padding: $spacing-3xl;
  text-align: center;
  color: $text-muted;

  svg {
    margin-bottom: $spacing-md;
    opacity: 0.5;
  }

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-sm;
  }

  p {
    font-size: $font-size-sm;
    margin: 0;
  }

  .note {
    margin-top: $spacing-md;
    padding: $spacing-sm $spacing-md;
    background: rgba($color-warning, 0.1);
    border-radius: $radius-md;
    color: $color-warning;
    display: inline-block;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
