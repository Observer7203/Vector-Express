<script setup>
import { ref } from 'vue'
import { Globe } from 'lucide-vue-next'
import { availableLocales, setLocale, getLocale } from '@/i18n'

const currentLocale = ref(getLocale())
const showDropdown = ref(false)

const changeLanguage = (locale) => {
  setLocale(locale)
  currentLocale.value = locale
  showDropdown.value = false
  // Reload page to apply translations
  window.location.reload()
}

const currentLocaleData = availableLocales.find(l => l.code === currentLocale.value)
</script>

<template>
  <div class="language-switcher">
    <button
      class="language-button"
      @click="showDropdown = !showDropdown"
      @blur="setTimeout(() => showDropdown = false, 200)"
    >
      <Globe :size="18" :stroke-width="1.5" />
      <span class="current-language">{{ currentLocaleData?.flag }} {{ currentLocaleData?.name }}</span>
    </button>

    <div v-if="showDropdown" class="language-dropdown">
      <button
        v-for="locale in availableLocales"
        :key="locale.code"
        class="language-option"
        :class="{ active: locale.code === currentLocale }"
        @click="changeLanguage(locale.code)"
      >
        <span class="locale-flag">{{ locale.flag }}</span>
        <span class="locale-name">{{ locale.name }}</span>
      </button>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../styles/_variables.scss';

.language-switcher {
  position: relative;
}

.language-button {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  padding: $spacing-xs $spacing-sm;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  background: $bg-white;
  color: $text-primary;
  cursor: pointer;
  font-size: $font-size-sm;
  transition: all $transition-base;

  &:hover {
    background: $bg-light;
    border-color: $color-primary;
  }

  svg {
    color: $text-secondary;
  }
}

.current-language {
  white-space: nowrap;
}

.language-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  box-shadow: $shadow-lg;
  min-width: 160px;
  overflow: hidden;
  z-index: 100;
}

.language-option {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  width: 100%;
  padding: $spacing-sm $spacing-md;
  border: none;
  background: $bg-white;
  color: $text-primary;
  cursor: pointer;
  font-size: $font-size-sm;
  text-align: left;
  transition: background $transition-fast;

  &:hover {
    background: $bg-light;
  }

  &.active {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
    font-weight: 500;
  }

  &:not(:last-child) {
    border-bottom: 1px solid $border-color;
  }
}

.locale-flag {
  font-size: $font-size-lg;
}

.locale-name {
  flex: 1;
}
</style>
