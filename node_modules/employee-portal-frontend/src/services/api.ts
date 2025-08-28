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
  
  // PayPass Payment Methods
  createPayPassSession: (contractId: string, phone: string) => api.post('/portal/paypass/create-session', { contract_id: contractId, phone }),
  verifyPayPassOtp: (sessionId: string, otp: string) => api.post('/portal/paypass/verify-otp', { session_id: sessionId, otp }),
  getPayPassSessionStatus: (sessionId: string) => api.get(`/portal/paypass/session/${sessionId}/status`),
  cancelPayPassSession: (sessionId: string) => api.post(`/portal/paypass/session/${sessionId}/cancel`),
  cancelReservation: (reservationId: string) => api.post(`/portal/reservations/${reservationId}/cancel`),
  cancelContract: (contractId: string) => api.post(`/portal/contracts/${contractId}/cancel`),
  
  // New enhanced endpoints
  getPackages: (params?: any) => api.get('/customer/packages', { params }),
  getDiscounts: () => api.get('/customer/discounts'),
  getAddresses: () => api.get('/customer/addresses'),
  createAddress: (data: any) => api.post('/customer/addresses', data),
  updateAddress: (addressId: string, data: any) => api.patch(`/customer/addresses/${addressId}`, data),
  deleteAddress: (addressId: string) => api.delete(`/customer/addresses/${addressId}`),
  createContractWizard: (data: any) => api.post('/customer/contracts', data),
  getContracts: (params?: any) => api.get('/customer/contracts', { params }),
  getContract: (contractId: string) => api.get(`/customer/contracts/${contractId}`),
};

// Agency Portal API
export const agencyAPI = {
  getRequests: (params?: any) => api.get('/agency/requests', { params }),
  submitProposal: (requestId: string, data: any) => api.post(`/agency/requests/${requestId}/proposals`, data),
  updateProposal: (proposalId: string, data: any) => api.patch(`/agency/proposals/${proposalId}`, data),
  withdrawProposal: (proposalId: string) => api.delete(`/agency/proposals/${proposalId}`),
  getProposals: (params?: any) => api.get('/agency/proposals', { params }),
  
  // New enhanced endpoints
  acceptRequest: (requestId: string, data: any) => api.post(`/agency/requests/${requestId}/accept`, data),
  rejectRequest: (requestId: string, data: any) => api.post(`/agency/requests/${requestId}/reject`, data),
  submitCandidateDetails: (proposalId: string, data: any) => api.post(`/agency/proposals/${proposalId}/candidates`, data),
  updateCandidateStatus: (proposalId: string, candidateId: string, data: any) => api.patch(`/agency/proposals/${proposalId}/candidates/${candidateId}`, data),
  getNationalities: () => api.get('/agency/nationalities'),
};

// Admin API
export const adminAPI = {
  getProposals: (requestId: string) => api.get(`/admin/requests/${requestId}/proposals`),
  approveProposal: (proposalId: string, data: any) => api.post(`/admin/proposals/${proposalId}/approve`, data),
  rejectProposal: (proposalId: string, data: any) => api.post(`/admin/proposals/${proposalId}/reject`, data),
  getUsers: (params?: any) => api.get('/admin/users', { params }),
  getSettings: () => api.get('/admin/settings'),
  
  // New enhanced endpoints
  getWorkers: (params?: any) => api.get('/admin/workers', { params }),
  createWorker: (data: any) => api.post('/admin/workers', data),
  updateWorker: (workerId: string, data: any) => api.patch(`/admin/workers/${workerId}`, data),
  deleteWorker: (workerId: string) => api.delete(`/admin/workers/${workerId}`),
  updateWorkerLifecycle: (workerId: string, data: any) => api.patch(`/admin/workers/${workerId}/lifecycle`, data),
  
  getDiscounts: (params?: any) => api.get('/admin/discounts', { params }),
  createDiscount: (data: any) => api.post('/admin/discounts', data),
  updateDiscount: (discountId: string, data: any) => api.patch(`/admin/discounts/${discountId}`, data),
  deleteDiscount: (discountId: string) => api.delete(`/admin/discounts/${discountId}`),
  
  getPackages: (params?: any) => api.get('/admin/packages', { params }),
  createPackage: (data: any) => api.post('/admin/packages', data),
  updatePackage: (packageId: string, data: any) => api.patch(`/admin/packages/${packageId}`, data),
  deletePackage: (packageId: string) => api.delete(`/admin/packages/${packageId}`),
  
  getWorkerProblems: (params?: any) => api.get('/admin/worker-problems', { params }),
  createWorkerProblem: (data: any) => api.post('/admin/worker-problems', data),
  updateWorkerProblem: (problemId: string, data: any) => api.patch(`/admin/worker-problems/${problemId}`, data),
  approveWorkerProblem: (problemId: string, data: any) => api.post(`/admin/worker-problems/${problemId}/approve`, data),
  rejectWorkerProblem: (problemId: string, data: any) => api.post(`/admin/worker-problems/${problemId}/reject`, data),
  
  getAuditLogs: (params?: any) => api.get('/admin/audit-logs', { params }),
  exportAuditLogs: (params?: any) => api.get('/admin/audit-logs/export', { params, responseType: 'blob' }),
  
  getNotifications: (params?: any) => api.get('/admin/notifications', { params }),
  markNotificationRead: (notificationId: string) => api.patch(`/admin/notifications/${notificationId}/read`),
  markAllNotificationsRead: () => api.patch('/admin/notifications/mark-all-read'),
  
  // Import/Export endpoints
  importWorkers: (file: File) => {
    const formData = new FormData();
    formData.append('file', file);
    return api.post('/admin/workers/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },
  exportWorkers: (params?: any) => api.get('/admin/workers/export', { params, responseType: 'blob' }),
  
  importContracts: (file: File) => {
    const formData = new FormData();
    formData.append('file', file);
    return api.post('/admin/contracts/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },
  exportContracts: (params?: any) => api.get('/admin/contracts/export', { params, responseType: 'blob' }),
  
  importAddresses: (file: File) => {
    const formData = new FormData();
    formData.append('file', file);
    return api.post('/admin/addresses/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },
  exportAddresses: (params?: any) => api.get('/admin/addresses/export', { params, responseType: 'blob' }),
};

// Lookups API
export const lookupsAPI = {
  getCountries: () => api.get('/lookups/countries'),
  getCities: (countryId?: string) => api.get('/lookups/cities', { params: { country_id: countryId } }),
  getDistricts: (cityId?: string) => api.get('/lookups/districts', { params: { city_id: cityId } }),
  getNationalities: () => api.get('/lookups/nationalities'),
  getProfessions: () => api.get('/lookups/professions'),
  getContractDurations: () => api.get('/lookups/contract-durations'),
  getCurrencies: () => api.get('/lookups/currencies'),
  
  // Admin CRUD for lookups
  createCountry: (data: any) => api.post('/lookups/countries', data),
  updateCountry: (countryId: string, data: any) => api.patch(`/lookups/countries/${countryId}`, data),
  deleteCountry: (countryId: string) => api.delete(`/lookups/countries/${countryId}`),
  
  createCity: (data: any) => api.post('/lookups/cities', data),
  updateCity: (cityId: string, data: any) => api.patch(`/lookups/cities/${cityId}`, data),
  deleteCity: (cityId: string) => api.delete(`/lookups/cities/${cityId}`),
  
  createDistrict: (data: any) => api.post('/lookups/districts', data),
  updateDistrict: (districtId: string, data: any) => api.patch(`/lookups/districts/${districtId}`, data),
  deleteDistrict: (districtId: string) => api.delete(`/lookups/districts/${districtId}`),
  
  createNationality: (data: any) => api.post('/lookups/nationalities', data),
  updateNationality: (nationalityId: string, data: any) => api.patch(`/lookups/nationalities/${nationalityId}`, data),
  deleteNationality: (nationalityId: string) => api.delete(`/lookups/nationalities/${nationalityId}`),
  
  createProfession: (data: any) => api.post('/lookups/professions', data),
  updateProfession: (professionId: string, data: any) => api.patch(`/lookups/professions/${professionId}`, data),
  deleteProfession: (professionId: string) => api.delete(`/lookups/professions/${professionId}`),
  
  createContractDuration: (data: any) => api.post('/lookups/contract-durations', data),
  updateContractDuration: (durationId: string, data: any) => api.patch(`/lookups/contract-durations/${durationId}`, data),
  deleteContractDuration: (durationId: string) => api.delete(`/lookups/contract-durations/${durationId}`),
};

// Attachments API
export const attachmentsAPI = {
  upload: (file: File, entityName: string, entityId: string, description?: string) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('entity_name', entityName);
    formData.append('entity_id', entityId);
    if (description) {
      formData.append('description', description);
    }
    return api.post('/attachments/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },
  
  getAttachments: (entityName: string, entityId: string) => api.get(`/attachments/${entityName}/${entityId}`),
  downloadAttachment: (attachmentId: string) => api.get(`/attachments/${attachmentId}/download`, { responseType: 'blob' }),
  deleteAttachment: (attachmentId: string) => api.delete(`/attachments/${attachmentId}`),
  updateDescription: (attachmentId: string, description: string) => api.patch(`/attachments/${attachmentId}`, { description }),
};

// Notifications API
export const notificationsAPI = {
  getNotifications: (params?: any) => api.get('/notifications', { params }),
  markAsRead: (notificationId: string) => api.patch(`/notifications/${notificationId}/read`),
  markAllAsRead: () => api.patch('/notifications/mark-all-read'),
  getUnreadCount: () => api.get('/notifications/unread-count'),
};

// Audit Logs API
export const auditLogsAPI = {
  getLogs: (params?: any) => api.get('/audit-logs', { params }),
  getEntityLogs: (entityName: string, entityId: string) => api.get(`/audit-logs/${entityName}/${entityId}`),
  getUserLogs: (userId: string) => api.get(`/audit-logs/user/${userId}`),
  exportLogs: (params?: any) => api.get('/audit-logs/export', { params, responseType: 'blob' }),
};

// Role Management API
export const roleManagementAPI = {
  assignRole: (data: any) => api.post('/admin/role-management/assign-role', data),
  removeRole: (data: any) => api.post('/admin/role-management/remove-role', data),
  syncRoles: (data: any) => api.post('/admin/role-management/sync-roles', data),
  setPrimaryRole: (data: any) => api.post('/admin/role-management/set-primary-role', data),
  getUserRoles: (userId: string) => api.get(`/admin/role-management/users/${userId}/roles`),
  getUsersByRole: (roleName: string, params?: any) => api.get(`/admin/role-management/roles/${roleName}/users`, { params }),
  getRoleAssignmentHistory: (userId: string) => api.get(`/admin/role-management/users/${userId}/role-history`),
  getRoleStatistics: () => api.get('/admin/role-management/statistics'),
};

// Localization API
export const localizationAPI = {
  getSupportedLanguages: () => api.get('/localization/supported-languages'),
  getSupportedCurrencies: () => api.get('/localization/supported-currencies'),
  getSupportedTimezones: () => api.get('/localization/supported-timezones'),
  getDateFormats: () => api.get('/localization/date-formats'),
  getUserLocalizationSettings: () => api.get('/localization/user-settings'),
  setUserLanguage: (data: any) => api.post('/localization/set-language', data),
  setUserCurrency: (data: any) => api.post('/localization/set-currency', data),
  setUserTimezone: (data: any) => api.post('/localization/set-timezone', data),
  setUserDateFormat: (data: any) => api.post('/localization/set-date-format', data),
  formatCurrency: (data: any) => api.post('/localization/format-currency', data),
  formatDate: (data: any) => api.post('/localization/format-date', data),
  getTranslation: (data: any) => api.post('/localization/translation', data),
  getTranslations: (data: any) => api.post('/localization/translations', data),
  getSystemLocalizationSettings: () => api.get('/localization/system-settings'),
  updateSystemLocalizationSettings: (data: any) => api.post('/localization/system-settings', data),
  exportTranslations: (data: any) => api.post('/localization/export-translations', data),
  importTranslations: (data: any) => api.post('/localization/import-translations', data),
};