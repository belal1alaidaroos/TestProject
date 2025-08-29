import { Routes, Route, Navigate } from 'react-router-dom'
import { Suspense, lazy } from 'react'
import { useAuth } from './stores/AuthProvider'
import { useI18n } from './i18n'
import LoadingSpinner from './components/LoadingSpinner'
import ProtectedRoute from './components/Auth/ProtectedRoute'
import PublicRoute from './components/Auth/PublicRoute'

// Lazy load only essential components for now
const LoginPage = lazy(() => import('./pages/auth/LoginPage'))
const AdminLayout = lazy(() => import('./components/Layout/AdminLayout'))
const CustomerLayout = lazy(() => import('./components/Layout/CustomerLayout'))
const AgencyLayout = lazy(() => import('./components/Layout/AgencyLayout'))

// Simple placeholder pages
const SimplePage = ({ title, userType }: { title: string; userType: string }) => (
  <div className="min-h-screen bg-gray-50 flex items-center justify-center">
    <div className="text-center">
      <h1 className="text-4xl font-bold text-gray-900 mb-4">{title}</h1>
      <p className="text-xl text-gray-600">Welcome to the {userType} Portal</p>
      <p className="text-lg text-gray-500 mt-2">This is a temporary placeholder page</p>
    </div>
  </div>
)

function App() {
  const { user } = useAuth()
  const { locale, dir } = useI18n()

  // Set document direction based on locale
  document.documentElement.dir = dir
  document.documentElement.lang = locale

  return (
    <div className="min-h-screen bg-gray-50">
      <Suspense fallback={<LoadingSpinner />}>
        <Routes>
          {/* Public Routes */}
          <Route
            path="/login"
            element={
              <PublicRoute>
                <LoginPage />
              </PublicRoute>
            }
          />

          {/* Protected Routes */}
          <Route
            path="/"
            element={
              <ProtectedRoute>
                <Navigate
                  to={
                    user?.roles?.some(role => ['admin', 'internal'].includes(role.name))
                      ? '/admin'
                      : user?.roles?.some(role => role.name === 'agency')
                      ? '/agency'
                      : '/customer'
                  }
                  replace
                />
              </ProtectedRoute>
            }
          />

          {/* Customer Portal */}
          <Route
            path="/customer"
            element={
              <ProtectedRoute allowedRoles={['customer']}>
                <CustomerLayout>
                  <SimplePage title="Customer Dashboard" userType="Customer" />
                </CustomerLayout>
              </ProtectedRoute>
            }
          />

          {/* Agency Portal */}
          <Route
            path="/agency"
            element={
              <ProtectedRoute allowedRoles={['agency']}>
                <AgencyLayout>
                  <SimplePage title="Agency Dashboard" userType="Agency" />
                </AgencyLayout>
              </ProtectedRoute>
            }
          />

          {/* Admin Portal */}
          <Route
            path="/admin"
            element={
              <ProtectedRoute allowedRoles={['admin', 'internal']}>
                <AdminLayout>
                  <SimplePage title="Admin Dashboard" userType="Admin" />
                </AdminLayout>
              </ProtectedRoute>
            }
          />

          {/* 404 Page */}
          <Route path="*" element={<SimplePage title="Page Not Found" userType="Unknown" />} />
        </Routes>
      </Suspense>
    </div>
  )
}

export default App
