import { api } from './api';

export const apiService = {
  // Employee API
  getEmployeeDashboard: () => api.get('/employee/dashboard'),
  getEmployeeWorkers: (params?: unknown) => api.get('/employee/workers', { params }),
  getEmployeeContracts: (params?: unknown) => api.get('/employee/contracts', { params }),
  getEmployeeReservations: (params?: unknown) => api.get('/employee/reservations', { params }),
  getEmployeeWorkerProblems: (params?: unknown) => api.get('/employee/worker-problems', { params }),
  getEmployeeNotifications: (params?: unknown) => api.get('/employee/notifications', { params }),
  
  // Update worker status
  updateWorkerStatus: (workerId: string, data: unknown) => api.patch(`/employee/workers/${workerId}/status`, data),
  
  // Update contract status
  updateContractStatus: (contractId: string, data: unknown) => api.patch(`/employee/contracts/${contractId}/status`, data),
  
  // Update reservation status
  updateReservationStatus: (reservationId: string, data: unknown) => api.patch(`/employee/reservations/${reservationId}/status`, data),
  
  // Update worker problem status
  updateWorkerProblemStatus: (problemId: string, data: unknown) => api.patch(`/employee/worker-problems/${problemId}/status`, data),
  
  // Update notification status
  updateNotificationStatus: (notificationId: string, data: unknown) => api.patch(`/employee/notifications/${notificationId}/status`, data),
};