<script setup>
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route  = useRoute()

const token = ref(localStorage.getItem('token'))
const user  = ref(JSON.parse(localStorage.getItem('user') || '{}'))

watch(route, () => {
  token.value      = localStorage.getItem('token')
  user.value       = JSON.parse(localStorage.getItem('user') || '{}')
  isLoggedIn.value = !!localStorage.getItem('token')
})

const isLoggedIn = ref(!!localStorage.getItem('token'))

function logout() {
  import('@/api/axios').then(({ default: api }) => {
    api.post('/logout').finally(() => {
      localStorage.clear()
      isLoggedIn.value = false
      router.push('/login')
    })
  })
}
</script>

<template>
  <div v-if="!isLoggedIn || route.path === '/login'">
    <RouterView />
  </div>

  <div v-else class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-brand">Kasir Digital</div>

      <nav class="sidebar-nav">
        <RouterLink to="/"      active-class="nav-active">Dashboard</RouterLink>
        <RouterLink to="/kasir" active-class="nav-active">Transaksi</RouterLink>
        <template v-if="user.role === 'admin'">
          <RouterLink to="/produk"       active-class="nav-active">Produk</RouterLink>
          <RouterLink to="/kelola-kasir" active-class="nav-active">Kelola Kasir</RouterLink>
        </template>
      </nav>

      <div class="sidebar-footer">
        <RouterLink to="/profil" style="text-decoration:none;">
          <p class="sidebar-user" style="cursor:pointer; padding:6px 8px; border-radius:8px; transition:background .15s;" @mouseenter="e=>e.currentTarget.style.background='#f8fafc'" @mouseleave="e=>e.currentTarget.style.background='transparent'">
            {{ user.name }}<span>{{ user.role }} · Lihat Profil</span>
          </p>
        </RouterLink>
        <button class="btn-logout" @click="logout">Logout</button>
      </div>
    </aside>

    <!-- Main -->
    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>

<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, -apple-system, sans-serif; color: #1e293b; }

/* ── Layout ── */
.layout {
  display: flex;
  min-height: 100vh;
  background: #f1f5f9;
}

/* ── Sidebar ── */
.sidebar {
  width: 230px;
  flex-shrink: 0;
  background: #ffffff;
  border-right: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  padding: 20px 16px;
}

.sidebar-brand {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  padding: 4px 12px 20px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 12px;
  letter-spacing: -.3px;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.sidebar-nav a {
  text-decoration: none;
  color: #64748b;
  font-size: 14px;
  padding: 9px 12px;
  border-radius: 8px;
  transition: background .15s, color .15s;
}

.sidebar-nav a:hover {
  background: #f8fafc;
  color: #0f172a;
}

.nav-active {
  background: #f1f5f9 !important;
  color: #0f172a !important;
  font-weight: 600;
}

.sidebar-footer {
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

.sidebar-user {
  font-size: 13px;
  color: #475569;
  margin: 0 0 10px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.sidebar-user span {
  font-size: 11px;
  color: #94a3b8;
  text-transform: capitalize;
}

.btn-logout {
  width: 100%;
  padding: 8px;
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  transition: background .15s;
}

.btn-logout:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fecaca;
}

/* ── Main Content ── */
.main-content {
  flex: 1;
  padding: 28px;
  overflow: auto;
}

/* ── Global Form ── */
input, select, textarea {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  width: 100%;
  margin-bottom: 8px;
  background: #fff;
  color: #1e293b;
  outline: none;
  transition: border-color .15s;
}

input:focus, select:focus, textarea:focus {
  border-color: #94a3b8;
}

button {
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
}

table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #e2e8f0; padding: 10px 12px; text-align: left; font-size: 14px; }
th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 13px; }
</style>
