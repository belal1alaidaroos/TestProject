import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useParams, useNavigate } from 'react-router-dom';
import { customerAPI, lookupsAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface Reservation {
  id: string;
  worker_id: string;
  customer_id: string;
  state: string;
  expires_at: string;
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
}

interface Package {
  id: string;
  name_en: string;
  name_ar: string;
  duration: string;
  price: number;
}

const ReservationPage: React.FC = () => {
  const { t } = useTranslation();
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  
  const [reservation, setReservation] = useState<Reservation | null>(null);
  const [packages, setPackages] = useState<Package[]>([]);
  const [selectedPackage, setSelectedPackage] = useState<string>('');
  const [startDate, setStartDate] = useState<string>('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [countdown, setCountdown] = useState<number>(0);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Load reservation and packages
  useEffect(() => {
    const loadData = async () => {
      if (!id) return;
      
      try {
        setLoading(true);
        setError(null);
        
        // Load packages
        const packagesRes = await lookupsAPI.getPackages();
        setPackages(packagesRes.data.data || []);
        
        // Load reservation (this would be a new API endpoint)
        // For now, we'll simulate the data
        const mockReservation: Reservation = {
          id,
          worker_id: 'worker-1',
          customer_id: 'customer-1',
          state: 'AwaitingContract',
          expires_at: new Date(Date.now() + 5 * 60 * 1000).toISOString(), // 5 minutes from now
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
        };
        
        setReservation(mockReservation);
        
        // Calculate initial countdown
        const expiresAt = new Date(mockReservation.expires_at).getTime();
        const now = Date.now();
        const remaining = Math.max(0, Math.floor((expiresAt - now) / 1000));
        setCountdown(remaining);
        
      } catch (error: any) {
        console.error('Failed to load reservation:', error);
        setError(error.response?.data?.message || 'Failed to load reservation');
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, [id]);

  // Countdown timer
  useEffect(() => {
    if (countdown <= 0) {
      // Reservation expired
      setToast({
        type: 'error',
        message: 'Reservation has expired',
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

  const calculateEndDate = (startDate: string, duration: string): string => {
    if (!startDate) return '';
    
    const start = new Date(startDate);
    let end = new Date(start);
    
    switch (duration) {
      case 'Week':
        end.setDate(start.getDate() + 7);
        break;
      case 'Month':
        end.setMonth(start.getMonth() + 1);
        break;
      case 'Year':
        end.setFullYear(start.getFullYear() + 1);
        break;
      case 'TwoYears':
        end.setFullYear(start.getFullYear() + 2);
        break;
      default:
        end = start;
    }
    
    return end.toISOString().split('T')[0];
  };

  const getSelectedPackageDetails = () => {
    return packages.find(pkg => pkg.id === selectedPackage);
  };

  const handleCreateContract = async () => {
    if (!reservation || !selectedPackage || !startDate) {
      setToast({
        type: 'error',
        message: 'Please fill in all required fields',
      });
      return;
    }

    try {
      setSubmitting(true);
      
      const packageDetails = getSelectedPackageDetails();
      const endDate = calculateEndDate(startDate, packageDetails?.duration || '');
      
      const contractData = {
        package_id: selectedPackage,
        start_date: startDate,
        end_date: endDate,
        total_amount: packageDetails?.price || 0,
      };
      
      const response = await customerAPI.createContract(reservation.id, contractData);
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t('customer.contract_created'),
        });
        
        // Navigate to contract page
        navigate(`/contract/${response.data.data.contract.id}`);
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to create contract',
      });
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancelReservation = async () => {
    if (!reservation) return;
    
    try {
      await customerAPI.cancelReservation(reservation.id);
      setToast({
        type: 'success',
        message: 'Reservation cancelled successfully',
      });
      navigate('/workers');
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to cancel reservation',
      });
    }
  };

  if (loading) {
    return <LoadingSpinner text="Loading reservation..." />;
  }

  if (!reservation) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Reservation not found</p>
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
        <h1 className="text-2xl font-bold text-gray-900">
          {t('customer.reservation')}
        </h1>
        <div className="text-right">
          <p className="text-sm text-gray-600">
            {t('customer.reservation_expires')}
          </p>
          <p className={`text-lg font-semibold ${countdown < 60 ? 'text-red-600' : 'text-gray-900'}`}>
            {formatTime(countdown)}
          </p>
        </div>
      </div>

      {/* Worker Information */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          {t('customer.worker_details')}
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p className="text-sm text-gray-600">{t('customer.name')}</p>
            <p className="font-medium">{reservation.worker.name_en}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">{t('customer.nationality')}</p>
            <p className="font-medium">{reservation.worker.nationality.name_en}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">{t('customer.profession')}</p>
            <p className="font-medium">{reservation.worker.profession.name_en}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">{t('customer.agency')}</p>
            <p className="font-medium">{reservation.worker.agency.name_en}</p>
          </div>
        </div>
      </div>

      {/* Contract Creation Form */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          {t('customer.create_contract')}
        </h2>
        
        <div className="space-y-4">
          {/* Package Selection */}
          <div>
            <label className="form-label">
              {t('customer.select_package')} *
            </label>
            <select
              value={selectedPackage}
              onChange={(e) => setSelectedPackage(e.target.value)}
              className="input-field"
            >
              <option value="">{t('common.select')}</option>
              {packages.map((pkg) => (
                <option key={pkg.id} value={pkg.id}>
                  {pkg.name_en} - {pkg.duration} (SAR {pkg.price.toLocaleString()})
                </option>
              ))}
            </select>
          </div>

          {/* Start Date */}
          <div>
            <label className="form-label">
              {t('customer.start_date')} *
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              min={new Date().toISOString().split('T')[0]}
              className="input-field"
            />
          </div>

          {/* End Date (calculated) */}
          {selectedPackage && startDate && (
            <div>
              <label className="form-label">
                {t('customer.end_date')}
              </label>
              <input
                type="date"
                value={calculateEndDate(startDate, getSelectedPackageDetails()?.duration || '')}
                disabled
                className="input-field bg-gray-50"
              />
            </div>
          )}

          {/* Total Amount */}
          {selectedPackage && (
            <div>
              <label className="form-label">
                {t('customer.total_amount')}
              </label>
              <div className="text-2xl font-bold text-primary-600">
                SAR {getSelectedPackageDetails()?.price.toLocaleString()}
              </div>
            </div>
          )}
        </div>

        {/* Actions */}
        <div className="flex justify-between items-center mt-6 pt-6 border-t border-gray-200">
          <button
            onClick={handleCancelReservation}
            className="btn-secondary"
          >
            {t('common.cancel')}
          </button>
          
          <button
            onClick={handleCreateContract}
            disabled={!selectedPackage || !startDate || submitting || countdown === 0}
            className="btn-primary"
          >
            {submitting ? t('common.loading') : t('customer.create_contract')}
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

export default ReservationPage;