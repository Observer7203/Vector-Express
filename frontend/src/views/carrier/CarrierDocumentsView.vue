<script setup>
import { onMounted, ref, computed } from 'vue'
import {
  FileText,
  Upload,
  Check,
  X,
  Clock,
  AlertCircle,
  Download,
  Trash2,
  Shield,
  CheckCircle2,
  XCircle
} from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import { documentsApi } from '@/api/documents'

const iconStrokeWidth = 1.2

const loading = ref(true)
const uploading = ref(false)
const documents = ref([])
const missingDocuments = ref([])
const documentTypeLabels = ref({})
const verificationStatus = ref(null)
const showUploadModal = ref(false)
const selectedDocumentType = ref('')
const selectedFile = ref(null)
const uploadNotes = ref('')
const uploadError = ref('')

const statusConfig = {
  pending: {
    label: 'На проверке',
    icon: Clock,
    class: 'status-pending'
  },
  approved: {
    label: 'Подтвержден',
    icon: Check,
    class: 'status-approved'
  },
  rejected: {
    label: 'Отклонен',
    icon: X,
    class: 'status-rejected'
  },
  not_uploaded: {
    label: 'Не загружен',
    icon: AlertCircle,
    class: 'status-not-uploaded'
  }
}

const isCompanyVerified = computed(() => verificationStatus.value?.company_verified)
const verificationMessage = computed(() => verificationStatus.value?.message || '')

const loadDocuments = async () => {
  loading.value = true
  try {
    const data = await documentsApi.getDocuments()
    documents.value = data.documents || []
    missingDocuments.value = data.missing_documents || []
    documentTypeLabels.value = data.document_type_labels || {}

    // Также загружаем статус верификации
    const status = await documentsApi.getVerificationStatus()
    verificationStatus.value = status
  } catch (error) {
    console.error('Failed to load documents:', error)
  } finally {
    loading.value = false
  }
}

const openUploadModal = (docType = '') => {
  selectedDocumentType.value = docType
  selectedFile.value = null
  uploadNotes.value = ''
  uploadError.value = ''
  showUploadModal.value = true
}

const closeUploadModal = () => {
  showUploadModal.value = false
  selectedDocumentType.value = ''
  selectedFile.value = null
  uploadNotes.value = ''
  uploadError.value = ''
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 10 * 1024 * 1024) {
      uploadError.value = 'Файл слишком большой. Максимальный размер: 10 МБ'
      return
    }
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg']
    if (!allowedTypes.includes(file.type)) {
      uploadError.value = 'Недопустимый формат файла. Разрешены: PDF, JPG, PNG'
      return
    }
    selectedFile.value = file
    uploadError.value = ''
  }
}

const uploadDocument = async () => {
  if (!selectedDocumentType.value || !selectedFile.value) {
    uploadError.value = 'Выберите тип документа и файл'
    return
  }

  uploading.value = true
  uploadError.value = ''

  try {
    await documentsApi.uploadDocument(
      selectedDocumentType.value,
      selectedFile.value,
      uploadNotes.value
    )
    closeUploadModal()
    await loadDocuments()
  } catch (error) {
    uploadError.value = error.response?.data?.message || 'Ошибка при загрузке документа'
  } finally {
    uploading.value = false
  }
}

const deleteDocument = async (docId) => {
  if (!confirm('Удалить документ?')) return

  try {
    await documentsApi.deleteDocument(docId)
    await loadDocuments()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка при удалении документа')
  }
}

const downloadDocument = (docId) => {
  window.open(documentsApi.getDownloadUrl(docId), '_blank')
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatFileSize = (bytes) => {
  if (!bytes) return '-'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const getDocumentByType = (type) => {
  return documents.value.find(d => d.document_type === type)
}

const requiredDocumentTypes = ['registration_certificate', 'transport_license', 'insurance_policy']

onMounted(loadDocuments)
</script>

<template>
  <div class="carrier-documents">
    <AppHeader />

    <main class="documents-main">
      <div class="container">
        <div class="page-header">
          <div class="page-title">
            <h1>Документы компании</h1>
            <p class="subtitle">Загрузите документы для верификации и начала работы</p>
          </div>
        </div>

        <!-- Verification Status Banner -->
        <div v-if="verificationStatus" class="verification-banner" :class="{ verified: isCompanyVerified }">
          <div class="banner-icon">
            <Shield v-if="!isCompanyVerified" :size="24" :stroke-width="iconStrokeWidth" />
            <CheckCircle2 v-else :size="24" :stroke-width="iconStrokeWidth" />
          </div>
          <div class="banner-content">
            <h3 v-if="isCompanyVerified">Компания верифицирована</h3>
            <h3 v-else>Верификация компании</h3>
            <p>{{ verificationMessage }}</p>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Загрузка документов...</p>
        </div>

        <!-- Documents Content -->
        <div v-else class="documents-content">
          <!-- Required Documents Section -->
          <section class="documents-section">
            <div class="section-header">
              <h2>Обязательные документы</h2>
              <p>Для верификации компании необходимо загрузить следующие документы</p>
            </div>

            <div class="documents-grid">
              <div
                v-for="docType in requiredDocumentTypes"
                :key="docType"
                class="document-card"
                :class="{ 'has-document': getDocumentByType(docType) }"
              >
                <div class="document-icon">
                  <FileText :size="32" :stroke-width="iconStrokeWidth" />
                </div>

                <div class="document-info">
                  <h3>{{ documentTypeLabels[docType] || docType }}</h3>

                  <template v-if="getDocumentByType(docType)">
                    <div
                      class="document-status"
                      :class="statusConfig[getDocumentByType(docType).status]?.class"
                    >
                      <component
                        :is="statusConfig[getDocumentByType(docType).status]?.icon"
                        :size="16"
                        :stroke-width="iconStrokeWidth"
                      />
                      <span>{{ statusConfig[getDocumentByType(docType).status]?.label }}</span>
                    </div>

                    <div class="document-details">
                      <span class="file-name">{{ getDocumentByType(docType).file_name }}</span>
                      <span class="file-size">{{ formatFileSize(getDocumentByType(docType).file_size) }}</span>
                      <span class="upload-date">{{ formatDate(getDocumentByType(docType).created_at) }}</span>
                    </div>

                    <div
                      v-if="getDocumentByType(docType).status === 'rejected'"
                      class="rejection-reason"
                    >
                      <XCircle :size="16" :stroke-width="iconStrokeWidth" />
                      <span>{{ getDocumentByType(docType).rejection_reason }}</span>
                    </div>

                    <div class="document-actions">
                      <button
                        class="btn-icon"
                        @click="downloadDocument(getDocumentByType(docType).id)"
                        title="Скачать"
                      >
                        <Download :size="18" :stroke-width="iconStrokeWidth" />
                      </button>
                      <button
                        v-if="getDocumentByType(docType).status !== 'approved'"
                        class="btn-icon btn-danger"
                        @click="deleteDocument(getDocumentByType(docType).id)"
                        title="Удалить"
                      >
                        <Trash2 :size="18" :stroke-width="iconStrokeWidth" />
                      </button>
                    </div>
                  </template>

                  <template v-else>
                    <div class="document-status status-not-uploaded">
                      <AlertCircle :size="16" :stroke-width="iconStrokeWidth" />
                      <span>Не загружен</span>
                    </div>
                    <button class="btn-upload" @click="openUploadModal(docType)">
                      <Upload :size="18" :stroke-width="iconStrokeWidth" />
                      <span>Загрузить</span>
                    </button>
                  </template>
                </div>
              </div>
            </div>
          </section>

          <!-- Other Documents Section -->
          <section class="documents-section">
            <div class="section-header">
              <h2>Дополнительные документы</h2>
              <button class="btn-add" @click="openUploadModal()">
                <Upload :size="18" :stroke-width="iconStrokeWidth" />
                <span>Загрузить документ</span>
              </button>
            </div>

            <div class="documents-table" v-if="documents.filter(d => !requiredDocumentTypes.includes(d.document_type)).length">
              <table>
                <thead>
                  <tr>
                    <th>Тип документа</th>
                    <th>Файл</th>
                    <th>Размер</th>
                    <th>Дата загрузки</th>
                    <th>Статус</th>
                    <th>Действия</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="doc in documents.filter(d => !requiredDocumentTypes.includes(d.document_type))"
                    :key="doc.id"
                  >
                    <td>{{ documentTypeLabels[doc.document_type] || doc.document_type }}</td>
                    <td>{{ doc.file_name }}</td>
                    <td>{{ formatFileSize(doc.file_size) }}</td>
                    <td>{{ formatDate(doc.created_at) }}</td>
                    <td>
                      <span class="status-badge" :class="statusConfig[doc.status]?.class">
                        {{ statusConfig[doc.status]?.label }}
                      </span>
                    </td>
                    <td>
                      <div class="table-actions">
                        <button class="btn-icon" @click="downloadDocument(doc.id)">
                          <Download :size="16" :stroke-width="iconStrokeWidth" />
                        </button>
                        <button
                          v-if="doc.status !== 'approved'"
                          class="btn-icon btn-danger"
                          @click="deleteDocument(doc.id)"
                        >
                          <Trash2 :size="16" :stroke-width="iconStrokeWidth" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="empty-state">
              <FileText :size="48" :stroke-width="iconStrokeWidth" />
              <p>Дополнительные документы не загружены</p>
            </div>
          </section>
        </div>

        <!-- Upload Modal -->
        <div v-if="showUploadModal" class="modal-overlay" @click.self="closeUploadModal">
          <div class="modal">
            <div class="modal-header">
              <h3>Загрузка документа</h3>
              <button class="btn-close" @click="closeUploadModal">
                <X :size="20" :stroke-width="iconStrokeWidth" />
              </button>
            </div>

            <div class="modal-body">
              <div class="form-group">
                <label>Тип документа *</label>
                <select v-model="selectedDocumentType" :disabled="!!selectedDocumentType && requiredDocumentTypes.includes(selectedDocumentType)">
                  <option value="">Выберите тип</option>
                  <option
                    v-for="(label, type) in documentTypeLabels"
                    :key="type"
                    :value="type"
                  >
                    {{ label }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label>Файл *</label>
                <div class="file-upload">
                  <input
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png"
                    @change="handleFileSelect"
                  />
                  <div v-if="!selectedFile" class="file-placeholder">
                    <Upload :size="24" :stroke-width="iconStrokeWidth" />
                    <span>Выберите файл или перетащите сюда</span>
                    <small>PDF, JPG, PNG до 10 МБ</small>
                  </div>
                  <div v-else class="file-selected">
                    <FileText :size="24" :stroke-width="iconStrokeWidth" />
                    <span>{{ selectedFile.name }}</span>
                    <small>{{ formatFileSize(selectedFile.size) }}</small>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Примечание</label>
                <textarea
                  v-model="uploadNotes"
                  placeholder="Дополнительная информация о документе..."
                  rows="3"
                ></textarea>
              </div>

              <div v-if="uploadError" class="error-message">
                <AlertCircle :size="16" :stroke-width="iconStrokeWidth" />
                {{ uploadError }}
              </div>
            </div>

            <div class="modal-footer">
              <button class="btn btn-secondary" @click="closeUploadModal">Отмена</button>
              <button
                class="btn btn-primary"
                @click="uploadDocument"
                :disabled="uploading || !selectedDocumentType || !selectedFile"
              >
                <span v-if="uploading">Загрузка...</span>
                <span v-else>Загрузить</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.carrier-documents {
  min-height: 100vh;
  background: $bg-light;
}

.documents-main {
  padding: 2rem 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.page-header {
  margin-bottom: 2rem;

  h1 {
    font-size: 1.75rem;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 0.5rem;
  }

  .subtitle {
    color: $text-secondary;
    margin: 0;
  }
}

// Verification Banner
.verification-banner {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
  border: 1px solid #fed7aa;
  border-radius: 12px;
  margin-bottom: 2rem;

  &.verified {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-color: #a7f3d0;

    .banner-icon {
      color: #059669;
    }
  }

  .banner-icon {
    color: #ea580c;
    flex-shrink: 0;
  }

  .banner-content {
    h3 {
      font-size: 1rem;
      font-weight: 600;
      color: $text-primary;
      margin: 0 0 0.25rem;
    }

    p {
      color: $text-secondary;
      margin: 0;
      font-size: 0.875rem;
    }
  }
}

// Loading
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: $text-secondary;

  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid $border-color;
    border-top-color: $color-primary;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

// Sections
.documents-section {
  margin-bottom: 2.5rem;

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;

    h2 {
      font-size: 1.25rem;
      font-weight: 600;
      color: $text-primary;
      margin: 0;
    }

    p {
      color: $text-secondary;
      font-size: 0.875rem;
      margin: 0.25rem 0 0;
    }
  }
}

// Documents Grid
.documents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
}

.document-card {
  background: white;
  border: 1px solid $border-color;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  transition: all 0.2s;

  &:hover {
    border-color: $color-primary;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  }

  &.has-document {
    .document-icon {
      color: $color-primary;
    }
  }

  .document-icon {
    color: $text-muted;
    flex-shrink: 0;
  }

  .document-info {
    flex: 1;
    min-width: 0;

    h3 {
      font-size: 1rem;
      font-weight: 600;
      color: $text-primary;
      margin: 0 0 0.75rem;
    }
  }

  .document-status {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 0.75rem;

    &.status-pending {
      background: #fef3c7;
      color: #92400e;
    }

    &.status-approved {
      background: #d1fae5;
      color: #065f46;
    }

    &.status-rejected {
      background: #fee2e2;
      color: #991b1b;
    }

    &.status-not-uploaded {
      background: $bg-light;
      color: $text-muted;
    }
  }

  .document-details {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;

    span {
      font-size: 0.75rem;
      color: $text-secondary;

      &.file-name {
        flex-basis: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }
  }

  .rejection-reason {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #fef2f2;
    border-radius: 8px;
    margin-bottom: 0.75rem;

    svg {
      color: #dc2626;
      flex-shrink: 0;
      margin-top: 2px;
    }

    span {
      font-size: 0.813rem;
      color: #991b1b;
    }
  }

  .document-actions {
    display: flex;
    gap: 0.5rem;
  }
}

// Buttons
.btn-upload {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.375rem 0.75rem;
  margin-top: 0.75rem;
  background: $color-primary;
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    background: $color-primary-dark;
  }

  svg {
    width: 14px;
    height: 14px;
  }
}

.btn-add {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: white;
  color: $color-primary;
  border: 1px solid $color-primary;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    background: rgba($color-primary, 0.05);
  }
}

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: $bg-light;
  border: none;
  border-radius: 6px;
  color: $text-secondary;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    background: darken($bg-light, 5%);
    color: $text-primary;
  }

  &.btn-danger:hover {
    background: #fee2e2;
    color: #dc2626;
  }
}

// Table
.documents-table {
  background: white;
  border: 1px solid $border-color;
  border-radius: 12px;
  overflow: hidden;

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th, td {
    padding: 0.875rem 1rem;
    text-align: left;
    border-bottom: 1px solid $border-color;
  }

  th {
    background: $bg-light;
    font-size: 0.75rem;
    font-weight: 600;
    color: $text-secondary;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  td {
    font-size: 0.875rem;
    color: $text-primary;
  }

  tbody tr:last-child td {
    border-bottom: none;
  }

  .table-actions {
    display: flex;
    gap: 0.5rem;
  }
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.625rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;

  &.status-pending {
    background: #fef3c7;
    color: #92400e;
  }

  &.status-approved {
    background: #d1fae5;
    color: #065f46;
  }

  &.status-rejected {
    background: #fee2e2;
    color: #991b1b;
  }
}

// Empty state
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  background: white;
  border: 1px dashed $border-color;
  border-radius: 12px;
  color: $text-muted;

  svg {
    margin-bottom: 1rem;
    opacity: 0.5;
  }

  p {
    margin: 0;
    font-size: 0.875rem;
  }
}

// Modal
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid $border-color;

    h3 {
      font-size: 1.125rem;
      font-weight: 600;
      color: $text-primary;
      margin: 0;
    }

    .btn-close {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      background: none;
      border: none;
      color: $text-secondary;
      cursor: pointer;
      border-radius: 6px;
      transition: all 0.2s;

      &:hover {
        background: $bg-light;
        color: $text-primary;
      }
    }
  }

  .modal-body {
    padding: 1.5rem;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid $border-color;
    background: $bg-light;
  }
}

.form-group {
  margin-bottom: 1.25rem;

  label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: $text-primary;
    margin-bottom: 0.5rem;
  }

  select, textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid $border-color;
    border-radius: 8px;
    font-size: 0.875rem;
    color: $text-primary;
    transition: all 0.2s;

    &:focus {
      outline: none;
      border-color: $color-primary;
      box-shadow: 0 0 0 3px rgba($color-primary, 0.1);
    }
  }

  textarea {
    resize: vertical;
    min-height: 80px;
  }
}

.file-upload {
  position: relative;
  border: 2px dashed $border-color;
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  transition: all 0.2s;
  cursor: pointer;

  &:hover {
    border-color: $color-primary;
    background: rgba($color-primary, 0.02);
  }

  input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
  }

  .file-placeholder, .file-selected {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: $text-secondary;

    span {
      font-size: 0.875rem;
    }

    small {
      font-size: 0.75rem;
      color: $text-muted;
    }
  }

  .file-selected {
    color: $color-primary;
  }
}

.error-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #fef2f2;
  border-radius: 8px;
  color: #dc2626;
  font-size: 0.875rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;

  &.btn-primary {
    background: $color-primary;
    color: white;

    &:hover:not(:disabled) {
      background: $color-primary-dark;
    }

    &:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
  }

  &.btn-secondary {
    background: white;
    color: $text-primary;
    border: 1px solid $border-color;

    &:hover {
      background: $bg-light;
    }
  }
}
</style>

<style lang="scss">
/* Dark theme styles for CarrierDocumentsView */
[data-theme="dark"] {
  .carrier-documents {
    background: #1a1a1a !important;
  }

  .carrier-documents .dashboard-main {
    background: #1a1a1a !important;
  }

  .carrier-documents .page-title h1 {
    color: #f5f5f5 !important;
  }

  .carrier-documents .page-title .subtitle {
    color: #999999 !important;
  }

  /* Verification status card */
  .carrier-documents .verification-status {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;
  }

  .carrier-documents .verification-status h3 {
    color: #f5f5f5 !important;
  }

  .carrier-documents .verification-status p {
    color: #999999 !important;
  }

  /* Documents section */
  .carrier-documents .documents-section {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;
  }

  .carrier-documents .section-title {
    color: #f5f5f5 !important;
    border-color: #2a2a2a !important;
  }

  .carrier-documents .document-card {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;

    &:hover {
      border-color: #f97316 !important;
    }
  }

  .carrier-documents .document-icon {
    background: rgba(249, 115, 22, 0.1) !important;

    svg {
      color: #f97316 !important;
    }
  }

  .carrier-documents .document-name {
    color: #f5f5f5 !important;
  }

  .carrier-documents .document-date {
    color: #999999 !important;
  }

  .carrier-documents .document-actions .btn-icon {
    color: #999999 !important;

    &:hover {
      background: #252525 !important;
      color: #f5f5f5 !important;
    }

    &.btn-icon-danger:hover {
      background: rgba(220, 53, 69, 0.2) !important;
      color: #dc3545 !important;
    }
  }

  /* Missing documents */
  .carrier-documents .missing-documents {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;
  }

  .carrier-documents .missing-doc {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;

    span {
      color: #f5f5f5 !important;
    }
  }

  .carrier-documents .empty-state {
    background: #0f0f0f !important;
    border-color: #2a2a2a !important;

    svg {
      color: #666666 !important;
    }

    h3 {
      color: #f5f5f5 !important;
    }

    p {
      color: #999999 !important;
    }
  }

  .carrier-documents .loading-state {
    color: #999999 !important;

    .spinner {
      border-color: #2a2a2a !important;
      border-top-color: #f97316 !important;
    }
  }

  /* Modal dark theme */
  .carrier-documents .modal {
    background: #0f0f0f !important;
    border: 1px solid #2a2a2a !important;
  }

  .carrier-documents .modal-header {
    border-color: #2a2a2a !important;

    h2 {
      color: #f5f5f5 !important;
    }
  }

  .carrier-documents .modal-footer {
    border-color: #2a2a2a !important;
  }

  .carrier-documents .btn-close {
    color: #999999 !important;

    &:hover {
      background: #252525 !important;
    }
  }

  .carrier-documents .form-group label {
    color: #f5f5f5 !important;
  }

  .carrier-documents .form-input,
  .carrier-documents .form-textarea {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;

    option {
      background: #1a1a1a !important;
      color: #f5f5f5 !important;
    }
  }

  .carrier-documents .upload-zone {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
    color: #999999 !important;

    &:hover {
      border-color: #f97316 !important;
    }
  }

  /* File upload drag and drop area */
  .carrier-documents .file-upload {
    background: #1a1a1a !important;
    border-color: #3a3a3a !important;

    &:hover {
      border-color: #f97316 !important;
      background: rgba(249, 115, 22, 0.05) !important;
    }

    .file-placeholder,
    .file-selected {
      color: #999999 !important;

      svg {
        color: #666666 !important;
      }

      span {
        color: #999999 !important;
      }

      small {
        color: #666666 !important;
      }
    }

    .file-selected {
      color: #f97316 !important;

      svg {
        color: #f97316 !important;
      }
    }
  }

  /* Modal primary button - white text */
  .carrier-documents .modal .btn-primary,
  .carrier-documents .modal-footer .btn-primary,
  .carrier-documents .modal .btn.btn-primary,
  .carrier-documents .modal-footer .btn.btn-primary {
    background: #f97316 !important;
    border-color: #f97316 !important;
    color: #ffffff !important;

    span {
      color: #ffffff !important;
    }

    &:hover {
      background: #ea580c !important;
      border-color: #ea580c !important;
    }

    &:disabled {
      background: #f97316 !important;
      opacity: 0.6;
      color: #ffffff !important;

      span {
        color: #ffffff !important;
      }
    }
  }

  .carrier-documents .file-info {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;
  }

  .carrier-documents .error-message {
    background: rgba(220, 53, 69, 0.1) !important;
    color: #dc3545 !important;
  }

  .carrier-documents .btn-secondary {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;

    &:hover {
      background: #252525 !important;
    }
  }

  /* Verification banner dark theme */
  .carrier-documents .verification-banner {
    background: linear-gradient(135deg, #1a1a1a 0%, #252525 100%) !important;
    border-color: #f97316 !important;

    &.verified {
      background: linear-gradient(135deg, #0f2318 0%, #14532d 100%) !important;
      border-color: #22c55e !important;

      .banner-icon {
        color: #22c55e !important;
      }
    }

    .banner-icon {
      color: #f97316 !important;
    }

    .banner-content h3 {
      color: #f5f5f5 !important;
    }

    .banner-content p {
      color: #999999 !important;
    }
  }

  /* Document icon - remove background, only color svg */
  .carrier-documents .document-icon {
    background: transparent !important;

    svg {
      color: #f97316 !important;
    }
  }

  .carrier-documents .document-card.has-document .document-icon svg {
    color: #f97316 !important;
  }

  /* Document info text */
  .carrier-documents .document-info h3 {
    color: #f5f5f5 !important;
  }

  .carrier-documents .document-details span {
    color: #999999 !important;
  }

  /* Btn-icon in document cards */
  .carrier-documents .document-actions .btn-icon {
    background: #252525 !important;
    border: 1px solid #2a2a2a !important;
    color: #999999 !important;

    &:hover {
      background: #333333 !important;
      border-color: #f97316 !important;
      color: #f97316 !important;
    }

    &.btn-danger {
      &:hover {
        background: rgba(220, 53, 69, 0.2) !important;
        border-color: #dc3545 !important;
        color: #dc3545 !important;
      }
    }
  }

  /* Modal footer dark theme */
  .carrier-documents .modal-footer {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
  }

  /* Section header in documents */
  .carrier-documents .section-header h2 {
    color: #f5f5f5 !important;
  }

  .carrier-documents .section-header p {
    color: #999999 !important;
  }

  /* Btn-add button */
  .carrier-documents .btn-add {
    background: transparent !important;
    border-color: #f97316 !important;
    color: #f97316 !important;

    &:hover {
      background: rgba(249, 115, 22, 0.1) !important;
    }
  }

  /* Btn-upload button in dark theme - orange with white text */
  .carrier-documents .btn-upload,
  .carrier-documents .document-card .btn-upload {
    background: #f97316 !important;
    color: #ffffff !important;
    margin-left: 0.5rem !important;

    span {
      color: #ffffff !important;
    }

    svg {
      color: #ffffff !important;
    }

    &:hover {
      background: #ea580c !important;
    }
  }
}
</style>
