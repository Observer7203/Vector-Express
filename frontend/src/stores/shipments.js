import { defineStore } from 'pinia'
import { ref } from 'vue'
import { shipmentsApi } from '@/api'

export const useShipmentsStore = defineStore('shipments', () => {
  const shipments = ref([])
  const currentShipment = ref(null)
  const quotes = ref([])
  const loading = ref(false)
  const calculating = ref(false)
  const error = ref(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0
  })

  async function fetchShipments(page = 1) {
    loading.value = true
    error.value = null
    try {
      const response = await shipmentsApi.getAll(page)
      shipments.value = response.data
      pagination.value = {
        currentPage: response.current_page,
        lastPage: response.last_page,
        total: response.total
      }
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки заявок'
    } finally {
      loading.value = false
    }
  }

  async function fetchShipment(id) {
    loading.value = true
    error.value = null
    try {
      const response = await shipmentsApi.get(id)
      currentShipment.value = response.shipment
      return response.shipment
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки заявки'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function createShipment(data) {
    loading.value = true
    error.value = null
    try {
      const response = await shipmentsApi.create(data)
      currentShipment.value = response.shipment
      return response.shipment
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка создания заявки'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateShipment(id, data) {
    loading.value = true
    error.value = null
    try {
      const response = await shipmentsApi.update(id, data)
      currentShipment.value = response.shipment
      return response.shipment
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка обновления заявки'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteShipment(id) {
    loading.value = true
    error.value = null
    try {
      await shipmentsApi.delete(id)
      shipments.value = shipments.value.filter((s) => s.id !== id)
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка удаления заявки'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function calculateQuotes(id) {
    calculating.value = true
    error.value = null
    try {
      const response = await shipmentsApi.calculate(id)
      quotes.value = response.quotes
      return response.quotes
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка расчета'
      throw e
    } finally {
      calculating.value = false
    }
  }

  async function fetchQuotes(id, sort = 'price', order = 'asc') {
    loading.value = true
    error.value = null
    try {
      const response = await shipmentsApi.getQuotes(id, { sort, order })
      currentShipment.value = response.shipment
      quotes.value = response.quotes
      return response.quotes
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки предложений'
      throw e
    } finally {
      loading.value = false
    }
  }

  function clearCurrent() {
    currentShipment.value = null
    quotes.value = []
  }

  return {
    shipments,
    currentShipment,
    quotes,
    loading,
    calculating,
    error,
    pagination,
    fetchShipments,
    fetchShipment,
    createShipment,
    updateShipment,
    deleteShipment,
    calculateQuotes,
    fetchQuotes,
    clearCurrent
  }
})
