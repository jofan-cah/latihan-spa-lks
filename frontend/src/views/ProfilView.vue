<template>
  <div>
    <h2 style="margin-top:0;">Profil Saya</h2>

    <!-- Info Akun -->
    <div style="display:grid; grid-template-columns:auto 1fr; gap:24px; background:white; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:24px; align-items:center;">
      <div style="width:64px; height:64px; border-radius:50%; background:#eff6ff; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:#2563eb;">
        {{ user.name?.charAt(0).toUpperCase() }}
      </div>
      <div>
        <h3 style="margin:0 0 4px; font-size:18px;">{{ user.name }}</h3>
        <p style="margin:0 0 2px; font-size:13px; color:#64748b;">{{ user.email }}</p>
        <span :style="`font-size:11px; font-weight:700; padding:2px 10px; border-radius:999px;
          background:${user.role === 'admin' ? '#fef3c7' : '#eff6ff'};
          color:${user.role === 'admin' ? '#d97706' : '#2563eb'};
          text-transform:uppercase; letter-spacing:.5px;`">
          {{ user.role }}
        </span>
      </div>
    </div>

    <!-- Stats -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px;">
      <div style="background:white; padding:20px; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.08); border-left:4px solid #2563eb;">
        <p style="color:#64748b; font-size:12px; margin:0 0 6px; text-transform:uppercase; letter-spacing:.5px;">Total Transaksi</p>
        <h3 style="margin:0; font-size:28px; color:#0f172a;">{{ riwayat.length }}</h3>
      </div>
      <div style="background:white; padding:20px; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.08); border-left:4px solid #16a34a;">
        <p style="color:#64748b; font-size:12px; margin:0 0 6px; text-transform:uppercase; letter-spacing:.5px;">Total Pendapatan</p>
        <h3 style="margin:0; font-size:20px; color:#0f172a;">{{ formatRupiah(totalPendapatan) }}</h3>
      </div>
      <div style="background:white; padding:20px; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.08); border-left:4px solid #f59e0b;">
        <p style="color:#64748b; font-size:12px; margin:0 0 6px; text-transform:uppercase; letter-spacing:.5px;">Hari Ini</p>
        <h3 style="margin:0; font-size:28px; color:#0f172a;">{{ transaksiHariIni }}</h3>
      </div>
    </div>

    <!-- Riwayat Transaksi -->
    <div style="background:white; border-radius:10px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08);">
      <div style="padding:16px 20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:15px;">Riwayat Transaksi Saya</h3>
        <span style="font-size:12px; color:#94a3b8;">{{ riwayat.length }} transaksi</span>
      </div>
      <table style="width:100%;">
        <thead>
          <tr>
            <th>Waktu</th>
            <th>Item</th>
            <th>Total</th>
            <th>Bayar</th>
            <th>Kembalian</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in riwayat" :key="t.id">
            <td style="white-space:nowrap;">{{ formatDate(t.created_at) }}</td>
            <td style="color:#64748b; font-size:13px;">{{ t.detail?.length ?? 0 }} item</td>
            <td style="font-weight:600;">{{ formatRupiah(t.total) }}</td>
            <td>{{ formatRupiah(t.bayar) }}</td>
            <td style="color:#16a34a; font-weight:600;">{{ formatRupiah(t.kembalian) }}</td>
          </tr>
          <tr v-if="riwayat.length === 0">
            <td colspan="5" style="text-align:center; color:#94a3b8; padding:32px;">Belum ada transaksi</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

const user     = ref(JSON.parse(localStorage.getItem('user') || '{}'))
const riwayat  = ref([])

onMounted(async () => {
  const res = await api.get('/transaksi')
  // Filter hanya transaksi milik user ini
  riwayat.value = res.data.data.filter(t => t.user_id === user.value.id)
})

const today = new Date().toDateString()

const totalPendapatan  = computed(() => riwayat.value.reduce((s, t) => s + Number(t.total), 0))
const transaksiHariIni = computed(() => riwayat.value.filter(t => new Date(t.created_at).toDateString() === today).length)

function formatRupiah(n) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}

function formatDate(d) {
  return new Date(d).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })
}
</script>
