import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '../../stores/authStore';

interface ProtectedRouteProps {
  children: React.ReactNode;
  requiredUserType?: 'Customer' | 'Agency' | 'Internal';
  allowedRoles?: string[];
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ 
  children, 
  requiredUserType,
  allowedRoles
}) => {
  const { isAuthenticated, user } = useAuthStore();
  const location = useLocation();

  if (!isAuthenticated) {
    // Redirect to login with return URL
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Check role-based access if allowedRoles is specified
  if (allowedRoles && allowedRoles.length > 0) {
    const hasRequiredRole = user?.roles?.some(role => 
      allowedRoles.includes(role.name)
    );
    
    if (!hasRequiredRole) {
      // Redirect to appropriate portal based on user type
      switch (user?.user_type) {
        case 'Customer':
          return <Navigate to="/workers" replace />;
        case 'Agency':
          return <Navigate to="/agency/requests" replace />;
        case 'Internal':
          return <Navigate to="/admin" replace />;
        default:
          return <Navigate to="/login" replace />;
      }
    }
  }

  if (requiredUserType && user?.user_type !== requiredUserType) {
    // Redirect to appropriate portal based on user type
    switch (user?.user_type) {
      case 'Customer':
        return <Navigate to="/workers" replace />;
      case 'Agency':
        return <Navigate to="/agency/requests" replace />;
      case 'Internal':
        return <Navigate to="/admin" replace />;
      default:
        return <Navigate to="/login" replace />;
    }
  }

  return <>{children}</>;
};

export default ProtectedRoute;