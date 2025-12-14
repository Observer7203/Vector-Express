<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { quotesApi } from '@/api'
import { useAuthStore } from '@/stores/auth'
import { ArrowLeft, Package, MapPin, User, Phone, Mail, Calendar, Clock, FileText, Check, AlertCircle } from 'lucide-vue-next'

const { t } = useI18n()

const iconStrokeWidth = 1.2

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const quote = ref(null)
const loading = ref(true)
const submitting = ref(false)
const error = ref(null)

const form = ref({
  contact_name: '',
  contact_phone: '',
  contact_email: '',
  pickup_contact_name: '',
  pickup_contact_phone: '',
  pickup_address: '',
  pickup_date: '',
  pickup_time_from: '',
  pickup_time_to: '',
  delivery_contact_name: '',
  delivery_contact_phone: '',
  delivery_address: '',
  notes: ''
})

const useContactForPickup = ref(true)
const useContactForDelivery = ref(true)

onMounted(async () => {
  try {
    const response = await quotesApi.get(route.params.quoteId)
    quote.value = response.quote

    // Pre-fill from user profile
    if (authStore.user) {
      form.value.contact_name = authStore.user.name || ''
      form.value.contact_phone = authStore.user.phone || ''
      form.value.contact_email = authStore.user.email || ''
    }

    // Pre-fill addresses from shipment
    if (quote.value.shipment) {
      form.value.pickup_address = quote.value.shipment.origin_address || ''
      form.value.delivery_address = quote.value.shipment.destination_address || ''
      form.value.pickup_date = quote.value.shipment.pickup_date || ''
    }
  } catch (e) {
    error.value = e.response?.data?.message || t('orderCreate.errors.loadQuote')
  } finally {
    loading.value = false
  }
})

const transportTypeLabels = computed(() => ({
  air: t('adminCarriers.transportTypes.air'),
  sea: t('adminCarriers.transportTypes.sea'),
  rail: t('adminCarriers.transportTypes.rail'),
  road: t('adminCarriers.transportTypes.road')
}))

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU')
}

function formatPrice(value) {
  if (!value && value !== 0) return '-'
  return Number(value).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const isFormValid = computed(() => {
  return form.value.contact_name &&
         form.value.contact_phone &&
         form.value.contact_email
})

async function submitOrder() {
  if (!isFormValid.value || submitting.value) return

  submitting.value = true
  error.value = null

  try {
    const orderData = {
      contact_name: form.value.contact_name,
      contact_phone: form.value.contact_phone,
      contact_email: form.value.contact_email,
      pickup_contact_name: useContactForPickup.value ? form.value.contact_name : form.value.pickup_contact_name,
      pickup_contact_phone: useContactForPickup.value ? form.value.contact_phone : form.value.pickup_contact_phone,
      pickup_address: form.value.pickup_address,
      pickup_date: form.value.pickup_date || null,
      pickup_time_from: form.value.pickup_time_from || null,
      pickup_time_to: form.value.pickup_time_to || null,
      delivery_contact_name: useContactForDelivery.value ? form.value.contact_name : form.value.delivery_contact_name,
      delivery_contact_phone: useContactForDelivery.value ? form.value.contact_phone : form.value.delivery_contact_phone,
      delivery_address: form.value.delivery_address,
      notes: form.value.notes || null
    }

    const response = await quotesApi.select(route.params.quoteId, orderData)
    router.push(`/orders/${response.order.id}`)
  } catch (e) {
    error.value = e.response?.data?.message || t('orderCreate.errors.createOrder')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <div class="header-content">
          <div class="header-left">
            <button @click="router.back()" class="back-link">
              <ArrowLeft :size="20" :stroke-width="iconStrokeWidth" />
            </button>
            <div>
              <h1>{{ t('orderCreate.title') }}</h1>
              <p v-if="quote?.shipment" class="route-summary">
                {{ quote.shipment.origin_city }}, {{ quote.shipment.origin_country }}
                <span class="arrow">→</span>
                {{ quote.shipment.destination_city }}, {{ quote.shipment.destination_country }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="page-content">
      <div class="container">
        <div v-if="loading" class="loading">
          <div class="spinner"></div>
          <span>{{ t('common.loading') }}</span>
        </div>

        <div v-else-if="error && !quote" class="error-state">
          <AlertCircle :size="48" :stroke-width="iconStrokeWidth" />
          <h3>{{ t('orderCreate.errors.loadingError') }}</h3>
          <p>{{ error }}</p>
          <button @click="router.back()" class="btn btn-secondary">{{ t('common.back') }}</button>
        </div>

        <template v-else>
          <div class="order-layout">
            <!-- Form Section -->
            <div class="form-section">
              <div v-if="error" class="error-alert">
                <AlertCircle :size="18" :stroke-width="iconStrokeWidth" />
                <span>{{ error }}</span>
              </div>

              <!-- Contact Information -->
              <div class="form-card">
                <div class="card-header">
                  <User :size="20" :stroke-width="iconStrokeWidth" />
                  <h3>{{ t('orderCreate.sections.contactInfo') }}</h3>
                </div>
                <div class="form-grid">
                  <div class="form-group">
                    <label>{{ t('orderCreate.form.fullName') }} <span class="required">*</span></label>
                    <input
                      v-model="form.contact_name"
                      type="text"
                      :placeholder="t('orderCreate.form.fullNamePlaceholder')"
                      required
                    />
                  </div>
                  <div class="form-group">
                    <label>{{ t('orderCreate.form.phone') }} <span class="required">*</span></label>
                    <input
                      v-model="form.contact_phone"
                      type="tel"
                      :placeholder="t('orderCreate.form.phonePlaceholder')"
                      required
                    />
                  </div>
                  <div class="form-group full-width">
                    <label>{{ t('orderCreate.form.email') }} <span class="required">*</span></label>
                    <input
                      v-model="form.contact_email"
                      type="email"
                      :placeholder="t('orderCreate.form.emailPlaceholder')"
                      required
                    />
                  </div>
                </div>
              </div>

              <!-- Pickup Information -->
              <div class="form-card">
                <div class="card-header">
                  <MapPin :size="20" :stroke-width="iconStrokeWidth" />
                  <h3>{{ t('orderCreate.sections.pickup') }}</h3>
                </div>

                <div class="checkbox-row">
                  <input
                    type="checkbox"
                    id="use-contact-pickup"
                    v-model="useContactForPickup"
                  />
                  <label for="use-contact-pickup">{{ t('orderCreate.form.useCustomerContact') }}</label>
                </div>

                <div v-if="!useContactForPickup" class="form-grid">
                  <div class="form-group">
                    <label>{{ t('orderCreate.form.contactPerson') }}</label>
                    <input
                      v-model="form.pickup_contact_name"
                      type="text"
                      :placeholder="t('orderCreate.form.pickupContactPlaceholder')"
                    />
                  </div>
                  <div class="form-group">
                    <label>{{ t('orderCreate.form.pickupPhone') }}</label>
                    <input
                      v-model="form.pickup_contact_phone"
                      type="tel"
                      :placeholder="t('orderCreate.form.phonePlaceholder')"
                    />
                  </div>
                </div>

                <div class="form-grid">
                  <div class="form-group full-width">
                    <label>{{ t('orderCreate.form.pickupAddress') }}</label>
                    <input
                      v-model="form.pickup_address"
                      type="text"
                      :placeholder="t('orderCreate.form.pickupAddressPlaceholder')"
                    />
                  </div>
                  <div class="form-group">
                    <label>{{ t('orderCreate.form.pickupDate') }}</label>
                    <input
                      v-model="form.pickup_date"
                      type="date"
                      :min="new Date().toISOString().split('T')[0]"
                    />
                  </div>
                  <div class="form-group time-range">
                    <label>{{ t('orderCreate.form.pickupTime') }}</label>
                    <div class="time-inputs">
                      <input
                        v-model="form.pickup_time_from"
                        type="time"
                        :placeholder="t('orderCreate.form.timeFrom')"
                      />
                      <span>—</span>
                      <input
                        v-model="form.pickup_time_to"
                        type="time"
                        :placeholder="t('orderCreate.form.timeTo')"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Delivery Information -->
              <div class="form-card">
                <div class="card-header">
                  <Package :size="20" :stroke-width="iconStrokeWidth" />
                  <h3>{{ t('orderCreate.sections.delivery') }}</h3>
                </div>

                <div class="checkbox-row">
                  <input
                    type="checkbox"
                    id="use-contact-delivery"
                    v-model="useContactForDelivery"
                  />
                  <label for="use-contact-delivery">{{ t('orderCreate.form.useCustomerContact') }}</label>
                </div>

                <div v-if="!useContactForDelivery" class="form-grid">
                  <div class="form-group">
                    <label>{{ t('orderCreate.form.contactPerson') }}</label>
                    <input
                      v-model="form.delivery_contact_name"
                      type="text"
                      :placeholder="t('orderCreate.form.deliveryContactPlaceholder')"
                    />
                  </div>
                  <div class="form-group">
                    <label>{{ t('orderCreate.form.deliveryPhone') }}</label>
                    <input
                      v-model="form.delivery_contact_phone"
                      type="tel"
                      :placeholder="t('orderCreate.form.phonePlaceholder')"
                    />
                  </div>
                </div>

                <div class="form-grid">
                  <div class="form-group full-width">
                    <label>{{ t('orderCreate.form.deliveryAddress') }}</label>
                    <input
                      v-model="form.delivery_address"
                      type="text"
                      :placeholder="t('orderCreate.form.deliveryAddressPlaceholder')"
                    />
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div class="form-card">
                <div class="card-header">
                  <FileText :size="20" :stroke-width="iconStrokeWidth" />
                  <h3>{{ t('orderCreate.sections.additional') }}</h3>
                </div>
                <div class="form-group">
                  <label>{{ t('orderCreate.form.notes') }}</label>
                  <textarea
                    v-model="form.notes"
                    rows="3"
                    :placeholder="t('orderCreate.form.notesPlaceholder')"
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
              <div class="summary-card sticky">
                <h3>{{ t('orderCreate.summary.title') }}</h3>

                <!-- Carrier Info -->
                <div class="carrier-info" v-if="quote?.carrier">
                  <div class="carrier-logo">
                    <span>{{ quote.carrier.company?.name?.charAt(0) || '?' }}</span>
                  </div>
                  <div class="carrier-details">
                    <span class="carrier-name">{{ quote.carrier.company?.name || t('orderCreate.summary.carrier') }}</span>
                    <span class="transport-type" :class="`transport-${quote.transport_type}`">
                      {{ transportTypeLabels.value[quote.transport_type] || quote.transport_type }}
                    </span>
                  </div>
                </div>

                <!-- Route -->
                <div class="summary-row route-row" v-if="quote?.shipment">
                  <div class="route-point">
                    <span class="route-label">{{ t('orderCreate.summary.from') }}</span>
                    <span class="route-value">{{ quote.shipment.origin_city }}, {{ quote.shipment.origin_country }}</span>
                  </div>
                  <div class="route-arrow">→</div>
                  <div class="route-point">
                    <span class="route-label">{{ t('orderCreate.summary.to') }}</span>
                    <span class="route-value">{{ quote.shipment.destination_city }}, {{ quote.shipment.destination_country }}</span>
                  </div>
                </div>

                <!-- Details -->
                <div class="summary-details">
                  <div class="detail-row">
                    <span class="label">{{ t('orderCreate.summary.billableWeight') }}</span>
                    <span class="value">{{ quote?.billable_weight || quote?.shipment?.total_weight }} {{ t('shipment.kg') }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="label">{{ t('orderCreate.summary.deliveryTime') }}</span>
                    <span class="value">
                      {{ quote?.delivery_days_min && quote?.delivery_days_min !== quote?.delivery_days
                         ? `${quote.delivery_days_min}-${quote.delivery_days}`
                         : quote?.delivery_days }} {{ t('orderCreate.summary.days') }}
                    </span>
                  </div>
                  <div class="detail-row" v-if="quote?.estimated_delivery_date">
                    <span class="label">{{ t('orderCreate.summary.estimatedDate') }}</span>
                    <span class="value">{{ formatDate(quote.estimated_delivery_date) }}</span>
                  </div>
                </div>

                <!-- Services -->
                <div class="services-list" v-if="quote?.services_included?.length">
                  <span class="services-label">{{ t('orderCreate.summary.included') }}</span>
                  <div class="services-tags">
                    <span v-for="service in quote.services_included" :key="service" class="service-tag">
                      <Check :size="14" :stroke-width="iconStrokeWidth" />
                      {{ service === 'door_pickup' ? t('orderCreate.services.pickup') :
                         service === 'door_delivery' ? t('orderCreate.services.delivery') :
                         service === 'customs_clearance' ? t('orderCreate.services.customs') :
                         service === 'insurance' ? t('orderCreate.services.insurance') : service }}
                    </span>
                  </div>
                </div>

                <!-- Price -->
                <div class="price-section">
                  <div class="price-row">
                    <span>{{ t('orderCreate.summary.baseRate') }}</span>
                    <span>{{ formatPrice(quote?.base_rate) }} {{ quote?.currency }}</span>
                  </div>
                  <div class="price-row" v-if="quote?.surcharges?.total > 0">
                    <span>{{ t('orderCreate.summary.surcharges') }}</span>
                    <span>{{ formatPrice(quote.surcharges.total) }} {{ quote?.currency }}</span>
                  </div>
                  <div class="price-row" v-if="quote?.insurance_cost > 0">
                    <span>{{ t('orderCreate.summary.insurance') }}</span>
                    <span>{{ formatPrice(quote.insurance_cost) }} {{ quote?.currency }}</span>
                  </div>
                  <div class="price-total">
                    <span>{{ t('common.total') }}</span>
                    <span class="total-value">{{ formatPrice(quote?.price) }} <small>{{ quote?.currency }}</small></span>
                  </div>
                </div>

                <!-- Valid Until -->
                <div class="valid-until" v-if="quote?.valid_until">
                  {{ t('orderCreate.summary.validUntil') }} {{ formatDate(quote.valid_until) }}
                </div>

                <!-- Submit Button -->
                <button
                  @click="submitOrder"
                  class="btn btn-primary btn-submit"
                  :disabled="!isFormValid || submitting"
                >
                  <span v-if="submitting">{{ t('orderCreate.submitting') }}</span>
                  <span v-else>{{ t('orderCreate.submitButton') }}</span>
                </button>
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
  max-width: 1200px;
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
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all $transition-fast;
  margin-top: 4px;

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

.error-state {
  text-align: center;
  padding: $spacing-2xl;
  color: $text-secondary;

  svg {
    color: $color-danger;
    margin-bottom: $spacing-md;
  }

  h3 {
    color: $text-primary;
    margin-bottom: $spacing-sm;
  }
}

.error-alert {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-md;
  background: rgba($color-danger, 0.1);
  border: 1px solid rgba($color-danger, 0.2);
  border-radius: $radius-md;
  color: $color-danger;
  margin-bottom: $spacing-lg;
}

.order-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: $spacing-xl;
  align-items: start;
}

// Form Section
.form-section {
  display: flex;
  flex-direction: column;
  gap: $spacing-lg;
}

.form-card {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-lg;
}

.card-header {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  margin-bottom: $spacing-lg;
  padding-bottom: $spacing-md;
  border-bottom: 1px solid $border-color;

  svg {
    color: $color-primary;
  }

  h3 {
    font-size: $font-size-base;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
  }
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-md;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;

  &.full-width {
    grid-column: 1 / -1;
  }

  label {
    font-size: $font-size-sm;
    font-weight: 500;
    color: $text-secondary;

    .required {
      color: $color-danger;
    }
  }

  input, textarea, select {
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

    &::placeholder {
      color: $text-muted;
    }
  }

  textarea {
    resize: vertical;
    min-height: 80px;
  }
}

.time-range {
  .time-inputs {
    display: flex;
    align-items: center;
    gap: $spacing-sm;

    input {
      flex: 1;
    }

    span {
      color: $text-muted;
    }
  }
}

.checkbox-row {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  margin-bottom: $spacing-md;

  input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: $color-primary;
  }

  label {
    font-size: $font-size-sm;
    color: $text-secondary;
    cursor: pointer;
  }
}

// Summary Section
.summary-section {
  .sticky {
    position: sticky;
    top: $spacing-lg;
  }
}

.summary-card {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-lg;

  h3 {
    font-size: $font-size-base;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-lg;
    padding-bottom: $spacing-md;
    border-bottom: 1px solid $border-color;
  }
}

.carrier-info {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  margin-bottom: $spacing-lg;
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

.carrier-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.carrier-name {
  font-weight: 600;
  color: $text-primary;
}

.transport-type {
  display: inline-block;
  padding: 2px $spacing-sm;
  border-radius: $radius-md;
  font-size: $font-size-xs;
  background: $bg-light;
  width: fit-content;
}

.transport-air { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.transport-sea { background: rgba(6, 182, 212, 0.1); color: #06b6d4; }
.transport-rail { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.transport-road { background: rgba(34, 197, 94, 0.1); color: #22c55e; }

.route-row {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-md;
  background: $bg-light;
  border-radius: $radius-md;
  margin-bottom: $spacing-lg;
}

.route-point {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.route-label {
  font-size: $font-size-xs;
  color: $text-muted;
}

.route-value {
  font-size: $font-size-sm;
  font-weight: 500;
  color: $text-primary;
}

.route-arrow {
  color: $color-primary;
  font-weight: bold;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
  margin-bottom: $spacing-lg;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: $font-size-sm;

  .label {
    color: $text-secondary;
  }

  .value {
    font-weight: 500;
    color: $text-primary;
  }
}

.services-list {
  margin-bottom: $spacing-lg;
}

.services-label {
  font-size: $font-size-xs;
  color: $text-muted;
  display: block;
  margin-bottom: $spacing-sm;
}

.services-tags {
  display: flex;
  flex-wrap: wrap;
  gap: $spacing-xs;
}

.service-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: $spacing-xs $spacing-sm;
  background: rgba($color-success, 0.1);
  color: $color-success;
  font-size: $font-size-xs;
  font-weight: 500;
  border-radius: $radius-full;

  svg {
    width: 14px;
    height: 14px;
  }
}

.price-section {
  border-top: 1px solid $border-color;
  padding-top: $spacing-md;
  margin-bottom: $spacing-lg;
}

.price-row {
  display: flex;
  justify-content: space-between;
  font-size: $font-size-sm;
  padding: $spacing-xs 0;
  color: $text-secondary;
}

.price-total {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding-top: $spacing-md;
  margin-top: $spacing-sm;
  border-top: 2px solid $border-color;
  font-weight: 600;
  color: $text-primary;

  .total-value {
    font-size: $font-size-xl;
    color: $color-primary;

    small {
      font-size: $font-size-sm;
      font-weight: normal;
    }
  }
}

.valid-until {
  font-size: $font-size-xs;
  color: $text-muted;
  text-align: center;
  margin-bottom: $spacing-md;
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

.btn-secondary {
  background: $bg-light;
  color: $text-primary;
  border: 1px solid $border-color;

  &:hover:not(:disabled) {
    background: $bg-hover;
  }
}

.btn-submit {
  width: 100%;
  padding: $spacing-md;
  font-size: $font-size-base;
}

@media (max-width: $breakpoint-lg) {
  .order-layout {
    grid-template-columns: 1fr;
  }

  .summary-section .sticky {
    position: static;
  }
}

@media (max-width: $breakpoint-md) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-group.full-width {
    grid-column: 1;
  }
}
</style>
