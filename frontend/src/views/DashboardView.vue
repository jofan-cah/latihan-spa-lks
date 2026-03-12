<template>
  <div>
    <h2 style="margin-top:0;">Dashboard</h2>
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
      <div style="background:white; padding:20px; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <p style="color:#64748b; font-size:13px; margin:0 0 8px;">Total Transaksi Hari Ini</p>
        <h3 style="margin:0; font-size:28px;">{{ stats.transaksiHariIni }}</h3>
      </div>
      <div style="background:white; padding:20px; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <p style="color:#64748b; font-size:13px; margin:0 0 8px;">Pendapatan Hari Ini</p>
        <h3 style="margin:0; font-size:24px;">{{ formatRupiah(stats.pendapatanHariIni) }}</h3>
      </div>
      <div style="background:white; padding:20px; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <p style="color:#64748b; font-size:13px; margin:0 0 8px;">Total Produk</p>
        <h3 style="margin:0; font-size:28px;">{{ stats.totalProduk }}</h3>
      </div>
    </div>

    <h3 style="margin-top:32px;">Transaksi Terbaru</h3>
    <div style="background:white; border-radius:10px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08);">
      <table style="width:100%;">
        <thead>
          <tr>
            <th>Waktu</th>
            <th>Kasir</th>
            <th>Total</th>
            <th>Bayar</th>
            <th>Kembalian</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in transaksi" :key="t.id">
            <td>{{ formatDate(t.created_at) }}</td>
            <td>{{ t.kasir?.name }}</td>
            <td>{{ formatRupiah(t.total) }}</td>
            <td>{{ formatRupiah(t.bayar) }}</td>
            <td>{{ formatRupiah(t.kembalian) }}</td>
          </tr>
          <tr v-if="transaksi.length === 0">
            <td colspan="5" style="text-align:center; color:#94a3b8;">Belum ada transaksi</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/api/axios'

const transaksi = ref([])

onMounted(async () => {
  const res = await api.get('/transaksi')
  transaksi.value = res.data.data
})

const today = new Date().toDateString()

const stats = computed(() => {
  const hariIni = transaksi.value.filter(t => new Date(t.created_at).toDateString() === today)
  return {
    transaksiHariIni:  hariIni.length,
    pendapatanHariIni: hariIni.reduce((sum, t) => sum + Number(t.total), 0),
    totalProduk:       0,
  }
})

function formatRupiah(n) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}

function formatDate(d) {
  return new Date(d).toLocaleString('id-ID')
}
</script>
