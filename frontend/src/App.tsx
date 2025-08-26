import { Routes, Route, Navigate } from 'react-router-dom'
import { Suspense, lazy } from 'react'
import { useAuth } from './stores/AuthProvider'
import { useI18n } from './i18n'
import Layout from './components/Layout/Layout'
import LoadingSpinner from './components/UI/LoadingSpinner'
import ProtectedRoute from './components/Auth/ProtectedRoute'
import PublicRoute from './components/Auth/PublicRoute'

// Lazy load pages for better performance
const LoginPage = lazy(() => import('./pages/Auth/LoginPage'))
const WorkersPage = lazy(() => import('./pages/Customer/WorkersPage'))
const WorkerDetailPage = lazy(() => import('./pages/Customer/WorkerDetailPage'))
const ReservationsPage = lazy(() => import('./pages/Customer/ReservationsPage'))
const ContractsPage = lazy(() => import('./pages/Customer/ContractsPage'))
const AgencyRequestsPage = lazy(() => import('./pages/Agency/RequestsPage'))
const AgencyProposalsPage = lazy(() => import('./pages/Agency/ProposalsPage'))
const AdminDashboardPage = lazy(() => import('./pages/Admin/DashboardPage'))
const AdminProposalsPage = lazy(() => import('./pages/Admin/ProposalsPage'))
const AdminUsersPage = lazy(() => import('./pages/Admin/UsersPage'))
const NotFoundPage = lazy(() => import('./pages/NotFoundPage'))

function App() {
  const { isAuthenticated, user } = useAuth()
  const { t, locale, dir } = useI18n()

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
                <Layout />
              </ProtectedRoute>
            }
          >
            {/* Customer Portal Routes */}
            <Route
              path="/workers"
              element={
                <ProtectedRoute allowedRoles={['customer']}>
                  <WorkersPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/workers/:id"
              element={
                <ProtectedRoute allowedRoles={['customer']}>
                  <WorkerDetailPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/reservations"
              element={
                <ProtectedRoute allowedRoles={['customer']}>
                  <ReservationsPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/contracts"
              element={
                <ProtectedRoute allowedRoles={['customer']}>
                  <ContractsPage />
                </ProtectedRoute>
              }
            />

            {/* Agency Portal Routes */}
            <Route
              path="/agency/requests"
              element={
                <ProtectedRoute allowedRoles={['agency']}>
                  <AgencyRequestsPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/agency/proposals"
              element={
                <ProtectedRoute allowedRoles={['agency']}>
                  <AgencyProposalsPage />
                </ProtectedRoute>
              }
            />

            {/* Admin Portal Routes */}
            <Route
              path="/admin"
              element={
                <ProtectedRoute allowedRoles={['admin', 'internal']}>
                  <AdminDashboardPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/admin/proposals"
              element={
                <ProtectedRoute allowedRoles={['admin', 'internal']}>
                  <AdminProposalsPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/admin/users"
              element={
                <ProtectedRoute allowedRoles={['admin', 'internal']}>
                  <AdminUsersPage />
                </ProtectedRoute>
              }
            />

            {/* Default redirect based on user role */}
            <Route
              index
              element={
                <Navigate
                  to={
                    user?.roles?.includes('admin') || user?.roles?.includes('internal')
                      ? '/admin'
                      : user?.roles?.includes('agency')
                      ? '/agency/requests'
                      : '/workers'
                  }
                  replace
                />
              }
            />
          </Route>

          {/* 404 Page */}
          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </Suspense>
    </div>
  )
}

export default App
