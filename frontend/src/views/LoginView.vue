<template>
  <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f1f5f9;">
    <div style="width:360px;">
      <div style="background:white; padding:32px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.1); margin-bottom:16px;">
        <h1 style="text-align:center; margin:0 0 24px; font-size:22px;">Kasir Digital</h1>
        <form @submit.prevent="login">
          <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Email</label>
          <input v-model="form.email" type="email" placeholder="admin@kasir.com" required />

          <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Password</label>
          <input v-model="form.password" type="password" placeholder="••••••••" required />

          <p v-if="error" style="color:#dc2626; font-size:13px; margin:4px 0 8px;">{{ error }}</p>

          <button type="submit" :disabled="loading" style="width:100%; background:#2563eb; color:white; padding:10px; margin-top:8px;">
            {{ loading ? 'Masuk...' : 'Masuk' }}
          </button>
        </form>
      </div>

      <!-- Akun Demo -->
      <div style="background:white; border-radius:10px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
        <p style="margin:0 0 10px; font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px;">Akun Demo</p>
        <div style="display:flex; flex-direction:column; gap:8px;">
          <div
            v-for="akun in akunDemo" :key="akun.email"
            @click="isiAkun(akun)"
            style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; border-radius:8px; border:1px solid #e2e8f0; cursor:pointer; transition:background .15s;"
            @mouseenter="e=>e.currentTarget.style.background='#f8fafc'"
            @mouseleave="e=>e.currentTarget.style.background='white'"
          >
            <div>
              <p style="margin:0; font-size:13px; font-weight:600; color:#0f172a;">{{ akun.label }}</p>
              <p style="margin:0; font-size:12px; color:#64748b;">{{ akun.email }}</p>
            </div>
            <span :style="`font-size:11px; font-weight:700; padding:2px 8px; border-radius:999px;
              background:${akun.role === 'admin' ? '#fef3c7' : '#eff6ff'};
              color:${akun.role === 'admin' ? '#d97706' : '#2563eb'};`">
              {{ akun.role }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'

const router  = useRouter()
const loading = ref(false)
const error   = ref('')
const form    = ref({ email: '', password: '' })

const akunDemo = [
  { label: 'Administrator', email: 'admin@kasir.com',  password: 'password', role: 'admin' },
  { label: 'Kasir',         email: 'kasir@kasir.com',  password: 'password', role: 'kasir' },
]

function isiAkun(akun) {
  form.value.email    = akun.email
  form.value.password = akun.password
}

async function login() {
  loading.value = true
  error.value   = ''
  try {
    const res = await api.post('/login', form.value)
    localStorage.setItem('token', res.data.data.token)
    localStorage.setItem('user',  JSON.stringify(res.data.data.user))
    router.push('/')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login gagal'
  } finally {
    loading.value = false
  }
}
</script>
