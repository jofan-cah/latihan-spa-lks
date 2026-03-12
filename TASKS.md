# LKS POS — Task Board Lengkap

> Format: setiap task punya **Sumber**, **File terkait**, **Command/Steps**, dan **Status**
> Stack: Laravel 12 + Sanctum (backend) · Vue 3 + Vite + Axios + Vue Router (frontend)
> Backend URL: `http://backend.test` (Laravel Herd) · Frontend Dev: `http://localhost:5173`

---

## ✅ DONE

### [BE-01] Setup Project Laravel 12
- **Sumber:** https://laravel.com/docs/12.x/installation
- **Tools:** Laravel Herd (lokal), Composer
- **Command:**
  ```bash
  composer create-project laravel/laravel backend
  cd backend && cp .env.example .env && php artisan key:generate
  ```
- **File terkait:** `backend/.env`, `backend/composer.json`
- **Catatan:** Pakai PHP 8.2+, Laravel Herd otomatis serve di `backend.test`

---

### [BE-02] Install & Konfigurasi Laravel Sanctum
- **Sumber:** https://laravel.com/docs/12.x/sanctum
- **Command:**
  ```bash
  composer require laravel/sanctum
  php artisan install:api
  ```
- **File terkait:**
  - `backend/config/sanctum.php`
  - `backend/app/Models/User.php` → tambah `HasApiTokens`
  - `backend/bootstrap/app.php` → daftarkan middleware `auth:sanctum`
- **Catatan:** Pakai token-based (bukan cookie/SPA), token disimpan di localStorage frontend

---

### [BE-03] Konfigurasi Database MySQL
- **Sumber:** https://laravel.com/docs/12.x/database
- **Tools:** Laravel Herd (MySQL built-in)
- **File terkait:** `backend/.env`
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=lks_kasir
  DB_USERNAME=root
  DB_PASSWORD=
  ```
- **Command:**
  ```bash
  mysql -u root -e "CREATE DATABASE lks_kasir"
  php artisan migrate:fresh --seed
  ```

---

### [BE-04] Migration: Tabel Produk
- **Sumber:** https://laravel.com/docs/12.x/migrations
- **File:** `backend/database/migrations/2026_03_11_130000_create_produk_table.php`
- **Kolom:** `id`, `nama`, `harga`, `stok`, `gambar` (nullable), `timestamps`
- **Command:** `php artisan make:migration create_produk_table`

---

### [BE-05] Migration: Tabel Transaksi & Detail Transaksi
- **Sumber:** https://laravel.com/docs/12.x/migrations
- **Command:**
  ```bash
  php artisan make:migration create_transaksi_table
  php artisan make:migration create_detail_transaksi_table
  php artisan migrate
  ```
- **File:**
  - `backend/database/migrations/..._create_transaksi_table.php`
    - Kolom: `id`, `user_id` (FK), `total`, `bayar`, `kembalian`, `timestamps`
  - `backend/database/migrations/..._create_detail_transaksi_table.php`
    - Kolom: `id`, `transaksi_id` (FK), `produk_id` (FK), `qty`, `harga`, `subtotal`

---

### [BE-06] Seeder: Admin & Kasir
- **Sumber:** https://laravel.com/docs/12.x/seeding
- **File:** `backend/database/seeders/DatabaseSeeder.php`
- **Akun:**
  - Admin: `admin@kasir.com` / `password` / role: `admin`
  - Kasir: `kasir@kasir.com` / `password` / role: `kasir`
- **Command:** `php artisan db:seed`

---

### [BE-07] AuthController — Login & Logout
- **Sumber:** https://laravel.com/docs/12.x/sanctum#issuing-mobile-application-tokens
- **File:** `backend/app/Http/Controllers/Api/AuthController.php`
- **Endpoint:**
  - `POST /api/login` → return `token`, `user`
  - `GET  /api/me` → return user login
  - `POST /api/logout` → revoke token
- **Auth:** Login pakai `Auth::attempt()`, token dibuat dengan `createToken()`

---

### [BE-08] ProdukController — CRUD
- **File:** `backend/app/Http/Controllers/Api/ProdukController.php`
- **Endpoint:**
  - `GET    /api/produk`       → list semua produk
  - `POST   /api/produk`       → tambah produk (+ upload gambar)
  - `POST   /api/produk/{id}`  → update produk (pakai POST karena ada file/multipart)
  - `DELETE /api/produk/{id}`  → hapus produk
- **Upload gambar:** `$request->file('gambar')->store('produk', 'public')`
- **Sumber storage:** `php artisan storage:link`

---

### [BE-09] TransaksiController — Buat Transaksi
- **File:** `backend/app/Http/Controllers/Api/TransaksiController.php`
- **Endpoint:**
  - `GET  /api/transaksi`  → riwayat transaksi
  - `POST /api/transaksi`  → simpan transaksi baru + detail
- **Logic:** Loop item keranjang → simpan ke `detail_transaksi`, kurangi stok produk

---

### [BE-10] UserController — Manajemen Kasir
- **File:** `backend/app/Http/Controllers/Api/UserController.php`
- **Endpoint:**
  - `GET    /api/kasir`       → list semua kasir
  - `POST   /api/kasir`       → tambah kasir baru
  - `DELETE /api/kasir/{id}`  → hapus kasir
- **Catatan:** Hanya admin yang boleh akses (middleware cek role)

---

### [FE-01] Setup Project Vue 3 + Vite
- **Sumber:** https://vuejs.org/guide/quick-start
- **Command:**
  ```bash
  npm create vue@latest frontend
  cd frontend && npm install
  npm install axios vue-router
  ```
- **File terkait:** `frontend/package.json`, `frontend/vite.config.js`

---

### [FE-02] Setup Vue Router + Route Guard
- **Sumber:** https://router.vuejs.org/guide/advanced/navigation-guards
- **File:** `frontend/src/router/index.js`
- **Guard logic:**
  - Cek `localStorage.getItem('token')` → kalau tidak ada, redirect ke `/login`
  - Cek `user.role` → kalau bukan admin, redirect ke `/`
- **Routes:** `/login`, `/` (dashboard), `/produk`, `/kasir`, `/kelola-kasir`

---

### [FE-03] Setup Axios Instance
- **Sumber:** https://axios-http.com/docs/instance
- **File:** `frontend/src/api/index.js`
- **Config:**
  ```js
  axios.defaults.baseURL = 'http://backend.test/api'
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  ```

---

### [FE-04] LoginView.vue
- **File:** `frontend/src/views/LoginView.vue`
- **Flow:** Input email + password → POST `/api/login` → simpan token & user ke localStorage → redirect ke `/`

---

### [FE-05] DashboardView.vue
- **File:** `frontend/src/views/DashboardView.vue`
- **Isi:** Summary total transaksi hari ini, total produk, shortcut menu

---

### [FE-06] ProdukView.vue — CRUD Produk
- **File:** `frontend/src/views/ProdukView.vue`
- **Fitur:** Tabel produk, form tambah/edit (modal), delete dengan konfirmasi, upload gambar preview

---

### [FE-07] ManajemenKasirView.vue
- **File:** `frontend/src/views/ManajemenKasirView.vue`
- **Fitur:** Tabel kasir, form tambah kasir, tombol hapus

---

### [FE-08] KasirView.vue — Halaman Transaksi
- **File:** `frontend/src/views/KasirView.vue`
- **Fitur:** List produk (klik = masuk keranjang), keranjang belanja, input bayar, hitung kembalian, submit transaksi

---

## 🔄 IN PROGRESS

### [FE-09] KasirView — Integrasi API Checkout
- **Sumber:** `POST /api/transaksi`
- **File:** `frontend/src/views/KasirView.vue`
- **Payload:**
  ```json
  {
    "items": [{ "produk_id": 1, "qty": 2, "harga": 5000 }],
    "bayar": 20000
  }
  ```
- **Steps:**
  1. Klik produk → push ke array `keranjang`
  2. Hitung total otomatis (computed)
  3. Input nominal bayar → hitung kembalian
  4. Tombol "Bayar" → POST ke `/api/transaksi`
  5. Kosongkan keranjang setelah sukses

---

### [FE-10] Role Guard Frontend (Admin vs Kasir)
- **File:** `frontend/src/router/index.js`
- **Steps:**
  1. Setelah login, simpan `user.role` di localStorage
  2. Di `beforeEach` router: cek `meta.role` vs `user.role`
  3. Sembunyikan menu admin di navbar kalau role kasir
- **Sumber:** https://router.vuejs.org/guide/advanced/meta

---

## 📋 TASK (To Do)

### [BE-11] Middleware Cek Role Admin
- **Sumber:** https://laravel.com/docs/12.x/middleware
- **File baru:** `backend/app/Http/Middleware/IsAdmin.php`
- **Command:** `php artisan make:middleware IsAdmin`
- **Logic:**
  ```php
  if (auth()->user()->role !== 'admin') {
      return response()->json(['message' => 'Forbidden'], 403);
  }
  ```
- **Daftarkan di:** `backend/bootstrap/app.php` → `withMiddleware()`
- **Pasang ke route:** `/api/produk (POST/DELETE)`, `/api/kasir`

---

### [BE-12] Endpoint Laporan Penjualan
- **File:** `backend/app/Http/Controllers/Api/TransaksiController.php`
- **Endpoint:** `GET /api/laporan?dari=2026-03-01&sampai=2026-03-12`
- **Query:**
  ```php
  Transaksi::whereBetween('created_at', [$dari, $sampai])
      ->with('detailTransaksi.produk')
      ->get();
  ```
- **Return:** total transaksi, total pendapatan, list transaksi

---

### [BE-13] Endpoint Detail Transaksi (Struk)
- **File:** `backend/app/Http/Controllers/Api/TransaksiController.php`
- **Endpoint:** `GET /api/transaksi/{id}`
- **Return:** data transaksi + detail item + nama produk + kasir

---

### [BE-14] Pagination List Produk
- **Sumber:** https://laravel.com/docs/12.x/pagination
- **File:** `ProdukController@index`
- **Code:** `Produk::paginate(10)`
- **Frontend:** tangkap `data`, `current_page`, `last_page` dari response

---

### [FE-11] Halaman Laporan / Riwayat Transaksi
- **File baru:** `frontend/src/views/LaporanView.vue`
- **Route:** `/laporan` (admin only)
- **Fitur:**
  - Filter tanggal (dari - sampai)
  - Tabel riwayat transaksi
  - Total pendapatan di bawah tabel
- **API:** `GET /api/laporan?dari=...&sampai=...`

---

### [FE-12] Print Struk Transaksi
- **File:** `frontend/src/views/KasirView.vue` atau komponen `Struk.vue`
- **Cara:** `window.print()` + CSS `@media print` untuk sembunyikan elemen lain
- **Alternatif:** pakai library `vue3-html2pdf` atau `jspdf`
  ```bash
  npm install jspdf
  ```
- **Sumber:** https://mozilla.github.io/jspdf/

---

### [FE-13] Toast Notifikasi Sukses/Gagal
- **Opsi 1 (tanpa library):** buat komponen `Toast.vue` sendiri, pakai `v-if` + `setTimeout`
- **Opsi 2 (pakai library):**
  ```bash
  npm install vue-toastification
  ```
  - Sumber: https://vue-toastification.maronato.dev/
- **Trigger:** setelah POST/DELETE berhasil atau gagal (di `.catch()`)

---

### [FE-14] Loading State Saat Fetch API
- **File:** semua View yang ada fetch Axios
- **Pattern:**
  ```js
  const loading = ref(false)
  loading.value = true
  await axios.get(...)
  loading.value = false
  ```
- **Template:** `<div v-if="loading">Loading...</div>` atau pakai spinner CSS

---

### [FE-15] Handle Error API Global (Axios Interceptor)
- **File:** `frontend/src/api/index.js`
- **Sumber:** https://axios-http.com/docs/interceptors
- **Logic:**
  ```js
  axios.interceptors.response.use(
    res => res,
    err => {
      if (err.response.status === 401) router.push('/login')
      return Promise.reject(err)
    }
  )
  ```
- **Error yang ditangani:** 401 (logout otomatis), 422 (validasi), 500 (server error)

---

### [FE-16] Tampilan Responsif (Mobile-Friendly)
- **File:** semua View
- **Cara:** pakai CSS Flexbox/Grid + media query
- **Alternatif:** install Tailwind CSS
  ```bash
  npm install -D tailwindcss @tailwindcss/vite
  ```
  - Sumber: https://tailwindcss.com/docs/installation/using-vite

---

### [TEST-01] Test Flow End-to-End
- **Skenario:**
  1. Login sebagai kasir → akses `/kasir` ✅
  2. Pilih produk → masuk keranjang ✅
  3. Input bayar → hitung kembalian ✅
  4. Submit → stok berkurang ✅
  5. Login sebagai admin → akses `/produk` & `/kelola-kasir` ✅
  6. Tambah/edit/hapus produk ✅
  7. Lihat laporan transaksi ✅

---

### [TEST-02] Cek Semua Protected Route
- **Tools:** Browser (manual) atau Postman
- **Cek:**
  - Akses `/produk` tanpa login → redirect ke `/login`
  - Akses `/kelola-kasir` sebagai kasir → redirect ke `/`
  - Request API tanpa token → 401 Unauthenticated
  - Request admin route sebagai kasir → 403 Forbidden

---

### [DEPLOY-01] Build & Demo Final
- **Command:**
  ```bash
  # Frontend
  cd frontend && npm run build
  # Output: frontend/dist/ → serve via Herd atau nginx

  # Backend
  php artisan config:cache
  php artisan route:cache
  php artisan storage:link
  ```
- **Pastikan:** `.env` production sudah benar, `APP_DEBUG=false`

---

## 📊 Progress Summary

| Kategori | Done | In Progress | Todo |
|----------|------|-------------|------|
| Backend  | 10   | 0           | 4    |
| Frontend | 8    | 2           | 6    |
| Testing  | 0    | 0           | 2    |
| Deploy   | 0    | 0           | 1    |
| **Total**| **18** | **2**     | **13** |

---

> Dibuat: 2026-03-12 · Update sesuai progress project
