import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { XMarkIcon, CheckIcon } from '@heroicons/react/24/outline';
import { api } from '../services/api';

interface Role {
  id: string;
  name: string;
  description: string;
  permissions: string[];
}

interface User {
  id: string;
  name: string;
  email: string;
  user_type: string;
  roles: Role[];
}

interface RoleAssignmentModalProps {
  user: User | null;
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

const RoleAssignmentModal: React.FC<RoleAssignmentModalProps> = ({
  user,
  isOpen,
  onClose,
  onSuccess,
}) => {
  const { t } = useTranslation();
  const [roles, setRoles] = useState<Role[]>([]);
  const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
  const [primaryRole, setPrimaryRole] = useState<string>('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (isOpen) {
      loadRoles();
      if (user) {
        setSelectedRoles(user.roles.map(role => role.id));
        const primaryRoleObj = user.roles.find(role => role.is_primary);
        setPrimaryRole(primaryRoleObj?.id || '');
      }
    }
  }, [isOpen, user]);

  const loadRoles = async () => {
    try {
      const response = await api.get('/admin/roles');
      if (response.data.success) {
        setRoles(response.data.data);
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('roles.loadError'));
    }
  };

  const handleRoleToggle = (roleId: string) => {
    setSelectedRoles(prev => {
      if (prev.includes(roleId)) {
        // Remove role
        const newRoles = prev.filter(id => id !== roleId);
        // If this was the primary role, clear it
        if (primaryRole === roleId) {
          setPrimaryRole('');
        }
        return newRoles;
      } else {
        // Add role
        return [...prev, roleId];
      }
    });
  };

  const handlePrimaryRoleChange = (roleId: string) => {
    setPrimaryRole(roleId);
  };

  const handleSave = async () => {
    if (!user || selectedRoles.length === 0) {
      setError(t('roles.selectAtLeastOne'));
      return;
    }

    try {
      setIsLoading(true);
      setError(null);

      const response = await api.post('/admin/role-management/sync-roles', {
        user_id: user.id,
        role_ids: selectedRoles,
        primary_role_id: primaryRole || null,
      });

      if (response.data.success) {
        onSuccess();
        onClose();
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('roles.saveError'));
    } finally {
      setIsLoading(false);
    }
  };

  const handleRemoveRole = async (roleId: string) => {
    if (!user) return;

    try {
      setIsLoading(true);
      setError(null);

      const response = await api.post('/admin/role-management/remove-role', {
        user_id: user.id,
        role_id: roleId,
      });

      if (response.data.success) {
        onSuccess();
        // Update local state
        setSelectedRoles(prev => prev.filter(id => id !== roleId));
        if (primaryRole === roleId) {
          setPrimaryRole('');
        }
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('roles.removeError'));
    } finally {
      setIsLoading(false);
    }
  };

  if (!isOpen || !user) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">
            {t('roles.assignRoles')} - {user.name}
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600"
          >
            <XMarkIcon className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6">
          {/* User Info */}
          <div className="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 className="font-medium text-gray-900 mb-2">{t('roles.userInfo')}</h3>
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span className="text-gray-600">{t('roles.name')}:</span>
                <span className="ml-2 font-medium">{user.name}</span>
              </div>
              <div>
                <span className="text-gray-600">{t('roles.email')}:</span>
                <span className="ml-2 font-medium">{user.email}</span>
              </div>
              <div>
                <span className="text-gray-600">{t('roles.userType')}:</span>
                <span className="ml-2 font-medium">{user.user_type}</span>
              </div>
              <div>
                <span className="text-gray-600">{t('roles.currentRoles')}:</span>
                <span className="ml-2 font-medium">{user.roles.length}</span>
              </div>
            </div>
          </div>

          {/* Error Message */}
          {error && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
              <p className="text-sm text-red-600">{error}</p>
            </div>
          )}

          {/* Available Roles */}
          <div className="mb-6">
            <h3 className="font-medium text-gray-900 mb-4">{t('roles.availableRoles')}</h3>
            <div className="space-y-3">
              {roles.map((role) => (
                <div
                  key={role.id}
                  className={`p-4 border rounded-lg transition-colors ${
                    selectedRoles.includes(role.id)
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  }`}
                >
                  <div className="flex items-start space-x-3">
                    <input
                      type="checkbox"
                      checked={selectedRoles.includes(role.id)}
                      onChange={() => handleRoleToggle(role.id)}
                      className="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    
                    <div className="flex-1">
                      <div className="flex items-center justify-between">
                        <h4 className="font-medium text-gray-900">{role.name}</h4>
                        {selectedRoles.includes(role.id) && (
                          <div className="flex items-center space-x-2">
                            <input
                              type="radio"
                              name="primaryRole"
                              checked={primaryRole === role.id}
                              onChange={() => handlePrimaryRoleChange(role.id)}
                              className="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            />
                            <span className="text-sm text-gray-600">{t('roles.primary')}</span>
                          </div>
                        )}
                      </div>
                      
                      {role.description && (
                        <p className="text-sm text-gray-600 mt-1">{role.description}</p>
                      )}
                      
                      {role.permissions && role.permissions.length > 0 && (
                        <div className="mt-2">
                          <p className="text-xs text-gray-500 mb-1">{t('roles.permissions')}:</p>
                          <div className="flex flex-wrap gap-1">
                            {role.permissions.slice(0, 3).map((permission, index) => (
                              <span
                                key={index}
                                className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                              >
                                {permission}
                              </span>
                            ))}
                            {role.permissions.length > 3 && (
                              <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                +{role.permissions.length - 3} {t('roles.more')}
                              </span>
                            )}
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Current Roles */}
          {user.roles.length > 0 && (
            <div className="mb-6">
              <h3 className="font-medium text-gray-900 mb-4">{t('roles.currentRoles')}</h3>
              <div className="space-y-2">
                {user.roles.map((role) => (
                  <div
                    key={role.id}
                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <div className="flex items-center space-x-3">
                      <CheckIcon className="w-5 h-5 text-green-500" />
                      <div>
                        <span className="font-medium text-gray-900">{role.name}</span>
                        {role.is_primary && (
                          <span className="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                            {t('roles.primary')}
                          </span>
                        )}
                      </div>
                    </div>
                    
                    <button
                      onClick={() => handleRemoveRole(role.id)}
                      disabled={isLoading}
                      className="text-red-600 hover:text-red-800 text-sm font-medium disabled:opacity-50"
                    >
                      {t('roles.remove')}
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
          <button
            onClick={onClose}
            disabled={isLoading}
            className="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 disabled:opacity-50"
          >
            {t('common.cancel')}
          </button>
          
          <button
            onClick={handleSave}
            disabled={isLoading || selectedRoles.length === 0}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
          >
            {isLoading ? (
              <>
                <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                <span>{t('common.saving')}</span>
              </>
            ) : (
              <span>{t('common.save')}</span>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

export default RoleAssignmentModal;