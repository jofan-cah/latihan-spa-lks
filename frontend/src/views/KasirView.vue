<template>
  <div>
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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

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

function formatRupiah(n) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}
</script>
