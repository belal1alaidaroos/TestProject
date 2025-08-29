import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { 
  AlertTriangle,
  Plus,
  Search,
  Filter,
  CheckCircle,
  XCircle,
  Clock,
  FileText
} from 'lucide-react';
import { employeeAPI } from '../../services/api';
import ProblemReportModal from '../../components/Employee/ProblemReportModal';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface WorkerProblem {
  id: string;
  worker_id: string;
  problem_type: string;
  description: string;
  date_reported: string;
  status: string;
  resolution_action?: string;
  resolution_notes?: string;
  worker: {
    id: string;
    name_en: string;
    name_ar: string;
    passport_number: string;
  };
  created_by: {
    id: string;
    name: string;
  };
  approved_by?: {
    id: string;
    name: string;
  };
  created_at: string;
  approved_at?: string;
}

const WorkerProblems: React.FC = () => {
  const { t, i18n } = useTranslation();
  const [problems, setProblems] = useState<WorkerProblem[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [statistics, setStatistics] = useState<any>(null);
  
  // Modal states
  const [showReportModal, setShowReportModal] = useState(false);
  const [showResolveModal, setShowResolveModal] = useState(false);
  const [selectedProblem, setSelectedProblem] = useState<WorkerProblem | null>(null);
  const [modalMode, setModalMode] = useState<'report' | 'resolve'>('report');

  useEffect(() => {
    fetchProblems();
    fetchStatistics();
  }, [statusFilter, typeFilter, currentPage]);

  const fetchProblems = async () => {
    try {
      setLoading(true);
      const response = await employeeAPI.getWorkerProblems({
        status: statusFilter,
        problem_type: typeFilter,
        page: currentPage,
        per_page: 10
      });

      if (response.data.success) {
        setProblems(response.data.data.data || []);
        setTotalPages(response.data.data.last_page || 1);
      }
    } catch (error) {
      console.error('Failed to fetch problems:', error);
      setToast({
        type: 'error',
        message: t('employee.problems_fetch_error')
      });
    } finally {
      setLoading(false);
    }
  };

  const fetchStatistics = async () => {
    try {
      const response = await employeeAPI.getWorkerProblemStatistics();
      if (response.data.success) {
        setStatistics(response.data.data);
      }
    } catch (error) {
      console.error('Failed to fetch statistics:', error);
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'Pending':
        return <span className="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full flex items-center">
          <Clock className="w-3 h-3 mr-1" />
          {t('status.pending')}
        </span>;
      case 'Approved':
        return <span className="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full flex items-center">
          <CheckCircle className="w-3 h-3 mr-1" />
          {t('status.approved')}
        </span>;
      case 'Rejected':
        return <span className="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full flex items-center">
          <XCircle className="w-3 h-3 mr-1" />
          {t('status.rejected')}
        </span>;
      case 'Closed':
        return <span className="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full flex items-center">
          <CheckCircle className="w-3 h-3 mr-1" />
          {t('status.closed')}
        </span>;
      default:
        return <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">{status}</span>;
    }
  };

  const getProblemTypeBadge = (type: string) => {
    const typeColors: Record<string, string> = {
      'escape': 'bg-red-100 text-red-800',
      'refusal': 'bg-orange-100 text-orange-800',
      'non_compliance': 'bg-yellow-100 text-yellow-800',
      'misconduct': 'bg-purple-100 text-purple-800',
      'early_return': 'bg-blue-100 text-blue-800'
    };
    
    const colorClass = typeColors[type] || 'bg-gray-100 text-gray-800';
    return <span className={`px-2 py-1 text-xs font-medium rounded-full ${colorClass}`}>
      {t(`problem.${type}`)}
    </span>;
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const handleReportProblem = () => {
    setSelectedProblem(null);
    setModalMode('report');
    setShowReportModal(true);
  };

  const handleResolveProblem = (problem: WorkerProblem) => {
    setSelectedProblem(problem);
    setModalMode('resolve');
    setShowResolveModal(true);
  };

  const handleModalSuccess = () => {
    setToast({
      type: 'success',
      message: modalMode === 'report' ? t('employee.problem_reported') : t('employee.problem_resolved')
    });
    fetchProblems();
    fetchStatistics();
  };

  const handleStatusFilter = (value: string) => {
    setStatusFilter(value);
    setCurrentPage(1);
  };

  const handleTypeFilter = (value: string) => {
    setTypeFilter(value);
    setCurrentPage(1);
  };

  if (loading && problems.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">{t('employee.worker_problems')}</h1>
          <p className="text-gray-600 mt-1">
            {t('employee.worker_problems_description')}
          </p>
        </div>
        <button onClick={handleReportProblem} className="btn-primary">
          <Plus className="h-4 w-4 mr-2" />
          {t('employee.report_problem')}
        </button>
      </div>

      {/* Statistics */}
      {statistics && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.total_problems')}</p>
                <p className="text-2xl font-semibold text-gray-900">{statistics.total_problems}</p>
              </div>
              <AlertTriangle className="h-8 w-8 text-gray-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.pending_problems')}</p>
                <p className="text-2xl font-semibold text-yellow-600">{statistics.pending_problems}</p>
              </div>
              <Clock className="h-8 w-8 text-yellow-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.approved_problems')}</p>
                <p className="text-2xl font-semibold text-blue-600">{statistics.approved_problems}</p>
              </div>
              <CheckCircle className="h-8 w-8 text-blue-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.closed_problems')}</p>
                <p className="text-2xl font-semibold text-green-600">{statistics.closed_problems}</p>
              </div>
              <CheckCircle className="h-8 w-8 text-green-400" />
            </div>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <select
            value={statusFilter}
            onChange={(e) => handleStatusFilter(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">{t('common.all_statuses')}</option>
            <option value="Pending">{t('status.pending')}</option>
            <option value="Approved">{t('status.approved')}</option>
            <option value="Rejected">{t('status.rejected')}</option>
            <option value="Closed">{t('status.closed')}</option>
          </select>

          <select
            value={typeFilter}
            onChange={(e) => handleTypeFilter(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">{t('problem.all_types')}</option>
            <option value="escape">{t('problem.escape')}</option>
            <option value="refusal">{t('problem.refusal')}</option>
            <option value="non_compliance">{t('problem.non_compliance')}</option>
            <option value="misconduct">{t('problem.misconduct')}</option>
            <option value="early_return">{t('problem.early_return')}</option>
          </select>

          <button onClick={fetchProblems} className="btn-secondary">
            {t('common.refresh')}
          </button>
        </div>
      </div>

      {/* Problems Table */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('worker.info')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('problem.details')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('problem.status')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('problem.reported_by')}
              </th>
              <th className="relative px-6 py-3">
                <span className="sr-only">{t('common.actions')}</span>
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {problems.map((problem) => (
              <tr key={problem.id}>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div className="text-sm font-medium text-gray-900">
                      {problem.worker.name_en}
                    </div>
                    <div className="text-sm text-gray-500">
                      {problem.worker.passport_number}
                    </div>
                  </div>
                </td>
                <td className="px-6 py-4">
                  <div>
                    <div className="flex items-center space-x-2 mb-1">
                      {getProblemTypeBadge(problem.problem_type)}
                      <span className="text-sm text-gray-500">
                        {formatDate(problem.date_reported)}
                      </span>
                    </div>
                    <p className="text-sm text-gray-600 line-clamp-2">
                      {problem.description}
                    </p>
                    {problem.resolution_action && (
                      <div className="mt-1">
                        <span className="text-xs font-medium text-gray-500">
                          {t('problem.resolution')}: {t(`problem.${problem.resolution_action.toLowerCase()}`)}
                        </span>
                      </div>
                    )}
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  {getStatusBadge(problem.status)}
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm text-gray-900">
                    {problem.created_by.name}
                  </div>
                  <div className="text-sm text-gray-500">
                    {formatDate(problem.created_at)}
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  {problem.status === 'Approved' && !problem.resolution_action && (
                    <button
                      onClick={() => handleResolveProblem(problem)}
                      className="text-blue-600 hover:text-blue-900"
                    >
                      <FileText className="h-4 w-4" />
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

      {/* Report Problem Modal */}
      <ProblemReportModal
        isOpen={showReportModal}
        onClose={() => setShowReportModal(false)}
        onSuccess={handleModalSuccess}
        mode="report"
      />

      {/* Resolve Problem Modal */}
      <ProblemReportModal
        isOpen={showResolveModal}
        onClose={() => setShowResolveModal(false)}
        onSuccess={handleModalSuccess}
        problem={selectedProblem}
        mode="resolve"
      />

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

export default WorkerProblems;