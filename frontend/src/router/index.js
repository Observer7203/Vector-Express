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
  } else {
    next()
  }
})

export default router
