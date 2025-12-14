<script setup>
import { onMounted, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import api from '@/api/client'
import {
  TrendingUp,
  Plus,
  Edit2,
  Trash2,
  Search,
  X,
  Save,
  Fuel,
  Home,
  MapPin,
  Package,
  AlertTriangle,
  ToggleLeft,
  ToggleRight
} from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'

const iconStrokeWidth = 1.2

const { t } = useI18n()
const authStore = useAuthStore()

const surcharges = ref([])
const loading = ref(true)
const searchQuery = ref('')
const showAddModal = ref(false)
const editingSurcharge = ref(null)

const newSurcharge = ref({
  surcharge_type: '',
  name: '',
  calculation_type: 'flat',
  value: 0,
  currency: 'USD',
  is_active: true
})

const surchargeTypes = computed(() => [
  { value: 'fuel', label: t('carrierSurcharges.types.fuel'), icon: Fuel },
  { value: 'residential', label: t('carrierSurcharges.types.residential'), icon: Home },
  { value: 'remote_area', label: t('carrierSurcharges.types.remoteArea'), icon: MapPin },
  { value: 'oversize', label: t('carrierSurcharges.types.oversize'), icon: Package },
  { value: 'dangerous_goods', label: t('carrierSurcharges.types.dangerousGoods'), icon: AlertTriangle },
  { value: 'peak_season', label: t('carrierSurcharges.types.peakSeason'), icon: TrendingUp },
  { value: 'handling', label: t('carrierSurcharges.types.handling'), icon: Package },
  { value: 'customs', label: t('carrierSurcharges.types.customs'), icon: Package }
])

const calculationTypes = computed(() => [
  { value: 'flat', label: t('carrierSurcharges.calculationTypes.flat') },
  { value: 'percentage', label: t('carrierSurcharges.calculationTypes.percentage') },
  { value: 'per_kg', label: t('carrierSurcharges.calculationTypes.perKg') }
])

const filteredSurcharges = computed(() => {
  if (!searchQuery.value) return surcharges.value
  const query = searchQuery.value.toLowerCase()
  return surcharges.value.filter(
    (s) =>
      s.name.toLowerCase().includes(query) ||
      s.surcharge_type.toLowerCase().includes(query)
  )
})

onMounted(async () => {
  await loadSurcharges()
})

async function loadSurcharges() {
  loading.value = true
  try {
    const response = await api.get('/carrier/surcharges')
    surcharges.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load surcharges:', error)
    // Mock data
    surcharges.value = [
      {
        id: 1,
        surcharge_type: 'fuel',
        name: t('carrierSurcharges.types.fuel'),
        calculation_type: 'percentage',
        value: 15.5,
        currency: 'USD',
        is_active: true
      },
      {
        id: 2,
        surcharge_type: 'residential',
        name: t('carrierSurcharges.types.residential'),
        calculation_type: 'flat',
        value: 8.0,
        currency: 'USD',
        is_active: true
      },
      {
        id: 3,
        surcharge_type: 'remote_area',
        name: t('carrierSurcharges.types.remoteArea'),
        calculation_type: 'flat',
        value: 25.0,
        currency: 'USD',
        is_active: true
      },
      {
        id: 4,
        surcharge_type: 'oversize',
        name: t('carrierSurcharges.types.oversize'),
        calculation_type: 'flat',
        value: 50.0,
        currency: 'USD',
        is_active: true
      },
      {
        id: 5,
        surcharge_type: 'dangerous_goods',
        name: t('carrierSurcharges.types.dangerousGoods'),
        calculation_type: 'flat',
        value: 75.0,
        currency: 'USD',
        is_active: false
      }
    ]
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editingSurcharge.value = null
  newSurcharge.value = {
    surcharge_type: '',
    name: '',
    calculation_type: 'flat',
    value: 0,
    currency: 'USD',
    is_active: true
  }
  showAddModal.value = true
}

function openEditModal(surcharge) {
  editingSurcharge.value = surcharge.id
  newSurcharge.value = {
    surcharge_type: surcharge.surcharge_type,
    name: surcharge.name,
    calculation_type: surcharge.calculation_type,
    value: surcharge.value,
    currency: surcharge.currency || 'USD',
    is_active: surcharge.is_active
  }
  showAddModal.value = true
}

function closeModal() {
  showAddModal.value = false
  editingSurcharge.value = null
}

function onTypeSelect() {
  const selectedType = surchargeTypes.value.find((t) => t.value === newSurcharge.value.surcharge_type)
  if (selectedType && !newSurcharge.value.name) {
    newSurcharge.value.name = selectedType.label
  }
}

async function saveSurcharge() {
  try {
    if (editingSurcharge.value) {
      await api.put(`/carrier/surcharges/${editingSurcharge.value}`, newSurcharge.value)
    } else {
      await api.post('/carrier/surcharges', newSurcharge.value)
    }
    closeModal()
    await loadSurcharges()
  } catch (error) {
    console.error('Failed to save surcharge:', error)
    // For development
    if (editingSurcharge.value) {
      const index = surcharges.value.findIndex((s) => s.id === editingSurcharge.value)
      if (index !== -1) {
        surcharges.value[index] = { ...surcharges.value[index], ...newSurcharge.value }
      }
    } else {
      surcharges.value.push({
        id: Date.now(),
        ...newSurcharge.value
      })
    }
    closeModal()
  }
}

async function deleteSurcharge(surchargeId) {
  if (!confirm(t('carrierSurcharges.confirmDelete'))) return

  try {
    await api.delete(`/carrier/surcharges/${surchargeId}`)
    await loadSurcharges()
  } catch (error) {
    console.error('Failed to delete surcharge:', error)
    surcharges.value = surcharges.value.filter((s) => s.id !== surchargeId)
  }
}

async function toggleSurchargeStatus(surcharge) {
  surcharge.is_active = !surcharge.is_active
  try {
    await api.put(`/carrier/surcharges/${surcharge.id}`, { is_active: surcharge.is_active })
  } catch (error) {
    console.error('Failed to update surcharge status:', error)
  }
}

function getSurchargeIcon(type) {
  const surchargeType = surchargeTypes.value.find((t) => t.value === type)
  return surchargeType?.icon || TrendingUp
}

function getCalculationLabel(type) {
  const calcType = calculationTypes.value.find((t) => t.value === type)
  return calcType?.label || type
}

function formatValue(surcharge) {
  const val = Number(surcharge.value) || 0
  if (surcharge.calculation_type === 'percentage') {
    return `${val}%`
  } else if (surcharge.calculation_type === 'per_kg') {
    return `$${val.toFixed(2)}/${t('carrierSurcharges.perKg')}`
  }
  return `$${val.toFixed(2)}`
}

async function handleLogout() {
  await authStore.logout()
}
</script>

<template>
  <div class="carrier-surcharges">
    <AppHeader />

    <main class="dashboard-main">
      <div class="container">
        <div class="page-header">
          <div class="page-title">
            <h1>{{ t('carrierSurcharges.title') }}</h1>
            <p class="subtitle">{{ t('carrierSurcharges.subtitle') }}</p>
          </div>
          <button class="btn btn-primary" @click="openAddModal">
            <Plus :size="18" :stroke-width="iconStrokeWidth" />
            {{ t('carrierSurcharges.addSurcharge') }}
          </button>
        </div>

        <!-- Search -->
        <div class="search-bar">
          <Search :size="18" :stroke-width="iconStrokeWidth" class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="t('carrierSurcharges.searchPlaceholder')"
            class="search-input"
          />
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>{{ t('carrierSurcharges.loading') }}</p>
        </div>

        <!-- Surcharges Grid -->
        <div v-else-if="filteredSurcharges.length" class="surcharges-grid">
          <div
            v-for="surcharge in filteredSurcharges"
            :key="surcharge.id"
            class="surcharge-card"
            :class="{ inactive: !surcharge.is_active }"
          >
            <div class="surcharge-header">
              <div class="surcharge-icon">
                <component :is="getSurchargeIcon(surcharge.surcharge_type)" :size="20" :stroke-width="iconStrokeWidth" />
              </div>
              <button
                class="status-toggle"
                :class="{ active: surcharge.is_active }"
                @click="toggleSurchargeStatus(surcharge)"
                :title="surcharge.is_active ? t('carrierSurcharges.deactivate') : t('carrierSurcharges.activate')"
              >
                <ToggleRight v-if="surcharge.is_active" :size="24" :stroke-width="iconStrokeWidth" />
                <ToggleLeft v-else :size="24" :stroke-width="iconStrokeWidth" />
              </button>
            </div>

            <div class="surcharge-body">
              <h3 class="surcharge-name">{{ surcharge.name }}</h3>
              <span class="surcharge-type">{{ surcharge.surcharge_type }}</span>

              <div class="surcharge-value">
                {{ formatValue(surcharge) }}
              </div>

              <div class="surcharge-calc-type">
                {{ getCalculationLabel(surcharge.calculation_type) }}
              </div>
            </div>

            <div class="surcharge-actions">
              <button class="btn-action" @click="openEditModal(surcharge)" :title="t('common.edit')">
                <Edit2 :size="16" :stroke-width="iconStrokeWidth" />
              </button>
              <button
                class="btn-action btn-action-danger"
                @click="deleteSurcharge(surcharge.id)"
                :title="t('common.delete')"
              >
                <Trash2 :size="16" :stroke-width="iconStrokeWidth" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <TrendingUp :size="48" :stroke-width="iconStrokeWidth" />
          <h3>{{ t('carrierSurcharges.noSurcharges') }}</h3>
          <p>{{ t('carrierSurcharges.noSurchargesText') }}</p>
          <button class="btn btn-primary" @click="openAddModal">
            <Plus :size="18" :stroke-width="iconStrokeWidth" />
            {{ t('carrierSurcharges.addSurcharge') }}
          </button>
        </div>
      </div>
    </main>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingSurcharge ? t('carrierSurcharges.editSurcharge') : t('carrierSurcharges.newSurcharge') }}</h2>
          <button class="btn-close" @click="closeModal">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label>{{ t('carrierSurcharges.form.surchargeType') }}</label>
            <select v-model="newSurcharge.surcharge_type" class="form-input" @change="onTypeSelect">
              <option value="">{{ t('carrierSurcharges.form.selectType') }}</option>
              <option v-for="type in surchargeTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>{{ t('carrierSurcharges.form.name') }}</label>
            <input
              v-model="newSurcharge.name"
              type="text"
              :placeholder="t('carrierSurcharges.form.namePlaceholder')"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label>{{ t('carrierSurcharges.form.calculationType') }}</label>
            <select v-model="newSurcharge.calculation_type" class="form-input">
              <option v-for="type in calculationTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>
              {{ newSurcharge.calculation_type === 'percentage' ? t('carrierSurcharges.form.percentage') : t('carrierSurcharges.form.amount') }}
            </label>
            <input
              v-model.number="newSurcharge.value"
              type="number"
              min="0"
              :step="newSurcharge.calculation_type === 'percentage' ? '0.1' : '0.01'"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="newSurcharge.is_active" />
              {{ t('carrierSurcharges.form.isActive') }}
            </label>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline" @click="closeModal">{{ t('common.cancel') }}</button>
          <button class="btn btn-primary" @click="saveSurcharge">
            <Save :size="16" :stroke-width="iconStrokeWidth" />
            {{ editingSurcharge ? t('common.save') : t('carrierSurcharges.createSurcharge') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.carrier-surcharges {
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

.surcharges-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: $spacing-md;
}

.surcharge-card {
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-lg;
  overflow: hidden;
  transition: all $transition-base;

  &:hover {
    border-color: $color-primary;
    box-shadow: $shadow;
  }

  &.inactive {
    opacity: 0.6;
    background: $bg-light;
  }
}

.surcharge-header {
  padding: $spacing-md $spacing-lg;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: $bg-light;
  border-bottom: 1px solid $border-color;
}

.surcharge-icon {
  width: 36px;
  height: 36px;
  background: rgba($color-primary, 0.1);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;

  svg {
    color: $color-primary;
  }
}

.status-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  color: $text-muted;
  transition: color $transition-fast;

  &.active {
    color: $color-success;
  }

  &:hover {
    opacity: 0.8;
  }
}

.surcharge-body {
  padding: $spacing-lg;
}

.surcharge-name {
  font-size: $font-size-base;
  font-weight: 600;
  color: $text-primary;
  margin: 0 0 $spacing-xs;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.surcharge-type {
  display: inline-block;
  font-size: $font-size-xs;
  color: $text-muted;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: $spacing-md;
}

.surcharge-value {
  font-size: $font-size-2xl;
  font-weight: 700;
  color: $color-primary;
  margin-bottom: $spacing-xs;
}

.surcharge-calc-type {
  font-size: $font-size-sm;
  color: $text-secondary;
}

.surcharge-actions {
  padding: $spacing-sm $spacing-lg;
  border-top: 1px solid $border-color;
  display: flex;
  justify-content: flex-end;
  gap: $spacing-xs;
  background: $bg-light;
}

.btn-action {
  padding: $spacing-xs $spacing-sm;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  background: $bg-white;
  color: $text-secondary;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: all $transition-base;

  &:hover {
    border-color: $color-primary;
    color: $color-primary;
  }

  &.btn-action-danger:hover {
    border-color: $color-danger;
    color: $color-danger;
  }
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
  max-width: 450px;
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

.checkbox-label {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  cursor: pointer;

  input {
    cursor: pointer;
  }
}

@media (max-width: $breakpoint-md) {
  .header-nav {
    display: none;
  }

  .page-header {
    flex-direction: column;
    gap: $spacing-md;
  }

  .surcharges-grid {
    grid-template-columns: 1fr;
  }
}
</style>
