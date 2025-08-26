import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { adminAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface SystemSetting {
  id: string;
  key: string;
  value: string;
  type: 'string' | 'number' | 'boolean' | 'json';
  category: string;
  description_en: string;
  description_ar: string;
  is_editable: boolean;
}

interface SettingsFormData {
  [key: string]: string | number | boolean;
}

const SettingsPage: React.FC = () => {
  const { t } = useTranslation();
  
  const [settings, setSettings] = useState<SystemSetting[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [formData, setFormData] = useState<SettingsFormData>({});
  const [activeTab, setActiveTab] = useState('general');

  // Load system settings
  useEffect(() => {
    const loadSettings = async () => {
      try {
        setLoading(true);
        setError(null);
        
        const response = await adminAPI.getSystemSettings();
        
        if (response.data.success) {
          const settingsData = response.data.data;
          setSettings(settingsData);
          
          // Initialize form data
          const initialFormData: SettingsFormData = {};
          settingsData.forEach((setting: SystemSetting) => {
            if (setting.type === 'boolean') {
              initialFormData[setting.key] = setting.value === 'true';
            } else if (setting.type === 'number') {
              initialFormData[setting.key] = parseFloat(setting.value) || 0;
            } else {
              initialFormData[setting.key] = setting.value;
            }
          });
          setFormData(initialFormData);
        } else {
          setError('Failed to load system settings');
        }
      } catch (error: any) {
        console.error('Failed to load settings:', error);
        setError(error.response?.data?.message || 'Failed to load system settings');
      } finally {
        setLoading(false);
      }
    };

    loadSettings();
  }, []);

  const handleInputChange = (key: string, value: string | number | boolean) => {
    setFormData(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const handleSaveSettings = async () => {
    try {
      setSaving(true);
      
      const response = await adminAPI.updateSystemSettings(formData);
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t('admin.settings_saved'),
        });
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to save settings',
      });
    } finally {
      setSaving(false);
    }
  };

  const handleResetToDefaults = async () => {
    if (!window.confirm(t('admin.confirm_reset_settings'))) {
      return;
    }
    
    try {
      setSaving(true);
      
      const response = await adminAPI.resetSystemSettings();
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t('admin.settings_reset'),
        });
        
        // Reload settings
        const reloadResponse = await adminAPI.getSystemSettings();
        if (reloadResponse.data.success) {
          const settingsData = reloadResponse.data.data;
          setSettings(settingsData);
          
          const initialFormData: SettingsFormData = {};
          settingsData.forEach((setting: SystemSetting) => {
            if (setting.type === 'boolean') {
              initialFormData[setting.key] = setting.value === 'true';
            } else if (setting.type === 'number') {
              initialFormData[setting.key] = parseFloat(setting.value) || 0;
            } else {
              initialFormData[setting.key] = setting.value;
            }
          });
          setFormData(initialFormData);
        }
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to reset settings',
      });
    } finally {
      setSaving(false);
    }
  };

  const getSettingsByCategory = (category: string) => {
    return settings.filter(setting => setting.category === category);
  };

  const renderSettingInput = (setting: SystemSetting) => {
    const value = formData[setting.key];
    
    switch (setting.type) {
      case 'boolean':
        return (
          <div className="flex items-center">
            <input
              type="checkbox"
              checked={value as boolean}
              onChange={(e) => handleInputChange(setting.key, e.target.checked)}
              disabled={!setting.is_editable}
              className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
            />
            <span className="ml-2 text-sm text-gray-700">
              {value ? t('common.enabled') : t('common.disabled')}
            </span>
          </div>
        );
        
      case 'number':
        return (
          <input
            type="number"
            value={value as number}
            onChange={(e) => handleInputChange(setting.key, parseFloat(e.target.value) || 0)}
            disabled={!setting.is_editable}
            className="input-field"
          />
        );
        
      case 'json':
        return (
          <textarea
            value={value as string}
            onChange={(e) => handleInputChange(setting.key, e.target.value)}
            disabled={!setting.is_editable}
            rows={4}
            className="input-field font-mono text-sm"
            placeholder="{}"
          />
        );
        
      default:
        return (
          <input
            type="text"
            value={value as string}
            onChange={(e) => handleInputChange(setting.key, e.target.value)}
            disabled={!setting.is_editable}
            className="input-field"
          />
        );
    }
  };

  const categories = [
    { key: 'general', label: t('admin.general_settings'), icon: '⚙️' },
    { key: 'email', label: t('admin.email_settings'), icon: '📧' },
    { key: 'payment', label: t('admin.payment_settings'), icon: '💳' },
    { key: 'security', label: t('admin.security_settings'), icon: '🔒' },
    { key: 'notifications', label: t('admin.notification_settings'), icon: '🔔' },
    { key: 'integration', label: t('admin.integration_settings'), icon: '🔗' },
  ];

  if (loading) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">
          {t('admin.system_settings')}
        </h1>
        <div className="flex space-x-3">
          <button
            onClick={handleResetToDefaults}
            disabled={saving}
            className="btn-secondary"
          >
            {t('admin.reset_to_defaults')}
          </button>
          <button
            onClick={handleSaveSettings}
            disabled={saving}
            className="btn-primary"
          >
            {saving ? t('common.saving') : t('common.save')}
          </button>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="rounded-md bg-red-50 p-4">
          <div className="text-sm text-red-700">{error}</div>
        </div>
      )}

      {/* Settings Tabs */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8 px-6">
            {categories.map((category) => {
              const categorySettings = getSettingsByCategory(category.key);
              return (
                <button
                  key={category.key}
                  onClick={() => setActiveTab(category.key)}
                  className={`py-4 px-1 border-b-2 font-medium text-sm ${
                    activeTab === category.key
                      ? 'border-primary-500 text-primary-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  <span className="mr-2">{category.icon}</span>
                  {category.label}
                  {categorySettings.length > 0 && (
                    <span className="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2 rounded-full text-xs">
                      {categorySettings.length}
                    </span>
                  )}
                </button>
              );
            })}
          </nav>
        </div>

        {/* Settings Content */}
        <div className="p-6">
          {categories.map((category) => {
            const categorySettings = getSettingsByCategory(category.key);
            
            if (activeTab !== category.key || categorySettings.length === 0) {
              return null;
            }
            
            return (
              <div key={category.key} className="space-y-6">
                <div>
                  <h3 className="text-lg font-medium text-gray-900 mb-4">
                    {category.label}
                  </h3>
                  
                  <div className="space-y-6">
                    {categorySettings.map((setting) => (
                      <div key={setting.id} className="border-b border-gray-200 pb-6 last:border-b-0">
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                          <div className="lg:col-span-1">
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                              {setting.key}
                            </label>
                            <p className="text-sm text-gray-500">
                              {setting.description_en}
                            </p>
                            {!setting.is_editable && (
                              <p className="text-xs text-orange-600 mt-1">
                                {t('admin.read_only')}
                              </p>
                            )}
                          </div>
                          
                          <div className="lg:col-span-2">
                            {renderSettingInput(setting)}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            );
          })}
          
          {getSettingsByCategory(activeTab).length === 0 && (
            <div className="text-center py-12">
              <p className="text-gray-500">
                {t('admin.no_settings_for_category')}
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">
            {t('admin.quick_actions')}
          </h3>
          <div className="space-y-3">
            <button className="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <div className="flex items-center">
                <span className="text-lg mr-3">🔄</span>
                <div>
                  <p className="text-sm font-medium text-gray-900">
                    {t('admin.clear_cache')}
                  </p>
                  <p className="text-xs text-gray-500">
                    {t('admin.clear_cache_description')}
                  </p>
                </div>
              </div>
            </button>
            
            <button className="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <div className="flex items-center">
                <span className="text-lg mr-3">📊</span>
                <div>
                  <p className="text-sm font-medium text-gray-900">
                    {t('admin.generate_reports')}
                  </p>
                  <p className="text-xs text-gray-500">
                    {t('admin.generate_reports_description')}
                  </p>
                </div>
              </div>
            </button>
            
            <button className="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <div className="flex items-center">
                <span className="text-lg mr-3">🔧</span>
                <div>
                  <p className="text-sm font-medium text-gray-900">
                    {t('admin.system_maintenance')}
                  </p>
                  <p className="text-xs text-gray-500">
                    {t('admin.system_maintenance_description')}
                  </p>
                </div>
              </div>
            </button>
          </div>
        </div>
        
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">
            {t('admin.system_info')}
          </h3>
          <div className="space-y-3">
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">{t('admin.app_version')}</span>
              <span className="text-sm font-medium">1.0.0</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">{t('admin.php_version')}</span>
              <span className="text-sm font-medium">8.3.0</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">{t('admin.laravel_version')}</span>
              <span className="text-sm font-medium">11.0</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">{t('admin.database')}</span>
              <span className="text-sm font-medium">SQL Server 2022</span>
            </div>
          </div>
        </div>
        
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">
            {t('admin.recent_activity')}
          </h3>
          <div className="space-y-3">
            <div className="flex items-start space-x-3">
              <div className="w-2 h-2 bg-green-400 rounded-full mt-2"></div>
              <div>
                <p className="text-sm text-gray-900">
                  {t('admin.settings_updated')}
                </p>
                <p className="text-xs text-gray-500">
                  {new Date().toLocaleString()}
                </p>
              </div>
            </div>
            <div className="flex items-start space-x-3">
              <div className="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
              <div>
                <p className="text-sm text-gray-900">
                  {t('admin.system_backup')}
                </p>
                <p className="text-xs text-gray-500">
                  {new Date(Date.now() - 86400000).toLocaleString()}
                </p>
              </div>
            </div>
            <div className="flex items-start space-x-3">
              <div className="w-2 h-2 bg-yellow-400 rounded-full mt-2"></div>
              <div>
                <p className="text-sm text-gray-900">
                  {t('admin.maintenance_mode')}
                </p>
                <p className="text-xs text-gray-500">
                  {new Date(Date.now() - 172800000).toLocaleString()}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

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

export default SettingsPage;