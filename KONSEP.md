# LKS POS — Konsep Sesi 2: Self Order + Table System

> Ekstensi dari sistem kasir existing.
> Prinsip: **tidak mengubah tabel/fitur lama**, hanya menambah.
> Stack tetap: Laravel 12 + Sanctum · Vue 3 + Vite + Axios + Vue Router

---

## Flow Lengkap

```
CUSTOMER SIDE
─────────────────────────────────────────────────────────
[Datang ke Resto] → Scan QR di meja / Ambil nomor antrian
        ↓
[Buka Halaman /order]
        ↓
Punya akun? ──── YA ──→ Login (no HP + password)
     │                         ↓
    TIDAK                  Pilih Menu
     ↓
Input Nama + No HP → Auto Register (password = no HP)
     ↓
Pilih Menu (bisa tambah/kurang qty)
     ↓
Pilih Tipe Order:
  [🪑 Makan di Tempat]        [🛍️ Bawa Pulang]
         ↓                            ↓
  Input Nomor Meja             Langsung Submit
         ↓
  Submit Order
        ↓
Dapat Nomor Antrian → Tunggu dipanggil

─────────────────────────────────────────────────────────
KASIR SIDE (halaman /kasir yang sudah ada)
─────────────────────────────────────────────────────────
Tab 1: [Kasir Manual]      ← EXISTING, tidak berubah
Tab 2: [Pesanan Masuk]     ← NEW

  Filter: [Semua] [Meja] [Bawa Pulang]
        ↓
  Lihat detail pesanan customer
        ↓
  [Proses] → status: pending → proses
        ↓
  [Selesai] → status: selesai → nomor antrian dipanggil
        ↓
  (Opsional) Print struk

─────────────────────────────────────────────────────────
DISPLAY ANTRIAN (opsional, halaman /antrian)
─────────────────────────────────────────────────────────
Tampil di TV / monitor kasir
Menampilkan nomor antrian yang sudah selesai / dipanggil
Auto refresh setiap beberapa detik
```

---

## Struktur Route

| Route | Akses | Keterangan |
|-------|-------|------------|
| `/order` | Public (customer) | Halaman self order customer |
| `/order/menu` | Public (customer) | Pilih menu |
| `/order/sukses` | Public (customer) | Konfirmasi + nomor antrian |
| `/antrian` | Public | Display antrian (TV/monitor) |
| `/kasir` | Kasir login | Tab manual + tab pesanan masuk |
| `/laporan` | Admin login | Sudah ada di sesi 1 |

---

## Database (Tambahan — tidak ubah tabel lama)

### Tabel `customers`
```sql
id              BIGINT PK AUTO_INCREMENT
nama            VARCHAR(100)
no_hp           VARCHAR(20) UNIQUE   -- sebagai username login
password        VARCHAR(255)         -- default: no_hp di-hash bcrypt
timestamps      created_at, updated_at
```

### Tabel `orders`
```sql
id              BIGINT PK AUTO_INCREMENT
customer_id     FK → customers.id
nomor_antrian   VARCHAR(10)          -- contoh: A001, A002
tipe            ENUM('dine_in', 'takeaway')
nomor_meja      VARCHAR(10) NULLABLE -- hanya untuk dine_in
status          ENUM('pending', 'proses', 'selesai') DEFAULT 'pending'
total           DECIMAL(12,2)
catatan         TEXT NULLABLE
timestamps      created_at, updated_at
```

### Tabel `order_items`
```sql
id              BIGINT PK AUTO_INCREMENT
order_id        FK → orders.id
produk_id       FK → produk.id
qty             INT
harga           DECIMAL(12,2)        -- snapshot harga saat order
subtotal        DECIMAL(12,2)
```

> Tabel `transaksi` & `detail_transaksi` lama → TIDAK DIUBAH ✅

---

## Backend — Endpoint Baru

### Auth Customer
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST | `/api/customer/register` | Daftar pakai nama + no HP |
| POST | `/api/customer/login` | Login pakai no HP + password |
| GET | `/api/customer/me` | Data customer login |

### Order
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST | `/api/orders` | Buat order baru (customer) |
| GET | `/api/orders/antrian` | List antrian aktif (public) |
| GET | `/api/orders` | Semua pesanan masuk (kasir) |
| GET | `/api/orders/{id}` | Detail 1 pesanan |
| PATCH | `/api/orders/{id}/status` | Update status (kasir) |

---

## Frontend — Halaman Baru

### `/order` — Customer Self Order
- Step 1: Cek akun (login atau daftar)
- Step 2: Pilih menu (grid produk + qty)
- Step 3: Pilih tipe (dine_in / takeaway) + input nomor meja
- Step 4: Review & Submit
- Step 5: Halaman sukses + nomor antrian

### `/antrian` — Display Antrian
- Tampil nomor antrian yang sedang diproses & selesai
- Auto polling tiap 5 detik (`setInterval` + GET `/api/orders/antrian`)
- Desain besar, kontras — cocok untuk TV/monitor

### `/kasir` — Tambah Tab "Pesanan Masuk"
- Tab baru di halaman kasir existing
- Filter: Semua / Meja / Bawa Pulang
- Card tiap pesanan: nama customer, nomor meja, item, total, status
- Tombol: `[Proses]` → `[Selesai]`

---

## Task Board Sesi 2

### ✅ SELESAI

#### [S2-BE-01] ✅ Migration: Tabel customers
- **File:** `backend/database/migrations/2026_03_14_094615_create_customers_table.php`
- Kolom: id, nama, no_hp (unique), password, timestamps

---

#### [S2-BE-02] ✅ Migration: Tabel orders & order_items
- **File:**
  - `backend/database/migrations/2026_03_14_094617_create_orders_table.php`
  - `backend/database/migrations/2026_03_14_094619_create_order_items_table.php`
- Catatan: `produk_id` pakai `char(36)` karena tabel produk pakai UUID

---

#### [S2-BE-03] ✅ Model: Customer, Order, OrderItem
- `backend/app/Models/Customer.php` → HasApiTokens, fillable, relasi orders
- `backend/app/Models/Order.php` → fillable, relasi customer + items
- `backend/app/Models/OrderItem.php` → fillable, timestamps = false, relasi produk

---

#### [S2-BE-04] ✅ CustomerAuthController — Register & Login
- `backend/app/Http/Controllers/Api/CustomerAuthController.php`
- `register`: buat customer baru, password = bcrypt(no_hp), return token
- `login`: cek no_hp + password, return token
- `me`: return data customer dari token

---

#### [S2-BE-05] ✅ OrderController — CRUD Order
- `backend/app/Http/Controllers/Api/OrderController.php`
- `store`: generate nomor antrian (A001, A002, ...) per hari, simpan order + items
- `index`: semua pesanan, support filter tipe & status
- `show`: detail 1 pesanan
- `updateStatus`: PATCH status (pending → proses → selesai)
- `antrian`: GET public, pesanan berstatus proses/selesai (max 20 terakhir)

---

#### [S2-BE-06] ✅ Route Customer & Order
- `backend/routes/api.php` diupdate dengan:
  - Public: `/customer/register`, `/customer/login`, `/orders/antrian`, `/produk` (GET)
  - Customer auth: `/customer/me`, `/orders` (POST)
  - Staff auth: `/orders` (GET), `/orders/{id}`, `/orders/{id}/status`

---

#### [S2-FE-01] ✅ Halaman `/order` — Customer Self Order
- `frontend/src/views/OrderView.vue`
- Step 1: Form login/daftar (auto-switch jika no_hp sudah ada)
- Step 2: Grid produk + qty counter (grid 2 kolom, mobile-friendly)
- Step 3: Pilih tipe dine_in/takeaway + nomor meja + catatan
- Step 4: Review & submit → redirect ke `/order/sukses`

---

#### [S2-FE-02] ✅ Halaman `/order/sukses` — Konfirmasi
- `frontend/src/views/OrderSuksesView.vue`
- Nomor antrian besar di tengah, nama customer, tombol "Pesan Lagi"

---

#### [S2-FE-03] ✅ Halaman `/antrian` — Display Antrian
- `frontend/src/views/AntrianView.vue`
- Auto polling tiap 5 detik
- Hijau = selesai, kuning = proses
- Desain dark mode besar cocok untuk TV/monitor

---

#### [S2-FE-04] ✅ Update `/kasir` — Tab Pesanan Masuk
- `frontend/src/views/KasirView.vue`
- Tab switcher: Kasir Manual | Pesanan Masuk
- Badge merah jumlah pesanan pending
- Filter tipe & status
- Card pesanan: nomor antrian, customer, items, total, tombol Proses/Selesai
- Tombol Refresh manual

---

#### [S2-FE-05] ✅ Route di Vue Router
- `frontend/src/router/index.js`
- `/order`, `/order/sukses`, `/antrian` → public (tanpa requiresAuth)

---

## Progress Summary Sesi 2

| Kategori | Done | In Progress | Todo |
|----------|------|-------------|------|
| Backend  | 6    | 0           | 0    |
| Frontend | 5    | 0           | 0    |
| **Total**| **11**| **0**      | **0** |

---

> Dibuat: 2026-03-13 · Selesai: 2026-03-14
> Sesi 1 lihat: `TASKS.md`
