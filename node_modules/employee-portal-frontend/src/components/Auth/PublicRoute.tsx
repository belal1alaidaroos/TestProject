import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../../stores/AuthProvider';

interface PublicRouteProps {
  children: React.ReactNode;
}

const PublicRoute: React.FC<PublicRouteProps> = ({ children }) => {
  const { isAuthenticated, user } = useAuth();

  // If user is authenticated, redirect based on their role
  if (isAuthenticated && user) {
    const redirectPath = user.roles?.some(role => 
      role.name === 'admin' || role.name === 'internal'
    ) ? '/admin' : 
    user.roles?.some(role => role.name === 'agency') ? '/agency/requests' : '/workers';
    
    return <Navigate to={redirectPath} replace />;
  }

  // If not authenticated, show the public route
  return <>{children}</>;
};

export default PublicRoute;