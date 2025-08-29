import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { 
  Bell,
  BellOff,
  Search,
  Filter,
  CheckCircle,
  Circle,
  Trash2,
  AlertCircle,
  Info,
  CheckCheck,
  Clock
} from 'lucide-react';
import { employeeAPI } from '../../services/api';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface Notification {
  id: string;
  user_id: string;
  type: string;
  title: string;
  message: string;
  data?: any;
  read_at: string | null;
  created_at: string;
}

const Notifications: React.FC = () => {
  const { t } = useTranslation();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [unreadOnly, setUnreadOnly] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [unreadCount, setUnreadCount] = useState(0);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [statistics, setStatistics] = useState<any>(null);
  
  // Delete confirmation
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [notificationToDelete, setNotificationToDelete] = useState<Notification | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [markingAll, setMarkingAll] = useState(false);

  useEffect(() => {
    fetchNotifications();
    fetchStatistics();
  }, [searchTerm, typeFilter, unreadOnly, currentPage]);

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const response = await employeeAPI.getNotifications({
        search: searchTerm,
        type: typeFilter,
        unread_only: unreadOnly,
        page: currentPage,
        per_page: 20
      });

      if (response.data.success) {
        setNotifications(response.data.data.data || []);
        setTotalPages(response.data.data.last_page || 1);
        setUnreadCount(response.data.unread_count || 0);
      }
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
      setToast({
        type: 'error',
        message: t('employee.notifications_fetch_error')
      });
    } finally {
      setLoading(false);
    }
  };

  const fetchStatistics = async () => {
    try {
      const response = await employeeAPI.getNotificationStatistics();
      if (response.data.success) {
        setStatistics(response.data.data);
      }
    } catch (error) {
      console.error('Failed to fetch statistics:', error);
    }
  };

  const getNotificationIcon = (type: string) => {
    switch (type) {
      case 'info':
        return <Info className="h-5 w-5 text-blue-500" />;
      case 'warning':
        return <AlertCircle className="h-5 w-5 text-yellow-500" />;
      case 'error':
        return <AlertCircle className="h-5 w-5 text-red-500" />;
      case 'success':
        return <CheckCircle className="h-5 w-5 text-green-500" />;
      default:
        return <Bell className="h-5 w-5 text-gray-500" />;
    }
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffDays > 7) {
      return date.toLocaleDateString();
    } else if (diffDays > 0) {
      return t('common.days_ago', { count: diffDays });
    } else if (diffHours > 0) {
      return t('common.hours_ago', { count: diffHours });
    } else if (diffMins > 0) {
      return t('common.minutes_ago', { count: diffMins });
    } else {
      return t('common.just_now');
    }
  };

  const handleMarkAsRead = async (notification: Notification) => {
    if (notification.read_at) return;
    
    try {
      await employeeAPI.markNotificationAsRead(notification.id);
      
      // Update local state
      setNotifications(prev => prev.map(n => 
        n.id === notification.id ? { ...n, read_at: new Date().toISOString() } : n
      ));
      setUnreadCount(prev => Math.max(0, prev - 1));
      
      // Update statistics
      fetchStatistics();
    } catch (error) {
      console.error('Failed to mark as read:', error);
    }
  };

  const handleMarkAllAsRead = async () => {
    setMarkingAll(true);
    try {
      await employeeAPI.markAllNotificationsAsRead();
      
      setToast({
        type: 'success',
        message: t('employee.all_notifications_marked_read')
      });
      
      // Refresh notifications
      fetchNotifications();
      fetchStatistics();
    } catch (error) {
      setToast({
        type: 'error',
        message: t('employee.mark_all_error')
      });
    } finally {
      setMarkingAll(false);
    }
  };

  const handleDeleteNotification = (notification: Notification) => {
    setNotificationToDelete(notification);
    setShowDeleteConfirm(true);
  };

  const confirmDelete = async () => {
    if (!notificationToDelete) return;
    
    setDeleting(true);
    try {
      await employeeAPI.deleteNotification(notificationToDelete.id);
      
      setToast({
        type: 'success',
        message: t('employee.notification_deleted')
      });
      
      // Refresh notifications
      fetchNotifications();
      fetchStatistics();
      setShowDeleteConfirm(false);
      setNotificationToDelete(null);
    } catch (error) {
      setToast({
        type: 'error',
        message: t('employee.delete_error')
      });
    } finally {
      setDeleting(false);
    }
  };

  const handleSearch = (value: string) => {
    setSearchTerm(value);
    setCurrentPage(1);
  };

  const handleTypeFilter = (value: string) => {
    setTypeFilter(value);
    setCurrentPage(1);
  };

  const handleUnreadFilter = (checked: boolean) => {
    setUnreadOnly(checked);
    setCurrentPage(1);
  };

  if (loading && notifications.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center">
            {t('employee.notifications')}
            {unreadCount > 0 && (
              <span className="ml-3 px-3 py-1 text-sm font-medium bg-red-100 text-red-800 rounded-full">
                {unreadCount}
              </span>
            )}
          </h1>
          <p className="text-gray-600 mt-1">
            {t('employee.notifications_description')}
          </p>
        </div>
        {unreadCount > 0 && (
          <button 
            onClick={handleMarkAllAsRead} 
            className="btn-secondary"
            disabled={markingAll}
          >
            <CheckCheck className="h-4 w-4 mr-2" />
            {markingAll ? t('common.processing') : t('employee.mark_all_read')}
          </button>
        )}
      </div>

      {/* Statistics */}
      {statistics && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.total_notifications')}</p>
                <p className="text-2xl font-semibold text-gray-900">{statistics.total_notifications}</p>
              </div>
              <Bell className="h-8 w-8 text-gray-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.unread_notifications')}</p>
                <p className="text-2xl font-semibold text-red-600">{statistics.unread_notifications}</p>
              </div>
              <BellOff className="h-8 w-8 text-red-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.info_notifications')}</p>
                <p className="text-2xl font-semibold text-blue-600">
                  {statistics.notifications_by_type?.info || 0}
                </p>
              </div>
              <Info className="h-8 w-8 text-blue-400" />
            </div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{t('employee.warning_notifications')}</p>
                <p className="text-2xl font-semibold text-yellow-600">
                  {statistics.notifications_by_type?.warning || 0}
                </p>
              </div>
              <AlertCircle className="h-8 w-8 text-yellow-400" />
            </div>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
            <input
              type="text"
              placeholder={t('common.search')}
              value={searchTerm}
              onChange={(e) => handleSearch(e.target.value)}
              className="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>

          <select
            value={typeFilter}
            onChange={(e) => handleTypeFilter(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">{t('common.all_types')}</option>
            <option value="info">{t('notification.info')}</option>
            <option value="warning">{t('notification.warning')}</option>
            <option value="error">{t('notification.error')}</option>
            <option value="success">{t('notification.success')}</option>
          </select>

          <label className="flex items-center">
            <input
              type="checkbox"
              checked={unreadOnly}
              onChange={(e) => handleUnreadFilter(e.target.checked)}
              className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
            <span className="ml-2 text-sm text-gray-700">{t('employee.unread_only')}</span>
          </label>

          <button onClick={fetchNotifications} className="btn-secondary">
            {t('common.refresh')}
          </button>
        </div>
      </div>

      {/* Notifications List */}
      <div className="space-y-4">
        {notifications.map((notification) => (
          <div
            key={notification.id}
            className={`bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow ${
              !notification.read_at ? 'border-l-4 border-primary-500' : ''
            }`}
            onClick={() => handleMarkAsRead(notification)}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-3 flex-1">
                <div className="flex-shrink-0 mt-1">
                  {getNotificationIcon(notification.type)}
                </div>
                <div className="flex-1">
                  <div className="flex items-center space-x-2">
                    <h3 className={`text-sm font-medium ${!notification.read_at ? 'text-gray-900' : 'text-gray-600'}`}>
                      {notification.title}
                    </h3>
                    {!notification.read_at && (
                      <Circle className="h-2 w-2 fill-current text-primary-500" />
                    )}
                  </div>
                  <p className="mt-1 text-sm text-gray-600">
                    {notification.message}
                  </p>
                  <div className="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                    <div className="flex items-center">
                      <Clock className="h-3 w-3 mr-1" />
                      {formatDate(notification.created_at)}
                    </div>
                    {notification.read_at && (
                      <div className="flex items-center">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        {t('notification.read')}
                      </div>
                    )}
                  </div>
                </div>
              </div>
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  handleDeleteNotification(notification);
                }}
                className="ml-4 text-gray-400 hover:text-red-600 transition-colors"
                title={t('common.delete')}
              >
                <Trash2 className="h-4 w-4" />
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Empty State */}
      {notifications.length === 0 && !loading && (
        <div className="text-center py-12">
          <BellOff className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-medium text-gray-900">{t('employee.no_notifications')}</h3>
          <p className="mt-1 text-sm text-gray-500">{t('employee.no_notifications_description')}</p>
        </div>
      )}

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="bg-white px-4 py-3 flex items-center justify-between border border-gray-200 sm:px-6 rounded-lg">
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

      {/* Delete Confirmation Dialog */}
      {showDeleteConfirm && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3 text-center">
              <AlertCircle className="mx-auto mb-4 h-12 w-12 text-red-600" />
              <h3 className="text-lg leading-6 font-medium text-gray-900">
                {t('employee.delete_notification_confirm')}
              </h3>
              <div className="mt-2 px-7 py-3">
                <p className="text-sm text-gray-500">
                  {t('employee.delete_notification_warning')}
                </p>
              </div>
              <div className="items-center px-4 py-3">
                <button
                  onClick={confirmDelete}
                  disabled={deleting}
                  className="btn-danger mr-2"
                >
                  {deleting ? t('common.deleting') : t('common.delete')}
                </button>
                <button
                  onClick={() => {
                    setShowDeleteConfirm(false);
                    setNotificationToDelete(null);
                  }}
                  disabled={deleting}
                  className="btn-secondary"
                >
                  {t('common.cancel')}
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

export default Notifications;