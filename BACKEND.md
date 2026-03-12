# 📘 Panduan Backend — Laravel REST API

> Studi Kasus: **Kasir Digital UMKM**
> Stack: **Laravel 11 + Sanctum + MySQL**

---

## 🗂️ Struktur Folder Penting

```
backend/
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php        ← Login, Logout, Me
│   │   ├── ProdukController.php      ← CRUD Produk + Upload Foto
│   │   ├── TransaksiController.php   ← Checkout + Riwayat
│   │   └── UserController.php        ← Kelola Kasir (admin)
│   └── Models/
│       ├── User.php
│       ├── Produk.php
│       ├── Transaksi.php
│       └── DetailTransaksi.php
├── config/
│   └── cors.php                      ← Izin akses dari frontend
├── database/
│   ├── migrations/                   ← Struktur tabel
│   └── seeders/                      ← Data awal
└── routes/
    └── api.php                       ← Semua endpoint API
```

---

## ⚙️ Setup Awal

### 1. Install Laravel
```bash
composer create-project laravel/laravel backend
cd backend
php artisan install:api        # Install Sanctum
php artisan storage:link       # Aktifkan akses foto publik
```

### 2. Konfigurasi `.env`
```env
APP_URL=http://backendd.test   # URL Herd kamu

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lks_kasir
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173
```

### 3. Konfigurasi CORS (`config/cors.php`)
```php
return [
    'paths'               => ['api/*'],
    'allowed_methods'     => ['*'],
    'allowed_origins'     => [
        'http://localhost:5173',  // port default Vite
        'http://localhost:5174',  // port fallback Vite
    ],
    'allowed_headers'     => ['*'],
    'supports_credentials'=> true,
];
```
> ⚠️ Kalau muncul error CORS di browser, cek port frontend (5173 atau 5174) dan tambahkan ke sini.

---

## 🗃️ Database

### Tabel yang Dibuat

| Tabel | Keterangan |
|-------|------------|
| `users` | Data admin & kasir |
| `produk` | Data produk (UUID, soft delete) |
| `transaksi` | Header transaksi (UUID) |
| `detail_transaksi` | Item per transaksi |

### Migration Users — Tambah Kolom `role`
```php
// 0001_01_01_000000_create_users_table.php
$table->enum('role', ['admin', 'kasir'])->default('kasir');
```

### Migration Produk
```php
Schema::create('produk', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('nama');
    $table->string('kategori')->nullable();
    $table->decimal('harga', 15, 2);
    $table->integer('stok')->default(0);
    $table->string('foto')->nullable();
    $table->softDeletes();    // deleted_at — hapus tidak permanen
    $table->timestamps();
});
```

### Migration Transaksi
```php
Schema::create('transaksi', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->constrained();  // kasir yang input
    $table->decimal('total', 15, 2);
    $table->decimal('bayar', 15, 2);
    $table->decimal('kembalian', 15, 2);
    $table->timestamps();
});
```

### Migration Detail Transaksi
```php
Schema::create('detail_transaksi', function (Blueprint $table) {
    $table->id();
    $table->uuid('transaksi_id');
    $table->uuid('produk_id');
    $table->integer('qty');
    $table->decimal('harga_satuan', 15, 2);
    $table->decimal('subtotal', 15, 2);
    $table->timestamps();

    $table->foreign('transaksi_id')->references('id')->on('transaksi');
    $table->foreign('produk_id')->references('id')->on('produk');
});
```

### Jalankan Migration + Seeder
```bash
php artisan migrate:fresh --seed
```

---

## 🧩 Model

### Konsep Penting

| Fitur | Dipakai di | Penjelasan |
|-------|------------|------------|
| `HasUuids` | Produk, Transaksi | Primary key otomatis UUID |
| `SoftDeletes` | Produk | Hapus tidak permanen (ada `deleted_at`) |
| `HasApiTokens` | User | Wajib untuk Sanctum token auth |
| `$fillable` | Semua | Kolom yang boleh diisi via `create()` / `update()` |
| `$appends` | Produk | Tambah atribut virtual ke JSON response |

### Model Produk
```php
use HasUuids, SoftDeletes;

protected $table    = 'produk';
protected $fillable = ['nama', 'kategori', 'harga', 'stok', 'foto'];
protected $appends  = ['foto_url'];

// Atribut virtual — otomatis muncul di response JSON
public function getFotoUrlAttribute(): ?string
{
    return $this->foto ? asset('storage/' . $this->foto) : null;
}
```

### Model Transaksi
```php
use HasUuids;

protected $fillable = ['user_id', 'total', 'bayar', 'kembalian'];

public function kasir() {
    return $this->belongsTo(User::class, 'user_id');
}

public function detail() {
    return $this->hasMany(DetailTransaksi::class, 'transaksi_id');
}
```

### Model User — Wajib Ada HasApiTokens
```php
use HasApiTokens, HasFactory, Notifiable;

protected $fillable = ['name', 'email', 'password', 'role'];
```

---

## 🔌 Controller

### AuthController
```php
// Login — kembalikan token
public function login(Request $request) {
    $request->validate(['email'=>'required|email','password'=>'required']);

    if (!Auth::attempt($request->only('email','password'))) {
        return response()->json(['success'=>false,'message'=>'Email atau password salah'], 401);
    }

    $user  = Auth::user();
    $token = $user->createToken('kasir_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'data'    => ['user' => $user, 'token' => $token]
    ]);
}

// Logout — hapus token aktif
public function logout(Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['success' => true, 'message' => 'Logout berhasil']);
}
```

### ProdukController — Upload Foto
```php
public function store(Request $request) {
    $request->validate([
        'nama'  => 'required|string',
        'harga' => 'required|numeric|min:0',
        'stok'  => 'required|integer|min:0',
        'foto'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    $data = $request->only('nama', 'harga', 'stok', 'kategori');

    if ($request->hasFile('foto')) {
        // Simpan ke storage/app/public/produk/
        $data['foto'] = $request->file('foto')->store('produk', 'public');
    }

    return response()->json(['success'=>true, 'data'=> Produk::create($data)], 201);
}

// Update — hapus foto lama sebelum simpan baru
public function update(Request $request, $id) {
    $produk = Produk::findOrFail($id);

    if ($request->hasFile('foto')) {
        if ($produk->foto) Storage::disk('public')->delete($produk->foto);
        $data['foto'] = $request->file('foto')->store('produk', 'public');
    }

    $produk->update($data);
}
```

> ⚠️ Update produk pakai method **POST** bukan PUT/PATCH karena `multipart/form-data` (upload file) tidak support PUT di beberapa browser.

### TransaksiController — DB Transaction
```php
public function store(Request $request) {
    DB::beginTransaction();
    try {
        $total = 0;
        foreach ($request->items as $item) {
            $produk = Produk::findOrFail($item['produk_id']);

            // Validasi stok
            if ($produk->stok < $item['qty']) {
                return response()->json(['message' => "Stok {$produk->nama} tidak cukup"], 422);
            }

            $subtotal = $produk->harga * $item['qty'];
            $total   += $subtotal;

            // Kurangi stok otomatis
            $produk->decrement('stok', $item['qty']);

            // Simpan detail
            DetailTransaksi::create([...]);
        }

        // Validasi uang bayar
        if ($request->bayar < $total) {
            return response()->json(['message' => 'Uang bayar kurang'], 422);
        }

        Transaksi::create([...]);

        DB::commit();   // Semua berhasil — simpan permanen
        return response()->json(['success' => true], 201);

    } catch (\Exception $e) {
        DB::rollBack(); // Ada error — batalkan semua perubahan
        return response()->json(['message' => 'Transaksi gagal'], 500);
    }
}
```

> 💡 **DB Transaction** penting agar kalau ada error di tengah proses, semua perubahan (stok, transaksi, detail) ikut dibatalkan — data tidak setengah-setengah.

---

## 🛣️ Routes API (`routes/api.php`)

```php
// Public — tidak perlu login
Route::post('/login', [AuthController::class, 'login']);

// Protected — wajib pakai token
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Produk
    Route::get('/produk',         [ProdukController::class, 'index']);
    Route::post('/produk',        [ProdukController::class, 'store']);
    Route::post('/produk/{id}',   [ProdukController::class, 'update']); // POST bukan PUT
    Route::delete('/produk/{id}', [ProdukController::class, 'destroy']);

    // Transaksi
    Route::get('/transaksi',  [TransaksiController::class, 'index']);
    Route::post('/transaksi', [TransaksiController::class, 'store']);

    // Manajemen Kasir
    Route::get('/kasir',         [UserController::class, 'index']);
    Route::post('/kasir',        [UserController::class, 'store']);
    Route::delete('/kasir/{id}', [UserController::class, 'destroy']);
});
```

### Cek semua route terdaftar
```bash
php artisan route:list --path=api
```

---

## 🔐 Sanctum — Token Auth

### Cara Kerja
1. User login → backend buat token → kirim ke frontend
2. Frontend simpan token di `localStorage`
3. Setiap request selanjutnya, frontend kirim header: `Authorization: Bearer <token>`
4. Backend validasi token via middleware `auth:sanctum`

### Flow Login
```
POST /api/login
  → Auth::attempt() → true/false
  → $user->createToken('nama')->plainTextToken
  → kembalikan token ke frontend
```

---

## 🧪 Test dengan cURL

```bash
# Login
curl -X POST http://backendd.test/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@kasir.com","password":"password"}'

# Get Produk (pakai token dari login)
curl http://backendd.test/api/produk \
  -H "Authorization: Bearer TOKEN_DISINI"
```

---

## ✅ Checklist Backend

- [x] Install Laravel + Sanctum (`php artisan install:api`)
- [x] `php artisan storage:link`
- [x] Tambah kolom `role` di migration users
- [x] Migration 4 tabel (users, produk, transaksi, detail_transaksi)
- [x] UUID primary key (Produk & Transaksi)
- [x] Soft Delete produk
- [x] Seeder admin & kasir
- [x] `HasApiTokens` di User model
- [x] AuthController (login, logout, me)
- [x] ProdukController (CRUD + upload foto)
- [x] TransaksiController (checkout + kurangi stok + DB transaction)
- [x] UserController (kelola kasir — admin only)
- [x] CORS config sesuai port frontend

---

## 🚨 Error Umum

| Error | Penyebab | Solusi |
|-------|----------|--------|
| CORS blocked | Port frontend tidak ada di `cors.php` | Tambah port ke `allowed_origins` + `php artisan config:clear` |
| 401 Unauthorized | Token tidak dikirim / tidak valid | Pastikan header `Authorization: Bearer token` ada |
| 422 Unprocessable | Validasi gagal | Cek field yang dikirim sesuai aturan validasi |
| Foto tidak muncul | `storage:link` belum dijalankan | `php artisan storage:link` |
| Route not found | Cache lama | `php artisan route:clear` |
