<template>
  <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f1f5f9;">
    <div style="background:white; padding:32px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.1); width:360px;">
      <h1 style="text-align:center; margin:0 0 24px; font-size:22px;">  Kasir Digital</h1>
      <form @submit.prevent="login">
        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Email</label>
        <input v-model="form.email" type="email" placeholder="admin@kasir.com" required />

        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Password</label>
        <input v-model="form.password" type="password" placeholder="password" required />

        <p v-if="error" style="color:#dc2626; font-size:13px; margin:4px 0 8px;">{{ error }}</p>

        <button type="submit" :disabled="loading" style="width:100%; background:#2563eb; color:white; padding:10px; margin-top:8px;">
          {{ loading ? 'Masuk...' : 'Masuk' }}
        </button>
      </form>
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
