<script setup>
import { ref, onMounted } from 'vue'
import {
  Settings,
  Globe,
  Percent,
  Bell,
  Mail,
  Shield,
  Database,
  Save,
  RefreshCw
} from 'lucide-vue-next'

const iconStrokeWidth = 1.5
const loading = ref(false)
const saving = ref(false)
const activeTab = ref('general')

// Настройки
const settings = ref({
  // Общие
  site_name: 'Vector Express',
  site_description: 'Метапоисковик логистических услуг',
  contact_email: 'info@vectorexpress.kz',
  contact_phone: '+7 (777) 123-45-67',

  // Комиссия
  commission_rate: 5,
  min_order_amount: 100,
  currency: 'USD',

  // Уведомления
  email_notifications: true,
  order_notifications: true,
  payment_notifications: true,

  // Безопасность
  require_email_verification: true,
  require_company_verification: true,
  session_lifetime: 120
})

const tabs = [
  { id: 'general', name: 'Общие', icon: Globe },
  { id: 'commission', name: 'Комиссия', icon: Percent },
  { id: 'notifications', name: 'Уведомления', icon: Bell },
  { id: 'email', name: 'Email', icon: Mail },
  { id: 'security', name: 'Безопасность', icon: Shield },
  { id: 'system', name: 'Система', icon: Database }
]

onMounted(() => {
  // TODO: Fetch settings from API
  loading.value = false
})

async function saveSettings() {
  saving.value = true
  // TODO: Save settings to API
  setTimeout(() => {
    saving.value = false
  }, 1000)
}
</script>

<template>
  <div class="settings-page">
    <div class="page-header">
      <div>
        <h1>Настройки</h1>
        <p class="subtitle">Управление настройками платформы</p>
      </div>
      <button class="btn btn-primary" @click="saveSettings" :disabled="saving">
        <Save :size="16" :stroke-width="iconStrokeWidth" />
        {{ saving ? 'Сохранение...' : 'Сохранить' }}
      </button>
    </div>

    <div class="settings-container">
      <!-- Sidebar -->
      <div class="settings-sidebar">
        <nav class="settings-nav">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            class="nav-item"
            :class="{ active: activeTab === tab.id }"
            @click="activeTab = tab.id"
          >
            <component :is="tab.icon" :size="18" :stroke-width="iconStrokeWidth" />
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Content -->
      <div class="settings-content">
        <!-- Общие настройки -->
        <div v-if="activeTab === 'general'" class="settings-section">
          <h2>Общие настройки</h2>
          <p class="section-description">Основная информация о платформе</p>

          <div class="form-group">
            <label>Название сайта</label>
            <input type="text" v-model="settings.site_name" class="form-input" />
          </div>

          <div class="form-group">
            <label>Описание</label>
            <textarea v-model="settings.site_description" class="form-input" rows="3"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Контактный email</label>
              <input type="email" v-model="settings.contact_email" class="form-input" />
            </div>
            <div class="form-group">
              <label>Контактный телефон</label>
              <input type="tel" v-model="settings.contact_phone" class="form-input" />
            </div>
          </div>
        </div>

        <!-- Комиссия -->
        <div v-if="activeTab === 'commission'" class="settings-section">
          <h2>Настройки комиссии</h2>
          <p class="section-description">Параметры комиссионных сборов</p>

          <div class="form-row">
            <div class="form-group">
              <label>Размер комиссии (%)</label>
              <input type="number" v-model="settings.commission_rate" class="form-input" min="0" max="100" step="0.1" />
            </div>
            <div class="form-group">
              <label>Мин. сумма заказа</label>
              <input type="number" v-model="settings.min_order_amount" class="form-input" min="0" />
            </div>
          </div>

          <div class="form-group">
            <label>Валюта по умолчанию</label>
            <select v-model="settings.currency" class="form-input">
              <option value="USD">USD - Доллар США</option>
              <option value="EUR">EUR - Евро</option>
              <option value="KZT">KZT - Тенге</option>
              <option value="RUB">RUB - Рубль</option>
            </select>
          </div>
        </div>

        <!-- Уведомления -->
        <div v-if="activeTab === 'notifications'" class="settings-section">
          <h2>Уведомления</h2>
          <p class="section-description">Настройки системных уведомлений</p>

          <div class="toggle-group">
            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">Email уведомления</span>
                <span class="toggle-description">Отправлять уведомления на email пользователей</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.email_notifications" />
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">Уведомления о заказах</span>
                <span class="toggle-description">Уведомлять о новых заказах и изменении статусов</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.order_notifications" />
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">Уведомления об оплате</span>
                <span class="toggle-description">Уведомлять о платежах и счетах</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.payment_notifications" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Email настройки -->
        <div v-if="activeTab === 'email'" class="settings-section">
          <h2>Настройки Email</h2>
          <p class="section-description">Конфигурация почтового сервера</p>

          <div class="info-card">
            <Mail :size="24" :stroke-width="iconStrokeWidth" />
            <div>
              <h4>Настройки email</h4>
              <p>Конфигурация почтового сервера выполняется через файл .env на сервере.</p>
            </div>
          </div>
        </div>

        <!-- Безопасность -->
        <div v-if="activeTab === 'security'" class="settings-section">
          <h2>Безопасность</h2>
          <p class="section-description">Параметры безопасности платформы</p>

          <div class="toggle-group">
            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">Верификация email</span>
                <span class="toggle-description">Требовать подтверждение email при регистрации</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.require_email_verification" />
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">Верификация компаний</span>
                <span class="toggle-description">Требовать верификацию компаний перевозчиков</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.require_company_verification" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>Время жизни сессии (минуты)</label>
            <input type="number" v-model="settings.session_lifetime" class="form-input" min="1" />
          </div>
        </div>

        <!-- Система -->
        <div v-if="activeTab === 'system'" class="settings-section">
          <h2>Система</h2>
          <p class="section-description">Системная информация и действия</p>

          <div class="system-info">
            <div class="info-row">
              <span class="info-label">Версия приложения</span>
              <span class="info-value">1.0.0</span>
            </div>
            <div class="info-row">
              <span class="info-label">Версия Laravel</span>
              <span class="info-value">11.x</span>
            </div>
            <div class="info-row">
              <span class="info-label">Версия PHP</span>
              <span class="info-value">8.2</span>
            </div>
            <div class="info-row">
              <span class="info-label">База данных</span>
              <span class="info-value">MySQL 8.0</span>
            </div>
          </div>

          <div class="system-actions">
            <button class="btn btn-secondary">
              <RefreshCw :size="16" :stroke-width="iconStrokeWidth" />
              Очистить кэш
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.settings-page {
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

.btn {
  display: inline-flex;
  align-items: center;
  gap: $spacing-xs;
  padding: $spacing-sm $spacing-md;
  font-size: $font-size-sm;
  font-weight: 500;
  border-radius: $radius-lg;
  cursor: pointer;
  transition: all $transition-fast;
  border: none;

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.btn-primary {
  background: $color-primary;
  color: white;

  &:hover:not(:disabled) {
    background: darken($color-primary, 5%);
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

.settings-container {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: $spacing-lg;
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-xl;
  overflow: hidden;
}

.settings-sidebar {
  background: $bg-light;
  padding: $spacing-md;
  border-right: 1px solid $border-color;
}

.settings-nav {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-sm $spacing-md;
  font-size: $font-size-sm;
  color: $text-secondary;
  background: none;
  border: none;
  border-radius: $radius-md;
  cursor: pointer;
  transition: all $transition-fast;
  text-align: left;

  &:hover {
    background: $bg-hover;
    color: $text-primary;
  }

  &.active {
    background: $bg-white;
    color: $color-primary;
    font-weight: 500;
    box-shadow: $shadow-sm;
  }
}

.settings-content {
  padding: $spacing-xl;
}

.settings-section {
  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-xs;
  }

  .section-description {
    font-size: $font-size-sm;
    color: $text-secondary;
    margin: 0 0 $spacing-lg;
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

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-md;
}

.form-input {
  width: 100%;
  padding: $spacing-sm $spacing-md;
  font-size: $font-size-sm;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  background: $bg-white;
  transition: all $transition-fast;

  &:focus {
    outline: none;
    border-color: $color-primary;
    box-shadow: 0 0 0 3px rgba($color-primary, 0.1);
  }
}

textarea.form-input {
  resize: vertical;
  min-height: 80px;
}

select.form-input {
  cursor: pointer;
}

.toggle-group {
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
}

.toggle-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-md;
  background: $bg-light;
  border-radius: $radius-lg;
}

.toggle-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.toggle-label {
  font-size: $font-size-sm;
  font-weight: 500;
  color: $text-primary;
}

.toggle-description {
  font-size: $font-size-xs;
  color: $text-secondary;
}

.toggle {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
  flex-shrink: 0;

  input {
    opacity: 0;
    width: 0;
    height: 0;

    &:checked + .toggle-slider {
      background: $color-primary;

      &:before {
        transform: translateX(20px);
      }
    }
  }
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: $border-color;
  border-radius: 24px;
  transition: all $transition-fast;

  &:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: all $transition-fast;
    box-shadow: $shadow-sm;
  }
}

.info-card {
  display: flex;
  align-items: flex-start;
  gap: $spacing-md;
  padding: $spacing-lg;
  background: rgba($color-primary, 0.05);
  border: 1px solid rgba($color-primary, 0.1);
  border-radius: $radius-lg;
  color: $color-primary;

  h4 {
    font-size: $font-size-sm;
    font-weight: 600;
    margin: 0 0 $spacing-xs;
  }

  p {
    font-size: $font-size-sm;
    margin: 0;
    opacity: 0.8;
  }
}

.system-info {
  background: $bg-light;
  border-radius: $radius-lg;
  padding: $spacing-md;
  margin-bottom: $spacing-lg;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-sm 0;

  &:not(:last-child) {
    border-bottom: 1px solid $border-color;
  }
}

.info-label {
  font-size: $font-size-sm;
  color: $text-secondary;
}

.info-value {
  font-size: $font-size-sm;
  font-weight: 500;
  color: $text-primary;
}

.system-actions {
  display: flex;
  gap: $spacing-sm;
}

@media (max-width: 768px) {
  .settings-container {
    grid-template-columns: 1fr;
  }

  .settings-sidebar {
    border-right: none;
    border-bottom: 1px solid $border-color;
  }

  .settings-nav {
    flex-direction: row;
    flex-wrap: wrap;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
