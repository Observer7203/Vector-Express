import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue')
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/auth/RegisterView.vue'),
      meta: { guest: true }
    },
    {
      path: '/tracking',
      name: 'tracking',
      component: () => import('@/views/tracking/TrackingView.vue')
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/dashboard/DashboardView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/views/profile/ProfileView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/shipments',
      name: 'shipments',
      component: () => import('@/views/shipments/ShipmentsListView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/shipments/new',
      name: 'shipments-new',
      component: () => import('@/views/shipments/ShipmentCreateView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/shipments/:id',
      name: 'shipments-detail',
      component: () => import('@/views/shipments/ShipmentDetailView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/shipments/:id/quotes',
      name: 'shipments-quotes',
      component: () => import('@/views/shipments/ShipmentQuotesView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/orders',
      name: 'orders',
      component: () => import('@/views/orders/OrdersListView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/orders/:id',
      name: 'orders-detail',
      component: () => import('@/views/orders/OrderDetailView.vue'),
      meta: { requiresAuth: true }
    },
    // Carrier routes
    {
      path: '/carrier',
      name: 'carrier-dashboard',
      component: () => import('@/views/carrier/CarrierDashboardView.vue'),
      meta: { requiresAuth: true, requiresRole: 'carrier' }
    },
    {
      path: '/carrier/zones',
      name: 'carrier-zones',
      component: () => import('@/views/carrier/CarrierZonesView.vue'),
      meta: { requiresAuth: true, requiresRole: 'carrier' }
    },
    {
      path: '/carrier/terminals',
      name: 'carrier-terminals',
      component: () => import('@/views/carrier/CarrierTerminalsView.vue'),
      meta: { requiresAuth: true, requiresRole: 'carrier' }
    },
    {
      path: '/carrier/rates',
      name: 'carrier-rates',
      component: () => import('@/views/carrier/CarrierRatesView.vue'),
      meta: { requiresAuth: true, requiresRole: 'carrier' }
    },
    {
      path: '/carrier/surcharges',
      name: 'carrier-surcharges',
      component: () => import('@/views/carrier/CarrierSurchargesView.vue'),
      meta: { requiresAuth: true, requiresRole: 'carrier' }
    },
    {
      path: '/carrier/settings',
      name: 'carrier-settings',
      component: () => import('@/views/carrier/CarrierSettingsView.vue'),
      meta: { requiresAuth: true, requiresRole: 'carrier' }
    },
    {
      path: '/carrier/documents',
      name: 'carrier-documents',
      component: () => import('@/views/carrier/CarrierDocumentsView.vue'),
      meta: { requiresAuth: true, requiresRole: 'carrier' }
    },
    // Admin routes
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresRole: 'admin' },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/views/admin/DashboardView.vue')
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/UsersView.vue')
        },
        {
          path: 'companies',
          name: 'admin-companies',
          component: () => import('@/views/admin/CompaniesView.vue')
        },
        {
          path: 'carriers',
          name: 'admin-carriers',
          component: () => import('@/views/admin/CarriersView.vue')
        },
        {
          path: 'orders',
          name: 'admin-orders',
          component: () => import('@/views/admin/OrdersView.vue')
        },
        {
          path: 'invoices',
          name: 'admin-invoices',
          component: () => import('@/views/admin/InvoicesView.vue')
        },
        {
          path: 'settings',
          name: 'admin-settings',
          component: () => import('@/views/admin/SettingsView.vue')
        }
      ]
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Fetch user if we have token but no user
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
  } else if (to.meta.requiresRole && authStore.user?.role !== to.meta.requiresRole) {
    // Role check - redirect to dashboard if role doesn't match
    next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router
