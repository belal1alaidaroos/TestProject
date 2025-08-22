import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add language header
    const language = localStorage.getItem('language-storage') 
      ? JSON.parse(localStorage.getItem('language-storage')!).state.language 
      : 'en';
    
    config.headers['Accept-Language'] = language;
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response?.status === 401) {
      // Clear auth data and redirect to login
      localStorage.removeItem('auth-storage');
      window.location.href = '/login';
    }
    
    return Promise.reject(error);
  }
);

// Auth API
export const authAPI = {
  requestOtp: (phone: string) => api.post('/auth/request-otp', { phone }),
  verifyOtp: (phone: string, code: string) => api.post('/auth/verify-otp', { phone, code }),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
};

// Customer Portal API
export const customerAPI = {
  getWorkers: (params?: any) => api.get('/portal/workers', { params }),
  reserveWorker: (workerId: string) => api.post(`/portal/workers/${workerId}/reserve`),
  createContract: (reservationId: string, data: any) => api.post(`/portal/reservations/${reservationId}/contract`, data),
  getContractInvoice: (contractId: string) => api.get(`/portal/contracts/${contractId}/invoice`),
  preparePayment: (contractId: string) => api.post(`/portal/contracts/${contractId}/prepare-payment`),
  confirmPayment: (contractId: string) => api.post(`/portal/contracts/${contractId}/confirm-payment`),
  cancelReservation: (reservationId: string) => api.post(`/portal/reservations/${reservationId}/cancel`),
  cancelContract: (contractId: string) => api.post(`/portal/contracts/${contractId}/cancel`),
};

// Agency Portal API
export const agencyAPI = {
  getRequests: (params?: any) => api.get('/agency/requests', { params }),
  submitProposal: (requestId: string, data: any) => api.post(`/agency/requests/${requestId}/proposals`, data),
  updateProposal: (proposalId: string, data: any) => api.patch(`/agency/proposals/${proposalId}`, data),
  withdrawProposal: (proposalId: string) => api.delete(`/agency/proposals/${proposalId}`),
  getProposals: (params?: any) => api.get('/agency/proposals', { params }),
};

// Admin API
export const adminAPI = {
  getProposals: (requestId: string) => api.get(`/admin/requests/${requestId}/proposals`),
  approveProposal: (proposalId: string, data: any) => api.post(`/admin/proposals/${proposalId}/approve`, data),
  rejectProposal: (proposalId: string, data: any) => api.post(`/admin/proposals/${proposalId}/reject`, data),
  getUsers: (params?: any) => api.get('/admin/users', { params }),
  getSettings: () => api.get('/admin/settings'),
};

// Lookups API
export const lookupsAPI = {
  getCountries: () => api.get('/lookups/countries'),
  getCities: (countryId?: string) => api.get('/lookups/cities', { params: { country_id: countryId } }),
  getDistricts: (cityId?: string) => api.get('/lookups/districts', { params: { city_id: cityId } }),
  getNationalities: () => api.get('/lookups/nationalities'),
  getProfessions: () => api.get('/lookups/professions'),
  getPackages: () => api.get('/lookups/packages'),
};