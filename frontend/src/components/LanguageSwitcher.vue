<script setup>
import { ref, computed } from 'vue'
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

// Short codes for display
const localeShortCodes = {
  ru: 'РУС',
  kk: 'ҚАЗ',
  en: 'ENG'
}

const currentShortCode = computed(() => localeShortCodes[currentLocale.value] || 'РУС')
</script>

<template>
  <div class="language-switcher">
    <button
      class="language-button"
      @click="showDropdown = !showDropdown"
      @blur="() => window.setTimeout(() => showDropdown = false, 200)"
    >
      <Globe :size="16" :stroke-width="1.2" />
      <span class="current-language">{{ currentShortCode }}</span>
    </button>

    <div v-if="showDropdown" class="language-dropdown">
      <button
        v-for="locale in availableLocales"
        :key="locale.code"
        class="language-option"
        :class="{ active: locale.code === currentLocale }"
        @click="changeLanguage(locale.code)"
      >
        <span class="locale-code">{{ localeShortCodes[locale.code] }}</span>
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
  border: none;
  border-radius: $radius-md;
  background: transparent;
  color: $text-secondary;
  cursor: pointer;
  font-size: $font-size-sm;
  font-weight: 500;
  transition: all $transition-fast;

  &:hover {
    background: $bg-hover;
    color: $text-primary;
  }

  svg {
    color: $text-secondary;
  }
}

.current-language {
  white-space: nowrap;
  font-size: $font-size-sm;
  letter-spacing: 0.5px;
}

.language-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: $bg-white;
  border: 1px solid $border-color;
  border-radius: $radius-lg;
  box-shadow: $shadow-lg;
  min-width: 80px;
  overflow: hidden;
  z-index: 1000;
}

.language-option {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: $spacing-sm $spacing-md;
  border: none;
  background: $bg-white;
  color: $text-secondary;
  cursor: pointer;
  font-size: $font-size-sm;
  font-weight: 500;
  text-align: center;
  transition: all $transition-fast;
  letter-spacing: 0.5px;

  &:hover {
    background: $bg-hover;
    color: $text-primary;
  }

  &.active {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
    font-weight: 600;
  }

  &:not(:last-child) {
    border-bottom: 1px solid $border-color;
  }
}

.locale-code {
  flex: 1;
}
</style>
