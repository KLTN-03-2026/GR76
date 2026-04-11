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
    color: '#dc2626', ring: '#fca5a5', border: '#991b1b',
    size: 20, ringSize: 36, speed: '1.2s', label: 'Khẩn cấp',
    svg: `<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`
  },
  high: {
    color: '#ea580c', ring: '#fdba74', border: '#c2410c',
    size: 18, ringSize: 32, speed: '1.8s', label: 'Cao',
    svg: `<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.07-2.14 0-5.5 3-7.5.5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.15.5-2.26 1.5-3.5z"/></svg>`
  },
  medium: {
    color: '#d97706', ring: '#fde68a', border: '#b45309',
    size: 16, ringSize: 28, speed: '2.5s', label: 'Trung bình',
    svg: `<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
  },
  low: {
    color: '#16a34a', ring: '#86efac', border: '#15803d',
    size: 14, ringSize: 24, speed: '3.5s', label: 'Thấp',
    svg: `<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`
  }
}

function createIcon(level) {
  const cfg = URGENCY[level] ?? URGENCY.medium
  const totalSize = cfg.ringSize + 8
  const iconPad = 4
  const iconSz = cfg.size - iconPad

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
      ${level === 'critical' || level === 'high' ? `
      <div style="
        position:absolute;
        width:${cfg.ringSize - 8}px; height:${cfg.ringSize - 8}px;
        border-radius:50%;
        background:${cfg.ring};
        animation:sosPulse ${cfg.speed} ease-out ${parseFloat(cfg.speed) * 0.4}s infinite;
        opacity:0.5;
      "></div>` : ''}
      <!-- Core marker with SVG icon -->
      <div style="
        position:relative;z-index:2;
        width:${cfg.size}px; height:${cfg.size}px;
        border-radius:${level === 'critical' ? '4px' : '50%'};
        background:${cfg.color};
        border:2.5px solid ${cfg.border};
        box-shadow:0 2px 8px ${cfg.color}88, 0 0 0 1px white;
        display:flex;align-items:center;justify-content:center;
      ">
        <div style="width:${iconSz}px;height:${iconSz}px;">${cfg.svg}</div>
      </div>
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

// ── Status display mapping ──────────────────────────────────────
const STATUS_LABELS = {
  pending: 'Chờ xử lý',
  in_progress: 'Đang xử lý',
  resolved: 'Đã giải quyết',
  rejected: 'Từ chối'
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
    const status = STATUS_LABELS[inc.trang_thai] || inc.trang_thai || ''
    const addr   = inc.dia_chi || ''
    const mucDo  = inc.muc_do_khan_cap?.ten_muc_do ?? inc.muc_do?.ten_muc_do ?? cfg.label

    const imgHtml = inc.hinh_anh
      ? `<img src="${inc.hinh_anh}" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px;" />`
      : `<div style="width:100%;height:80px;border-radius:8px;margin-bottom:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px;">📷 Chưa có hình ảnh</div>`

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

  // CartoDB Voyager — no nine-dash line
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 20
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
