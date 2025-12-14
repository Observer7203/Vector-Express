<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  LayoutDashboard,
  Users,
  Building2,
  Truck,
  Package,
  FileText,
  Settings,
  LogOut,
  ChevronRight
} from 'lucide-vue-next'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const { t } = useI18n()
const route = useRoute()
const authStore = useAuthStore()

const iconStrokeWidth = 1.5

const menuItems = computed(() => [
  { path: '/admin', icon: LayoutDashboard, label: t('adminLayout.menu.dashboard'), exact: true },
  { path: '/admin/users', icon: Users, label: t('adminLayout.menu.users') },
  { path: '/admin/companies', icon: Building2, label: t('adminLayout.menu.companies') },
  { path: '/admin/carriers', icon: Truck, label: t('adminLayout.menu.carriers') },
  { path: '/admin/orders', icon: Package, label: t('adminLayout.menu.orders') },
  { path: '/admin/invoices', icon: FileText, label: t('adminLayout.menu.invoices') },
  { path: '/admin/settings', icon: Settings, label: t('adminLayout.menu.settings') }
])

const isActive = (item) => {
  if (item.exact) {
    return route.path === item.path
  }
  return route.path.startsWith(item.path)
}

const pageTitle = computed(() => {
  const item = menuItems.value.find(item => isActive(item))
  return item?.label || t('admin.dashboard')
})

async function handleLogout() {
  await authStore.logout()
}
</script>

<template>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <RouterLink to="/admin" class="logo">
          <span class="logo-icon">V</span>
          <span class="logo-text">Vector Admin</span>
        </RouterLink>
      </div>

      <nav class="sidebar-nav">
        <RouterLink
          v-for="item in menuItems"
          :key="item.path"
          :to="item.path"
          class="nav-item"
          :class="{ active: isActive(item) }"
        >
          <component :is="item.icon" :size="20" :stroke-width="iconStrokeWidth" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">
            {{ authStore.user?.name?.charAt(0) || 'A' }}
          </div>
          <div class="user-details">
            <span class="user-name">{{ authStore.user?.name || 'Admin' }}</span>
            <span class="user-role">{{ t('adminLayout.administrator') }}</span>
          </div>
        </div>
        <button @click="handleLogout" class="logout-btn" :title="t('nav.logout')">
          <LogOut :size="18" :stroke-width="iconStrokeWidth" />
        </button>
      </div>
    </aside>

    <main class="main-content">
      <header class="main-header">
        <div class="breadcrumb">
          <RouterLink to="/admin">{{ t('admin.dashboard') }}</RouterLink>
          <template v-if="route.path !== '/admin'">
            <ChevronRight :size="16" :stroke-width="iconStrokeWidth" />
            <span>{{ pageTitle }}</span>
          </template>
        </div>
        <div class="header-actions">
          <LanguageSwitcher />
        </div>
      </header>

      <div class="page-content">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
@import '../styles/_variables.scss';

.admin-layout {
  display: flex;
  min-height: 100vh;
  background: $bg-light;
}

.sidebar {
  width: 260px;
  background: $bg-dark;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  z-index: 100;
}

.sidebar-header {
  padding: $spacing-lg;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  text-decoration: none;
}

.logo-icon {
  width: 36px;
  height: 36px;
  background: $color-primary;
  color: $text-white;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: $font-size-lg;
}

.logo-text {
  color: $text-white;
  font-size: $font-size-lg;
  font-weight: 600;
}

.sidebar-nav {
  flex: 1;
  padding: $spacing-md;
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-sm $spacing-md;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  border-radius: $radius-md;
  transition: all $transition-fast;
  font-size: $font-size-sm;

  svg {
    flex-shrink: 0;
  }

  &:hover {
    background: rgba(255, 255, 255, 0.1);
    color: $text-white;
  }

  &.active {
    background: $color-primary;
    color: $text-white;
  }
}

.sidebar-footer {
  padding: $spacing-md;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.user-info {
  flex: 1;
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.user-avatar {
  width: 36px;
  height: 36px;
  background: $color-primary;
  color: $text-white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: $font-size-sm;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  color: $text-white;
  font-size: $font-size-sm;
  font-weight: 500;
}

.user-role {
  color: rgba(255, 255, 255, 0.5);
  font-size: $font-size-xs;
}

.logout-btn {
  width: 36px;
  height: 36px;
  background: transparent;
  border: none;
  border-radius: $radius-md;
  color: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all $transition-fast;

  &:hover {
    background: rgba(255, 255, 255, 0.1);
    color: $color-danger;
  }
}

.main-content {
  flex: 1;
  margin-left: 260px;
  display: flex;
  flex-direction: column;
}

.main-header {
  background: $bg-white;
  padding: $spacing-md $spacing-lg;
  border-bottom: 1px solid $border-color;
  position: sticky;
  top: 0;
  z-index: 50;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  font-size: $font-size-sm;
  color: $text-secondary;

  a {
    color: $text-secondary;
    text-decoration: none;

    &:hover {
      color: $color-primary;
    }
  }

  span {
    color: $text-primary;
    font-weight: 500;
  }

  svg {
    color: $text-muted;
  }
}

.page-content {
  flex: 1;
  padding: $spacing-lg;
}

@media (max-width: $breakpoint-lg) {
  .sidebar {
    width: 80px;
  }

  .logo-text,
  .nav-item span,
  .user-details {
    display: none;
  }

  .sidebar-header {
    padding: $spacing-md;
  }

  .logo-icon {
    margin: 0 auto;
  }

  .nav-item {
    justify-content: center;
    padding: $spacing-sm;
  }

  .user-info {
    justify-content: center;
  }

  .main-content {
    margin-left: 80px;
  }
}
</style>
