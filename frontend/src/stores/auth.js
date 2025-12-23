import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)
  const isCustomer = computed(() => user.value?.role === 'customer')
  const isCarrier = computed(() => user.value?.role === 'carrier')
  const isAdmin = computed(() => user.value?.role === 'admin')

  async function login(data) {
    loading.value = true
    error.value = null
    console.log('=== LOGIN REQUEST ===')
    console.log('Sending data:', { email: data.email, password: '***' })
    try {
      const response = await authApi.login(data)
      console.log('=== LOGIN SUCCESS ===')
      console.log('Response:', response)
      token.value = response.token
      user.value = response.user
      localStorage.setItem('token', response.token)
      return response
    } catch (e) {
      console.log('=== LOGIN ERROR ===')
      console.log('Error status:', e.response?.status)
      console.log('Error data:', e.response?.data)
      console.log('Full error:', e)
      error.value = e.response?.data?.message || 'Ошибка входа'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function register(data) {
    loading.value = true
    error.value = null
    try {
      const response = await authApi.register(data)
      token.value = response.token
      user.value = response.user
      localStorage.setItem('token', response.token)
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка регистрации'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
    }
  }

  async function fetchUser() {
    if (!token.value) return
    loading.value = true
    try {
      const response = await authApi.getUser()
      user.value = response.user
    } catch (e) {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isCustomer,
    isCarrier,
    isAdmin,
    login,
    register,
    logout,
    fetchUser
  }
})
