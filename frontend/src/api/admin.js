import client from './client'

export const adminApi = {
  // Dashboard
  async getDashboard() {
    const response = await client.get('/admin/dashboard')
    return response.data
  },

  // Users
  async getUsers(params = {}) {
    const response = await client.get('/admin/users', { params })
    return response.data
  },

  async getUser(id) {
    const response = await client.get(`/admin/users/${id}`)
    return response.data
  },

  async createUser(data) {
    const response = await client.post('/admin/users', data)
    return response.data
  },

  async updateUser(id, data) {
    const response = await client.put(`/admin/users/${id}`, data)
    return response.data
  },

  async deleteUser(id) {
    const response = await client.delete(`/admin/users/${id}`)
    return response.data
  },

  async toggleUserActive(id) {
    const response = await client.post(`/admin/users/${id}/toggle-active`)
    return response.data
  },

  async resetUserPassword(id, password) {
    const response = await client.post(`/admin/users/${id}/reset-password`, { password })
    return response.data
  },

  // Companies
  async getCompanies(params = {}) {
    const response = await client.get('/admin/companies', { params })
    return response.data
  },

  async getCompany(id) {
    const response = await client.get(`/admin/companies/${id}`)
    return response.data
  },

  async createCompany(data) {
    const response = await client.post('/admin/companies', data)
    return response.data
  },

  async updateCompany(id, data) {
    const response = await client.put(`/admin/companies/${id}`, data)
    return response.data
  },

  async deleteCompany(id) {
    const response = await client.delete(`/admin/companies/${id}`)
    return response.data
  },

  async verifyCompany(id) {
    const response = await client.post(`/admin/companies/${id}/verify`)
    return response.data
  },

  async unverifyCompany(id) {
    const response = await client.post(`/admin/companies/${id}/unverify`)
    return response.data
  },

  async getCompanyDocuments(companyId) {
    const response = await client.get(`/admin/companies/${companyId}/documents`)
    return response.data
  },

  async getPendingVerification(params = {}) {
    const response = await client.get('/admin/companies/pending-verification', { params })
    return response.data
  },

  // Document verification
  async approveDocument(documentId) {
    const response = await client.post(`/admin/documents/${documentId}/approve`)
    return response.data
  },

  async rejectDocument(documentId, rejectionReason) {
    const response = await client.post(`/admin/documents/${documentId}/reject`, {
      rejection_reason: rejectionReason
    })
    return response.data
  },

  getDocumentDownloadUrl(documentId) {
    const token = localStorage.getItem('token')
    const baseUrl = client.defaults.baseURL
    return `${baseUrl}/documents/${documentId}/download?token=${token}`
  },

  // Carriers
  async getCarriers(params = {}) {
    const response = await client.get('/admin/carriers', { params })
    return response.data
  },

  async getCarrier(id) {
    const response = await client.get(`/admin/carriers/${id}`)
    return response.data
  },

  async createCarrier(data) {
    const response = await client.post('/admin/carriers', data)
    return response.data
  },

  async updateCarrier(id, data) {
    const response = await client.put(`/admin/carriers/${id}`, data)
    return response.data
  },

  async deleteCarrier(id) {
    const response = await client.delete(`/admin/carriers/${id}`)
    return response.data
  },

  async toggleCarrierActive(id) {
    const response = await client.post(`/admin/carriers/${id}/toggle-active`)
    return response.data
  },

  async getAvailableCompanies() {
    const response = await client.get('/admin/carriers/available-companies')
    return response.data
  },

  async getCountries() {
    const response = await client.get('/admin/carriers/countries')
    return response.data
  },

  // Orders
  async getOrders(params = {}) {
    const response = await client.get('/admin/orders', { params })
    return response.data
  },

  async getOrder(id) {
    const response = await client.get(`/admin/orders/${id}`)
    return response.data
  },

  async updateOrderStatus(id, status, note = null) {
    const response = await client.put(`/admin/orders/${id}/status`, { status, note })
    return response.data
  },

  async getOrderStatistics(params = {}) {
    const response = await client.get('/admin/orders/statistics', { params })
    return response.data
  }
}
