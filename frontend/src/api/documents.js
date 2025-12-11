import client from './client'

export const documentsApi = {
  // Получить документы компании пользователя
  async getDocuments() {
    const response = await client.get('/company-documents')
    return response.data
  },

  // Загрузить документ
  async uploadDocument(documentType, file, notes = null) {
    const formData = new FormData()
    formData.append('document_type', documentType)
    formData.append('file', file)
    if (notes) {
      formData.append('notes', notes)
    }

    const response = await client.post('/company-documents', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data
  },

  // Получить информацию о документе
  async getDocument(id) {
    const response = await client.get(`/company-documents/${id}`)
    return response.data
  },

  // Удалить документ
  async deleteDocument(id) {
    const response = await client.delete(`/company-documents/${id}`)
    return response.data
  },

  // Получить статус верификации компании
  async getVerificationStatus() {
    const response = await client.get('/company-documents/verification-status')
    return response.data
  },

  // Получить URL для скачивания документа
  getDownloadUrl(id) {
    const token = localStorage.getItem('token')
    const baseUrl = client.defaults.baseURL
    return `${baseUrl}/company-documents/${id}/download?token=${token}`
  }
}
