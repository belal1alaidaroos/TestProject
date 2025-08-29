import React from 'react';
import { useTranslation } from 'react-i18next';
import { useAuthStore } from '../stores/authStore';
import { useLanguageStore } from '../stores/languageStore';

const SimpleDashboard: React.FC = () => {
  const { t } = useTranslation();
  const { user, logout } = useAuthStore();
  const { language, toggleLanguage } = useLanguageStore();

  const handleLogout = async () => {
    try {
      await logout();
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            {/* Logo */}
            <div className="flex items-center">
              <h1 className="text-xl font-semibold text-gray-900">
                Worker Management System
              </h1>
            </div>

            {/* Right side */}
            <div className="flex items-center space-x-4">
              {/* Language Toggle */}
              <button
                onClick={toggleLanguage}
                className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                {language === 'en' ? 'العربية' : 'English'}
              </button>

              {/* User Menu */}
              {user && (
                <div className="relative">
                  <div className="flex items-center space-x-3">
                    <span className="text-sm text-gray-700">
                      Welcome, {user.name}
                    </span>
                    <span className="text-sm text-gray-500">
                      ({user.user_type})
                    </span>
                    <button
                      onClick={handleLogout}
                      className="text-sm text-gray-500 hover:text-gray-700"
                    >
                      {t('common.logout')}
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div className="px-4 py-6 sm:px-0">
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-2xl font-bold text-gray-900 mb-4">
              Dashboard
            </h2>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {/* User Info Card */}
              <div className="bg-blue-50 rounded-lg p-6">
                <h3 className="text-lg font-semibold text-blue-900 mb-2">
                  User Information
                </h3>
                <div className="space-y-2 text-sm text-blue-800">
                  <p><strong>Name:</strong> {user?.name}</p>
                  <p><strong>Email:</strong> {user?.email}</p>
                  <p><strong>Phone:</strong> {user?.phone}</p>
                  <p><strong>Type:</strong> {user?.user_type}</p>
                </div>
              </div>

              {/* System Status Card */}
              <div className="bg-green-50 rounded-lg p-6">
                <h3 className="text-lg font-semibold text-green-900 mb-2">
                  System Status
                </h3>
                <div className="space-y-2 text-sm text-green-800">
                  <p>✅ Frontend is running</p>
                  <p>✅ Authentication working</p>
                  <p>✅ Internationalization active</p>
                  <p>🌐 Language: {language === 'en' ? 'English' : 'العربية'}</p>
                </div>
              </div>

              {/* Quick Actions Card */}
              <div className="bg-purple-50 rounded-lg p-6">
                <h3 className="text-lg font-semibold text-purple-900 mb-2">
                  Quick Actions
                </h3>
                <div className="space-y-2">
                  <button
                    onClick={toggleLanguage}
                    className="w-full text-left text-sm text-purple-800 hover:text-purple-900"
                  >
                    🔄 Switch Language
                  </button>
                  <button
                    onClick={handleLogout}
                    className="w-full text-left text-sm text-purple-800 hover:text-purple-900"
                  >
                    🚪 Logout
                  </button>
                </div>
              </div>
            </div>

            {/* Welcome Message */}
            <div className="mt-8 bg-gray-50 rounded-lg p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                Welcome to Worker Management System
              </h3>
              <p className="text-gray-600">
                This is a simplified dashboard to demonstrate that the frontend is working correctly. 
                The system includes authentication, internationalization, and basic routing functionality.
              </p>
              <p className="text-gray-600 mt-2">
                User Type: <span className="font-semibold">{user?.user_type}</span>
              </p>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default SimpleDashboard;