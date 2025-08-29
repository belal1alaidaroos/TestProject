import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { api } from '../services/api';

interface UserRole {
  name: string;
  display_name: string;
}

interface User {
  id: string;
  name: string;
  email: string;
  phone: string;
  user_type: 'Customer' | 'Agency' | 'Internal';
  customer?: unknown;
  agency?: unknown;
  roles?: UserRole[];
}

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
}

interface AuthActions {
  login: (user: User, token: string) => void;
  loginWithOtp: (phone: string, code: string) => Promise<void>;
  loginWithEmail: (email: string, password: string, portalType: string) => Promise<void>;
  logout: () => void;
  setUser: (user: User) => void;
  setToken: (token: string) => void;
  clearError: () => void;
  checkAuth: () => Promise<void>;
}

type AuthStore = AuthState & AuthActions;

export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      // State
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,

      // Actions
      login: (user: User, token: string) => {
        set({
          user,
          token,
          isAuthenticated: true,
          isLoading: false,
          error: null,
        });
        
        // Set token in API headers
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      },

      loginWithOtp: async (phone: string, code: string) => {
        set({ isLoading: true, error: null });
        
        try {
          const response = await api.post('/auth/verify-otp', { phone, code });
          
          if (response.data.success) {
            const { user, token } = response.data.data;
            
            set({
              user,
              token,
              isAuthenticated: true,
              isLoading: false,
              error: null,
            });
            
            // Set token in API headers
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
          } else {
            set({
              isLoading: false,
              error: response.data.message || 'Login failed',
            });
          }
        } catch (error: unknown) {
          const errorMessage = error instanceof Error ? error.message : 'Login failed';
          set({
            isLoading: false,
            error: errorMessage,
          });
        }
      },

      loginWithEmail: async (email: string, password: string, portalType: string) => {
        set({ isLoading: true, error: null });
        
        try {
          const response = await api.post('/auth/email-login', { email, password, portal_type: portalType });
          
          if (response.data.success) {
            const { user, token } = response.data.data;
            
            set({
              user,
              token,
              isAuthenticated: true,
              isLoading: false,
              error: null,
            });
            
            // Set token in API headers
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
          } else {
            set({
              isLoading: false,
              error: response.data.message || 'Login failed',
            });
          }
        } catch (error: unknown) {
          const errorMessage = error instanceof Error ? error.message : 'Login failed';
          set({
            isLoading: false,
            error: errorMessage,
          });
        }
      },

      logout: () => {
        set({
          user: null,
          token: null,
          isAuthenticated: false,
          isLoading: false,
          error: null,
        });
        
        // Clear token from API headers
        delete api.defaults.headers.common['Authorization'];
      },

      setUser: (user: User) => {
        set({ user });
      },

      setToken: (token: string) => {
        set({ token, isAuthenticated: true });
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      },

      clearError: () => {
        set({ error: null });
      },

      checkAuth: async () => {
        const { token } = get();
        
        if (!token) {
          set({ isAuthenticated: false });
          return;
        }

        try {
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
          const response = await api.get('/auth/me');
          
          if (response.data.success) {
            set({
              user: response.data.data.user,
              isAuthenticated: true,
            });
          } else {
            set({ isAuthenticated: false });
          }
        } catch {
          set({ isAuthenticated: false });
          delete api.defaults.headers.common['Authorization'];
        }
      },
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);