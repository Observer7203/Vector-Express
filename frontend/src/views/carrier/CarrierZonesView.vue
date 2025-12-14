<script setup>
import { onMounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/api/client'
import {
  Globe,
  Plus,
  Edit2,
  Trash2,
  Search,
  ChevronDown,
  ChevronUp,
  MapPin,
  X,
  Save,
  AlertCircle,
  Upload,
  FileSpreadsheet
} from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'

const { t } = useI18n()

const iconStrokeWidth = 1.2

const zones = ref([])
const loading = ref(true)
const searchQuery = ref('')
const expandedZone = ref(null)
const showAddModal = ref(false)
const editingZone = ref(null)

const newZone = ref({
  zone_code: '',
  zone_name: '',
  country_code: '',
  description: '',
  postal_codes: []
})

const newPostalCode = ref({
  postal_code_prefix: '',
  city: '',
  region: '',
  is_remote_area: false
})

// Import functionality
const showImportModal = ref(false)
const importFile = ref(null)
const importLoading = ref(false)
const importResult = ref(null)

// Country codes
const countryCodes = [
  'AF', 'AL', 'DZ', 'AD', 'AO', 'AR', 'AM', 'AU', 'AT', 'AZ',
  'BY', 'BE', 'BD', 'BR', 'BG', 'CA', 'CL', 'CN', 'CO', 'HR',
  'CY', 'CZ', 'DK', 'EG', 'EE', 'FI', 'FR', 'GE', 'DE', 'GR',
  'HK', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IL', 'IT',
  'JP', 'JO', 'KZ', 'KE', 'KW', 'KG', 'LV', 'LB', 'LT', 'LU',
  'MY', 'MX', 'MD', 'MN', 'ME', 'MA', 'NL', 'NZ', 'NG', 'NO',
  'OM', 'PK', 'PA', 'PE', 'PH', 'PL', 'PT', 'QA', 'RO', 'RU',
  'SA', 'RS', 'SG', 'SK', 'SI', 'ZA', 'KR', 'ES', 'LK', 'SE',
  'CH', 'TW', 'TJ', 'TH', 'TR', 'TM', 'UA', 'AE', 'GB', 'US',
  'UZ', 'VE', 'VN'
]

// Full list of countries with translations
const allCountries = computed(() =>
  countryCodes.map(code => ({
    code,
    name: t(`carrierZones.countries.${code}`)
  }))
)

const countrySearch = ref('')
const showCountryDropdown = ref(false)

const filteredCountries = computed(() => {
  if (!countrySearch.value) return allCountries.value
  const query = countrySearch.value.toLowerCase()
  return allCountries.value.filter(
    (c) =>
      c.name.toLowerCase().includes(query) ||
      c.code.toLowerCase().includes(query)
  )
})

function selectCountry(country) {
  newZone.value.country_code = country.code
  countrySearch.value = country.name
  showCountryDropdown.value = false

  // Auto-fill zone code and name
  if (!editingZone.value || !newZone.value.zone_code) {
    newZone.value.zone_code = country.code
  }
  if (!editingZone.value || !newZone.value.zone_name) {
    newZone.value.zone_name = country.name
  }
}

function onCountrySearchFocus() {
  showCountryDropdown.value = true
}

function onCountrySearchBlur() {
  // Small delay to allow click on option
  setTimeout(() => {
    showCountryDropdown.value = false
  }, 200)
}

const countries = allCountries

const filteredZones = computed(() => {
  if (!searchQuery.value) return zones.value
  const query = searchQuery.value.toLowerCase()
  return zones.value.filter(
    (z) =>
      z.zone_name.toLowerCase().includes(query) ||
      z.zone_code.toLowerCase().includes(query) ||
      z.country_code.toLowerCase().includes(query)
  )
})

onMounted(async () => {
  await loadZones()
})

async function loadZones() {
  loading.value = true
  try {
    const response = await api.get('/carrier/zones')
    zones.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load zones:', error)
    // Mock data for development
    zones.value = [
      {
        id: 1,
        zone_code: 'Z1',
        zone_name: 'Казахстан',
        country_code: 'KZ',
        description: 'Зона для Казахстана',
        postal_codes: [
          { id: 1, postal_code_prefix: '050', city: 'Алматы', region: 'Алматы', is_remote_area: false },
          { id: 2, postal_code_prefix: '010', city: 'Астана', region: 'Астана', is_remote_area: false },
          { id: 3, postal_code_prefix: '160', city: 'Шымкент', region: 'Шымкент', is_remote_area: false },
          { id: 4, postal_code_prefix: '150', city: 'Туркестан', region: 'Туркестанская область', is_remote_area: true }
        ]
      },
      {
        id: 2,
        zone_code: 'Z2',
        zone_name: 'Россия',
        country_code: 'RU',
        description: 'Зона для России',
        postal_codes: [
          { id: 5, postal_code_prefix: '101', city: 'Москва', region: 'Москва', is_remote_area: false },
          { id: 6, postal_code_prefix: '190', city: 'Санкт-Петербург', region: 'Санкт-Петербург', is_remote_area: false }
        ]
      },
      {
        id: 3,
        zone_code: 'Z3',
        zone_name: 'Китай',
        country_code: 'CN',
        description: 'Зона для Китая',
        postal_codes: [
          { id: 7, postal_code_prefix: '510', city: 'Гуанчжоу', region: 'Гуандун', is_remote_area: false },
          { id: 8, postal_code_prefix: '200', city: 'Шанхай', region: 'Шанхай', is_remote_area: false }
        ]
      }
    ]
  } finally {
    loading.value = false
  }
}

function toggleZone(zoneId) {
  expandedZone.value = expandedZone.value === zoneId ? null : zoneId
}

function openAddModal() {
  editingZone.value = null
  countrySearch.value = ''
  newZone.value = {
    zone_code: '',
    zone_name: '',
    country_code: '',
    description: '',
    postal_codes: []
  }
  showAddModal.value = true
}

function openEditModal(zone) {
  editingZone.value = zone.id

  // Находим страну по коду и заполняем поле поиска
  const country = allCountries.value.find((c) => c.code === zone.country_code)
  countrySearch.value = country ? country.name : zone.country_code

  newZone.value = {
    zone_code: zone.zone_code,
    zone_name: zone.zone_name,
    country_code: zone.country_code,
    description: zone.description || '',
    postal_codes: [...(zone.postal_codes || [])]
  }
  showAddModal.value = true
}

function closeModal() {
  showAddModal.value = false
  editingZone.value = null
  newZone.value = {
    zone_code: '',
    zone_name: '',
    country_code: '',
    description: '',
    postal_codes: []
  }
}

function addPostalCode() {
  if (!newPostalCode.value.city && !newPostalCode.value.postal_code_prefix) {
    alert(t('carrierZones.alerts.enterCityOrPostalCode'))
    return
  }

  newZone.value.postal_codes.push({
    ...newPostalCode.value,
    id: Date.now()
  })

  newPostalCode.value = {
    postal_code_prefix: '',
    city: '',
    region: '',
    is_remote_area: false
  }
}

function removePostalCode(index) {
  newZone.value.postal_codes.splice(index, 1)
}

async function saveZone() {
  try {
    if (editingZone.value) {
      await api.put(`/carrier/zones/${editingZone.value}`, newZone.value)
    } else {
      await api.post('/carrier/zones', newZone.value)
    }
    closeModal()
    await loadZones()
  } catch (error) {
    console.error('Failed to save zone:', error)
    // For development, just add to local state
    if (editingZone.value) {
      const index = zones.value.findIndex((z) => z.id === editingZone.value)
      if (index !== -1) {
        zones.value[index] = { ...zones.value[index], ...newZone.value }
      }
    } else {
      zones.value.push({
        id: Date.now(),
        ...newZone.value
      })
    }
    closeModal()
  }
}

async function deleteZone(zoneId) {
  if (!confirm(t('carrierZones.alerts.confirmDelete'))) return

  try {
    await api.delete(`/carrier/zones/${zoneId}`)
    await loadZones()
  } catch (error) {
    console.error('Failed to delete zone:', error)
    zones.value = zones.value.filter((z) => z.id !== zoneId)
  }
}

function getCountryName(code) {
  const country = countries.value.find((c) => c.code === code)
  return country ? country.name : code
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

    const response = await api.post('/carrier/import/zones', formData, {
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

    await loadZones()
  } catch (error) {
    console.error('Import failed:', error)
    importResult.value = {
      success: false,
      message: error.response?.data?.error || t('carrierZones.import.error')
    }
  } finally {
    importLoading.value = false
  }
}
</script>

<template>
  <div class="carrier-zones">
    <AppHeader />

    <main class="dashboard-main">
      <div class="container">
        <div class="page-header">
          <div class="page-title">
            <h1>{{ t('carrierZones.title') }}</h1>
            <p class="subtitle">{{ t('carrierZones.subtitle') }}</p>
          </div>
          <div class="header-actions">
            <button class="btn btn-outline" @click="openImportModal">
              <Upload :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('carrierZones.importFromExcel') }}
            </button>
            <button class="btn btn-primary" @click="openAddModal">
              <Plus :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('carrierZones.addZone') }}
            </button>
          </div>
        </div>

        <!-- Search -->
        <div class="search-bar">
          <Search :size="18" :stroke-width="iconStrokeWidth" class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="t('carrierZones.searchPlaceholder')"
            class="search-input"
          />
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>{{ t('carrierZones.loading') }}</p>
        </div>

        <!-- Zones List -->
        <div v-else-if="filteredZones.length" class="zones-list">
          <div
            v-for="zone in filteredZones"
            :key="zone.id"
            class="zone-card"
            :class="{ expanded: expandedZone === zone.id }"
          >
            <div class="zone-header" @click="toggleZone(zone.id)">
              <div class="zone-icon">
                <Globe :size="20" :stroke-width="iconStrokeWidth" />
              </div>
              <div class="zone-info">
                <div class="zone-title">
                  <span class="zone-code">{{ zone.zone_code }}</span>
                  <span class="zone-name">{{ zone.zone_name }}</span>
                </div>
                <div class="zone-meta">
                  <span class="country-badge">{{ getCountryName(zone.country_code) }}</span>
                  <span class="postal-count">
                    {{ zone.postal_codes?.length || 0 }} {{ t('carrierZones.postalCodes') }}
                  </span>
                </div>
              </div>
              <div class="zone-actions">
                <button class="btn-icon" @click.stop="openEditModal(zone)" :title="t('common.edit')">
                  <Edit2 :size="18" :stroke-width="iconStrokeWidth" />
                </button>
                <button class="btn-icon btn-icon-danger" @click.stop="deleteZone(zone.id)" :title="t('common.delete')">
                  <Trash2 :size="18" :stroke-width="iconStrokeWidth" />
                </button>
                <div class="expand-icon">
                  <ChevronDown
                    v-if="expandedZone !== zone.id"
                    :size="20"
                    :stroke-width="iconStrokeWidth"
                  />
                  <ChevronUp v-else :size="20" :stroke-width="iconStrokeWidth" />
                </div>
              </div>
            </div>

            <div v-if="expandedZone === zone.id" class="zone-details">
              <p class="zone-description" v-if="zone.description">{{ zone.description }}</p>

              <div class="postal-codes-section">
                <h4>{{ t('carrierZones.postalCodesAndCities') }}</h4>
                <div class="postal-codes-grid">
                  <div
                    v-for="pc in zone.postal_codes"
                    :key="pc.id"
                    class="postal-code-item"
                    :class="{ 'remote-area': pc.is_remote_area }"
                  >
                    <div class="postal-prefix">{{ pc.postal_code_prefix }}xxx</div>
                    <div class="postal-city">{{ pc.city }}</div>
                    <div class="postal-region">{{ pc.region }}</div>
                    <span v-if="pc.is_remote_area" class="remote-badge">
                      <MapPin :size="12" :stroke-width="iconStrokeWidth" />
                      {{ t('carrierZones.remoteArea') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <Globe :size="48" :stroke-width="iconStrokeWidth" />
          <h3>{{ t('carrierZones.noZones') }}</h3>
          <p>{{ t('carrierZones.addFirstZone') }}</p>
          <button class="btn btn-primary" @click="openAddModal">
            <Plus :size="18" :stroke-width="iconStrokeWidth" />
            {{ t('carrierZones.addZone') }}
          </button>
        </div>
      </div>
    </main>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingZone ? t('carrierZones.editZone') : t('carrierZones.newZone') }}</h2>
          <button class="btn-close" @click="closeModal">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label>{{ t('carrierZones.form.country') }}</label>
            <div class="country-autocomplete">
              <input
                v-model="countrySearch"
                type="text"
                :placeholder="t('carrierZones.form.countryPlaceholder')"
                class="form-input"
                @focus="onCountrySearchFocus"
                @blur="onCountrySearchBlur"
                autocomplete="off"
              />
              <div v-if="showCountryDropdown && filteredCountries.length" class="country-dropdown">
                <div
                  v-for="country in filteredCountries"
                  :key="country.code"
                  class="country-option"
                  @mousedown="selectCountry(country)"
                >
                  <span class="country-code">{{ country.code }}</span>
                  <span class="country-name">{{ country.name }}</span>
                </div>
              </div>
            </div>
            <span class="form-hint">{{ t('carrierZones.form.countryHint') }}</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>{{ t('carrierZones.form.zoneCode') }}</label>
              <input
                v-model="newZone.zone_code"
                type="text"
                placeholder="KZ"
                class="form-input"
              />
            </div>
            <div class="form-group">
              <label>{{ t('carrierZones.form.zoneName') }}</label>
              <input
                v-model="newZone.zone_name"
                type="text"
                :placeholder="t('carrierZones.form.zoneNamePlaceholder')"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-group">
            <label>{{ t('carrierZones.form.description') }}</label>
            <textarea
              v-model="newZone.description"
              :placeholder="t('carrierZones.form.descriptionPlaceholder')"
              class="form-input"
              rows="2"
            ></textarea>
          </div>

          <div class="postal-codes-form">
            <h4>{{ t('carrierZones.form.postalCodes') }}</h4>

            <div class="add-postal-row">
              <input
                v-model="newPostalCode.postal_code_prefix"
                type="text"
                :placeholder="t('carrierZones.form.prefix')"
                class="form-input"
              />
              <input
                v-model="newPostalCode.city"
                type="text"
                :placeholder="t('carrierZones.form.city')"
                class="form-input"
              />
              <input
                v-model="newPostalCode.region"
                type="text"
                :placeholder="t('carrierZones.form.region')"
                class="form-input"
              />
              <label class="checkbox-label">
                <input type="checkbox" v-model="newPostalCode.is_remote_area" />
                {{ t('carrierZones.form.remote') }}
              </label>
              <button class="btn btn-secondary" @click="addPostalCode">
                <Plus :size="16" :stroke-width="iconStrokeWidth" />
              </button>
            </div>

            <div v-if="newZone.postal_codes.length" class="postal-codes-list">
              <div
                v-for="(pc, index) in newZone.postal_codes"
                :key="pc.id || index"
                class="postal-item"
              >
                <span class="postal-prefix">{{ pc.postal_code_prefix }}</span>
                <span class="postal-city">{{ pc.city }}</span>
                <span class="postal-region">{{ pc.region }}</span>
                <span v-if="pc.is_remote_area" class="remote-badge-small">{{ t('carrierZones.form.remote') }}</span>
                <button class="btn-remove" @click="removePostalCode(index)">
                  <X :size="14" :stroke-width="iconStrokeWidth" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline" @click="closeModal">{{ t('common.cancel') }}</button>
          <button class="btn btn-primary" @click="saveZone">
            <Save :size="16" :stroke-width="iconStrokeWidth" />
            {{ editingZone ? t('common.save') : t('carrierZones.createZone') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="modal-overlay" @click.self="closeImportModal">
      <div class="modal import-modal">
        <div class="modal-header">
          <h2>{{ t('carrierZones.import.title') }}</h2>
          <button class="btn-close" @click="closeImportModal">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="import-info">
            <FileSpreadsheet :size="48" :stroke-width="iconStrokeWidth" class="import-icon" />
            <h3>{{ t('carrierZones.import.uploadFile') }}</h3>
            <p>{{ t('carrierZones.import.fileFormat') }}</p>
            <ul class="columns-list">
              <li><strong>zone_code</strong> - {{ t('carrierZones.import.zoneCode') }}</li>
              <li><strong>zone_name</strong> - {{ t('carrierZones.import.zoneName') }}</li>
              <li><strong>country_code</strong> - {{ t('carrierZones.import.countryCode') }}</li>
              <li><strong>description</strong> - {{ t('carrierZones.import.description') }}</li>
            </ul>
          </div>

          <div class="file-upload">
            <input
              type="file"
              id="import-file"
              accept=".xlsx,.xls,.csv"
              @change="handleFileSelect"
              class="file-input"
            />
            <label for="import-file" class="file-label">
              <Upload :size="20" :stroke-width="iconStrokeWidth" />
              <span v-if="importFile">{{ importFile.name }}</span>
              <span v-else>{{ t('carrierZones.import.selectFile') }}</span>
            </label>
          </div>

          <div v-if="importResult" class="import-result" :class="{ success: importResult.success, error: !importResult.success }">
            <AlertCircle :size="20" :stroke-width="iconStrokeWidth" />
            <div class="result-content">
              <p>{{ importResult.message }}</p>
              <p v-if="importResult.success">
                {{ t('carrierZones.import.imported') }}: {{ importResult.imported }}, {{ t('carrierZones.import.skipped') }}: {{ importResult.skipped }}
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
            <span v-if="importLoading" class="spinner-small"></span>
            {{ importLoading ? t('carrierZones.import.importing') : t('carrierZones.import.importButton') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.carrier-zones {
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

.btn-secondary {
  background: $bg-hover;
  color: $text-primary;

  &:hover {
    background: $border-color;
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
  width: 36px;
  height: 36px;
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

.search-bar {
  position: relative;
  margin-bottom: $spacing-lg;
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

.zones-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.zone-card {
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-lg;
  overflow: hidden;
  transition: all $transition-base;

  &:hover {
    border-color: $border-color-dark;
  }

  &.expanded {
    border-color: $color-primary;
  }
}

.zone-header {
  padding: $spacing-md $spacing-lg;
  display: flex;
  align-items: center;
  gap: $spacing-md;
  cursor: pointer;

  &:hover {
    background: $bg-light;
  }
}

.zone-icon {
  width: 40px;
  height: 40px;
  background: rgba($color-primary, 0.1);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  svg {
    color: $color-primary;
  }
}

.zone-info {
  flex: 1;
  min-width: 0;
}

.zone-title {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  margin-bottom: $spacing-xs;
}

.zone-code {
  background: $bg-light;
  padding: 2px $spacing-sm;
  border-radius: $radius-sm;
  font-size: $font-size-xs;
  font-weight: 600;
  color: $text-secondary;
}

.zone-name {
  font-weight: 600;
  color: $text-primary;
}

.zone-meta {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  font-size: $font-size-sm;
  color: $text-secondary;
}

.country-badge {
  background: rgba($color-secondary, 0.1);
  color: $color-secondary;
  padding: 2px $spacing-sm;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;
}

.zone-actions {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
}

.expand-icon {
  color: $text-muted;
  margin-left: $spacing-sm;
}

.zone-details {
  padding: $spacing-lg;
  border-top: 1px solid $border-color;
  background: $bg-light;
}

.zone-description {
  color: $text-secondary;
  font-size: $font-size-sm;
  margin: 0 0 $spacing-md;
}

.postal-codes-section {
  h4 {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-md;
  }
}

.postal-codes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: $spacing-sm;
}

.postal-code-item {
  background: $bg-white;
  padding: $spacing-sm $spacing-md;
  border-radius: $radius-md;
  border: 1px solid $border-color;

  &.remote-area {
    border-color: $color-warning;
    background: rgba($color-warning, 0.05);
  }
}

.postal-prefix {
  font-weight: 600;
  color: $text-primary;
  font-size: $font-size-sm;
}

.postal-city {
  color: $text-primary;
  font-size: $font-size-sm;
}

.postal-region {
  color: $text-secondary;
  font-size: $font-size-xs;
}

.remote-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: $spacing-xs;
  padding: 2px $spacing-sm;
  background: rgba($color-warning, 0.1);
  color: $color-warning;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;
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
  max-width: 600px;
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
  grid-template-columns: 1fr 2fr;
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

.form-hint {
  display: block;
  font-size: $font-size-xs;
  color: $text-muted;
  margin-top: $spacing-xs;
}

textarea.form-input {
  resize: vertical;
}

// Country autocomplete
.country-autocomplete {
  position: relative;
}

.country-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 250px;
  overflow-y: auto;
  background: $bg-white;
  border: 1px solid $border-color;
  border-top: none;
  border-radius: 0 0 $radius-md $radius-md;
  box-shadow: $shadow;
  z-index: 100;
}

.country-option {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-sm $spacing-md;
  cursor: pointer;
  transition: background $transition-fast;

  &:hover {
    background: $bg-light;
  }

  .country-code {
    font-weight: 600;
    color: $color-primary;
    font-size: $font-size-xs;
    min-width: 28px;
    padding: 2px 4px;
    background: rgba($color-primary, 0.1);
    border-radius: $radius-sm;
    text-align: center;
  }

  .country-name {
    color: $text-primary;
    font-size: $font-size-sm;
  }
}

.postal-codes-form {
  margin-top: $spacing-lg;
  padding-top: $spacing-lg;
  border-top: 1px solid $border-color;

  h4 {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-md;
  }
}

.add-postal-row {
  display: grid;
  grid-template-columns: 100px 1fr 1fr auto auto;
  gap: $spacing-sm;
  align-items: center;
  margin-bottom: $spacing-md;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  font-size: $font-size-sm;
  color: $text-secondary;
  white-space: nowrap;

  input {
    cursor: pointer;
  }
}

.postal-codes-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.postal-item {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-sm $spacing-md;
  background: $bg-light;
  border-radius: $radius-md;
  font-size: $font-size-sm;

  .postal-prefix {
    font-weight: 600;
    color: $color-primary;
    min-width: 60px;
  }

  .postal-city {
    color: $text-primary;
    min-width: 100px;
  }

  .postal-region {
    flex: 1;
    color: $text-secondary;
  }
}

.remote-badge-small {
  background: rgba($color-warning, 0.1);
  color: $color-warning;
  padding: 2px $spacing-sm;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;
}

.btn-remove {
  width: 24px;
  height: 24px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: $radius-sm;
  display: flex;
  align-items: center;
  justify-content: center;
  color: $text-muted;

  &:hover {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }
}

// Import Modal Styles
.import-modal {
  max-width: 500px;
}

.import-info {
  text-align: center;
  padding: $spacing-lg 0;

  .import-icon {
    color: $color-primary;
    margin-bottom: $spacing-md;
  }

  h3 {
    font-size: $font-size-base;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-sm;
  }

  p {
    color: $text-secondary;
    font-size: $font-size-sm;
    margin: 0 0 $spacing-md;
  }
}

.columns-list {
  text-align: left;
  background: $bg-light;
  padding: $spacing-md;
  border-radius: $radius-md;
  margin: 0;
  list-style: none;

  li {
    font-size: $font-size-sm;
    color: $text-secondary;
    padding: $spacing-xs 0;

    strong {
      color: $text-primary;
      font-family: monospace;
    }
  }
}

.file-upload {
  margin-top: $spacing-lg;
}

.file-input {
  display: none;
}

.file-label {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: $spacing-lg;
  border: 2px dashed $border-color;
  border-radius: $radius-md;
  cursor: pointer;
  transition: all $transition-fast;
  color: $text-secondary;
  font-size: $font-size-sm;

  &:hover {
    border-color: $color-primary;
    background: rgba($color-primary, 0.05);
  }
}

.import-result {
  display: flex;
  align-items: flex-start;
  gap: $spacing-sm;
  margin-top: $spacing-lg;
  padding: $spacing-md;
  border-radius: $radius-md;

  &.success {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.error {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }

  .result-content {
    p {
      margin: 0;
      font-size: $font-size-sm;

      &:first-child {
        font-weight: 500;
      }
    }
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

.header-actions {
  display: flex;
  gap: $spacing-sm;
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
    flex-direction: column;
    width: 100%;

    .btn {
      width: 100%;
    }
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .add-postal-row {
    grid-template-columns: 1fr 1fr;

    .btn {
      grid-column: span 2;
    }
  }

  .postal-codes-grid {
    grid-template-columns: 1fr;
  }
}
</style>
