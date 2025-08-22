import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useParams, useNavigate } from 'react-router-dom';
import { customerAPI } from '../../services/api';
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
  worker: {
    id: string;
    name_en: string;
    name_ar: string;
    nationality: {
      name_en: string;
      name_ar: string;
    };
    profession: {
      name_en: string;
      name_ar: string;
    };
    agency: {
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
  reservation: {
    id: string;
    expires_at: string;
  };
}

interface Invoice {
  id: string;
  invoice_number: string;
  amount: number;
  status: string;
  due_date: string;
  created_at: string;
}

const ContractPage: React.FC = () => {
  const { t } = useTranslation();
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  
  const [contract, setContract] = useState<Contract | null>(null);
  const [invoice, setInvoice] = useState<Invoice | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [countdown, setCountdown] = useState<number>(0);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Load contract data
  useEffect(() => {
    const loadContract = async () => {
      if (!id) return;
      
      try {
        setLoading(true);
        setError(null);
        
        // Load contract (this would be a new API endpoint)
        // For now, we'll simulate the data
        const mockContract: Contract = {
          id,
          contract_number: `CON-${id.slice(0, 8).toUpperCase()}`,
          customer_id: 'customer-1',
          worker_id: 'worker-1',
          package_id: 'package-1',
          start_date: '2024-02-01',
          end_date: '2025-02-01',
          total_amount: 15000,
          status: 'AwaitingPayment',
          created_at: '2024-01-15T10:30:00Z',
          worker: {
            id: 'worker-1',
            name_en: 'John Doe',
            name_ar: 'جون دو',
            nationality: {
              name_en: 'Filipino',
              name_ar: 'فلبيني',
            },
            profession: {
              name_en: 'Domestic Worker',
              name_ar: 'عامل منزلي',
            },
            agency: {
              name_en: 'ABC Agency',
              name_ar: 'وكالة ABC',
            },
          },
          package: {
            id: 'package-1',
            name_en: 'Yearly Package',
            name_ar: 'الباقة السنوية',
            duration: 'Year',
            price: 15000,
          },
          reservation: {
            id: 'reservation-1',
            expires_at: new Date(Date.now() + 10 * 60 * 1000).toISOString(), // 10 minutes from now
          },
        };
        
        setContract(mockContract);
        
        // Load invoice
        const mockInvoice: Invoice = {
          id: 'invoice-1',
          invoice_number: `INV-${id.slice(0, 8).toUpperCase()}`,
          amount: 15000,
          status: 'Pending',
          due_date: new Date(Date.now() + 10 * 60 * 1000).toISOString(),
          created_at: '2024-01-15T10:30:00Z',
        };
        
        setInvoice(mockInvoice);
        
        // Calculate countdown for payment
        const expiresAt = new Date(mockInvoice.due_date).getTime();
        const now = Date.now();
        const remaining = Math.max(0, Math.floor((expiresAt - now) / 1000));
        setCountdown(remaining);
        
      } catch (error: any) {
        console.error('Failed to load contract:', error);
        setError(error.response?.data?.message || 'Failed to load contract');
      } finally {
        setLoading(false);
      }
    };

    loadContract();
  }, [id]);

  // Countdown timer for payment
  useEffect(() => {
    if (countdown <= 0 || contract?.status !== 'AwaitingPayment') return;

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
  }, [countdown, contract?.status]);

  const formatTime = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Draft':
        return 'bg-gray-100 text-gray-800';
      case 'AwaitingPayment':
        return 'bg-yellow-100 text-yellow-800';
      case 'Active':
        return 'bg-green-100 text-green-800';
      case 'Cancelled':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    return t(`status.${status.toLowerCase()}`);
  };

  const handlePreparePayment = async () => {
    if (!contract) return;
    
    try {
      const response = await customerAPI.preparePayment(contract.id);
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: 'Payment prepared successfully',
        });
        
        // Navigate to payment page
        navigate(`/payment/${contract.id}`);
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to prepare payment',
      });
    }
  };

  const handleCancelContract = async () => {
    if (!contract) return;
    
    if (!window.confirm('Are you sure you want to cancel this contract?')) {
      return;
    }
    
    try {
      await customerAPI.cancelContract(contract.id);
      setToast({
        type: 'success',
        message: 'Contract cancelled successfully',
      });
      navigate('/workers');
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to cancel contract',
      });
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  if (loading) {
    return <LoadingSpinner text="Loading contract..." />;
  }

  if (!contract) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Contract not found</p>
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
            {t('customer.contract')} #{contract.contract_number}
          </h1>
          <p className="text-sm text-gray-600 mt-1">
            {t('common.created_at')}: {formatDate(contract.created_at)}
          </p>
        </div>
        
        <div className="text-right">
          <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(contract.status)}`}>
            {getStatusText(contract.status)}
          </span>
          
          {contract.status === 'AwaitingPayment' && countdown > 0 && (
            <div className="mt-2">
              <p className="text-sm text-gray-600">
                {t('customer.payment_expires')}
              </p>
              <p className={`text-lg font-semibold ${countdown < 60 ? 'text-red-600' : 'text-gray-900'}`}>
                {formatTime(countdown)}
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Contract Details */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          {t('customer.contract_details')}
        </h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Package Information */}
          <div>
            <h3 className="font-medium text-gray-900 mb-3">
              {t('customer.package_info')}
            </h3>
            <div className="space-y-2">
              <div>
                <span className="text-sm text-gray-600">{t('customer.package')}:</span>
                <p className="font-medium">{contract.package.name_en}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.duration')}:</span>
                <p className="font-medium">{contract.package.duration}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.start_date')}:</span>
                <p className="font-medium">{formatDate(contract.start_date)}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.end_date')}:</span>
                <p className="font-medium">{formatDate(contract.end_date)}</p>
              </div>
            </div>
          </div>

          {/* Worker Information */}
          <div>
            <h3 className="font-medium text-gray-900 mb-3">
              {t('customer.worker_details')}
            </h3>
            <div className="space-y-2">
              <div>
                <span className="text-sm text-gray-600">{t('customer.name')}:</span>
                <p className="font-medium">{contract.worker.name_en}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.nationality')}:</span>
                <p className="font-medium">{contract.worker.nationality.name_en}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.profession')}:</span>
                <p className="font-medium">{contract.worker.profession.name_en}</p>
              </div>
              <div>
                <span className="text-sm text-gray-600">{t('customer.agency')}:</span>
                <p className="font-medium">{contract.worker.agency.name_en}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Invoice Information */}
      {invoice && (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            {t('customer.invoice')} #{invoice.invoice_number}
          </h2>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <span className="text-sm text-gray-600">{t('customer.amount')}:</span>
              <p className="text-2xl font-bold text-primary-600">
                SAR {invoice.amount.toLocaleString()}
              </p>
            </div>
            <div>
              <span className="text-sm text-gray-600">{t('customer.status')}:</span>
              <p className="font-medium">{getStatusText(invoice.status)}</p>
            </div>
            <div>
              <span className="text-sm text-gray-600">{t('customer.due_date')}:</span>
              <p className="font-medium">{formatDate(invoice.due_date)}</p>
            </div>
          </div>
        </div>
      )}

      {/* Actions */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          {t('common.actions')}
        </h2>
        
        <div className="flex flex-wrap gap-4">
          {contract.status === 'AwaitingPayment' && (
            <button
              onClick={handlePreparePayment}
              disabled={countdown === 0}
              className="btn-primary"
            >
              {t('customer.prepare_payment')}
            </button>
          )}
          
          {contract.status === 'Draft' && (
            <button
              onClick={handleCancelContract}
              className="btn-danger"
            >
              {t('common.cancel')}
            </button>
          )}
          
          <button
            onClick={() => navigate('/workers')}
            className="btn-secondary"
          >
            {t('common.back')}
          </button>
        </div>
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

export default ContractPage;