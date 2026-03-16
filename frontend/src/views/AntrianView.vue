<template>
  <div style="min-height:100vh; background:#0f172a; color:white; padding:24px; font-family:sans-serif;">
    <div style="text-align:center; margin-bottom:32px;">
      <h1 style="margin:0; font-size:28px; letter-spacing:2px; color:#94a3b8;">NOMOR ANTRIAN</h1>
      <p style="margin:4px 0 0; font-size:14px; color:#475569;">Auto refresh setiap 5 detik</p>
    </div>

    <div v-if="antrian.length === 0" style="text-align:center; color:#475569; font-size:18px; margin-top:60px;">
      Belum ada pesanan yang diproses
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:16px; max-width:1000px; margin:0 auto;">
      <div
        v-for="o in antrian" :key="o.id"
        :class="{ 'baru-dipanggil': baruDipanggil.has(o.id) }"
        :style="`border-radius:16px; padding:24px; text-align:center;
          background:${baruDipanggil.has(o.id) ? '#1e3a5f' : o.status === 'selesai' ? '#14532d' : '#713f12'};
          border:2px solid ${baruDipanggil.has(o.id) ? '#3b82f6' : o.status === 'selesai' ? '#16a34a' : '#ca8a04'};
          transition:all .4s;`"
      >
        <p style="margin:0 0 4px; font-size:12px; letter-spacing:1px; opacity:0.7;">
          {{ baruDipanggil.has(o.id) ? '📢 DIPANGGIL!' : o.status === 'selesai' ? 'SELESAI ✓' : 'DIPROSES...' }}
        </p>
        <div :style="`font-size:56px; font-weight:900; letter-spacing:2px;
          color:${baruDipanggil.has(o.id) ? '#60a5fa' : o.status === 'selesai' ? '#4ade80' : '#fde047'};`">
          {{ o.nomor_antrian }}
        </div>
        <p style="margin:8px 0 4px; font-size:13px; opacity:0.7;">
          {{ o.tipe === 'dine_in' ? '🪑 Meja ' + o.nomor_meja : '🛍️ Bawa Pulang' }}
        </p>
        <p style="margin:0; font-size:12px; opacity:0.6;">{{ o.customer?.nama }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const BASE_URL    = import.meta.env.VITE_API_URL
const antrian     = ref([])
const baruDipanggil = ref(new Set())
let interval = null

async function fetchAntrian() {
  const res  = await axios.get(`${BASE_URL}/orders/antrian`)
  const baru = res.data

  // Deteksi nomor yang baru berubah jadi 'selesai'
  const idLamaSelesai = new Set(antrian.value.filter(o => o.status === 'selesai').map(o => o.id))
  baru.forEach(o => {
    if (o.status === 'selesai' && !idLamaSelesai.has(o.id)) {
      baruDipanggil.value = new Set([...baruDipanggil.value, o.id])
      setTimeout(() => {
        baruDipanggil.value = new Set([...baruDipanggil.value].filter(id => id !== o.id))
      }, 5000)
    }
  })

  antrian.value = baru
}

onMounted(() => {
  fetchAntrian()
  interval = setInterval(fetchAntrian, 5000)
})

onUnmounted(() => clearInterval(interval))
</script>

<style scoped>
.baru-dipanggil {
  animation: pulse 0.6s ease-in-out infinite alternate;
}
@keyframes pulse {
  from { box-shadow: 0 0 0px #3b82f6; }
  to   { box-shadow: 0 0 24px #3b82f6; }
}
</style>
