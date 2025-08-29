import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './stores/AuthProvider';

// Auth pages
import LoginPage from './pages/auth/LoginPage';

// Customer pages
import CustomerLayout from './components/Layout/CustomerLayout';
import WorkersPage from './pages/customer/WorkersPage';
import WorkerDetailPage from './pages/customer/WorkerDetailPage';
import ReservationPage from './pages/customer/ReservationPage';
import ContractPage from './pages/customer/ContractPage';
import PaymentPage from './pages/customer/PaymentPage';

// Agency pages
import AgencyLayout from './components/Layout/AgencyLayout';
import RequestsPage from './pages/agency/RequestsPage';
import ProposalsPage from './pages/agency/ProposalsPage';
import SubmitProposalPage from './pages/agency/SubmitProposalPage';

// Admin pages
import AdminLayout from './components/Layout/AdminLayout';
import AdminDashboardPage from './pages/admin/AdminDashboardPage';
import UsersPage from './pages/admin/UsersPage';
import ProposalsReviewPage from './pages/admin/ProposalsReviewPage';
import SettingsPage from './pages/admin/SettingsPage';

// Common pages
import NotFoundPage from './pages/NotFoundPage';

// Protected Route Component
const ProtectedRoute = ({ children, requiredRole }: { children: React.ReactNode; requiredRole?: string[] }) => {
  const { isAuthenticated, user } = useAuth();

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (requiredRole && requiredRole.length > 0) {
    const hasRole = user?.roles?.some((role: any) => requiredRole.includes(role.name));
    if (!hasRole) {
      return <Navigate to="/unauthorized" replace />;
    }
  }

  return <>{children}</>;
};

function App() {
  const { isAuthenticated, user } = useAuth();

  // Function to get default route based on user role
  const getDefaultRoute = () => {
    if (!isAuthenticated || !user) return '/login';
    
    if (user.roles?.some((role: any) => ['admin', 'internal'].includes(role.name))) {
      return '/admin/dashboard';
    } else if (user.roles?.some((role: any) => role.name === 'agency')) {
      return '/agency/requests';
    } else {
      return '/customer/workers';
    }
  };

  return (
    <Routes>
      {/* Public Routes */}
      <Route path="/login" element={!isAuthenticated ? <LoginPage /> : <Navigate to={getDefaultRoute()} replace />} />
      
      {/* Customer Routes */}
      <Route
        path="/customer"
        element={
          <ProtectedRoute requiredRole={['customer']}>
            <CustomerLayout>
              <Navigate to="/customer/workers" replace />
            </CustomerLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/customer/workers"
        element={
          <ProtectedRoute requiredRole={['customer']}>
            <CustomerLayout>
              <WorkersPage />
            </CustomerLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/customer/workers/:workerId"
        element={
          <ProtectedRoute requiredRole={['customer']}>
            <CustomerLayout>
              <WorkerDetailPage />
            </CustomerLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/customer/reservations"
        element={
          <ProtectedRoute requiredRole={['customer']}>
            <CustomerLayout>
              <ReservationPage />
            </CustomerLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/customer/contracts"
        element={
          <ProtectedRoute requiredRole={['customer']}>
            <CustomerLayout>
              <ContractPage />
            </CustomerLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/customer/payment/:contractId"
        element={
          <ProtectedRoute requiredRole={['customer']}>
            <CustomerLayout>
              <PaymentPage />
            </CustomerLayout>
          </ProtectedRoute>
        }
      />

      {/* Agency Routes */}
      <Route
        path="/agency"
        element={
          <ProtectedRoute requiredRole={['agency']}>
            <AgencyLayout>
              <Navigate to="/agency/requests" replace />
            </AgencyLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/agency/requests"
        element={
          <ProtectedRoute requiredRole={['agency']}>
            <AgencyLayout>
              <RequestsPage />
            </AgencyLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/agency/proposals"
        element={
          <ProtectedRoute requiredRole={['agency']}>
            <AgencyLayout>
              <ProposalsPage />
            </AgencyLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/agency/submit-proposal/:requestId"
        element={
          <ProtectedRoute requiredRole={['agency']}>
            <AgencyLayout>
              <SubmitProposalPage />
            </AgencyLayout>
          </ProtectedRoute>
        }
      />

      {/* Admin Routes */}
      <Route
        path="/admin"
        element={
          <ProtectedRoute requiredRole={['admin', 'internal']}>
            <AdminLayout>
              <Navigate to="/admin/dashboard" replace />
            </AdminLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/dashboard"
        element={
          <ProtectedRoute requiredRole={['admin', 'internal']}>
            <AdminLayout>
              <AdminDashboardPage />
            </AdminLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/users"
        element={
          <ProtectedRoute requiredRole={['admin', 'internal']}>
            <AdminLayout>
              <UsersPage />
            </AdminLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/proposals"
        element={
          <ProtectedRoute requiredRole={['admin', 'internal']}>
            <AdminLayout>
              <ProposalsReviewPage />
            </AdminLayout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/settings"
        element={
          <ProtectedRoute requiredRole={['admin', 'internal']}>
            <AdminLayout>
              <SettingsPage />
            </AdminLayout>
          </ProtectedRoute>
        }
      />

      {/* Default Route */}
      <Route path="/" element={<Navigate to={getDefaultRoute()} replace />} />
      
      {/* Unauthorized */}
      <Route
        path="/unauthorized"
        element={
          <div className="min-h-screen bg-gray-50 flex items-center justify-center">
            <div className="text-center">
              <h1 className="text-4xl font-bold text-gray-900 mb-4">Unauthorized</h1>
              <p className="text-xl text-gray-600">You don't have permission to access this page.</p>
            </div>
          </div>
        }
      />
      
      {/* 404 */}
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}

export default App;