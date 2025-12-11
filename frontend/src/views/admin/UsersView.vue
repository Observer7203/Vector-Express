<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  Plus,
  Search,
  User,
  Edit,
  Trash2,
  Ban,
  CheckCircle,
  Key,
  X
} from 'lucide-vue-next'

const adminStore = useAdminStore()
const iconStrokeWidth = 1.5

const search = ref('')
const filterRole = ref('')
const filterActive = ref('')
const showModal = ref(false)
const showPasswordModal = ref(false)
const editingUser = ref(null)
const saving = ref(false)
const newPassword = ref('')
const passwordUser = ref(null)

const form = ref({
  name: '',
  email: '',
  password: '',
  phone: '',
  role: 'customer',
  company_id: null,
  is_active: true
})

const roleLabels = {
  customer: 'Заказчик',
  carrier: 'Перевозчик',
  admin: 'Администратор'
}

onMounted(() => {
  adminStore.fetchUsers()
  adminStore.fetchCompanies({ per_page: 100 })
})

watch([search, filterRole, filterActive], () => {
  adminStore.fetchUsers({
    search: search.value,
    role: filterRole.value,
    is_active: filterActive.value
  })
})

const users = computed(() => adminStore.users)
const companies = computed(() => adminStore.companies)
const loading = computed(() => adminStore.loading)
const pagination = computed(() => adminStore.usersPagination)

function openCreateModal() {
  editingUser.value = null
  form.value = {
    name: '',
    email: '',
    password: '',
    phone: '',
    role: 'customer',
    company_id: null,
    is_active: true
  }
  showModal.value = true
}

function openEditModal(user) {
  editingUser.value = user
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    phone: user.phone || '',
    role: user.role,
    company_id: user.company_id,
    is_active: user.is_active
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingUser.value = null
}

async function saveUser() {
  saving.value = true
  try {
    const data = { ...form.value }
    if (!data.password) delete data.password
    if (!data.phone) delete data.phone

    if (editingUser.value) {
      await adminStore.updateUser(editingUser.value.id, data)
    } else {
      await adminStore.createUser(data)
    }
    await adminStore.fetchUsers()
    closeModal()
  } catch (e) {
    console.error('Error saving user:', e)
  } finally {
    saving.value = false
  }
}

async function toggleActive(user) {
  try {
    await adminStore.toggleUserActive(user.id)
  } catch (e) {
    console.error('Error toggling user:', e)
  }
}

async function resetPassword(user) {
  if (!confirm(`Сбросить пароль для ${user.name}?`)) return

  try {
    const result = await adminStore.resetUserPassword(user.id)
    passwordUser.value = user
    newPassword.value = result.new_password
    showPasswordModal.value = true
  } catch (e) {
    console.error('Error resetting password:', e)
  }
}

async function deleteUser(user) {
  if (!confirm(`Удалить пользователя "${user.name}"?`)) return

  try {
    await adminStore.deleteUser(user.id)
  } catch (e) {
    console.error('Error deleting user:', e)
  }
}

function changePage(page) {
  adminStore.fetchUsers({
    page,
    search: search.value,
    role: filterRole.value,
    is_active: filterActive.value
  })
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('ru-RU')
}
</script>

<template>
  <div class="users-page">
    <div class="page-header">
      <div>
        <h1>Пользователи</h1>
        <p class="subtitle">Управление пользователями системы</p>
      </div>
      <button @click="openCreateModal" class="btn btn-primary">
        <Plus :size="18" :stroke-width="iconStrokeWidth" />
        <span>Добавить пользователя</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="search-box">
        <Search :size="18" :stroke-width="iconStrokeWidth" class="search-icon" />
        <input
          v-model="search"
          type="text"
          placeholder="Поиск по имени, email..."
        />
      </div>
      <div class="filter-group">
        <select v-model="filterRole">
          <option value="">Все роли</option>
          <option value="customer">Заказчики</option>
          <option value="carrier">Перевозчики</option>
          <option value="admin">Администраторы</option>
        </select>
      </div>
      <div class="filter-group">
        <select v-model="filterActive">
          <option value="">Все</option>
          <option value="1">Активные</option>
          <option value="0">Заблокированные</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <!-- Table -->
    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Пользователь</th>
            <th>Роль</th>
            <th>Компания</th>
            <th>Статус</th>
            <th>Регистрация</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>
              <span class="user-id">#{{ user.id }}</span>
            </td>
            <td>
              <div class="user-cell">
                <div class="user-avatar" :class="user.role">
                  <User :size="18" :stroke-width="iconStrokeWidth" />
                </div>
                <div>
                  <span class="user-name">{{ user.name }}</span>
                  <span class="user-email">{{ user.email }}</span>
                </div>
              </div>
            </td>
            <td>
              <span class="role-badge" :class="user.role">
                {{ roleLabels[user.role] }}
              </span>
            </td>
            <td>
              <span class="company-name" v-if="user.company">{{ user.company.name }}</span>
              <span v-else class="no-data">-</span>
            </td>
            <td>
              <span class="status-badge" :class="user.is_active ? 'active' : 'blocked'">
                <CheckCircle v-if="user.is_active" :size="14" :stroke-width="iconStrokeWidth" />
                <Ban v-else :size="14" :stroke-width="iconStrokeWidth" />
                {{ user.is_active ? 'Активен' : 'Заблокирован' }}
              </span>
            </td>
            <td>
              <span class="date">{{ formatDate(user.created_at) }}</span>
            </td>
            <td>
              <div class="actions">
                <button @click="openEditModal(user)" class="action-btn" title="Редактировать">
                  <Edit :size="16" :stroke-width="iconStrokeWidth" />
                </button>
                <button @click="toggleActive(user)" class="action-btn" :class="user.is_active ? 'warning' : 'success'" :title="user.is_active ? 'Заблокировать' : 'Активировать'">
                  <Ban v-if="user.is_active" :size="16" :stroke-width="iconStrokeWidth" />
                  <CheckCircle v-else :size="16" :stroke-width="iconStrokeWidth" />
                </button>
                <button @click="resetPassword(user)" class="action-btn" title="Сбросить пароль">
                  <Key :size="16" :stroke-width="iconStrokeWidth" />
                </button>
                <button @click="deleteUser(user)" class="action-btn danger" title="Удалить">
                  <Trash2 :size="16" :stroke-width="iconStrokeWidth" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="users.length === 0">
            <td colspan="7" class="empty-row">
              Пользователи не найдены
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
        Назад
      </button>
      <span class="page-info">
        Страница {{ pagination.currentPage }} из {{ pagination.lastPage }}
      </span>
      <button
        @click="changePage(pagination.currentPage + 1)"
        :disabled="pagination.currentPage === pagination.lastPage"
        class="btn btn-sm btn-outline"
      >
        Далее
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingUser ? 'Редактировать пользователя' : 'Добавить пользователя' }}</h2>
          <button @click="closeModal" class="close-btn">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Имя *</label>
              <input v-model="form.name" type="text" required />
            </div>
            <div class="form-group">
              <label>Email *</label>
              <input v-model="form.email" type="email" required />
            </div>
          </div>

          <div class="form-group" v-if="!editingUser">
            <label>Пароль *</label>
            <input v-model="form.password" type="password" required />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Телефон</label>
              <input v-model="form.phone" type="tel" />
            </div>
            <div class="form-group">
              <label>Роль *</label>
              <select v-model="form.role" required>
                <option value="customer">Заказчик</option>
                <option value="carrier">Перевозчик</option>
                <option value="admin">Администратор</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Компания</label>
            <select v-model="form.company_id">
              <option :value="null">Без компании</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="form.is_active" type="checkbox" />
              <span>Активен</span>
            </label>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeModal" class="btn btn-outline">Отмена</button>
          <button
            @click="saveUser"
            class="btn btn-primary"
            :disabled="saving || !form.name || !form.email || (!editingUser && !form.password)"
          >
            <span v-if="saving">Сохранение...</span>
            <span v-else>{{ editingUser ? 'Сохранить' : 'Создать' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Password Modal -->
    <div v-if="showPasswordModal" class="modal-overlay" @click.self="showPasswordModal = false">
      <div class="modal modal-sm">
        <div class="modal-header">
          <h2>Новый пароль</h2>
          <button @click="showPasswordModal = false" class="close-btn">
            <X :size="20" :stroke-width="iconStrokeWidth" />
          </button>
        </div>

        <div class="modal-body">
          <p class="password-info">
            Пароль для пользователя <strong>{{ passwordUser?.name }}</strong> был сброшен.
          </p>
          <div class="password-display">
            <span class="password-label">Новый пароль:</span>
            <code class="password-value">{{ newPassword }}</code>
          </div>
          <p class="password-warning">
            Сохраните этот пароль и передайте его пользователю. После закрытия окна он не будет показан снова.
          </p>
        </div>

        <div class="modal-footer">
          <button @click="showPasswordModal = false" class="btn btn-primary">
            Закрыть
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '../../styles/_variables.scss';

.users-page {
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
  padding: 10px 12px;
  border: 1px solid $border-color;
  border-radius: $radius-md;
  font-size: $font-size-sm;
  height: 40px;
  min-width: 150px;

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

.user-id {
  font-family: monospace;
  color: $text-muted;
  font-size: $font-size-xs;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  &.customer {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.carrier {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.admin {
    background: rgba(#7c3aed, 0.1);
    color: #7c3aed;
  }
}

.user-name {
  display: block;
  font-weight: 500;
  color: $text-primary;
}

.user-email {
  display: block;
  font-size: $font-size-xs;
  color: $text-muted;
}

.role-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: $radius-md;
  font-size: $font-size-xs;
  font-weight: 500;

  &.customer {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.carrier {
    background: rgba($color-primary, 0.1);
    color: $color-primary;
  }

  &.admin {
    background: rgba(#7c3aed, 0.1);
    color: #7c3aed;
  }
}

.company-name {
  color: $text-secondary;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: $radius-md;
  font-size: $font-size-xs;
  font-weight: 500;

  &.active {
    background: rgba($color-success, 0.1);
    color: $color-success;
  }

  &.blocked {
    background: rgba($color-danger, 0.1);
    color: $color-danger;
  }
}

.date {
  color: $text-secondary;
  font-size: $font-size-xs;
}

.no-data {
  color: $text-muted;
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

  &.warning:hover {
    background: $color-warning;
  }

  &.success:hover {
    background: $color-success;
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

// Modal styles
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

  &.modal-sm {
    max-width: 400px;
  }
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

.checkbox-group {
  .checkbox-label {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    cursor: pointer;

    input[type="checkbox"] {
      width: auto;
    }

    span {
      font-size: $font-size-sm;
      color: $text-primary;
    }
  }
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: $spacing-md;
}

// Password modal
.password-info {
  font-size: $font-size-sm;
  color: $text-secondary;
  margin-bottom: $spacing-md;

  strong {
    color: $text-primary;
  }
}

.password-display {
  background: $bg-light;
  padding: $spacing-md;
  border-radius: $radius-md;
  margin-bottom: $spacing-md;
}

.password-label {
  display: block;
  font-size: $font-size-xs;
  color: $text-secondary;
  margin-bottom: $spacing-xs;
}

.password-value {
  font-family: monospace;
  font-size: $font-size-lg;
  font-weight: 600;
  color: $text-primary;
  user-select: all;
}

.password-warning {
  font-size: $font-size-xs;
  color: $color-warning;
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
</style>
