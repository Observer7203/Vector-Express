<script setup>
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import { Globe, DollarSign, Truck, ShieldCheck, Calculator, Search, UserPlus } from 'lucide-vue-next'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'

// Lucide icons с тонкими линиями
const iconStrokeWidth = 1.2

const authStore = useAuthStore()
const { t } = useI18n()
</script>

<template>
  <div class="home">
    <header class="header">
      <div class="container">
        <nav class="nav">
          <div class="logo">Vector Express</div>
          <div class="nav-links">
            <ThemeToggle />
            <LanguageSwitcher />
            <RouterLink v-if="authStore.isAuthenticated" to="/dashboard" class="btn btn-outline">
              {{ t('home.personalCabinet') }}
            </RouterLink>
            <template v-else>
              <RouterLink to="/login" class="btn btn-text">{{ t('nav.login') }}</RouterLink>
              <RouterLink to="/register" class="btn btn-primary">{{ t('nav.register') }}</RouterLink>
            </template>
          </div>
        </nav>
      </div>
    </header>

    <section class="hero">
      <div class="hero-bg">
        <svg class="hero-pattern" viewBox="0 0 1200 500" preserveAspectRatio="xMidYMid slice">
          <defs>
            <linearGradient id="fadeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="currentColor" stop-opacity="0"/>
              <stop offset="20%" stop-color="currentColor" stop-opacity="1"/>
              <stop offset="80%" stop-color="currentColor" stop-opacity="1"/>
              <stop offset="100%" stop-color="currentColor" stop-opacity="0"/>
            </linearGradient>
          </defs>

          <!-- Globe outline - тонкие линии -->
          <g stroke="currentColor" fill="none" opacity="0.08" stroke-width="0.5">
            <!-- Основной круг глобуса -->
            <circle cx="600" cy="250" r="200"/>

            <!-- Широтные линии -->
            <ellipse cx="600" cy="250" rx="200" ry="30"/>
            <ellipse cx="600" cy="250" rx="200" ry="80"/>
            <ellipse cx="600" cy="250" rx="200" ry="130"/>
            <ellipse cx="600" cy="250" rx="200" ry="170"/>

            <!-- Меридианы -->
            <ellipse cx="600" cy="250" rx="30" ry="200"/>
            <ellipse cx="600" cy="250" rx="80" ry="200"/>
            <ellipse cx="600" cy="250" rx="130" ry="200"/>
            <ellipse cx="600" cy="250" rx="170" ry="200"/>
          </g>

          <!-- Маршрутные линии - тонкие пунктиры -->
          <g stroke="currentColor" fill="none" opacity="0.12" stroke-width="0.8">
            <path d="M150 350 Q400 200 600 220 Q800 240 1050 150" stroke-dasharray="6 4"/>
            <path d="M100 200 Q350 320 600 280 Q850 240 1100 320" stroke-dasharray="6 4"/>
            <path d="M200 120 Q450 180 600 160 Q750 140 1000 200" stroke-dasharray="6 4"/>
          </g>

          <!-- Точки-города - маленькие -->
          <g fill="currentColor" opacity="0.15">
            <circle cx="150" cy="350" r="3"/>
            <circle cx="600" cy="220" r="4"/>
            <circle cx="1050" cy="150" r="3"/>
            <circle cx="100" cy="200" r="2.5"/>
            <circle cx="600" cy="280" r="3"/>
            <circle cx="1100" cy="320" r="2.5"/>
            <circle cx="200" cy="120" r="2.5"/>
            <circle cx="600" cy="160" r="3"/>
            <circle cx="1000" cy="200" r="2.5"/>
            <!-- Дополнительные точки -->
            <circle cx="350" cy="260" r="2"/>
            <circle cx="450" cy="180" r="2"/>
            <circle cx="750" cy="200" r="2"/>
            <circle cx="850" cy="280" r="2"/>
            <circle cx="300" cy="380" r="2"/>
            <circle cx="900" cy="120" r="2"/>
          </g>
        </svg>
      </div>
      <div class="container">
        <div class="hero-content">
          <h1>{{ t('home.hero.title') }}</h1>
          <p class="hero-subtitle">
            {{ t('home.hero.subtitle') }}
          </p>
          <div class="hero-actions">
            <RouterLink to="/shipments/new" class="btn btn-primary btn-lg">
              <Calculator :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('home.hero.calculateCost') }}
            </RouterLink>
            <RouterLink to="/tracking" class="btn btn-outline-dark btn-lg">
              <Search :size="18" :stroke-width="iconStrokeWidth" />
              {{ t('home.hero.trackShipment') }}
            </RouterLink>
          </div>
        </div>
      </div>
    </section>

    <section class="features">
      <div class="container">
        <h2 class="section-title">{{ t('home.features.title') }}</h2>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">
              <Globe :size="24" :stroke-width="iconStrokeWidth" />
            </div>
            <h3>{{ t('home.features.globalNetwork.title') }}</h3>
            <p>{{ t('home.features.globalNetwork.description') }}</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <DollarSign :size="24" :stroke-width="iconStrokeWidth" />
            </div>
            <h3>{{ t('home.features.bestPrices.title') }}</h3>
            <p>{{ t('home.features.bestPrices.description') }}</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <Truck :size="24" :stroke-width="iconStrokeWidth" />
            </div>
            <h3>{{ t('home.features.allCargoTypes.title') }}</h3>
            <p>{{ t('home.features.allCargoTypes.description') }}</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <ShieldCheck :size="24" :stroke-width="iconStrokeWidth" />
            </div>
            <h3>{{ t('home.features.reliability.title') }}</h3>
            <p>{{ t('home.features.reliability.description') }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="carriers">
      <div class="container">
        <h2 class="section-title">{{ t('home.partners.title') }}</h2>
        <div class="carriers-grid">
          <div class="carrier-item">DHL</div>
          <div class="carrier-item">FedEx</div>
          <div class="carrier-item">UPS</div>
          <div class="carrier-item">Ponyexpress</div>
        </div>
      </div>
    </section>

    <section class="cta">
      <div class="container">
        <div class="cta-content">
          <h2>{{ t('home.cta.title') }}</h2>
          <p>{{ t('home.cta.subtitle') }}</p>
          <RouterLink to="/register" class="btn btn-white btn-lg">
            <UserPlus :size="18" :stroke-width="iconStrokeWidth" />
            {{ t('home.cta.register') }}
          </RouterLink>
        </div>
      </div>
    </section>

    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-brand">
            <div class="footer-logo">Vector Express</div>
            <p>{{ t('home.footer.tagline') }}</p>
          </div>
          <div class="footer-links">
            <h4>{{ t('home.footer.company') }}</h4>
            <a href="#">{{ t('home.footer.aboutUs') }}</a>
            <a href="#">{{ t('home.footer.contacts') }}</a>
          </div>
          <div class="footer-links">
            <h4>{{ t('home.footer.services') }}</h4>
            <a href="#">{{ t('home.footer.airFreight') }}</a>
            <a href="#">{{ t('home.footer.oceanFreight') }}</a>
          </div>
          <div class="footer-links">
            <h4>{{ t('home.footer.support') }}</h4>
            <a href="#">{{ t('home.footer.faq') }}</a>
            <a href="#">{{ t('home.footer.documents') }}</a>
          </div>
        </div>
        <div class="footer-bottom">
          <p>{{ t('home.footer.copyright') }}</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style lang="scss" scoped>
@import '../styles/_variables.scss';

.home {
  min-height: 100vh;
  background: $bg-white;
}

.container {
  max-width: $container-max-width;
  margin: 0 auto;
  padding: 0 $container-padding;
}

// Header
.header {
  background: $bg-white;
  border-bottom: 1px solid $border-color;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 64px;
}

.logo {
  font-size: $font-size-xl;
  font-weight: 600;
  color: $text-primary;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

// Buttons
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: $spacing-sm $spacing-md;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-base;
  text-decoration: none;
  border: none;
}

.btn-primary {
  background: $color-primary;
  color: $text-white;

  &:hover {
    background: $color-primary-dark;
  }
}

.btn-outline {
  background: transparent;
  color: $color-primary;
  border: 1px solid $color-primary;

  &:hover {
    background: $color-primary;
    color: $text-white;
  }
}

.btn-outline-dark {
  background: transparent;
  color: $text-primary;
  border: 1px solid $border-color-dark;

  &:hover {
    background: $bg-hover;
  }
}

.btn-text {
  background: transparent;
  color: $text-secondary;

  &:hover {
    color: $text-primary;
  }
}

.btn-white {
  background: $bg-white;
  color: $color-primary;

  &:hover {
    background: $bg-light;
  }
}

.btn-lg {
  padding: $spacing-sm $spacing-lg;
  font-size: $font-size-base;
  gap: $spacing-xs;
}

// Hero
.hero {
  padding: $spacing-2xl 0;
  background: $bg-light;
  position: relative;
  overflow: hidden;
}

.hero-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
}

.hero-pattern {
  width: 100%;
  height: 100%;
  color: $color-primary;
}

.hero-content {
  max-width: 700px;
  text-align: center;
  margin: 0 auto;
  position: relative;
  z-index: 1;

  h1 {
    font-size: $font-size-3xl;
    font-weight: 600;
    color: $text-primary;
    margin-bottom: $spacing-md;
    line-height: 1.2;
  }
}

.hero-subtitle {
  font-size: $font-size-lg;
  color: $text-secondary;
  margin-bottom: $spacing-xl;
  line-height: 1.6;
}

.hero-actions {
  display: flex;
  gap: $spacing-md;
  justify-content: center;
}

// Section title
.section-title {
  font-size: $font-size-2xl;
  font-weight: 600;
  color: $text-primary;
  text-align: center;
  margin-bottom: $spacing-xl;
}

// Features
.features {
  padding: $spacing-2xl 0;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: $spacing-lg;
}

.feature-card {
  background: $bg-white;
  padding: $spacing-lg;
  border-radius: $radius-lg;
  border: 1px solid $border-color;
  transition: all $transition-base;

  &:hover {
    border-color: $color-primary;
    box-shadow: $shadow;
  }

  h3 {
    font-size: $font-size-lg;
    font-weight: 500;
    color: $text-primary;
    margin: $spacing-md 0 $spacing-sm;
  }

  p {
    color: $text-secondary;
    font-size: $font-size-sm;
    line-height: 1.6;
    margin: 0;
  }
}

.feature-icon {
  width: 48px;
  height: 48px;
  background: rgba($color-primary, 0.1);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;

  svg {
    width: 24px;
    height: 24px;
    color: $color-primary;
  }
}

// Carriers
.carriers {
  padding: $spacing-2xl 0;
  background: $bg-light;
}

.carriers-grid {
  display: flex;
  justify-content: center;
  gap: $spacing-xl;
  flex-wrap: wrap;
}

.carrier-item {
  font-size: $font-size-xl;
  font-weight: 600;
  color: $text-muted;
  padding: $spacing-md $spacing-xl;
  background: $bg-white;
  border-radius: $radius-md;
  border: 1px solid $border-color;
}

// CTA
.cta {
  padding: $spacing-2xl 0;
  background-color: $color-primary;
}

.cta-content {
  text-align: center;
  color: $text-white;

  h2 {
    font-size: $font-size-2xl;
    font-weight: 600;
    margin-bottom: $spacing-sm;
  }

  p {
    opacity: 0.9;
    margin-bottom: $spacing-lg;
  }
}

// Footer
.footer {
  background: $bg-dark;
  color: $text-white;
  padding: $spacing-2xl 0 $spacing-lg;
}

.footer-content {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: $spacing-xl;
  margin-bottom: $spacing-xl;
}

.footer-brand {
  .footer-logo {
    font-size: $font-size-xl;
    font-weight: 600;
    color: $text-white;
    margin-bottom: $spacing-sm;
  }

  p {
    color: $text-muted;
    font-size: $font-size-sm;
  }
}

.footer-links {
  h4 {
    font-size: $font-size-sm;
    font-weight: 500;
    margin-bottom: $spacing-md;
  }

  a {
    display: block;
    color: $text-muted;
    font-size: $font-size-sm;
    text-decoration: none;
    margin-bottom: $spacing-sm;
    transition: color $transition-fast;

    &:hover {
      color: $text-white;
    }
  }
}

.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: $spacing-lg;
  text-align: center;

  p {
    color: $text-muted;
    font-size: $font-size-sm;
    margin: 0;
  }
}

// Responsive
@media (max-width: $breakpoint-md) {
  .hero-content h1 {
    font-size: $font-size-2xl;
  }

  .hero-actions {
    flex-direction: column;
    align-items: center;
  }

  .footer-content {
    grid-template-columns: 1fr;
    gap: $spacing-lg;
  }
}

// Dark theme overrides
[data-theme="dark"] {
  .home {
    background: var(--bg-dark);
  }

  .header {
    background: var(--bg-white);
    border-bottom-color: var(--border-color);
  }

  .logo {
    color: var(--color-primary);
  }

  .hero {
    background: var(--bg-light);
  }

  .hero-pattern {
    color: var(--color-primary);
  }

  .hero-content h1 {
    color: var(--text-primary);
  }

  .hero-subtitle {
    color: var(--text-secondary);
  }

  .btn-primary {
    background: var(--color-primary);

    &:hover {
      background: var(--color-primary-dark);
    }
  }

  .btn-outline {
    color: var(--color-primary);
    border-color: var(--color-primary);

    &:hover {
      background: var(--color-primary);
      color: var(--text-white);
    }
  }

  .btn-outline-dark {
    color: var(--text-primary);
    border-color: var(--border-color-dark);

    &:hover {
      background: var(--bg-hover);
    }
  }

  .btn-text {
    color: var(--text-secondary);

    &:hover {
      color: var(--text-primary);
    }
  }

  .btn-white {
    background: var(--bg-white);
    color: var(--color-primary);

    &:hover {
      background: var(--bg-light);
    }
  }

  .section-title {
    color: var(--text-primary);
  }

  .features {
    background: var(--bg-dark);
  }

  .feature-card {
    background: var(--bg-white);
    border-color: var(--border-color);

    &:hover {
      border-color: var(--color-primary);
    }

    h3 {
      color: var(--text-primary);
    }

    p {
      color: var(--text-secondary);
    }
  }

  .feature-icon {
    background: rgba(249, 115, 22, 0.15);

    svg {
      color: var(--color-primary);
    }
  }

  .carriers {
    background: var(--bg-light);
  }

  .carrier-item {
    background: var(--bg-white);
    border-color: var(--border-color);
    color: var(--text-muted);
  }

  .cta {
    background: #0f0f0f;
  }

  .cta-content {
    color: #ffffff;

    h2 {
      color: #ffffff;
    }

    p {
      color: #ffffff;
      opacity: 1;
    }
  }

  .footer {
    background: #000000;
  }

  .footer-brand p,
  .footer-links a {
    color: var(--text-muted);

    &:hover {
      color: var(--text-white);
    }
  }

  .footer-bottom {
    border-top-color: rgba(255, 255, 255, 0.05);

    p {
      color: var(--text-muted);
    }
  }
}
</style>
