import { useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuthStore } from './stores/authStore';
import { useLanguageStore } from './stores/languageStore';

// Auth Pages
import LoginPage from './pages/auth/LoginPage';
import OtpVerificationPage from './pages/auth/OtpVerificationPage';

// Simple Dashboard
import SimpleDashboard from './pages/SimpleDashboard';

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

  return (
    <Router>
      <div className="min-h-screen bg-gray-50">
        <Routes>
          {!isAuthenticated ? (
            <>
              <Route path="/login" element={<LoginPage />} />
              <Route path="/verify-otp" element={<OtpVerificationPage />} />
              <Route path="*" element={<Navigate to="/login" replace />} />
            </>
          ) : (
            <>
              <Route path="/dashboard" element={<SimpleDashboard />} />
              <Route path="*" element={<Navigate to="/dashboard" replace />} />
            </>
          )}
        </Routes>
      </div>
    </Router>
  );
}

export default App;
