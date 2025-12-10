<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: ''
})
const errors = ref({})

async function handleSubmit() {
  errors.value = {}
  try {
    await authStore.login(form.value)
    const redirect = route.query.redirect || '/dashboard'
    router.push(redirect)
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
      </div>

      <div class="auth-card">
        <h1>Вход в систему</h1>
        <p class="subtitle">Войдите для доступа к личному кабинету</p>

        <div v-if="authStore.error" class="alert alert-error">
          {{ authStore.error }}
        </div>

        <form @submit.prevent="handleSubmit" class="auth-form">
          <div class="form-group">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              placeholder="your@email.com"
              :class="{ 'has-error': errors.email }"
            />
            <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
          </div>

          <div class="form-group">
            <label for="password">Пароль</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              placeholder="••••••••"
              :class="{ 'has-error': errors.password }"
            />
            <span v-if="errors.password" class="field-error">{{ errors.password[0] }}</span>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="authStore.loading">
            <span v-if="authStore.loading" class="btn-loader"></span>
            {{ authStore.loading ? 'Вход...' : 'Войти' }}
          </button>
        </form>

        <div class="auth-footer">
          <p>Нет аккаунта? <RouterLink to="/register">Зарегистрироваться</RouterLink></p>
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
  max-width: 400px;
}

.auth-header {
  text-align: center;
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

label {
  font-size: $font-size-sm;
  font-weight: 500;
  color: $text-primary;
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
</style>
