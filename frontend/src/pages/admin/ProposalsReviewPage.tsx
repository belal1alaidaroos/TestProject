import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { adminAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface Proposal {
  id: string;
  proposal_number: string;
  request_id: string;
  agency_id: string;
  offered_qty: number;
  unit_price: number;
  lead_time_days: number;
  valid_until: string;
  notes: string;
  status: string;
  created_at: string;
  agency: {
    id: string;
    name_en: string;
    name_ar: string;
    phone: string;
    email: string;
  };
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
    unit_price_range_min: number;
    unit_price_range_max: number;
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
  agency_id?: string;
}

const ProposalsReviewPage: React.FC = () => {
  const { t } = useTranslation();
  
  const [proposals, setProposals] = useState<Proposal[]>([]);
  const [agencies, setAgencies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState<Filters>({});
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [selectedProposal, setSelectedProposal] = useState<Proposal | null>(null);
  const [showReviewModal, setShowReviewModal] = useState(false);
  const [reviewAction, setReviewAction] = useState<'approve' | 'reject' | 'partial' | null>(null);
  const [reviewNotes, setReviewNotes] = useState('');
  const [processing, setProcessing] = useState(false);

  // Load agencies for filtering
  useEffect(() => {
    const loadAgencies = async () => {
      try {
        const response = await adminAPI.getAgencies();
        setAgencies(response.data.data || []);
      } catch (error) {
        console.error('Failed to load agencies:', error);
      }
    };

    loadAgencies();
  }, []);

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
        
        const response = await adminAPI.getProposals(params);
        
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

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'SAR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const handleFilterChange = (newFilters: Filters) => {
    setFilters(newFilters);
    setCurrentPage(1);
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };

  const handleReviewProposal = (proposal: Proposal, action: 'approve' | 'reject' | 'partial') => {
    setSelectedProposal(proposal);
    setReviewAction(action);
    setReviewNotes('');
    setShowReviewModal(true);
  };

  const handleSubmitReview = async () => {
    if (!selectedProposal || !reviewAction) return;

    try {
      setProcessing(true);
      
      let endpoint = '';
      let data: any = { notes: reviewNotes };
      
      switch (reviewAction) {
        case 'approve':
          endpoint = 'approve';
          break;
        case 'reject':
          endpoint = 'reject';
          break;
        case 'partial':
          endpoint = 'partial-approve';
          data.approved_qty = Math.floor(selectedProposal.offered_qty * 0.5); // Example: approve 50%
          break;
      }
      
      const response = await adminAPI[`${endpoint}Proposal`](selectedProposal.id, data);
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t(`admin.proposal_${reviewAction}d`),
        });
        
        // Reload proposals
        const reloadResponse = await adminAPI.getProposals({
          ...filters,
          page: currentPage,
          per_page: 10,
        });
        
        if (reloadResponse.data.success) {
          setProposals(reloadResponse.data.data.data || []);
        }
        
        setShowReviewModal(false);
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to process review',
      });
    } finally {
      setProcessing(false);
    }
  };

  const handleDownloadAttachment = (attachment: any) => {
    if (attachment && attachment.url) {
      window.open(attachment.url, '_blank');
    }
  };

  const getRemainingQuantity = (proposal: Proposal) => {
    return proposal.request.quantity_required - proposal.request.quantity_awarded;
  };

  if (loading && proposals.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">
          {t('admin.proposals_review')}
        </h1>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
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
          
          <div>
            <label className="form-label">{t('admin.agency')}</label>
            <select
              value={filters.agency_id || ''}
              onChange={(e) => handleFilterChange({ ...filters, agency_id: e.target.value || undefined })}
              className="input-field"
            >
              <option value="">{t('common.all')}</option>
              {agencies.map((agency: any) => (
                <option key={agency.id} value={agency.id}>
                  {agency.name_en}
                </option>
              ))}
            </select>
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
                    {t('admin.agency')}: {proposal.agency.name_en}
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
                  <p className="font-medium">{formatCurrency(proposal.unit_price)}</p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                  <span className="text-sm text-gray-600">{t('agency.total_amount')}:</span>
                  <p className="text-lg font-bold text-primary-600">
                    {formatCurrency(proposal.offered_qty * proposal.unit_price)}
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

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <span className="text-sm text-gray-600">{t('admin.agency_contact')}:</span>
                  <p className="font-medium">{proposal.agency.phone}</p>
                  <p className="text-sm text-gray-600">{proposal.agency.email}</p>
                </div>
                
                <div>
                  <span className="text-sm text-gray-600">{t('admin.request_requirements')}:</span>
                  <p className="font-medium">
                    {t('admin.required')}: {proposal.request.quantity_required} | 
                    {t('admin.awarded')}: {proposal.request.quantity_awarded} | 
                    {t('admin.remaining')}: {getRemainingQuantity(proposal)}
                  </p>
                  <p className="text-sm text-gray-600">
                    {t('admin.price_range')}: {formatCurrency(proposal.request.unit_price_range_min)} - {formatCurrency(proposal.request.unit_price_range_max)}
                  </p>
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
                  {proposal.status === 'Submitted' && (
                    <>
                      <button
                        onClick={() => handleReviewProposal(proposal, 'approve')}
                        className="btn-success"
                      >
                        {t('admin.approve')}
                      </button>
                      <button
                        onClick={() => handleReviewProposal(proposal, 'partial')}
                        className="btn-warning"
                      >
                        {t('admin.partial_approve')}
                      </button>
                      <button
                        onClick={() => handleReviewProposal(proposal, 'reject')}
                        className="btn-danger"
                      >
                        {t('admin.reject')}
                      </button>
                    </>
                  )}
                  
                  {proposal.status !== 'Submitted' && (
                    <span className="text-sm text-gray-500">
                      {t('admin.already_reviewed')}
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

      {/* Review Modal */}
      {showReviewModal && selectedProposal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {t(`admin.${reviewAction}_proposal`)}
              </h3>
              
              <div className="mb-4">
                <p className="text-sm text-gray-600 mb-2">
                  {t('agency.proposal')} #{selectedProposal.proposal_number}
                </p>
                <p className="text-sm text-gray-600">
                  {t('admin.agency')}: {selectedProposal.agency.name_en}
                </p>
              </div>
              
              <div className="mb-4">
                <label className="form-label">
                  {t('admin.review_notes')}
                </label>
                <textarea
                  value={reviewNotes}
                  onChange={(e) => setReviewNotes(e.target.value)}
                  rows={4}
                  className="input-field"
                  placeholder={t('admin.review_notes_placeholder')}
                />
              </div>
              
              <div className="flex justify-end space-x-3">
                <button
                  onClick={() => setShowReviewModal(false)}
                  className="btn-secondary"
                  disabled={processing}
                >
                  {t('common.cancel')}
                </button>
                <button
                  onClick={handleSubmitReview}
                  className={`btn-${reviewAction === 'approve' ? 'success' : reviewAction === 'reject' ? 'danger' : 'warning'}`}
                  disabled={processing}
                >
                  {processing ? t('common.loading') : t(`admin.${reviewAction}`)}
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

export default ProposalsReviewPage;