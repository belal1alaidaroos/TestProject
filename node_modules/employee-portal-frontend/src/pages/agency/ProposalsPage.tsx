import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { agencyAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface Proposal {
  id: string;
  proposal_number: string;
  request_id: string;
  offered_qty: number;
  unit_price: number;
  lead_time_days: number;
  valid_until: string;
  notes: string;
  status: string;
  created_at: string;
  updated_at: string;
  request: {
    id: string;
    request_number: string;
    title_en: string;
    title_ar: string;
    profession: {
      name_en: string;
      name_ar: string;
    };
    quantity_required: number;
    quantity_awarded: number;
  };
  attachment?: {
    id: string;
    filename: string;
    url: string;
  };
}

interface Filters {
  status?: string;
  request_id?: string;
}

const ProposalsPage: React.FC = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  
  const [proposals, setProposals] = useState<Proposal[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState<Filters>({});
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Load proposals
  useEffect(() => {
    const loadProposals = async () => {
      try {
        setLoading(true);
        setError(null);
        
        const params = {
          ...filters,
          page: currentPage,
          per_page: 10,
        };
        
        const response = await agencyAPI.getProposals(params);
        
        if (response.data.success) {
          setProposals(response.data.data.data || []);
          setTotalPages(response.data.data.last_page || 1);
        } else {
          setError('Failed to load proposals');
        }
      } catch (error: any) {
        console.error('Failed to load proposals:', error);
        setError(error.response?.data?.message || 'Failed to load proposals');
      } finally {
        setLoading(false);
      }
    };

    loadProposals();
  }, [filters, currentPage]);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Submitted':
        return 'bg-blue-100 text-blue-800';
      case 'Approved':
        return 'bg-green-100 text-green-800';
      case 'PartiallyApproved':
        return 'bg-yellow-100 text-yellow-800';
      case 'Rejected':
        return 'bg-red-100 text-red-800';
      case 'Withdrawn':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    return t(`status.${status.toLowerCase()}`);
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

  const handleEditProposal = (proposalId: string) => {
    // Navigate to edit proposal page (to be implemented)
    navigate(`/edit-proposal/${proposalId}`);
  };

  const handleWithdrawProposal = async (proposalId: string) => {
    if (!window.confirm('Are you sure you want to withdraw this proposal?')) {
      return;
    }
    
    try {
      await agencyAPI.withdrawProposal(proposalId);
      setToast({
        type: 'success',
        message: t('agency.proposal_withdrawn'),
      });
      
      // Reload proposals
      const response = await agencyAPI.getProposals({
        ...filters,
        page: currentPage,
        per_page: 10,
      });
      
      if (response.data.success) {
        setProposals(response.data.data.data || []);
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to withdraw proposal',
      });
    }
  };

  const handleDownloadAttachment = (attachment: any) => {
    if (attachment && attachment.url) {
      window.open(attachment.url, '_blank');
    }
  };

  const canEditProposal = (proposal: Proposal) => {
    return proposal.status === 'Submitted';
  };

  const canWithdrawProposal = (proposal: Proposal) => {
    return proposal.status === 'Submitted' || proposal.status === 'PartiallyApproved';
  };

  if (loading && proposals.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">
          {t('agency.proposals')}
        </h1>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="form-label">{t('common.status')}</label>
            <select
              value={filters.status || ''}
              onChange={(e) => handleFilterChange({ ...filters, status: e.target.value || undefined })}
              className="input-field"
            >
              <option value="">{t('common.all')}</option>
              <option value="Submitted">{t('status.submitted')}</option>
              <option value="Approved">{t('status.approved')}</option>
              <option value="PartiallyApproved">{t('status.partially_approved')}</option>
              <option value="Rejected">{t('status.rejected')}</option>
              <option value="Withdrawn">{t('status.withdrawn')}</option>
            </select>
          </div>
          
          <div>
            <label className="form-label">{t('agency.request_number')}</label>
            <input
              type="text"
              value={filters.request_id || ''}
              onChange={(e) => handleFilterChange({ ...filters, request_id: e.target.value || undefined })}
              className="input-field"
              placeholder="REQ-12345678"
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

      {/* Proposals List */}
      {proposals.length > 0 ? (
        <div className="space-y-4">
          {proposals.map((proposal) => (
            <div key={proposal.id} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">
                    {t('agency.proposal')} #{proposal.proposal_number}
                  </h3>
                  <p className="text-sm text-gray-600 mt-1">
                    {t('agency.request')} #{proposal.request.request_number}
                  </p>
                  <p className="text-sm text-gray-600">
                    {t('common.created_at')}: {formatDate(proposal.created_at)}
                  </p>
                </div>
                
                <div className="text-right">
                  <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(proposal.status)}`}>
                    {getStatusText(proposal.status)}
                  </span>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                  <span className="text-sm text-gray-600">{t('agency.request_title')}:</span>
                  <p className="font-medium">{proposal.request.title_en}</p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('customer.profession')}:</span>
                  <p className="font-medium">{proposal.request.profession.name_en}</p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('agency.offered_qty')}:</span>
                  <p className="font-medium">{proposal.offered_qty}</p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('agency.unit_price')}:</span>
                  <p className="font-medium">SAR {proposal.unit_price.toLocaleString()}</p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                  <span className="text-sm text-gray-600">{t('agency.total_amount')}:</span>
                  <p className="text-lg font-bold text-primary-600">
                    SAR {(proposal.offered_qty * proposal.unit_price).toLocaleString()}
                  </p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('agency.lead_time')}:</span>
                  <p className="font-medium">{proposal.lead_time_days} {t('common.days')}</p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('agency.valid_until')}:</span>
                  <p className="font-medium">{formatDate(proposal.valid_until)}</p>
                </div>
              </div>

              {proposal.notes && (
                <div className="mb-4">
                  <span className="text-sm text-gray-600">{t('agency.notes')}:</span>
                  <p className="text-sm text-gray-900 mt-1">{proposal.notes}</p>
                </div>
              )}

              {proposal.attachment && (
                <div className="mb-4">
                  <span className="text-sm text-gray-600">{t('agency.attachment')}:</span>
                  <button
                    onClick={() => handleDownloadAttachment(proposal.attachment)}
                    className="ml-2 text-sm text-primary-600 hover:text-primary-700 underline"
                  >
                    {proposal.attachment.filename}
                  </button>
                </div>
              )}

              <div className="border-t border-gray-200 pt-4">
                <div className="flex justify-end space-x-3">
                  {canEditProposal(proposal) && (
                    <button
                      onClick={() => handleEditProposal(proposal.id)}
                      className="btn-secondary"
                    >
                      {t('agency.edit_proposal')}
                    </button>
                  )}
                  
                  {canWithdrawProposal(proposal) && (
                    <button
                      onClick={() => handleWithdrawProposal(proposal.id)}
                      className="btn-danger"
                    >
                      {t('agency.withdraw_proposal')}
                    </button>
                  )}
                  
                  <button
                    onClick={() => navigate('/requests')}
                    className="btn-primary"
                  >
                    {t('agency.view_request')}
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="text-center py-12">
          <p className="text-gray-500">{t('common.no_data')}</p>
          <button
            onClick={() => navigate('/requests')}
            className="mt-4 btn-primary"
          >
            {t('agency.browse_requests')}
          </button>
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

export default ProposalsPage;