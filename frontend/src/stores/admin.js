import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminApi } from '@/api'

export const useAdminStore = defineStore('admin', () => {
  // Dashboard
  const dashboardData = ref(null)
  const dashboardLoading = ref(false)

  // Users
  const users = ref([])
  const currentUser = ref(null)
  const usersPagination = ref({ currentPage: 1, lastPage: 1, total: 0 })

  // Companies
  const companies = ref([])
  const currentCompany = ref(null)
  const companiesPagination = ref({ currentPage: 1, lastPage: 1, total: 0 })

  // Carriers
  const carriers = ref([])
  const currentCarrier = ref(null)
  const carriersPagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
  const availableCompanies = ref([])

  // Orders
  const orders = ref([])
  const currentOrder = ref(null)
  const ordersPagination = ref({ currentPage: 1, lastPage: 1, total: 0 })

  const loading = ref(false)
  const error = ref(null)

  // Dashboard
  async function fetchDashboard() {
    dashboardLoading.value = true
    error.value = null
    try {
      dashboardData.value = await adminApi.getDashboard()
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки данных'
    } finally {
      dashboardLoading.value = false
    }
  }

  // Users
  async function fetchUsers(params = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.getUsers(params)
      users.value = response.data
      usersPagination.value = {
        currentPage: response.current_page,
        lastPage: response.last_page,
        total: response.total
      }
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки пользователей'
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(id) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.getUser(id)
      currentUser.value = response.user
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки пользователя'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function createUser(data) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.createUser(data)
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка создания пользователя'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateUser(id, data) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.updateUser(id, data)
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка обновления пользователя'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteUser(id) {
    loading.value = true
    error.value = null
    try {
      await adminApi.deleteUser(id)
      users.value = users.value.filter(u => u.id !== id)
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка удаления пользователя'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function toggleUserActive(id) {
    try {
      const response = await adminApi.toggleUserActive(id)
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = response.user
      }
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка'
      throw e
    }
  }

  // Companies
  async function fetchCompanies(params = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.getCompanies(params)
      companies.value = response.data
      companiesPagination.value = {
        currentPage: response.current_page,
        lastPage: response.last_page,
        total: response.total
      }
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки компаний'
    } finally {
      loading.value = false
    }
  }

  async function fetchCompany(id) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.getCompany(id)
      currentCompany.value = response.company
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки компании'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function createCompany(data) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.createCompany(data)
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка создания компании'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateCompany(id, data) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.updateCompany(id, data)
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка обновления компании'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function verifyCompany(id) {
    try {
      const response = await adminApi.verifyCompany(id)
      const index = companies.value.findIndex(c => c.id === id)
      if (index !== -1) {
        companies.value[index] = response.company
      }
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка верификации'
      throw e
    }
  }

  // Carriers
  async function fetchCarriers(params = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.getCarriers(params)
      carriers.value = response.data
      carriersPagination.value = {
        currentPage: response.current_page,
        lastPage: response.last_page,
        total: response.total
      }
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки перевозчиков'
    } finally {
      loading.value = false
    }
  }

  async function fetchCarrier(id) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.getCarrier(id)
      currentCarrier.value = response.carrier
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки перевозчика'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchAvailableCompanies() {
    try {
      availableCompanies.value = await adminApi.getAvailableCompanies()
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки компаний'
    }
  }

  async function createCarrier(data) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.createCarrier(data)
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка создания перевозчика'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateCarrier(id, data) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.updateCarrier(id, data)
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка обновления перевозчика'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function toggleCarrierActive(id) {
    try {
      const response = await adminApi.toggleCarrierActive(id)
      const index = carriers.value.findIndex(c => c.id === id)
      if (index !== -1) {
        carriers.value[index] = response.carrier
      }
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка'
      throw e
    }
  }

  // Orders
  async function fetchOrders(params = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.getOrders(params)
      orders.value = response.data
      ordersPagination.value = {
        currentPage: response.current_page,
        lastPage: response.last_page,
        total: response.total
      }
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки заказов'
    } finally {
      loading.value = false
    }
  }

  async function fetchOrder(id) {
    loading.value = true
    error.value = null
    try {
      const response = await adminApi.getOrder(id)
      currentOrder.value = response.order
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки заказа'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateOrderStatus(id, status, note = null) {
    try {
      const response = await adminApi.updateOrderStatus(id, status, note)
      const index = orders.value.findIndex(o => o.id === id)
      if (index !== -1) {
        orders.value[index] = response.order
      }
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка обновления статуса'
      throw e
    }
  }

  // Order Statistics
  const orderStatistics = ref({})

  async function fetchOrderStatistics() {
    try {
      orderStatistics.value = await adminApi.getOrderStatistics()
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки статистики'
    }
  }

  // Reset user password
  async function resetUserPassword(id) {
    try {
      const response = await adminApi.resetUserPassword(id)
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка сброса пароля'
      throw e
    }
  }

  // Unverify company
  async function unverifyCompany(id) {
    try {
      const response = await adminApi.unverifyCompany(id)
      const index = companies.value.findIndex(c => c.id === id)
      if (index !== -1) {
        companies.value[index] = response.company
      }
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка'
      throw e
    }
  }

  // Delete company
  async function deleteCompany(id) {
    loading.value = true
    error.value = null
    try {
      await adminApi.deleteCompany(id)
      companies.value = companies.value.filter(c => c.id !== id)
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка удаления компании'
      throw e
    } finally {
      loading.value = false
    }
  }

  // Delete carrier
  async function deleteCarrier(id) {
    loading.value = true
    error.value = null
    try {
      await adminApi.deleteCarrier(id)
      carriers.value = carriers.value.filter(c => c.id !== id)
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка удаления перевозчика'
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    dashboardData,
    dashboardLoading,
    users,
    currentUser,
    usersPagination,
    companies,
    currentCompany,
    companiesPagination,
    carriers,
    currentCarrier,
    carriersPagination,
    availableCompanies,
    orders,
    currentOrder,
    ordersPagination,
    loading,
    error,

    // Actions
    fetchDashboard,
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    deleteUser,
    toggleUserActive,
    fetchCompanies,
    fetchCompany,
    createCompany,
    updateCompany,
    verifyCompany,
    fetchCarriers,
    fetchCarrier,
    fetchAvailableCompanies,
    createCarrier,
    updateCarrier,
    toggleCarrierActive,
    fetchOrders,
    fetchOrder,
    updateOrderStatus,
    orderStatistics,
    fetchOrderStatistics,
    resetUserPassword,
    unverifyCompany,
    deleteCompany,
    deleteCarrier
  }
})
