<script setup>
import { onMounted, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import api from '@/api/client'
import {
  DollarSign,
  Plus,
  Edit2,
  Trash2,
  Search,
  Filter,
  X,
  Save,
  ChevronDown,
  Upload,
  FileSpreadsheet,
  AlertCircle
} from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'

const { t } = useI18n()
const iconStrokeWidth = 1.2

const authStore = useAuthStore()

const rateCards = ref([])
const zones = ref([])
const loading = ref(true)
const searchQuery = ref('')
const showAddModal = ref(false)
const editingRateCard = ref(null)

const filterTransportType = ref('')
const filterOriginZone = ref('')
const filterDestZone = ref('')

const newRateCard = ref({
  origin_zone_id: '',
  destination_zone_id: '',
  transport_type: 'road',
  min_weight: 0,
  max_weight: null,
  rate: 0,
  currency: 'USD',
  transit_days_min: 1,
  transit_days_max: 7
})

const transportTypes = computed(() => [
  { value: 'air', label: t('carrierRates.transportTypes.air') },
  { value: 'road', label: t('carrierRates.transportTypes.road') },
  { value: 'rail', label: t('carrierRates.transportTypes.rail') },
  { value: 'sea', label: t('carrierRates.transportTypes.sea') }
])

// Import functionality
const showImportModal = ref(false)
const importFile = ref(null)
const importLoading = ref(false)
const importResult = ref(null)

const filteredRateCards = computed(() => {
  let result = rateCards.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(
      (r) =>
        (r.originZone?.zone_name || r.origin_zone?.zone_name)?.toLowerCase().includes(query) ||
        (r.destinationZone?.zone_name || r.destination_zone?.zone_name)?.toLowerCase().includes(query)
    )
  }

  if (filterTransportType.value) {
    result = result.filter((r) => r.transport_type === filterTransportType.value)
  }

  if (filterOriginZone.value) {
    result = result.filter((r) => r.origin_zone_id == filterOriginZone.value)
  }

  if (filterDestZone.value) {
    result = result.filter((r) => r.destination_zone_id == filterDestZone.value)
  }

  return result
})

onMounted(async () => {
  await Promise.all([loadRateCards(), loadZones()])
})

async function loadZones() {
  try {
    const response = await api.get('/carrier/zones')
    zones.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load zones:', error)
    zones.value = [
      { id: 1, zone_code: 'Z1', zone_name: 'Казахстан' },
      { id: 2, zone_code: 'Z2', zone_name: 'Россия' },
      { id: 3, zone_code: 'Z3', zone_name: 'Китай' }
    ]
  }
}

async function loadRateCards() {
  loading.value = true
  try {
    const response = await api.get('/carrier/rate-cards')
    console.log('Rate cards API response:', response.data)
    rateCards.value = response.data.data || response.data || []
    console.log('Rate cards loaded:', rateCards.value.length, 'items')
  } catch (error) {
    console.error('Failed to load rate cards:', error)
    // Mock data
    rateCards.value = [
      {
        id: 1,
        origin_zone_id: 1,
        destination_zone_id: 2,
        originZone: { id: 1, zone_code: 'KZ', zone_name: 'Казахстан' },
        destinationZone: { id: 2, zone_code: 'RU', zone_name: 'Россия' },
        transport_type: 'air',
        min_weight: 0,
        max_weight: 5,
        rate: 15.5,
        currency: 'USD',
        transit_days_min: 3,
        transit_days_max: 5
      },
      {
        id: 2,
        origin_zone_id: 1,
        destination_zone_id: 2,
        originZone: { id: 1, zone_code: 'KZ', zone_name: 'Казахстан' },
        destinationZone: { id: 2, zone_code: 'RU', zone_name: 'Россия' },
        transport_type: 'air',
        min_weight: 5,
        max_weight: 20,
        rate: 12.0,
        currency: 'USD',
        transit_days_min: 3,
        transit_days_max: 5
      },
      {
        id: 3,
        origin_zone_id: 1,
        destination_zone_id: 3,
        originZone: { id: 1, zone_code: 'KZ', zone_name: 'Казахстан' },
        destinationZone: { id: 3, zone_code: 'CN', zone_name: 'Китай' },
        transport_type: 'road',
        min_weight: 0,
        max_weight: 100,
        rate: 4.5,
        currency: 'USD',
        transit_days_min: 10,
        transit_days_max: 14
      }
    ]
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editingRateCard.value = null
  newRateCard.value = {
    origin_zone_id: '',
    destination_zone_id: '',
    transport_type: 'road',
    min_weight: 0,
    max_weight: null,
    rate: 0,
    currency: 'USD',
    transit_days_min: 1,
    transit_days_max: 7
  }
  showAddModal.value = true
}

function openEditModal(rateCard) {
  editingRateCard.value = rateCard.id
  newRateCard.value = {
    origin_zone_id: rateCard.origin_zone_id,
    destination_zone_id: rateCard.destination_zone_id,
    transport_type: rateCard.transport_type,
    min_weight: rateCard.min_weight,
    max_weight: rateCard.max_weight,
    rate: rateCard.rate,
    currency: rateCard.currency || 'USD',
    transit_days_min: rateCard.transit_days_min,
    transit_days_max: rateCard.transit_days_max
  }
  showAddModal.value = true
}

function closeModal() {
  showAddModal.value = false
  editingRateCard.value = null
}

async function saveRateCard() {
  try {
    if (editingRateCard.value) {
      await api.put(`/carrier/rate-cards/${editingRateCard.value}`, newRateCard.value)
    } else {
      await api.post('/carrier/rate-cards', newRateCard.value)
    }
    closeModal()
    await loadRateCards()
  } catch (error) {
    console.error('Failed to save rate card:', error)
    // For development
    const originZone = zones.value.find((z) => z.id == newRateCard.value.origin_zone_id)
    const destZone = zones.value.find((z) => z.id == newRateCard.value.destination_zone_id)

    if (editingRateCard.value) {
      const index = rateCards.value.findIndex((r) => r.id === editingRateCard.value)
      if (index !== -1) {
        rateCards.value[index] = {
          ...rateCards.value[index],
          ...newRateCard.value,
          originZone,
          destinationZone: destZone
        }
      }
    } else {
      rateCards.value.push({
        id: Date.now(),
        ...newRateCard.value,
        originZone,
        destinationZone: destZone
      })
    }
    closeModal()
  }
}

async function deleteRateCard(rateCardId) {
  if (!confirm(t('carrierRates.confirmDelete'))) return

  try {
    await api.delete(`/carrier/rate-cards/${rateCardId}`)
    await loadRateCards()
  } catch (error) {
    console.error('Failed to delete rate card:', error)
    rateCards.value = rateCards.value.filter((r) => r.id !== rateCardId)
  }
}

function getTransportLabel(type) {
  const transportType = transportTypes.value.find((tt) => tt.value === type)
  return transportType ? transportType.label : type
}

function formatWeight(minWeight, maxWeight) {
  if (maxWeight === null || maxWeight === undefined) {
    return `${minWeight}+ ${t('carrierRates.kg')}`
  }
  return `${minWeight}-${maxWeight} ${t('carrierRates.kg')}`
}

function clearFilters() {
  filterTransportType.value = ''
  filterOriginZone.value = ''
  filterDestZone.value = ''
  searchQuery.value = ''
}

// Import functions
function openImportModal() {
  showImportModal.value = true
  importFile.value = null
  importResult.value = null
}

function closeImportModal() {
  showImportModal.value = false
  importFile.value = null
  importResult.value = null
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    importFile.value = file
  }
}

async function handleImport() {
  if (!importFile.value) return

  importLoading.value = true
  importResult.value = null

  try {
    const formData = new FormData()
    formData.append('file', importFile.value)

    const response = await api.post('/carrier/import/rate-cards', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    importResult.value = {
      success: true,
      message: response.data.message,
      imported: response.data.imported,
      skipped: response.data.skipped
    }

    await loadRateCards()
  } catch (error) {
    console.error('Import failed:', error)
    importResult.value = {
      success: false,
      message: error.response?.data?.error || t('carrierRates.importError')
    }
  } finally {
    importLoading.value = false
  }
}

async function handleLogout() {
  await authStore.logout()
}
</script>

<template>
  <div class="carrier-rates">
    <AppHeader />

    <main class="dashboard-main">
      <div class="container">
        <div class="page-header">
          <div class="page-title">
            <h1>{{ t('carrierRates.title') }}</h1>
            <p class="subtitle">{{ t('carrierRates.subtitle') }}</p>
          </div>
          <div class="header-actions">
            <button class="btn btn-outline" @click="openImportModal">
              <Upload :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('carrierRates.importFromExcel') }}
            </button>
            <button class="btn btn-primary" @click="openAddModal">
              <Plus :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('carrierRates.addRate') }}
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
          <div class="search-wrapper">
            <Search :size="18" :stroke-width="iconStrokeWidth" class="search-icon" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('carrierRates.searchByZones')"
              class="search-input"
            />
          </div>

          <div class="filter-group">
            <select v-model="filterTransportType" class="filter-select">
              <option value="">{{ t('carrierRates.allTypes') }}</option>
              <option v-for="type in transportTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>

            <select v-model="filterOriginZone" class="filter-select">
              <option value="">{{ t('carrierRates.from') }}</option>
              <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                {{ zone.zone_name }}
              </option>
            </select>

            <select v-model="filterDestZone" class="filter-select">
              <option value="">{{ t('carrierRates.to') }}</option>
              <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                {{ zone.zone_name }}
              </option>
            </select>

            <button
              v-if="filterTransportType || filterOriginZone || filterDestZone"
              class="btn-clear-filters"
              @click="clearFilters"
            >
              <X :size="16" :stroke-width="iconStrokeWidth" />
              {{ t('carrierRates.reset') }}
            </button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>{{ t('carrierRates.loadingRates') }}</p>
        </div>

        <!-- Rate Cards Table -->
        <div v-else-if="filteredRateCards.length" class="rates-table-wrapper">
          <table class="rates-table">
            <thead>
              <tr>
                <th>{{ t('carrierRates.tableHeaders.from') }}</th>
                <th>{{ t('carrierRates.tableHeaders.to') }}</th>
                <th>{{ t('carrierRates.tableHeaders.type') }}</th>
                <th>{{ t('carrierRates.tableHeaders.weight') }}</th>
                <th>{{ t('carrierRates.tableHeaders.rate') }}</th>
                <th>{{ t('carrierRates.tableHeaders.transit') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="rate in filteredRateCards" :key="rate.id">
                <td>
                  <div class="zone-cell">
                    <span class="zone-code">{{ (rate.originZone || rate.origin_zone)?.zone_code }}</span>
                    <span class="zone-name">{{ (rate.originZone || rate.origin_zone)?.zone_name }}</span>
                  </div>
                </td>
                <td>
                  <div class="zone-cell">
                    <span class="zone-code">{{ (rate.destinationZone || rate.destination_zone)?.zone_code }}</span>
                    <span class="zone-name">{{ (rate.destinationZone || rate.destination_zone)?.zone_name }}</span>
                  </div>
                </td>
                <td>
                  <span class="transport-badge" :class="rate.transport_type">
                    {{ getTransportLabel(rate.transport_type) }}
                  </span>
                </td>
                <td class="weight-cell">
                  {{ formatWeight(rate.min_weight, rate.max_weight) }}
                </td>
                <td class="rate-cell">
                  <strong>${{ parseFloat(rate.rate || 0).toFixed(2) }}</strong>
                  <span class="rate-unit">/{{ t('carrierRates.kg') }}</span>
                </td>
                <td class="transit-cell">
                  {{ rate.transit_days_min }}-{{ rate.transit_days_max }} {{ t('carrierRates.days') }}
                </td>
                <td class="actions-cell">
                  <button class="btn-icon" @click="openEditModal(rate)" :title="t('common.edit')">
                    <Edit2 :size="16" :stroke-width="iconStrokeWidth" />
                  </button>
                  <button class="btn-icon btn-icon-danger" @click="deleteRateCard(rate.id)" :title="t('common.delete')">
                    <Trash2 :size="16" :stroke-width="iconStrokeWidth" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <DollarSign :size="48" :stroke-width="iconStrokeWidth" />
          <h3>{{ t('carrierRates.noRates') }}</h3>
          <p>{{ t('carrierRates.addRatePrompt') }}</p>
          <button class="btn btn-primary" @click="openAddModal">
            <Plus :size="18" :stroke-width="iconStrokeWidth" />
            {{ t('carrierRates.addRate') }}
          </button>
        </div>
      </div>
    </main>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingRateCard ? t('carrierRates.editRate') : t('carrierRates.newRate') }}</h2>
          <button class="btn-close" @click="closeModal">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>{{ t('carrierRates.form.originZone') }}</label>
              <select v-model="newRateCard.origin_zone_id" class="form-input">
                <option value="">{{ t('carrierRates.form.selectZone') }}</option>
                <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                  {{ zone.zone_code }} - {{ zone.zone_name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>{{ t('carrierRates.form.destinationZone') }}</label>
              <select v-model="newRateCard.destination_zone_id" class="form-input">
                <option value="">{{ t('carrierRates.form.selectZone') }}</option>
                <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                  {{ zone.zone_code }} - {{ zone.zone_name }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>{{ t('carrierRates.form.transportType') }}</label>
            <select v-model="newRateCard.transport_type" class="form-input">
              <option v-for="type in transportTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>{{ t('carrierRates.form.weightFrom') }}</label>
              <input
                v-model.number="newRateCard.min_weight"
                type="number"
                min="0"
                step="0.1"
                class="form-input"
              />
            </div>
            <div class="form-group">
              <label>{{ t('carrierRates.form.weightTo') }}</label>
              <input
                v-model.number="newRateCard.max_weight"
                type="number"
                min="0"
                step="0.1"
                :placeholder="t('carrierRates.form.noLimit')"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>{{ t('carrierRates.form.ratePerKg') }}</label>
              <input
                v-model.number="newRateCard.rate"
                type="number"
                min="0"
                step="0.01"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>{{ t('carrierRates.form.transitMin') }}</label>
              <input
                v-model.number="newRateCard.transit_days_min"
                type="number"
                min="1"
                class="form-input"
              />
            </div>
            <div class="form-group">
              <label>{{ t('carrierRates.form.transitMax') }}</label>
              <input
                v-model.number="newRateCard.transit_days_max"
                type="number"
                min="1"
                class="form-input"
              />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline" @click="closeModal">{{ t('common.cancel') }}</button>
          <button class="btn btn-primary" @click="saveRateCard">
            <Save :size="16" :stroke-width="iconStrokeWidth" />
            {{ editingRateCard ? t('common.save') : t('carrierRates.createRate') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="modal-overlay" @click.self="closeImportModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ t('carrierRates.import.title') }}</h2>
          <button class="btn-close" @click="closeImportModal">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="import-info">
            <FileSpreadsheet :size="24" :stroke-width="iconStrokeWidth" />
            <div>
              <p><strong>{{ t('carrierRates.import.fileFormat') }}</strong> .xlsx, .xls {{ t('carrierRates.import.or') }} .csv</p>
              <p class="import-hint">{{ t('carrierRates.import.ensureZones') }}</p>
            </div>
          </div>

          <div class="columns-info">
            <h4>{{ t('carrierRates.import.requiredColumns') }}</h4>
            <ul>
              <li><code>origin_zone</code> — {{ t('carrierRates.import.originZoneCode') }}</li>
              <li><code>destination_zone</code> — {{ t('carrierRates.import.destinationZoneCode') }}</li>
              <li><code>rate</code> — {{ t('carrierRates.import.ratePerKg') }}</li>
            </ul>
            <h4>{{ t('carrierRates.import.optionalColumns') }}</h4>
            <ul>
              <li><code>transport_type</code> — {{ t('carrierRates.import.transportTypeDesc') }}</li>
              <li><code>min_weight</code> — {{ t('carrierRates.import.minWeight') }}</li>
              <li><code>max_weight</code> — {{ t('carrierRates.import.maxWeight') }}</li>
              <li><code>currency</code> — {{ t('carrierRates.import.currency') }}</li>
              <li><code>transit_days_min</code> — {{ t('carrierRates.import.minTransit') }}</li>
              <li><code>transit_days_max</code> — {{ t('carrierRates.import.maxTransit') }}</li>
            </ul>
          </div>

          <div class="form-group">
            <label>{{ t('carrierRates.import.selectFile') }}</label>
            <input
              type="file"
              accept=".xlsx,.xls,.csv"
              @change="handleFileSelect"
              class="form-input file-input"
            />
          </div>

          <div v-if="importResult" class="import-result" :class="{ success: importResult.success, error: !importResult.success }">
            <AlertCircle :size="20" :stroke-width="iconStrokeWidth" />
            <div>
              <p>{{ importResult.message }}</p>
              <p v-if="importResult.success">
                {{ t('carrierRates.import.imported') }}: {{ importResult.imported }}, {{ t('carrierRates.import.skipped') }}: {{ importResult.skipped }}
              </p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline" @click="closeImportModal">{{ t('common.cancel') }}</button>
          <button
            class="btn btn-primary"
            @click="handleImport"
            :disabled="!importFile || importLoading"
          >
            <Upload v-if="!importLoading" :size="16" :stroke-width="iconStrokeWidth" />
            <span v-else class="spinner-small"></span>
            {{ importLoading ? t('carrierRates.import.importing') : t('carrierRates.import.importButton') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.carrier-rates {
  min-height: 100vh;
  background: $bg-light;
}

.dashboard-header {
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
  align-items: center;
  height: 64px;
  gap: $spacing-xl;
}

.header-info {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.logo {
  font-size: $font-size-xl;
  font-weight: 600;
  color: $color-primary;
  text-decoration: none;
}

.role-badge {
  background: rgba($color-secondary, 0.1);
  color: $color-secondary;
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;
}

.header-nav {
  display: flex;
  gap: $spacing-md;
  flex: 1;
}

.nav-link {
  color: $text-secondary;
  text-decoration: none;
  font-size: $font-size-sm;
  font-weight: 500;
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-md;
  transition: all $transition-fast;

  &:hover {
    color: $text-primary;
    background: $bg-hover;
  }

  &.router-link-active {
    color: $color-primary;
    background: rgba($color-primary, 0.1);
  }
}

.header-actions {
  display: flex;
  align-items: center;
  gap: $spacing-md;
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

.btn-icon {
  width: 32px;
  height: 32px;
  padding: 0;
  border-radius: $radius-md;
  background: transparent;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: $text-secondary;
  transition: all $transition-base;

  &:hover {
    background: $bg-hover;
    color: $text-primary;
  }

  &.btn-icon-danger:hover {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }
}

.dashboard-main {
  padding: $spacing-xl 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
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

.filters-bar {
  display: flex;
  gap: $spacing-md;
  margin-bottom: $spacing-lg;
  flex-wrap: wrap;
}

.search-wrapper {
  position: relative;
  flex: 1;
  min-width: 200px;
}

.search-icon {
  position: absolute;
  left: $spacing-md;
  top: 50%;
  transform: translateY(-50%);
  color: $text-muted;
}

.search-input {
  width: 100%;
  padding: $spacing-sm $spacing-md $spacing-sm 44px;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  background: $bg-white;

  &:focus {
    outline: none;
    border-color: $color-primary;
  }
}

.filter-group {
  display: flex;
  gap: $spacing-sm;
  flex-wrap: wrap;
}

.filter-select {
  padding: $spacing-sm $spacing-md;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  background: $bg-white;
  min-width: 120px;

  &:focus {
    outline: none;
    border-color: $color-primary;
  }
}

.btn-clear-filters {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  padding: $spacing-sm $spacing-md;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  background: $bg-white;
  color: $text-secondary;
  font-size: $font-size-sm;
  cursor: pointer;

  &:hover {
    border-color: $color-danger;
    color: $color-danger;
  }
}

.loading-state {
  text-align: center;
  padding: $spacing-2xl;

  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid $border-color;
    border-top-color: $color-primary;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto $spacing-md;
  }

  p {
    color: $text-secondary;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.rates-table-wrapper {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  overflow: hidden;
}

.rates-table {
  width: 100%;
  border-collapse: collapse;

  th, td {
    padding: $spacing-md $spacing-lg;
    text-align: left;
  }

  th {
    background: $bg-light;
    color: $text-secondary;
    font-size: $font-size-xs;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  td {
    border-top: 1px solid $border-color;
    font-size: $font-size-sm;
  }

  tr:hover td {
    background: $bg-light;
  }
}

.zone-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.zone-code {
  font-size: $font-size-xs;
  color: $text-muted;
  font-weight: 600;
}

.zone-name {
  color: $text-primary;
}

.transport-badge {
  display: inline-block;
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;

  &.air {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.road {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.rail {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
  }

  &.sea {
    background: rgba($color-secondary, 0.1);
    color: $color-secondary;
  }
}

.weight-cell {
  color: $text-secondary;
}

.rate-cell {
  strong {
    color: $color-primary;
    font-size: $font-size-base;
  }

  .rate-unit {
    color: $text-muted;
    font-size: $font-size-xs;
  }
}

.transit-cell {
  color: $text-secondary;
}

.actions-cell {
  text-align: right;
  white-space: nowrap;
}

.empty-state {
  text-align: center;
  padding: $spacing-2xl;
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;

  svg {
    color: $text-muted;
    margin-bottom: $spacing-md;
  }

  h3 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-xs;
  }

  p {
    color: $text-secondary;
    margin: 0 0 $spacing-lg;
  }
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
  padding: $spacing-lg;
}

.modal {
  background: $bg-white;
  border-radius: $radius-lg;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: $spacing-lg;
  border-bottom: 1px solid $border-color;
  display: flex;
  justify-content: space-between;
  align-items: center;

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
  }
}

.btn-close {
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  color: $text-secondary;

  &:hover {
    background: $bg-hover;
  }
}

.modal-body {
  padding: $spacing-lg;
}

.modal-footer {
  padding: $spacing-md $spacing-lg;
  border-top: 1px solid $border-color;
  display: flex;
  justify-content: flex-end;
  gap: $spacing-sm;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-md;
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

.form-input {
  width: 100%;
  padding: $spacing-sm $spacing-md;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  transition: border-color $transition-fast;

  &:focus {
    outline: none;
    border-color: $color-primary;
  }
}

// Import modal styles
.import-info {
  display: flex;
  gap: $spacing-md;
  padding: $spacing-md;
  background: rgba($color-primary, 0.05);
  border-radius: $radius-md;
  margin-bottom: $spacing-lg;

  p {
    margin: 0;
    font-size: $font-size-sm;
  }

  .import-hint {
    color: $text-muted;
    font-size: $font-size-xs;
    margin-top: $spacing-xs;
  }

  svg {
    color: $color-primary;
    flex-shrink: 0;
  }
}

.columns-info {
  background: $bg-light;
  border-radius: $radius-md;
  padding: $spacing-md;
  margin-bottom: $spacing-lg;

  h4 {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-sm;

    &:not(:first-child) {
      margin-top: $spacing-md;
    }
  }

  ul {
    margin: 0;
    padding-left: $spacing-lg;
    font-size: $font-size-sm;
  }

  li {
    color: $text-secondary;
    margin-bottom: $spacing-xs;
  }

  code {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
    padding: 2px 6px;
    border-radius: $radius-sm;
    font-size: $font-size-xs;
  }
}

.file-input {
  padding: $spacing-sm;
}

.import-result {
  display: flex;
  gap: $spacing-md;
  padding: $spacing-md;
  border-radius: $radius-md;
  margin-top: $spacing-md;

  &.success {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.error {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }

  p {
    margin: 0;
    font-size: $font-size-sm;
  }

  svg {
    flex-shrink: 0;
  }
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@media (max-width: $breakpoint-md) {
  .header-nav {
    display: none;
  }

  .page-header {
    flex-direction: column;
    gap: $spacing-md;
  }

  .header-actions {
    width: 100%;
    flex-direction: column;
  }

  .filters-bar {
    flex-direction: column;
  }

  .filter-group {
    width: 100%;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .rates-table {
    th, td {
      padding: $spacing-sm;
    }
  }
}
</style>
