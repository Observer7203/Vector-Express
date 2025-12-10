import client from './client'

export const authApi = {
  async register(data) {
    const response = await client.post('/auth/register', data)
    return response.data
  },

  async login(data) {
    const response = await client.post('/auth/login', data)
    return response.data
  },

  async logout() {
    await client.post('/auth/logout')
  },

  async getUser() {
    const response = await client.get('/auth/user')
    return response.data
  },

  async forgotPassword(email) {
    const response = await client.post('/auth/forgot-password', { email })
    return response.data
  },

  async resetPassword(data) {
    const response = await client.post('/auth/reset-password', data)
    return response.data
  }
}
