import React, { useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuthStore } from './stores/authStore';
import { useLanguageStore } from './stores/languageStore';

// Layout Components
import Layout from './components/Layout/Layout';
import CustomerLayout from './components/Layout/CustomerLayout';
import AgencyLayout from './components/Layout/AgencyLayout';
import AdminLayout from './components/Layout/AdminLayout';
import EmployeeLayout from './pages/employee/Layout';

// Auth Pages
import LoginPage from './pages/auth/LoginPage';
import OtpVerificationPage from './pages/auth/OtpVerificationPage';

// Customer Portal Pages
import WorkersPage from './pages/customer/WorkersPage';
import ReservationPage from './pages/customer/ReservationPage';
import ContractPage from './pages/customer/ContractPage';
import PaymentPage from './pages/customer/PaymentPage';

// Agency Portal Pages
import RequestsPage from './pages/agency/RequestsPage';
import ProposalsPage from './pages/agency/ProposalsPage';
import SubmitProposalPage from './pages/agency/SubmitProposalPage';

// Admin Pages
import AdminDashboardPage from './pages/admin/AdminDashboardPage';
import ProposalsReviewPage from './pages/admin/ProposalsReviewPage';
import UsersPage from './pages/admin/UsersPage';
import SettingsPage from './pages/admin/SettingsPage';

// Employee Portal Pages
import EmployeeDashboard from './pages/employee/Dashboard';
import EmployeeWorkers from './pages/employee/Workers';
import EmployeeContracts from './pages/employee/Contracts';
import EmployeeReservations from './pages/employee/Reservations';
import EmployeeNotifications from './pages/employee/Notifications';

// Protected Route Component
import ProtectedRoute from './components/Auth/ProtectedRoute';

// Initialize i18n
import './i18n';

function App() {
  const { i18n } = useTranslation();
  const { user, isAuthenticated } = useAuthStore();
  const { language } = useLanguageStore();

  useEffect(() => {
    // Set language direction
    document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = language;
    
    // Change i18n language
    i18n.changeLanguage(language);
  }, [language, i18n]);

  const getLayout = () => {
    if (!isAuthenticated || !user) return null;
    
    switch (user.user_type) {
      case 'Customer':
        return <CustomerLayout />;
      case 'Agency':
        return <AgencyLayout />;
      case 'Internal':
        // Check if user has employee role to determine layout
        const hasEmployeeRole = user.roles?.some((role: any) => 
          role.name === 'Employee' || role.display_name === 'Employee'
        );
        return hasEmployeeRole ? <EmployeeLayout /> : <AdminLayout />;
      default:
        return <Layout />;
    }
  };

  const getRoutes = () => {
    if (!isAuthenticated || !user) {
      return (
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/verify-otp" element={<OtpVerificationPage />} />
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      );
    }

    switch (user.user_type) {
      case 'Customer':
        return (
          <Routes>
            <Route path="/workers" element={
              <ProtectedRoute requiredUserType="Customer">
                <WorkersPage />
              </ProtectedRoute>
            } />
            <Route path="/reservation/:id" element={
              <ProtectedRoute requiredUserType="Customer">
                <ReservationPage />
              </ProtectedRoute>
            } />
            <Route path="/contract/:id" element={
              <ProtectedRoute requiredUserType="Customer">
                <ContractPage />
              </ProtectedRoute>
            } />
            <Route path="/payment/:id" element={
              <ProtectedRoute requiredUserType="Customer">
                <PaymentPage />
              </ProtectedRoute>
            } />
            <Route path="*" element={<Navigate to="/workers" replace />} />
          </Routes>
        );
      
      case 'Agency':
        return (
          <Routes>
            <Route path="/requests" element={
              <ProtectedRoute requiredUserType="Agency">
                <RequestsPage />
              </ProtectedRoute>
            } />
            <Route path="/proposals" element={
              <ProtectedRoute requiredUserType="Agency">
                <ProposalsPage />
              </ProtectedRoute>
            } />
            <Route path="/submit-proposal/:requestId" element={
              <ProtectedRoute requiredUserType="Agency">
                <SubmitProposalPage />
              </ProtectedRoute>
            } />
            <Route path="*" element={<Navigate to="/requests" replace />} />
          </Routes>
        );
      
      case 'Internal':
        // Check if user has employee role to determine routes
        const hasEmployeeRole = user.roles?.some((role: any) => 
          role.name === 'Employee' || role.display_name === 'Employee'
        );
        
        if (hasEmployeeRole) {
          return (
            <Routes>
              <Route path="/employee" element={
                <ProtectedRoute requiredUserType="Internal">
                  <EmployeeDashboard />
                </ProtectedRoute>
              } />
              <Route path="/employee/workers" element={
                <ProtectedRoute requiredUserType="Internal">
                  <EmployeeWorkers />
                </ProtectedRoute>
              } />
              <Route path="/employee/contracts" element={
                <ProtectedRoute requiredUserType="Internal">
                  <EmployeeContracts />
                </ProtectedRoute>
              } />
              <Route path="/employee/reservations" element={
                <ProtectedRoute requiredUserType="Internal">
                  <EmployeeReservations />
                </ProtectedRoute>
              } />
              <Route path="/employee/notifications" element={
                <ProtectedRoute requiredUserType="Internal">
                  <EmployeeNotifications />
                </ProtectedRoute>
              } />
              <Route path="*" element={<Navigate to="/employee" replace />} />
            </Routes>
          );
        } else {
          return (
            <Routes>
              <Route path="/dashboard" element={
                <ProtectedRoute requiredUserType="Internal">
                  <AdminDashboardPage />
                </ProtectedRoute>
              } />
              <Route path="/proposals-review/:requestId" element={
                <ProtectedRoute requiredUserType="Internal">
                  <ProposalsReviewPage />
                </ProtectedRoute>
              } />
              <Route path="/users" element={
                <ProtectedRoute requiredUserType="Internal">
                  <UsersPage />
                </ProtectedRoute>
              } />
              <Route path="/settings" element={
                <ProtectedRoute requiredUserType="Internal">
                  <SettingsPage />
                </ProtectedRoute>
              } />
              <Route path="*" element={<Navigate to="/dashboard" replace />} />
            </Routes>
          );
        }
      
      default:
        return (
          <Routes>
            <Route path="*" element={<Navigate to="/login" replace />} />
          </Routes>
        );
    }
  };

  return (
    <Router>
      <div className="min-h-screen bg-gray-50">
        {getLayout()}
        <main className={getLayout() ? 'pt-16' : ''}>
          {getRoutes()}
        </main>
      </div>
    </Router>
  );
}

export default App;
