<script setup>
import { onMounted } from 'vue'
import { Sun, Moon } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme'

const { isDark, toggleTheme, initTheme } = useTheme()

onMounted(() => {
  initTheme()
})
</script>

<template>
  <button class="theme-toggle" @click="toggleTheme" :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
    <Sun v-if="isDark" :size="18" :stroke-width="1.2" class="icon icon-sun" />
    <Moon v-else :size="18" :stroke-width="1.2" class="icon icon-moon" />
  </button>
</template>

<style lang="scss" scoped>
@import '../styles/_variables.scss';

.theme-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: $radius-md;
  background: transparent;
  color: $text-secondary;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover {
    background: $bg-hover;
    color: $text-primary;
  }

  .icon {
    transition: all $transition-fast;
  }

  .icon-sun {
    color: #f59e0b;
  }

  .icon-moon {
    color: $text-secondary;
  }
}
</style>

<style lang="scss">
/* Dark theme styles for ThemeToggle */
[data-theme="dark"] {
  .theme-toggle {
    color: #999999 !important;
    background: transparent !important;

    &:hover {
      background: #252525 !important;
      color: #f5f5f5 !important;
    }

    .icon-sun {
      color: #fbbf24 !important;
    }

    .icon-moon {
      color: #999999 !important;
    }
  }
}
</style>
