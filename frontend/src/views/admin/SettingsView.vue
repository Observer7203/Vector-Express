<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()

const iconStrokeWidth = 1.5
const loading = ref(false)
const saving = ref(false)
const activeTab = ref('general')

const settings = ref({
  site_name: 'Vector Express',
  site_description: 'Метапоисковик логистических услуг',
  contact_email: 'info@vectorexpress.kz',
  contact_phone: '+7 (777) 123-45-67',
  commission_rate: 5,
  min_order_amount: 100,
  currency: 'USD',
  email_notifications: true,
  order_notifications: true,
  payment_notifications: true,
  require_email_verification: true,
  require_company_verification: true,
  session_lifetime: 120
})

const tabs = [
  { id: 'general', name: t('adminSettings.tabs.general'), icon: Globe },
  { id: 'commission', name: t('adminSettings.tabs.commission'), icon: Percent },
  { id: 'notifications', name: t('adminSettings.tabs.notifications'), icon: Bell },
  { id: 'email', name: t('adminSettings.tabs.email'), icon: Mail },
  { id: 'security', name: t('adminSettings.tabs.security'), icon: Shield },
  { id: 'system', name: t('adminSettings.tabs.system'), icon: Database }
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
        <h1>{{ t('adminSettings.title') }}</h1>
        <p class="subtitle">{{ t('adminSettings.subtitle') }}</p>
      </div>
      <button class="btn btn-primary" @click="saveSettings" :disabled="saving">
        <Save :size="16" :stroke-width="iconStrokeWidth" />
        {{ saving ? t('adminSettings.saving') : t('common.save') }}
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
        <!-- General Settings -->
        <div v-if="activeTab === 'general'" class="settings-section">
          <h2>{{ t('adminSettings.general.title') }}</h2>
          <p class="section-description">{{ t('adminSettings.general.description') }}</p>

          <div class="form-group">
            <label>{{ t('adminSettings.general.siteName') }}</label>
            <input type="text" v-model="settings.site_name" class="form-input" />
          </div>

          <div class="form-group">
            <label>{{ t('adminSettings.general.siteDescription') }}</label>
            <textarea v-model="settings.site_description" class="form-input" rows="3"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>{{ t('adminSettings.general.contactEmail') }}</label>
              <input type="email" v-model="settings.contact_email" class="form-input" />
            </div>
            <div class="form-group">
              <label>{{ t('adminSettings.general.contactPhone') }}</label>
              <input type="tel" v-model="settings.contact_phone" class="form-input" />
            </div>
          </div>
        </div>

        <!-- Commission -->
        <div v-if="activeTab === 'commission'" class="settings-section">
          <h2>{{ t('adminSettings.commission.title') }}</h2>
          <p class="section-description">{{ t('adminSettings.commission.description') }}</p>

          <div class="form-row">
            <div class="form-group">
              <label>{{ t('adminSettings.commission.commissionRate') }}</label>
              <input type="number" v-model="settings.commission_rate" class="form-input" min="0" max="100" step="0.1" />
            </div>
            <div class="form-group">
              <label>{{ t('adminSettings.commission.minOrderAmount') }}</label>
              <input type="number" v-model="settings.min_order_amount" class="form-input" min="0" />
            </div>
          </div>

          <div class="form-group">
            <label>{{ t('adminSettings.commission.defaultCurrency') }}</label>
            <select v-model="settings.currency" class="form-input">
              <option value="USD">{{ t('adminSettings.commission.currencies.usd') }}</option>
              <option value="EUR">{{ t('adminSettings.commission.currencies.eur') }}</option>
              <option value="KZT">{{ t('adminSettings.commission.currencies.kzt') }}</option>
              <option value="RUB">{{ t('adminSettings.commission.currencies.rub') }}</option>
            </select>
          </div>
        </div>

        <!-- Notifications -->
        <div v-if="activeTab === 'notifications'" class="settings-section">
          <h2>{{ t('adminSettings.notifications.title') }}</h2>
          <p class="section-description">{{ t('adminSettings.notifications.description') }}</p>

          <div class="toggle-group">
            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">{{ t('adminSettings.notifications.emailNotifications') }}</span>
                <span class="toggle-description">{{ t('adminSettings.notifications.emailNotificationsDesc') }}</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.email_notifications" />
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">{{ t('adminSettings.notifications.orderNotifications') }}</span>
                <span class="toggle-description">{{ t('adminSettings.notifications.orderNotificationsDesc') }}</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.order_notifications" />
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">{{ t('adminSettings.notifications.paymentNotifications') }}</span>
                <span class="toggle-description">{{ t('adminSettings.notifications.paymentNotificationsDesc') }}</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.payment_notifications" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Email Settings -->
        <div v-if="activeTab === 'email'" class="settings-section">
          <h2>{{ t('adminSettings.email.title') }}</h2>
          <p class="section-description">{{ t('adminSettings.email.description') }}</p>

          <div class="info-card">
            <Mail :size="24" :stroke-width="iconStrokeWidth" />
            <div>
              <h4>{{ t('adminSettings.email.infoTitle') }}</h4>
              <p>{{ t('adminSettings.email.infoText') }}</p>
            </div>
          </div>
        </div>

        <!-- Security -->
        <div v-if="activeTab === 'security'" class="settings-section">
          <h2>{{ t('adminSettings.security.title') }}</h2>
          <p class="section-description">{{ t('adminSettings.security.description') }}</p>

          <div class="toggle-group">
            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">{{ t('adminSettings.security.emailVerification') }}</span>
                <span class="toggle-description">{{ t('adminSettings.security.emailVerificationDesc') }}</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.require_email_verification" />
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <span class="toggle-label">{{ t('adminSettings.security.companyVerification') }}</span>
                <span class="toggle-description">{{ t('adminSettings.security.companyVerificationDesc') }}</span>
              </div>
              <label class="toggle">
                <input type="checkbox" v-model="settings.require_company_verification" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>{{ t('adminSettings.security.sessionLifetime') }}</label>
            <input type="number" v-model="settings.session_lifetime" class="form-input" min="1" />
          </div>
        </div>

        <!-- System -->
        <div v-if="activeTab === 'system'" class="settings-section">
          <h2>{{ t('adminSettings.system.title') }}</h2>
          <p class="section-description">{{ t('adminSettings.system.description') }}</p>

          <div class="system-info">
            <div class="info-row">
              <span class="info-label">{{ t('adminSettings.system.appVersion') }}</span>
              <span class="info-value">1.0.0</span>
            </div>
            <div class="info-row">
              <span class="info-label">{{ t('adminSettings.system.laravelVersion') }}</span>
              <span class="info-value">11.x</span>
            </div>
            <div class="info-row">
              <span class="info-label">{{ t('adminSettings.system.phpVersion') }}</span>
              <span class="info-value">8.2</span>
            </div>
            <div class="info-row">
              <span class="info-label">{{ t('adminSettings.system.database') }}</span>
              <span class="info-value">MySQL 8.0</span>
            </div>
          </div>

          <div class="system-actions">
            <button class="btn btn-secondary">
              <RefreshCw :size="16" :stroke-width="iconStrokeWidth" />
              {{ t('adminSettings.system.clearCache') }}
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

<style lang="scss">
/* Dark theme styles for SettingsView */
[data-theme="dark"] {
  .settings-page {
    h1 {
      color: #f5f5f5 !important;
    }

    .subtitle {
      color: #999999 !important;
    }

    .btn-primary {
      background: #f97316 !important;
      color: #ffffff !important;

      &:hover:not(:disabled) {
        background: #ea580c !important;
      }
    }

    .btn-secondary {
      background: #1a1a1a !important;
      border-color: #2a2a2a !important;
      color: #f5f5f5 !important;

      &:hover:not(:disabled) {
        background: #2a2a2a !important;
      }
    }

    .settings-container {
      background: #0f0f0f !important;
      border-color: #2a2a2a !important;
    }

    .settings-sidebar {
      background: #0f0f0f !important;
      border-color: #2a2a2a !important;
    }

    .nav-item {
      color: #999999 !important;

      &:hover {
        background: #1a1a1a !important;
        color: #f5f5f5 !important;
      }

      &.active {
        background: rgba(249, 115, 22, 0.1) !important;
        color: #f97316 !important;
      }
    }

    .settings-content {
      background: #0f0f0f !important;
    }

    .settings-section {
      h2 {
        color: #f97316 !important;
      }

      .section-description {
        color: #999999 !important;
      }
    }

    .form-group {
      label {
        color: #f5f5f5 !important;
      }
    }

    .form-input {
      background: #1a1a1a !important;
      border-color: #2a2a2a !important;
      color: #f5f5f5 !important;

      &::placeholder {
        color: #666666 !important;
      }

      &:focus {
        border-color: #f97316 !important;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1) !important;
      }
    }

    .toggle-item {
      background: #1a1a1a !important;
    }

    .toggle-label {
      color: #f5f5f5 !important;
    }

    .toggle-description {
      color: #999999 !important;
    }

    .toggle-slider {
      background: #2a2a2a !important;

      &:before {
        background: #666666 !important;
      }
    }

    .toggle input:checked + .toggle-slider {
      background: #f97316 !important;

      &:before {
        background: #ffffff !important;
      }
    }

    .info-card {
      background: rgba(249, 115, 22, 0.1) !important;
      border-color: rgba(249, 115, 22, 0.2) !important;
      color: #f97316 !important;

      h4 {
        color: #f97316 !important;
      }

      p {
        color: #f5f5f5 !important;
      }
    }

    .system-info {
      background: #1a1a1a !important;
    }

    .info-row {
      border-color: #2a2a2a !important;
    }

    .info-label {
      color: #999999 !important;
    }

    .info-value {
      color: #f5f5f5 !important;
    }
  }
}
</style>
