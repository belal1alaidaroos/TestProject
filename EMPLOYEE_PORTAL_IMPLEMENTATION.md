# Employee Portal Implementation

## Overview

The Employee Portal is the fourth portal in our worker management system, designed specifically for company employees (internal users) who need to manage assigned workers, contracts, and reservations. This portal provides a focused interface for employees to handle their daily tasks efficiently.

## Features

### 1. Dashboard
- **Statistics Overview**: Total workers managed, active contracts, pending reservations, completed tasks
- **Tasks Summary**: Detailed breakdown of workers, contracts, and reservations by status
- **Quick Actions**: Direct access to main functions
- **Recent Activity**: Timeline of recent actions and updates

### 2. Workers Management
- **View Assigned Workers**: List of workers assigned to the employee
- **Worker Details**: Complete worker information including personal details, profession, nationality
- **Search & Filter**: Filter by status, profession, and search by name or passport
- **Status Tracking**: Monitor worker status (active, inactive, pending)

### 3. Contracts Management
- **Assigned Contracts**: View contracts assigned to the employee
- **Contract Details**: Complete contract information with worker and customer details
- **Status Updates**: Update contract status (pending, active, completed, cancelled, on_hold)
- **Employee Notes**: Add notes and comments to contracts
- **Search & Filter**: Filter by status and search functionality

### 4. Reservations Management
- **Assigned Reservations**: View worker reservations assigned to the employee
- **Reservation Details**: Complete reservation information
- **Status Updates**: Update reservation status (pending, confirmed, rejected, cancelled)
- **Employee Notes**: Add notes to reservations
- **Search & Filter**: Filter by status

### 5. Notifications
- **Real-time Notifications**: View system notifications
- **Read/Unread Status**: Mark notifications as read
- **Filter Options**: Filter by read/unread status
- **Bulk Actions**: Mark all notifications as read

## Technical Implementation

### Backend Components

#### 1. EmployeeController (`backend/app/Http/Controllers/Api/EmployeeController.php`)
- **Dashboard Statistics**: `getDashboardStats()`, `getTasksSummary()`
- **Workers Management**: `getAssignedWorkers()`, `getWorker()`
- **Contracts Management**: `getAssignedContracts()`, `getContract()`, `updateContractStatus()`
- **Reservations Management**: `getAssignedReservations()`, `updateReservationStatus()`
- **Notifications**: `getNotifications()`, `markNotificationAsRead()`
- **Audit Logs**: `getAuditLogs()`

#### 2. API Routes (`backend/routes/api.php`)
```php
// Employee Portal Routes
Route::prefix('employee')->group(function () {
    // Dashboard
    Route::get('dashboard/stats', [EmployeeController::class, 'getDashboardStats']);
    Route::get('tasks/summary', [EmployeeController::class, 'getTasksSummary']);
    
    // Workers Management
    Route::get('workers', [EmployeeController::class, 'getAssignedWorkers']);
    Route::get('workers/{workerId}', [EmployeeController::class, 'getWorker']);
    
    // Contracts Management
    Route::get('contracts', [EmployeeController::class, 'getAssignedContracts']);
    Route::get('contracts/{contractId}', [EmployeeController::class, 'getContract']);
    Route::patch('contracts/{contractId}/status', [EmployeeController::class, 'updateContractStatus']);
    
    // Reservations Management
    Route::get('reservations', [EmployeeController::class, 'getAssignedReservations']);
    Route::patch('reservations/{reservationId}/status', [EmployeeController::class, 'updateReservationStatus']);
    
    // Notifications
    Route::get('notifications', [EmployeeController::class, 'getNotifications']);
    Route::patch('notifications/{notificationId}/read', [EmployeeController::class, 'markNotificationAsRead']);
    
    // Audit Logs
    Route::get('audit-logs', [EmployeeController::class, 'getAuditLogs']);
});
```

#### 3. Database Changes

**New Role**: Employee role with specific permissions
```sql
-- Employee role permissions
- workers.view
- workers.manage
- contracts.view
- contracts.update_status
- reservations.view
- reservations.update_status
- notifications.view
- notifications.mark_read
- profile.view
- profile.update
```

**New Fields Added**:
- `workers.assigned_employee_id` - Links workers to assigned employees
- `contracts.assigned_employee_id` - Links contracts to assigned employees
- `contracts.employee_notes` - Employee-specific notes on contracts
- `contracts.status_updated_at` - When contract status was last updated
- `contracts.status_updated_by` - Who updated the contract status
- `worker_reservations.assigned_employee_id` - Links reservations to assigned employees
- `worker_reservations.employee_notes` - Employee-specific notes on reservations
- `worker_reservations.status_updated_at` - When reservation status was last updated
- `worker_reservations.status_updated_by` - Who updated the reservation status

#### 4. Model Updates
- **Worker Model**: Added `assignedEmployee()` relationship
- **Contract Model**: Added `assignedEmployee()` and `statusUpdatedBy()` relationships
- **WorkerReservation Model**: Added `assignedEmployee()` and `statusUpdatedBy()` relationships

### Frontend Components

#### 1. Layout (`frontend/src/pages/employee/Layout.tsx`)
- Responsive sidebar navigation
- User profile section
- Mobile-friendly design
- Active route highlighting

#### 2. Dashboard (`frontend/src/pages/employee/Dashboard.tsx`)
- Statistics cards with icons
- Tasks summary sections
- Quick action buttons
- Recent activity timeline

#### 3. Workers Page (`frontend/src/pages/employee/Workers.tsx`)
- Grid layout for worker cards
- Search and filter functionality
- Pagination support
- Worker detail cards with contact information

#### 4. Contracts Page (`frontend/src/pages/employee/Contracts.tsx`)
- Contract list with detailed information
- Status update modal
- Employee notes functionality
- Search and filter options

#### 5. Reservations Page (`frontend/src/pages/employee/Reservations.tsx`)
- Reservation management interface
- Status update functionality
- Employee notes support
- Filtering and search

#### 6. Notifications Page (`frontend/src/pages/employee/Notifications.tsx`)
- Notification list with read/unread status
- Bulk actions (mark all as read)
- Filtering options
- Real-time updates

## Setup Instructions

### 1. Database Migrations
```bash
# Run the migrations to create employee role and add new fields
php artisan migrate
```

### 2. Seed Sample Data
```bash
# Create sample employee users
php artisan db:seed --class=EmployeeSeeder
```

### 3. Frontend Setup
The employee portal is automatically integrated into the main application. Users with the Employee role will be redirected to the employee portal instead of the admin portal.

## User Management

### Creating Employee Users
1. Create a new user with `user_type = 'Internal'`
2. Assign the "Employee" role to the user
3. The user will automatically access the employee portal

### Sample Employee Credentials
```
Email: ahmed.hassan@company.com
Password: password123

Email: fatima.alzahra@company.com
Password: password123

Email: omar.khalil@company.com
Password: password123
```

## Role-Based Access Control

### Employee Role Permissions
- **Read Access**: View assigned workers, contracts, reservations, notifications
- **Update Access**: Update contract status, reservation status, mark notifications as read
- **No Create/Delete**: Employees cannot create or delete records
- **Profile Management**: Update own profile information

### Security Features
- **Route Protection**: All employee routes are protected with authentication
- **Data Isolation**: Employees only see data assigned to them
- **Audit Logging**: All actions are logged for accountability
- **Input Validation**: All inputs are validated and sanitized

## Workflow Examples

### 1. Managing a Worker Assignment
1. Employee logs into the portal
2. Views assigned workers in the Workers section
3. Clicks on a worker to view detailed information
4. Can contact the worker or update their status

### 2. Updating Contract Status
1. Employee navigates to Contracts section
2. Finds the contract that needs status update
3. Clicks "Update Status" button
4. Selects new status and adds notes
5. Saves the update (logged in audit trail)

### 3. Handling Reservations
1. Employee checks pending reservations
2. Reviews reservation details
3. Updates status to confirmed/rejected/cancelled
4. Adds notes explaining the decision

## Integration with Existing System

### Portal Routing Logic
The application automatically routes users based on their role:
- Users with "Employee" role → Employee Portal
- Users with "Admin" role → Admin Portal
- Users with "Customer" role → Customer Portal
- Users with "Agency" role → Agency Portal

### Data Relationships
- Workers can be assigned to employees for management
- Contracts can be assigned to employees for oversight
- Reservations can be assigned to employees for processing
- All assignments maintain referential integrity

## Future Enhancements

### Potential Features
1. **Task Assignment**: Admin can assign specific tasks to employees
2. **Performance Metrics**: Track employee performance and efficiency
3. **Communication Tools**: Built-in messaging between employees and workers
4. **Mobile App**: Native mobile application for field work
5. **Reporting**: Advanced reporting and analytics for employee activities
6. **Integration**: Connect with external HR systems

### Scalability Considerations
- Role-based permissions can be easily extended
- New employee-specific features can be added modularly
- Database design supports horizontal scaling
- API endpoints follow RESTful conventions

## Troubleshooting

### Common Issues
1. **Employee not seeing assigned data**: Check if worker/contract/reservation is assigned to the employee
2. **Permission denied errors**: Verify employee role is properly assigned
3. **Missing notifications**: Check notification settings and user preferences

### Debug Steps
1. Verify user has "Employee" role assigned
2. Check database for proper assignments
3. Review audit logs for any errors
4. Validate API responses in browser developer tools

## Support

For technical support or questions about the Employee Portal implementation, please refer to the main project documentation or contact the development team.