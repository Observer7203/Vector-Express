<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/api/client'
import { ArrowLeft, Search, AlertCircle, ArrowRight, MapPin, Truck, Bookmark, Calendar, History, ClipboardList } from 'lucide-vue-next'
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const { t, locale } = useI18n()

const iconStrokeWidth = 1.2

const trackingNumber = ref('')
const trackingResult = ref(null)
const loading = ref(false)
const error = ref('')

async function searchTracking() {
  if (!trackingNumber.value.trim()) {
    error.value = t('tracking.enterTrackingError')
    return
  }

  loading.value = true
  error.value = ''
  trackingResult.value = null

  try {
    const response = await api.get(`/tracking/${trackingNumber.value}`)
    trackingResult.value = response.data.data
  } catch (e) {
    if (e.response?.status === 404) {
      error.value = t('tracking.notFound')
    } else {
      error.value = t('tracking.searchError')
    }
  } finally {
    loading.value = false
  }
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  const localeMap = { ru: 'ru-RU', en: 'en-US', kk: 'kk-KZ' }
  return new Date(dateString).toLocaleString(localeMap[locale.value] || 'ru-RU')
}

const statusLabels = computed(() => ({
  pending: t('tracking.statusPending'),
  confirmed: t('tracking.statusConfirmed'),
  pickup_scheduled: t('tracking.statusPickupScheduled'),
  picked_up: t('tracking.statusPickedUp'),
  in_transit: t('tracking.statusInTransit'),
  customs: t('tracking.statusCustoms'),
  out_for_delivery: t('tracking.statusOutForDelivery'),
  delivered: t('tracking.statusDelivered'),
  cancelled: t('tracking.statusCancelled')
}))
</script>

<template>
  <div class="page">
    <header class="page-header">
      <div class="container">
        <div class="header-content">
          <div class="header-left">
            <RouterLink to="/" class="back-link">
              <ArrowLeft :size="20" :stroke-width="iconStrokeWidth" />
            </RouterLink>
            <h1>{{ t('tracking.title') }}</h1>
          </div>
          <div class="header-right">
            <ThemeToggle />
            <LanguageSwitcher />
          </div>
        </div>
      </div>
    </header>

    <main class="page-content">
      <div class="container">
        <div class="search-section">
          <div class="search-card">
            <h2>{{ t('tracking.enterTracking') }}</h2>
            <form @submit.prevent="searchTracking" class="search-form">
              <div class="search-input-wrapper">
                <Search :size="20" :stroke-width="iconStrokeWidth" class="search-icon" />
                <input
                  v-model="trackingNumber"
                  type="text"
                  :placeholder="t('tracking.placeholder')"
                  class="search-input"
                />
              </div>
              <button type="submit" class="btn btn-primary" :disabled="loading">
                <span v-if="loading" class="btn-loader"></span>
                {{ loading ? t('tracking.searching') : t('tracking.trackBtn') }}
              </button>
            </form>

            <div v-if="error" class="error-message">
              <AlertCircle :size="18" :stroke-width="iconStrokeWidth" />
              {{ error }}
            </div>
          </div>
        </div>

        <div v-if="trackingResult" class="tracking-result">
          <div class="result-header">
            <div class="order-info">
              <div class="order-number-wrapper">
                <span class="order-label">{{ t('tracking.orderNumber') }}</span>
                <span class="order-number">{{ trackingResult.order_number || trackingResult.tracking_number }}</span>
              </div>
              <span :class="['status', `status-${trackingResult.current_status}`]">
                {{ statusLabels[trackingResult.current_status] }}
              </span>
            </div>

            <div class="route" v-if="trackingResult.origin && trackingResult.destination">
              <div class="route-point">
                <span class="point-label">{{ t('tracking.from') }}</span>
                <span class="point-value">{{ trackingResult.origin }}</span>
              </div>
              <div class="route-arrow">
                <ArrowRight :size="18" :stroke-width="iconStrokeWidth" />
              </div>
              <div class="route-point">
                <span class="point-label">{{ t('tracking.to') }}</span>
                <span class="point-value">{{ trackingResult.destination }}</span>
              </div>
            </div>
          </div>

          <div class="current-location" v-if="trackingResult.current_location">
            <h3>
              <MapPin :size="22" :stroke-width="iconStrokeWidth" />
              {{ t('tracking.currentLocation') }}
            </h3>
            <div class="location-card">
              <div class="location-info">
                <span class="city">{{ trackingResult.current_location.city }}</span>
                <span class="country">{{ trackingResult.current_location.country }}</span>
              </div>
            </div>
          </div>

          <div class="delivery-info">
            <div class="info-card">
              <Truck :size="24" :stroke-width="iconStrokeWidth" />
              <div class="info-content">
                <span class="label">{{ t('tracking.carrier') }}</span>
                <span class="value">{{ trackingResult.carrier || '-' }}</span>
              </div>
            </div>
            <div class="info-card" v-if="trackingResult.carrier_tracking">
              <Bookmark :size="24" :stroke-width="iconStrokeWidth" />
              <div class="info-content">
                <span class="label">{{ t('tracking.carrierTracking') }}</span>
                <span class="value track">{{ trackingResult.carrier_tracking }}</span>
              </div>
            </div>
            <div class="info-card" v-if="trackingResult.estimated_delivery">
              <Calendar :size="24" :stroke-width="iconStrokeWidth" />
              <div class="info-content">
                <span class="label">{{ t('tracking.estimatedDelivery') }}</span>
                <span class="value">{{ formatDateTime(trackingResult.estimated_delivery) }}</span>
              </div>
            </div>
          </div>

          <div class="events-section" v-if="trackingResult.events?.length">
            <h3>
              <History :size="22" :stroke-width="iconStrokeWidth" />
              {{ t('tracking.movementHistory') }}
            </h3>
            <div class="timeline">
              <div
                v-for="(event, index) in trackingResult.events"
                :key="index"
                :class="['timeline-item', { latest: index === 0 }]"
              >
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                  <div class="event-header">
                    <span class="event-status">{{ event.status }}</span>
                    <span class="event-time">{{ formatDateTime(event.timestamp) }}</span>
                  </div>
                  <div class="event-location" v-if="event.location">
                    <MapPin :size="14" :stroke-width="iconStrokeWidth" />
                    {{ event.location }}
                  </div>
                  <div class="event-description" v-if="event.description">
                    {{ event.description }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="!loading && !error" class="help-section">
          <h3>{{ t('tracking.howToTrack') }}</h3>
          <div class="help-cards">
            <div class="help-card">
              <div class="help-icon">
                <ClipboardList :size="28" :stroke-width="iconStrokeWidth" />
              </div>
              <h4>{{ t('tracking.step1Title') }}</h4>
              <p>{{ t('tracking.step1Desc') }}</p>
            </div>
            <div class="help-card">
              <div class="help-icon">
                <Search :size="28" :stroke-width="iconStrokeWidth" />
              </div>
              <h4>{{ t('tracking.step2Title') }}</h4>
              <p>{{ t('tracking.step2Desc') }}</p>
            </div>
            <div class="help-card">
              <div class="help-icon">
                <MapPin :size="28" :stroke-width="iconStrokeWidth" />
              </div>
              <h4>{{ t('tracking.step3Title') }}</h4>
              <p>{{ t('tracking.step3Desc') }}</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.page {
  min-height: 100vh;
  background: $bg-light;
}

.page-header {
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
  justify-content: space-between;
  align-items: center;
  height: 64px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.header-right {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.back-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: $radius-md;
  color: $text-secondary;
  transition: all $transition-fast;

  svg {
    width: 20px;
    height: 20px;
  }

  &:hover {
    background: $bg-hover;
    color: $text-primary;
  }
}

h1 {
  font-size: $font-size-xl;
  font-weight: 600;
  color: $text-primary;
  margin: 0;
}

.page-content {
  padding: $spacing-xl 0;
}

.search-section {
  margin-bottom: $spacing-xl;
}

.search-card {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-xl;
  box-shadow: $shadow;

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-lg;
    text-align: center;
  }
}

.search-form {
  display: flex;
  gap: $spacing-md;
}

.search-input-wrapper {
  flex: 1;
  position: relative;
}

.search-icon {
  position: absolute;
  left: $spacing-md;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: $text-muted;
}

.search-input {
  width: 100%;
  padding: $spacing-md $spacing-md $spacing-md 48px;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-base;
  font-family: $font-family;
  transition: border-color $transition-fast, box-shadow $transition-fast;

  &::placeholder {
    color: $text-muted;
  }

  &:focus {
    outline: none;
    border-color: $color-primary;
    box-shadow: 0 0 0 3px rgba($color-primary, 0.1);
  }
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: $spacing-md $spacing-lg;
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

.error-message {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  margin-top: $spacing-md;
  padding: $spacing-md;
  background: rgba($color-danger, 0.1);
  color: $color-danger;
  border-radius: $radius-md;
  font-size: $font-size-sm;

  svg {
    width: 18px;
    height: 18px;
  }
}

.tracking-result {
  display: flex;
  flex-direction: column;
  gap: $spacing-lg;
}

.result-header {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-xl;
}

.order-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: $spacing-lg;
}

.order-number-wrapper {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.order-label {
  font-size: $font-size-xs;
  color: $text-muted;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.order-number {
  font-size: $font-size-xl;
  font-weight: 700;
  color: $text-primary;
}

.status {
  display: inline-block;
  padding: $spacing-xs $spacing-md;
  border-radius: $radius-full;
  font-size: $font-size-sm;
  font-weight: 500;
}

.status-pending,
.status-confirmed {
  background: rgba($color-warning, 0.1);
  color: $color-warning;
}

.status-pickup_scheduled,
.status-picked_up {
  background: rgba($color-primary, 0.1);
  color: $color-primary;
}

.status-in_transit,
.status-out_for_delivery {
  background: rgba($color-secondary, 0.1);
  color: $color-secondary;
}

.status-customs {
  background: rgba($color-warning, 0.1);
  color: $color-warning;
}

.status-delivered {
  background: rgba($color-success, 0.1);
  color: $color-success;
}

.status-cancelled {
  background: rgba($color-danger, 0.1);
  color: $color-danger;
}

.route {
  display: flex;
  align-items: center;
  gap: $spacing-lg;
  padding-top: $spacing-lg;
  border-top: 1px solid $border-color;
}

.route-point {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.point-label {
  font-size: $font-size-xs;
  color: $text-muted;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.point-value {
  font-size: $font-size-base;
  font-weight: 500;
  color: $text-primary;
}

.route-arrow {
  width: 32px;
  height: 32px;
  background: rgba($color-primary, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  svg {
    width: 18px;
    height: 18px;
    color: $color-primary;
  }
}

.current-location {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-xl;

  h3 {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-lg;

    svg {
      width: 22px;
      height: 22px;
      color: $color-primary;
    }
  }
}

.location-card {
  padding: $spacing-lg;
  background: rgba($color-primary, 0.05);
  border-radius: $radius-md;
  border-left: 4px solid $color-primary;
}

.location-info .city {
  display: block;
  font-size: $font-size-lg;
  font-weight: 600;
  color: $text-primary;
}

.location-info .country {
  display: block;
  color: $text-secondary;
  margin-top: 2px;
}

.delivery-info {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: $spacing-md;
}

.info-card {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-lg;
  display: flex;
  align-items: flex-start;
  gap: $spacing-md;

  > svg {
    width: 24px;
    height: 24px;
    color: $text-muted;
    flex-shrink: 0;
  }
}

.info-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.info-card .label {
  font-size: $font-size-xs;
  color: $text-muted;
}

.info-card .value {
  font-weight: 500;
  color: $text-primary;
}

.info-card .value.track {
  font-family: monospace;
  background: $bg-light;
  padding: 2px 6px;
  border-radius: $radius-sm;
  font-size: $font-size-sm;
}

.events-section {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-xl;

  h3 {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $spacing-lg;

    svg {
      width: 22px;
      height: 22px;
      color: $color-primary;
    }
  }
}

.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 5px;
  bottom: 5px;
  width: 2px;
  background: $border-color;
}

.timeline-item {
  position: relative;
  padding-bottom: $spacing-lg;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-dot {
  position: absolute;
  left: -26px;
  top: 5px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: $border-color;
}

.timeline-item.latest .timeline-dot {
  background: $color-primary;
  box-shadow: 0 0 0 4px rgba($color-primary, 0.2);
}

.timeline-content {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.event-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.event-status {
  font-weight: 600;
  color: $text-primary;
}

.event-time {
  font-size: $font-size-sm;
  color: $text-muted;
}

.event-location {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  color: $color-primary;
  font-size: $font-size-sm;

  svg {
    width: 14px;
    height: 14px;
  }
}

.event-description {
  color: $text-secondary;
  font-size: $font-size-sm;
}

.help-section {
  padding: $spacing-xl 0;

  h3 {
    text-align: center;
    color: $text-primary;
    font-size: $font-size-xl;
    font-weight: 600;
    margin: 0 0 $spacing-xl;
  }
}

.help-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: $spacing-lg;
}

.help-card {
  background: $bg-white;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  padding: $spacing-xl;
  text-align: center;
}

.help-icon {
  width: 56px;
  height: 56px;
  background: rgba($color-primary, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto $spacing-md;

  svg {
    width: 28px;
    height: 28px;
    color: $color-primary;
  }
}

.help-card h4 {
  font-size: $font-size-base;
  font-weight: 600;
  color: $text-primary;
  margin: 0 0 $spacing-sm;
}

.help-card p {
  color: $text-secondary;
  font-size: $font-size-sm;
  margin: 0;
  line-height: 1.5;
}

@media (max-width: $breakpoint-md) {
  .search-form {
    flex-direction: column;
  }

  .delivery-info {
    grid-template-columns: 1fr;
  }

  .help-cards {
    grid-template-columns: 1fr;
  }

  .route {
    flex-direction: column;
    align-items: flex-start;
    gap: $spacing-md;
  }

  .route-arrow {
    transform: rotate(90deg);
  }
}

// Dark theme
[data-theme="dark"] {
  .page {
    background: #1a1a1a !important;
  }

  .page-header {
    background: #0f0f0f !important;
    border-bottom-color: #2a2a2a !important;
  }

  h1 {
    color: #f5f5f5 !important;
  }

  .back-link {
    color: #999999;

    &:hover {
      background: #252525;
      color: #f5f5f5;
    }
  }

  .search-card {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    h2 {
      color: #f97316 !important;
    }
  }

  .search-input {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;

    &::placeholder {
      color: #666666;
    }

    &:focus {
      border-color: #f97316 !important;
    }
  }

  .search-icon {
    color: #666666;
  }

  .help-section h3 {
    color: #f5f5f5 !important;
  }

  .help-card {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    h4 {
      color: #f5f5f5 !important;
    }

    p {
      color: #999999 !important;
    }
  }

  .help-icon {
    background: rgba(249, 115, 22, 0.15) !important;

    svg {
      color: #f97316 !important;
    }
  }

  // Tracking result styles
  .result-header {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;
  }

  .order-label {
    color: #999999 !important;
  }

  .order-number {
    color: #f5f5f5 !important;
  }

  .route {
    border-top-color: #2a2a2a !important;
  }

  .point-label {
    color: #999999 !important;
  }

  .point-value {
    color: #f5f5f5 !important;
  }

  .route-arrow {
    background: rgba(249, 115, 22, 0.15) !important;

    svg {
      color: #f97316 !important;
    }
  }

  .current-location {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    h3 {
      color: #f5f5f5 !important;

      svg {
        color: #f97316 !important;
      }
    }
  }

  .location-card {
    background: rgba(249, 115, 22, 0.1) !important;
    border-left-color: #f97316 !important;
  }

  .location-info .city {
    color: #f5f5f5 !important;
  }

  .location-info .country {
    color: #999999 !important;
  }

  .info-card {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    > svg {
      color: #f97316 !important;
    }

    .label {
      color: #999999 !important;
    }

    .value {
      color: #f5f5f5 !important;
    }

    .value.track {
      background: #1a1a1a !important;
      color: #f97316 !important;
    }
  }

  .events-section {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    h3 {
      color: #f5f5f5 !important;

      svg {
        color: #f97316 !important;
      }
    }
  }

  .timeline::before {
    background: #2a2a2a !important;
  }

  .timeline-dot {
    background: #3a3a3a !important;
  }

  .timeline-item.latest .timeline-dot {
    background: #f97316 !important;
    box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.2) !important;
  }

  .event-status {
    color: #f5f5f5 !important;
  }

  .event-time {
    color: #999999 !important;
  }

  .event-location {
    color: #f97316 !important;
  }

  .event-description {
    color: #999999 !important;
  }
}
</style>
