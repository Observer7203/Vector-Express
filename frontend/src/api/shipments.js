import client from './client'

export const shipmentsApi = {
  async getAll(page = 1) {
    const response = await client.get('/shipments', { params: { page } })
    return response.data
  },

  async get(id) {
    const response = await client.get(`/shipments/${id}`)
    return response.data
  },

  async create(data) {
    const response = await client.post('/shipments', data)
    return response.data
  },

  async update(id, data) {
    const response = await client.put(`/shipments/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await client.delete(`/shipments/${id}`)
    return response.data
  },

  async calculate(id) {
    const response = await client.post(`/shipments/${id}/calculate`)
    return response.data
  },

  async getQuotes(id, params = {}) {
    const response = await client.get(`/shipments/${id}/quotes`, { params })
    return response.data
  }
}
