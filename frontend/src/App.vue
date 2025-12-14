<script setup>
import { RouterView } from 'vue-router'
import { onMounted } from 'vue'
import { useTheme } from '@/composables/useTheme'

const { initTheme } = useTheme()

onMounted(() => {
  initTheme()
})
</script>

<template>
  <RouterView />
</template>

<style lang="scss">
@import './styles/_dark-theme.scss';

// Light theme CSS variables (default)
:root {
  --bg-white: #ffffff;
  --bg-light: #f9fafb;
  --bg-hover: #f3f4f6;
  --bg-dark: #111827;
  --bg-card: #ffffff;

  --text-primary: #1f2937;
  --text-secondary: #6b7280;
  --text-muted: #9ca3af;
  --text-white: #ffffff;

  --color-primary: #2e5492;
  --color-primary-dark: #1e3a6e;
  --color-primary-light: #4a7cc9;
  --color-secondary: #5c7caa;

  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --color-info: #3b82f6;

  --border-color: #e5e7eb;
  --border-color-dark: #d1d5db;

  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell,
    'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: var(--text-primary);
  background: var(--bg-light);
  transition: background-color 0.2s ease, color 0.2s ease;
}

a {
  color: inherit;
}

button {
  font-family: inherit;
}

input,
select,
textarea {
  font-family: inherit;
}
</style>
