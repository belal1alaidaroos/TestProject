import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { agencyAPI, lookupsAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface RecruitmentRequest {
  id: string;
  request_number: string;
  title_en: string;
  title_ar: string;
  description_en: string;
  description_ar: string;
  profession_id: string;
  quantity_required: number;
  quantity_awarded: number;
  unit_price_range_min: number;
  unit_price_range_max: number;
  lead_time_days: number;
  valid_until: string;
  status: string;
  created_at: string;
  profession: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  my_proposal?: {
    id: string;
    offered_qty: number;
    unit_price: number;
    lead_time_days: number;
    valid_until: string;
    status: string;
  };
}

interface Filters {
  profession_id?: string;
  status?: string;
  min_quantity?: number;
  max_quantity?: number;
}

const RequestsPage: React.FC = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  
  const [requests, setRequests] = useState<RecruitmentRequest[]>([]);
  const [professions, setProfessions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState<Filters>({});
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Load professions for filtering
  useEffect(() => {
    const loadProfessions = async () => {
      try {
        const response = await lookupsAPI.getProfessions();
        setProfessions(response.data.data || []);
      } catch (error) {
        console.error('Failed to load professions:', error);
      }
    };

    loadProfessions();
  }, []);

  // Load recruitment requests
  useEffect(() => {
    const loadRequests = async () => {
      try {
        setLoading(true);
        setError(null);
        
        const params = {
          ...filters,
          page: currentPage,
          per_page: 10,
        };
        
        const response = await agencyAPI.getRequests(params);
        
        if (response.data.success) {
          setRequests(response.data.data.data || []);
          setTotalPages(response.data.data.last_page || 1);
        } else {
          setError('Failed to load requests');
        }
      } catch (error: any) {
        console.error('Failed to load requests:', error);
        setError(error.response?.data?.message || 'Failed to load requests');
      } finally {
        setLoading(false);
      }
    };

    loadRequests();
  }, [filters, currentPage]);

  const getRemainingQuantity = (request: RecruitmentRequest) => {
    return request.quantity_required - request.quantity_awarded;
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Open':
        return 'bg-green-100 text-green-800';
      case 'PartiallyAwarded':
        return 'bg-yellow-100 text-yellow-800';
      case 'FullyAwarded':
        return 'bg-blue-100 text-blue-800';
      case 'Closed':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    return t(`status.${status.toLowerCase()}`);
  };

  const getProposalStatusColor = (status: string) => {
    switch (status) {
      case 'Submitted':
        return 'bg-blue-100 text-blue-800';
      case 'Approved':
        return 'bg-green-100 text-green-800';
      case 'PartiallyApproved':
        return 'bg-yellow-100 text-yellow-800';
      case 'Rejected':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
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

  const handleSubmitProposal = (requestId: string) => {
    navigate(`/submit-proposal/${requestId}`);
  };

  const handleViewProposal = (requestId: string) => {
    navigate(`/proposals`);
  };

  const isEligibleForProposal = (request: RecruitmentRequest) => {
    return (
      request.status === 'Open' || 
      request.status === 'PartiallyAwarded'
    ) && getRemainingQuantity(request) > 0;
  };

  if (loading && requests.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">
          {t('agency.requests')}
        </h1>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label className="form-label">{t('customer.profession')}</label>
            <select
              value={filters.profession_id || ''}
              onChange={(e) => handleFilterChange({ ...filters, profession_id: e.target.value || undefined })}
              className="input-field"
            >
              <option value="">{t('common.all')}</option>
              {professions.map((profession: any) => (
                <option key={profession.id} value={profession.id}>
                  {profession.name_en}
                </option>
              ))}
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
              <option value="Open">{t('status.open')}</option>
              <option value="PartiallyAwarded">{t('status.partially_awarded')}</option>
              <option value="FullyAwarded">{t('status.fully_awarded')}</option>
              <option value="Closed">{t('status.closed')}</option>
            </select>
          </div>
          
          <div>
            <label className="form-label">{t('agency.min_quantity')}</label>
            <input
              type="number"
              value={filters.min_quantity || ''}
              onChange={(e) => handleFilterChange({ ...filters, min_quantity: e.target.value ? parseInt(e.target.value) : undefined })}
              className="input-field"
              placeholder="0"
            />
          </div>
          
          <div>
            <label className="form-label">{t('agency.max_quantity')}</label>
            <input
              type="number"
              value={filters.max_quantity || ''}
              onChange={(e) => handleFilterChange({ ...filters, max_quantity: e.target.value ? parseInt(e.target.value) : undefined })}
              className="input-field"
              placeholder="100"
            />
          </div>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="rounded-md bg-red-50 p-4">
          <div className="text-sm text-red-700">{error}</div>
        </div>
      )}

      {/* Requests List */}
      {requests.length > 0 ? (
        <div className="space-y-4">
          {requests.map((request) => (
            <div key={request.id} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">
                    {request.title_en}
                  </h3>
                  <p className="text-sm text-gray-600 mt-1">
                    {t('common.created_at')}: {formatDate(request.created_at)}
                  </p>
                </div>
                
                <div className="text-right">
                  <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(request.status)}`}>
                    {getStatusText(request.status)}
                  </span>
                  
                  {request.my_proposal && (
                    <div className="mt-2">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getProposalStatusColor(request.my_proposal.status)}`}>
                        {getStatusText(request.my_proposal.status)}
                      </span>
                    </div>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                  <span className="text-sm text-gray-600">{t('customer.profession')}:</span>
                  <p className="font-medium">{request.profession.name_en}</p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('agency.quantity_required')}:</span>
                  <p className="font-medium">{request.quantity_required}</p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('agency.quantity_awarded')}:</span>
                  <p className="font-medium">{request.quantity_awarded}</p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('agency.remaining')}:</span>
                  <p className="font-medium">
                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                      getRemainingQuantity(request) > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    }`}>
                      {getRemainingQuantity(request)}
                    </span>
                  </p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                  <span className="text-sm text-gray-600">{t('agency.unit_price_range')}:</span>
                  <p className="font-medium">
                    SAR {request.unit_price_range_min.toLocaleString()} - {request.unit_price_range_max.toLocaleString()}
                  </p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('agency.lead_time')}:</span>
                  <p className="font-medium">{request.lead_time_days} {t('common.days')}</p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('agency.valid_until')}:</span>
                  <p className="font-medium">{formatDate(request.valid_until)}</p>
                </div>
              </div>

              <div className="border-t border-gray-200 pt-4">
                <p className="text-sm text-gray-600 mb-4">
                  {request.description_en}
                </p>
                
                <div className="flex justify-end space-x-3">
                  {isEligibleForProposal(request) && !request.my_proposal && (
                    <button
                      onClick={() => handleSubmitProposal(request.id)}
                      className="btn-primary"
                    >
                      {t('agency.submit_proposal')}
                    </button>
                  )}
                  
                  {request.my_proposal && (
                    <button
                      onClick={() => handleViewProposal(request.id)}
                      className="btn-secondary"
                    >
                      {t('agency.view_proposal')}
                    </button>
                  )}
                  
                  {!isEligibleForProposal(request) && (
                    <span className="text-sm text-gray-500">
                      {t('agency.not_eligible')}
                    </span>
                  )}
                </div>
              </div>
            </div>
          ))}
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

export default RequestsPage;