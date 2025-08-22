import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { adminAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface User {
  id: string;
  name_en: string;
  name_ar: string;
  email: string;
  phone: string;
  user_type: 'Customer' | 'Agency' | 'Internal';
  status: 'Active' | 'Inactive' | 'Suspended';
  created_at: string;
  last_login_at?: string;
  roles: Array<{
    id: string;
    name_en: string;
    name_ar: string;
  }>;
  profile?: {
    company_name?: string;
    address?: string;
    city?: string;
    country?: string;
  };
}

interface Filters {
  user_type?: string;
  status?: string;
  search?: string;
}

const UsersPage: React.FC = () => {
  const { t } = useTranslation();
  
  const [users, setUsers] = useState<User[]>([]);
  const [roles, setRoles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState<Filters>({});
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [showUserModal, setShowUserModal] = useState(false);
  const [showRoleModal, setShowRoleModal] = useState(false);
  const [processing, setProcessing] = useState(false);

  // Load roles for assignment
  useEffect(() => {
    const loadRoles = async () => {
      try {
        const response = await adminAPI.getRoles();
        setRoles(response.data.data || []);
      } catch (error) {
        console.error('Failed to load roles:', error);
      }
    };

    loadRoles();
  }, []);

  // Load users
  useEffect(() => {
    const loadUsers = async () => {
      try {
        setLoading(true);
        setError(null);
        
        const params = {
          ...filters,
          page: currentPage,
          per_page: 10,
        };
        
        const response = await adminAPI.getUsers(params);
        
        if (response.data.success) {
          setUsers(response.data.data.data || []);
          setTotalPages(response.data.data.last_page || 1);
        } else {
          setError('Failed to load users');
        }
      } catch (error: any) {
        console.error('Failed to load users:', error);
        setError(error.response?.data?.message || 'Failed to load users');
      } finally {
        setLoading(false);
      }
    };

    loadUsers();
  }, [filters, currentPage]);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Active':
        return 'bg-green-100 text-green-800';
      case 'Inactive':
        return 'bg-gray-100 text-gray-800';
      case 'Suspended':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    return t(`status.${status.toLowerCase()}`);
  };

  const getUserTypeColor = (userType: string) => {
    switch (userType) {
      case 'Customer':
        return 'bg-blue-100 text-blue-800';
      case 'Agency':
        return 'bg-purple-100 text-purple-800';
      case 'Internal':
        return 'bg-orange-100 text-orange-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getUserTypeText = (userType: string) => {
    return t(`user_type.${userType.toLowerCase()}`);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const handleFilterChange = (newFilters: Filters) => {
    setFilters(newFilters);
    setCurrentPage(1);
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };

  const handleEditUser = (user: User) => {
    setSelectedUser(user);
    setShowUserModal(true);
  };

  const handleAssignRoles = (user: User) => {
    setSelectedUser(user);
    setShowRoleModal(true);
  };

  const handleToggleStatus = async (userId: string, currentStatus: string) => {
    const newStatus = currentStatus === 'Active' ? 'Suspended' : 'Active';
    
    try {
      setProcessing(true);
      
      const response = await adminAPI.updateUserStatus(userId, { status: newStatus });
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t('admin.user_status_updated'),
        });
        
        // Reload users
        const reloadResponse = await adminAPI.getUsers({
          ...filters,
          page: currentPage,
          per_page: 10,
        });
        
        if (reloadResponse.data.success) {
          setUsers(reloadResponse.data.data.data || []);
        }
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to update user status',
      });
    } finally {
      setProcessing(false);
    }
  };

  const handleUpdateUser = async (userData: any) => {
    if (!selectedUser) return;

    try {
      setProcessing(true);
      
      const response = await adminAPI.updateUser(selectedUser.id, userData);
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t('admin.user_updated'),
        });
        
        // Reload users
        const reloadResponse = await adminAPI.getUsers({
          ...filters,
          page: currentPage,
          per_page: 10,
        });
        
        if (reloadResponse.data.success) {
          setUsers(reloadResponse.data.data.data || []);
        }
        
        setShowUserModal(false);
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to update user',
      });
    } finally {
      setProcessing(false);
    }
  };

  const handleUpdateRoles = async (roleIds: string[]) => {
    if (!selectedUser) return;

    try {
      setProcessing(true);
      
      const response = await adminAPI.updateUserRoles(selectedUser.id, { role_ids: roleIds });
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t('admin.user_roles_updated'),
        });
        
        // Reload users
        const reloadResponse = await adminAPI.getUsers({
          ...filters,
          page: currentPage,
          per_page: 10,
        });
        
        if (reloadResponse.data.success) {
          setUsers(reloadResponse.data.data.data || []);
        }
        
        setShowRoleModal(false);
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to update user roles',
      });
    } finally {
      setProcessing(false);
    }
  };

  if (loading && users.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">
          {t('admin.users_management')}
        </h1>
        <button className="btn-primary">
          {t('admin.add_user')}
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label className="form-label">{t('admin.user_type')}</label>
            <select
              value={filters.user_type || ''}
              onChange={(e) => handleFilterChange({ ...filters, user_type: e.target.value || undefined })}
              className="input-field"
            >
              <option value="">{t('common.all')}</option>
              <option value="Customer">{t('user_type.customer')}</option>
              <option value="Agency">{t('user_type.agency')}</option>
              <option value="Internal">{t('user_type.internal')}</option>
            </select>
          </div>
          
          <div>
            <label className="form-label">{t('common.status')}</label>
            <select
              value={filters.status || ''}
              onChange={(e) => handleFilterChange({ ...filters, status: e.target.value || undefined })}
              className="input-field"
            >
              <option value="">{t('common.all')}</option>
              <option value="Active">{t('status.active')}</option>
              <option value="Inactive">{t('status.inactive')}</option>
              <option value="Suspended">{t('status.suspended')}</option>
            </select>
          </div>
          
          <div>
            <label className="form-label">{t('common.search')}</label>
            <input
              type="text"
              value={filters.search || ''}
              onChange={(e) => handleFilterChange({ ...filters, search: e.target.value || undefined })}
              className="input-field"
              placeholder={t('admin.search_users')}
            />
          </div>
          
          <div className="flex items-end">
            <button
              onClick={() => handleFilterChange({})}
              className="btn-secondary w-full"
            >
              {t('common.clear_filters')}
            </button>
          </div>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="rounded-md bg-red-50 p-4">
          <div className="text-sm text-red-700">{error}</div>
        </div>
      )}

      {/* Users List */}
      {users.length > 0 ? (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {t('admin.user')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {t('admin.contact')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {t('admin.type')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {t('common.status')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {t('admin.roles')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {t('common.created_at')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {t('common.actions')}
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {users.map((user) => (
                  <tr key={user.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        <div className="flex-shrink-0 h-10 w-10">
                          <div className="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                            <span className="text-sm font-medium text-gray-700">
                              {user.name_en.charAt(0).toUpperCase()}
                            </span>
                          </div>
                        </div>
                        <div className="ml-4">
                          <div className="text-sm font-medium text-gray-900">
                            {user.name_en}
                          </div>
                          <div className="text-sm text-gray-500">
                            {user.name_ar}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-900">{user.email}</div>
                      <div className="text-sm text-gray-500">{user.phone}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getUserTypeColor(user.user_type)}`}>
                        {getUserTypeText(user.user_type)}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(user.status)}`}>
                        {getStatusText(user.status)}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex flex-wrap gap-1">
                        {user.roles.map((role) => (
                          <span
                            key={role.id}
                            className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                          >
                            {role.name_en}
                          </span>
                        ))}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {formatDate(user.created_at)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div className="flex space-x-2">
                        <button
                          onClick={() => handleEditUser(user)}
                          className="text-primary-600 hover:text-primary-900"
                        >
                          {t('common.edit')}
                        </button>
                        <button
                          onClick={() => handleAssignRoles(user)}
                          className="text-blue-600 hover:text-blue-900"
                        >
                          {t('admin.assign_roles')}
                        </button>
                        <button
                          onClick={() => handleToggleStatus(user.id, user.status)}
                          disabled={processing}
                          className={`${
                            user.status === 'Active' 
                              ? 'text-red-600 hover:text-red-900' 
                              : 'text-green-600 hover:text-green-900'
                          }`}
                        >
                          {user.status === 'Active' ? t('admin.suspend') : t('admin.activate')}
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      ) : (
        <div className="text-center py-12">
          <p className="text-gray-500">{t('common.no_data')}</p>
        </div>
      )}

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex justify-center">
          <nav className="flex items-center space-x-2">
            <button
              onClick={() => handlePageChange(currentPage - 1)}
              disabled={currentPage === 1}
              className="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {t('common.previous')}
            </button>
            
            {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
              <button
                key={page}
                onClick={() => handlePageChange(page)}
                className={`px-3 py-2 text-sm font-medium rounded-md ${
                  currentPage === page
                    ? 'bg-primary-600 text-white'
                    : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'
                }`}
              >
                {page}
              </button>
            ))}
            
            <button
              onClick={() => handlePageChange(currentPage + 1)}
              disabled={currentPage === totalPages}
              className="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {t('common.next')}
            </button>
          </nav>
        </div>
      )}

      {/* User Edit Modal */}
      {showUserModal && selectedUser && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {t('admin.edit_user')}
              </h3>
              
              <div className="space-y-4">
                <div>
                  <label className="form-label">{t('admin.name_en')}</label>
                  <input
                    type="text"
                    defaultValue={selectedUser.name_en}
                    className="input-field"
                  />
                </div>
                
                <div>
                  <label className="form-label">{t('admin.name_ar')}</label>
                  <input
                    type="text"
                    defaultValue={selectedUser.name_ar}
                    className="input-field"
                  />
                </div>
                
                <div>
                  <label className="form-label">{t('admin.email')}</label>
                  <input
                    type="email"
                    defaultValue={selectedUser.email}
                    className="input-field"
                  />
                </div>
                
                <div>
                  <label className="form-label">{t('admin.phone')}</label>
                  <input
                    type="tel"
                    defaultValue={selectedUser.phone}
                    className="input-field"
                  />
                </div>
              </div>
              
              <div className="flex justify-end space-x-3 mt-6">
                <button
                  onClick={() => setShowUserModal(false)}
                  className="btn-secondary"
                  disabled={processing}
                >
                  {t('common.cancel')}
                </button>
                <button
                  onClick={() => handleUpdateUser({})}
                  className="btn-primary"
                  disabled={processing}
                >
                  {processing ? t('common.loading') : t('common.save')}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Role Assignment Modal */}
      {showRoleModal && selectedUser && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {t('admin.assign_roles')}
              </h3>
              
              <div className="mb-4">
                <p className="text-sm text-gray-600">
                  {t('admin.user')}: {selectedUser.name_en}
                </p>
              </div>
              
              <div className="space-y-2">
                {roles.map((role: any) => (
                  <label key={role.id} className="flex items-center">
                    <input
                      type="checkbox"
                      defaultChecked={selectedUser.roles.some(r => r.id === role.id)}
                      className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                    />
                    <span className="ml-2 text-sm text-gray-900">{role.name_en}</span>
                  </label>
                ))}
              </div>
              
              <div className="flex justify-end space-x-3 mt-6">
                <button
                  onClick={() => setShowRoleModal(false)}
                  className="btn-secondary"
                  disabled={processing}
                >
                  {t('common.cancel')}
                </button>
                <button
                  onClick={() => handleUpdateRoles([])}
                  className="btn-primary"
                  disabled={processing}
                >
                  {processing ? t('common.loading') : t('common.save')}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Toast */}
      {toast && (
        <Toast
          type={toast.type}
          message={toast.message}
          onClose={() => setToast(null)}
        />
      )}
    </div>
  );
};

export default UsersPage;