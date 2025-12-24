<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useShipmentsStore } from '@/stores/shipments'
import { ArrowLeft, ArrowRight, Check, MapPin, Trash2, Plus, Shield, FileText, Home } from 'lucide-vue-next'
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import AddressAutocomplete from '@/components/AddressAutocomplete.vue'
import MapPicker from '@/components/MapPicker.vue'

const { t } = useI18n()
const iconStrokeWidth = 1.2

const router = useRouter()
const shipmentsStore = useShipmentsStore()

const step = ref(1)
const totalSteps = 4

const form = ref({
  origin_country: '',
  origin_city: '',
  origin_address: '',
  origin_lat: null,
  origin_lng: null,
  origin_postcode: '',
  destination_country: '',
  destination_city: '',
  destination_address: '',
  destination_lat: null,
  destination_lng: null,
  destination_postcode: '',
  transport_type: '',
  cargo_type: 'general',
  packaging_type: '',
  declared_value: null,
  currency: 'USD',
  insurance_required: false,
  customs_clearance: true,
  door_to_door: true,
  pickup_date: '',
  notes: '',
  items: [{ length: null, width: null, height: null, weight: null, quantity: 1, description: '' }]
})

// Address autocomplete state
const originAddress = ref({
  address: '',
  city: '',
  country: '',
  lat: null,
  lng: null
})

const destinationAddress = ref({
  address: '',
  city: '',
  country: '',
  lat: null,
  lng: null
})

// Map picker state
const showOriginMap = ref(false)
const showDestinationMap = ref(false)

// Handle origin address selection
function onOriginSelect(data) {
  form.value.origin_address = data.address || data.displayName || ''
  form.value.origin_city = data.city || ''
  form.value.origin_country = data.country || ''
  form.value.origin_lat = data.lat
  form.value.origin_lng = data.lng
  form.value.origin_postcode = data.postcode || ''
  originAddress.value = data
}

// Handle destination address selection
function onDestinationSelect(data) {
  form.value.destination_address = data.address || data.displayName || ''
  form.value.destination_city = data.city || ''
  form.value.destination_country = data.country || ''
  form.value.destination_lat = data.lat
  form.value.destination_lng = data.lng
  form.value.destination_postcode = data.postcode || ''
  destinationAddress.value = data
}

// Handle map picker selection
function onOriginMapSelect(data) {
  onOriginSelect(data)
  showOriginMap.value = false
}

function onDestinationMapSelect(data) {
  onDestinationSelect(data)
  showDestinationMap.value = false
}

const errors = ref({})
const submitting = ref(false)
const errorMessage = ref('')

const canProceed = computed(() => {
  if (step.value === 1) {
    return (
      form.value.origin_country &&
      form.value.origin_city &&
      form.value.destination_country &&
      form.value.destination_city
    )
  }
  if (step.value === 2) {
    return form.value.items.some((item) => item.weight > 0)
  }
  return true
})

const totalWeight = computed(() => {
  return form.value.items.reduce((sum, i) => sum + (i.weight || 0) * (i.quantity || 1), 0)
})

const stepTitles = computed(() => [
  t('shipmentCreate.steps.direction'),
  t('shipmentCreate.steps.cargo'),
  t('shipmentCreate.steps.services'),
  t('shipmentCreate.steps.review')
])

function addItem() {
  form.value.items.push({
    length: null,
    width: null,
    height: null,
    weight: null,
    quantity: 1,
    description: ''
  })
}

function removeItem(index) {
  if (form.value.items.length > 1) {
    form.value.items.splice(index, 1)
  }
}

function nextStep() {
  if (step.value < totalSteps) {
    step.value++
  }
}

function prevStep() {
  if (step.value > 1) {
    step.value--
  }
}

async function handleSubmit() {
  errors.value = {}
  errorMessage.value = ''
  submitting.value = true

  try {
    // Prepare data - ensure items have proper values
    const submitData = {
      ...form.value,
      items: form.value.items.map(item => ({
        length: item.length || 0,
        width: item.width || 0,
        height: item.height || 0,
        weight: item.weight || 0,
        quantity: item.quantity || 1,
        description: item.description || ''
      }))
    }

    const shipment = await shipmentsStore.createShipment(submitData)
    await shipmentsStore.calculateQuotes(shipment.id)
    router.push(`/shipments/${shipment.id}/quotes`)
  } catch (e) {
    console.error('Submit error:', e)
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
    }
    errorMessage.value = e.response?.data?.message || t('shipmentCreate.errors.createError')
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
            <RouterLink to="/shipments" class="back-link">
              <ArrowLeft :size="20" :stroke-width="iconStrokeWidth" />
            </RouterLink>
            <h1>{{ t('shipmentCreate.title') }}</h1>
          </div>
          <div class="header-right">
            <ThemeToggle />
            <LanguageSwitcher />
          </div>
        </div>

        <div class="stepper">
          <div
            v-for="s in totalSteps"
            :key="s"
            :class="['step', { active: s === step, done: s < step }]"
          >
            <div class="step-number">
              <Check v-if="s < step" :size="18" :stroke-width="iconStrokeWidth" />
              <span v-else>{{ s }}</span>
            </div>
            <span class="step-title">{{ stepTitles[s - 1] }}</span>
          </div>
        </div>
      </div>
    </header>

    <main class="page-content">
      <div class="container">
        <form @submit.prevent="handleSubmit" class="shipment-form">
          <!-- Step 1: Route -->
          <div v-if="step === 1" class="form-step">
            <h2>{{ t('shipmentCreate.step1.title') }}</h2>
            <p class="step-desc">{{ t('shipmentCreate.step1.subtitle') }}</p>

            <div class="route-form">
              <div class="route-section">
                <div class="section-header">
                  <div class="section-icon">
                    <MapPin :size="18" :stroke-width="iconStrokeWidth" />
                  </div>
                  <h3>{{ t('shipmentCreate.step1.from') }}</h3>
                </div>

                <!-- Origin Address with Autocomplete -->
                <div class="form-group">
                  <AddressAutocomplete
                    v-model="originAddress"
                    :label="t('shipmentCreate.step1.fullAddress', 'Full Address')"
                    :placeholder="t('shipmentCreate.step1.addressPlaceholderFull', 'Start typing address...')"
                    :required="true"
                    @select="onOriginSelect"
                    @open-map="showOriginMap = true"
                  />
                </div>

                <!-- Manual override fields (collapsed by default, show if needed) -->
                <div v-if="form.origin_city || form.origin_country" class="address-details">
                  <div class="detail-row">
                    <span class="detail-label">{{ t('shipmentCreate.step1.city') }}:</span>
                    <span class="detail-value">{{ form.origin_city || '-' }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">{{ t('shipmentCreate.step1.country') }}:</span>
                    <span class="detail-value">{{ form.origin_country || '-' }}</span>
                  </div>
                </div>
              </div>

              <div class="route-arrow">
                <ArrowRight :size="24" :stroke-width="iconStrokeWidth" />
              </div>

              <div class="route-section">
                <div class="section-header">
                  <div class="section-icon destination">
                    <MapPin :size="18" :stroke-width="iconStrokeWidth" />
                  </div>
                  <h3>{{ t('shipmentCreate.step1.to') }}</h3>
                </div>

                <!-- Destination Address with Autocomplete -->
                <div class="form-group">
                  <AddressAutocomplete
                    v-model="destinationAddress"
                    :label="t('shipmentCreate.step1.fullAddress', 'Full Address')"
                    :placeholder="t('shipmentCreate.step1.addressPlaceholderFull', 'Start typing address...')"
                    :required="true"
                    @select="onDestinationSelect"
                    @open-map="showDestinationMap = true"
                  />
                </div>

                <!-- Manual override fields -->
                <div v-if="form.destination_city || form.destination_country" class="address-details">
                  <div class="detail-row">
                    <span class="detail-label">{{ t('shipmentCreate.step1.city') }}:</span>
                    <span class="detail-value">{{ form.destination_city || '-' }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">{{ t('shipmentCreate.step1.country') }}:</span>
                    <span class="detail-value">{{ form.destination_country || '-' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Map Picker Modals -->
          <MapPicker
            :show="showOriginMap"
            :initial-lat="form.origin_lat"
            :initial-lng="form.origin_lng"
            @close="showOriginMap = false"
            @select="onOriginMapSelect"
          />

          <MapPicker
            :show="showDestinationMap"
            :initial-lat="form.destination_lat"
            :initial-lng="form.destination_lng"
            @close="showDestinationMap = false"
            @select="onDestinationMapSelect"
          />

          <!-- Step 2: Cargo -->
          <div v-if="step === 2" class="form-step">
            <h2>{{ t('shipmentCreate.step2.title') }}</h2>
            <p class="step-desc">{{ t('shipmentCreate.step2.subtitle') }}</p>

            <div class="cargo-list">
              <div v-for="(item, index) in form.items" :key="index" class="cargo-item">
                <div class="cargo-header">
                  <span class="cargo-title">{{ t('shipmentCreate.step2.place') }} {{ index + 1 }}</span>
                  <button
                    v-if="form.items.length > 1"
                    type="button"
                    @click="removeItem(index)"
                    class="btn-remove"
                  >
                    <Trash2 :size="18" :stroke-width="iconStrokeWidth" />
                  </button>
                </div>

                <div class="cargo-fields">
                  <div class="form-group">
                    <label>{{ t('shipmentCreate.step2.length') }}</label>
                    <input v-model.number="item.length" type="number" min="0" placeholder="100" />
                  </div>
                  <div class="form-group">
                    <label>{{ t('shipmentCreate.step2.width') }}</label>
                    <input v-model.number="item.width" type="number" min="0" placeholder="50" />
                  </div>
                  <div class="form-group">
                    <label>{{ t('shipmentCreate.step2.height') }}</label>
                    <input v-model.number="item.height" type="number" min="0" placeholder="50" />
                  </div>
                  <div class="form-group">
                    <label>{{ t('shipmentCreate.step2.weight') }} *</label>
                    <input v-model.number="item.weight" type="number" min="0" required placeholder="25" />
                  </div>
                  <div class="form-group">
                    <label>{{ t('shipmentCreate.step2.quantity') }}</label>
                    <input v-model.number="item.quantity" type="number" min="1" placeholder="1" />
                  </div>
                </div>

                <div class="form-group">
                  <label>{{ t('shipmentCreate.step2.description') }}</label>
                  <input v-model="item.description" type="text" :placeholder="t('shipmentCreate.step2.descriptionPlaceholder')" />
                </div>
              </div>
            </div>

            <button type="button" @click="addItem" class="btn btn-outline btn-add">
              <Plus :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('shipmentCreate.step2.addPlace') }}
            </button>
          </div>

          <!-- Step 3: Options -->
          <div v-if="step === 3" class="form-step">
            <h2>{{ t('shipmentCreate.step3.title') }}</h2>
            <p class="step-desc">{{ t('shipmentCreate.step3.subtitle') }}</p>

            <div class="options-grid">
              <div class="form-group">
                <label>{{ t('shipmentCreate.step3.transportType') }}</label>
                <select v-model="form.transport_type">
                  <option value="">{{ t('shipmentCreate.step3.transportAny') }}</option>
                  <option value="air">{{ t('shipmentCreate.step3.transportAir') }}</option>
                  <option value="sea">{{ t('shipmentCreate.step3.transportSea') }}</option>
                  <option value="rail">{{ t('shipmentCreate.step3.transportRail') }}</option>
                  <option value="road">{{ t('shipmentCreate.step3.transportRoad') }}</option>
                </select>
              </div>

              <div class="form-group">
                <label>{{ t('shipmentCreate.step3.cargoType') }}</label>
                <select v-model="form.cargo_type">
                  <option value="general">{{ t('shipmentCreate.step3.cargoGeneral') }}</option>
                  <option value="dangerous">{{ t('shipmentCreate.step3.cargoDangerous') }}</option>
                  <option value="fragile">{{ t('shipmentCreate.step3.cargoFragile') }}</option>
                  <option value="perishable">{{ t('shipmentCreate.step3.cargoPerishable') }}</option>
                </select>
              </div>

              <div class="form-group">
                <label>{{ t('shipmentCreate.step3.declaredValue') }}</label>
                <input v-model.number="form.declared_value" type="number" min="0" placeholder="5000" />
              </div>

              <div class="form-group">
                <label>{{ t('shipmentCreate.step3.pickupDate') }}</label>
                <input v-model="form.pickup_date" type="date" />
              </div>
            </div>

            <div class="services-section">
              <h3>{{ t('shipmentCreate.step3.additionalServices') }}</h3>
              <div class="checkbox-group">
                <label class="checkbox-card" :class="{ checked: form.insurance_required }">
                  <input v-model="form.insurance_required" type="checkbox" />
                  <div class="checkbox-content">
                    <Shield :size="24" :stroke-width="iconStrokeWidth" />
                    <span>{{ t('shipmentCreate.step3.insurance') }}</span>
                  </div>
                </label>
                <label class="checkbox-card" :class="{ checked: form.customs_clearance }">
                  <input v-model="form.customs_clearance" type="checkbox" />
                  <div class="checkbox-content">
                    <FileText :size="24" :stroke-width="iconStrokeWidth" />
                    <span>{{ t('shipmentCreate.step3.customs') }}</span>
                  </div>
                </label>
                <label class="checkbox-card" :class="{ checked: form.door_to_door }">
                  <input v-model="form.door_to_door" type="checkbox" />
                  <div class="checkbox-content">
                    <Home :size="24" :stroke-width="iconStrokeWidth" />
                    <span>{{ t('shipmentCreate.step3.doorToDoor') }}</span>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- Step 4: Review -->
          <div v-if="step === 4" class="form-step">
            <h2>{{ t('shipmentCreate.step4.title') }}</h2>
            <p class="step-desc">{{ t('shipmentCreate.step4.subtitle') }}</p>

            <div class="summary-card">
              <div class="summary-section">
                <h3>{{ t('shipmentCreate.step4.route') }}</h3>
                <div class="summary-route">
                  <div class="route-point">
                    <span class="point-label">{{ t('shipmentCreate.step4.from') }}</span>
                    <span class="point-value">{{ form.origin_city }}, {{ form.origin_country }}</span>
                  </div>
                  <div class="route-arrow-small">
                    <ArrowRight :size="18" :stroke-width="iconStrokeWidth" />
                  </div>
                  <div class="route-point">
                    <span class="point-label">{{ t('shipmentCreate.step4.to') }}</span>
                    <span class="point-value">{{ form.destination_city }}, {{ form.destination_country }}</span>
                  </div>
                </div>
              </div>

              <div class="summary-section">
                <h3>{{ t('shipmentCreate.step4.cargo') }}</h3>
                <div class="summary-details">
                  <div class="detail-item">
                    <span class="detail-label">{{ t('shipmentCreate.step4.places') }}</span>
                    <span class="detail-value">{{ form.items.length }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">{{ t('shipmentCreate.step4.totalWeight') }}</span>
                    <span class="detail-value">{{ totalWeight }} {{ t('shipmentCreate.units.kg') }}</span>
                  </div>
                </div>
              </div>

              <div class="summary-section">
                <h3>{{ t('shipmentCreate.step4.services') }}</h3>
                <div class="services-list">
                  <span v-if="form.insurance_required" class="service-tag">{{ t('shipmentCreate.step4.insuranceTag') }}</span>
                  <span v-if="form.customs_clearance" class="service-tag">{{ t('shipmentCreate.step4.customsTag') }}</span>
                  <span v-if="form.door_to_door" class="service-tag">{{ t('shipmentCreate.step4.doorToDoorTag') }}</span>
                  <span v-if="!form.insurance_required && !form.customs_clearance && !form.door_to_door" class="no-services">{{ t('shipmentCreate.step4.noServices') }}</span>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>{{ t('shipmentCreate.step4.comment') }}</label>
              <textarea
                v-model="form.notes"
                rows="3"
                :placeholder="t('shipmentCreate.step4.commentPlaceholder')"
              ></textarea>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="error-message">
            {{ errorMessage }}
          </div>

          <div class="form-actions">
            <button v-if="step > 1" type="button" @click="prevStep" class="btn btn-outline" :disabled="submitting">
              <ArrowLeft :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('shipmentCreate.buttons.back') }}
            </button>
            <button
              v-if="step < totalSteps"
              type="button"
              @click="nextStep"
              class="btn btn-primary"
              :disabled="!canProceed"
            >
              {{ t('shipmentCreate.buttons.next') }}
              <ArrowRight :size="18" :stroke-width="iconStrokeWidth" />
            </button>
            <button
              v-if="step === totalSteps"
              type="submit"
              class="btn btn-primary"
              :disabled="submitting"
            >
              <span v-if="submitting" class="btn-loader"></span>
              {{ submitting ? t('shipmentCreate.buttons.calculating') : t('shipmentCreate.buttons.calculate') }}
            </button>
          </div>
        </form>
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
  padding-bottom: $spacing-lg;
}

.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 $container-padding;
}

.header-content {
  display: flex;
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

.stepper {
  display: flex;
  justify-content: space-between;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $spacing-xs;
  flex: 1;
  position: relative;

  &:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 16px;
    left: calc(50% + 20px);
    width: calc(100% - 40px);
    height: 2px;
    background: $border-color;
  }

  &.done:not(:last-child)::after {
    background: $color-success;
  }
}

.step-number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: $bg-light;
  color: $text-muted;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: $font-size-sm;
  font-weight: 600;
  position: relative;
  z-index: 1;

  svg {
    width: 18px;
    height: 18px;
  }

  .active & {
    background: $color-primary;
    color: $text-white;
  }

  .done & {
    background: $color-success;
    color: $text-white;
  }
}

.step-title {
  font-size: $font-size-xs;
  color: $text-muted;
  text-align: center;

  .active & {
    color: $color-primary;
    font-weight: 500;
  }

  .done & {
    color: $color-success;
  }
}

.page-content {
  padding: $spacing-xl 0;
}

.shipment-form {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-xl;
}

.form-step {
  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-xs;
  }
}

.step-desc {
  color: $text-secondary;
  font-size: $font-size-sm;
  margin: 0 0 $spacing-lg;
}

.route-form {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: $spacing-lg;
  align-items: start;
}

.route-section {
  .section-header {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    margin-bottom: $spacing-md;
  }

  h3 {
    font-size: $font-size-base;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
  }
}

.address-details {
  margin-top: $spacing-sm;
  padding: $spacing-sm $spacing-md;
  background: rgba($color-primary, 0.05);
  border-radius: $radius-md;
  border-left: 3px solid $color-primary;

  .detail-row {
    display: flex;
    gap: $spacing-sm;
    font-size: $font-size-sm;
    padding: 2px 0;

    .detail-label {
      color: $text-muted;
      min-width: 60px;
    }

    .detail-value {
      color: $text-primary;
      font-weight: 500;
    }
  }
}

.section-icon {
  width: 32px;
  height: 32px;
  background: rgba($color-primary, 0.1);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;

  svg {
    width: 18px;
    height: 18px;
    color: $color-primary;
  }

  &.destination {
    background: rgba($color-success, 0.1);
    svg { color: $color-success; }
  }
}

.route-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-top: 48px;

  svg {
    width: 24px;
    height: 24px;
    color: $text-muted;
  }
}

.form-group {
  margin-bottom: $spacing-md;

  label {
    display: block;
    font-size: $font-size-sm;
    font-weight: 500;
    color: $text-primary;
    margin-bottom: $spacing-xs;
  }
}

input, select, textarea {
  width: 100%;
  padding: $spacing-sm $spacing-md;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-base;
  font-family: $font-family;
  transition: border-color $transition-fast, box-shadow $transition-fast;
  background: $bg-white;

  &::placeholder {
    color: $text-muted;
  }

  &:focus {
    outline: none;
    border-color: $color-primary;
    box-shadow: 0 0 0 3px rgba($color-primary, 0.1);
  }
}

textarea {
  resize: vertical;
  min-height: 80px;
}

.cargo-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
  margin-bottom: $spacing-md;
}

.cargo-item {
  border: 1px solid $border-color;
  border-radius: $radius-lg;
  padding: $spacing-lg;
}

.cargo-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-md;
}

.cargo-title {
  font-weight: 600;
  color: $text-primary;
}

.btn-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: none;
  border: none;
  border-radius: $radius-md;
  color: $text-muted;
  cursor: pointer;
  transition: all $transition-fast;

  svg {
    width: 18px;
    height: 18px;
  }

  &:hover {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }
}

.cargo-fields {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: $spacing-md;
  margin-bottom: $spacing-md;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: $spacing-sm $spacing-md;
  border-radius: $radius-md;
  font-size: $font-size-base;
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

  &:hover:not(:disabled) {
    background: $color-primary-dark;
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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

.btn-add {
  width: 100%;
}

.btn-loader {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.options-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: $spacing-md;
  margin-bottom: $spacing-xl;
}

.services-section {
  h3 {
    font-size: $font-size-base;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-md;
  }
}

.checkbox-group {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: $spacing-md;
}

.checkbox-card {
  cursor: pointer;

  input {
    display: none;
  }

  .checkbox-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: $spacing-sm;
    padding: $spacing-lg;
    border: 1px solid $border-color;
    border-radius: $radius-lg;
    transition: all $transition-fast;

    svg {
      width: 24px;
      height: 24px;
      color: $text-muted;
    }

    span {
      font-size: $font-size-sm;
      color: $text-secondary;
      text-align: center;
    }
  }

  &:hover .checkbox-content {
    border-color: $color-primary;
  }

  &.checked .checkbox-content {
    border-color: $color-primary;
    background: rgba($color-primary, 0.05);

    svg {
      color: $color-primary;
    }

    span {
      color: $color-primary;
      font-weight: 500;
    }
  }
}

.summary-card {
  background: $bg-light;
  border-radius: $radius-lg;
  padding: $spacing-lg;
  margin-bottom: $spacing-lg;
}

.summary-section {
  padding-bottom: $spacing-md;
  margin-bottom: $spacing-md;
  border-bottom: 1px solid $border-color;

  &:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  }

  h3 {
    font-size: $font-size-xs;
    font-weight: 600;
    color: $text-muted;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 $spacing-sm;
  }
}

.summary-route {
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
}

.point-value {
  font-size: $font-size-base;
  font-weight: 500;
  color: $text-primary;
}

.route-arrow-small {
  svg {
    width: 18px;
    height: 18px;
    color: $text-muted;
  }
}

.summary-details {
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
  font-size: $font-size-base;
  font-weight: 500;
  color: $text-primary;
}

.services-list {
  display: flex;
  gap: $spacing-sm;
  flex-wrap: wrap;
}

.service-tag {
  display: inline-block;
  padding: $spacing-xs $spacing-sm;
  background: rgba($color-primary, 0.1);
  color: $color-primary;
  font-size: $font-size-xs;
  font-weight: 500;
  border-radius: $radius-full;
}

.no-services {
  color: $text-muted;
  font-size: $font-size-sm;
}

.error-message {
  background: rgba($color-danger, 0.1);
  color: $color-danger;
  padding: $spacing-md;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  margin-top: $spacing-md;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: $spacing-md;
  padding-top: $spacing-lg;
  margin-top: $spacing-lg;
  border-top: 1px solid $border-color;
}

@media (max-width: $breakpoint-md) {
  .route-form {
    grid-template-columns: 1fr;
  }

  .route-arrow {
    padding: 0;
    transform: rotate(90deg);
    margin: $spacing-md 0;
  }

  .cargo-fields {
    grid-template-columns: repeat(2, 1fr);
  }

  .options-grid {
    grid-template-columns: 1fr;
  }

  .checkbox-group {
    grid-template-columns: 1fr;
  }

  .stepper {
    display: none;
  }
}

.header-right {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}
</style>

<style lang="scss">
/* Dark theme styles for ShipmentCreateView */
[data-theme="dark"] {
  .page {
    background: #1a1a1a !important;
  }

  .page-header {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    h1 {
      color: #f5f5f5 !important;
    }
  }

  .back-link {
    color: #999999 !important;

    &:hover {
      color: #f5f5f5 !important;
    }
  }

  .stepper .step {
    .step-number {
      background: #252525 !important;
      border-color: #3a3a3a !important;
      color: #999999 !important;
    }

    .step-title {
      color: #666666 !important;
    }

    &.active {
      .step-number {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #ffffff !important;
      }

      .step-title {
        color: #f97316 !important;
      }
    }

    &.done {
      .step-number {
        background: #22c55e !important;
        border-color: #22c55e !important;
        color: #ffffff !important;
      }

      .step-title {
        color: #22c55e !important;
      }
    }
  }

  .page-content {
    background: #1a1a1a !important;
  }

  .shipment-form {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;
  }

  .form-step {
    h2 {
      color: #f5f5f5 !important;
    }
  }

  .step-desc {
    color: #999999 !important;
  }

  .route-section {
    h3 {
      color: #f5f5f5 !important;
    }
  }

  .address-details {
    background: rgba(249, 115, 22, 0.1) !important;
    border-color: #f97316 !important;

    .detail-label {
      color: #999999 !important;
    }

    .detail-value {
      color: #f5f5f5 !important;
    }
  }

  .section-icon {
    background: rgba(249, 115, 22, 0.15) !important;

    svg {
      color: #f97316 !important;
    }

    &.destination {
      background: rgba(34, 197, 94, 0.15) !important;

      svg {
        color: #22c55e !important;
      }
    }
  }

  .route-arrow {
    color: #666666 !important;
  }

  .form-group {
    label {
      color: #f5f5f5 !important;
    }

    input, select, textarea {
      background: #1a1a1a !important;
      border-color: #2a2a2a !important;
      color: #f5f5f5 !important;

      &::placeholder {
        color: #666666 !important;
      }

      &:focus {
        border-color: #f97316 !important;
      }

      option {
        background: #1a1a1a !important;
        color: #f5f5f5 !important;
      }
    }
  }

  .cargo-item {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;

    h4 {
      color: #f5f5f5 !important;
    }
  }

  .btn-remove-item {
    color: #999999 !important;

    &:hover {
      color: #ef4444 !important;
      background: rgba(239, 68, 68, 0.1) !important;
    }
  }

  .btn-add-item {
    background: transparent !important;
    border-color: #3a3a3a !important;
    color: #999999 !important;

    &:hover {
      border-color: #f97316 !important;
      color: #f97316 !important;
    }
  }

  .transport-options,
  .packaging-options {
    .option {
      background: #1a1a1a !important;
      border-color: #2a2a2a !important;
      color: #999999 !important;

      &:hover {
        border-color: #3a3a3a !important;
      }

      &.selected {
        background: rgba(249, 115, 22, 0.1) !important;
        border-color: #f97316 !important;
        color: #f5f5f5 !important;

        svg {
          color: #f97316 !important;
        }
      }

      .option-label {
        color: #f5f5f5 !important;
      }

      .option-desc {
        color: #666666 !important;
      }
    }
  }

  .checkbox-group {
    .checkbox-item {
      background: #1a1a1a !important;
      border-color: #2a2a2a !important;

      &.checked {
        border-color: #f97316 !important;
        background: rgba(249, 115, 22, 0.1) !important;
      }

      .checkbox-label {
        color: #f5f5f5 !important;
      }

      .checkbox-desc {
        color: #666666 !important;
      }
    }

    /* Checkbox cards (services step) */
    .checkbox-card {
      .checkbox-content {
        background: #1a1a1a !important;
        border-color: #2a2a2a !important;

        svg {
          color: #666666 !important;
        }

        span {
          color: #999999 !important;
        }
      }

      &:hover .checkbox-content {
        border-color: #f97316 !important;
      }

      &.checked .checkbox-content {
        border-color: #f97316 !important;
        background: rgba(249, 115, 22, 0.1) !important;

        svg {
          color: #f97316 !important;
        }

        span {
          color: #f97316 !important;
        }
      }
    }
  }

  /* Summary card on step 4 */
  .summary-card {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
  }

  .summary-section {
    background: transparent !important;
    border-color: #2a2a2a !important;

    h3 {
      color: #999999 !important;
    }
  }

  .summary-route {
    .route-point {
      .point-label {
        color: #999999 !important;
      }

      .point-value {
        color: #f5f5f5 !important;
      }
    }

    .route-arrow-small svg {
      color: #666666 !important;
    }
  }

  .summary-details {
    .detail-item {
      .detail-label {
        color: #999999 !important;
      }

      .detail-value {
        color: #f5f5f5 !important;
      }
    }
  }

  .services-list {
    .service-tag {
      background: rgba(249, 115, 22, 0.15) !important;
      color: #f97316 !important;
    }

    .no-services {
      color: #666666 !important;
    }
  }

  .summary-item {
    border-color: #2a2a2a !important;

    .summary-label {
      color: #999999 !important;
    }

    .summary-value {
      color: #f5f5f5 !important;
    }
  }

  .form-actions {
    border-color: #2a2a2a !important;
  }

  .btn-secondary {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;

    &:hover {
      background: #252525 !important;
    }
  }

  .btn-primary {
    background: #f97316 !important;
    border-color: #f97316 !important;
    color: #ffffff !important;

    &:hover {
      background: #ea580c !important;
    }

    &:disabled {
      opacity: 0.6;
    }
  }

  .error-message {
    background: rgba(239, 68, 68, 0.1) !important;
    border-color: #ef4444 !important;
    color: #ef4444 !important;
  }
}
</style>
