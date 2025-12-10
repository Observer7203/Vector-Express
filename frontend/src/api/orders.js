import client from './client'

export const ordersApi = {
  async getAll(page = 1) {
    const response = await client.get('/orders', { params: { page } })
    return response.data
  },

  async get(id) {
    const response = await client.get(`/orders/${id}`)
    return response.data
  },

  async create(data) {
    const response = await client.post('/orders', data)
    return response.data
  },

  async update(id, data) {
    const response = await client.put(`/orders/${id}`, data)
    return response.data
  },

  async cancel(id, reason) {
    const response = await client.post(`/orders/${id}/cancel`, { reason })
    return response.data
  },

  async confirm(id) {
    const response = await client.post(`/orders/${id}/confirm`)
    return response.data
  },

  async updateStatus(id, data) {
    const response = await client.put(`/orders/${id}/status`, data)
    return response.data
  },

  async getTracking(id) {
    const response = await client.get(`/orders/${id}/tracking`)
    return response.data
  }
}

export const trackingApi = {
  async track(trackingNumber) {
    const response = await client.post('/tracking', { tracking_number: trackingNumber })
    return response.data
  }
}
