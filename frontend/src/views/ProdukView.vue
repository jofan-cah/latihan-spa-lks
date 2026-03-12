<template>
  <div>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <h2 style="margin:0;">Manajemen Produk</h2>
      <button @click="openForm()" style="background:#2563eb; color:white;">+ Tambah Produk</button>
    </div>

    <!-- Modal Form -->
    <div v-if="showForm" style="position:fixed; inset:0; background:rgba(0,0,0,.4); display:flex; align-items:center; justify-content:center; z-index:100;">
      <div style="background:white; padding:28px; border-radius:12px; width:460px; max-height:90vh; overflow:auto;">
        <h3 style="margin-top:0;">{{ editId ? 'Edit Produk' : 'Tambah Produk' }}</h3>
        <form @submit.prevent="simpan">
          <label style="font-size:13px; font-weight:600;">Nama Produk</label>
          <input v-model="form.nama" placeholder="Nama produk" required />

          <label style="font-size:13px; font-weight:600;">Kategori</label>
          <select v-model="form.kategori">
            <option value="">Pilih kategori</option>
            <option value="Makanan">Makanan</option>
            <option value="Minuman">Minuman</option>
            <option value="Snack">Snack</option>
            <option value="Lainnya">Lainnya</option>
          </select>

          <label style="font-size:13px; font-weight:600;">Harga</label>
          <input v-model.number="form.harga" type="number" min="0" placeholder="0" required />

          <label style="font-size:13px; font-weight:600;">Stok</label>
          <input v-model.number="form.stok" type="number" min="0" placeholder="0" required />

          <label style="font-size:13px; font-weight:600;">Foto</label>
          <input type="file" accept="image/*" @change="handleFoto" style="padding:4px;" />
          <img v-if="preview" :src="preview" style="width:100%;max-height:160px;object-fit:cover;border-radius:6px;margin-bottom:8px;" />

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
            <th>Foto</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in produk" :key="p.id">
            <td>
              <img v-if="p.foto_url" :src="p.foto_url" style="width:50px;height:50px;object-fit:cover;border-radius:4px;" />
              <span v-else style="color:#94a3b8;">-</span>
            </td>
            <td>{{ p.nama }}</td>
            <td>{{ p.kategori }}</td>
            <td>{{ formatRupiah(p.harga) }}</td>
            <td>
              <span :style="p.stok < 10 ? 'color:#dc2626;font-weight:600;' : ''">{{ p.stok }}</span>
            </td>
            <td>
              <button @click="openForm(p)" style="background:#f59e0b; color:white; margin-right:6px;">Edit</button>
              <button @click="hapus(p.id)" style="background:#ef4444; color:white;">Hapus</button>
            </td>
          </tr>
          <tr v-if="produk.length === 0">
            <td colspan="6" style="text-align:center; color:#94a3b8; padding:24px;">Belum ada produk</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api/axios'

const produk   = ref([])
const showForm = ref(false)
const editId   = ref(null)
const fotoFile = ref(null)
const preview  = ref('')
const error    = ref('')
const loading  = ref(false)
const form     = ref({ nama: '', kategori: '', harga: 0, stok: 0 })

onMounted(fetchProduk)

async function fetchProduk() {
  const res = await api.get('/produk')
  produk.value = res.data.data
}

function openForm(p = null) {
  if (p) {
    editId.value = p.id
    form.value   = { nama: p.nama, kategori: p.kategori || '', harga: p.harga, stok: p.stok }
    preview.value = p.foto_url || ''
  } else {
    resetForm()
  }
  showForm.value = true
}

function resetForm() {
  editId.value   = null
  form.value     = { nama: '', kategori: '', harga: 0, stok: 0 }
  fotoFile.value = null
  preview.value  = ''
  error.value    = ''
  showForm.value = false
}

function handleFoto(e) {
  fotoFile.value = e.target.files[0]
  if (fotoFile.value) preview.value = URL.createObjectURL(fotoFile.value)
}

async function simpan() {
  loading.value = true
  error.value   = ''
  const fd = new FormData()
  fd.append('nama',     form.value.nama)
  fd.append('kategori', form.value.kategori)
  fd.append('harga',    form.value.harga)
  fd.append('stok',     form.value.stok)
  if (fotoFile.value) fd.append('foto', fotoFile.value)

  try {
    const url = editId.value ? `/produk/${editId.value}` : '/produk'
    await api.post(url, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    await fetchProduk()
    resetForm()
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal menyimpan'
  } finally {
    loading.value = false
  }
}

async function hapus(id) {
  if (!confirm('Hapus produk ini?')) return
  await api.delete(`/produk/${id}`)
  fetchProduk()
}

function formatRupiah(n) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}
</script>
