import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '../../stores/authStore';

interface ProtectedRouteProps {
  children: React.ReactNode;
  requiredUserType?: 'Customer' | 'Agency' | 'Internal';
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ 
  children, 
  requiredUserType 
}) => {
  const { isAuthenticated, user } = useAuthStore();
  const location = useLocation();

  if (!isAuthenticated) {
    // Redirect to login with return URL
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (requiredUserType && user?.user_type !== requiredUserType) {
    // Redirect to appropriate portal based on user type
    switch (user?.user_type) {
      case 'Customer':
        return <Navigate to="/workers" replace />;
      case 'Agency':
        return <Navigate to="/requests" replace />;
      case 'Internal':
        return <Navigate to="/dashboard" replace />;
      default:
        return <Navigate to="/login" replace />;
    }
  }

  return <>{children}</>;
};

export default ProtectedRoute;