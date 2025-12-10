import { defineStore } from 'pinia'
import { ref } from 'vue'
import { ordersApi } from '@/api'

export const useOrdersStore = defineStore('orders', () => {
  const orders = ref([])
  const currentOrder = ref(null)
  const trackingEvents = ref([])
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0
  })

  async function fetchOrders(page = 1) {
    loading.value = true
    error.value = null
    try {
      const response = await ordersApi.getAll(page)
      orders.value = response.data
      pagination.value = {
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
      const response = await ordersApi.get(id)
      currentOrder.value = response.order
      trackingEvents.value = response.order.tracking_events || []
      return response.order
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки заказа'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function createOrder(data) {
    loading.value = true
    error.value = null
    try {
      const response = await ordersApi.create(data)
      currentOrder.value = response.order
      return response.order
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка создания заказа'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function cancelOrder(id, reason) {
    loading.value = true
    error.value = null
    try {
      const response = await ordersApi.cancel(id, reason)
      currentOrder.value = response.order
      const index = orders.value.findIndex((o) => o.id === id)
      if (index !== -1) {
        orders.value[index] = response.order
      }
      return response.order
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка отмены заказа'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function confirmOrder(id) {
    loading.value = true
    error.value = null
    try {
      const response = await ordersApi.confirm(id)
      currentOrder.value = response.order
      const index = orders.value.findIndex((o) => o.id === id)
      if (index !== -1) {
        orders.value[index] = response.order
      }
      return response.order
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка подтверждения заказа'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateOrderStatus(id, data) {
    loading.value = true
    error.value = null
    try {
      const response = await ordersApi.updateStatus(id, data)
      currentOrder.value = response.order
      trackingEvents.value = response.order.tracking_events || []
      return response.order
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка обновления статуса'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchTracking(id) {
    loading.value = true
    error.value = null
    try {
      const response = await ordersApi.getTracking(id)
      trackingEvents.value = response.events
      return response
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки трекинга'
      throw e
    } finally {
      loading.value = false
    }
  }

  function clearCurrent() {
    currentOrder.value = null
    trackingEvents.value = []
  }

  return {
    orders,
    currentOrder,
    trackingEvents,
    loading,
    error,
    pagination,
    fetchOrders,
    fetchOrder,
    createOrder,
    cancelOrder,
    confirmOrder,
    updateOrderStatus,
    fetchTracking,
    clearCurrent
  }
})
