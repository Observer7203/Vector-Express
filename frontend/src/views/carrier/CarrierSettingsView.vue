<script setup>
import { onMounted, ref, reactive } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/api/client'
import {
  Settings,
  Save,
  Plane,
  Truck,
  Train,
  Ship,
  ToggleLeft,
  ToggleRight,
  Calculator,
  Percent,
  DollarSign,
  AlertCircle,
  CheckCircle
} from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'

const { t } = useI18n()

const iconStrokeWidth = 1.2

const loading = ref(true)
const saving = ref(false)
const message = ref({ type: '', text: '' })

const settings = reactive({
  // DIM-фактор
  dim_factor: 5000,

  // Минимальный тариф
  min_rate: 30.00,

  // Страховка (процент от стоимости груза)
  insurance_rate: 0.5,

  // Валюта по умолчанию
  default_currency: 'USD',

  // Типы перевозок
  transport_types: {
    air: true,
    road: true,
    rail: true,
    sea: false
  },

  // Максимальные габариты
  max_weight: 1000,      // кг
  max_length: 300,       // см
  max_width: 200,        // см
  max_height: 200,       // см

  // Время работы
  working_hours_start: '09:00',
  working_hours_end: '18:00',
  working_days: ['mon', 'tue', 'wed', 'thu', 'fri'],

  // Уведомления
  notifications: {
    new_orders: true,
    status_updates: true,
    payments: true
  }
})

const currencies = [
  { value: 'USD', label: 'USD ($)' },
  { value: 'EUR', label: 'EUR (€)' },
  { value: 'KZT', label: 'KZT (₸)' },
  { value: 'RUB', label: 'RUB (₽)' }
]

const weekdays = [
  { value: 'mon', label: t('carrierSettings.weekdays.mon') },
  { value: 'tue', label: t('carrierSettings.weekdays.tue') },
  { value: 'wed', label: t('carrierSettings.weekdays.wed') },
  { value: 'thu', label: t('carrierSettings.weekdays.thu') },
  { value: 'fri', label: t('carrierSettings.weekdays.fri') },
  { value: 'sat', label: t('carrierSettings.weekdays.sat') },
  { value: 'sun', label: t('carrierSettings.weekdays.sun') }
]

onMounted(async () => {
  loading.value = true
  try {
    const response = await api.get('/carrier/pricing-rule')
    if (response.data) {
      Object.assign(settings, response.data)
    }
  } catch (error) {
    console.error('Failed to load settings:', error)
    // Use default values
  } finally {
    loading.value = false
  }
})

async function saveSettings() {
  saving.value = true
  message.value = { type: '', text: '' }

  try {
    await api.put('/carrier/pricing-rule', settings)
    message.value = { type: 'success', text: t('carrierSettings.messages.saveSuccess') }

    setTimeout(() => {
      message.value = { type: '', text: '' }
    }, 3000)
  } catch (error) {
    console.error('Failed to save settings:', error)
    message.value = { type: 'error', text: t('carrierSettings.messages.saveError') }
  } finally {
    saving.value = false
  }
}

function toggleTransportType(type) {
  settings.transport_types[type] = !settings.transport_types[type]
}

function toggleWorkingDay(day) {
  const index = settings.working_days.indexOf(day)
  if (index > -1) {
    settings.working_days.splice(index, 1)
  } else {
    settings.working_days.push(day)
  }
}

function toggleNotification(key) {
  settings.notifications[key] = !settings.notifications[key]
}
</script>

<template>
  <div class="carrier-settings">
    <AppHeader />

    <main class="dashboard-main">
      <div class="container">
        <div class="page-header">
          <div class="page-title">
            <h1>{{ t('carrierSettings.title') }}</h1>
            <p class="subtitle">{{ t('carrierSettings.subtitle') }}</p>
          </div>
          <button @click="saveSettings" class="btn btn-primary" :disabled="saving">
            <Save :size="18" :stroke-width="iconStrokeWidth" />
            {{ saving ? t('carrierSettings.saving') : t('common.save') }}
          </button>
        </div>

        <!-- Message -->
        <div v-if="message.text" :class="['message', `message-${message.type}`]">
          <CheckCircle v-if="message.type === 'success'" :size="18" :stroke-width="iconStrokeWidth" />
          <AlertCircle v-else :size="18" :stroke-width="iconStrokeWidth" />
          {{ message.text }}
        </div>

        <div class="settings-grid" v-if="!loading">
          <!-- Pricing Rules -->
          <section class="settings-section">
            <div class="section-header">
              <Calculator :size="20" :stroke-width="iconStrokeWidth" />
              <h2>{{ t('carrierSettings.pricingRules.title') }}</h2>
            </div>
            <div class="section-body">
              <div class="form-group">
                <label>{{ t('carrierSettings.pricingRules.dimFactor') }}</label>
                <div class="input-with-hint">
                  <input type="number" v-model.number="settings.dim_factor" min="1000" max="10000" step="100" />
                  <span class="hint">{{ t('carrierSettings.pricingRules.dimFactorHint') }}</span>
                </div>
              </div>

              <div class="form-group">
                <label>{{ t('carrierSettings.pricingRules.minRate') }}</label>
                <div class="input-group">
                  <span class="input-prefix">$</span>
                  <input type="number" v-model.number="settings.min_rate" min="0" step="0.01" />
                </div>
              </div>

              <div class="form-group">
                <label>{{ t('carrierSettings.pricingRules.insuranceRate') }}</label>
                <div class="input-group">
                  <input type="number" v-model.number="settings.insurance_rate" min="0" max="10" step="0.1" />
                  <span class="input-suffix">%</span>
                </div>
                <span class="hint">{{ t('carrierSettings.pricingRules.insuranceRateHint') }}</span>
              </div>

              <div class="form-group">
                <label>{{ t('carrierSettings.pricingRules.defaultCurrency') }}</label>
                <select v-model="settings.default_currency">
                  <option v-for="currency in currencies" :key="currency.value" :value="currency.value">
                    {{ currency.label }}
                  </option>
                </select>
              </div>
            </div>
          </section>

          <!-- Transport Types -->
          <section class="settings-section">
            <div class="section-header">
              <Truck :size="20" :stroke-width="iconStrokeWidth" />
              <h2>{{ t('carrierSettings.transportTypes.title') }}</h2>
            </div>
            <div class="section-body">
              <p class="section-description">{{ t('carrierSettings.transportTypes.description') }}</p>

              <div class="transport-toggles">
                <div
                  class="transport-toggle"
                  :class="{ active: settings.transport_types.air }"
                  @click="toggleTransportType('air')"
                >
                  <div class="toggle-icon air">
                    <Plane :size="24" :stroke-width="iconStrokeWidth" />
                  </div>
                  <div class="toggle-info">
                    <span class="toggle-label">{{ t('carrierSettings.transportTypes.air') }}</span>
                    <span class="toggle-desc">{{ t('carrierSettings.transportTypes.airDesc') }}</span>
                  </div>
                  <component
                    :is="settings.transport_types.air ? ToggleRight : ToggleLeft"
                    :size="32"
                    :stroke-width="iconStrokeWidth"
                    :class="['toggle-switch', { active: settings.transport_types.air }]"
                  />
                </div>

                <div
                  class="transport-toggle"
                  :class="{ active: settings.transport_types.road }"
                  @click="toggleTransportType('road')"
                >
                  <div class="toggle-icon road">
                    <Truck :size="24" :stroke-width="iconStrokeWidth" />
                  </div>
                  <div class="toggle-info">
                    <span class="toggle-label">{{ t('carrierSettings.transportTypes.road') }}</span>
                    <span class="toggle-desc">{{ t('carrierSettings.transportTypes.roadDesc') }}</span>
                  </div>
                  <component
                    :is="settings.transport_types.road ? ToggleRight : ToggleLeft"
                    :size="32"
                    :stroke-width="iconStrokeWidth"
                    :class="['toggle-switch', { active: settings.transport_types.road }]"
                  />
                </div>

                <div
                  class="transport-toggle"
                  :class="{ active: settings.transport_types.rail }"
                  @click="toggleTransportType('rail')"
                >
                  <div class="toggle-icon rail">
                    <Train :size="24" :stroke-width="iconStrokeWidth" />
                  </div>
                  <div class="toggle-info">
                    <span class="toggle-label">{{ t('carrierSettings.transportTypes.rail') }}</span>
                    <span class="toggle-desc">{{ t('carrierSettings.transportTypes.railDesc') }}</span>
                  </div>
                  <component
                    :is="settings.transport_types.rail ? ToggleRight : ToggleLeft"
                    :size="32"
                    :stroke-width="iconStrokeWidth"
                    :class="['toggle-switch', { active: settings.transport_types.rail }]"
                  />
                </div>

                <div
                  class="transport-toggle"
                  :class="{ active: settings.transport_types.sea }"
                  @click="toggleTransportType('sea')"
                >
                  <div class="toggle-icon sea">
                    <Ship :size="24" :stroke-width="iconStrokeWidth" />
                  </div>
                  <div class="toggle-info">
                    <span class="toggle-label">{{ t('carrierSettings.transportTypes.sea') }}</span>
                    <span class="toggle-desc">{{ t('carrierSettings.transportTypes.seaDesc') }}</span>
                  </div>
                  <component
                    :is="settings.transport_types.sea ? ToggleRight : ToggleLeft"
                    :size="32"
                    :stroke-width="iconStrokeWidth"
                    :class="['toggle-switch', { active: settings.transport_types.sea }]"
                  />
                </div>
              </div>
            </div>
          </section>

          <!-- Cargo Limits -->
          <section class="settings-section">
            <div class="section-header">
              <DollarSign :size="20" :stroke-width="iconStrokeWidth" />
              <h2>{{ t('carrierSettings.cargoLimits.title') }}</h2>
            </div>
            <div class="section-body">
              <p class="section-description">{{ t('carrierSettings.cargoLimits.description') }}</p>

              <div class="limits-grid">
                <div class="form-group">
                  <label>{{ t('carrierSettings.cargoLimits.maxWeight') }}</label>
                  <div class="input-group">
                    <input type="number" v-model.number="settings.max_weight" min="1" step="1" />
                    <span class="input-suffix">{{ t('shipment.kg') }}</span>
                  </div>
                </div>

                <div class="form-group">
                  <label>{{ t('carrierSettings.cargoLimits.maxLength') }}</label>
                  <div class="input-group">
                    <input type="number" v-model.number="settings.max_length" min="1" step="1" />
                    <span class="input-suffix">{{ t('shipment.cm') }}</span>
                  </div>
                </div>

                <div class="form-group">
                  <label>{{ t('carrierSettings.cargoLimits.maxWidth') }}</label>
                  <div class="input-group">
                    <input type="number" v-model.number="settings.max_width" min="1" step="1" />
                    <span class="input-suffix">{{ t('shipment.cm') }}</span>
                  </div>
                </div>

                <div class="form-group">
                  <label>{{ t('carrierSettings.cargoLimits.maxHeight') }}</label>
                  <div class="input-group">
                    <input type="number" v-model.number="settings.max_height" min="1" step="1" />
                    <span class="input-suffix">{{ t('shipment.cm') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Working Hours -->
          <section class="settings-section">
            <div class="section-header">
              <Settings :size="20" :stroke-width="iconStrokeWidth" />
              <h2>{{ t('carrierSettings.workingHours.title') }}</h2>
            </div>
            <div class="section-body">
              <div class="time-inputs">
                <div class="form-group">
                  <label>{{ t('carrierSettings.workingHours.start') }}</label>
                  <input type="time" v-model="settings.working_hours_start" />
                </div>
                <span class="time-separator">—</span>
                <div class="form-group">
                  <label>{{ t('carrierSettings.workingHours.end') }}</label>
                  <input type="time" v-model="settings.working_hours_end" />
                </div>
              </div>

              <div class="form-group">
                <label>{{ t('carrierSettings.workingHours.workingDays') }}</label>
                <div class="weekdays">
                  <button
                    v-for="day in weekdays"
                    :key="day.value"
                    :class="['weekday-btn', { active: settings.working_days.includes(day.value) }]"
                    @click="toggleWorkingDay(day.value)"
                  >
                    {{ day.label }}
                  </button>
                </div>
              </div>
            </div>
          </section>

          <!-- Notifications -->
          <section class="settings-section">
            <div class="section-header">
              <AlertCircle :size="20" :stroke-width="iconStrokeWidth" />
              <h2>{{ t('carrierSettings.notifications.title') }}</h2>
            </div>
            <div class="section-body">
              <div class="notification-toggles">
                <div
                  class="notification-toggle"
                  @click="toggleNotification('new_orders')"
                >
                  <div class="toggle-info">
                    <span class="toggle-label">{{ t('carrierSettings.notifications.newOrders') }}</span>
                    <span class="toggle-desc">{{ t('carrierSettings.notifications.newOrdersDesc') }}</span>
                  </div>
                  <component
                    :is="settings.notifications.new_orders ? ToggleRight : ToggleLeft"
                    :size="32"
                    :stroke-width="iconStrokeWidth"
                    :class="['toggle-switch', { active: settings.notifications.new_orders }]"
                  />
                </div>

                <div
                  class="notification-toggle"
                  @click="toggleNotification('status_updates')"
                >
                  <div class="toggle-info">
                    <span class="toggle-label">{{ t('carrierSettings.notifications.statusUpdates') }}</span>
                    <span class="toggle-desc">{{ t('carrierSettings.notifications.statusUpdatesDesc') }}</span>
                  </div>
                  <component
                    :is="settings.notifications.status_updates ? ToggleRight : ToggleLeft"
                    :size="32"
                    :stroke-width="iconStrokeWidth"
                    :class="['toggle-switch', { active: settings.notifications.status_updates }]"
                  />
                </div>

                <div
                  class="notification-toggle"
                  @click="toggleNotification('payments')"
                >
                  <div class="toggle-info">
                    <span class="toggle-label">{{ t('carrierSettings.notifications.payments') }}</span>
                    <span class="toggle-desc">{{ t('carrierSettings.notifications.paymentsDesc') }}</span>
                  </div>
                  <component
                    :is="settings.notifications.payments ? ToggleRight : ToggleLeft"
                    :size="32"
                    :stroke-width="iconStrokeWidth"
                    :class="['toggle-switch', { active: settings.notifications.payments }]"
                  />
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- Loading -->
        <div v-else class="loading-state">
          <div class="spinner"></div>
          <span>{{ t('carrierSettings.loadingSettings') }}</span>
        </div>
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.carrier-settings {
  min-height: 100vh;
  background: $bg-light;
}

.container {
  max-width: $container-max-width;
  margin: 0 auto;
  padding: 0 $container-padding;
}

.dashboard-main {
  padding: $spacing-xl 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.message {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-md;
  border-radius: $radius-md;
  margin-bottom: $spacing-lg;
  font-size: $font-size-sm;
  font-weight: 500;

  &.message-success {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.message-error {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: $spacing-lg;
}

.settings-section {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  overflow: hidden;
}

.section-header {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-md $spacing-lg;
  border-bottom: 1px solid $border-color;
  background: $bg-light;

  svg {
    color: $color-primary;
  }

  h2 {
    font-size: $font-size-base;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
  }
}

.section-body {
  padding: $spacing-lg;
}

.section-description {
  color: $text-secondary;
  font-size: $font-size-sm;
  margin: 0 0 $spacing-md;
}

.form-group {
  margin-bottom: $spacing-md;

  &:last-child {
    margin-bottom: 0;
  }

  label {
    display: block;
    font-size: $font-size-sm;
    font-weight: 500;
    color: $text-primary;
    margin-bottom: $spacing-xs;
  }

  input, select {
    width: 100%;
    padding: $spacing-sm $spacing-md;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    transition: all $transition-fast;

    &:focus {
      outline: none;
      border-color: $color-primary;
      box-shadow: 0 0 0 3px rgba($color-primary, 0.1);
    }
  }

  .hint {
    display: block;
    font-size: $font-size-xs;
    color: $text-muted;
    margin-top: $spacing-xs;
  }
}

.input-group {
  display: flex;
  align-items: center;

  input {
    flex: 1;
  }

  .input-prefix {
    padding: $spacing-sm $spacing-md;
    background: $bg-light;
    border: 1px solid $border-color;
    border-right: none;
    border-radius: $radius-md 0 0 $radius-md;
    color: $text-secondary;
    font-size: $font-size-sm;
  }

  .input-suffix {
    padding: $spacing-sm $spacing-md;
    background: $bg-light;
    border: 1px solid $border-color;
    border-left: none;
    border-radius: 0 $radius-md $radius-md 0;
    color: $text-secondary;
    font-size: $font-size-sm;
  }

  input {
    border-radius: 0;

    &:first-child {
      border-radius: $radius-md 0 0 $radius-md;
    }

    &:last-child {
      border-radius: 0 $radius-md $radius-md 0;
    }
  }
}

.input-with-hint {
  input {
    width: 100%;
  }
}

.transport-toggles {
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
}

.transport-toggle {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-md;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  cursor: pointer;
  transition: all $transition-base;

  &:hover {
    background: $bg-hover;
  }

  &.active {
    border-color: $color-primary;
    background: rgba($color-primary, 0.05);
  }
}

.toggle-icon {
  width: 48px;
  height: 48px;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;

  &.air {
    background: rgba($color-primary, 0.1);
    svg { color: $color-primary; }
  }

  &.road {
    background: rgba($color-success, 0.1);
    svg { color: $color-success; }
  }

  &.rail {
    background: rgba($color-warning, 0.1);
    svg { color: $color-warning; }
  }

  &.sea {
    background: rgba($color-secondary, 0.1);
    svg { color: $color-secondary; }
  }
}

.toggle-info {
  flex: 1;

  .toggle-label {
    display: block;
    font-weight: 500;
    color: $text-primary;
  }

  .toggle-desc {
    display: block;
    font-size: $font-size-sm;
    color: $text-secondary;
  }
}

.toggle-switch {
  color: $text-muted;
  transition: color $transition-fast;

  &.active {
    color: $color-primary;
  }
}

.limits-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-md;
}

.time-inputs {
  display: flex;
  align-items: flex-end;
  gap: $spacing-md;
  margin-bottom: $spacing-md;

  .form-group {
    flex: 1;
    margin-bottom: 0;
  }
}

.time-separator {
  color: $text-muted;
  padding-bottom: $spacing-sm;
}

.weekdays {
  display: flex;
  gap: $spacing-xs;
}

.weekday-btn {
  width: 40px;
  height: 40px;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  background: $bg-white;
  color: $text-secondary;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover {
    background: $bg-hover;
  }

  &.active {
    background: $color-primary;
    border-color: $color-primary;
    color: $text-white;
  }
}

.notification-toggles {
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
}

.notification-toggle {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-md;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  cursor: pointer;
  transition: all $transition-base;

  &:hover {
    background: $bg-hover;
  }
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: $spacing-2xl;
  color: $text-secondary;
  gap: $spacing-md;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid $border-color;
  border-top-color: $color-primary;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: $breakpoint-md) {
  .settings-grid {
    grid-template-columns: 1fr;
  }

  .limits-grid {
    grid-template-columns: 1fr;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: $spacing-md;
  }
}
</style>
