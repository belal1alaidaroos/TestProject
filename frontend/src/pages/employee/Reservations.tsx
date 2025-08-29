import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { 
  Calendar,
  Clock,
  User,
  Search,
  Filter,
  CheckCircle,
  XCircle,
  Timer,
  AlertTriangle,
  Phone,
  Mail
} from 'lucide-react';
import { employeeAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface Reservation {
  id: string;
  worker_id: string;
  customer_id: string;
  state: string;
  expires_at: string;
  created_at: string;
  worker: {
    id: string;
    name_en: string;
    name_ar: string;
    passport_number: string;
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
  customer: {
    id: string;
    name_en: string;
    name_ar: string;
    phone: string;
    email: string;
  };
  remaining_minutes?: number;
}

const Reservations: React.FC = () => {
  const { t, i18n } = useTranslation();
  const [reservations, setReservations] = useState<Reservation[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [stateFilter, setStateFilter] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [statistics, setStatistics] = useState<any>(null);
  
  // Process modal states
  const [showProcessModal, setShowProcessModal] = useState(false);
  const [selectedReservation, setSelectedReservation] = useState<Reservation | null>(null);
  const [processAction, setProcessAction] = useState<'approve' | 'reject' | 'extend'>('approve');
  const [extensionMinutes, setExtensionMinutes] = useState(30);
  const [processNotes, setProcessNotes] = useState('');
  const [processing, setProcessing] = useState(false);

  // Timer for active reservations
  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    fetchReservations();
    fetchStatistics();
    
    // Update timer every minute
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 60000);
    
    return () => clearInterval(timer);
  }, [searchTerm, stateFilter, dateFrom, dateTo, currentPage]);

  const fetchReservations = async () => {
    try {
      setLoading(true);
      const response = await employeeAPI.getReservations({
        search: searchTerm,
        state: stateFilter,
        date_from: dateFrom,
        date_to: dateTo,
        page: currentPage,
        per_page: 10
      });

      if (response.data.success) {
        setReservations(response.data.data.data || []);
        setTotalPages(response.data.data.last_page || 1);
      }
    } catch (error) {
      console.error('Failed to fetch reservations:', error);
      setToast({
        type: 'error',
        message: t('employee.reservations_fetch_error')
      });
    } finally {
      setLoading(false);
    }
  };

  const fetchStatistics = async () => {
    try {
      const response = await employeeAPI.getReservationStatistics();
      if (response.data.success) {
        setStatistics(response.data.data);
      }
    } catch (error) {
      console.error('Failed to fetch statistics:', error);
    }
  };

  const getStateBadge = (state: string) => {
    switch (state) {
      case 'Reserved':
        return <span className="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full flex items-center">
          <Clock className="w-3 h-3 mr-1" />
          {t('reservation.reserved')}
        </span>;
      case 'Confirmed':
        return <span className="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full flex items-center">
          <CheckCircle className="w-3 h-3 mr-1" />
          {t('reservation.confirmed')}
        </span>;
      case 'Cancelled':
        return <span className="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full flex items-center">
          <XCircle className="w-3 h-3 mr-1" />
          {t('reservation.cancelled')}
        </span>;
      case 'Expired':
        return <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full flex items-center">
          <Timer className="w-3 h-3 mr-1" />
          {t('reservation.expired')}
        </span>;
      default:
        return <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">{state}</span>;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  const calculateRemainingTime = (expiresAt: string) => {
    const expiry = new Date(expiresAt);
    const now = currentTime;
    const diffMs = expiry.getTime() - now.getTime();
    
    if (diffMs <= 0) return { expired: true, text: t('reservation.expired') };
    
    const diffMins = Math.floor(diffMs / 60000);
    const hours = Math.floor(diffMins / 60);
    const minutes = diffMins % 60;
    
    if (hours > 0) {
      return { expired: false, text: `${hours}h ${minutes}m`, urgent: hours < 1 };
    }
    return { expired: false, text: `${minutes}m`, urgent: true };
  };

  const handleProcessReservation = (reservation: Reservation, action: 'approve' | 'reject' | 'extend') => {
    setSelectedReservation(reservation);
    setProcessAction(action);
    setProcessNotes('');
    setExtensionMinutes(30);
    setShowProcessModal(true);
  };

  const submitProcessReservation = async () => {
    if (!selectedReservation) return;
    
    setProcessing(true);
    try {
      const data: any = {
        action: processAction,
        notes: processNotes
      };
      
      if (processAction === 'extend') {
        data.extension_minutes = extensionMinutes;
      }
      
      await employeeAPI.processReservation(selectedReservation.id, data);
      
      setToast({
        type: 'success',
        message: t(`employee.reservation_${processAction}_success`)
      });
      
      fetchReservations();
      fetchStatistics();
      setShowProcessModal(false);
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || t('employee.process_error')
      });
    } finally {
      setProcessing(false);
    }
  };

  const handleSearch = (value: string) => {
    setSearchTerm(value);
    setCurrentPage(1);
  };

  const handleStateFilter = (value: string) => {
    setStateFilter(value);
    setCurrentPage(1);
  };

  if (loading && reservations.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">{t('employee.reservations_management')}</h1>
        <p className="text-gray-600 mt-1">
          {t('employee.reservations_description')}
        </p>
      </div>

      {/* Statistics */}
      {statistics && (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.total_reservations')}</p>
                <p className="text-2xl font-semibold text-gray-900">{statistics.total_reservations}</p>
              </div>
              <Calendar className="h-8 w-8 text-gray-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.active_reservations')}</p>
                <p className="text-2xl font-semibold text-yellow-600">{statistics.active_reservations}</p>
              </div>
              <Clock className="h-8 w-8 text-yellow-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.expired_today')}</p>
                <p className="text-2xl font-semibold text-red-600">{statistics.expired_today}</p>
              </div>
              <Timer className="h-8 w-8 text-red-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.confirmed_today')}</p>
                <p className="text-2xl font-semibold text-green-600">{statistics.confirmed_today}</p>
              </div>
              <CheckCircle className="h-8 w-8 text-green-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.expiring_soon')}</p>
                <p className="text-2xl font-semibold text-orange-600">{statistics.expiring_soon}</p>
              </div>
              <AlertTriangle className="h-8 w-8 text-orange-400" />
            </div>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow">
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
            <input
              type="text"
              placeholder={t('common.search_customer')}
              value={searchTerm}
              onChange={(e) => handleSearch(e.target.value)}
              className="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>

          <select
            value={stateFilter}
            onChange={(e) => handleStateFilter(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">{t('common.all_states')}</option>
            <option value="Reserved">{t('reservation.reserved')}</option>
            <option value="Confirmed">{t('reservation.confirmed')}</option>
            <option value="Cancelled">{t('reservation.cancelled')}</option>
            <option value="Expired">{t('reservation.expired')}</option>
          </select>

          <input
            type="date"
            value={dateFrom}
            onChange={(e) => setDateFrom(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder={t('common.from_date')}
          />

          <input
            type="date"
            value={dateTo}
            onChange={(e) => setDateTo(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder={t('common.to_date')}
          />

          <button onClick={fetchReservations} className="btn-secondary">
            {t('common.refresh')}
          </button>
        </div>
      </div>

      {/* Reservations Table */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('customer.info')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('worker.info')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('reservation.timing')}
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('reservation.status')}
              </th>
              <th className="relative px-6 py-3">
                <span className="sr-only">{t('common.actions')}</span>
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {reservations.map((reservation) => {
              const timeRemaining = reservation.state === 'Reserved' ? calculateRemainingTime(reservation.expires_at) : null;
              
              return (
                <tr key={reservation.id} className={timeRemaining?.urgent ? 'bg-red-50' : ''}>
                  <td className="px-6 py-4">
                    <div>
                      <div className="text-sm font-medium text-gray-900">
                        {i18n.language === 'ar' ? reservation.customer.name_ar : reservation.customer.name_en}
                      </div>
                      <div className="text-sm text-gray-500">
                        <Phone className="inline w-3 h-3 mr-1" />
                        {reservation.customer.phone}
                      </div>
                      <div className="text-sm text-gray-500">
                        <Mail className="inline w-3 h-3 mr-1" />
                        {reservation.customer.email}
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <div>
                      <div className="text-sm font-medium text-gray-900">
                        {reservation.worker.name_en}
                      </div>
                      <div className="text-sm text-gray-500">
                        {reservation.worker.profession.name_en} • {reservation.worker.nationality.name_en}
                      </div>
                      <div className="text-sm text-gray-500">
                        {reservation.worker.agency.name_en}
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <div>
                      <div className="text-sm text-gray-900">
                        {t('reservation.created')}: {formatDateTime(reservation.created_at)}
                      </div>
                      <div className="text-sm text-gray-500">
                        {t('reservation.expires')}: {formatDateTime(reservation.expires_at)}
                      </div>
                      {timeRemaining && !timeRemaining.expired && (
                        <div className={`text-sm font-medium ${timeRemaining.urgent ? 'text-red-600' : 'text-yellow-600'}`}>
                          <Clock className="inline w-3 h-3 mr-1" />
                          {timeRemaining.text} {t('common.remaining')}
                        </div>
                      )}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    {getStateBadge(reservation.state)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    {reservation.state === 'Reserved' && !timeRemaining?.expired && (
                      <div className="flex justify-end space-x-2">
                        <button
                          onClick={() => handleProcessReservation(reservation, 'approve')}
                          className="text-green-600 hover:text-green-900"
                          title={t('common.approve')}
                        >
                          <CheckCircle className="h-5 w-5" />
                        </button>
                        <button
                          onClick={() => handleProcessReservation(reservation, 'reject')}
                          className="text-red-600 hover:text-red-900"
                          title={t('common.reject')}
                        >
                          <XCircle className="h-5 w-5" />
                        </button>
                        <button
                          onClick={() => handleProcessReservation(reservation, 'extend')}
                          className="text-blue-600 hover:text-blue-900"
                          title={t('common.extend')}
                        >
                          <Timer className="h-5 w-5" />
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
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

      {/* Process Reservation Modal */}
      {showProcessModal && selectedReservation && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                {processAction === 'approve' && t('employee.approve_reservation')}
                {processAction === 'reject' && t('employee.reject_reservation')}
                {processAction === 'extend' && t('employee.extend_reservation')}
              </h3>
              
              <div className="mb-4 bg-gray-50 p-3 rounded">
                <p className="text-sm text-gray-700">
                  <strong>{t('customer.name')}:</strong> {selectedReservation.customer.name_en}
                </p>
                <p className="text-sm text-gray-700">
                  <strong>{t('worker.name')}:</strong> {selectedReservation.worker.name_en}
                </p>
                <p className="text-sm text-gray-700">
                  <strong>{t('reservation.expires')}:</strong> {formatDateTime(selectedReservation.expires_at)}
                </p>
              </div>

              {processAction === 'extend' && (
                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    {t('reservation.extension_minutes')} *
                  </label>
                  <select
                    value={extensionMinutes}
                    onChange={(e) => setExtensionMinutes(Number(e.target.value))}
                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  >
                    <option value={15}>15 {t('common.minutes')}</option>
                    <option value={30}>30 {t('common.minutes')}</option>
                    <option value={60}>1 {t('common.hour')}</option>
                    <option value={120}>2 {t('common.hours')}</option>
                  </select>
                </div>
              )}

              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  {t('common.notes')}
                </label>
                <textarea
                  value={processNotes}
                  onChange={(e) => setProcessNotes(e.target.value)}
                  rows={3}
                  className="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  placeholder={t('common.optional')}
                />
              </div>

              {processAction === 'approve' && (
                <div className="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
                  <div className="flex">
                    <CheckCircle className="h-5 w-5 text-green-400" />
                    <div className="ml-3">
                      <p className="text-sm text-green-700">
                        {t('reservation.approve_info')}
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {processAction === 'reject' && (
                <div className="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                  <div className="flex">
                    <XCircle className="h-5 w-5 text-red-400" />
                    <div className="ml-3">
                      <p className="text-sm text-red-700">
                        {t('reservation.reject_info')}
                      </p>
                    </div>
                  </div>
                </div>
              )}

              <div className="flex justify-end space-x-3">
                <button
                  type="button"
                  onClick={() => setShowProcessModal(false)}
                  className="btn-secondary"
                  disabled={processing}
                >
                  {t('common.cancel')}
                </button>
                <button
                  onClick={submitProcessReservation}
                  className={`btn-${processAction === 'approve' ? 'success' : processAction === 'reject' ? 'danger' : 'primary'}`}
                  disabled={processing}
                >
                  {processing ? t('common.processing') : t('common.confirm')}
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

export default Reservations;