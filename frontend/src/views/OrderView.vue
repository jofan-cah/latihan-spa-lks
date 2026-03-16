<template>
  <div style="min-height:100vh; background:#f8fafc; padding:20px;">
    <div style="max-width:480px; margin:0 auto;">

      <!-- Header -->
      <div style="text-align:center; margin-bottom:24px;">
        <h1 style="margin:0; font-size:24px; color:#1e293b;">Self Order</h1>
        <p style="margin:4px 0 0; color:#64748b; font-size:14px;">Pesan langsung dari meja kamu</p>
      </div>

      <!-- STEP 1: Auth -->
      <div v-if="step === 1" style="background:white; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <h2 style="margin:0 0 16px; font-size:18px;">{{ isLogin ? 'Masuk' : 'Daftar / Masuk' }}</h2>

        <div v-if="!isLogin">
          <label style="font-size:13px; font-weight:600;">Nama</label>
          <input v-model="form.nama" placeholder="Nama kamu" style="width:100%; box-sizing:border-box;" />
        </div>

        <label style="font-size:13px; font-weight:600;">No HP</label>
        <input v-model="form.no_hp" placeholder="08xxxxxxxxxx" style="width:100%; box-sizing:border-box;" />

        <div v-if="isLogin">
          <label style="font-size:13px; font-weight:600;">Password</label>
          <input v-model="form.password" type="password" placeholder="Password" style="width:100%; box-sizing:border-box;" />
        </div>

        <p v-if="authError" style="color:#dc2626; font-size:13px; margin-top:8px;">{{ authError }}</p>

        <button @click="handleAuth" :disabled="authLoading" style="width:100%; background:#2563eb; color:white; padding:12px; font-size:15px; font-weight:600; margin-top:12px;">
          {{ authLoading ? 'Loading...' : (isLogin ? 'Masuk' : 'Lanjut') }}
        </button>

        <p v-if="isLogin" @click="isLogin = false; authError = ''" style="text-align:center; font-size:13px; color:#2563eb; cursor:pointer; margin-top:12px;">
          Belum punya akun? Daftar
        </p>
        <p v-else @click="isLogin = true; authError = ''" style="text-align:center; font-size:13px; color:#2563eb; cursor:pointer; margin-top:12px;">
          Sudah punya akun? Masuk
        </p>
      </div>

      <!-- STEP 2: Pilih Menu -->
      <div v-if="step === 2">
        <!-- Header + Tab -->
        <div style="background:white; border-radius:12px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:16px;">
          <p style="margin:0 0 12px; font-size:14px; color:#64748b;">Halo, <strong>{{ customer.nama }}</strong></p>
          <div style="display:flex; gap:8px;">
            <button
              @click="activeTab = 'menu'"
              :style="`flex:1; padding:8px; font-size:13px; font-weight:600; border-radius:8px;
                background:${activeTab==='menu'?'#2563eb':'#f1f5f9'};
                color:${activeTab==='menu'?'white':'#64748b'};`"
            >Pesan</button>
            <button
              @click="activeTab = 'riwayat'; fetchRiwayat()"
              :style="`flex:1; padding:8px; font-size:13px; font-weight:600; border-radius:8px;
                background:${activeTab==='riwayat'?'#2563eb':'#f1f5f9'};
                color:${activeTab==='riwayat'?'white':'#64748b'};`"
            >Riwayat</button>
          </div>
        </div>

        <!-- Tab Riwayat -->
        <div v-if="activeTab === 'riwayat'">
          <div v-if="loadingRiwayat" style="text-align:center; padding:40px; color:#94a3b8;">Memuat...</div>
          <div v-else-if="riwayat.length === 0" style="text-align:center; padding:40px; background:white; border-radius:12px; color:#94a3b8;">
            Belum ada riwayat pesanan
          </div>
          <div v-else style="display:flex; flex-direction:column; gap:12px;">
            <div v-for="o in riwayat" :key="o.id" style="background:white; border-radius:12px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.08);">
              <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <div>
                  <p style="margin:0; font-size:20px; font-weight:900; color:#1e293b;">{{ o.nomor_antrian }}</p>
                  <p style="margin:2px 0 0; font-size:12px; color:#64748b;">{{ formatDate(o.created_at) }}</p>
                </div>
                <span :style="`font-size:11px; font-weight:700; padding:3px 10px; border-radius:999px;
                  background:${o.status==='selesai'?'#dcfce7':o.status==='proses'?'#fef3c7':'#fee2e2'};
                  color:${o.status==='selesai'?'#16a34a':o.status==='proses'?'#d97706':'#dc2626'};`">
                  {{ o.status === 'selesai' ? 'Selesai' : o.status === 'proses' ? 'Diproses' : 'Pending' }}
                </span>
              </div>
              <div style="border-top:1px solid #f1f5f9; padding-top:8px;">
                <div v-for="item in o.items" :key="item.id" style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                  <span>{{ item.produk?.nama }} × {{ item.qty }}</span>
                  <span style="color:#64748b;">{{ formatRupiah(item.subtotal) }}</span>
                </div>
              </div>
              <div style="display:flex; justify-content:space-between; margin-top:10px; padding-top:10px; border-top:1px solid #f1f5f9;">
                <span style="font-size:13px; color:#64748b;">{{ o.tipe === 'dine_in' ? '🪑 Meja ' + o.nomor_meja : '🛍️ Bawa Pulang' }}</span>
                <span style="font-size:14px; font-weight:700;">{{ formatRupiah(o.total) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab Menu -->
        <div v-if="activeTab === 'menu'">
        <div style="background:white; border-radius:12px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.08);">
          <h2 style="margin:0 0 12px; font-size:18px;">Pilih Menu</h2>
          <input v-model="search" placeholder="Cari menu..." style="width:100%; box-sizing:border-box; margin-bottom:12px;" />

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div
              v-for="p in filteredProduk" :key="p.id"
              style="border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; background:white;"
            >
              <img v-if="p.foto_url" :src="p.foto_url" style="width:100%; height:80px; object-fit:cover;" />
              <div v-else style="width:100%; height:80px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#cbd5e1; font-size:12px; letter-spacing:.5px;">NO FOTO</div>
              <div style="padding:8px;">
                <p style="margin:0; font-size:13px; font-weight:600; line-height:1.3;">{{ p.nama }}</p>
                <p style="margin:2px 0 8px; color:#2563eb; font-size:12px;">{{ formatRupiah(p.harga) }}</p>
                <div v-if="getQty(p.id) === 0">
                  <button @click="tambah(p)" :disabled="p.stok === 0" style="width:100%; background:#2563eb; color:white; padding:6px; font-size:12px; font-weight:600;">
                    {{ p.stok === 0 ? 'Habis' : '+ Tambah' }}
                  </button>
                </div>
                <div v-else style="display:flex; align-items:center; justify-content:space-between; gap:4px;">
                  <button @click="kurang(p.id)" style="width:32px; height:32px; padding:0; background:#f1f5f9; font-size:16px;">-</button>
                  <span style="font-weight:700; font-size:15px;">{{ getQty(p.id) }}</span>
                  <button @click="tambah(p)" style="width:32px; height:32px; padding:0; background:#2563eb; color:white; font-size:16px;">+</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="keranjang.length > 0" style="position:sticky; bottom:16px; margin-top:16px;">
          <button @click="step = 3" style="width:100%; background:#16a34a; color:white; padding:14px; font-size:15px; font-weight:700; border-radius:10px; box-shadow:0 4px 12px rgba(22,163,74,.4);">
            Lanjut ke Checkout ({{ totalItem }} item — {{ formatRupiah(totalHarga) }})
          </button>
        </div>
        </div> <!-- end tab menu -->
      </div>

      <!-- STEP 3: Tipe Order -->
      <div v-if="step === 3" style="background:white; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <h2 style="margin:0 0 16px; font-size:18px;">Tipe Pesanan</h2>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
          <div
            @click="tipe = 'dine_in'"
            :style="`border:2px solid ${tipe==='dine_in'?'#2563eb':'#e2e8f0'}; border-radius:10px; padding:16px; text-align:center; cursor:pointer; background:${tipe==='dine_in'?'#eff6ff':'white'}`"
          >
            <div style="font-size:28px; margin-bottom:4px;">🪑</div>
            <p style="margin:0; font-weight:600; font-size:14px;">Makan di Tempat</p>
          </div>
          <div
            @click="tipe = 'takeaway'"
            :style="`border:2px solid ${tipe==='takeaway'?'#2563eb':'#e2e8f0'}; border-radius:10px; padding:16px; text-align:center; cursor:pointer; background:${tipe==='takeaway'?'#eff6ff':'white'}`"
          >
            <div style="font-size:28px; margin-bottom:4px;">🛍️</div>
            <p style="margin:0; font-weight:600; font-size:14px;">Bawa Pulang</p>
          </div>
        </div>

        <div v-if="tipe === 'dine_in'">
          <label style="font-size:13px; font-weight:600;">Nomor Meja</label>
          <input v-model="nomor_meja" placeholder="Contoh: 5, A3" style="width:100%; box-sizing:border-box;" />
        </div>

        <label style="font-size:13px; font-weight:600;">Catatan (opsional)</label>
        <textarea v-model="catatan" placeholder="Misal: tidak pedas, tanpa bawang..." style="width:100%; box-sizing:border-box; height:70px; resize:none;"></textarea>

        <div style="display:flex; gap:10px; margin-top:12px;">
          <button @click="step = 2" style="flex:1; background:#f1f5f9; color:#334155; padding:12px; font-size:14px; font-weight:600;">← Kembali</button>
          <button @click="step = 4" :disabled="!tipe || (tipe==='dine_in' && !nomor_meja)" style="flex:2; background:#2563eb; color:white; padding:12px; font-size:14px; font-weight:600;">Review Pesanan →</button>
        </div>
      </div>

      <!-- STEP 4: Review -->
      <div v-if="step === 4" style="background:white; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <h2 style="margin:0 0 16px; font-size:18px;">Review Pesanan</h2>

        <div style="background:#f8fafc; border-radius:8px; padding:12px; margin-bottom:12px;">
          <p style="margin:0 0 4px; font-size:13px; color:#64748b;">Tipe: <strong>{{ tipe === 'dine_in' ? '🪑 Makan di Tempat' : '🛍️ Bawa Pulang' }}</strong></p>
          <p v-if="tipe === 'dine_in'" style="margin:0; font-size:13px; color:#64748b;">Meja: <strong>{{ nomor_meja }}</strong></p>
        </div>

        <div v-for="item in keranjang" :key="item.produk_id" style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f1f5f9;">
          <div>
            <p style="margin:0; font-size:14px; font-weight:500;">{{ item.nama }}</p>
            <p style="margin:2px 0 0; font-size:12px; color:#64748b;">{{ formatRupiah(item.harga) }} × {{ item.qty }}</p>
          </div>
          <span style="font-weight:700; font-size:14px;">{{ formatRupiah(item.harga * item.qty) }}</span>
        </div>

        <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:700; margin-top:12px; padding-top:12px; border-top:2px solid #e2e8f0;">
          <span>Total</span>
          <span>{{ formatRupiah(totalHarga) }}</span>
        </div>

        <p v-if="orderError" style="color:#dc2626; font-size:13px; margin-top:8px;">{{ orderError }}</p>

        <div style="display:flex; gap:10px; margin-top:16px;">
          <button @click="step = 3" style="flex:1; background:#f1f5f9; color:#334155; padding:12px; font-size:14px; font-weight:600;">← Kembali</button>
          <button @click="submitOrder" :disabled="orderLoading" style="flex:2; background:#16a34a; color:white; padding:12px; font-size:14px; font-weight:700;">
            {{ orderLoading ? 'Mengirim...' : 'Pesan Sekarang' }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const BASE_URL = import.meta.env.VITE_API_URL

const step      = ref(1)
const isLogin   = ref(false)
const activeTab = ref('menu')

const form     = ref({ nama: '', no_hp: '', password: '' })
const authError   = ref('')
const authLoading = ref(false)
const customer    = ref(null)
const token       = ref('')

const produk    = ref([])
const search    = ref('')
const keranjang = ref([])

const riwayat       = ref([])
const loadingRiwayat = ref(false)

async function fetchRiwayat() {
  loadingRiwayat.value = true
  try {
    const res = await api().get('/customer/orders')
    riwayat.value = res.data
  } finally {
    loadingRiwayat.value = false
  }
}

const tipe        = ref('')
const nomor_meja  = ref('')
const catatan     = ref('')
const orderError  = ref('')
const orderLoading = ref(false)

const api = () => axios.create({
  baseURL: BASE_URL,
  headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
})

onMounted(async () => {
  const saved = localStorage.getItem('customer_token')
  const savedCustomer = localStorage.getItem('customer_data')
  if (saved && savedCustomer) {
    token.value = saved
    customer.value = JSON.parse(savedCustomer)
    step.value = 2
    fetchProduk()
  } else {
    fetchProduk()
  }
})

async function fetchProduk() {
  const res = await axios.get(`${BASE_URL}/produk`)
  produk.value = res.data.data ?? res.data
}

const filteredProduk = computed(() =>
  produk.value.filter(p => p.nama.toLowerCase().includes(search.value.toLowerCase()))
)

function getQty(id) {
  return keranjang.value.find(k => k.produk_id === id)?.qty || 0
}

function tambah(p) {
  const ada = keranjang.value.find(k => k.produk_id === p.id)
  if (ada) {
    if (ada.qty < p.stok) ada.qty++
  } else {
    keranjang.value.push({ produk_id: p.id, nama: p.nama, harga: p.harga, stok: p.stok, qty: 1 })
  }
}

function kurang(id) {
  const i = keranjang.value.findIndex(k => k.produk_id === id)
  if (i === -1) return
  if (keranjang.value[i].qty > 1) {
    keranjang.value[i].qty--
  } else {
    keranjang.value.splice(i, 1)
  }
}

const totalItem  = computed(() => keranjang.value.reduce((s, k) => s + k.qty, 0))
const totalHarga = computed(() => keranjang.value.reduce((s, k) => s + k.harga * k.qty, 0))

async function handleAuth() {
  authError.value = ''
  authLoading.value = true
  try {
    let res
    if (isLogin.value) {
      res = await axios.post(`${BASE_URL}/customer/login`, {
        no_hp: form.value.no_hp,
        password: form.value.password,
      })
    } else {
      res = await axios.post(`${BASE_URL}/customer/register`, {
        nama: form.value.nama,
        no_hp: form.value.no_hp,
      })
    }
    token.value    = res.data.token
    customer.value = res.data.customer
    localStorage.setItem('customer_token', token.value)
    localStorage.setItem('customer_data', JSON.stringify(customer.value))
    step.value = 2
    fetchProduk()
  } catch (err) {
    authError.value = err.response?.data?.message || 'Gagal, coba lagi'
    if (err.response?.status === 422 && err.response?.data?.errors?.no_hp) {
      // no_hp already exists, switch to login
      isLogin.value = true
      authError.value = 'No HP sudah terdaftar, silakan masuk'
    }
  } finally {
    authLoading.value = false
  }
}

async function submitOrder() {
  orderError.value  = ''
  orderLoading.value = true
  try {
    const res = await api().post('/orders', {
      tipe: tipe.value,
      nomor_meja: nomor_meja.value || null,
      catatan: catatan.value || null,
      items: keranjang.value.map(k => ({ produk_id: k.produk_id, qty: k.qty })),
    })
    localStorage.removeItem('customer_token')
    localStorage.removeItem('customer_data')
    router.push({ path: '/order/sukses', query: { antrian: res.data.nomor_antrian, nama: customer.value.nama } })
  } catch (err) {
    orderError.value = err.response?.data?.message || 'Gagal mengirim pesanan'
  } finally {
    orderLoading.value = false
  }
}

function formatRupiah(n) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}

function formatDate(d) {
  return new Date(d).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })
}
</script>
