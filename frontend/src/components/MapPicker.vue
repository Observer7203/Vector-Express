<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { X, Check, MapPin, Loader2, Search } from 'lucide-vue-next'
import { reverseGeocode, searchAddresses } from '@/services/geocoding'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  initialLat: {
    type: Number,
    default: null
  },
  initialLng: {
    type: Number,
    default: null
  },
  // Default center (Kazakhstan, Almaty)
  defaultLat: {
    type: Number,
    default: 43.238949
  },
  defaultLng: {
    type: Number,
    default: 76.945465
  }
})

const emit = defineEmits(['close', 'select'])

const { t } = useI18n()

const mapContainer = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const isSearching = ref(false)
const isLoadingAddress = ref(false)
const selectedLocation = ref(null)
const selectedAddress = ref(null)

let map = null
let marker = null

// Initialize map when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    // Wait for DOM to update
    setTimeout(() => {
      initMap()
    }, 100)
  } else {
    destroyMap()
  }
})

function initMap() {
  if (!mapContainer.value || map) return

  // Determine initial center
  const lat = props.initialLat || props.defaultLat
  const lng = props.initialLng || props.defaultLng
  const zoom = props.initialLat ? 15 : 10

  // Create map
  map = L.map(mapContainer.value, {
    center: [lat, lng],
    zoom: zoom,
    zoomControl: true
  })

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  }).addTo(map)

  // Custom marker icon
  const markerIcon = L.divIcon({
    className: 'custom-marker',
    html: `<div class="marker-pin"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="#f97316" stroke="#ffffff" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3" fill="#ffffff" stroke="#f97316"/></svg></div>`,
    iconSize: [32, 32],
    iconAnchor: [16, 32]
  })

  // Add marker if initial position provided
  if (props.initialLat && props.initialLng) {
    marker = L.marker([props.initialLat, props.initialLng], { icon: markerIcon }).addTo(map)
    selectedLocation.value = { lat: props.initialLat, lng: props.initialLng }
    loadAddressForLocation(props.initialLat, props.initialLng)
  }

  // Handle map clicks
  map.on('click', async (e) => {
    const { lat, lng } = e.latlng

    // Update or create marker
    if (marker) {
      marker.setLatLng([lat, lng])
    } else {
      marker = L.marker([lat, lng], { icon: markerIcon }).addTo(map)
    }

    selectedLocation.value = { lat, lng }
    await loadAddressForLocation(lat, lng)
  })
}

function destroyMap() {
  if (map) {
    map.remove()
    map = null
    marker = null
  }
  selectedLocation.value = null
  selectedAddress.value = null
  searchQuery.value = ''
  searchResults.value = []
}

async function loadAddressForLocation(lat, lng) {
  isLoadingAddress.value = true
  selectedAddress.value = null

  try {
    const result = await reverseGeocode(lat, lng)
    if (result) {
      selectedAddress.value = result
    }
  } catch (error) {
    console.error('Reverse geocoding error:', error)
  } finally {
    isLoadingAddress.value = false
  }
}

async function handleSearch() {
  if (!searchQuery.value || searchQuery.value.length < 3) {
    searchResults.value = []
    return
  }

  isSearching.value = true
  try {
    const results = await searchAddresses(searchQuery.value, { limit: 5 })
    searchResults.value = results
  } catch (error) {
    console.error('Search error:', error)
    searchResults.value = []
  } finally {
    isSearching.value = false
  }
}

function selectSearchResult(result) {
  if (!map) return

  // Custom marker icon
  const markerIcon = L.divIcon({
    className: 'custom-marker',
    html: `<div class="marker-pin"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="#f97316" stroke="#ffffff" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3" fill="#ffffff" stroke="#f97316"/></svg></div>`,
    iconSize: [32, 32],
    iconAnchor: [16, 32]
  })

  // Move map to location
  map.setView([result.lat, result.lng], 16)

  // Update marker
  if (marker) {
    marker.setLatLng([result.lat, result.lng])
  } else {
    marker = L.marker([result.lat, result.lng], { icon: markerIcon }).addTo(map)
  }

  selectedLocation.value = { lat: result.lat, lng: result.lng }
  selectedAddress.value = result
  searchResults.value = []
  searchQuery.value = result.address || result.displayName
}

function confirmSelection() {
  if (!selectedLocation.value) return

  emit('select', {
    lat: selectedLocation.value.lat,
    lng: selectedLocation.value.lng,
    address: selectedAddress.value?.address || selectedAddress.value?.displayName || '',
    city: selectedAddress.value?.city || '',
    country: selectedAddress.value?.country || '',
    countryCode: selectedAddress.value?.countryCode || '',
    street: selectedAddress.value?.street || '',
    houseNumber: selectedAddress.value?.houseNumber || '',
    displayName: selectedAddress.value?.displayName || ''
  })

  emit('close')
}

function close() {
  emit('close')
}

// Cleanup on unmount
onUnmounted(() => {
  destroyMap()
})
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="map-picker-overlay" @click.self="close">
      <div class="map-picker-modal">
        <div class="modal-header">
          <h3>{{ t('map.title', 'Select Location on Map') }}</h3>
          <button type="button" class="btn-close" @click="close">
            <X :size="20" />
          </button>
        </div>

        <div class="modal-body">
          <!-- Search bar -->
          <div class="search-bar">
            <div class="search-input-wrapper">
              <Search class="search-icon" :size="18" />
              <input
                v-model="searchQuery"
                type="text"
                :placeholder="t('map.searchPlaceholder', 'Search for address...')"
                @input="handleSearch"
                @keyup.enter="handleSearch"
              />
              <Loader2 v-if="isSearching" class="loading-icon" :size="18" />
            </div>

            <!-- Search results dropdown -->
            <div v-if="searchResults.length > 0" class="search-results">
              <div
                v-for="result in searchResults"
                :key="result.id"
                class="search-result-item"
                @click="selectSearchResult(result)"
              >
                <MapPin :size="16" />
                <div class="result-text">
                  <div class="result-main">{{ result.address || result.displayName }}</div>
                  <div v-if="result.city" class="result-sub">{{ result.city }}, {{ result.country }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Map container -->
          <div ref="mapContainer" class="map-container"></div>

          <!-- Selected location info -->
          <div v-if="selectedLocation" class="selected-info">
            <div class="info-header">
              <MapPin :size="18" />
              <span>{{ t('map.selectedLocation', 'Selected Location') }}</span>
            </div>

            <div v-if="isLoadingAddress" class="info-loading">
              <Loader2 class="loading-icon" :size="16" />
              <span>{{ t('map.loadingAddress', 'Loading address...') }}</span>
            </div>

            <div v-else-if="selectedAddress" class="info-content">
              <div class="info-address">{{ selectedAddress.address || selectedAddress.displayName }}</div>
              <div class="info-coords">
                {{ selectedLocation.lat.toFixed(6) }}, {{ selectedLocation.lng.toFixed(6) }}
              </div>
            </div>

            <div v-else class="info-content">
              <div class="info-coords">
                {{ selectedLocation.lat.toFixed(6) }}, {{ selectedLocation.lng.toFixed(6) }}
              </div>
            </div>
          </div>

          <div v-else class="map-hint">
            <MapPin :size="18" />
            <span>{{ t('map.clickToSelect', 'Click on the map to select a location') }}</span>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" @click="close">
            {{ t('map.cancel', 'Cancel') }}
          </button>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="!selectedLocation"
            @click="confirmSelection"
          >
            <Check :size="18" />
            {{ t('map.confirm', 'Confirm Location') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style lang="scss" scoped>
.map-picker-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.map-picker-modal {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;

  h3 {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0;
  }

  .btn-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    border-radius: 8px;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
      background: #f3f4f6;
      color: #111827;
    }
  }
}

.modal-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.search-bar {
  position: relative;
  padding: 12px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;

  .search-icon {
    position: absolute;
    left: 12px;
    color: #9ca3af;
  }

  input {
    flex: 1;
    padding: 10px 40px 10px 40px;
    border: none;
    background: transparent;
    font-size: 14px;
    color: #111827;
    outline: none;

    &::placeholder {
      color: #9ca3af;
    }
  }

  .loading-icon {
    position: absolute;
    right: 12px;
    color: #f97316;
    animation: spin 1s linear infinite;
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.search-results {
  position: absolute;
  top: 100%;
  left: 20px;
  right: 20px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 10;
  max-height: 200px;
  overflow-y: auto;
}

.search-result-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 12px;
  cursor: pointer;
  transition: background 0.15s ease;

  &:hover {
    background: #f9fafb;
  }

  svg {
    color: #9ca3af;
    flex-shrink: 0;
    margin-top: 2px;
  }
}

.result-text {
  flex: 1;
  min-width: 0;
}

.result-main {
  font-size: 14px;
  color: #111827;
  line-height: 1.4;
}

.result-sub {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

.map-container {
  flex: 1;
  min-height: 400px;
  background: #f3f4f6;
}

.selected-info,
.map-hint {
  padding: 12px 20px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.map-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
  font-size: 14px;

  svg {
    color: #9ca3af;
  }
}

.info-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  margin-bottom: 8px;

  svg {
    color: #f97316;
  }
}

.info-loading {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
  font-size: 14px;

  .loading-icon {
    color: #f97316;
    animation: spin 1s linear infinite;
  }
}

.info-content {
  .info-address {
    font-size: 14px;
    color: #111827;
    font-weight: 500;
    margin-bottom: 4px;
  }

  .info-coords {
    font-size: 12px;
    color: #6b7280;
    font-family: monospace;
  }
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 20px;
  border-top: 1px solid #e5e7eb;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.btn-outline {
  background: transparent;
  border: 1px solid #d1d5db;
  color: #374151;

  &:hover {
    background: #f3f4f6;
  }
}

.btn-primary {
  background: #f97316;
  color: #ffffff;

  &:hover:not(:disabled) {
    background: #ea580c;
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

/* Custom marker styles */
:deep(.custom-marker) {
  background: transparent;
  border: none;
}

:deep(.marker-pin) {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}
</style>

<style lang="scss">
/* Dark theme */
[data-theme="dark"] {
  .map-picker-overlay {
    background: rgba(0, 0, 0, 0.7);
  }

  .map-picker-modal {
    background: #0f0f0f;
  }

  .modal-header {
    border-color: #2a2a2a;

    h3 {
      color: #f5f5f5;
    }

    .btn-close {
      color: #999999;

      &:hover {
        background: #1a1a1a;
        color: #f5f5f5;
      }
    }
  }

  .search-bar {
    border-color: #2a2a2a;
  }

  .search-input-wrapper {
    background: #1a1a1a;
    border-color: #2a2a2a;

    .search-icon {
      color: #666666;
    }

    input {
      color: #f5f5f5;

      &::placeholder {
        color: #666666;
      }
    }
  }

  .search-results {
    background: #0f0f0f;
    border-color: #2a2a2a;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
  }

  .search-result-item {
    &:hover {
      background: #1a1a1a;
    }

    svg {
      color: #666666;
    }
  }

  .result-main {
    color: #f5f5f5;
  }

  .result-sub {
    color: #999999;
  }

  .map-container {
    background: #1a1a1a;
  }

  .selected-info,
  .map-hint {
    border-color: #2a2a2a;
    background: #1a1a1a;
  }

  .map-hint {
    color: #999999;

    svg {
      color: #666666;
    }
  }

  .info-header {
    color: #999999;
  }

  .info-loading {
    color: #999999;
  }

  .info-content {
    .info-address {
      color: #f5f5f5;
    }

    .info-coords {
      color: #999999;
    }
  }

  .modal-footer {
    border-color: #2a2a2a;
  }

  .btn-outline {
    border-color: #2a2a2a;
    color: #f5f5f5;

    &:hover {
      background: #1a1a1a;
    }
  }
}
</style>
