import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { X, AlertTriangle } from 'lucide-react';
import { employeeAPI } from '../../services/api';
import LoadingSpinner from '../LoadingSpinner';

interface ProblemReportModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  problem?: any;
  mode: 'report' | 'resolve';
}

const ProblemReportModal: React.FC<ProblemReportModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
  problem,
  mode
}) => {
  const { t } = useTranslation();
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [workers, setWorkers] = useState([]);
  
  // Form data for reporting
  const [reportData, setReportData] = useState({
    worker_id: '',
    problem_type: '',
    description: '',
    date_reported: new Date().toISOString().split('T')[0]
  });
  
  // Form data for resolving
  const [resolveData, setResolveData] = useState({
    resolution_action: '',
    resolution_notes: ''
  });

  useEffect(() => {
    if (isOpen && mode === 'report') {
      loadWorkers();
    }
    if (isOpen && mode === 'resolve' && problem) {
      // Problem data is already loaded
    }
  }, [isOpen, mode, problem]);

  const loadWorkers = async () => {
    try {
      setLoading(true);
      // Get only contracted or delivered workers
      const response = await employeeAPI.getWorkers({
        per_page: 100,
        status: 'Active'
      });
      
      const eligibleWorkers = response.data.data.data.filter((w: any) => 
        ['Contracted', 'Delivered'].includes(w.state)
      );
      
      setWorkers(eligibleWorkers);
    } catch (error) {
      console.error('Failed to load workers:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      if (mode === 'report') {
        await employeeAPI.reportProblem(reportData);
      } else {
        await employeeAPI.resolveProblem(problem.id, resolveData);
      }
      
      onSuccess();
      onClose();
    } catch (error: any) {
      setError(error.response?.data?.message || `Failed to ${mode} problem`);
    } finally {
      setSubmitting(false);
    }
  };

  const handleReportChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setReportData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleResolveChange = (e: React.ChangeEvent<HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setResolveData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div className="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        {/* Header */}
        <div className="flex justify-between items-center mb-4">
          <div className="flex items-center">
            <AlertTriangle className="h-6 w-6 text-red-600 mr-2" />
            <h3 className="text-lg font-medium text-gray-900">
              {mode === 'report' ? t('employee.report_problem') : t('employee.resolve_problem')}
            </h3>
          </div>
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
          <form onSubmit={handleSubmit} className="space-y-4">
            {mode === 'report' ? (
              <>
                {/* Report Problem Form */}
                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('worker.select_worker')} *
                  </label>
                  <select
                    name="worker_id"
                    value={reportData.worker_id}
                    onChange={handleReportChange}
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  >
                    <option value="">{t('common.select')}</option>
                    {workers.map((worker: any) => (
                      <option key={worker.id} value={worker.id}>
                        {worker.name_en} - {worker.passport_number} ({worker.state})
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('problem.type')} *
                  </label>
                  <select
                    name="problem_type"
                    value={reportData.problem_type}
                    onChange={handleReportChange}
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  >
                    <option value="">{t('common.select')}</option>
                    <option value="escape">{t('problem.escape')}</option>
                    <option value="refusal">{t('problem.refusal')}</option>
                    <option value="non_compliance">{t('problem.non_compliance')}</option>
                    <option value="misconduct">{t('problem.misconduct')}</option>
                    <option value="early_return">{t('problem.early_return')}</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('problem.date_reported')} *
                  </label>
                  <input
                    type="date"
                    name="date_reported"
                    value={reportData.date_reported}
                    onChange={handleReportChange}
                    required
                    max={new Date().toISOString().split('T')[0]}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('problem.description')} *
                  </label>
                  <textarea
                    name="description"
                    value={reportData.description}
                    onChange={handleReportChange}
                    required
                    rows={4}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    placeholder={t('problem.description_placeholder')}
                  />
                </div>
              </>
            ) : (
              <>
                {/* Resolve Problem Form */}
                {problem && (
                  <div className="bg-gray-50 p-4 rounded-lg mb-4">
                    <h4 className="font-medium text-gray-900 mb-2">{t('problem.details')}</h4>
                    <div className="space-y-1 text-sm text-gray-600">
                      <p><strong>{t('worker.name')}:</strong> {problem.worker?.name_en}</p>
                      <p><strong>{t('problem.type')}:</strong> {t(`problem.${problem.problem_type}`)}</p>
                      <p><strong>{t('problem.date_reported')}:</strong> {new Date(problem.date_reported).toLocaleDateString()}</p>
                      <p><strong>{t('problem.description')}:</strong> {problem.description}</p>
                    </div>
                  </div>
                )}

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('problem.resolution_action')} *
                  </label>
                  <select
                    name="resolution_action"
                    value={resolveData.resolution_action}
                    onChange={handleResolveChange}
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  >
                    <option value="">{t('common.select')}</option>
                    <option value="Dismissal">{t('problem.dismissal')}</option>
                    <option value="Re-training">{t('problem.retraining')}</option>
                    <option value="Escalation">{t('problem.escalation')}</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('problem.resolution_notes')} *
                  </label>
                  <textarea
                    name="resolution_notes"
                    value={resolveData.resolution_notes}
                    onChange={handleResolveChange}
                    required
                    rows={4}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    placeholder={t('problem.resolution_notes_placeholder')}
                  />
                </div>

                {resolveData.resolution_action === 'Dismissal' && (
                  <div className="bg-red-50 border-l-4 border-red-400 p-4">
                    <div className="flex">
                      <AlertTriangle className="h-5 w-5 text-red-400" />
                      <div className="ml-3">
                        <p className="text-sm text-red-700">
                          {t('problem.dismissal_warning')}
                        </p>
                      </div>
                    </div>
                  </div>
                )}
              </>
            )}

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
                {submitting ? t('common.submitting') : t('common.submit')}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
};

export default ProblemReportModal;