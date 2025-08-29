import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { X } from 'lucide-react';
import { employeeAPI, lookupsAPI, adminAPI } from '../../services/api';
import LoadingSpinner from '../LoadingSpinner';
import Toast from '../Toast';

interface WorkerFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  worker?: any;
  mode: 'create' | 'edit';
}

const WorkerFormModal: React.FC<WorkerFormModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
  worker,
  mode
}) => {
  const { t } = useTranslation();
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // Lookup data
  const [nationalities, setNationalities] = useState([]);
  const [professions, setProfessions] = useState([]);
  const [agencies, setAgencies] = useState([]);
  
  // Form data
  const [formData, setFormData] = useState({
    name_en: '',
    name_ar: '',
    passport_number: '',
    date_of_birth: '',
    nationality_id: '',
    profession_id: '',
    agency_id: '',
    gender: 'Male',
    experience_years: 0,
    education_level: 'Secondary',
    marital_status: 'Single',
    languages_spoken: [],
    monthly_salary: '',
    contract_duration: 24,
    medical_status: 'Pending',
    skills: [],
    status: 'Active'
  });

  // Load lookups
  useEffect(() => {
    if (isOpen) {
      loadLookups();
      if (mode === 'edit' && worker) {
        setFormData({
          name_en: worker.name_en || '',
          name_ar: worker.name_ar || '',
          passport_number: worker.passport_number || '',
          date_of_birth: worker.date_of_birth || '',
          nationality_id: worker.nationality_id || '',
          profession_id: worker.profession_id || '',
          agency_id: worker.agency_id || '',
          gender: worker.gender || 'Male',
          experience_years: worker.experience_years || 0,
          education_level: worker.education_level || 'Secondary',
          marital_status: worker.marital_status || 'Single',
          languages_spoken: worker.languages_spoken || [],
          monthly_salary: worker.monthly_salary || '',
          contract_duration: worker.contract_duration || 24,
          medical_status: worker.medical_status || 'Pending',
          skills: worker.skills || [],
          status: worker.status || 'Active'
        });
      }
    }
  }, [isOpen, mode, worker]);

  const loadLookups = async () => {
    try {
      setLoading(true);
      const [natResponse, profResponse, agencyResponse] = await Promise.all([
        lookupsAPI.getNationalities(),
        lookupsAPI.getProfessions(),
        adminAPI.getUsers({ user_type: 'Agency', per_page: 100 })
      ]);
      
      setNationalities(natResponse.data.data || []);
      setProfessions(profResponse.data.data || []);
      setAgencies(agencyResponse.data.data?.data || []);
    } catch (error) {
      console.error('Failed to load lookups:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      if (mode === 'create') {
        await employeeAPI.createWorker(formData);
      } else {
        await employeeAPI.updateWorker(worker.id, formData);
      }
      
      onSuccess();
      onClose();
    } catch (error: any) {
      setError(error.response?.data?.message || `Failed to ${mode} worker`);
    } finally {
      setSubmitting(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleArrayChange = (field: string, value: string) => {
    const values = value.split(',').map(v => v.trim()).filter(v => v);
    setFormData(prev => ({
      ...prev,
      [field]: values
    }));
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div className="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        {/* Header */}
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-medium text-gray-900">
            {mode === 'create' ? t('employee.create_worker') : t('employee.edit_worker')}
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
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('common.name_en')} *
                </label>
                <input
                  type="text"
                  name="name_en"
                  value={formData.name_en}
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
                  name="name_ar"
                  value={formData.name_ar}
                  onChange={handleChange}
                  required
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.passport_number')} *
                </label>
                <input
                  type="text"
                  name="passport_number"
                  value={formData.passport_number}
                  onChange={handleChange}
                  required
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.date_of_birth')} *
                </label>
                <input
                  type="date"
                  name="date_of_birth"
                  value={formData.date_of_birth}
                  onChange={handleChange}
                  required
                  max={new Date().toISOString().split('T')[0]}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.nationality')} *
                </label>
                <select
                  name="nationality_id"
                  value={formData.nationality_id}
                  onChange={handleChange}
                  required
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
                  <option value="">{t('common.select')}</option>
                  {nationalities.map((nat: any) => (
                    <option key={nat.id} value={nat.id}>
                      {nat.name_en}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.profession')} *
                </label>
                <select
                  name="profession_id"
                  value={formData.profession_id}
                  onChange={handleChange}
                  required
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
                  <option value="">{t('common.select')}</option>
                  {professions.map((prof: any) => (
                    <option key={prof.id} value={prof.id}>
                      {prof.name_en}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.agency')} *
                </label>
                <select
                  name="agency_id"
                  value={formData.agency_id}
                  onChange={handleChange}
                  required
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
                  <option value="">{t('common.select')}</option>
                  {agencies.map((agency: any) => (
                    <option key={agency.id} value={agency.agency?.id}>
                      {agency.agency?.name_en}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.gender')} *
                </label>
                <select
                  name="gender"
                  value={formData.gender}
                  onChange={handleChange}
                  required
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
                  <option value="Male">{t('common.male')}</option>
                  <option value="Female">{t('common.female')}</option>
                </select>
              </div>
            </div>

            {/* Additional Information */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.experience_years')} *
                </label>
                <input
                  type="number"
                  name="experience_years"
                  value={formData.experience_years}
                  onChange={handleChange}
                  required
                  min="0"
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.education_level')} *
                </label>
                <select
                  name="education_level"
                  value={formData.education_level}
                  onChange={handleChange}
                  required
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
                  <option value="Primary">{t('education.primary')}</option>
                  <option value="Secondary">{t('education.secondary')}</option>
                  <option value="Bachelor">{t('education.bachelor')}</option>
                  <option value="Master">{t('education.master')}</option>
                  <option value="Doctorate">{t('education.doctorate')}</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.marital_status')} *
                </label>
                <select
                  name="marital_status"
                  value={formData.marital_status}
                  onChange={handleChange}
                  required
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
                  <option value="Single">{t('marital.single')}</option>
                  <option value="Married">{t('marital.married')}</option>
                  <option value="Divorced">{t('marital.divorced')}</option>
                  <option value="Widowed">{t('marital.widowed')}</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.monthly_salary')} *
                </label>
                <input
                  type="number"
                  name="monthly_salary"
                  value={formData.monthly_salary}
                  onChange={handleChange}
                  required
                  min="0"
                  step="0.01"
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.contract_duration')} ({t('common.months')}) *
                </label>
                <input
                  type="number"
                  name="contract_duration"
                  value={formData.contract_duration}
                  onChange={handleChange}
                  required
                  min="1"
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.medical_status')} *
                </label>
                <select
                  name="medical_status"
                  value={formData.medical_status}
                  onChange={handleChange}
                  required
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
                  <option value="Pending">{t('medical.pending')}</option>
                  <option value="Fit">{t('medical.fit')}</option>
                  <option value="Unfit">{t('medical.unfit')}</option>
                </select>
              </div>

              {mode === 'edit' && (
                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('common.status')}
                  </label>
                  <select
                    name="status"
                    value={formData.status}
                    onChange={handleChange}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  >
                    <option value="Active">{t('status.active')}</option>
                    <option value="Inactive">{t('status.inactive')}</option>
                    <option value="Blocked">{t('status.blocked')}</option>
                  </select>
                </div>
              )}
            </div>

            {/* Languages and Skills */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.languages_spoken')}
                </label>
                <input
                  type="text"
                  placeholder={t('common.comma_separated')}
                  value={formData.languages_spoken.join(', ')}
                  onChange={(e) => handleArrayChange('languages_spoken', e.target.value)}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('worker.skills')}
                </label>
                <input
                  type="text"
                  placeholder={t('common.comma_separated')}
                  value={formData.skills.join(', ')}
                  onChange={(e) => handleArrayChange('skills', e.target.value)}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                />
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

export default WorkerFormModal;