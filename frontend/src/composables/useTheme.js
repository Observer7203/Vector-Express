import { ref, watch, onMounted } from 'vue'

const THEME_KEY = 'vector-express-theme'
const isDark = ref(false)

export function useTheme() {
  const initTheme = () => {
    const savedTheme = localStorage.getItem(THEME_KEY)
    if (savedTheme) {
      isDark.value = savedTheme === 'dark'
    } else {
      // Default to light theme
      isDark.value = false
    }
    applyTheme()
  }

  const applyTheme = () => {
    if (isDark.value) {
      document.documentElement.setAttribute('data-theme', 'dark')
    } else {
      document.documentElement.removeAttribute('data-theme')
    }
  }

  const toggleTheme = () => {
    isDark.value = !isDark.value
    localStorage.setItem(THEME_KEY, isDark.value ? 'dark' : 'light')
    applyTheme()
  }

  const setTheme = (dark) => {
    isDark.value = dark
    localStorage.setItem(THEME_KEY, dark ? 'dark' : 'light')
    applyTheme()
  }

  // Watch for changes from other components
  watch(isDark, () => {
    applyTheme()
  })

  return {
    isDark,
    toggleTheme,
    setTheme,
    initTheme
  }
}
