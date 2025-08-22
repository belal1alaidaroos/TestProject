import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { agencyAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface ProposalFormData {
  offered_qty: number;
  unit_price: number;
  lead_time_days: number;
  valid_until: string;
  notes: string;
  attachment?: File;
}

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
  profession: {
    id: string;
    name_en: string;
    name_ar: string;
  };
}

const SubmitProposalPage: React.FC = () => {
  const { t } = useTranslation();
  const { requestId } = useParams<{ requestId: string }>();
  const navigate = useNavigate();
  
  const [request, setRequest] = useState<RecruitmentRequest | null>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors, isValid },
    setValue,
  } = useForm<ProposalFormData>({
    mode: 'onChange',
    defaultValues: {
      offered_qty: 0,
      unit_price: 0,
      lead_time_days: 0,
      valid_until: '',
      notes: '',
    },
  });

  const offeredQty = watch('offered_qty');
  const unitPrice = watch('unit_price');

  // Load recruitment request
  useEffect(() => {
    const loadRequest = async () => {
      if (!requestId) return;
      
      try {
        setLoading(true);
        setError(null);
        
        // Load request (this would be a new API endpoint)
        // For now, we'll simulate the data
        const mockRequest: RecruitmentRequest = {
          id: requestId,
          request_number: `REQ-${requestId.slice(0, 8).toUpperCase()}`,
          title_en: 'Domestic Workers Required',
          title_ar: 'مطلوب عمال منزليين',
          description_en: 'We are looking for experienced domestic workers for our company. The workers should have at least 2 years of experience in household work.',
          description_ar: 'نحن نبحث عن عمال منزليين ذوي خبرة لشركتنا. يجب أن يكون لدى العمال خبرة لا تقل عن سنتين في العمل المنزلي.',
          profession_id: 'profession-1',
          quantity_required: 10,
          quantity_awarded: 3,
          unit_price_range_min: 8000,
          unit_price_range_max: 12000,
          lead_time_days: 30,
          valid_until: '2024-03-15',
          status: 'Open',
          profession: {
            id: 'profession-1',
            name_en: 'Domestic Worker',
            name_ar: 'عامل منزلي',
          },
        };
        
        setRequest(mockRequest);
        
        // Set default values
        setValue('offered_qty', Math.min(5, mockRequest.quantity_required - mockRequest.quantity_awarded));
        setValue('unit_price', mockRequest.unit_price_range_min);
        setValue('lead_time_days', mockRequest.lead_time_days);
        setValue('valid_until', mockRequest.valid_until);
        
      } catch (error: any) {
        console.error('Failed to load request:', error);
        setError(error.response?.data?.message || 'Failed to load request');
      } finally {
        setLoading(false);
      }
    };

    loadRequest();
  }, [requestId, setValue]);

  const getRemainingQuantity = () => {
    if (!request) return 0;
    return request.quantity_required - request.quantity_awarded;
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      // Validate file size (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        setToast({
          type: 'error',
          message: 'File size must be less than 5MB',
        });
        event.target.value = '';
        return;
      }
      
      // Validate file type
      const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
      if (!allowedTypes.includes(file.type)) {
        setToast({
          type: 'error',
          message: 'File type not supported. Please upload PDF, DOC, DOCX, JPG, or PNG files.',
        });
        event.target.value = '';
        return;
      }
    }
  };

  const handleSubmitProposal = async (data: ProposalFormData) => {
    if (!request) return;
    
    try {
      setSubmitting(true);
      
      // Validate remaining quantity
      const remaining = getRemainingQuantity();
      if (data.offered_qty > remaining) {
        setToast({
          type: 'error',
          message: `You cannot offer more than ${remaining} workers`,
        });
        return;
      }
      
      // Validate unit price range
      if (data.unit_price < request.unit_price_range_min || data.unit_price > request.unit_price_range_max) {
        setToast({
          type: 'error',
          message: `Unit price must be between SAR ${request.unit_price_range_min.toLocaleString()} and SAR ${request.unit_price_range_max.toLocaleString()}`,
        });
        return;
      }
      
      // Prepare form data for file upload
      const formData = new FormData();
      formData.append('request_id', request.id);
      formData.append('offered_qty', data.offered_qty.toString());
      formData.append('unit_price', data.unit_price.toString());
      formData.append('lead_time_days', data.lead_time_days.toString());
      formData.append('valid_until', data.valid_until);
      formData.append('notes', data.notes);
      
      if (data.attachment) {
        formData.append('attachment', data.attachment);
      }
      
      const response = await agencyAPI.submitProposal(request.id, formData);
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t('agency.proposal_submitted'),
        });
        
        // Navigate back to requests page
        setTimeout(() => {
          navigate('/requests');
        }, 2000);
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to submit proposal',
      });
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancel = () => {
    navigate('/requests');
  };

  if (loading) {
    return <LoadingSpinner text="Loading request..." />;
  }

  if (!request) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Request not found</p>
        <button
          onClick={() => navigate('/requests')}
          className="mt-4 btn-primary"
        >
          {t('common.back')}
        </button>
      </div>
    );
  }

  const remainingQuantity = getRemainingQuantity();

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">
            {t('agency.submit_proposal')}
          </h1>
          <p className="text-sm text-gray-600 mt-1">
            {t('agency.request')} #{request.request_number}
          </p>
        </div>
        
        <div className="text-right">
          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
            remainingQuantity > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
          }`}>
            {t('agency.remaining')}: {remainingQuantity}
          </span>
        </div>
      </div>

      {/* Request Details */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          {t('agency.request_details')}
        </h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 className="font-medium text-gray-900 mb-3">
              {t('agency.basic_info')}
            </h3>
            <div className="space-y-2">
              <div>
                <span className="text-sm text-gray-600">{t('agency.title')}:</span>
                <p className="font-medium">{request.title_en}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.profession')}:</span>
                <p className="font-medium">{request.profession.name_en}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('agency.quantity_required')}:</span>
                <p className="font-medium">{request.quantity_required}</p>
              </div>
            </div>
          </div>
          
          <div>
            <h3 className="font-medium text-gray-900 mb-3">
              {t('agency.requirements')}
            </h3>
            <div className="space-y-2">
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
          </div>
        </div>
        
        <div className="mt-4 pt-4 border-t border-gray-200">
          <span className="text-sm text-gray-600">{t('agency.description')}:</span>
          <p className="text-sm text-gray-900 mt-1">{request.description_en}</p>
        </div>
      </div>

      {/* Proposal Form */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          {t('agency.proposal_form')}
        </h2>
        
        <form onSubmit={handleSubmit(handleSubmitProposal)} className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Offered Quantity */}
            <div>
              <label className="form-label">
                {t('agency.offered_qty')} *
              </label>
              <input
                type="number"
                min="1"
                max={remainingQuantity}
                {...register('offered_qty', {
                  required: t('validation.required'),
                  min: { value: 1, message: t('validation.min_quantity') },
                  max: { value: remainingQuantity, message: t('validation.max_quantity', { max: remainingQuantity }) },
                })}
                className={`input-field ${errors.offered_qty ? 'border-red-500' : ''}`}
              />
              {errors.offered_qty && (
                <p className="form-error">{errors.offered_qty.message}</p>
              )}
              <p className="text-xs text-gray-500 mt-1">
                {t('agency.max_available')}: {remainingQuantity}
              </p>
            </div>

            {/* Unit Price */}
            <div>
              <label className="form-label">
                {t('agency.unit_price')} (SAR) *
              </label>
              <input
                type="number"
                min={request.unit_price_range_min}
                max={request.unit_price_range_max}
                step="100"
                {...register('unit_price', {
                  required: t('validation.required'),
                  min: { value: request.unit_price_range_min, message: t('validation.min_price', { min: request.unit_price_range_min.toLocaleString() }) },
                  max: { value: request.unit_price_range_max, message: t('validation.max_price', { max: request.unit_price_range_max.toLocaleString() }) },
                })}
                className={`input-field ${errors.unit_price ? 'border-red-500' : ''}`}
              />
              {errors.unit_price && (
                <p className="form-error">{errors.unit_price.message}</p>
              )}
              <p className="text-xs text-gray-500 mt-1">
                {t('agency.price_range')}: SAR {request.unit_price_range_min.toLocaleString()} - {request.unit_price_range_max.toLocaleString()}
              </p>
            </div>

            {/* Lead Time */}
            <div>
              <label className="form-label">
                {t('agency.lead_time')} ({t('common.days')}) *
              </label>
              <input
                type="number"
                min="1"
                max="365"
                {...register('lead_time_days', {
                  required: t('validation.required'),
                  min: { value: 1, message: t('validation.min_lead_time') },
                  max: { value: 365, message: t('validation.max_lead_time') },
                })}
                className={`input-field ${errors.lead_time_days ? 'border-red-500' : ''}`}
              />
              {errors.lead_time_days && (
                <p className="form-error">{errors.lead_time_days.message}</p>
              )}
            </div>

            {/* Valid Until */}
            <div>
              <label className="form-label">
                {t('agency.valid_until')} *
              </label>
              <input
                type="date"
                min={new Date().toISOString().split('T')[0]}
                {...register('valid_until', {
                  required: t('validation.required'),
                })}
                className={`input-field ${errors.valid_until ? 'border-red-500' : ''}`}
              />
              {errors.valid_until && (
                <p className="form-error">{errors.valid_until.message}</p>
              )}
            </div>
          </div>

          {/* Notes */}
          <div>
            <label className="form-label">
              {t('agency.notes')}
            </label>
            <textarea
              rows={4}
              {...register('notes', {
                maxLength: { value: 1000, message: t('validation.max_length', { max: 1000 }) },
              })}
              className={`input-field ${errors.notes ? 'border-red-500' : ''}`}
              placeholder={t('agency.notes_placeholder')}
            />
            {errors.notes && (
              <p className="form-error">{errors.notes.message}</p>
            )}
          </div>

          {/* Attachment */}
          <div>
            <label className="form-label">
              {t('agency.attachment')}
            </label>
            <input
              type="file"
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
              onChange={handleFileChange}
              className="input-field"
            />
            <p className="text-xs text-gray-500 mt-1">
              {t('agency.file_requirements')}
            </p>
          </div>

          {/* Total Amount Preview */}
          <div className="bg-gray-50 rounded-lg p-4">
            <h3 className="font-medium text-gray-900 mb-2">
              {t('agency.total_amount_preview')}
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <span className="text-sm text-gray-600">{t('agency.offered_qty')}:</span>
                <p className="font-medium">{offeredQty}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('agency.unit_price')}:</span>
                <p className="font-medium">SAR {unitPrice.toLocaleString()}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('agency.total')}:</span>
                <p className="text-lg font-bold text-primary-600">
                  SAR {(offeredQty * unitPrice).toLocaleString()}
                </p>
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="flex justify-between items-center pt-6 border-t border-gray-200">
            <button
              type="button"
              onClick={handleCancel}
              className="btn-secondary"
            >
              {t('common.cancel')}
            </button>
            
            <button
              type="submit"
              disabled={!isValid || submitting || remainingQuantity === 0}
              className="btn-primary"
            >
              {submitting ? t('common.loading') : t('agency.submit_proposal')}
            </button>
          </div>
        </form>
      </div>

      {/* Error Message */}
      {error && (
        <div className="rounded-md bg-red-50 p-4">
          <div className="text-sm text-red-700">{error}</div>
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

export default SubmitProposalPage;