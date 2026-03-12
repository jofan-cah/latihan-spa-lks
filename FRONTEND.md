# 📗 Panduan Frontend — Vue.js SPA

> Studi Kasus: **Kasir Digital UMKM**
> Stack: **Vue 3 + Vite + Axios + Vue Router**

---

## 🗂️ Struktur Folder

```
frontend/src/
├── api/
│   └── axios.js              ← Konfigurasi Axios + interceptor token
├── views/
│   ├── LoginView.vue         ← Halaman login
│   ├── DashboardView.vue     ← Statistik & riwayat transaksi
│   ├── KasirView.vue         ← Layar POS (transaksi)
│   ├── ProdukView.vue        ← CRUD produk + foto (admin)
│   └── ManajemenKasirView.vue← Kelola user kasir (admin)
├── router/
│   └── index.js              ← Definisi route + guard auth
├── App.vue                   ← Layout utama + sidebar
└── main.js                   ← Entry point
```

---

## ⚙️ Setup Awal

### 1. Buat Project
```bash
npm create vue@latest frontend
cd frontend
npm install
npm install axios vue-router
```

### 2. Konfigurasi `.env`
```env
# Sesuaikan dengan URL backend kamu
VITE_API_URL=http://backendd.test/api
```
> ⚠️ Setiap ubah `.env`, wajib **restart `npm run dev`** agar Vite baca ulang.

### 3. Daftarkan Router di `main.js`
```js
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

createApp(App).use(router).mount('#app')
```

---

## 🌐 Axios — Konfigurasi API (`src/api/axios.js`)

```js
import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL  // baca dari .env
})

// Interceptor REQUEST — otomatis sisipkan token di setiap request
api.interceptors.request.use(config => {
    const token = localStorage.getItem('token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})

// Interceptor RESPONSE — kalau 401, redirect ke login
api.interceptors.response.use(
    res => res,
    err => {
        if (err.response?.status === 401 && window.location.pathname !== '/login') {
            localStorage.clear()
            window.location.href = '/login'
        }
        return Promise.reject(err)
    }
)

export default api
```

### Kenapa Pakai Interceptor?
- **Request interceptor** → tidak perlu manually tulis `Authorization` header di setiap komponen
- **Response interceptor** → kalau token expired/invalid (401), otomatis logout dan redirect ke login

---

## 🛣️ Router (`src/router/index.js`)

```js
import { createRouter, createWebHistory } from 'vue-router'

const routes = [
    { path: '/login',        component: () => import('@/views/LoginView.vue') },
    { path: '/',             component: () => import('@/views/DashboardView.vue'),      meta: { requiresAuth: true } },
    { path: '/kasir',        component: () => import('@/views/KasirView.vue'),          meta: { requiresAuth: true } },
    { path: '/produk',       component: () => import('@/views/ProdukView.vue'),         meta: { requiresAuth: true, role: 'admin' } },
    { path: '/kelola-kasir', component: () => import('@/views/ManajemenKasirView.vue'), meta: { requiresAuth: true, role: 'admin' } },
]

const router = createRouter({ history: createWebHistory(), routes })

// Navigation Guard — cek auth sebelum masuk halaman
router.beforeEach((to) => {
    const token = localStorage.getItem('token')
    const user  = JSON.parse(localStorage.getItem('user') || '{}')

    if (to.meta.requiresAuth && !token) return '/login'   // belum login → ke login
    if (to.meta.role && user.role !== to.meta.role) return '/'  // role tidak sesuai → ke dashboard
})

export default router
```

### Konsep Penting

| Konsep | Penjelasan |
|--------|------------|
| `meta.requiresAuth` | Route ini butuh login |
| `meta.role` | Route ini hanya untuk role tertentu |
| `beforeEach` | Dijalankan setiap kali pindah halaman |
| Lazy loading `() => import(...)` | Komponen dimuat hanya saat dibutuhkan |

---

## 🏗️ App.vue — Layout + Sidebar

### Masalah: `localStorage` Tidak Reaktif

`localStorage` bukan reactive state di Vue. Jadi `computed(() => localStorage.getItem('token'))` tidak akan update otomatis setelah login.

**Solusi:** pakai `ref` + `watch` pada perubahan route:

```js
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const route      = useRoute()
const isLoggedIn = ref(!!localStorage.getItem('token'))
const user       = ref(JSON.parse(localStorage.getItem('user') || '{}'))

// Re-check setiap kali route berubah (setelah login/logout)
watch(route, () => {
    isLoggedIn.value = !!localStorage.getItem('token')
    user.value       = JSON.parse(localStorage.getItem('user') || '{}')
})
```

### Template Bersyarat
```html
<!-- Tampilan tanpa sidebar: halaman login -->
<div v-if="!isLoggedIn || route.path === '/login'">
    <RouterView />
</div>

<!-- Tampilan dengan sidebar: setelah login -->
<div v-else style="display:flex;">
    <aside><!-- Sidebar --></aside>
    <main><RouterView /></main>
</div>
```

---

## 🔑 LoginView.vue

```js
async function login() {
    try {
        const res = await api.post('/login', { email, password })

        // Simpan token dan data user ke localStorage
        localStorage.setItem('token', res.data.data.token)
        localStorage.setItem('user',  JSON.stringify(res.data.data.user))

        router.push('/')  // Redirect ke dashboard
    } catch (err) {
        error.value = err.response?.data?.message || 'Login gagal'
    }
}
```

---

## 🛒 KasirView.vue — Layar POS

### Alur Kerja
```
Klik produk → tambahKeranjang()
           → qty++ kalau sudah ada
           → push item baru kalau belum ada

Klik Checkout → POST /api/transaksi
             → { bayar, items: [{produk_id, qty}] }
             → Refresh stok produk
```

### Computed Total Otomatis
```js
// Dihitung ulang setiap keranjang berubah
const total = computed(() =>
    keranjang.value.reduce((sum, item) => sum + item.harga * item.qty, 0)
)
```

### Fungsi Tambah ke Keranjang
```js
function tambahKeranjang(produk) {
    const ada = keranjang.value.find(k => k.produk_id === produk.id)
    if (ada) {
        if (ada.qty < produk.stok) ada.qty++  // tambah qty kalau stok cukup
    } else {
        keranjang.value.push({
            produk_id: produk.id,
            nama:      produk.nama,
            harga:     produk.harga,
            stok:      produk.stok,
            qty:       1
        })
    }
}
```

### Kirim Transaksi ke API
```js
await api.post('/transaksi', {
    bayar: bayar.value,
    items: keranjang.value.map(k => ({
        produk_id: k.produk_id,
        qty:       k.qty
    }))
})
```

---

## 📦 ProdukView.vue — CRUD + Upload Foto

### Upload Foto dengan FormData
```js
// Wajib pakai FormData untuk upload file
const fd = new FormData()
fd.append('nama',    form.nama)
fd.append('harga',   form.harga)
fd.append('stok',    form.stok)
fd.append('foto',    fotoFile)   // file input

// Set Content-Type ke multipart/form-data
await api.post('/produk', fd, {
    headers: { 'Content-Type': 'multipart/form-data' }
})
```

> ⚠️ Untuk tambah dan edit produk sama-sama pakai **POST** bukan PUT, karena browser tidak bisa kirim file dengan method PUT.

### Preview Foto Sebelum Upload
```js
function handleFoto(e) {
    fotoFile.value = e.target.files[0]
    // Buat URL sementara untuk preview di browser
    preview.value  = URL.createObjectURL(fotoFile.value)
}
```
```html
<input type="file" accept="image/*" @change="handleFoto" />
<img v-if="preview" :src="preview" />
```

---

## 💰 Format Rupiah

```js
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style:                'currency',
        currency:             'IDR',
        maximumFractionDigits: 0
    }).format(angka)
}

// Output: Rp 15.000
```

---

## 🔄 Pola Umum di Setiap View

```js
// 1. Ambil data saat komponen dipasang
onMounted(() => fetchData())

// 2. Fungsi fetch data dari API
async function fetchData() {
    const res = await api.get('/endpoint')
    data.value = res.data.data
}

// 3. Simpan data
async function simpan() {
    loading.value = true
    error.value   = ''
    try {
        await api.post('/endpoint', payload)
        await fetchData()   // refresh data setelah simpan
        resetForm()
    } catch (err) {
        error.value = err.response?.data?.message || 'Gagal'
    } finally {
        loading.value = false
    }
}

// 4. Hapus data
async function hapus(id) {
    if (!confirm('Yakin hapus?')) return
    await api.delete(`/endpoint/${id}`)
    fetchData()
}
```

---

## 🧠 Konsep Vue 3 yang Dipakai

| Konsep | Dipakai di | Penjelasan |
|--------|------------|------------|
| `ref()` | Semua | State reaktif untuk nilai primitif |
| `computed()` | KasirView | Nilai yang dihitung dari state lain |
| `watch()` | App.vue | Pantau perubahan route untuk update auth state |
| `onMounted()` | Semua view | Fetch data saat komponen pertama kali tampil |
| `v-model` | Form input | Two-way binding input ↔ state |
| `v-for` | List produk, keranjang | Render daftar data |
| `v-if / v-else` | Kondisional | Tampilkan elemen berdasarkan kondisi |
| `@click / @input` | Tombol, input | Event handler |
| `:disabled` | Tombol submit | Binding atribut dinamis |
| `<script setup>` | Semua | Composition API modern (lebih ringkas) |

---

## ✅ Checklist Frontend

- [x] Setup Vue + Axios + Vue Router
- [x] Konfigurasi `VITE_API_URL` di `.env`
- [x] Axios interceptor (otomatis kirim token + handle 401)
- [x] Router guard (redirect kalau belum login / role salah)
- [x] App.vue layout dinamis (sidebar muncul setelah login)
- [x] LoginView — simpan token & user ke localStorage
- [x] DashboardView — statistik & riwayat transaksi
- [x] KasirView — klik produk, keranjang, checkout
- [x] ProdukView — CRUD + preview & upload foto
- [x] ManajemenKasirView — tambah & hapus kasir
- [x] Format rupiah dengan `Intl.NumberFormat`
- [x] Loading state & error handling di semua form

---

## 🚨 Error Umum

| Error | Penyebab | Solusi |
|-------|----------|--------|
| CORS blocked | Port frontend tidak terdaftar di backend | Tambah port ke `cors.php` di backend |
| `VITE_API_URL` undefined | `.env` belum dibaca Vite | Restart `npm run dev` |
| Langsung balik ke `/login` | Token tidak tersimpan / API 401 | Cek response login di DevTools → Network |
| Foto tidak tampil | `storage:link` belum jalan di backend | `php artisan storage:link` |
| Sidebar tidak muncul setelah login | `isLoggedIn` tidak reaktif | Pakai `ref` + `watch(route, ...)` |
