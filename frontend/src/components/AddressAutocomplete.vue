<script setup>
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { MapPin, X, Map, Loader2 } from 'lucide-vue-next'
import { searchAddresses } from '@/services/geocoding'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      address: '',
      city: '',
      country: '',
      lat: null,
      lng: null
    })
  },
  placeholder: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  countryCode: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'select', 'openMap'])

const { t } = useI18n()

const query = ref(props.modelValue?.address || '')
const suggestions = ref([])
const isLoading = ref(false)
const showDropdown = ref(false)
const selectedIndex = ref(-1)
const inputRef = ref(null)

// Watch for external value changes
watch(() => props.modelValue?.address, (newVal) => {
  if (newVal !== query.value) {
    query.value = newVal || ''
  }
})

// Search when query changes
watch(query, async (newQuery) => {
  if (!newQuery || newQuery.length < 3) {
    suggestions.value = []
    showDropdown.value = false
    return
  }

  isLoading.value = true
  selectedIndex.value = -1

  try {
    const results = await searchAddresses(newQuery, {
      countryCode: props.countryCode,
      limit: 6
    })
    suggestions.value = results
    showDropdown.value = results.length > 0
  } catch (error) {
    console.error('Search error:', error)
    suggestions.value = []
  } finally {
    isLoading.value = false
  }
})

function selectSuggestion(suggestion) {
  query.value = suggestion.address || suggestion.displayName

  const value = {
    address: suggestion.address || suggestion.displayName,
    city: suggestion.city,
    country: suggestion.country,
    countryCode: suggestion.countryCode,
    street: suggestion.street,
    houseNumber: suggestion.houseNumber,
    postcode: suggestion.postcode,
    lat: suggestion.lat,
    lng: suggestion.lng,
    displayName: suggestion.displayName
  }

  emit('update:modelValue', value)
  emit('select', value)

  showDropdown.value = false
  suggestions.value = []
}

function handleKeydown(event) {
  if (!showDropdown.value) return

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      selectedIndex.value = Math.min(selectedIndex.value + 1, suggestions.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      selectedIndex.value = Math.max(selectedIndex.value - 1, 0)
      break
    case 'Enter':
      event.preventDefault()
      if (selectedIndex.value >= 0 && suggestions.value[selectedIndex.value]) {
        selectSuggestion(suggestions.value[selectedIndex.value])
      }
      break
    case 'Escape':
      showDropdown.value = false
      break
  }
}

function handleFocus() {
  if (suggestions.value.length > 0) {
    showDropdown.value = true
  }
}

function handleBlur() {
  // Delay to allow click on suggestion
  setTimeout(() => {
    showDropdown.value = false
  }, 200)
}

function clearInput() {
  query.value = ''
  emit('update:modelValue', {
    address: '',
    city: '',
    country: '',
    lat: null,
    lng: null
  })
  suggestions.value = []
  inputRef.value?.focus()
}

function openMapPicker() {
  emit('openMap')
}

const hasValue = computed(() => query.value && query.value.length > 0)
const hasCoordinates = computed(() => props.modelValue?.lat && props.modelValue?.lng)
</script>

<template>
  <div class="address-autocomplete">
    <label v-if="label" class="address-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>

    <div class="input-wrapper" :class="{ focused: showDropdown, hasValue }">
      <MapPin class="input-icon" :size="18" />

      <input
        ref="inputRef"
        v-model="query"
        type="text"
        :placeholder="placeholder || t('address.placeholder', 'Enter address...')"
        :disabled="disabled"
        @keydown="handleKeydown"
        @focus="handleFocus"
        @blur="handleBlur"
        autocomplete="off"
      />

      <div class="input-actions">
        <Loader2 v-if="isLoading" class="loading-icon" :size="18" />
        <button
          v-if="hasValue && !isLoading"
          type="button"
          class="btn-clear"
          @click="clearInput"
          :title="t('address.clear', 'Clear')"
        >
          <X :size="16" />
        </button>
        <button
          type="button"
          class="btn-map"
          @click="openMapPicker"
          :title="t('address.selectOnMap', 'Select on map')"
        >
          <Map :size="18" />
        </button>
      </div>
    </div>

    <!-- Coordinates indicator -->
    <div v-if="hasCoordinates" class="coordinates-badge">
      {{ modelValue.lat.toFixed(4) }}, {{ modelValue.lng.toFixed(4) }}
    </div>

    <!-- Suggestions dropdown -->
    <div v-if="showDropdown && suggestions.length > 0" class="suggestions-dropdown">
      <div
        v-for="(suggestion, index) in suggestions"
        :key="suggestion.id"
        class="suggestion-item"
        :class="{ selected: index === selectedIndex }"
        @mousedown.prevent="selectSuggestion(suggestion)"
        @mouseenter="selectedIndex = index"
      >
        <MapPin class="suggestion-icon" :size="16" />
        <div class="suggestion-content">
          <div class="suggestion-main">{{ suggestion.address || suggestion.displayName }}</div>
          <div v-if="suggestion.city || suggestion.country" class="suggestion-sub">
            {{ [suggestion.city, suggestion.country].filter(Boolean).join(', ') }}
          </div>
        </div>
      </div>

      <!-- Can't find address option -->
      <div class="suggestion-item suggestion-map" @mousedown.prevent="openMapPicker">
        <Map class="suggestion-icon" :size="16" />
        <div class="suggestion-content">
          <div class="suggestion-main">{{ t('address.cantFind', "Can't find address?") }}</div>
          <div class="suggestion-sub">{{ t('address.selectOnMapHint', 'Select location on map') }}</div>
        </div>
      </div>
    </div>

    <!-- No results -->
    <div v-if="showDropdown && suggestions.length === 0 && query.length >= 3 && !isLoading" class="suggestions-dropdown">
      <div class="no-results">
        <span>{{ t('address.noResults', 'No addresses found') }}</span>
      </div>
      <div class="suggestion-item suggestion-map" @mousedown.prevent="openMapPicker">
        <Map class="suggestion-icon" :size="16" />
        <div class="suggestion-content">
          <div class="suggestion-main">{{ t('address.selectOnMapAlt', 'Select on map instead') }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.address-autocomplete {
  position: relative;
  width: 100%;
}

.address-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 6px;

  .required {
    color: #ef4444;
    margin-left: 2px;
  }
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  transition: all 0.2s ease;

  &:hover {
    border-color: #9ca3af;
  }

  &.focused {
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
  }

  .input-icon {
    position: absolute;
    left: 12px;
    color: #9ca3af;
    pointer-events: none;
  }

  input {
    flex: 1;
    padding: 10px 12px 10px 40px;
    border: none;
    background: transparent;
    font-size: 14px;
    color: #111827;
    outline: none;
    width: 100%;

    &::placeholder {
      color: #9ca3af;
    }

    &:disabled {
      cursor: not-allowed;
      opacity: 0.6;
    }
  }

  .input-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    padding-right: 8px;
  }
}

.loading-icon {
  color: #f97316;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-clear,
.btn-map {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  border-radius: 6px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s ease;

  &:hover {
    background: #f3f4f6;
    color: #374151;
  }
}

.btn-map {
  &:hover {
    background: rgba(249, 115, 22, 0.1);
    color: #f97316;
  }
}

.coordinates-badge {
  display: inline-block;
  margin-top: 4px;
  padding: 2px 8px;
  background: rgba(249, 115, 22, 0.1);
  color: #f97316;
  font-size: 11px;
  font-family: monospace;
  border-radius: 4px;
}

.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  max-height: 320px;
  overflow-y: auto;
}

.suggestion-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 12px;
  cursor: pointer;
  transition: background 0.15s ease;

  &:hover,
  &.selected {
    background: #f9fafb;
  }

  &.suggestion-map {
    border-top: 1px solid #e5e7eb;
    background: #fafafa;

    .suggestion-icon {
      color: #f97316;
    }

    .suggestion-main {
      color: #f97316;
    }

    &:hover {
      background: rgba(249, 115, 22, 0.05);
    }
  }
}

.suggestion-icon {
  color: #9ca3af;
  flex-shrink: 0;
  margin-top: 2px;
}

.suggestion-content {
  flex: 1;
  min-width: 0;
}

.suggestion-main {
  font-size: 14px;
  color: #111827;
  line-height: 1.4;
  word-break: break-word;
}

.suggestion-sub {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

.no-results {
  padding: 12px;
  text-align: center;
  color: #6b7280;
  font-size: 14px;
}
</style>

<style lang="scss">
/* Dark theme */
[data-theme="dark"] {
  .address-autocomplete {
    .address-label {
      color: #f5f5f5;
    }

    .input-wrapper {
      background: #1a1a1a;
      border-color: #2a2a2a;

      &:hover {
        border-color: #3a3a3a;
      }

      &.focused {
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
      }

      .input-icon {
        color: #666666;
      }

      input {
        color: #f5f5f5;

        &::placeholder {
          color: #666666;
        }
      }
    }

    .btn-clear,
    .btn-map {
      color: #999999;

      &:hover {
        background: #252525;
        color: #f5f5f5;
      }
    }

    .btn-map:hover {
      background: rgba(249, 115, 22, 0.15);
      color: #f97316;
    }

    .coordinates-badge {
      background: rgba(249, 115, 22, 0.15);
    }

    .suggestions-dropdown {
      background: #0f0f0f;
      border-color: #2a2a2a;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .suggestion-item {
      &:hover,
      &.selected {
        background: #1a1a1a;
      }

      &.suggestion-map {
        border-color: #2a2a2a;
        background: #151515;

        &:hover {
          background: rgba(249, 115, 22, 0.1);
        }
      }
    }

    .suggestion-icon {
      color: #666666;
    }

    .suggestion-main {
      color: #f5f5f5;
    }

    .suggestion-sub {
      color: #999999;
    }

    .no-results {
      color: #999999;
    }
  }
}
</style>
