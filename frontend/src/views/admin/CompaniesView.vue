<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAdminStore } from '@/stores/admin'
import {
  Plus,
  Search,
  Building2,
  Edit,
  Trash2,
  CheckCircle,
  XCircle,
  X,
  Shield,
  ShieldOff,
  FileText,
  Download,
  Clock,
  AlertCircle
} from 'lucide-vue-next'
import { adminApi } from '@/api/admin'

const { t } = useI18n()
const adminStore = useAdminStore()
const iconStrokeWidth = 1.5

const search = ref('')
const filterType = ref('')
const filterVerified = ref('')
const showModal = ref(false)
const editingCompany = ref(null)
const saving = ref(false)

const form = ref({
  name: '',
  inn: '',
  type: 'carrier',
  legal_address: '',
  actual_address: '',
  phone: '',
  email: '',
  website: ''
})

// Documents modal
const showDocumentsModal = ref(false)
const selectedCompany = ref(null)
const companyDocuments = ref([])
const loadingDocuments = ref(false)
const documentTypeLabels = ref({})
const rejectionReason = ref('')
const rejectingDocumentId = ref(null)

onMounted(() => {
  adminStore.fetchCompanies()
})

watch([search, filterType, filterVerified], () => {
  adminStore.fetchCompanies({
    search: search.value,
    type: filterType.value,
    verified: filterVerified.value
  })
})

const companies = computed(() => adminStore.companies)
const loading = computed(() => adminStore.loading)
const pagination = computed(() => adminStore.companiesPagination)

function openCreateModal() {
  editingCompany.value = null
  form.value = {
    name: '',
    inn: '',
    type: 'carrier',
    legal_address: '',
    actual_address: '',
    phone: '',
    email: '',
    website: ''
  }
  showModal.value = true
}

function openEditModal(company) {
  editingCompany.value = company
  form.value = {
    name: company.name,
    inn: company.inn || '',
    type: company.type,
    legal_address: company.legal_address || '',
    actual_address: company.actual_address || '',
    phone: company.phone || '',
    email: company.email || '',
    website: company.website || ''
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingCompany.value = null
}

async function saveCompany() {
  saving.value = true
  try {
    if (editingCompany.value) {
      await adminStore.updateCompany(editingCompany.value.id, form.value)
    } else {
      await adminStore.createCompany(form.value)
    }
    await adminStore.fetchCompanies()
    closeModal()
  } catch (e) {
    console.error('Error saving company:', e)
  } finally {
    saving.value = false
  }
}

async function verifyCompany(company) {
  try {
    await adminStore.verifyCompany(company.id)
  } catch (e) {
    console.error('Error verifying company:', e)
  }
}

async function deleteCompany(company) {
  if (!confirm(t('adminCompanies.confirmDelete', { name: company.name }))) return

  try {
    await adminApi.deleteCompany(company.id)
    await adminStore.fetchCompanies()
  } catch (e) {
    console.error('Error deleting company:', e)
  }
}

function changePage(page) {
  adminStore.fetchCompanies({
    page,
    search: search.value,
    type: filterType.value,
    verified: filterVerified.value
  })
}

// Documents functionality
async function openDocumentsModal(company) {
  selectedCompany.value = company
  showDocumentsModal.value = true
  loadingDocuments.value = true
  rejectionReason.value = ''
  rejectingDocumentId.value = null

  try {
    const data = await adminApi.getCompanyDocuments(company.id)
    companyDocuments.value = data.documents || []
    documentTypeLabels.value = data.document_type_labels || {}
  } catch (e) {
    console.error('Error loading documents:', e)
    companyDocuments.value = []
  } finally {
    loadingDocuments.value = false
  }
}

function closeDocumentsModal() {
  showDocumentsModal.value = false
  selectedCompany.value = null
  companyDocuments.value = []
  rejectionReason.value = ''
  rejectingDocumentId.value = null
}

async function approveDocument(docId) {
  try {
    const result = await adminApi.approveDocument(docId)
    if (result.company_verified) {
      alert(t('adminCompanies.documents.approved'))
      await adminStore.fetchCompanies()
    }
    // Reload documents
    await openDocumentsModal(selectedCompany.value)
  } catch (e) {
    console.error('Error approving document:', e)
    alert(t('adminCompanies.documents.errorApproving'))
  }
}

function startRejectDocument(docId) {
  rejectingDocumentId.value = docId
  rejectionReason.value = ''
}

function cancelRejectDocument() {
  rejectingDocumentId.value = null
  rejectionReason.value = ''
}

async function rejectDocument() {
  if (!rejectionReason.value.trim()) {
    alert(t('adminCompanies.documents.provideReason'))
    return
  }

  try {
    await adminApi.rejectDocument(rejectingDocumentId.value, rejectionReason.value)
    rejectingDocumentId.value = null
    rejectionReason.value = ''
    // Reload documents
    await openDocumentsModal(selectedCompany.value)
  } catch (e) {
    console.error('Error rejecting document:', e)
    alert(t('adminCompanies.documents.errorRejecting'))
  }
}

function downloadDocument(docId) {
  window.open(adminApi.getDocumentDownloadUrl(docId), '_blank')
}

function getDocumentStatusClass(status) {
  return {
    'status-pending': status === 'pending',
    'status-approved': status === 'approved',
    'status-rejected': status === 'rejected'
  }
}

function getDocumentStatusLabel(status) {
  const labels = {
    pending: t('adminCompanies.documents.statusPending'),
    approved: t('adminCompanies.documents.statusApproved'),
    rejected: t('adminCompanies.documents.statusRejected')
  }
  return labels[status] || status
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}
</script>

<template>
  <div class="companies-page">
    <div class="page-header">
      <div>
        <h1>{{ t('adminCompanies.title') }}</h1>
        <p class="subtitle">{{ t('adminCompanies.subtitle') }}</p>
      </div>
      <button @click="openCreateModal" class="btn btn-primary">
        <Plus :size="18" :stroke-width="iconStrokeWidth" />
        <span>{{ t('adminCompanies.addCompany') }}</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="search-box">
        <Search :size="18" :stroke-width="iconStrokeWidth" class="search-icon" />
        <input
          v-model="search"
          type="text"
          :placeholder="t('adminCompanies.searchPlaceholder')"
        />
      </div>
      <div class="filter-group">
        <select v-model="filterType">
          <option value="">{{ t('adminCompanies.filters.allTypes') }}</option>
          <option value="shipper">{{ t('adminCompanies.filters.shippers') }}</option>
          <option value="carrier">{{ t('adminCompanies.filters.carriers') }}</option>
        </select>
      </div>
      <div class="filter-group">
        <select v-model="filterVerified">
          <option value="">{{ t('adminCompanies.filters.all') }}</option>
          <option value="1">{{ t('adminCompanies.filters.verified') }}</option>
          <option value="0">{{ t('adminCompanies.filters.notVerified') }}</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <span>{{ t('common.loading') }}</span>
    </div>

    <!-- Table -->
    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ t('adminCompanies.table.company') }}</th>
            <th>{{ t('adminCompanies.table.inn') }}</th>
            <th>{{ t('adminCompanies.table.type') }}</th>
            <th>{{ t('adminCompanies.table.contacts') }}</th>
            <th>{{ t('adminCompanies.table.rating') }}</th>
            <th>{{ t('adminCompanies.table.verification') }}</th>
            <th>{{ t('adminCompanies.table.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="company in companies" :key="company.id">
            <td>
              <div class="company-cell">
                <div class="company-avatar" :class="company.type">
                  <Building2 :size="18" :stroke-width="iconStrokeWidth" />
                </div>
                <div>
                  <span class="company-name">{{ company.name }}</span>
                  <span class="company-users">{{ company.users_count || 0 }} {{ t('adminCompanies.users') }}</span>
                </div>
              </div>
            </td>
            <td>
              <span class="inn">{{ company.inn || '-' }}</span>
            </td>
            <td>
              <span class="type-badge" :class="company.type">
                {{ company.type === 'carrier' ? t('adminCompanies.typeCarrier') : t('adminCompanies.typeShipper') }}
              </span>
            </td>
            <td>
              <div class="contacts-cell">
                <span v-if="company.phone">{{ company.phone }}</span>
                <span v-if="company.email">{{ company.email }}</span>
                <span v-if="!company.phone && !company.email" class="no-data">-</span>
              </div>
            </td>
            <td>
              <div class="rating-cell" v-if="company.rating">
                <span class="rating-value">{{ Number(company.rating).toFixed(1) }}</span>
                <span class="rating-stars">★</span>
              </div>
              <span v-else class="no-data">-</span>
            </td>
            <td>
              <div class="verification-cell">
                <span v-if="company.verified" class="verified">
                  <CheckCircle :size="16" :stroke-width="iconStrokeWidth" />
                  {{ t('adminCompanies.verified') }}
                </span>
                <button
                  v-else
                  @click="verifyCompany(company)"
                  class="verify-btn"
                >
                  <Shield :size="16" :stroke-width="iconStrokeWidth" />
                  {{ t('adminCompanies.verify') }}
                </button>
              </div>
            </td>
            <td>
              <div class="actions">
                <button
                  v-if="company.type === 'carrier'"
                  @click="openDocumentsModal(company)"
                  class="action-btn"
                  :title="t('adminCompanies.documents.title')"
                >
                  <FileText :size="16" :stroke-width="iconStrokeWidth" />
                </button>
                <button @click="openEditModal(company)" class="action-btn" :title="t('common.edit')">
                  <Edit :size="16" :stroke-width="iconStrokeWidth" />
                </button>
                <button @click="deleteCompany(company)" class="action-btn danger" :title="t('common.delete')">
                  <Trash2 :size="16" :stroke-width="iconStrokeWidth" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="companies.length === 0">
            <td colspan="7" class="empty-row">
              {{ t('adminCompanies.noCompanies') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.lastPage > 1" class="pagination">
      <button
        @click="changePage(pagination.currentPage - 1)"
        :disabled="pagination.currentPage === 1"
        class="btn btn-sm btn-outline"
      >
        {{ t('common.back') }}
      </button>
      <span class="page-info">
        {{ t('adminCompanies.pageInfo', { current: pagination.currentPage, last: pagination.lastPage }) }}
      </span>
      <button
        @click="changePage(pagination.currentPage + 1)"
        :disabled="pagination.currentPage === pagination.lastPage"
        class="btn btn-sm btn-outline"
      >
        {{ t('common.next') }}
      </button>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingCompany ? t('adminCompanies.editCompany') : t('adminCompanies.addCompany') }}</h2>
          <button @click="closeModal" class="close-btn">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>{{ t('adminCompanies.form.name') }} *</label>
              <input v-model="form.name" type="text" required />
            </div>
            <div class="form-group">
              <label>{{ t('adminCompanies.form.inn') }}</label>
              <input v-model="form.inn" type="text" />
            </div>
          </div>

          <div class="form-group">
            <label>{{ t('adminCompanies.form.type') }} *</label>
            <select v-model="form.type" required>
              <option value="shipper">{{ t('adminCompanies.typeShipper') }}</option>
              <option value="carrier">{{ t('adminCompanies.typeCarrier') }}</option>
            </select>
          </div>

          <div class="form-group">
            <label>{{ t('adminCompanies.form.legalAddress') }}</label>
            <input v-model="form.legal_address" type="text" />
          </div>

          <div class="form-group">
            <label>{{ t('adminCompanies.form.actualAddress') }}</label>
            <input v-model="form.actual_address" type="text" />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>{{ t('adminCompanies.form.phone') }}</label>
              <input v-model="form.phone" type="text" />
            </div>
            <div class="form-group">
              <label>{{ t('adminCompanies.form.email') }}</label>
              <input v-model="form.email" type="email" />
            </div>
          </div>

          <div class="form-group">
            <label>{{ t('adminCompanies.form.website') }}</label>
            <input v-model="form.website" type="url" placeholder="https://..." />
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeModal" class="btn btn-outline">{{ t('common.cancel') }}</button>
          <button
            @click="saveCompany"
            class="btn btn-primary"
            :disabled="saving || !form.name"
          >
            <span v-if="saving">{{ t('adminCompanies.saving') }}</span>
            <span v-else>{{ editingCompany ? t('common.save') : t('adminCompanies.create') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Documents Modal -->
    <div v-if="showDocumentsModal" class="modal-overlay" @click.self="closeDocumentsModal">
      <div class="modal modal-wide">
        <div class="modal-header">
          <h2>{{ t('adminCompanies.documents.title') }}: {{ selectedCompany?.name }}</h2>
          <button @click="closeDocumentsModal" class="close-btn">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div v-if="loadingDocuments" class="loading">
            <div class="spinner"></div>
            <span>{{ t('adminCompanies.documents.loading') }}</span>
          </div>

          <div v-else-if="companyDocuments.length === 0" class="empty-state">
            <FileText :size="48" :stroke-width="iconStrokeWidth" />
            <p>{{ t('adminCompanies.documents.noDocuments') }}</p>
          </div>

          <div v-else class="documents-list">
            <div
              v-for="doc in companyDocuments"
              :key="doc.id"
              class="document-item"
              :class="{ 'is-rejecting': rejectingDocumentId === doc.id }"
            >
              <div class="document-icon">
                <FileText :size="24" :stroke-width="iconStrokeWidth" />
              </div>

              <div class="document-info">
                <div class="document-header">
                  <span class="document-type">{{ documentTypeLabels[doc.document_type] || doc.document_type }}</span>
                  <span class="document-status" :class="getDocumentStatusClass(doc.status)">
                    <Clock v-if="doc.status === 'pending'" :size="14" :stroke-width="iconStrokeWidth" />
                    <CheckCircle v-else-if="doc.status === 'approved'" :size="14" :stroke-width="iconStrokeWidth" />
                    <XCircle v-else :size="14" :stroke-width="iconStrokeWidth" />
                    {{ getDocumentStatusLabel(doc.status) }}
                  </span>
                </div>
                <div class="document-details">
                  <span>{{ doc.file_name }}</span>
                  <span>{{ t('adminCompanies.documents.uploaded') }}: {{ formatDate(doc.created_at) }}</span>
                </div>
                <div v-if="doc.rejection_reason" class="rejection-reason">
                  <AlertCircle :size="14" :stroke-width="iconStrokeWidth" />
                  {{ doc.rejection_reason }}
                </div>
              </div>

              <div class="document-actions" v-if="rejectingDocumentId !== doc.id">
                <button @click="downloadDocument(doc.id)" class="action-btn" :title="t('common.download')">
                  <Download :size="16" :stroke-width="iconStrokeWidth" />
                </button>
                <template v-if="doc.status === 'pending'">
                  <button @click="approveDocument(doc.id)" class="action-btn success" :title="t('adminCompanies.documents.approve')">
                    <CheckCircle :size="16" :stroke-width="iconStrokeWidth" />
                  </button>
                  <button @click="startRejectDocument(doc.id)" class="action-btn danger" :title="t('adminCompanies.documents.reject')">
                    <XCircle :size="16" :stroke-width="iconStrokeWidth" />
                  </button>
                </template>
              </div>

              <!-- Rejection form -->
              <div v-if="rejectingDocumentId === doc.id" class="rejection-form">
                <input
                  v-model="rejectionReason"
                  type="text"
                  :placeholder="t('adminCompanies.documents.reasonPlaceholder')"
                  @keyup.enter="rejectDocument"
                />
                <button @click="rejectDocument" class="btn btn-sm btn-danger">{{ t('adminCompanies.documents.reject') }}</button>
                <button @click="cancelRejectDocument" class="btn btn-sm btn-outline">{{ t('common.cancel') }}</button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeDocumentsModal" class="btn btn-outline">{{ t('common.close') }}</button>
          <button
            v-if="selectedCompany && !selectedCompany.verified"
            @click="verifyCompany(selectedCompany); closeDocumentsModal()"
            class="btn btn-primary"
          >
            <Shield :size="18" :stroke-width="iconStrokeWidth" />
            {{ t('adminCompanies.verifyCompany') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.companies-page {
  h1 {
    font-size: $font-size-2xl;
    font-weight: 600;
    color: $text-primary;
    margin: 0;
  }

  .subtitle {
    font-size: $font-size-sm;
    color: $text-secondary;
    margin: $spacing-xs 0 0;
  }
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: $spacing-lg;
}

.filters-bar {
  display: flex;
  gap: $spacing-md;
  margin-bottom: $spacing-lg;
}

.search-box {
  flex: 1;
  max-width: 300px;
  position: relative;

  .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: $text-muted;
  }

  input {
    width: 100%;
    padding: 10px 12px 10px 40px;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    height: 40px;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

.filter-group select {
  padding: 10px 32px 10px 12px;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  height: 40px;
  min-width: 150px;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  cursor: pointer;

  &:focus {
    outline: none;
    border-color: $color-primary;
  }
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: $spacing-3xl;
  gap: $spacing-md;
  color: $text-secondary;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid $border-color;
  border-top-color: $color-primary;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.table-container {
  background: $bg-white;
  border-radius: $radius-xl;
  border: 1px solid $border-color;
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;

  th, td {
    padding: $spacing-md;
    text-align: left;
    border-bottom: 1px solid $border-color;
  }

  th {
    background: $bg-light;
    font-weight: 600;
    font-size: $font-size-xs;
    color: $text-secondary;
    text-transform: uppercase;
  }

  td {
    font-size: $font-size-sm;
  }

  tbody tr:hover {
    background: $bg-hover;
  }

  tbody tr:last-child td {
    border-bottom: none;
  }
}

.company-cell {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.company-avatar {
  width: 36px;
  height: 36px;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;

  &.carrier {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.shipper {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }
}

.company-name {
  display: block;
  font-weight: 500;
  color: $text-primary;
}

.company-users {
  display: block;
  font-size: $font-size-xs;
  color: $text-muted;
}

.inn {
  font-family: monospace;
  color: $text-secondary;
}

.type-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: $radius-md;
  font-size: $font-size-xs;
  font-weight: 500;

  &.carrier {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.shipper {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }
}

.contacts-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: $font-size-xs;
  color: $text-secondary;
}

.rating-cell {
  display: flex;
  align-items: center;
  gap: 4px;
}

.rating-value {
  font-weight: 600;
  color: $text-primary;
}

.rating-stars {
  color: $color-warning;
}

.no-data {
  color: $text-muted;
}

.verification-cell {
  .verified {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: $color-success;
    font-size: $font-size-sm;
  }
}

.verify-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  background: rgba($color-primary, 0.1);
  border: none;
  border-radius: $radius-md;
  color: $color-primary;
  font-size: $font-size-xs;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover {
    background: $color-primary;
    color: white;
  }
}

.actions {
  display: flex;
  gap: $spacing-xs;
}

.action-btn {
  width: 32px;
  height: 32px;
  background: $bg-light;
  border: none;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: $text-secondary;
  transition: all $transition-fast;

  &:hover {
    background: $color-primary;
    color: white;
  }

  &.danger:hover {
    background: $color-danger;
  }
}

.empty-row {
  text-align: center;
  color: $text-muted;
  padding: $spacing-2xl !important;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: $spacing-md;
  margin-top: $spacing-lg;
}

.page-info {
  font-size: $font-size-sm;
  color: $text-secondary;
}

// Modal styles (same as CarriersView)
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
}

.modal {
  background: $bg-white;
  border-radius: $radius-xl;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-lg;
  border-bottom: 1px solid $border-color;

  h2 {
    font-size: $font-size-lg;
    font-weight: 600;
    margin: 0;
  }
}

.close-btn {
  width: 32px;
  height: 32px;
  background: $bg-light;
  border: none;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: $text-secondary;

  &:hover {
    background: $border-color;
  }
}

.modal-body {
  padding: $spacing-lg;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: $spacing-sm;
  padding: $spacing-lg;
  border-top: 1px solid $border-color;
}

.form-group {
  margin-bottom: $spacing-md;

  label {
    display: block;
    font-size: $font-size-sm;
    font-weight: 500;
    color: $text-primary;
    margin-bottom: $spacing-xs;
  }

  input, select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid $border-color;
    border-radius: $radius-md;
    font-size: $font-size-sm;

    &:focus {
      outline: none;
      border-color: $color-primary;
    }
  }
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: $spacing-md;
}

// Buttons
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: 10px 20px;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-base;
  border: none;
  height: 40px;

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.btn-primary {
  background: $color-primary;
  color: $text-white;

  &:hover:not(:disabled) {
    background: $color-primary-dark;
  }
}

.btn-outline {
  background: $bg-white;
  color: $text-primary;
  border: 1px solid $border-color;

  &:hover:not(:disabled) {
    background: $bg-light;
  }
}

.btn-sm {
  padding: 6px 12px;
  height: 32px;
  font-size: $font-size-xs;
}

// Document modal styles
.modal-wide {
  max-width: 700px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  color: $text-muted;

  svg {
    margin-bottom: 1rem;
    opacity: 0.5;
  }

  p {
    margin: 0;
    font-size: $font-size-sm;
  }
}

.documents-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.document-item {
  display: flex;
  align-items: flex-start;
  gap: $spacing-md;
  padding: $spacing-md;
  background: $bg-light;
  border-radius: $radius-md;
  border: 1px solid $border-color;
  transition: all 0.2s;

  &:hover {
    border-color: $color-primary;
  }

  &.is-rejecting {
    flex-wrap: wrap;
  }

  .document-icon {
    color: $text-muted;
    flex-shrink: 0;
    padding-top: 2px;
  }

  .document-info {
    flex: 1;
    min-width: 0;

    .document-header {
      display: flex;
      align-items: center;
      gap: $spacing-sm;
      margin-bottom: $spacing-xs;

      .document-type {
        font-weight: 500;
        color: $text-primary;
      }

      .document-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: $font-size-xs;
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
    }

    .document-details {
      display: flex;
      flex-wrap: wrap;
      gap: $spacing-sm;
      font-size: $font-size-xs;
      color: $text-secondary;

      span {
        &:not(:last-child)::after {
          content: '•';
          margin-left: $spacing-sm;
        }
      }
    }

    .rejection-reason {
      display: flex;
      align-items: flex-start;
      gap: 6px;
      margin-top: $spacing-sm;
      padding: $spacing-sm;
      background: #fef2f2;
      border-radius: $radius-sm;
      font-size: $font-size-xs;
      color: #991b1b;

      svg {
        flex-shrink: 0;
        margin-top: 1px;
      }
    }
  }

  .document-actions {
    display: flex;
    gap: $spacing-xs;
    flex-shrink: 0;

    .action-btn {
      &.success {
        color: #059669;

        &:hover {
          background: #d1fae5;
        }
      }
    }
  }

  .rejection-form {
    width: 100%;
    display: flex;
    gap: $spacing-sm;
    margin-top: $spacing-sm;
    padding-top: $spacing-sm;
    border-top: 1px solid $border-color;

    input {
      flex: 1;
      padding: 6px 12px;
      border: 1px solid $border-color;
      border-radius: $radius-sm;
      font-size: $font-size-sm;

      &:focus {
        outline: none;
        border-color: $color-primary;
      }
    }
  }
}

.btn-danger {
  background: #dc2626;
  color: white;
  border: none;

  &:hover:not(:disabled) {
    background: #b91c1c;
  }
}

// Dark theme
[data-theme="dark"] {
  .companies h1 {
    color: #f5f5f5;
  }

  .subtitle {
    color: #999999;
  }

  .btn-primary {
    background: #f97316;
    border-color: #f97316;
    color: #ffffff;

    span {
      color: #ffffff !important;
    }

    &:hover {
      background: #ea580c;
      border-color: #ea580c;
    }
  }

  .search-input,
  .filter-select {
    background: #0f0f0f;
    border-color: #2a2a2a;
    color: #f5f5f5;

    &::placeholder {
      color: #666666;
    }
  }

  .search-box input {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;

    &::placeholder {
      color: #666666 !important;
    }
  }

  .search-icon {
    color: #666666 !important;
  }

  .filter-group select {
    background-color: #0f0f0f;
    border-color: #2a2a2a;
    color: #f5f5f5;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23a0a0a0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  }

  .table-container {
    background: #0f0f0f;
    border-color: #2a2a2a;
  }

  .data-table {
    th {
      background: #0f0f0f;
      color: #999999;
      border-bottom-color: #2a2a2a;
    }

    td {
      border-bottom-color: #2a2a2a;
    }

    tbody tr:hover {
      background: #1a1a1a;
    }
  }

  .company-name {
    color: #f5f5f5 !important;
  }

  .company-users {
    color: #666666 !important;
  }

  // Carrier avatar and badge - orange color in dark theme
  .company-avatar.carrier {
    background: rgba(249, 115, 22, 0.15) !important;
    color: #f97316 !important;
  }

  .type-badge.carrier {
    background: rgba(249, 115, 22, 0.15) !important;
    color: #f97316 !important;
  }

  .inn {
    color: #999999 !important;
  }

  .contacts-cell {
    color: #999999 !important;
  }

  .rating-value {
    color: #f5f5f5 !important;
  }

  .no-data {
    color: #666666 !important;
  }

  .data-table td {
    color: #cccccc;
  }

  .action-btn {
    background: #1a1a1a;
    color: #f97316;
    border: 1px solid #2a2a2a;

    &:hover {
      background: #f97316;
      color: white;
      border-color: #f97316;
    }

    &.danger:hover {
      background: #ef4444;
      border-color: #ef4444;
    }

    &.success:hover {
      background: #22c55e;
      border-color: #22c55e;
    }
  }

  .modal {
    background: #0f0f0f;
    border: 1px solid #2a2a2a;
  }

  .modal-header {
    border-bottom-color: #2a2a2a;

    h2 {
      color: #f5f5f5;
    }
  }

  .modal-footer {
    border-top-color: #2a2a2a;
  }

  .close-btn {
    background: #1a1a1a !important;
    color: #999999;

    &:hover {
      background: #2a2a2a !important;
      color: #f5f5f5;
    }
  }

  .form-group label {
    color: #f5f5f5 !important;
  }

  .form-group input,
  .form-group select {
    background: #1a1a1a;
    border-color: #2a2a2a;
    color: #f5f5f5;
  }

  .btn-outline {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;

    &:hover:not(:disabled) {
      background: #2a2a2a !important;
    }
  }

  .pagination {
    .page-info {
      color: #999999;
    }

    .btn-outline {
      background: #1a1a1a;
      border-color: #2a2a2a;
      color: #f5f5f5;

      &:hover:not(:disabled) {
        border-color: #f97316;
        color: #f97316;
      }
    }
  }

  .empty-row {
    color: #666666;
  }

  .loading {
    color: #999999;
  }

  .spinner {
    border-color: #2a2a2a;
    border-top-color: #f97316;
  }

  .documents-list .document-item {
    background: #1a1a1a;
    border-color: #2a2a2a;
  }

  .document-icon {
    color: #666666 !important;
  }

  .document-type {
    color: #f5f5f5;
  }

  .document-details {
    color: #999999;
  }

  .empty-state {
    color: #666666 !important;
  }

  .rejection-form input {
    background: #1a1a1a !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;
  }

  .verify-btn {
    background: rgba(249, 115, 22, 0.15) !important;
    color: #f97316 !important;

    &:hover {
      background: #f97316 !important;
      color: white !important;
    }
  }

  .verified {
    color: #22c55e !important;
  }
}
</style>

<style lang="scss">
/* Dark theme styles for CompaniesView - non-scoped for proper selector matching */
[data-theme="dark"] {
  .companies-page {
    h1 {
      color: #f5f5f5 !important;
    }

    .subtitle {
      color: #999999 !important;
    }

    .company-avatar.carrier {
      background: rgba(249, 115, 22, 0.15) !important;
      color: #f97316 !important;
    }

    .type-badge.carrier {
      background: rgba(249, 115, 22, 0.15) !important;
      color: #f97316 !important;
    }

    .btn-primary {
      background: #f97316 !important;
      color: #ffffff !important;

      span {
        color: #ffffff !important;
      }
    }
  }
}
</style>
