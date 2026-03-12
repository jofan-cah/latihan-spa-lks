import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/login', component: () => import('@/views/LoginView.vue') },
  {
    path: '/',
    component: () => import('@/views/DashboardView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/produk',
    component: () => import('@/views/ProdukView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/kasir',
    component: () => import('@/views/KasirView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/kelola-kasir',
    component: () => import('@/views/ManajemenKasirView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  const user  = JSON.parse(localStorage.getItem('user') || '{}')

  if (to.meta.requiresAuth && !token) return '/login'
  if (to.meta.role && user.role !== to.meta.role) return '/'
})

export default router
