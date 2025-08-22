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
import AdminDashboardPage from './pages/admin/DashboardPage';
import ProposalsReviewPage from './pages/admin/ProposalsReviewPage';
import UsersPage from './pages/admin/UsersPage';
import SettingsPage from './pages/admin/SettingsPage';

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
        return <AdminLayout />;
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
            <Route path="/workers" element={<WorkersPage />} />
            <Route path="/reservation/:id" element={<ReservationPage />} />
            <Route path="/contract/:id" element={<ContractPage />} />
            <Route path="/payment/:id" element={<PaymentPage />} />
            <Route path="*" element={<Navigate to="/workers" replace />} />
          </Routes>
        );
      
      case 'Agency':
        return (
          <Routes>
            <Route path="/requests" element={<RequestsPage />} />
            <Route path="/proposals" element={<ProposalsPage />} />
            <Route path="/submit-proposal/:requestId" element={<SubmitProposalPage />} />
            <Route path="*" element={<Navigate to="/requests" replace />} />
          </Routes>
        );
      
      case 'Internal':
        return (
          <Routes>
            <Route path="/dashboard" element={<AdminDashboardPage />} />
            <Route path="/proposals-review/:requestId" element={<ProposalsReviewPage />} />
            <Route path="/users" element={<UsersPage />} />
            <Route path="/settings" element={<SettingsPage />} />
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        );
      
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
