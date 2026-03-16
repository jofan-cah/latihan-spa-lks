<template>
  <div style="min-height:100vh; background:#f0fdf4; display:flex; align-items:center; justify-content:center; padding:20px;">
    <div style="text-align:center; max-width:400px; width:100%;">
      <div style="width:64px; height:64px; border-radius:50%; background:#16a34a; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <h1 style="margin:0 0 8px; font-size:22px; color:#15803d;">Pesanan Berhasil!</h1>
      <p style="margin:0 0 32px; color:#64748b;">Terima kasih, <strong>{{ nama }}</strong>! Pesananmu sedang diproses.</p>

      <div style="background:white; border-radius:16px; padding:32px; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:24px;">
        <p style="margin:0 0 8px; font-size:14px; color:#64748b;">Nomor Antrian Kamu</p>
        <div style="font-size:72px; font-weight:900; color:#16a34a; letter-spacing:4px; line-height:1;">{{ antrian }}</div>
        <p style="margin:16px 0 0; font-size:13px; color:#94a3b8;">Tunggu dipanggil di layar antrian</p>
      </div>

      <button @click="$router.push('/order')" style="width:100%; background:#2563eb; color:white; padding:14px; font-size:15px; font-weight:600; border-radius:10px;">
        Pesan Lagi
      </button>
    </div>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route   = useRoute()
const antrian = route.query.antrian || '—'
const nama    = route.query.nama    || 'Customer'
</script>
