import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { 
  FileText,
  Search,
  Filter,
  Calendar,
  DollarSign,
  User,
  Building,
  CheckCircle,
  XCircle,
  AlertTriangle,
  Clock
} from 'lucide-react';
import { employeeAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface Contract {
  id: string;
  contract_number: string;
  customer_id: string;
  worker_id: string;
  package_id: string;
  start_date: string;
  end_date: string;
  total_amount: number;
  status: string;
  created_at: string;
  customer: {
    id: string;
    name_en: string;
    name_ar: string;
    phone: string;
  };
  worker: {
    id: string;
    name_en: string;
    name_ar: string;
    passport_number: string;
    profession: {
      name_en: string;
      name_ar: string;
    };
  };
  package: {
    id: string;
    name_en: string;
    name_ar: string;
    duration: string;
    price: number;
  };
}

const Contracts: React.FC = () => {
  const { t, i18n } = useTranslation();
  const [contracts, setContracts] = useState<Contract[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [statistics, setStatistics] = useState<any>(null);
  
  // Status update modal
  const [showStatusModal, setShowStatusModal] = useState(false);
  const [selectedContract, setSelectedContract] = useState<Contract | null>(null);
  const [newStatus, setNewStatus] = useState('');
  const [statusNotes, setStatusNotes] = useState('');
  const [updating, setUpdating] = useState(false);

  useEffect(() => {
    fetchContracts();
    fetchStatistics();
  }, [searchTerm, statusFilter, dateFrom, dateTo, currentPage]);

  const fetchContracts = async () => {
    try {
      setLoading(true);
      const response = await employeeAPI.getContracts({
        search: searchTerm,
        status: statusFilter,
        date_from: dateFrom,
        date_to: dateTo,
        page: currentPage,
        per_page: 10
      });

      if (response.data.success) {
        setContracts(response.data.data.data || []);
        setTotalPages(response.data.data.last_page || 1);
      }
    } catch (error) {
      console.error('Failed to fetch contracts:', error);
      setToast({
        type: 'error',
        message: t('employee.contracts_fetch_error')
      });
    } finally {
      setLoading(false);
    }
  };

  const fetchStatistics = async () => {
    try {
      const response = await employeeAPI.getContractStatistics();
      if (response.data.success) {
        setStatistics(response.data.data);
      }
    } catch (error) {
      console.error('Failed to fetch statistics:', error);
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'Draft':
        return <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">{t('contract.draft')}</span>;
      case 'Active':
        return <span className="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full flex items-center">
          <CheckCircle className="w-3 h-3 mr-1" />
          {t('contract.active')}
        </span>;
      case 'Suspended':
        return <span className="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full flex items-center">
          <AlertTriangle className="w-3 h-3 mr-1" />
          {t('contract.suspended')}
        </span>;
      case 'Terminated':
        return <span className="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full flex items-center">
          <XCircle className="w-3 h-3 mr-1" />
          {t('contract.terminated')}
        </span>;
      case 'Completed':
        return <span className="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full flex items-center">
          <CheckCircle className="w-3 h-3 mr-1" />
          {t('contract.completed')}
        </span>;
      case 'Cancelled':
        return <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">{t('contract.cancelled')}</span>;
      default:
        return <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">{status}</span>;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'SAR'
    }).format(amount);
  };

  const getDaysRemaining = (endDate: string) => {
    const end = new Date(endDate);
    const today = new Date();
    const diffTime = end.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
  };

  const handleStatusUpdate = (contract: Contract) => {
    setSelectedContract(contract);
    setNewStatus(contract.status);
    setStatusNotes('');
    setShowStatusModal(true);
  };

  const submitStatusUpdate = async () => {
    if (!selectedContract || !newStatus) return;
    
    setUpdating(true);
    try {
      await employeeAPI.updateContractStatus(selectedContract.id, {
        status: newStatus,
        notes: statusNotes
      });
      
      setToast({
        type: 'success',
        message: t('employee.contract_status_updated')
      });
      
      fetchContracts();
      fetchStatistics();
      setShowStatusModal(false);
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || t('employee.status_update_error')
      });
    } finally {
      setUpdating(false);
    }
  };

  const getValidStatusTransitions = (currentStatus: string): string[] => {
    const transitions: Record<string, string[]> = {
      'Draft': ['Active', 'Cancelled'],
      'Active': ['Suspended', 'Terminated', 'Completed'],
      'Suspended': ['Active', 'Terminated'],
      'Terminated': [],
      'Completed': [],
      'Cancelled': []
    };
    
    return transitions[currentStatus] || [];
  };

  const handleSearch = (value: string) => {
    setSearchTerm(value);
    setCurrentPage(1);
  };

  const handleStatusFilter = (value: string) => {
    setStatusFilter(value);
    setCurrentPage(1);
  };

  if (loading && contracts.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">{t('employee.contracts_management')}</h1>
        <p className="text-gray-600 mt-1">
          {t('employee.contracts_description')}
        </p>
      </div>

      {/* Statistics */}
      {statistics && (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.total_contracts')}</p>
                <p className="text-2xl font-semibold text-gray-900">{statistics.total_contracts}</p>
              </div>
              <FileText className="h-8 w-8 text-gray-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.active_contracts')}</p>
                <p className="text-2xl font-semibold text-green-600">{statistics.active_contracts}</p>
              </div>
              <CheckCircle className="h-8 w-8 text-green-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.suspended_contracts')}</p>
                <p className="text-2xl font-semibold text-yellow-600">{statistics.suspended_contracts}</p>
              </div>
              <AlertTriangle className="h-8 w-8 text-yellow-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.completed_contracts')}</p>
                <p className="text-2xl font-semibold text-blue-600">{statistics.completed_contracts}</p>
              </div>
              <CheckCircle className="h-8 w-8 text-blue-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.expiring_soon')}</p>
                <p className="text-2xl font-semibold text-orange-600">{statistics.expiring_soon}</p>
              </div>
              <Clock className="h-8 w-8 text-orange-400" />
            </div>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow">
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
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
            <option value="Active">{t('contract.active')}</option>
            <option value="Suspended">{t('contract.suspended')}</option>
            <option value="Terminated">{t('contract.terminated')}</option>
            <option value="Completed">{t('contract.completed')}</option>
          </select>

          <input
            type="date"
            value={dateFrom}
            onChange={(e) => setDateFrom(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder={t('common.from_date')}
          />

          <input
            type="date"
            value={dateTo}
            onChange={(e) => setDateTo(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder={t('common.to_date')}
          />

          <button onClick={fetchContracts} className="btn-secondary">
            {t('common.refresh')}
          </button>
        </div>
      </div>

      {/* Contracts Table */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('contract.number')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('contract.parties')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('contract.duration')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('contract.amount')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('contract.status')}
              </th>
              <th className="relative px-6 py-3">
                <span className="sr-only">{t('common.actions')}</span>
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {contracts.map((contract) => (
              <tr key={contract.id}>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm font-medium text-gray-900">
                    {contract.contract_number}
                  </div>
                  <div className="text-sm text-gray-500">
                    {formatDate(contract.created_at)}
                  </div>
                </td>
                <td className="px-6 py-4">
                  <div className="text-sm">
                    <div className="font-medium text-gray-900">
                      <User className="inline w-4 h-4 mr-1" />
                      {i18n.language === 'ar' ? contract.customer.name_ar : contract.customer.name_en}
                    </div>
                    <div className="text-gray-500">
                      <Building className="inline w-4 h-4 mr-1" />
                      {contract.worker.name_en} - {contract.worker.profession.name_en}
                    </div>
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm">
                    <div className="text-gray-900">
                      {formatDate(contract.start_date)} - {formatDate(contract.end_date)}
                    </div>
                    <div className="text-gray-500">
                      {contract.status === 'Active' && (
                        <>
                          {getDaysRemaining(contract.end_date) > 0 
                            ? `${getDaysRemaining(contract.end_date)} ${t('common.days_remaining')}`
                            : <span className="text-red-600">{t('contract.expired')}</span>
                          }
                        </>
                      )}
                    </div>
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm">
                    <div className="font-medium text-gray-900">
                      {formatCurrency(contract.total_amount)}
                    </div>
                    <div className="text-gray-500">
                      {contract.package.name_en}
                    </div>
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  {getStatusBadge(contract.status)}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  {getValidStatusTransitions(contract.status).length > 0 && (
                    <button
                      onClick={() => handleStatusUpdate(contract)}
                      className="text-blue-600 hover:text-blue-900"
                    >
                      {t('common.update_status')}
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
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

      {/* Status Update Modal */}
      {showStatusModal && selectedContract && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                {t('employee.update_contract_status')}
              </h3>
              
              <div className="mb-4">
                <p className="text-sm text-gray-500 mb-2">
                  {t('contract.number')}: <strong>{selectedContract.contract_number}</strong>
                </p>
                <p className="text-sm text-gray-500">
                  {t('contract.current_status')}: {getStatusBadge(selectedContract.status)}
                </p>
              </div>

              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  {t('contract.new_status')} *
                </label>
                <select
                  value={newStatus}
                  onChange={(e) => setNewStatus(e.target.value)}
                  className="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option value="">{t('common.select')}</option>
                  {getValidStatusTransitions(selectedContract.status).map(status => (
                    <option key={status} value={status}>
                      {t(`contract.${status.toLowerCase()}`)}
                    </option>
                  ))}
                </select>
              </div>

              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  {t('common.notes')}
                </label>
                <textarea
                  value={statusNotes}
                  onChange={(e) => setStatusNotes(e.target.value)}
                  rows={3}
                  className="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  placeholder={t('common.optional')}
                />
              </div>

              {newStatus === 'Terminated' && (
                <div className="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                  <div className="flex">
                    <AlertTriangle className="h-5 w-5 text-red-400" />
                    <div className="ml-3">
                      <p className="text-sm text-red-700">
                        {t('contract.termination_warning')}
                      </p>
                    </div>
                  </div>
                </div>
              )}

              <div className="flex justify-end space-x-3">
                <button
                  type="button"
                  onClick={() => setShowStatusModal(false)}
                  className="btn-secondary"
                  disabled={updating}
                >
                  {t('common.cancel')}
                </button>
                <button
                  onClick={submitStatusUpdate}
                  className="btn-primary"
                  disabled={updating || !newStatus || newStatus === selectedContract.status}
                >
                  {updating ? t('common.updating') : t('common.update')}
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

export default Contracts;