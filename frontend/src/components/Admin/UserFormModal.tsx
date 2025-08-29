import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { X, AlertTriangle } from 'lucide-react';
import { adminAPI, lookupsAPI } from '../../services/api';
import LoadingSpinner from '../LoadingSpinner';

interface UserFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  user?: any;
  mode: 'create' | 'edit';
}

const UserFormModal: React.FC<UserFormModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
  user,
  mode
}) => {
  const { t } = useTranslation();
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // Form data
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    user_type: 'Customer',
    roles: [] as string[],
    is_active: true,
    // Customer specific
    customer_name_en: '',
    customer_name_ar: '',
    customer_type: 'Individual',
    // Agency specific
    agency_name_en: '',
    agency_name_ar: '',
    agency_license_number: '',
    agency_phone: '',
    agency_email: '',
    agency_address: ''
  });

  const availableRoles = [
    { value: 'admin', label: t('roles.admin') },
    { value: 'internal', label: t('roles.internal') },
    { value: 'customer', label: t('roles.customer') },
    { value: 'agency', label: t('roles.agency') },
    { value: 'employee', label: t('roles.employee') }
  ];

  useEffect(() => {
    if (isOpen && mode === 'edit' && user) {
      setFormData({
        name: user.name || '',
        email: user.email || '',
        phone: user.phone || '',
        password: '',
        password_confirmation: '',
        user_type: user.user_type || 'Customer',
        roles: user.roles?.map((r: any) => r.name) || [],
        is_active: user.is_active !== false,
        customer_name_en: user.customer?.name_en || '',
        customer_name_ar: user.customer?.name_ar || '',
        customer_type: user.customer?.customer_type || 'Individual',
        agency_name_en: user.agency?.name_en || '',
        agency_name_ar: user.agency?.name_ar || '',
        agency_license_number: user.agency?.license_number || '',
        agency_phone: user.agency?.phone || '',
        agency_email: user.agency?.email || '',
        agency_address: user.agency?.address || ''
      });
    }
  }, [isOpen, mode, user]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError(null);

    // Validate passwords for create mode
    if (mode === 'create' && formData.password !== formData.password_confirmation) {
      setError(t('admin.passwords_not_match'));
      setSubmitting(false);
      return;
    }

    try {
      const payload: any = {
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        user_type: formData.user_type,
        roles: formData.roles,
        is_active: formData.is_active
      };

      if (mode === 'create' || formData.password) {
        payload.password = formData.password;
        payload.password_confirmation = formData.password_confirmation;
      }

      // Add type-specific data
      if (formData.user_type === 'Customer') {
        payload.customer = {
          name_en: formData.customer_name_en,
          name_ar: formData.customer_name_ar,
          customer_type: formData.customer_type
        };
      } else if (formData.user_type === 'Agency') {
        payload.agency = {
          name_en: formData.agency_name_en,
          name_ar: formData.agency_name_ar,
          license_number: formData.agency_license_number,
          phone: formData.agency_phone,
          email: formData.agency_email,
          address: formData.agency_address
        };
      }

      if (mode === 'create') {
        await adminAPI.createUser(payload);
      } else {
        await adminAPI.updateUser(user.id, payload);
      }
      
      onSuccess();
      onClose();
    } catch (error: any) {
      setError(error.response?.data?.message || `Failed to ${mode} user`);
    } finally {
      setSubmitting(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value, type } = e.target;
    if (type === 'checkbox') {
      const checked = (e.target as HTMLInputElement).checked;
      setFormData(prev => ({
        ...prev,
        [name]: checked
      }));
    } else {
      setFormData(prev => ({
        ...prev,
        [name]: value
      }));
    }
  };

  const handleRoleToggle = (role: string) => {
    setFormData(prev => ({
      ...prev,
      roles: prev.roles.includes(role) 
        ? prev.roles.filter(r => r !== role)
        : [...prev.roles, role]
    }));
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div className="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        {/* Header */}
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-medium text-gray-900">
            {mode === 'create' ? t('admin.create_user') : t('admin.edit_user')}
          </h3>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-500"
          >
            <X className="h-6 w-6" />
          </button>
        </div>

        {loading ? (
          <LoadingSpinner />
        ) : (
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Basic Information */}
            <div>
              <h4 className="text-md font-medium text-gray-900 mb-4">{t('admin.basic_information')}</h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('common.name')} *
                  </label>
                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('common.email')} *
                  </label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('common.phone')} *
                  </label>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('admin.user_type')} *
                  </label>
                  <select
                    name="user_type"
                    value={formData.user_type}
                    onChange={handleChange}
                    required
                    disabled={mode === 'edit'}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  >
                    <option value="Customer">{t('user_types.customer')}</option>
                    <option value="Agency">{t('user_types.agency')}</option>
                    <option value="Internal">{t('user_types.internal')}</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('common.password')} {mode === 'create' && '*'}
                  </label>
                  <input
                    type="password"
                    name="password"
                    value={formData.password}
                    onChange={handleChange}
                    required={mode === 'create'}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    placeholder={mode === 'edit' ? t('admin.leave_blank_to_keep') : ''}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('common.confirm_password')} {mode === 'create' && '*'}
                  </label>
                  <input
                    type="password"
                    name="password_confirmation"
                    value={formData.password_confirmation}
                    onChange={handleChange}
                    required={mode === 'create'}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>
              </div>
            </div>

            {/* Customer Information */}
            {formData.user_type === 'Customer' && (
              <div>
                <h4 className="text-md font-medium text-gray-900 mb-4">{t('admin.customer_information')}</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      {t('common.name_en')} *
                    </label>
                    <input
                      type="text"
                      name="customer_name_en"
                      value={formData.customer_name_en}
                      onChange={handleChange}
                      required
                      className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      {t('common.name_ar')} *
                    </label>
                    <input
                      type="text"
                      name="customer_name_ar"
                      value={formData.customer_name_ar}
                      onChange={handleChange}
                      required
                      className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      {t('customer.type')} *
                    </label>
                    <select
                      name="customer_type"
                      value={formData.customer_type}
                      onChange={handleChange}
                      required
                      className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    >
                      <option value="Individual">{t('customer.individual')}</option>
                      <option value="Company">{t('customer.company')}</option>
                    </select>
                  </div>
                </div>
              </div>
            )}

            {/* Agency Information */}
            {formData.user_type === 'Agency' && (
              <div>
                <h4 className="text-md font-medium text-gray-900 mb-4">{t('admin.agency_information')}</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      {t('common.name_en')} *
                    </label>
                    <input
                      type="text"
                      name="agency_name_en"
                      value={formData.agency_name_en}
                      onChange={handleChange}
                      required
                      className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      {t('common.name_ar')} *
                    </label>
                    <input
                      type="text"
                      name="agency_name_ar"
                      value={formData.agency_name_ar}
                      onChange={handleChange}
                      required
                      className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      {t('agency.license_number')} *
                    </label>
                    <input
                      type="text"
                      name="agency_license_number"
                      value={formData.agency_license_number}
                      onChange={handleChange}
                      required
                      className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      {t('agency.phone')} *
                    </label>
                    <input
                      type="tel"
                      name="agency_phone"
                      value={formData.agency_phone}
                      onChange={handleChange}
                      required
                      className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      {t('agency.email')} *
                    </label>
                    <input
                      type="email"
                      name="agency_email"
                      value={formData.agency_email}
                      onChange={handleChange}
                      required
                      className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      {t('agency.address')} *
                    </label>
                    <textarea
                      name="agency_address"
                      value={formData.agency_address}
                      onChange={handleChange}
                      required
                      rows={2}
                      className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>
                </div>
              </div>
            )}

            {/* Roles and Status */}
            <div>
              <h4 className="text-md font-medium text-gray-900 mb-4">{t('admin.roles_and_status')}</h4>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    {t('admin.user_roles')}
                  </label>
                  <div className="space-y-2">
                    {availableRoles.map(role => (
                      <label key={role.value} className="flex items-center">
                        <input
                          type="checkbox"
                          checked={formData.roles.includes(role.value)}
                          onChange={() => handleRoleToggle(role.value)}
                          className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                        <span className="ml-2 text-sm text-gray-700">{role.label}</span>
                      </label>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="flex items-center">
                    <input
                      type="checkbox"
                      name="is_active"
                      checked={formData.is_active}
                      onChange={handleChange}
                      className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                    <span className="ml-2 text-sm text-gray-700">{t('admin.user_is_active')}</span>
                  </label>
                </div>
              </div>
            </div>

            {/* Error message */}
            {error && (
              <div className="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
                {error}
              </div>
            )}

            {/* Footer */}
            <div className="flex justify-end space-x-3">
              <button
                type="button"
                onClick={onClose}
                className="btn-secondary"
                disabled={submitting}
              >
                {t('common.cancel')}
              </button>
              <button
                type="submit"
                className="btn-primary"
                disabled={submitting}
              >
                {submitting ? t('common.saving') : t('common.save')}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
};

export default UserFormModal;