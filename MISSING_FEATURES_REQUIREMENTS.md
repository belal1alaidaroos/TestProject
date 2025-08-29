# Requirements to Build Missing Features

## What I Need to Build Missing Features:

### 1. Backend Controllers & Routes

#### Employee Portal Controllers (NEW)
Need to create these controllers:
- `EmployeeWorkerController` - for worker management
- `EmployeeContractController` - for contract management
- `EmployeeReservationController` - for reservation handling
- `EmployeeWorkerProblemController` - for problem reporting/resolution
- `EmployeeNotificationController` - for notification management

#### Missing API Routes
```php
// Employee Portal Routes (NEW)
Route::prefix('employee')->middleware('role:employee,admin,internal')->group(function () {
    // Workers management
    Route::get('workers', [EmployeeWorkerController::class, 'index']);
    Route::post('workers', [EmployeeWorkerController::class, 'store']);
    Route::patch('workers/{worker}', [EmployeeWorkerController::class, 'update']);
    Route::delete('workers/{worker}', [EmployeeWorkerController::class, 'destroy']);
    
    // Contracts management
    Route::get('contracts', [EmployeeContractController::class, 'index']);
    Route::patch('contracts/{contract}/status', [EmployeeContractController::class, 'updateStatus']);
    
    // Reservations management
    Route::get('reservations', [EmployeeReservationController::class, 'index']);
    Route::patch('reservations/{reservation}/process', [EmployeeReservationController::class, 'process']);
    
    // Worker Problems
    Route::get('worker-problems', [EmployeeWorkerProblemController::class, 'index']);
    Route::post('worker-problems', [EmployeeWorkerProblemController::class, 'store']);
    Route::patch('worker-problems/{problem}/resolve', [EmployeeWorkerProblemController::class, 'resolve']);
    
    // Notifications
    Route::get('notifications', [EmployeeNotificationController::class, 'index']);
    Route::patch('notifications/{notification}/read', [EmployeeNotificationController::class, 'markAsRead']);
    Route::patch('notifications/mark-all-read', [EmployeeNotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{notification}', [EmployeeNotificationController::class, 'destroy']);
});
```

### 2. Backend Models & Relationships

Already exist but need to verify:
- `Worker` model with relationships
- `WorkerProblem` model 
- `Notification` model
- `Contract` model
- `WorkerReservation` model

### 3. Frontend API Service Updates

Add to `/workspace/frontend/src/services/api.ts`:
```typescript
// Employee Portal API
export const employeeAPI = {
  // Workers
  getWorkers: (params?: any) => api.get('/employee/workers', { params }),
  createWorker: (data: any) => api.post('/employee/workers', data),
  updateWorker: (workerId: string, data: any) => api.patch(`/employee/workers/${workerId}`, data),
  deleteWorker: (workerId: string) => api.delete(`/employee/workers/${workerId}`),
  
  // Contracts
  getContracts: (params?: any) => api.get('/employee/contracts', { params }),
  updateContractStatus: (contractId: string, status: string) => 
    api.patch(`/employee/contracts/${contractId}/status`, { status }),
  
  // Reservations
  getReservations: (params?: any) => api.get('/employee/reservations', { params }),
  processReservation: (reservationId: string, action: string) => 
    api.patch(`/employee/reservations/${reservationId}/process`, { action }),
  
  // Worker Problems
  getWorkerProblems: (params?: any) => api.get('/employee/worker-problems', { params }),
  reportProblem: (data: any) => api.post('/employee/worker-problems', data),
  resolveProblem: (problemId: string, data: any) => 
    api.patch(`/employee/worker-problems/${problemId}/resolve`, data),
  
  // Notifications
  getNotifications: (params?: any) => api.get('/employee/notifications', { params }),
  markNotificationAsRead: (notificationId: string) => 
    api.patch(`/employee/notifications/${notificationId}/read`),
  markAllNotificationsAsRead: () => api.patch('/employee/notifications/mark-all-read'),
  deleteNotification: (notificationId: string) => 
    api.delete(`/employee/notifications/${notificationId}`),
};
```

### 4. Frontend Component Updates

#### Customer Portal
1. **PaymentPage**: Connect to existing payment endpoints
2. **ContractPage**: Add invoice download functionality using existing endpoint

#### Admin Portal  
1. **UsersPage**: Add create user modal and delete confirmation
   - Backend endpoints already exist (`POST /admin/users`, `DELETE /admin/users/{user}`)

#### Agency Portal
1. **ProposalsPage**: Implement full edit functionality
   - Backend endpoint exists (`PATCH /agency/proposals/{proposal}`)

#### Employee Portal (Major work needed)
1. **Workers.tsx**: Add CRUD modals and forms
2. **WorkerProblems.tsx**: Add problem reporting form and resolution workflow
3. **Contracts.tsx**: Add status update functionality
4. **Reservations.tsx**: Add reservation processing actions
5. **Notifications.tsx**: Add mark as read and delete functionality

### 5. Database Considerations

Tables already exist:
- `workers` - has all necessary fields
- `worker_problems` - has problem tracking fields
- `notifications` - has notification fields
- `contracts` - has status field
- `worker_reservations` - has state field

### 6. Authorization & Permissions

Need to ensure:
- Employee role has proper permissions for CRUD operations
- Role-based access control for each operation
- Validation rules for data integrity

### 7. Form Validation & Error Handling

Need to implement:
- Form validation schemas (using existing patterns)
- Error handling for all API calls
- Success/error toast notifications
- Loading states
- Confirmation dialogs for destructive actions

## Priority Implementation Order:

1. **Backend Employee Controllers** - Create missing controllers and routes
2. **Frontend API Service** - Add employee API endpoints
3. **Admin User Management** - Complete CRUD (endpoints exist)
4. **Employee Worker Management** - Full CRUD implementation
5. **Employee Problem Reporting** - Create and resolve workflow
6. **Payment & Invoice** - Connect existing endpoints
7. **Notifications System** - Mark as read and delete
8. **Agency Proposal Edit** - Complete implementation

## Estimated Effort:
- Backend Controllers: 2-3 days
- Frontend Updates: 3-4 days
- Testing & Integration: 1-2 days
- Total: ~1 week for all missing features