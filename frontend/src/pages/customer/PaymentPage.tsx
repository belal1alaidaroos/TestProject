import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { customerAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface PaymentFormData {
  payment_method: string;
  card_number?: string;
  expiry_date?: string;
  cvv?: string;
  cardholder_name?: string;
  bank_name?: string;
  account_number?: string;
  iban?: string;
  terms_accepted: boolean;
}

interface Contract {
  id: string;
  contract_number: string;
  total_amount: number;
  worker: {
    name_en: string;
    name_ar: string;
  };
  package: {
    name_en: string;
    name_ar: string;
  };
}

interface Invoice {
  id: string;
  invoice_number: string;
  amount: number;
  due_date: string;
}

const PaymentPage: React.FC = () => {
  const { t } = useTranslation();
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  
  const [contract, setContract] = useState<Contract | null>(null);
  const [invoice, setInvoice] = useState<Invoice | null>(null);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [countdown, setCountdown] = useState<number>(0);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors, isValid },
  } = useForm<PaymentFormData>({
    mode: 'onChange',
    defaultValues: {
      payment_method: 'card',
      terms_accepted: false,
    },
  });

  const paymentMethod = watch('payment_method');
  const termsAccepted = watch('terms_accepted');

  // Load contract and invoice data
  useEffect(() => {
    const loadData = async () => {
      if (!id) return;
      
      try {
        setLoading(true);
        setError(null);
        
        // Load contract (this would be a new API endpoint)
        // For now, we'll simulate the data
        const mockContract: Contract = {
          id,
          contract_number: `CON-${id.slice(0, 8).toUpperCase()}`,
          total_amount: 15000,
          worker: {
            name_en: 'John Doe',
            name_ar: 'جون دو',
          },
          package: {
            name_en: 'Yearly Package',
            name_ar: 'الباقة السنوية',
          },
        };
        
        setContract(mockContract);
        
        // Load invoice
        const mockInvoice: Invoice = {
          id: 'invoice-1',
          invoice_number: `INV-${id.slice(0, 8).toUpperCase()}`,
          amount: 15000,
          due_date: new Date(Date.now() + 10 * 60 * 1000).toISOString(),
        };
        
        setInvoice(mockInvoice);
        
        // Calculate countdown for payment
        const expiresAt = new Date(mockInvoice.due_date).getTime();
        const now = Date.now();
        const remaining = Math.max(0, Math.floor((expiresAt - now) / 1000));
        setCountdown(remaining);
        
      } catch (error: any) {
        console.error('Failed to load payment data:', error);
        setError(error.response?.data?.message || 'Failed to load payment data');
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, [id]);

  // Countdown timer
  useEffect(() => {
    if (countdown <= 0) {
      setToast({
        type: 'error',
        message: 'Payment session has expired',
      });
      setTimeout(() => {
        navigate('/workers');
      }, 3000);
      return;
    }

    const timer = setInterval(() => {
      setCountdown(prev => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [countdown, navigate]);

  const formatTime = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const handlePaymentSubmit = async (data: PaymentFormData) => {
    if (!contract || !invoice) return;
    
    try {
      setProcessing(true);
      
      // Prepare payment data
      const paymentData = {
        contract_id: contract.id,
        invoice_id: invoice.id,
        amount: invoice.amount,
        payment_method: data.payment_method,
        payment_details: {
          ...(data.payment_method === 'card' && {
            card_number: data.card_number,
            expiry_date: data.expiry_date,
            cvv: data.cvv,
            cardholder_name: data.cardholder_name,
          }),
          ...(data.payment_method === 'bank_transfer' && {
            bank_name: data.bank_name,
            account_number: data.account_number,
            iban: data.iban,
          }),
        },
      };
      
      const response = await customerAPI.confirmPayment(contract.id, paymentData);
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t('customer.payment_success'),
        });
        
        // Navigate to success page or contract page
        setTimeout(() => {
          navigate(`/contract/${contract.id}`);
        }, 2000);
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Payment failed',
      });
    } finally {
      setProcessing(false);
    }
  };

  const handleCancelPayment = () => {
    if (window.confirm('Are you sure you want to cancel the payment?')) {
      navigate(`/contract/${id}`);
    }
  };

  if (loading) {
    return <LoadingSpinner text="Loading payment..." />;
  }

  if (!contract || !invoice) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Payment data not found</p>
        <button
          onClick={() => navigate('/workers')}
          className="mt-4 btn-primary"
        >
          {t('common.back')}
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">
            {t('customer.payment')}
          </h1>
          <p className="text-sm text-gray-600 mt-1">
            {t('customer.contract')} #{contract.contract_number}
          </p>
        </div>
        
        <div className="text-right">
          <p className="text-sm text-gray-600">
            {t('customer.payment_expires')}
          </p>
          <p className={`text-lg font-semibold ${countdown < 60 ? 'text-red-600' : 'text-gray-900'}`}>
            {formatTime(countdown)}
          </p>
        </div>
      </div>

      {/* Payment Summary */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          {t('customer.payment_summary')}
        </h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 className="font-medium text-gray-900 mb-3">
              {t('customer.contract_info')}
            </h3>
            <div className="space-y-2">
              <div>
                <span className="text-sm text-gray-600">{t('customer.worker')}:</span>
                <p className="font-medium">{contract.worker.name_en}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.package')}:</span>
                <p className="font-medium">{contract.package.name_en}</p>
              </div>
            </div>
          </div>
          
          <div>
            <h3 className="font-medium text-gray-900 mb-3">
              {t('customer.invoice_info')}
            </h3>
            <div className="space-y-2">
              <div>
                <span className="text-sm text-gray-600">{t('customer.invoice')}:</span>
                <p className="font-medium">#{invoice.invoice_number}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.amount')}:</span>
                <p className="text-2xl font-bold text-primary-600">
                  SAR {invoice.amount.toLocaleString()}
                </p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.due_date')}:</span>
                <p className="font-medium">{formatDate(invoice.due_date)}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Payment Form */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          {t('customer.payment_method')}
        </h2>
        
        <form onSubmit={handleSubmit(handlePaymentSubmit)} className="space-y-6">
          {/* Payment Method Selection */}
          <div>
            <label className="form-label">
              {t('customer.select_payment_method')} *
            </label>
            <div className="space-y-3">
              <label className="flex items-center">
                <input
                  type="radio"
                  value="card"
                  {...register('payment_method', { required: true })}
                  className="mr-3"
                />
                <span>{t('customer.credit_card')}</span>
              </label>
              <label className="flex items-center">
                <input
                  type="radio"
                  value="bank_transfer"
                  {...register('payment_method', { required: true })}
                  className="mr-3"
                />
                <span>{t('customer.bank_transfer')}</span>
              </label>
            </div>
          </div>

          {/* Credit Card Fields */}
          {paymentMethod === 'card' && (
            <div className="space-y-4">
              <div>
                <label className="form-label">
                  {t('customer.card_number')} *
                </label>
                <input
                  type="text"
                  {...register('card_number', {
                    required: paymentMethod === 'card',
                    pattern: {
                      value: /^[0-9]{16}$/,
                      message: t('validation.invalid_card'),
                    },
                  })}
                  className={`input-field ${errors.card_number ? 'border-red-500' : ''}`}
                  placeholder="1234 5678 9012 3456"
                  maxLength={16}
                />
                {errors.card_number && (
                  <p className="form-error">{errors.card_number.message}</p>
                )}
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="form-label">
                    {t('customer.expiry_date')} *
                  </label>
                  <input
                    type="text"
                    {...register('expiry_date', {
                      required: paymentMethod === 'card',
                      pattern: {
                        value: /^(0[1-9]|1[0-2])\/([0-9]{2})$/,
                        message: t('validation.invalid_expiry'),
                      },
                    })}
                    className={`input-field ${errors.expiry_date ? 'border-red-500' : ''}`}
                    placeholder="MM/YY"
                    maxLength={5}
                  />
                  {errors.expiry_date && (
                    <p className="form-error">{errors.expiry_date.message}</p>
                  )}
                </div>

                <div>
                  <label className="form-label">
                    {t('customer.cvv')} *
                  </label>
                  <input
                    type="text"
                    {...register('cvv', {
                      required: paymentMethod === 'card',
                      pattern: {
                        value: /^[0-9]{3,4}$/,
                        message: t('validation.invalid_cvv'),
                      },
                    })}
                    className={`input-field ${errors.cvv ? 'border-red-500' : ''}`}
                    placeholder="123"
                    maxLength={4}
                  />
                  {errors.cvv && (
                    <p className="form-error">{errors.cvv.message}</p>
                  )}
                </div>

                <div>
                  <label className="form-label">
                    {t('customer.cardholder_name')} *
                  </label>
                  <input
                    type="text"
                    {...register('cardholder_name', {
                      required: paymentMethod === 'card',
                    })}
                    className={`input-field ${errors.cardholder_name ? 'border-red-500' : ''}`}
                    placeholder="John Doe"
                  />
                  {errors.cardholder_name && (
                    <p className="form-error">{errors.cardholder_name.message}</p>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Bank Transfer Fields */}
          {paymentMethod === 'bank_transfer' && (
            <div className="space-y-4">
              <div>
                <label className="form-label">
                  {t('customer.bank_name')} *
                </label>
                <input
                  type="text"
                  {...register('bank_name', {
                    required: paymentMethod === 'bank_transfer',
                  })}
                  className={`input-field ${errors.bank_name ? 'border-red-500' : ''}`}
                  placeholder="Saudi National Bank"
                />
                {errors.bank_name && (
                  <p className="form-error">{errors.bank_name.message}</p>
                )}
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="form-label">
                    {t('customer.account_number')} *
                  </label>
                  <input
                    type="text"
                    {...register('account_number', {
                      required: paymentMethod === 'bank_transfer',
                    })}
                    className={`input-field ${errors.account_number ? 'border-red-500' : ''}`}
                    placeholder="1234567890"
                  />
                  {errors.account_number && (
                    <p className="form-error">{errors.account_number.message}</p>
                  )}
                </div>

                <div>
                  <label className="form-label">
                    {t('customer.iban')} *
                  </label>
                  <input
                    type="text"
                    {...register('iban', {
                      required: paymentMethod === 'bank_transfer',
                    })}
                    className={`input-field ${errors.iban ? 'border-red-500' : ''}`}
                    placeholder="SA0380000000608010167519"
                  />
                  {errors.iban && (
                    <p className="form-error">{errors.iban.message}</p>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Terms and Conditions */}
          <div>
            <label className="flex items-start">
              <input
                type="checkbox"
                {...register('terms_accepted', {
                  required: true,
                })}
                className="mr-3 mt-1"
              />
              <span className="text-sm text-gray-600">
                {t('customer.terms_acceptance')}
              </span>
            </label>
            {errors.terms_accepted && (
              <p className="form-error">{t('validation.terms_required')}</p>
            )}
          </div>

          {/* Actions */}
          <div className="flex justify-between items-center pt-6 border-t border-gray-200">
            <button
              type="button"
              onClick={handleCancelPayment}
              className="btn-secondary"
            >
              {t('common.cancel')}
            </button>
            
            <button
              type="submit"
              disabled={!isValid || processing || countdown === 0}
              className="btn-primary"
            >
              {processing ? t('common.loading') : t('customer.confirm_payment')}
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

export default PaymentPage;