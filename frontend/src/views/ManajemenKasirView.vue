<template>
  <div>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <h2 style="margin:0;">Manajemen Kasir</h2>
      <button @click="showForm = true" style="background:#2563eb; color:white;">+ Tambah Kasir</button>
    </div>

    <!-- Modal Form -->
    <div v-if="showForm" style="position:fixed; inset:0; background:rgba(0,0,0,.4); display:flex; align-items:center; justify-content:center; z-index:100;">
      <div style="background:white; padding:28px; border-radius:12px; width:400px;">
        <h3 style="margin-top:0;">Tambah Kasir</h3>
        <form @submit.prevent="simpan">
          <label style="font-size:13px; font-weight:600;">Nama</label>
          <input v-model="form.name" placeholder="Nama kasir" required />

          <label style="font-size:13px; font-weight:600;">Email</label>
          <input v-model="form.email" type="email" placeholder="email@kasir.com" required />

          <label style="font-size:13px; font-weight:600;">Password</label>
          <input v-model="form.password" type="password" placeholder="Minimal 6 karakter" required />

          <p v-if="error" style="color:#dc2626; font-size:13px; margin:4px 0;">{{ error }}</p>

          <div style="display:flex; gap:8px; margin-top:8px;">
            <button type="submit" :disabled="loading" style="flex:1; background:#2563eb; color:white;">
              {{ loading ? 'Menyimpan...' : 'Simpan' }}
            </button>
            <button type="button" @click="resetForm" style="flex:1; background:#f1f5f9;">Batal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel -->
    <div style="background:white; border-radius:10px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08);">
      <table style="width:100%;">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Bergabung</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in kasir" :key="u.id">
            <td>{{ u.name }}</td>
            <td>{{ u.email }}</td>
            <td>{{ new Date(u.created_at).toLocaleDateString('id-ID') }}</td>
            <td>
              <button @click="hapus(u.id)" style="background:#ef4444; color:white;">Hapus</button>
            </td>
          </tr>
          <tr v-if="kasir.length === 0">
            <td colspan="4" style="text-align:center; color:#94a3b8; padding:24px;">Belum ada kasir</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api/axios'

const kasir    = ref([])
const showForm = ref(false)
const loading  = ref(false)
const error    = ref('')
const form     = ref({ name: '', email: '', password: '' })

onMounted(fetchKasir)

async function fetchKasir() {
  const res = await api.get('/kasir')
  kasir.value = res.data.data
}

function resetForm() {
  showForm.value = false
  error.value    = ''
  form.value     = { name: '', email: '', password: '' }
}

async function simpan() {
  loading.value = true
  error.value   = ''
  try {
    await api.post('/kasir', form.value)
    await fetchKasir()
    resetForm()
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal menambahkan kasir'
  } finally {
    loading.value = false
  }
}

async function hapus(id) {
  if (!confirm('Hapus kasir ini?')) return
  await api.delete(`/kasir/${id}`)
  fetchKasir()
}
</script>
