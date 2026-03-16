<template>
  <div>
    <!-- Tab header -->
    <div style="display:flex; gap:4px; margin-bottom:20px; border-bottom:2px solid #e2e8f0; padding-bottom:0;">
      <button
        @click="activeTab = 'manual'"
        :style="`padding:10px 20px; font-weight:600; font-size:14px; border-radius:6px 6px 0 0; border:none; cursor:pointer; background:${activeTab==='manual'?'#2563eb':'#f1f5f9'}; color:${activeTab==='manual'?'white':'#64748b'}; margin-bottom:-2px; border-bottom:${activeTab==='manual'?'2px solid #2563eb':'2px solid transparent'};`"
      >Kasir Manual</button>
      <button
        @click="activeTab = 'pesanan'; fetchOrders()"
        :style="`padding:10px 20px; font-weight:600; font-size:14px; border-radius:6px 6px 0 0; border:none; cursor:pointer; background:${activeTab==='pesanan'?'#2563eb':'#f1f5f9'}; color:${activeTab==='pesanan'?'white':'#64748b'}; margin-bottom:-2px; border-bottom:${activeTab==='pesanan'?'2px solid #2563eb':'2px solid transparent'};`"
      >
        Pesanan Masuk
        <span v-if="pendingCount > 0" style="background:#ef4444; color:white; font-size:11px; font-weight:700; border-radius:999px; padding:1px 6px; margin-left:6px;">{{ pendingCount }}</span>
      </button>
    </div>

    <!-- Tab 1: Kasir Manual -->
    <div v-if="activeTab === 'manual'">
      <h2 style="margin-top:0;">Transaksi Penjualan</h2>
      <div style="display:flex; gap:20px; align-items:flex-start;">

        <!-- Kiri: Daftar Produk -->
        <div style="flex:2;">
          <div style="display:flex; gap:8px; margin-bottom:12px;">
            <input v-model="search" placeholder="Cari produk..." @input="fetchProduk" style="flex:1; margin-bottom:0;" />
            <select v-model="filterKategori" @change="fetchProduk" style="width:140px; margin-bottom:0;">
              <option value="">Semua</option>
              <option value="Makanan">Makanan</option>
              <option value="Minuman">Minuman</option>
            </select>
          </div>
          <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;">
            <div
              v-for="p in produk" :key="p.id"
              @click="tambahKeranjang(p)"
              style="border:1px solid #e2e8f0; padding:10px; cursor:pointer; border-radius:8px; background:white; transition:box-shadow .2s;"
              @mouseenter="e => e.currentTarget.style.boxShadow='0 4px 12px rgba(0,0,0,.1)'"
              @mouseleave="e => e.currentTarget.style.boxShadow='none'"
            >
              <img v-if="p.foto_url" :src="p.foto_url" style="width:100%;height:80px;object-fit:cover;border-radius:4px;" />
              <div v-else style="width:100%;height:80px;background:#f1f5f9;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#94a3b8;"> </div>
              <p style="margin:6px 0 2px; font-weight:600; font-size:14px;">{{ p.nama }}</p>
              <p style="margin:0; color:#2563eb; font-size:13px;">{{ formatRupiah(p.harga) }}</p>
              <p style="margin:2px 0 0; font-size:12px; color:#64748b;">Stok: {{ p.stok }}</p>
            </div>
          </div>
        </div>

        <!-- Kanan: Keranjang -->
        <div style="flex:1; background:white; border-radius:10px; padding:20px; box-shadow:0 1px 4px rgba(0,0,0,.08); position:sticky; top:0;">
          <h3 style="margin-top:0;">  Keranjang</h3>

          <div v-if="keranjang.length === 0" style="color:#94a3b8; text-align:center; padding:20px 0;">Klik produk untuk menambahkan</div>

          <div v-for="(item, i) in keranjang" :key="i" style="display:flex; align-items:center; gap:8px; margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #f1f5f9;">
            <div style="flex:1;">
              <p style="margin:0; font-size:14px; font-weight:500;">{{ item.nama }}</p>
              <p style="margin:2px 0 0; font-size:12px; color:#64748b;">{{ formatRupiah(item.harga) }} × {{ item.qty }}</p>
            </div>
            <div style="display:flex; align-items:center; gap:4px;">
              <button @click="kurangQty(i)" style="width:28px; height:28px; padding:0; background:#f1f5f9;">-</button>
              <input
                type="number"
                v-model.number="item.qty"
                :min="1"
                :max="item.stok"
                @change="validasiQty(i)"
                style="width:48px; text-align:center; padding:4px; margin-bottom:0; font-size:14px;"
              />
              <button @click="tambahQty(i)" style="width:28px; height:28px; padding:0; background:#f1f5f9;">+</button>
            </div>
            <span style="font-size:14px; font-weight:600; min-width:70px; text-align:right;">{{ formatRupiah(item.harga * item.qty) }}</span>
            <button @click="hapusItem(i)" style="background:none; color:#ef4444; font-size:18px; padding:0; width:24px;">✕</button>
          </div>

          <div v-if="keranjang.length > 0">
            <div style="margin-top:12px; padding-top:12px; border-top:2px solid #e2e8f0;">
              <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:700; margin-bottom:12px;">
                <span>Total</span>
                <span>{{ formatRupiah(total) }}</span>
              </div>
              <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Uang Bayar</label>
              <input v-model.number="bayar" type="number" placeholder="Masukkan nominal" style="font-size:16px;" />
              <div v-if="bayar > 0 && bayar >= total" style="display:flex; justify-content:space-between; background:#dcfce7; padding:8px 12px; border-radius:6px; margin-bottom:12px;">
                <span style="font-size:14px;">Kembalian</span>
                <span style="font-weight:700; color:#16a34a;">{{ formatRupiah(bayar - total) }}</span>
              </div>
              <div v-if="bayar > 0 && bayar < total" style="background:#fee2e2; padding:8px 12px; border-radius:6px; margin-bottom:12px; font-size:13px; color:#dc2626;">
                Kurang {{ formatRupiah(total - bayar) }}
              </div>
            </div>

            <p v-if="error" style="color:#dc2626; font-size:13px;">{{ error }}</p>
            <div v-if="sukses" style="background:#dcfce7; padding:10px; border-radius:6px; color:#16a34a; font-size:13px; margin-bottom:8px;">{{ sukses }}</div>

            <button
              @click="checkout"
              :disabled="loading || keranjang.length === 0 || bayar < total"
              style="width:100%; background:#2563eb; color:white; padding:12px; font-size:15px; font-weight:600;"
            >
              {{ loading ? 'Memproses...' : 'Bayar / Checkout' }}
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- Tab 2: Pesanan Masuk -->
    <div v-if="activeTab === 'pesanan'">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <h2 style="margin:0;">Pesanan Masuk</h2>
        <div style="display:flex; gap:8px; align-items:center;">
          <select v-model="filterTipe" @change="fetchOrders" style="margin-bottom:0; width:140px;">
            <option value="">Semua</option>
            <option value="dine_in">Meja</option>
            <option value="takeaway">Bawa Pulang</option>
          </select>
          <select v-model="filterStatus" @change="fetchOrders" style="margin-bottom:0; width:130px;">
            <option value="">Semua Status</option>
            <option value="pending">Pending</option>
            <option value="proses">Proses</option>
            <option value="selesai">Selesai</option>
          </select>
          <button @click="fetchOrders" style="padding:8px 14px; background:#f1f5f9; color:#334155; font-size:13px;">↺ Refresh</button>
        </div>
      </div>

      <div v-if="orders.length === 0" style="text-align:center; padding:40px; color:#94a3b8;">
        Tidak ada pesanan
      </div>

      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:16px;">
        <div
          v-for="o in orders" :key="o.id"
          style="background:white; border-radius:10px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.08);"
          :style="`border-left:4px solid ${statusColor(o.status)};`"
        >
          <!-- Header card -->
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
            <div>
              <p style="margin:0; font-size:20px; font-weight:900; color:#1e293b;">{{ o.nomor_antrian }}</p>
              <p style="margin:2px 0 0; font-size:13px; color:#64748b;">{{ o.customer?.nama }} · {{ o.tipe === 'dine_in' ? '🪑 Meja ' + o.nomor_meja : '🛍️ Bawa Pulang' }}</p>
            </div>
            <span :style="`font-size:12px; font-weight:700; padding:4px 10px; border-radius:999px; background:${statusBg(o.status)}; color:${statusColor(o.status)};`">
              {{ statusLabel(o.status) }}
            </span>
          </div>

          <!-- Items -->
          <div style="border-top:1px solid #f1f5f9; padding-top:8px; margin-bottom:10px;">
            <div v-for="item in o.items" :key="item.id" style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
              <span>{{ item.produk?.nama }} × {{ item.qty }}</span>
              <span style="color:#64748b;">{{ formatRupiah(item.subtotal) }}</span>
            </div>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <span style="font-size:14px; font-weight:700;">Total: {{ formatRupiah(o.total) }}</span>
            <span style="font-size:11px; color:#94a3b8;">{{ formatTime(o.created_at) }}</span>
          </div>

          <p v-if="o.catatan" style="margin:0 0 10px; font-size:12px; color:#64748b; background:#f8fafc; padding:6px 8px; border-radius:6px;">
            Catatan: {{ o.catatan }}
          </p>

          <!-- Actions -->
          <div style="display:flex; gap:8px;">
            <button
              v-if="o.status === 'pending'"
              @click="updateStatus(o.id, 'proses')"
              style="flex:1; background:#f59e0b; color:white; padding:8px; font-size:13px; font-weight:600;"
            >Proses</button>
            <button
              v-if="o.status === 'proses'"
              @click="selesaikanDanPanggil(o)"
              style="flex:1; background:#16a34a; color:white; padding:8px; font-size:13px; font-weight:600;"
            >Selesai ✓</button>
            <button
              v-if="o.status === 'selesai'"
              disabled
              style="flex:1; background:#dcfce7; color:#16a34a; padding:8px; font-size:13px; font-weight:600; cursor:default;"
            >Selesai ✓</button>
            <button
              v-if="o.status === 'selesai'"
              @click="panggil(o.nomor_antrian)"
              style="background:#2563eb; color:white; padding:8px 12px; font-size:13px; font-weight:600; border-radius:6px;"
            >📢 Panggil Lagi</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

// ── Tab state ──────────────────────────────────────────────
const activeTab = ref('manual')

// ── Tab 1: Manual kasir ───────────────────────────────────
const produk         = ref([])
const keranjang      = ref([])
const search         = ref('')
const filterKategori = ref('')
const bayar          = ref(0)
const error          = ref('')
const sukses         = ref('')
const loading        = ref(false)

onMounted(() => fetchProduk())

async function fetchProduk() {
  const res = await api.get('/produk', {
    params: { search: search.value, kategori: filterKategori.value || undefined }
  })
  produk.value = res.data.data
}

function tambahKeranjang(p) {
  if (p.stok === 0) return
  const ada = keranjang.value.find(k => k.produk_id === p.id)
  if (ada) {
    if (ada.qty < p.stok) ada.qty++
  } else {
    keranjang.value.push({ produk_id: p.id, nama: p.nama, harga: p.harga, stok: p.stok, qty: 1 })
  }
}

function tambahQty(i) {
  const item = keranjang.value[i]
  if (item.qty < item.stok) item.qty++
}

function validasiQty(i) {
  const item = keranjang.value[i]
  if (!item.qty || item.qty < 1) item.qty = 1
  if (item.qty > item.stok) item.qty = item.stok
}

function kurangQty(i) {
  if (keranjang.value[i].qty > 1) {
    keranjang.value[i].qty--
  } else {
    hapusItem(i)
  }
}

function hapusItem(i) { keranjang.value.splice(i, 1) }

const total = computed(() => keranjang.value.reduce((sum, k) => sum + k.harga * k.qty, 0))

async function checkout() {
  error.value  = ''
  sukses.value = ''
  loading.value = true
  try {
    await api.post('/transaksi', {
      bayar: bayar.value,
      items: keranjang.value.map(k => ({ produk_id: k.produk_id, qty: k.qty })),
    })
    sukses.value = `Transaksi berhasil! Kembalian: ${formatRupiah(bayar.value - total.value)}`
    keranjang.value = []
    bayar.value = 0
    fetchProduk()
    setTimeout(() => { sukses.value = '' }, 4000)
  } catch (err) {
    error.value = err.response?.data?.message || 'Transaksi gagal'
  } finally {
    loading.value = false
  }
}

// ── Tab 2: Pesanan Masuk ──────────────────────────────────
const orders      = ref([])
const filterTipe  = ref('')
const filterStatus = ref('')

const pendingCount = computed(() => orders.value.filter(o => o.status === 'pending').length)

async function fetchOrders() {
  const res = await api.get('/orders', {
    params: {
      tipe: filterTipe.value || undefined,
      status: filterStatus.value || undefined,
    }
  })
  orders.value = res.data
}

async function updateStatus(id, status) {
  await api.patch(`/orders/${id}/status`, { status })
  fetchOrders()
}

async function selesaikanDanPanggil(o) {
  await api.patch(`/orders/${o.id}/status`, { status: 'selesai' })
  fetchOrders()
  panggil(o.nomor_antrian)
}

function panggil(nomorAntrian) {
  if (!window.speechSynthesis) return
  window.speechSynthesis.cancel()
  const utter = new SpeechSynthesisUtterance(
    `Nomor antrian ${nomorAntrian}, silakan menuju kasir`
  )
  utter.lang = 'id-ID'
  utter.rate = 0.9
  window.speechSynthesis.speak(utter)
}

function statusLabel(s) {
  return s === 'pending' ? 'Pending' : s === 'proses' ? 'Diproses' : 'Selesai'
}

function statusColor(s) {
  return s === 'pending' ? '#dc2626' : s === 'proses' ? '#d97706' : '#16a34a'
}

function statusBg(s) {
  return s === 'pending' ? '#fee2e2' : s === 'proses' ? '#fef3c7' : '#dcfce7'
}

function formatTime(dt) {
  return new Date(dt).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

// ── Shared ─────────────────────────────────────────────────
function formatRupiah(n) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}
</script>
