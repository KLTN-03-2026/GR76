import axios from 'axios'
import router from '@/router'

const api = axios.create({
  baseURL: '/api',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
})

// Attach token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('sos_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Handle 401
api.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      localStorage.removeItem('sos_token')
      localStorage.removeItem('sos_user')
      router.push('/login')
    }
    return Promise.reject(err)
  }
)

export default api

// ── Auth ─────────────────────────────────────────────────────────
export const authApi = {
  login:           (data) => api.post('/login', data),
  loginAdmin:      (data) => api.post('/admin/login', data),
  register:        (data) => api.post('/register', data),
  logout:          ()     => api.post('/logout'),
  me:              ()     => api.get('/me'),
  updateProfile:   (d)    => api.put('/me', d),
  changePassword:  (d)    => api.patch('/me/password', d),
  // Password reset
  forgotPassword:  (d)    => api.post('/forgot-password', d),
  resetPassword:   (d)    => api.post('/reset-password', d),
  // Email verification
  sendVerifyEmail: (d)    => api.post('/send-verify-email', d),
  verifyEmail:     (d)    => api.post('/verify-email', d),
}

// ── Incidents (User) ─────────────────────────────────────────────
export const incidentApi = {
  mapIncidents:  ()          => api.get('/su-co/map'),
  myIncidents:   ()          => api.get('/my-su-co'),
  store:         (data)      => api.post('/su-co', data),
  show:          (id)        => api.get(`/su-co/${id}`),
  update:        (id, data)  => api.patch(`/su-co/${id}`, data),
  tiepNhan:      (id)        => api.patch(`/su-co/${id}/tiep-nhan`),
  publicMap:     ()          => api.get('/public/map'),
  publicSearch:  (params)    => api.get('/public/su-co', { params })
}

// ── Notifications (User) ─────────────────────────────────────────
export const notificationApi = {
  list:   ()   => api.get('/notifications'),
  read:   (id) => api.patch(`/notifications/${id}/read`)
}

// ── Admin ─────────────────────────────────────────────────────────
export const adminApi = {
  // Dashboard
  dashboard: () => api.get('/admin/dashboard'),

  // Incidents
  incidents:     (params)       => api.get('/admin/su-co', { params }),
  incidentShow:  (id)           => api.get(`/admin/su-co/${id}`),
  incidentUpdate:(id, data)     => api.patch(`/admin/su-co/${id}`, data),
  incidentDelete:(id)           => api.delete(`/admin/su-co/${id}`),
  incidentStatus:(id, status)   => api.patch(`/admin/su-co/${id}/status`, { trang_thai: status }),
  incidentSearch:(q)            => api.get('/admin/su-co/search', { params: { q } }),

  // Users
  users:              ()        => api.get('/admin/users'),
  userShow:           (id)      => api.get(`/admin/users/${id}`),
  userCreate:         (data)    => api.post('/admin/users', data),
  userUpdate:         (id, d)   => api.patch(`/admin/users/${id}`, d),
  userDelete:         (id)      => api.delete(`/admin/users/${id}`),
  userSearch:         (q)       => api.get('/admin/users/search', { params: { q } }),
  userLock:           (id)      => api.patch(`/admin/users/${id}/lock`),
  userUnlock:         (id)      => api.patch(`/admin/users/${id}/unlock`),
  userChangePassword: (id, d)   => api.patch(`/admin/users/${id}/password`, d),

  // Categories (Loại sự cố)
  categories:       ()           => api.get('/admin/loai-su-co'),
  categoryCreate:   (data)       => api.post('/admin/loai-su-co', data),
  categoryUpdate:   (id, data)   => api.patch(`/admin/loai-su-co/${id}`, data),
  categoryDelete:   (id)         => api.delete(`/admin/loai-su-co/${id}`),

  // Levels (Mức độ khẩn cấp)
  levels:       ()               => api.get('/admin/muc-do'),
  levelCreate:  (data)           => api.post('/admin/muc-do', data),
  levelUpdate:  (id, data)       => api.patch(`/admin/muc-do/${id}`, data),
  levelDelete:  (id)             => api.delete(`/admin/muc-do/${id}`),

  // Notifications (Admin)
  notifications:       ()        => api.get('/admin/notifications'),
  sendNotification:    (data)    => api.post('/admin/notifications', data),
}
