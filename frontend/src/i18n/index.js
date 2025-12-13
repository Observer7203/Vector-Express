import { createI18n } from 'vue-i18n'
import ru from './locales/ru.json'
import kk from './locales/kk.json'
import en from './locales/en.json'

// Get language from localStorage or browser
const getBrowserLocale = () => {
  const saved = localStorage.getItem('locale')
  if (saved) return saved

  const browserLang = navigator.language.split('-')[0]
  // Default to Russian for CIS countries
  if (['ru', 'kk', 'en'].includes(browserLang)) {
    return browserLang
  }
  return 'ru' // Default to Russian
}

const i18n = createI18n({
  legacy: false,
  locale: getBrowserLocale(),
  fallbackLocale: 'ru',
  messages: {
    ru,
    kk,
    en
  },
  globalInjection: true,
  missingWarn: false,
  fallbackWarn: false
})

export default i18n

// Helper to change language
export const setLocale = (locale) => {
  i18n.global.locale.value = locale
  localStorage.setItem('locale', locale)
  // Update HTML lang attribute
  document.querySelector('html').setAttribute('lang', locale)
}

// Get current locale
export const getLocale = () => i18n.global.locale.value

// Available locales
export const availableLocales = [
  { code: 'ru', name: 'Русский', flag: '🇷🇺' },
  { code: 'kk', name: 'Қазақша', flag: '🇰🇿' },
  { code: 'en', name: 'English', flag: '🇬🇧' }
]
