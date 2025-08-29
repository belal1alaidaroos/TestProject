import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { 
  Plus,
  Search, 
  Filter, 
  Eye,
  Edit,
  Trash2,
  UserCheck,
  UserX,
  AlertTriangle
} from 'lucide-react';
import { employeeAPI, lookupsAPI } from '../../services/api';
import WorkerFormModal from '../../components/Employee/WorkerFormModal';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface Worker {
  id: string;
  name_en: string;
  name_ar: string;
  passport_number: string;
  date_of_birth: string;
  status: string;
  state: string;
  gender: string;
  experience_years: number;
  profession: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  nationality: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  agency: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  created_at: string;
}

const Workers: React.FC = () => {
  const { t, i18n } = useTranslation();
  const [workers, setWorkers] = useState<Worker[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [professionFilter, setProfessionFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [professions, setProfessions] = useState([]);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  
  // Modal states
  const [showFormModal, setShowFormModal] = useState(false);
  const [selectedWorker, setSelectedWorker] = useState<Worker | null>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit'>('create');
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [workerToDelete, setWorkerToDelete] = useState<Worker | null>(null);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    fetchWorkers();
    loadProfessions();
  }, [searchTerm, statusFilter, professionFilter, currentPage]);

  const loadProfessions = async () => {
    try {
      const response = await lookupsAPI.getProfessions();
      setProfessions(response.data.data || []);
    } catch (error) {
      console.error('Failed to load professions:', error);
    }
  };

  const fetchWorkers = async () => {
    try {
      setLoading(true);
      const response = await employeeAPI.getWorkers({
        search: searchTerm,
        status: statusFilter,
        profession_id: professionFilter,
        page: currentPage,
        per_page: 10
      });

      if (response.data.success) {
        setWorkers(response.data.data.data || []);
        setTotalPages(response.data.data.last_page || 1);
      }
    } catch (error) {
      console.error('Failed to fetch workers:', error);
      setToast({
        type: 'error',
        message: t('employee.workers_fetch_error')
      });
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'Active':
        return <span className="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">{t('status.active')}</span>;
      case 'Inactive':
        return <span className="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">{t('status.inactive')}</span>;
      case 'Blocked':
        return <span className="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">{t('status.blocked')}</span>;
      default:
        return <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">{status}</span>;
    }
  };

  const getStateBadge = (state: string) => {
    const stateColors: Record<string, string> = {
      'Inventory': 'bg-blue-100 text-blue-800',
      'Reserved': 'bg-yellow-100 text-yellow-800',
      'Contracted': 'bg-purple-100 text-purple-800',
      'UnderProcessing': 'bg-orange-100 text-orange-800',
      'Delivered': 'bg-green-100 text-green-800',
      'Returned': 'bg-gray-100 text-gray-800'
    };
    
    const colorClass = stateColors[state] || 'bg-gray-100 text-gray-800';
    return <span className={`px-2 py-1 text-xs font-medium rounded-full ${colorClass}`}>{state}</span>;
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const calculateAge = (dateOfBirth: string) => {
    const today = new Date();
    const birthDate = new Date(dateOfBirth);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    return age;
  };

  const handleCreateWorker = () => {
    setSelectedWorker(null);
    setFormMode('create');
    setShowFormModal(true);
  };

  const handleEditWorker = (worker: Worker) => {
    setSelectedWorker(worker);
    setFormMode('edit');
    setShowFormModal(true);
  };

  const handleDeleteWorker = (worker: Worker) => {
    setWorkerToDelete(worker);
    setShowDeleteConfirm(true);
  };

  const confirmDelete = async () => {
    if (!workerToDelete) return;
    
    setDeleting(true);
    try {
      await employeeAPI.deleteWorker(workerToDelete.id);
      setToast({
        type: 'success',
        message: t('employee.worker_deleted')
      });
      fetchWorkers();
      setShowDeleteConfirm(false);
      setWorkerToDelete(null);
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || t('employee.delete_error')
      });
    } finally {
      setDeleting(false);
    }
  };

  const handleFormSuccess = () => {
    setToast({
      type: 'success',
      message: formMode === 'create' ? t('employee.worker_created') : t('employee.worker_updated')
    });
    fetchWorkers();
  };

  const handleSearch = (value: string) => {
    setSearchTerm(value);
    setCurrentPage(1);
  };

  const handleStatusFilter = (value: string) => {
    setStatusFilter(value);
    setCurrentPage(1);
  };

  const handleProfessionFilter = (value: string) => {
    setProfessionFilter(value);
    setCurrentPage(1);
  };

  if (loading && workers.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">{t('employee.workers_management')}</h1>
          <p className="text-gray-600 mt-1">
            {t('employee.workers_description')}
          </p>
        </div>
        <button onClick={handleCreateWorker} className="btn-primary">
          <Plus className="h-4 w-4 mr-2" />
          {t('employee.create_worker')}
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
            <input
              type="text"
              placeholder={t('common.search')}
              value={searchTerm}
              onChange={(e) => handleSearch(e.target.value)}
              className="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>

          <select
            value={statusFilter}
            onChange={(e) => handleStatusFilter(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">{t('common.all_statuses')}</option>
            <option value="Active">{t('status.active')}</option>
            <option value="Inactive">{t('status.inactive')}</option>
            <option value="Blocked">{t('status.blocked')}</option>
          </select>

          <select
            value={professionFilter}
            onChange={(e) => handleProfessionFilter(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">{t('common.all_professions')}</option>
            {professions.map((prof: any) => (
              <option key={prof.id} value={prof.id}>
                {i18n.language === 'ar' ? prof.name_ar : prof.name_en}
              </option>
            ))}
          </select>

          <button onClick={fetchWorkers} className="btn-secondary">
            {t('common.refresh')}
          </button>
        </div>
      </div>

      {/* Workers Table */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('worker.info')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('worker.details')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('worker.status')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('worker.agency')}
              </th>
              <th className="relative px-6 py-3">
                <span className="sr-only">{t('common.actions')}</span>
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {workers.map((worker) => (
              <tr key={worker.id}>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div className="text-sm font-medium text-gray-900">
                      {i18n.language === 'ar' ? worker.name_ar : worker.name_en}
                    </div>
                    <div className="text-sm text-gray-500">
                      {worker.passport_number}
                    </div>
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm text-gray-900">
                    <div>{i18n.language === 'ar' ? worker.profession.name_ar : worker.profession.name_en}</div>
                    <div className="text-gray-500">
                      {i18n.language === 'ar' ? worker.nationality.name_ar : worker.nationality.name_en} • 
                      {calculateAge(worker.date_of_birth)} {t('common.years')} • 
                      {worker.experience_years} {t('common.years_exp')}
                    </div>
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="space-y-1">
                    {getStatusBadge(worker.status)}
                    {getStateBadge(worker.state)}
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {i18n.language === 'ar' ? worker.agency.name_ar : worker.agency.name_en}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div className="flex justify-end space-x-2">
                    <button
                      onClick={() => handleEditWorker(worker)}
                      className="text-blue-600 hover:text-blue-900"
                      disabled={['Contracted', 'UnderProcessing', 'Delivered'].includes(worker.state)}
                      title={t('common.edit')}
                    >
                      <Edit className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => handleDeleteWorker(worker)}
                      className="text-red-600 hover:text-red-900"
                      disabled={worker.state !== 'Inventory'}
                      title={t('common.delete')}
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div className="flex-1 flex justify-between sm:hidden">
              <button
                onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                disabled={currentPage === 1}
                className="btn-secondary"
              >
                {t('common.previous')}
              </button>
              <button
                onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                disabled={currentPage === totalPages}
                className="btn-secondary"
              >
                {t('common.next')}
              </button>
            </div>
            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
              <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                {[...Array(totalPages)].map((_, index) => (
                  <button
                    key={index + 1}
                    onClick={() => setCurrentPage(index + 1)}
                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                      currentPage === index + 1
                        ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                    }`}
                  >
                    {index + 1}
                  </button>
                ))}
              </nav>
            </div>
          </div>
        )}
      </div>

      {/* Worker Form Modal */}
      <WorkerFormModal
        isOpen={showFormModal}
        onClose={() => setShowFormModal(false)}
        onSuccess={handleFormSuccess}
        worker={selectedWorker}
        mode={formMode}
      />

      {/* Delete Confirmation Dialog */}
      {showDeleteConfirm && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3 text-center">
              <AlertTriangle className="mx-auto mb-4 h-12 w-12 text-red-600" />
              <h3 className="text-lg leading-6 font-medium text-gray-900">
                {t('employee.delete_worker_confirm')}
              </h3>
              <div className="mt-2 px-7 py-3">
                <p className="text-sm text-gray-500">
                  {t('employee.delete_worker_warning')} <br/>
                  <strong>{workerToDelete?.name_en}</strong>
                </p>
              </div>
              <div className="items-center px-4 py-3">
                <button
                  onClick={confirmDelete}
                  disabled={deleting}
                  className="btn-danger mr-2"
                >
                  {deleting ? t('common.deleting') : t('common.delete')}
                </button>
                <button
                  onClick={() => {
                    setShowDeleteConfirm(false);
                    setWorkerToDelete(null);
                  }}
                  disabled={deleting}
                  className="btn-secondary"
                >
                  {t('common.cancel')}
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

export default Workers;