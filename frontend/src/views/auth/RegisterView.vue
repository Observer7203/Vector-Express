<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { User, Truck } from 'lucide-vue-next'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const { t } = useI18n()
const iconStrokeWidth = 1.2

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  role: 'customer',
  company_name: '',
  company_inn: ''
})
const errors = ref({})

async function handleSubmit() {
  errors.value = {}
  try {
    const data = { ...form.value }
    if (data.role === 'carrier') {
      data.company_type = 'carrier'
    } else {
      // Remove company fields for customers
      delete data.company_name
      delete data.company_inn
    }
    await authStore.register(data)
    router.push('/dashboard')
  } catch (e) {
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
    }
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-header">
        <RouterLink to="/" class="logo">Vector Express</RouterLink>
        <LanguageSwitcher />
      </div>

      <div class="auth-card">
        <h1>{{ t('register.title') }}</h1>
        <p class="subtitle">{{ t('register.subtitle') }}</p>

        <div v-if="authStore.error" class="alert alert-error">
          {{ authStore.error }}
        </div>

        <form @submit.prevent="handleSubmit" class="auth-form">
          <div class="form-group">
            <label for="role">{{ t('register.accountType') }}</label>
            <div class="role-selector">
              <label class="role-option" :class="{ active: form.role === 'customer' }">
                <input type="radio" v-model="form.role" value="customer" />
                <div class="role-content">
                  <User :size="24" :stroke-width="iconStrokeWidth" />
                  <span>{{ t('register.roles.customer') }}</span>
                </div>
              </label>
              <label class="role-option" :class="{ active: form.role === 'carrier' }">
                <input type="radio" v-model="form.role" value="carrier" />
                <div class="role-content">
                  <Truck :size="24" :stroke-width="iconStrokeWidth" />
                  <span>{{ t('register.roles.carrier') }}</span>
                </div>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label for="name">{{ t('auth.name') }}</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              :placeholder="t('register.namePlaceholder')"
              :class="{ 'has-error': errors.name }"
            />
            <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
          </div>

          <div class="form-group">
            <label for="email">{{ t('auth.email') }}</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              :placeholder="t('register.emailPlaceholder')"
              :class="{ 'has-error': errors.email }"
            />
            <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
          </div>

          <div class="form-group">
            <label for="phone">{{ t('auth.phone') }}</label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              :placeholder="t('register.phonePlaceholder')"
              :class="{ 'has-error': errors.phone }"
            />
            <span v-if="errors.phone" class="field-error">{{ errors.phone[0] }}</span>
          </div>

          <template v-if="form.role === 'carrier'">
            <div class="form-group">
              <label for="company_name">{{ t('register.companyName') }} *</label>
              <input
                id="company_name"
                v-model="form.company_name"
                type="text"
                required
                :placeholder="t('register.companyNamePlaceholder')"
                :class="{ 'has-error': errors.company_name }"
              />
              <span v-if="errors.company_name" class="field-error">{{ errors.company_name[0] }}</span>
            </div>

            <div class="form-group">
              <label for="company_inn">{{ t('register.companyInn') }}</label>
              <input
                id="company_inn"
                v-model="form.company_inn"
                type="text"
                :placeholder="t('register.companyInnPlaceholder')"
              />
            </div>
          </template>

          <div class="form-row">
            <div class="form-group">
              <label for="password">{{ t('auth.password') }}</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                :placeholder="t('register.passwordPlaceholder')"
                :class="{ 'has-error': errors.password }"
              />
              <span v-if="errors.password" class="field-error">{{ errors.password[0] }}</span>
            </div>

            <div class="form-group">
              <label for="password_confirmation">{{ t('auth.confirmPassword') }}</label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                :placeholder="t('register.confirmPasswordPlaceholder')"
              />
            </div>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="authStore.loading">
            <span v-if="authStore.loading" class="btn-loader"></span>
            {{ authStore.loading ? t('register.registering') : t('auth.registerButton') }}
          </button>
        </form>

        <div class="auth-footer">
          <p>{{ t('auth.alreadyHaveAccount') }} <RouterLink to="/login">{{ t('auth.loginButton') }}</RouterLink></p>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: $bg-light;
  padding: $spacing-lg;
}

.auth-container {
  width: 100%;
  max-width: 480px;
}

.auth-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-lg;
}

.logo {
  font-size: $font-size-xl;
  font-weight: 600;
  color: $color-primary;
  text-decoration: none;
}

.auth-card {
  background: $bg-white;
  padding: $spacing-xl;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  box-shadow: $shadow;

  h1 {
    font-size: $font-size-xl;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-xs;
    text-align: center;
  }
}

.subtitle {
  color: $text-secondary;
  font-size: $font-size-sm;
  text-align: center;
  margin: 0 0 $spacing-lg;
}

.alert {
  padding: $spacing-sm $spacing-md;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  margin-bottom: $spacing-md;
}

.alert-error {
  background: rgba($color-danger, 0.1);
  color: $color-danger;
  border: 1px solid rgba($color-danger, 0.2);
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: $spacing-md;
}

label {
  font-size: $font-size-sm;
  font-weight: 500;
  color: $text-primary;
}

.role-selector {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: $spacing-sm;
}

.role-option {
  cursor: pointer;

  input {
    display: none;
  }

  .role-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: $spacing-xs;
    padding: $spacing-md;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    transition: all $transition-fast;

    svg {
      width: 24px;
      height: 24px;
      color: $text-muted;
    }

    span {
      font-size: $font-size-sm;
      color: $text-secondary;
    }
  }

  &:hover .role-content {
    border-color: $color-primary;
  }

  &.active .role-content {
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

input {
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

  &.has-error {
    border-color: $color-danger;

    &:focus {
      box-shadow: 0 0 0 3px rgba($color-danger, 0.1);
    }
  }
}

.field-error {
  color: $color-danger;
  font-size: $font-size-xs;
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
  font-family: $font-family;
  cursor: pointer;
  transition: all $transition-base;
  text-decoration: none;
  border: none;
}

.btn-primary {
  background: $color-primary;
  color: $text-white;
  width: 100%;
  padding: $spacing-md;

  &:hover:not(:disabled) {
    background: $color-primary-dark;
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
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
  to {
    transform: rotate(360deg);
  }
}

.auth-footer {
  margin-top: $spacing-lg;
  padding-top: $spacing-lg;
  border-top: 1px solid $border-color;
  text-align: center;

  p {
    color: $text-secondary;
    font-size: $font-size-sm;
    margin: 0;
  }

  a {
    color: $color-primary;
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }
}

@media (max-width: $breakpoint-sm) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
