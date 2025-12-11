<script setup>
import { onMounted, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/client'
import {
  MapPin,
  Plus,
  Edit2,
  Trash2,
  Search,
  Clock,
  Building,
  X,
  Save,
  Phone,
  Mail,
  Upload,
  FileSpreadsheet,
  AlertCircle
} from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'

const iconStrokeWidth = 1.2

const authStore = useAuthStore()

const terminals = ref([])
const loading = ref(true)
const searchQuery = ref('')
const showAddModal = ref(false)
const editingTerminal = ref(null)

const newTerminal = ref({
  terminal_code: '',
  name: '',
  type: 'hub',
  country_code: '',
  city: '',
  address: '',
  postal_code: '',
  phone: '',
  email: '',
  latitude: '',
  longitude: '',
  is_active: true,
  working_hours: {
    monday: '09:00-18:00',
    tuesday: '09:00-18:00',
    wednesday: '09:00-18:00',
    thursday: '09:00-18:00',
    friday: '09:00-18:00',
    saturday: '10:00-15:00',
    sunday: 'closed'
  }
})

const terminalTypes = [
  { value: 'hub', label: 'Хаб (основной терминал)' },
  { value: 'warehouse', label: 'Склад' },
  { value: 'pickup', label: 'Пункт приема' },
  { value: 'delivery', label: 'Пункт выдачи' }
]

const countries = [
  { code: 'KZ', name: 'Казахстан' },
  { code: 'RU', name: 'Россия' },
  { code: 'CN', name: 'Китай' },
  { code: 'UZ', name: 'Узбекистан' },
  { code: 'KG', name: 'Кыргызстан' },
  { code: 'DE', name: 'Германия' },
  { code: 'US', name: 'США' },
  { code: 'TR', name: 'Турция' }
]

const daysOfWeek = [
  { key: 'monday', label: 'Понедельник' },
  { key: 'tuesday', label: 'Вторник' },
  { key: 'wednesday', label: 'Среда' },
  { key: 'thursday', label: 'Четверг' },
  { key: 'friday', label: 'Пятница' },
  { key: 'saturday', label: 'Суббота' },
  { key: 'sunday', label: 'Воскресенье' }
]

const filteredTerminals = computed(() => {
  if (!searchQuery.value) return terminals.value
  const query = searchQuery.value.toLowerCase()
  return terminals.value.filter(
    (t) =>
      t.name?.toLowerCase().includes(query) ||
      t.terminal_code?.toLowerCase().includes(query) ||
      t.city?.toLowerCase().includes(query)
  )
})

onMounted(async () => {
  await loadTerminals()
})

async function loadTerminals() {
  loading.value = true
  try {
    const response = await api.get('/carrier/terminals')
    console.log('Terminals API response:', response.data)
    terminals.value = response.data.data || response.data || []
    console.log('Terminals loaded:', terminals.value.length, 'items')
  } catch (error) {
    console.error('Failed to load terminals:', error)
    // Mock data for development
    terminals.value = [
      {
        id: 1,
        terminal_code: 'ALM-HUB',
        name: 'Almaty Hub',
        type: 'hub',
        country_code: 'KZ',
        city: 'Алматы',
        address: 'Airport area, Almaty',
        postal_code: '050000',
        phone: '+7 727 123 45 67',
        email: 'almaty@carrier.kz',
        latitude: 43.2567,
        longitude: 76.9286,
        is_active: true,
        working_hours: {
          monday: '09:00-18:00',
          tuesday: '09:00-18:00',
          wednesday: '09:00-18:00',
          thursday: '09:00-18:00',
          friday: '09:00-18:00',
          saturday: '10:00-15:00',
          sunday: 'closed'
        }
      },
      {
        id: 2,
        terminal_code: 'AST-HUB',
        name: 'Astana Hub',
        type: 'hub',
        country_code: 'KZ',
        city: 'Астана',
        address: 'Logistics center, Astana',
        postal_code: '010000',
        phone: '+7 717 234 56 78',
        email: 'astana@carrier.kz',
        latitude: 51.1605,
        longitude: 71.4704,
        is_active: true,
        working_hours: {
          monday: '09:00-18:00',
          tuesday: '09:00-18:00',
          wednesday: '09:00-18:00',
          thursday: '09:00-18:00',
          friday: '09:00-18:00',
          saturday: '10:00-15:00',
          sunday: 'closed'
        }
      },
      {
        id: 3,
        terminal_code: 'MOW-HUB',
        name: 'Moscow Hub',
        type: 'hub',
        country_code: 'RU',
        city: 'Москва',
        address: 'Sheremetyevo Airport',
        postal_code: '141400',
        latitude: 55.9726,
        longitude: 37.4146,
        is_active: true,
        working_hours: {
          monday: '08:00-20:00',
          tuesday: '08:00-20:00',
          wednesday: '08:00-20:00',
          thursday: '08:00-20:00',
          friday: '08:00-20:00',
          saturday: '09:00-17:00',
          sunday: '10:00-15:00'
        }
      },
      {
        id: 4,
        terminal_code: 'GZ-HUB',
        name: 'Guangzhou Hub',
        type: 'hub',
        country_code: 'CN',
        city: 'Гуанчжоу',
        address: 'Baiyun Airport',
        postal_code: '510000',
        latitude: 23.3924,
        longitude: 113.2988,
        is_active: true,
        working_hours: {
          monday: '00:00-24:00',
          tuesday: '00:00-24:00',
          wednesday: '00:00-24:00',
          thursday: '00:00-24:00',
          friday: '00:00-24:00',
          saturday: '00:00-24:00',
          sunday: '00:00-24:00'
        }
      }
    ]
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editingTerminal.value = null
  newTerminal.value = {
    terminal_code: '',
    name: '',
    type: 'hub',
    country_code: '',
    city: '',
    address: '',
    postal_code: '',
    phone: '',
    email: '',
    latitude: '',
    longitude: '',
    is_active: true,
    working_hours: {
      monday: '09:00-18:00',
      tuesday: '09:00-18:00',
      wednesday: '09:00-18:00',
      thursday: '09:00-18:00',
      friday: '09:00-18:00',
      saturday: '10:00-15:00',
      sunday: 'closed'
    }
  }
  showAddModal.value = true
}

function openEditModal(terminal) {
  editingTerminal.value = terminal.id
  newTerminal.value = {
    terminal_code: terminal.terminal_code,
    name: terminal.name,
    type: terminal.type,
    country_code: terminal.country_code,
    city: terminal.city,
    address: terminal.address,
    postal_code: terminal.postal_code,
    phone: terminal.phone || '',
    email: terminal.email || '',
    latitude: terminal.latitude || '',
    longitude: terminal.longitude || '',
    is_active: terminal.is_active,
    working_hours: { ...terminal.working_hours }
  }
  showAddModal.value = true
}

function closeModal() {
  showAddModal.value = false
  editingTerminal.value = null
}

async function saveTerminal() {
  try {
    if (editingTerminal.value) {
      await api.put(`/carrier/terminals/${editingTerminal.value}`, newTerminal.value)
    } else {
      await api.post('/carrier/terminals', newTerminal.value)
    }
    closeModal()
    await loadTerminals()
  } catch (error) {
    console.error('Failed to save terminal:', error)
    // For development, just add to local state
    if (editingTerminal.value) {
      const index = terminals.value.findIndex((t) => t.id === editingTerminal.value)
      if (index !== -1) {
        terminals.value[index] = { ...terminals.value[index], ...newTerminal.value }
      }
    } else {
      terminals.value.push({
        id: Date.now(),
        ...newTerminal.value
      })
    }
    closeModal()
  }
}

async function deleteTerminal(terminalId) {
  if (!confirm('Вы уверены, что хотите удалить этот терминал?')) return

  try {
    await api.delete(`/carrier/terminals/${terminalId}`)
    await loadTerminals()
  } catch (error) {
    console.error('Failed to delete terminal:', error)
    terminals.value = terminals.value.filter((t) => t.id !== terminalId)
  }
}

async function toggleTerminalStatus(terminal) {
  terminal.is_active = !terminal.is_active
  try {
    await api.put(`/carrier/terminals/${terminal.id}`, { is_active: terminal.is_active })
  } catch (error) {
    console.error('Failed to update terminal status:', error)
  }
}

function getCountryName(code) {
  const country = countries.find((c) => c.code === code)
  return country ? country.name : code
}

function getTerminalTypeLabel(type) {
  const t = terminalTypes.find((tt) => tt.value === type)
  return t ? t.label : type
}

function formatWorkingHours(hours) {
  if (!hours) return 'Не указано'
  const today = new Date().toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase()
  return hours[today] || 'closed'
}

async function handleLogout() {
  await authStore.logout()
}

// Import functionality
const showImportModal = ref(false)
const importFile = ref(null)
const importLoading = ref(false)
const importResult = ref(null)

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

    const response = await api.post('/carrier/import/terminals', formData, {
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

    await loadTerminals()
  } catch (error) {
    console.error('Import failed:', error)
    importResult.value = {
      success: false,
      message: error.response?.data?.error || 'Ошибка импорта'
    }
  } finally {
    importLoading.value = false
  }
}
</script>

<template>
  <div class="carrier-terminals">
    <AppHeader />

    <main class="dashboard-main">
      <div class="container">
        <div class="page-header">
          <div class="page-title">
            <h1>Терминалы и точки приема</h1>
            <p class="subtitle">Управление складами, хабами и пунктами выдачи</p>
          </div>
          <div class="header-actions">
            <button class="btn btn-outline" @click="openImportModal">
              <Upload :size="18" :stroke-width="iconStrokeWidth" />
              Импорт из Excel
            </button>
            <button class="btn btn-primary" @click="openAddModal">
              <Plus :size="18" :stroke-width="iconStrokeWidth" />
              Добавить терминал
            </button>
          </div>
        </div>

        <!-- Search -->
        <div class="search-bar">
          <Search :size="18" :stroke-width="iconStrokeWidth" class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Поиск по названию или городу..."
            class="search-input"
          />
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Загрузка терминалов...</p>
        </div>

        <!-- Terminals Grid -->
        <div v-else-if="filteredTerminals.length" class="terminals-grid">
          <div
            v-for="terminal in filteredTerminals"
            :key="terminal.id"
            class="terminal-card"
            :class="{ inactive: !terminal.is_active }"
          >
            <div class="terminal-header">
              <div class="terminal-icon">
                <MapPin :size="20" :stroke-width="iconStrokeWidth" />
              </div>
              <div class="terminal-status">
                <span class="status-badge" :class="{ active: terminal.is_active }">
                  {{ terminal.is_active ? 'Активен' : 'Неактивен' }}
                </span>
              </div>
            </div>

            <div class="terminal-body">
              <div class="terminal-code">{{ terminal.terminal_code }}</div>
              <h3 class="terminal-name">{{ terminal.name }}</h3>

              <div class="terminal-type">
                <Building :size="14" :stroke-width="iconStrokeWidth" />
                {{ getTerminalTypeLabel(terminal.type) }}
              </div>

              <div class="terminal-location">
                <MapPin :size="14" :stroke-width="iconStrokeWidth" />
                <span>{{ terminal.city }}, {{ getCountryName(terminal.country_code) }}</span>
              </div>

              <div class="terminal-address">
                {{ terminal.address }}
              </div>

              <div class="terminal-hours">
                <Clock :size="14" :stroke-width="iconStrokeWidth" />
                <span>Сегодня: {{ formatWorkingHours(terminal.working_hours) }}</span>
              </div>

              <div class="terminal-contacts" v-if="terminal.phone || terminal.email">
                <div class="contact-item" v-if="terminal.phone">
                  <Phone :size="12" :stroke-width="iconStrokeWidth" />
                  {{ terminal.phone }}
                </div>
                <div class="contact-item" v-if="terminal.email">
                  <Mail :size="12" :stroke-width="iconStrokeWidth" />
                  {{ terminal.email }}
                </div>
              </div>
            </div>

            <div class="terminal-actions">
              <button class="btn-action" @click="openEditModal(terminal)" title="Редактировать">
                <Edit2 :size="16" :stroke-width="iconStrokeWidth" />
              </button>
              <button
                class="btn-action"
                @click="toggleTerminalStatus(terminal)"
                :title="terminal.is_active ? 'Деактивировать' : 'Активировать'"
              >
                <span v-if="terminal.is_active">Откл.</span>
                <span v-else>Вкл.</span>
              </button>
              <button
                class="btn-action btn-action-danger"
                @click="deleteTerminal(terminal.id)"
                title="Удалить"
              >
                <Trash2 :size="16" :stroke-width="iconStrokeWidth" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <MapPin :size="48" :stroke-width="iconStrokeWidth" />
          <h3>Нет терминалов</h3>
          <p>Добавьте первый терминал для обработки грузов</p>
          <button class="btn btn-primary" @click="openAddModal">
            <Plus :size="18" :stroke-width="iconStrokeWidth" />
            Добавить терминал
          </button>
        </div>
      </div>
    </main>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>{{ editingTerminal ? 'Редактировать терминал' : 'Новый терминал' }}</h2>
          <button class="btn-close" @click="closeModal">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-section">
            <h4>Основная информация</h4>

            <div class="form-row">
              <div class="form-group">
                <label>Код терминала</label>
                <input
                  v-model="newTerminal.terminal_code"
                  type="text"
                  placeholder="ALM-HUB"
                  class="form-input"
                />
              </div>
              <div class="form-group">
                <label>Название</label>
                <input
                  v-model="newTerminal.name"
                  type="text"
                  placeholder="Almaty Hub"
                  class="form-input"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Тип терминала</label>
                <select v-model="newTerminal.type" class="form-input">
                  <option v-for="type in terminalTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Статус</label>
                <label class="checkbox-label-inline">
                  <input type="checkbox" v-model="newTerminal.is_active" />
                  Активен
                </label>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h4>Адрес</h4>

            <div class="form-row">
              <div class="form-group">
                <label>Страна</label>
                <select v-model="newTerminal.country_code" class="form-input">
                  <option value="">Выберите страну</option>
                  <option v-for="country in countries" :key="country.code" :value="country.code">
                    {{ country.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Город</label>
                <input
                  v-model="newTerminal.city"
                  type="text"
                  placeholder="Алматы"
                  class="form-input"
                />
              </div>
            </div>

            <div class="form-group">
              <label>Адрес</label>
              <input
                v-model="newTerminal.address"
                type="text"
                placeholder="ул. Примерная, 123"
                class="form-input"
              />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Почтовый индекс</label>
                <input
                  v-model="newTerminal.postal_code"
                  type="text"
                  placeholder="050000"
                  class="form-input"
                />
              </div>
              <div class="form-group">
                <label>Широта</label>
                <input
                  v-model="newTerminal.latitude"
                  type="text"
                  placeholder="43.2567"
                  class="form-input"
                />
              </div>
              <div class="form-group">
                <label>Долгота</label>
                <input
                  v-model="newTerminal.longitude"
                  type="text"
                  placeholder="76.9286"
                  class="form-input"
                />
              </div>
            </div>
          </div>

          <div class="form-section">
            <h4>Контакты</h4>

            <div class="form-row">
              <div class="form-group">
                <label>Телефон</label>
                <input
                  v-model="newTerminal.phone"
                  type="text"
                  placeholder="+7 727 123 45 67"
                  class="form-input"
                />
              </div>
              <div class="form-group">
                <label>Email</label>
                <input
                  v-model="newTerminal.email"
                  type="email"
                  placeholder="terminal@carrier.kz"
                  class="form-input"
                />
              </div>
            </div>
          </div>

          <div class="form-section">
            <h4>Часы работы</h4>

            <div class="working-hours-grid">
              <div v-for="day in daysOfWeek" :key="day.key" class="working-hours-row">
                <label>{{ day.label }}</label>
                <input
                  v-model="newTerminal.working_hours[day.key]"
                  type="text"
                  placeholder="09:00-18:00 или closed"
                  class="form-input"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline" @click="closeModal">Отмена</button>
          <button class="btn btn-primary" @click="saveTerminal">
            <Save :size="16" :stroke-width="iconStrokeWidth" />
            {{ editingTerminal ? 'Сохранить' : 'Создать терминал' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="modal-overlay" @click.self="closeImportModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Импорт терминалов из Excel</h2>
          <button class="btn-close" @click="closeImportModal">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="import-info">
            <FileSpreadsheet :size="24" :stroke-width="iconStrokeWidth" />
            <div>
              <p><strong>Формат файла:</strong> .xlsx, .xls или .csv</p>
              <p class="import-hint">Существующие терминалы с тем же кодом будут обновлены</p>
            </div>
          </div>

          <div class="columns-info">
            <h4>Обязательные колонки:</h4>
            <ul>
              <li><code>name</code> — название терминала</li>
              <li><code>city</code> — город</li>
            </ul>
            <h4>Опциональные колонки:</h4>
            <ul>
              <li><code>terminal_code</code> — уникальный код (напр: ALM-HUB)</li>
              <li><code>type</code> — тип: hub, warehouse, pickup, delivery</li>
              <li><code>country_code</code> — код страны (KZ, RU, CN и т.д.)</li>
              <li><code>state</code> — область/регион</li>
              <li><code>address</code> — адрес</li>
              <li><code>postal_code</code> — почтовый индекс</li>
              <li><code>latitude</code> — широта</li>
              <li><code>longitude</code> — долгота</li>
              <li><code>service_radius</code> — радиус обслуживания (км)</li>
              <li><code>phone</code> — телефон</li>
              <li><code>email</code> — email</li>
              <li><code>is_active</code> — активен (1/0)</li>
              <li><code>working_hours</code> — часы работы (текст или JSON)</li>
            </ul>
          </div>

          <div class="form-group">
            <label>Выберите файл</label>
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
                Импортировано: {{ importResult.imported }}, Пропущено: {{ importResult.skipped }}
              </p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline" @click="closeImportModal">Отмена</button>
          <button
            class="btn btn-primary"
            @click="handleImport"
            :disabled="!importFile || importLoading"
          >
            <Upload v-if="!importLoading" :size="16" :stroke-width="iconStrokeWidth" />
            <span v-else class="spinner-small"></span>
            {{ importLoading ? 'Импорт...' : 'Импортировать' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.carrier-terminals {
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

.terminals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: $spacing-md;
}

.terminal-card {
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
    opacity: 0.7;
    background: $bg-light;
  }
}

.terminal-header {
  padding: $spacing-md $spacing-lg;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: $bg-light;
  border-bottom: 1px solid $border-color;
}

.terminal-icon {
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

.status-badge {
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;
  background: rgba($color-danger, 0.1);
  color: $color-danger;

  &.active {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }
}

.terminal-body {
  padding: $spacing-lg;
}

.terminal-code {
  font-size: $font-size-xs;
  font-weight: 600;
  color: $text-muted;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: $spacing-xs;
}

.terminal-name {
  font-size: $font-size-lg;
  font-weight: 600;
  color: $text-primary;
  margin: 0 0 $spacing-md;
}

.terminal-type,
.terminal-location,
.terminal-hours {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  font-size: $font-size-sm;
  color: $text-secondary;
  margin-bottom: $spacing-xs;

  svg {
    color: $text-muted;
    flex-shrink: 0;
  }
}

.terminal-address {
  font-size: $font-size-sm;
  color: $text-secondary;
  margin: $spacing-sm 0;
  padding-left: 22px;
}

.terminal-contacts {
  margin-top: $spacing-md;
  padding-top: $spacing-md;
  border-top: 1px solid $border-color;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  font-size: $font-size-xs;
  color: $text-secondary;
  margin-bottom: $spacing-xs;

  svg {
    color: $text-muted;
  }
}

.terminal-actions {
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
  font-size: $font-size-xs;
  color: $text-secondary;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: $spacing-xs;
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
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;

  &.modal-lg {
    max-width: 700px;
  }
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

.form-section {
  margin-bottom: $spacing-lg;
  padding-bottom: $spacing-lg;
  border-bottom: 1px solid $border-color;

  &:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
  }

  h4 {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-md;
  }
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-md;

  &:has(.form-group:nth-child(3)) {
    grid-template-columns: repeat(3, 1fr);
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

.checkbox-label-inline {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  font-size: $font-size-sm;
  color: $text-primary;
  cursor: pointer;
  padding: $spacing-sm 0;

  input {
    cursor: pointer;
  }
}

.working-hours-grid {
  display: grid;
  gap: $spacing-sm;
}

.working-hours-row {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: $spacing-md;
  align-items: center;

  label {
    font-size: $font-size-sm;
    color: $text-secondary;
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
  max-height: 300px;
  overflow-y: auto;

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

  .terminals-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
