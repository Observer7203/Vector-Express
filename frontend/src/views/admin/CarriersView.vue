<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  Plus,
  Search,
  Truck,
  Edit,
  Trash2,
  ToggleLeft,
  ToggleRight,
  X,
  Check,
  AlertCircle
} from 'lucide-vue-next'

const adminStore = useAdminStore()
const iconStrokeWidth = 1.5

const search = ref('')
const filterActive = ref('')
const showModal = ref(false)
const editingCarrier = ref(null)
const saving = ref(false)

const form = ref({
  company_id: '',
  api_type: 'manual',
  supported_transport_types: [],
  supported_countries: [],
  is_active: true
})

const newCountry = ref('')

const transportTypes = [
  { value: 'air', label: 'Авиа' },
  { value: 'sea', label: 'Морской' },
  { value: 'rail', label: 'Ж/Д' },
  { value: 'road', label: 'Автомобильный' }
]

const apiTypes = [
  { value: 'manual', label: 'Ручной расчёт' },
  { value: 'dhl', label: 'DHL API' },
  { value: 'fedex', label: 'FedEx API' },
  { value: 'ups', label: 'UPS API' },
  { value: 'ponyexpress', label: 'Ponyexpress API' },
  { value: 'custom', label: 'Custom API' }
]

onMounted(() => {
  adminStore.fetchCarriers()
  adminStore.fetchAvailableCompanies()
})

watch([search, filterActive], () => {
  adminStore.fetchCarriers({
    search: search.value,
    is_active: filterActive.value
  })
})

const carriers = computed(() => adminStore.carriers)
const availableCompanies = computed(() => adminStore.availableCompanies)
const loading = computed(() => adminStore.loading)
const pagination = computed(() => adminStore.carriersPagination)

function openCreateModal() {
  editingCarrier.value = null
  form.value = {
    company_id: '',
    api_type: 'manual',
    supported_transport_types: [],
    supported_countries: [],
    is_active: true
  }
  showModal.value = true
}

function openEditModal(carrier) {
  editingCarrier.value = carrier
  form.value = {
    company_id: carrier.company_id,
    api_type: carrier.api_type,
    supported_transport_types: [...(carrier.supported_transport_types || [])],
    supported_countries: [...(carrier.supported_countries || [])],
    is_active: carrier.is_active
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingCarrier.value = null
}

function addCountry() {
  if (newCountry.value && !form.value.supported_countries.includes(newCountry.value)) {
    form.value.supported_countries.push(newCountry.value)
    newCountry.value = ''
  }
}

function removeCountry(country) {
  form.value.supported_countries = form.value.supported_countries.filter(c => c !== country)
}

async function saveCarrier() {
  saving.value = true
  try {
    if (editingCarrier.value) {
      await adminStore.updateCarrier(editingCarrier.value.id, form.value)
    } else {
      await adminStore.createCarrier(form.value)
    }
    await adminStore.fetchCarriers()
    await adminStore.fetchAvailableCompanies()
    closeModal()
  } catch (e) {
    console.error('Error saving carrier:', e)
  } finally {
    saving.value = false
  }
}

async function toggleActive(carrier) {
  try {
    await adminStore.toggleCarrierActive(carrier.id)
  } catch (e) {
    console.error('Error toggling carrier:', e)
  }
}

async function deleteCarrier(carrier) {
  if (!confirm(`Удалить перевозчика "${carrier.company?.name}"?`)) return

  try {
    await adminStore.deleteCarrier(carrier.id)
    await adminStore.fetchCarriers()
    await adminStore.fetchAvailableCompanies()
  } catch (e) {
    console.error('Error deleting carrier:', e)
  }
}

function getTransportLabel(type) {
  return transportTypes.find(t => t.value === type)?.label || type
}

function getApiTypeLabel(type) {
  return apiTypes.find(t => t.value === type)?.label || type
}

function changePage(page) {
  adminStore.fetchCarriers({ page, search: search.value, is_active: filterActive.value })
}
</script>

<template>
  <div class="carriers-page">
    <div class="page-header">
      <div>
        <h1>Перевозчики</h1>
        <p class="subtitle">Управление настройками перевозчиков для расчёта цен</p>
      </div>
      <button @click="openCreateModal" class="btn btn-primary">
        <Plus :size="18" :stroke-width="iconStrokeWidth" />
        <span>Добавить перевозчика</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="search-box">
        <Search :size="18" :stroke-width="iconStrokeWidth" class="search-icon" />
        <input
          v-model="search"
          type="text"
          placeholder="Поиск по названию..."
        />
      </div>
      <div class="filter-group">
        <select v-model="filterActive">
          <option value="">Все статусы</option>
          <option value="1">Активные</option>
          <option value="0">Неактивные</option>
        </select>
      </div>
    </div>

    <!-- Info Banner for companies without carrier -->
    <div v-if="availableCompanies.length > 0" class="info-banner">
      <AlertCircle :size="20" :stroke-width="iconStrokeWidth" />
      <span>
        <strong>{{ availableCompanies.length }}</strong> компаний типа "перевозчик" без настроек carrier.
        Нажмите "Добавить перевозчика" чтобы настроить.
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <!-- Table -->
    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Компания</th>
            <th>Тип API</th>
            <th>Типы перевозок</th>
            <th>Страны</th>
            <th>Статистика</th>
            <th>Статус</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="carrier in carriers" :key="carrier.id">
            <td>
              <div class="company-cell">
                <div class="company-avatar">
                  <Truck :size="18" :stroke-width="iconStrokeWidth" />
                </div>
                <div>
                  <span class="company-name">{{ carrier.company?.name || 'N/A' }}</span>
                  <span class="company-inn">ИНН: {{ carrier.company?.inn || '-' }}</span>
                </div>
              </div>
            </td>
            <td>
              <span class="api-badge" :class="carrier.api_type">
                {{ getApiTypeLabel(carrier.api_type) }}
              </span>
            </td>
            <td>
              <div class="transport-tags">
                <span
                  v-for="type in carrier.supported_transport_types"
                  :key="type"
                  class="transport-tag"
                  :class="type"
                >
                  {{ getTransportLabel(type) }}
                </span>
              </div>
            </td>
            <td>
              <div class="countries-cell">
                <span v-for="(country, i) in (carrier.supported_countries || []).slice(0, 3)" :key="i">
                  {{ country }}<template v-if="i < 2 && i < carrier.supported_countries.length - 1">, </template>
                </span>
                <span v-if="carrier.supported_countries?.length > 3" class="more-count">
                  +{{ carrier.supported_countries.length - 3 }}
                </span>
              </div>
            </td>
            <td>
              <div class="stats-cell">
                <span>{{ carrier.quotes_count || 0 }} предложений</span>
                <span>{{ carrier.orders_count || 0 }} заказов</span>
              </div>
            </td>
            <td>
              <button
                @click="toggleActive(carrier)"
                class="status-toggle"
                :class="{ active: carrier.is_active }"
              >
                <ToggleRight v-if="carrier.is_active" :size="24" :stroke-width="iconStrokeWidth" />
                <ToggleLeft v-else :size="24" :stroke-width="iconStrokeWidth" />
                <span>{{ carrier.is_active ? 'Активен' : 'Неактивен' }}</span>
              </button>
            </td>
            <td>
              <div class="actions">
                <button @click="openEditModal(carrier)" class="action-btn" title="Редактировать">
                  <Edit :size="16" :stroke-width="iconStrokeWidth" />
                </button>
                <button @click="deleteCarrier(carrier)" class="action-btn danger" title="Удалить">
                  <Trash2 :size="16" :stroke-width="iconStrokeWidth" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="carriers.length === 0">
            <td colspan="7" class="empty-row">
              Перевозчики не найдены
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.lastPage > 1" class="pagination">
      <button
        @click="changePage(pagination.currentPage - 1)"
        :disabled="pagination.currentPage === 1"
        class="btn btn-sm btn-outline"
      >
        Назад
      </button>
      <span class="page-info">
        Страница {{ pagination.currentPage }} из {{ pagination.lastPage }}
      </span>
      <button
        @click="changePage(pagination.currentPage + 1)"
        :disabled="pagination.currentPage === pagination.lastPage"
        class="btn btn-sm btn-outline"
      >
        Далее
      </button>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingCarrier ? 'Редактировать перевозчика' : 'Добавить перевозчика' }}</h2>
          <button @click="closeModal" class="close-btn">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group" v-if="!editingCarrier">
            <label>Компания *</label>
            <select v-model="form.company_id" required>
              <option value="">Выберите компанию</option>
              <option
                v-for="company in availableCompanies"
                :key="company.id"
                :value="company.id"
              >
                {{ company.name }} ({{ company.inn || 'без ИНН' }})
                {{ company.verified ? '✓' : '' }}
              </option>
            </select>
            <p class="form-hint" v-if="availableCompanies.length === 0">
              Нет компаний без настроек carrier
            </p>
          </div>

          <div class="form-group">
            <label>Тип API *</label>
            <select v-model="form.api_type" required>
              <option v-for="type in apiTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Типы перевозок *</label>
            <div class="checkbox-group">
              <label v-for="type in transportTypes" :key="type.value" class="checkbox-label">
                <input
                  type="checkbox"
                  :value="type.value"
                  v-model="form.supported_transport_types"
                />
                <span>{{ type.label }}</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>Страны *</label>
            <div class="tags-input">
              <div class="tags-list">
                <span
                  v-for="country in form.supported_countries"
                  :key="country"
                  class="tag"
                >
                  {{ country }}
                  <button @click="removeCountry(country)" class="tag-remove">
                    <X :size="14" :stroke-width="iconStrokeWidth" />
                  </button>
                </span>
              </div>
              <div class="tag-input-row">
                <input
                  v-model="newCountry"
                  type="text"
                  placeholder="Введите страну..."
                  @keyup.enter="addCountry"
                />
                <button @click="addCountry" class="btn btn-sm btn-outline">
                  Добавить
                </button>
              </div>
            </div>
            <p class="form-hint">Популярные: Казахстан, Китай, Россия, США, Германия, Турция</p>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.is_active" />
              <span>Активен (будет участвовать в расчётах)</span>
            </label>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeModal" class="btn btn-outline">Отмена</button>
          <button
            @click="saveCarrier"
            class="btn btn-primary"
            :disabled="saving || !form.company_id && !editingCarrier || form.supported_transport_types.length === 0 || form.supported_countries.length === 0"
          >
            <span v-if="saving">Сохранение...</span>
            <span v-else>{{ editingCarrier ? 'Сохранить' : 'Создать' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.carriers-page {
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

.filters-bar {
  display: flex;
  gap: $spacing-md;
  margin-bottom: $spacing-lg;
}

.search-box {
  flex: 1;
  max-width: 300px;
  position: relative;

  .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: $text-muted;
  }

  input {
    width: 100%;
    padding: 10px 12px 10px 40px;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    height: 40px;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

.filter-group select {
  padding: 10px 32px 10px 12px;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  height: 40px;
  min-width: 150px;
  appearance: none;
  background: $bg-white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
  cursor: pointer;

  &:focus {
    outline: none;
    border-color: $color-primary;
  }
}

.info-banner {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-md;
  background: rgba($color-warning, 0.1);
  border: 1px solid rgba($color-warning, 0.3);
  border-radius: $radius-lg;
  margin-bottom: $spacing-lg;
  color: darken($color-warning, 15%);
  font-size: $font-size-sm;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: $spacing-3xl;
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

.table-container {
  background: $bg-white;
  border-radius: $radius-xl;
  border: 1px solid $border-color;
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;

  th, td {
    padding: $spacing-md;
    text-align: left;
    border-bottom: 1px solid $border-color;
  }

  th {
    background: $bg-light;
    font-weight: 600;
    font-size: $font-size-xs;
    color: $text-secondary;
    text-transform: uppercase;
  }

  td {
    font-size: $font-size-sm;
  }

  tbody tr:hover {
    background: $bg-hover;
  }

  tbody tr:last-child td {
    border-bottom: none;
  }
}

.company-cell {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.company-avatar {
  width: 36px;
  height: 36px;
  background: rgba($color-primary, 0.1);
  color: $color-primary;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
}

.company-name {
  display: block;
  font-weight: 500;
  color: $text-primary;
}

.company-inn {
  display: block;
  font-size: $font-size-xs;
  color: $text-muted;
}

.api-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: $radius-md;
  font-size: $font-size-xs;
  font-weight: 500;

  &.manual { background: $bg-light; color: $text-secondary; }
  &.dhl { background: #FFCC00; color: #C00; }
  &.fedex { background: #4D148C; color: white; }
  &.ups { background: #351C15; color: #FFB500; }
  &.ponyexpress { background: #E31E24; color: white; }
  &.custom { background: $color-info; color: white; }
}

.transport-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.transport-tag {
  display: inline-block;
  padding: 2px 6px;
  border-radius: $radius-sm;
  font-size: $font-size-xs;

  &.air { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
  &.sea { background: rgba(6, 182, 212, 0.1); color: #06b6d4; }
  &.rail { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
  &.road { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
}

.countries-cell {
  font-size: $font-size-sm;
  color: $text-secondary;
}

.more-count {
  color: $text-muted;
  font-size: $font-size-xs;
}

.stats-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: $font-size-xs;
  color: $text-muted;
}

.status-toggle {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  background: none;
  border: none;
  cursor: pointer;
  font-size: $font-size-sm;
  color: $text-muted;

  &.active {
    color: $color-success;
  }
}

.actions {
  display: flex;
  gap: $spacing-xs;
}

.action-btn {
  width: 32px;
  height: 32px;
  background: $bg-light;
  border: none;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: $text-secondary;
  transition: all $transition-fast;

  &:hover {
    background: $color-primary;
    color: white;
  }

  &.danger:hover {
    background: $color-danger;
  }
}

.empty-row {
  text-align: center;
  color: $text-muted;
  padding: $spacing-2xl !important;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: $spacing-md;
  margin-top: $spacing-lg;
}

.page-info {
  font-size: $font-size-sm;
  color: $text-secondary;
}

// Modal
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: $bg-white;
  border-radius: $radius-xl;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-lg;
  border-bottom: 1px solid $border-color;

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    margin: 0;
  }
}

.close-btn {
  width: 32px;
  height: 32px;
  background: $bg-light;
  border: none;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: $text-secondary;

  &:hover {
    background: $border-color;
  }
}

.modal-body {
  padding: $spacing-lg;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: $spacing-sm;
  padding: $spacing-lg;
  border-top: 1px solid $border-color;
}

.form-group {
  margin-bottom: $spacing-lg;

  label {
    display: block;
    font-size: $font-size-sm;
    font-weight: 500;
    color: $text-primary;
    margin-bottom: $spacing-xs;
  }

  input, select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

.form-hint {
  font-size: $font-size-xs;
  color: $text-muted;
  margin-top: $spacing-xs;
}

.checkbox-group {
  display: flex;
  flex-wrap: wrap;
  gap: $spacing-md;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  cursor: pointer;
  font-size: $font-size-sm;

  input[type="checkbox"] {
    width: 16px;
    height: 16px;
  }
}

.tags-input {
  border: 1px solid $border-color;
  border-radius: $radius-md;
  padding: $spacing-sm;
}

.tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: $spacing-xs;
  margin-bottom: $spacing-sm;
}

.tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  background: $bg-light;
  border-radius: $radius-md;
  font-size: $font-size-sm;
}

.tag-remove {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: $text-muted;
  display: flex;

  &:hover {
    color: $color-danger;
  }
}

.tag-input-row {
  display: flex;
  gap: $spacing-sm;

  input {
    flex: 1;
    border: none;
    padding: $spacing-xs;

    &:focus {
      outline: none;
    }
  }
}

// Buttons
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: 10px 20px;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-base;
  border: none;
  height: 40px;

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

.btn-outline {
  background: $bg-white;
  color: $text-primary;
  border: 1px solid $border-color;

  &:hover:not(:disabled) {
    background: $bg-light;
  }
}

.btn-sm {
  padding: 6px 12px;
  height: 32px;
  font-size: $font-size-xs;
}
</style>
