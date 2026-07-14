import axios from 'axios';

// Create axios instance with base configuration
const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:5000/api',
  headers: {
    'Content-Type': 'application/json'
  },
  withCredentials: true
});

// Request interceptor - Add JWT token to requests
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle token expiration
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Authentication APIs
export const authAPI = {
  register: (data) => api.post('/auth/register', data),
  login: (data) => api.post('/auth/login', data),
  logout: () => api.post('/auth/logout'),
  getMe: () => api.get('/auth/me'),
  updateProfile: (data) => api.put('/auth/profile', data),
  changePassword: (data) => api.put('/auth/change-password', data)
};

// Appointment APIs
export const appointmentAPI = {
  getAll: (params) => api.get('/appointments', { params }),
  create: (data) => api.post('/appointments', data),
  getById: (id) => api.get(`/appointments/${id}`),
  update: (id, data) => api.put(`/appointments/${id}`, data),
  delete: (id) => api.delete(`/appointments/${id}`),
  claim: (id) => api.put(`/appointments/${id}/claim`),
  getStats: () => api.get('/appointments/stats/summary')
};

// Patient APIs
export const patientAPI = {
  admit: (data) => api.post('/patients/admit', data),
  getAll: (params) => api.get('/patients', { params }),
  getById: (id) => api.get(`/patients/${id}`),
  update: (id, data) => api.put(`/patients/${id}`, data),
  discharge: (id, data) => api.put(`/patients/${id}/discharge`, data),
  getHistory: (id) => api.get(`/patients/${id}/history`),
  getStats: () => api.get('/patients/stats/summary')
};

// Bed APIs
export const bedAPI = {
  getAll: (params) => api.get('/beds', { params }),
  create: (data) => api.post('/beds', data),
  getById: (id) => api.get(`/beds/${id}`),
  update: (id, data) => api.put(`/beds/${id}`, data),
  delete: (id) => api.delete(`/beds/${id}`),
  assign: (id, data) => api.put(`/beds/${id}/assign`, data),
  release: (id) => api.put(`/beds/${id}/release`),
  getStats: () => api.get('/beds/stats/occupancy')
};

// Doctor APIs
export const doctorAPI = {
  getAll: (params) => api.get('/doctors', { params }),
  getById: (id) => api.get(`/doctors/${id}`),
  getAppointments: (id, params) => api.get(`/doctors/${id}/appointments`, { params })
};

// Staff APIs
export const staffAPI = {
  getAll: () => api.get('/staff'),
  getById: (id) => api.get(`/staff/${id}`)
};

// QR APIs
export const qrAPI = {
  generate: (data) => api.post('/qr/generate', data),
  scan: (data) => api.post('/qr/scan', data),
  verify: (data) => api.post('/qr/verify', data),
  getScanHistory: (admissionId) => api.get(`/qr/scans/${admissionId}`)
};

// Chatbot APIs
export const chatbotAPI = {
  sendMessage: (data) => api.post('/chatbot/message', data),
  getHistory: () => api.get('/chatbot/history')
};

// Admin APIs
export const adminAPI = {
  getStats: () => api.get('/admin/stats'),
  getUsers: (params) => api.get('/admin/users', { params }),
  updateUser: (id, data) => api.put(`/admin/users/${id}`, data),
  deleteUser: (id) => api.delete(`/admin/users/${id}`),
  getLogs: (params) => api.get('/admin/logs', { params })
};

export default api;
