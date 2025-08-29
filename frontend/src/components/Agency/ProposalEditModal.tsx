import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { X, AlertTriangle, FileText, Upload } from 'lucide-react';
import { agencyAPI } from '../../services/api';
import LoadingSpinner from '../LoadingSpinner';

interface ProposalEditModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  proposal: any;
}

const ProposalEditModal: React.FC<ProposalEditModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
  proposal
}) => {
  const { t, i18n } = useTranslation();
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // Form data
  const [formData, setFormData] = useState({
    offered_qty: '',
    unit_price: '',
    lead_time_days: '',
    valid_until: '',
    notes: '',
    attachment: null as File | null
  });

  // File upload states
  const [attachmentPreview, setAttachmentPreview] = useState<string | null>(null);
  const [uploadProgress, setUploadProgress] = useState(0);

  useEffect(() => {
    if (isOpen && proposal) {
      // Pre-fill form with existing proposal data
      setFormData({
        offered_qty: proposal.offered_qty?.toString() || '',
        unit_price: proposal.unit_price?.toString() || '',
        lead_time_days: proposal.lead_time_days?.toString() || '',
        valid_until: proposal.valid_until ? proposal.valid_until.split('T')[0] : '',
        notes: proposal.notes || '',
        attachment: null
      });
      
      // Set existing attachment preview if any
      if (proposal.attachment) {
        setAttachmentPreview(proposal.attachment.filename);
      }
    }
  }, [isOpen, proposal]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      // Validate file size (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        setError(t('agency.file_too_large'));
        return;
      }
      
      // Validate file type (PDF, DOC, DOCX)
      const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
      if (!allowedTypes.includes(file.type)) {
        setError(t('agency.invalid_file_type'));
        return;
      }
      
      setFormData(prev => ({ ...prev, attachment: file }));
      setAttachmentPreview(file.name);
      setError(null);
    }
  };

  const validateForm = () => {
    // Check required fields
    if (!formData.offered_qty || !formData.unit_price || !formData.lead_time_days || !formData.valid_until) {
      setError(t('common.fill_required_fields'));
      return false;
    }
    
    // Validate offered quantity
    const offeredQty = parseInt(formData.offered_qty);
    if (isNaN(offeredQty) || offeredQty <= 0) {
      setError(t('agency.invalid_quantity'));
      return false;
    }
    
    // Check if offered quantity exceeds requested quantity
    if (proposal.request && offeredQty > proposal.request.quantity_required) {
      setError(t('agency.quantity_exceeds_requested'));
      return false;
    }
    
    // Validate unit price
    const unitPrice = parseFloat(formData.unit_price);
    if (isNaN(unitPrice) || unitPrice <= 0) {
      setError(t('agency.invalid_price'));
      return false;
    }
    
    // Validate lead time
    const leadTime = parseInt(formData.lead_time_days);
    if (isNaN(leadTime) || leadTime <= 0) {
      setError(t('agency.invalid_lead_time'));
      return false;
    }
    
    // Validate valid until date
    const validUntil = new Date(formData.valid_until);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (validUntil <= today) {
      setError(t('agency.invalid_validity_date'));
      return false;
    }
    
    return true;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }
    
    setSubmitting(true);
    setError(null);

    try {
      const submitData = new FormData();
      submitData.append('offered_qty', formData.offered_qty);
      submitData.append('unit_price', formData.unit_price);
      submitData.append('lead_time_days', formData.lead_time_days);
      submitData.append('valid_until', formData.valid_until);
      submitData.append('notes', formData.notes);
      
      if (formData.attachment) {
        submitData.append('attachment', formData.attachment);
      }

      await agencyAPI.updateProposal(proposal.id, submitData);
      
      onSuccess();
      onClose();
    } catch (error: any) {
      setError(error.response?.data?.message || t('agency.update_proposal_error'));
    } finally {
      setSubmitting(false);
    }
  };

  const calculateTotalPrice = () => {
    const qty = parseFloat(formData.offered_qty) || 0;
    const price = parseFloat(formData.unit_price) || 0;
    return (qty * price).toFixed(2);
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div className="relative top-20 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
        {/* Header */}
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-medium text-gray-900">
            {t('agency.edit_proposal')} - {proposal?.proposal_number}
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
            {/* Request Information (Read-only) */}
            <div className="bg-gray-50 p-4 rounded-lg">
              <h4 className="text-md font-medium text-gray-900 mb-3">{t('agency.request_information')}</h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('agency.request_number')}
                  </label>
                  <p className="mt-1 text-sm text-gray-900">{proposal?.request?.request_number}</p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('agency.request_title')}
                  </label>
                  <p className="mt-1 text-sm text-gray-900">
                    {i18n.language === 'ar' ? proposal?.request?.title_ar : proposal?.request?.title_en}
                  </p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('agency.profession')}
                  </label>
                  <p className="mt-1 text-sm text-gray-900">
                    {i18n.language === 'ar' 
                      ? proposal?.request?.profession?.name_ar 
                      : proposal?.request?.profession?.name_en}
                  </p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('agency.quantity_required')}
                  </label>
                  <p className="mt-1 text-sm text-gray-900">{proposal?.request?.quantity_required}</p>
                </div>
              </div>
            </div>

            {/* Proposal Details */}
            <div>
              <h4 className="text-md font-medium text-gray-900 mb-4">{t('agency.proposal_details')}</h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('agency.offered_quantity')} *
                  </label>
                  <input
                    type="number"
                    name="offered_qty"
                    value={formData.offered_qty}
                    onChange={handleChange}
                    min="1"
                    max={proposal?.request?.quantity_required}
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                  <p className="mt-1 text-xs text-gray-500">
                    {t('agency.max_quantity')}: {proposal?.request?.quantity_required}
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('agency.unit_price')} (SAR) *
                  </label>
                  <input
                    type="number"
                    name="unit_price"
                    value={formData.unit_price}
                    onChange={handleChange}
                    min="0.01"
                    step="0.01"
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('agency.lead_time_days')} *
                  </label>
                  <input
                    type="number"
                    name="lead_time_days"
                    value={formData.lead_time_days}
                    onChange={handleChange}
                    min="1"
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    {t('agency.valid_until')} *
                  </label>
                  <input
                    type="date"
                    name="valid_until"
                    value={formData.valid_until}
                    onChange={handleChange}
                    min={new Date().toISOString().split('T')[0]}
                    required
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>
              </div>

              <div className="mt-4">
                <label className="block text-sm font-medium text-gray-700">
                  {t('agency.notes')}
                </label>
                <textarea
                  name="notes"
                  value={formData.notes}
                  onChange={handleChange}
                  rows={3}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  placeholder={t('agency.proposal_notes_placeholder')}
                />
              </div>

              {/* Attachment */}
              <div className="mt-4">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  {t('agency.attachment')}
                </label>
                <div className="flex items-center space-x-4">
                  <label className="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                    <span className="flex items-center">
                      <Upload className="h-4 w-4 mr-2" />
                      {attachmentPreview ? t('agency.change_file') : t('agency.upload_file')}
                    </span>
                    <input
                      type="file"
                      className="sr-only"
                      accept=".pdf,.doc,.docx"
                      onChange={handleFileChange}
                    />
                  </label>
                  {attachmentPreview && (
                    <div className="flex items-center text-sm text-gray-600">
                      <FileText className="h-4 w-4 mr-1" />
                      {attachmentPreview}
                    </div>
                  )}
                </div>
                <p className="mt-1 text-xs text-gray-500">
                  {t('agency.attachment_hint')} (PDF, DOC, DOCX - Max 5MB)
                </p>
              </div>
            </div>

            {/* Total Price Display */}
            <div className="bg-primary-50 p-4 rounded-lg">
              <div className="flex justify-between items-center">
                <span className="text-lg font-medium text-gray-900">
                  {t('agency.total_price')}:
                </span>
                <span className="text-2xl font-bold text-primary-600">
                  SAR {calculateTotalPrice()}
                </span>
              </div>
            </div>

            {/* Warning for status change */}
            <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4">
              <div className="flex">
                <AlertTriangle className="h-5 w-5 text-yellow-400" />
                <div className="ml-3">
                  <p className="text-sm text-yellow-700">
                    {t('agency.edit_proposal_warning')}
                  </p>
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
                {submitting ? t('common.saving') : t('common.save_changes')}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
};

export default ProposalEditModal;