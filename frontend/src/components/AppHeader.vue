<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { User, Settings, ChevronDown, LogOut, Shield } from 'lucide-vue-next'
import LanguageSwitcher from './LanguageSwitcher.vue'

const iconStrokeWidth = 1.2
const authStore = useAuthStore()

const showUserMenu = ref(false)
const isCarrier = computed(() => authStore.user?.role === 'carrier')
const isAdmin = computed(() => authStore.user?.role === 'admin')

function closeMenu() {
  showUserMenu.value = false
}

async function handleLogout() {
  await authStore.logout()
}

onMounted(() => {
  document.addEventListener('click', closeMenu)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenu)
})
</script>

<template>
  <header class="app-header">
    <div class="container">
      <div class="header-content">
        <div class="header-info">
          <RouterLink to="/dashboard" class="logo">Vector Express</RouterLink>
        </div>
        <nav class="header-nav">
          <RouterLink to="/shipments" class="nav-link">Заявки</RouterLink>
          <RouterLink to="/orders" class="nav-link">Заказы</RouterLink>
          <RouterLink to="/tracking" class="nav-link">Отслеживание</RouterLink>
          <RouterLink v-if="isCarrier" to="/carrier" class="nav-link">Панель перевозчика</RouterLink>
          <RouterLink v-if="isAdmin" to="/admin" class="nav-link nav-link-admin">Админ-панель</RouterLink>
        </nav>
        <div class="header-actions">
          <LanguageSwitcher />
          <div class="user-menu" @click.stop="showUserMenu = !showUserMenu">
            <div class="user-menu-trigger">
              <div class="user-avatar">
                <User :size="18" :stroke-width="iconStrokeWidth" />
              </div>
              <span class="user-name">{{ authStore.user?.name }}</span>
              <ChevronDown :size="16" :stroke-width="iconStrokeWidth" :class="{ 'rotated': showUserMenu }" />
            </div>
            <div class="user-dropdown" v-if="showUserMenu">
              <RouterLink to="/profile" class="dropdown-item" @click="showUserMenu = false">
                <User :size="16" :stroke-width="iconStrokeWidth" />
                Профиль
              </RouterLink>
              <RouterLink v-if="isCarrier" to="/carrier" class="dropdown-item" @click="showUserMenu = false">
                <Settings :size="16" :stroke-width="iconStrokeWidth" />
                Панель перевозчика
              </RouterLink>
              <RouterLink v-if="isAdmin" to="/admin" class="dropdown-item dropdown-item-admin" @click="showUserMenu = false">
                <Shield :size="16" :stroke-width="iconStrokeWidth" />
                Админ-панель
              </RouterLink>
              <div class="dropdown-divider"></div>
              <button @click="handleLogout" class="dropdown-item dropdown-item-danger">
                <LogOut :size="16" :stroke-width="iconStrokeWidth" />
                Выход
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<style lang="scss" scoped>
@import '../styles/_variables.scss';

.app-header {
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

.user-menu {
  position: relative;
}

.user-menu-trigger {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-xs $spacing-sm;
  border-radius: $radius-md;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover {
    background: $bg-hover;
  }

  svg.rotated {
    transform: rotate(180deg);
  }

  svg {
    transition: transform $transition-fast;
  }
}

.user-avatar {
  width: 32px;
  height: 32px;
  background: rgba($color-primary, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  svg {
    color: $color-primary;
  }
}

.user-name {
  color: $text-secondary;
  font-size: $font-size-sm;
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 200px;
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-lg;
  box-shadow: $shadow-lg;
  z-index: 1000;
  padding: $spacing-xs 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-sm $spacing-md;
  color: $text-primary;
  text-decoration: none;
  font-size: $font-size-sm;
  transition: all $transition-fast;
  border: none;
  background: none;
  width: 100%;
  cursor: pointer;

  svg {
    color: $text-secondary;
  }

  &:hover {
    background: $bg-hover;
  }
}

.dropdown-item-danger {
  color: $color-danger;

  svg {
    color: $color-danger;
  }

  &:hover {
    background: rgba($color-danger, 0.1);
  }
}

.dropdown-item-admin {
  color: #7c3aed;

  svg {
    color: #7c3aed;
  }

  &:hover {
    background: rgba(#7c3aed, 0.1);
  }
}

.nav-link-admin {
  color: #7c3aed !important;

  &:hover {
    background: rgba(#7c3aed, 0.1) !important;
  }

  &.router-link-active {
    background: rgba(#7c3aed, 0.15) !important;
  }
}

.dropdown-divider {
  height: 1px;
  background: $border-color;
  margin: $spacing-xs 0;
}

@media (max-width: $breakpoint-md) {
  .header-nav {
    display: none;
  }

  .user-name {
    display: none;
  }
}
</style>
