import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', redirect: '/home' },

  // Auth (guest only)
  { path: '/login',           component: () => import('@/pages/Login.vue'),          meta: { guest: true } },
  { path: '/register',        component: () => import('@/pages/Register.vue'),       meta: { guest: true } },
  { path: '/forgot-password', component: () => import('@/pages/ForgotPassword.vue'), meta: { guest: true } },

  // Public
  { path: '/public', component: () => import('@/pages/PublicMap.vue') },

  // User routes
  { path: '/home',          component: () => import('@/pages/UserHome.vue'),     meta: { requiresAuth: true } },
  { path: '/map',            component: () => import('@/pages/MapView.vue'),     meta: { requiresAuth: true } },
  { path: '/my-incidents',   component: () => import('@/pages/MyIncidents.vue'), meta: { requiresAuth: true } },
  { path: '/profile',        component: () => import('@/pages/Profile.vue'),     meta: { requiresAuth: true } },

  // Admin routes
  { path: '/dashboard',             component: () => import('@/pages/Dashboard.vue'),             meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/incidents',       component: () => import('@/pages/AdminIncidents.vue'),        meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/users',           component: () => import('@/pages/AdminUsers.vue'),            meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/categories',      component: () => import('@/pages/AdminCategories.vue'),      meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/notifications',   component: () => import('@/pages/AdminNotifications.vue'),   meta: { requiresAuth: true, requiresAdmin: true } },

  // Fallback
  { path: '/:pathMatch(.*)*', redirect: '/map' }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token   = localStorage.getItem('sos_token')
  const user    = JSON.parse(localStorage.getItem('sos_user') || '{}')
  const isAdmin = user?.vai_tro === 'admin' || user?.role === 'admin'

  if (to.meta.requiresAuth && !token)       return next('/login')
  if (to.meta.guest && token)               return next(isAdmin ? '/dashboard' : '/home')
  if (to.meta.requiresAdmin && !isAdmin)    return next('/home')
  next()
})

export default router
