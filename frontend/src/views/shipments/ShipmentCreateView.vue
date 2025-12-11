<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useShipmentsStore } from '@/stores/shipments'
import { ArrowLeft, ArrowRight, Check, MapPin, Trash2, Plus, Shield, FileText, Home } from 'lucide-vue-next'

const iconStrokeWidth = 1.2

const router = useRouter()
const shipmentsStore = useShipmentsStore()

const step = ref(1)
const totalSteps = 4

const form = ref({
  origin_country: '',
  origin_city: '',
  origin_address: '',
  destination_country: '',
  destination_city: '',
  destination_address: '',
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

const stepTitles = ['Направление', 'Груз', 'Услуги', 'Проверка']

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
    errorMessage.value = e.response?.data?.message || 'Произошла ошибка при создании заявки'
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
            <h1>Создание заявки</h1>
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
            <h2>Укажите направление доставки</h2>
            <p class="step-desc">Откуда и куда нужно доставить груз</p>

            <div class="route-form">
              <div class="route-section">
                <div class="section-header">
                  <div class="section-icon">
                    <MapPin :size="18" :stroke-width="iconStrokeWidth" />
                  </div>
                  <h3>Откуда</h3>
                </div>
                <div class="form-group">
                  <label>Страна *</label>
                  <input v-model="form.origin_country" type="text" required placeholder="Китай" />
                </div>
                <div class="form-group">
                  <label>Город *</label>
                  <input v-model="form.origin_city" type="text" required placeholder="Гуанчжоу" />
                </div>
                <div class="form-group">
                  <label>Адрес (опционально)</label>
                  <input v-model="form.origin_address" type="text" placeholder="Улица, дом" />
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
                  <h3>Куда</h3>
                </div>
                <div class="form-group">
                  <label>Страна *</label>
                  <input v-model="form.destination_country" type="text" required placeholder="Казахстан" />
                </div>
                <div class="form-group">
                  <label>Город *</label>
                  <input v-model="form.destination_city" type="text" required placeholder="Алматы" />
                </div>
                <div class="form-group">
                  <label>Адрес (опционально)</label>
                  <input v-model="form.destination_address" type="text" placeholder="Улица, дом" />
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Cargo -->
          <div v-if="step === 2" class="form-step">
            <h2>Параметры груза</h2>
            <p class="step-desc">Укажите габариты и вес каждого места</p>

            <div class="cargo-list">
              <div v-for="(item, index) in form.items" :key="index" class="cargo-item">
                <div class="cargo-header">
                  <span class="cargo-title">Место {{ index + 1 }}</span>
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
                    <label>Длина (см)</label>
                    <input v-model.number="item.length" type="number" min="0" placeholder="100" />
                  </div>
                  <div class="form-group">
                    <label>Ширина (см)</label>
                    <input v-model.number="item.width" type="number" min="0" placeholder="50" />
                  </div>
                  <div class="form-group">
                    <label>Высота (см)</label>
                    <input v-model.number="item.height" type="number" min="0" placeholder="50" />
                  </div>
                  <div class="form-group">
                    <label>Вес (кг) *</label>
                    <input v-model.number="item.weight" type="number" min="0" required placeholder="25" />
                  </div>
                  <div class="form-group">
                    <label>Кол-во</label>
                    <input v-model.number="item.quantity" type="number" min="1" placeholder="1" />
                  </div>
                </div>

                <div class="form-group">
                  <label>Описание содержимого</label>
                  <input v-model="item.description" type="text" placeholder="Электроника, одежда и т.д." />
                </div>
              </div>
            </div>

            <button type="button" @click="addItem" class="btn btn-outline btn-add">
              <Plus :size="18" :stroke-width="iconStrokeWidth" />
              Добавить место
            </button>
          </div>

          <!-- Step 3: Options -->
          <div v-if="step === 3" class="form-step">
            <h2>Дополнительные услуги</h2>
            <p class="step-desc">Выберите тип перевозки и дополнительные опции</p>

            <div class="options-grid">
              <div class="form-group">
                <label>Тип перевозки</label>
                <select v-model="form.transport_type">
                  <option value="">Любой (подобрать лучший)</option>
                  <option value="air">Авиа</option>
                  <option value="sea">Морской</option>
                  <option value="rail">Ж/Д</option>
                  <option value="road">Автомобильный</option>
                </select>
              </div>

              <div class="form-group">
                <label>Тип груза</label>
                <select v-model="form.cargo_type">
                  <option value="general">Обычный</option>
                  <option value="dangerous">Опасный</option>
                  <option value="fragile">Хрупкий</option>
                  <option value="perishable">Скоропортящийся</option>
                </select>
              </div>

              <div class="form-group">
                <label>Объявленная стоимость (USD)</label>
                <input v-model.number="form.declared_value" type="number" min="0" placeholder="5000" />
              </div>

              <div class="form-group">
                <label>Желаемая дата забора</label>
                <input v-model="form.pickup_date" type="date" />
              </div>
            </div>

            <div class="services-section">
              <h3>Дополнительные услуги</h3>
              <div class="checkbox-group">
                <label class="checkbox-card" :class="{ checked: form.insurance_required }">
                  <input v-model="form.insurance_required" type="checkbox" />
                  <div class="checkbox-content">
                    <Shield :size="24" :stroke-width="iconStrokeWidth" />
                    <span>Страхование груза</span>
                  </div>
                </label>
                <label class="checkbox-card" :class="{ checked: form.customs_clearance }">
                  <input v-model="form.customs_clearance" type="checkbox" />
                  <div class="checkbox-content">
                    <FileText :size="24" :stroke-width="iconStrokeWidth" />
                    <span>Таможня</span>
                  </div>
                </label>
                <label class="checkbox-card" :class="{ checked: form.door_to_door }">
                  <input v-model="form.door_to_door" type="checkbox" />
                  <div class="checkbox-content">
                    <Home :size="24" :stroke-width="iconStrokeWidth" />
                    <span>Доставка до двери</span>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- Step 4: Review -->
          <div v-if="step === 4" class="form-step">
            <h2>Проверьте данные</h2>
            <p class="step-desc">Убедитесь, что все данные заполнены верно</p>

            <div class="summary-card">
              <div class="summary-section">
                <h3>Маршрут</h3>
                <div class="summary-route">
                  <div class="route-point">
                    <span class="point-label">Откуда</span>
                    <span class="point-value">{{ form.origin_city }}, {{ form.origin_country }}</span>
                  </div>
                  <div class="route-arrow-small">
                    <ArrowRight :size="18" :stroke-width="iconStrokeWidth" />
                  </div>
                  <div class="route-point">
                    <span class="point-label">Куда</span>
                    <span class="point-value">{{ form.destination_city }}, {{ form.destination_country }}</span>
                  </div>
                </div>
              </div>

              <div class="summary-section">
                <h3>Груз</h3>
                <div class="summary-details">
                  <div class="detail-item">
                    <span class="detail-label">Мест</span>
                    <span class="detail-value">{{ form.items.length }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Общий вес</span>
                    <span class="detail-value">{{ totalWeight }} кг</span>
                  </div>
                </div>
              </div>

              <div class="summary-section">
                <h3>Услуги</h3>
                <div class="services-list">
                  <span v-if="form.insurance_required" class="service-tag">Страхование</span>
                  <span v-if="form.customs_clearance" class="service-tag">Таможня</span>
                  <span v-if="form.door_to_door" class="service-tag">До двери</span>
                  <span v-if="!form.insurance_required && !form.customs_clearance && !form.door_to_door" class="no-services">Не выбраны</span>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Комментарий для перевозчика</label>
              <textarea
                v-model="form.notes"
                rows="3"
                placeholder="Дополнительная информация о грузе или особые требования"
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
              Назад
            </button>
            <button
              v-if="step < totalSteps"
              type="button"
              @click="nextStep"
              class="btn btn-primary"
              :disabled="!canProceed"
            >
              Далее
              <ArrowRight :size="18" :stroke-width="iconStrokeWidth" />
            </button>
            <button
              v-if="step === totalSteps"
              type="submit"
              class="btn btn-primary"
              :disabled="submitting"
            >
              <span v-if="submitting" class="btn-loader"></span>
              {{ submitting ? 'Расчет...' : 'Рассчитать стоимость' }}
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
</style>
