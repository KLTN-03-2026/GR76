<template>
  <div ref="mapEl" class="map-container w-full h-full" style="min-height: 400px;" />
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'

const props = defineProps({
  incidents: { type: Array, default: () => [] },
  center:    { type: Array, default: () => [10.7769, 106.7009] },
  zoom:      { type: Number, default: 13 },
  clickable: { type: Boolean, default: true }
})

const emit = defineEmits(['map-click', 'marker-click'])

const mapEl   = ref(null)
let   mapInst = null
let   markers = []

// ── Urgency → visual config ───────────────────────────────────────
// Laravel JSON serializes relations as snake_case: muc_do_khan_cap (not mucDoKhanCap)
function getUrgencyLevel(incident) {
  const priority = incident.muc_do_khan_cap?.do_uu_tien
                ?? incident.muc_do?.do_uu_tien
                ?? null

  if (priority !== null && priority !== undefined) {
    if (priority >= 3) return 'critical'
    if (priority >= 2) return 'high'
    if (priority >= 1) return 'medium'
    return 'low'
  }

  // Fallback name-based
  const name = (
    incident.muc_do_khan_cap?.ten_muc_do
    ?? incident.muc_do?.ten_muc_do
    ?? incident.muc_do?.ten
    ?? ''
  ).toLowerCase()

  if (name.includes('khẩn') || name.includes('nguy') || name.includes('cao') || name.includes('critical')) return 'critical'
  if (name.includes('vừa') || name.includes('trung') || name.includes('high')) return 'high'
  if (name.includes('thấp') || name.includes('nhẹ') || name.includes('low')) return 'low'
  return 'medium'
}

const URGENCY = {
  critical: {
    color: '#dc2626',       // red-600
    ring:  '#fca5a5',       // red-300
    size:  20,
    ringSize: 36,
    speed: '1.2s',
    border: '#991b1b',
    label: 'Khẩn cấp'
  },
  high: {
    color: '#ea580c',       // orange-600
    ring:  '#fdba74',       // orange-300
    size:  17,
    ringSize: 30,
    speed: '1.8s',
    border: '#c2410c',
    label: 'Cao'
  },
  medium: {
    color: '#d97706',       // amber-600
    ring:  '#fde68a',       // amber-200
    size:  14,
    ringSize: 24,
    speed: '2.5s',
    border: '#b45309',
    label: 'Trung bình'
  },
  low: {
    color: '#16a34a',       // green-600
    ring:  '#86efac',       // green-300
    size:  12,
    ringSize: 20,
    speed: '3.5s',
    border: '#15803d',
    label: 'Thấp'
  }
}

function createIcon(level) {
  const cfg = URGENCY[level] ?? URGENCY.medium
  const half    = cfg.size / 2
  const halfRing = cfg.ringSize / 2
  const totalSize = cfg.ringSize + 4

  const html = `
    <div style="position:relative;width:${totalSize}px;height:${totalSize}px;display:flex;align-items:center;justify-content:center;">
      <!-- Outer pulse ring -->
      <div style="
        position:absolute;
        width:${cfg.ringSize}px; height:${cfg.ringSize}px;
        border-radius:50%;
        background:${cfg.ring};
        animation:sosPulse ${cfg.speed} ease-out infinite;
        opacity:0.7;
      "></div>
      <!-- Middle ring (second wave for critical/high) -->
      ${level === 'critical' || level === 'high' ? `
      <div style="
        position:absolute;
        width:${cfg.ringSize - 8}px; height:${cfg.ringSize - 8}px;
        border-radius:50%;
        background:${cfg.ring};
        animation:sosPulse ${cfg.speed} ease-out ${parseFloat(cfg.speed) * 0.4}s infinite;
        opacity:0.5;
      "></div>` : ''}
      <!-- Core dot -->
      <div style="
        position:relative;z-index:2;
        width:${cfg.size}px; height:${cfg.size}px;
        border-radius:50%;
        background:${cfg.color};
        border:2.5px solid ${cfg.border};
        box-shadow:0 2px 8px ${cfg.color}88, 0 0 0 1px white;
      "></div>
    </div>
  `

  return L.divIcon({
    html,
    className: '',
    iconSize:   [totalSize, totalSize],
    iconAnchor: [totalSize / 2, totalSize / 2],
    popupAnchor:[0, -(totalSize / 2 + 4)]
  })
}

function clearMarkers() {
  markers.forEach(m => m.remove())
  markers = []
}

function plotIncidents() {
  clearMarkers()
  props.incidents.forEach(inc => {
    const lat = parseFloat(inc.vi_do ?? inc.lat)
    const lng = parseFloat(inc.kinh_do ?? inc.lng)
    if (isNaN(lat) || isNaN(lng)) return

    const level  = getUrgencyLevel(inc)
    const cfg    = URGENCY[level]
    const title  = inc.tieu_de || 'Sự cố'
    const status = inc.trang_thai || ''
    const addr   = inc.dia_chi || ''
    const mucDo  = inc.muc_do_khan_cap?.ten_muc_do ?? inc.muc_do?.ten_muc_do ?? cfg.label

    const imgHtml = inc.hinh_anh
      ? `<img src="${inc.hinh_anh}" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px;" />`
      : ''

    const marker = L.marker([lat, lng], { icon: createIcon(level) })
      .bindPopup(`
        <div style="min-width:210px;max-width:240px;font-family:'Inter',system-ui,sans-serif;line-height:1.5">
          ${imgHtml}
          <p style="font-weight:700;font-size:14px;margin:0 0 3px">${title}</p>
          <p style="font-size:12px;color:#6b7280;margin:0 0 6px">📍 ${addr || 'Không có địa chỉ'}</p>
          <div style="display:flex;gap:5px;flex-wrap:wrap">
            <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;
              background:${cfg.color}22;color:${cfg.border};border:1px solid ${cfg.color}44">⚡ ${mucDo}</span>
            <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;
              background:#f3f4f6;color:#374151">${status}</span>
          </div>
        </div>
      `, { maxWidth: 260 })
      .addTo(mapInst)

    marker.on('click', () => emit('marker-click', inc))
    markers.push(marker)
  })
}

// Inject CSS animation once
function injectPulseCSS() {
  if (document.getElementById('sos-pulse-style')) return
  const style = document.createElement('style')
  style.id = 'sos-pulse-style'
  style.textContent = `
    @keyframes sosPulse {
      0%   { transform: scale(0.6); opacity: 0.8; }
      70%  { transform: scale(1.4); opacity: 0.15; }
      100% { transform: scale(0.6); opacity: 0; }
    }
  `
  document.head.appendChild(style)
}

onMounted(() => {
  injectPulseCSS()

  mapInst = L.map(mapEl.value, {
    center: props.center,
    zoom:   props.zoom,
    zoomControl: true
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(mapInst)

  if (props.clickable) {
    mapInst.on('click', e => emit('map-click', { lat: e.latlng.lat, lng: e.latlng.lng }))
  }

  plotIncidents()
})

onUnmounted(() => {
  if (mapInst) mapInst.remove()
})

watch(() => props.incidents, plotIncidents, { deep: true })

// ── Exposed API for parent to control map ────────────────────────
let userLocationMarker = null

function showUserLocation(lat, lng) {
  if (userLocationMarker) userLocationMarker.remove()

  const html = `
    <div style="position:relative;width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
      <div style="
        position:absolute;width:32px;height:32px;border-radius:50%;
        background:rgba(66,133,244,0.25);
        animation:sosPulse 2s ease-out infinite;
      "></div>
      <div style="
        position:relative;z-index:2;
        width:14px;height:14px;border-radius:50%;
        background:#4285F4;
        border:3px solid white;
        box-shadow:0 2px 8px rgba(66,133,244,0.6);
      "></div>
    </div>`

  userLocationMarker = L.marker([lat, lng], {
    icon: L.divIcon({ html, className: '', iconSize: [32, 32], iconAnchor: [16, 16] }),
    zIndexOffset: 1000
  }).addTo(mapInst)
  userLocationMarker.bindPopup('<b>📍 Vị trí của bạn</b>').openPopup()
}

function setCenter(lat, lng, zoom = 15) {
  if (mapInst) {
    mapInst.flyTo([lat, lng], zoom, { animate: true, duration: 0.8 })
    showUserLocation(lat, lng)
  }
}

function flyToIncident(lat, lng) {
  if (mapInst) mapInst.flyTo([lat, lng], 16, { animate: true, duration: 0.6 })
}

defineExpose({ setCenter, showUserLocation, flyToIncident })
</script>
