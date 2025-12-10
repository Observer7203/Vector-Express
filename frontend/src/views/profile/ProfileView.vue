<script setup>
import { onMounted, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'
import {
  User,
  Building,
  Mail,
  Phone,
  MapPin,
  Globe,
  Camera,
  Save,
  Lock,
  Shield,
  Bell,
  FileText,
  CheckCircle,
  AlertCircle,
  Eye,
  EyeOff
} from 'lucide-vue-next'

const iconStrokeWidth = 1.2

const authStore = useAuthStore()

const activeTab = ref('profile')
const loading = ref(false)
const saving = ref(false)
const message = ref({ type: '', text: '' })

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const profileForm = ref({
  name: '',
  email: '',
  phone: ''
})

const companyForm = ref({
  name: '',
  inn: '',
  legal_address: '',
  actual_address: '',
  phone: '',
  email: '',
  website: ''
})

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})

const notificationSettings = ref({
  email_orders: true,
  email_status_updates: true,
  email_quotes: true,
  email_marketing: false,
  push_orders: true,
  push_status_updates: true
})

const tabs = [
  { id: 'profile', label: 'Профиль', icon: User },
  { id: 'company', label: 'Компания', icon: Building },
  { id: 'security', label: 'Безопасность', icon: Lock },
  { id: 'notifications', label: 'Уведомления', icon: Bell }
]

const user = computed(() => authStore.user || {})
const company = computed(() => authStore.user?.company || {})

onMounted(async () => {
  loading.value = true
  try {
    // Load user data
    profileForm.value = {
      name: user.value.name || '',
      email: user.value.email || '',
      phone: user.value.phone || ''
    }

    // Load company data
    if (company.value) {
      companyForm.value = {
        name: company.value.name || '',
        inn: company.value.inn || '',
        legal_address: company.value.legal_address || '',
        actual_address: company.value.actual_address || '',
        phone: company.value.phone || '',
        email: company.value.email || '',
        website: company.value.website || ''
      }
    }
  } finally {
    loading.value = false
  }
})

async function saveProfile() {
  saving.value = true
  message.value = { type: '', text: '' }

  try {
    await api.put('/user/profile', profileForm.value)
    await authStore.fetchUser()
    message.value = { type: 'success', text: 'Профиль успешно обновлен' }
  } catch (error) {
    console.error('Failed to save profile:', error)
    message.value = {
      type: 'error',
      text: error.response?.data?.message || 'Не удалось сохранить профиль'
    }
  } finally {
    saving.value = false
  }
}

async function saveCompany() {
  saving.value = true
  message.value = { type: '', text: '' }

  try {
    await api.put(`/companies/${company.value.id}`, companyForm.value)
    await authStore.fetchUser()
    message.value = { type: 'success', text: 'Данные компании обновлены' }
  } catch (error) {
    console.error('Failed to save company:', error)
    message.value = {
      type: 'error',
      text: error.response?.data?.message || 'Не удалось сохранить данные компании'
    }
  } finally {
    saving.value = false
  }
}

async function changePassword() {
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    message.value = { type: 'error', text: 'Пароли не совпадают' }
    return
  }

  saving.value = true
  message.value = { type: '', text: '' }

  try {
    await api.put('/user/password', passwordForm.value)
    message.value = { type: 'success', text: 'Пароль успешно изменен' }
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: ''
    }
  } catch (error) {
    console.error('Failed to change password:', error)
    message.value = {
      type: 'error',
      text: error.response?.data?.message || 'Не удалось изменить пароль'
    }
  } finally {
    saving.value = false
  }
}

async function saveNotifications() {
  saving.value = true
  message.value = { type: '', text: '' }

  try {
    await api.put('/user/notifications', notificationSettings.value)
    message.value = { type: 'success', text: 'Настройки уведомлений сохранены' }
  } catch (error) {
    console.error('Failed to save notifications:', error)
    message.value = {
      type: 'error',
      text: error.response?.data?.message || 'Не удалось сохранить настройки'
    }
  } finally {
    saving.value = false
  }
}

async function uploadAvatar(event) {
  const file = event.target.files?.[0]
  if (!file) return

  const formData = new FormData()
  formData.append('avatar', file)

  try {
    await api.post('/user/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    await authStore.fetchUser()
    message.value = { type: 'success', text: 'Аватар обновлен' }
  } catch (error) {
    console.error('Failed to upload avatar:', error)
    message.value = { type: 'error', text: 'Не удалось загрузить аватар' }
  }
}

async function handleLogout() {
  await authStore.logout()
}
</script>

<template>
  <div class="profile-page">
    <header class="dashboard-header">
      <div class="container">
        <div class="header-content">
          <div class="header-info">
            <RouterLink to="/" class="logo">Vector Express</RouterLink>
          </div>
          <nav class="header-nav">
            <RouterLink to="/dashboard" class="nav-link">Дашборд</RouterLink>
            <RouterLink to="/shipments" class="nav-link">Заявки</RouterLink>
            <RouterLink to="/orders" class="nav-link">Заказы</RouterLink>
            <RouterLink to="/tracking" class="nav-link">Отслеживание</RouterLink>
          </nav>
          <div class="header-actions">
            <RouterLink to="/profile" class="user-link">
              <User :size="18" :stroke-width="iconStrokeWidth" />
              {{ user.name }}
            </RouterLink>
            <button @click="handleLogout" class="btn btn-outline">Выход</button>
          </div>
        </div>
      </div>
    </header>

    <main class="dashboard-main">
      <div class="container">
        <div class="page-header">
          <h1>Настройки профиля</h1>
          <p class="subtitle">Управление личными данными и настройками аккаунта</p>
        </div>

        <!-- Message -->
        <div v-if="message.text" class="message" :class="message.type">
          <CheckCircle v-if="message.type === 'success'" :size="18" :stroke-width="iconStrokeWidth" />
          <AlertCircle v-else :size="18" :stroke-width="iconStrokeWidth" />
          {{ message.text }}
        </div>

        <div class="profile-layout">
          <!-- Sidebar -->
          <aside class="profile-sidebar">
            <div class="user-card">
              <div class="avatar-wrapper">
                <div class="avatar">
                  <img v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                  <User v-else :size="32" :stroke-width="iconStrokeWidth" />
                </div>
                <label class="avatar-upload">
                  <Camera :size="16" :stroke-width="iconStrokeWidth" />
                  <input type="file" accept="image/*" @change="uploadAvatar" hidden />
                </label>
              </div>
              <div class="user-info">
                <h3>{{ user.name }}</h3>
                <p>{{ user.email }}</p>
                <span class="role-badge">
                  {{ user.role === 'carrier' ? 'Перевозчик' : 'Заказчик' }}
                </span>
              </div>
            </div>

            <nav class="profile-nav">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                class="nav-item"
                :class="{ active: activeTab === tab.id }"
                @click="activeTab = tab.id"
              >
                <component :is="tab.icon" :size="18" :stroke-width="iconStrokeWidth" />
                {{ tab.label }}
              </button>
            </nav>
          </aside>

          <!-- Content -->
          <div class="profile-content">
            <!-- Profile Tab -->
            <div v-if="activeTab === 'profile'" class="tab-content">
              <div class="content-header">
                <h2>Личные данные</h2>
                <p>Основная информация о вашем аккаунте</p>
              </div>

              <form @submit.prevent="saveProfile" class="profile-form">
                <div class="form-group">
                  <label>
                    <User :size="16" :stroke-width="iconStrokeWidth" />
                    Имя
                  </label>
                  <input
                    v-model="profileForm.name"
                    type="text"
                    placeholder="Ваше имя"
                    class="form-input"
                  />
                </div>

                <div class="form-group">
                  <label>
                    <Mail :size="16" :stroke-width="iconStrokeWidth" />
                    Email
                  </label>
                  <input
                    v-model="profileForm.email"
                    type="email"
                    placeholder="email@example.com"
                    class="form-input"
                  />
                </div>

                <div class="form-group">
                  <label>
                    <Phone :size="16" :stroke-width="iconStrokeWidth" />
                    Телефон
                  </label>
                  <input
                    v-model="profileForm.phone"
                    type="tel"
                    placeholder="+7 777 123 45 67"
                    class="form-input"
                  />
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    <Save :size="16" :stroke-width="iconStrokeWidth" />
                    {{ saving ? 'Сохранение...' : 'Сохранить' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Company Tab -->
            <div v-if="activeTab === 'company'" class="tab-content">
              <div class="content-header">
                <h2>Данные компании</h2>
                <p>Информация о вашей организации</p>
              </div>

              <div v-if="company.verified" class="verification-badge success">
                <CheckCircle :size="18" :stroke-width="iconStrokeWidth" />
                Компания верифицирована
              </div>
              <div v-else class="verification-badge warning">
                <AlertCircle :size="18" :stroke-width="iconStrokeWidth" />
                Компания не верифицирована
              </div>

              <form @submit.prevent="saveCompany" class="profile-form">
                <div class="form-row">
                  <div class="form-group">
                    <label>
                      <Building :size="16" :stroke-width="iconStrokeWidth" />
                      Название компании
                    </label>
                    <input
                      v-model="companyForm.name"
                      type="text"
                      placeholder="ТОО Моя Компания"
                      class="form-input"
                    />
                  </div>

                  <div class="form-group">
                    <label>
                      <FileText :size="16" :stroke-width="iconStrokeWidth" />
                      ИНН/БИН
                    </label>
                    <input
                      v-model="companyForm.inn"
                      type="text"
                      placeholder="123456789012"
                      class="form-input"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label>
                    <MapPin :size="16" :stroke-width="iconStrokeWidth" />
                    Юридический адрес
                  </label>
                  <input
                    v-model="companyForm.legal_address"
                    type="text"
                    placeholder="г. Алматы, ул. Примерная, 123"
                    class="form-input"
                  />
                </div>

                <div class="form-group">
                  <label>
                    <MapPin :size="16" :stroke-width="iconStrokeWidth" />
                    Фактический адрес
                  </label>
                  <input
                    v-model="companyForm.actual_address"
                    type="text"
                    placeholder="г. Алматы, ул. Примерная, 123"
                    class="form-input"
                  />
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>
                      <Phone :size="16" :stroke-width="iconStrokeWidth" />
                      Телефон
                    </label>
                    <input
                      v-model="companyForm.phone"
                      type="tel"
                      placeholder="+7 727 123 45 67"
                      class="form-input"
                    />
                  </div>

                  <div class="form-group">
                    <label>
                      <Mail :size="16" :stroke-width="iconStrokeWidth" />
                      Email
                    </label>
                    <input
                      v-model="companyForm.email"
                      type="email"
                      placeholder="company@example.com"
                      class="form-input"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label>
                    <Globe :size="16" :stroke-width="iconStrokeWidth" />
                    Веб-сайт
                  </label>
                  <input
                    v-model="companyForm.website"
                    type="url"
                    placeholder="https://www.example.com"
                    class="form-input"
                  />
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    <Save :size="16" :stroke-width="iconStrokeWidth" />
                    {{ saving ? 'Сохранение...' : 'Сохранить' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Security Tab -->
            <div v-if="activeTab === 'security'" class="tab-content">
              <div class="content-header">
                <h2>Безопасность</h2>
                <p>Настройки пароля и безопасности аккаунта</p>
              </div>

              <form @submit.prevent="changePassword" class="profile-form">
                <div class="form-group">
                  <label>
                    <Lock :size="16" :stroke-width="iconStrokeWidth" />
                    Текущий пароль
                  </label>
                  <div class="password-input">
                    <input
                      v-model="passwordForm.current_password"
                      :type="showCurrentPassword ? 'text' : 'password'"
                      placeholder="Введите текущий пароль"
                      class="form-input"
                    />
                    <button type="button" class="password-toggle" @click="showCurrentPassword = !showCurrentPassword">
                      <Eye v-if="!showCurrentPassword" :size="18" :stroke-width="iconStrokeWidth" />
                      <EyeOff v-else :size="18" :stroke-width="iconStrokeWidth" />
                    </button>
                  </div>
                </div>

                <div class="form-group">
                  <label>
                    <Lock :size="16" :stroke-width="iconStrokeWidth" />
                    Новый пароль
                  </label>
                  <div class="password-input">
                    <input
                      v-model="passwordForm.password"
                      :type="showNewPassword ? 'text' : 'password'"
                      placeholder="Введите новый пароль"
                      class="form-input"
                    />
                    <button type="button" class="password-toggle" @click="showNewPassword = !showNewPassword">
                      <Eye v-if="!showNewPassword" :size="18" :stroke-width="iconStrokeWidth" />
                      <EyeOff v-else :size="18" :stroke-width="iconStrokeWidth" />
                    </button>
                  </div>
                </div>

                <div class="form-group">
                  <label>
                    <Lock :size="16" :stroke-width="iconStrokeWidth" />
                    Подтверждение пароля
                  </label>
                  <div class="password-input">
                    <input
                      v-model="passwordForm.password_confirmation"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      placeholder="Повторите новый пароль"
                      class="form-input"
                    />
                    <button type="button" class="password-toggle" @click="showConfirmPassword = !showConfirmPassword">
                      <Eye v-if="!showConfirmPassword" :size="18" :stroke-width="iconStrokeWidth" />
                      <EyeOff v-else :size="18" :stroke-width="iconStrokeWidth" />
                    </button>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    <Shield :size="16" :stroke-width="iconStrokeWidth" />
                    {{ saving ? 'Сохранение...' : 'Изменить пароль' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Notifications Tab -->
            <div v-if="activeTab === 'notifications'" class="tab-content">
              <div class="content-header">
                <h2>Уведомления</h2>
                <p>Настройте способы получения уведомлений</p>
              </div>

              <form @submit.prevent="saveNotifications" class="profile-form">
                <div class="notification-section">
                  <h4>
                    <Mail :size="18" :stroke-width="iconStrokeWidth" />
                    Email уведомления
                  </h4>

                  <label class="toggle-item">
                    <span class="toggle-info">
                      <span class="toggle-title">Новые заказы</span>
                      <span class="toggle-desc">Уведомления о новых заказах</span>
                    </span>
                    <input type="checkbox" v-model="notificationSettings.email_orders" class="toggle" />
                  </label>

                  <label class="toggle-item">
                    <span class="toggle-info">
                      <span class="toggle-title">Изменения статуса</span>
                      <span class="toggle-desc">Обновления по вашим заказам</span>
                    </span>
                    <input type="checkbox" v-model="notificationSettings.email_status_updates" class="toggle" />
                  </label>

                  <label class="toggle-item">
                    <span class="toggle-info">
                      <span class="toggle-title">Новые предложения</span>
                      <span class="toggle-desc">Уведомления о новых ценовых предложениях</span>
                    </span>
                    <input type="checkbox" v-model="notificationSettings.email_quotes" class="toggle" />
                  </label>

                  <label class="toggle-item">
                    <span class="toggle-info">
                      <span class="toggle-title">Маркетинг</span>
                      <span class="toggle-desc">Новости и специальные предложения</span>
                    </span>
                    <input type="checkbox" v-model="notificationSettings.email_marketing" class="toggle" />
                  </label>
                </div>

                <div class="notification-section">
                  <h4>
                    <Bell :size="18" :stroke-width="iconStrokeWidth" />
                    Push уведомления
                  </h4>

                  <label class="toggle-item">
                    <span class="toggle-info">
                      <span class="toggle-title">Новые заказы</span>
                      <span class="toggle-desc">Мгновенные уведомления о заказах</span>
                    </span>
                    <input type="checkbox" v-model="notificationSettings.push_orders" class="toggle" />
                  </label>

                  <label class="toggle-item">
                    <span class="toggle-info">
                      <span class="toggle-title">Изменения статуса</span>
                      <span class="toggle-desc">Уведомления об изменениях в реальном времени</span>
                    </span>
                    <input type="checkbox" v-model="notificationSettings.push_status_updates" class="toggle" />
                  </label>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    <Save :size="16" :stroke-width="iconStrokeWidth" />
                    {{ saving ? 'Сохранение...' : 'Сохранить настройки' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.profile-page {
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
}

.logo {
  font-size: $font-size-xl;
  font-weight: 600;
  color: $color-primary;
  text-decoration: none;
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

.user-link {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
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

  &:disabled {
    opacity: 0.7;
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
  margin-bottom: $spacing-xl;

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

.message {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-md;
  border-radius: $radius-md;
  margin-bottom: $spacing-lg;
  font-size: $font-size-sm;

  &.success {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.error {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }
}

.profile-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: $spacing-xl;
}

.profile-sidebar {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.user-card {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-lg;
  text-align: center;
}

.avatar-wrapper {
  position: relative;
  display: inline-block;
  margin-bottom: $spacing-md;
}

.avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: $bg-light;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  svg {
    color: $text-muted;
  }
}

.avatar-upload {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 28px;
  height: 28px;
  background: $color-primary;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: 2px solid $bg-white;

  svg {
    color: $text-white;
  }

  &:hover {
    background: $color-primary-dark;
  }
}

.user-info {
  h3 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-xs;
  }

  p {
    font-size: $font-size-sm;
    color: $text-secondary;
    margin: 0 0 $spacing-sm;
  }
}

.role-badge {
  display: inline-block;
  padding: $spacing-xs $spacing-sm;
  background: rgba($color-primary, 0.1);
  color: $color-primary;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 500;
}

.profile-nav {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  overflow: hidden;
}

.nav-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-md $spacing-lg;
  border: none;
  background: transparent;
  color: $text-secondary;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-fast;
  text-align: left;

  &:not(:last-child) {
    border-bottom: 1px solid $border-color;
  }

  &:hover {
    background: $bg-hover;
    color: $text-primary;
  }

  &.active {
    background: rgba($color-primary, 0.05);
    color: $color-primary;
  }
}

.profile-content {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
}

.tab-content {
  padding: $spacing-xl;
}

.content-header {
  margin-bottom: $spacing-xl;

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-xs;
  }

  p {
    color: $text-secondary;
    font-size: $font-size-sm;
    margin: 0;
  }
}

.verification-badge {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-md;
  border-radius: $radius-md;
  margin-bottom: $spacing-lg;
  font-size: $font-size-sm;
  font-weight: 500;

  &.success {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.warning {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
  }
}

.profile-form {
  max-width: 600px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-md;
}

.form-group {
  margin-bottom: $spacing-lg;

  label {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    font-size: $font-size-sm;
    font-weight: 500;
    color: $text-primary;
    margin-bottom: $spacing-xs;

    svg {
      color: $text-muted;
    }
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

.password-input {
  position: relative;

  .form-input {
    padding-right: 44px;
  }
}

.password-toggle {
  position: absolute;
  right: $spacing-sm;
  top: 50%;
  transform: translateY(-50%);
  padding: $spacing-xs;
  border: none;
  background: transparent;
  cursor: pointer;
  color: $text-muted;

  &:hover {
    color: $text-primary;
  }
}

.form-actions {
  margin-top: $spacing-xl;
  padding-top: $spacing-lg;
  border-top: 1px solid $border-color;
}

.notification-section {
  margin-bottom: $spacing-xl;

  h4 {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    font-size: $font-size-base;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-md;

    svg {
      color: $color-primary;
    }
  }
}

.toggle-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md 0;
  border-bottom: 1px solid $border-color;
  cursor: pointer;

  &:last-child {
    border-bottom: none;
  }
}

.toggle-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.toggle-title {
  font-size: $font-size-sm;
  font-weight: 500;
  color: $text-primary;
}

.toggle-desc {
  font-size: $font-size-xs;
  color: $text-secondary;
}

.toggle {
  width: 44px;
  height: 24px;
  appearance: none;
  background: $border-color;
  border-radius: $radius-full;
  position: relative;
  cursor: pointer;
  transition: background $transition-base;

  &::before {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: $bg-white;
    border-radius: 50%;
    transition: transform $transition-base;
  }

  &:checked {
    background: $color-primary;

    &::before {
      transform: translateX(20px);
    }
  }
}

@media (max-width: $breakpoint-lg) {
  .profile-layout {
    grid-template-columns: 1fr;
  }

  .profile-sidebar {
    flex-direction: row;
    flex-wrap: wrap;
    gap: $spacing-md;

    .user-card {
      flex: 1;
      min-width: 200px;
    }

    .profile-nav {
      flex: 2;
      min-width: 300px;
      display: flex;
      flex-wrap: wrap;

      .nav-item {
        flex: 1;
        min-width: 140px;
        border-bottom: none !important;
        border-right: 1px solid $border-color;

        &:last-child {
          border-right: none;
        }
      }
    }
  }
}

@media (max-width: $breakpoint-md) {
  .header-nav {
    display: none;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .profile-sidebar {
    flex-direction: column;

    .profile-nav {
      display: flex;
      flex-direction: column;

      .nav-item {
        border-right: none;
        border-bottom: 1px solid $border-color !important;

        &:last-child {
          border-bottom: none !important;
        }
      }
    }
  }
}
</style>
