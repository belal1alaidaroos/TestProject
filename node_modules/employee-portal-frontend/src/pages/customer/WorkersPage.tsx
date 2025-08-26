import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { customerAPI, lookupsAPI } from '../../services/api';
import WorkerCard from '../../components/WorkerCard';
import FilterPanel from '../../components/FilterPanel';
import LoadingSpinner from '../../components/LoadingSpinner';
import Toast from '../../components/Toast';

interface Worker {
  id: string;
  name_en: string;
  name_ar: string;
  date_of_birth: string;
  nationality: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  profession: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  agency: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  experience_years: number;
  photo_file_id?: string;
  status: string;
}

interface Filters {
  nationality_id?: string;
  profession_id?: string;
  min_age?: number;
  max_age?: number;
  experience_years?: number;
}

const WorkersPage: React.FC = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  
  const [workers, setWorkers] = useState<Worker[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState<Filters>({});
  const [nationalities, setNationalities] = useState([]);
  const [professions, setProfessions] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Load lookups
  useEffect(() => {
    const loadLookups = async () => {
      try {
        const [nationalitiesRes, professionsRes] = await Promise.all([
          lookupsAPI.getNationalities(),
          lookupsAPI.getProfessions(),
        ]);
        
        setNationalities(nationalitiesRes.data.data || []);
        setProfessions(professionsRes.data.data || []);
      } catch (error) {
        console.error('Failed to load lookups:', error);
      }
    };

    loadLookups();
  }, []);

  // Load workers
  useEffect(() => {
    const loadWorkers = async () => {
      try {
        setLoading(true);
        setError(null);
        
        const params = {
          ...filters,
          page: currentPage,
          per_page: 12,
        };
        
        const response = await customerAPI.getWorkers(params);
        
        if (response.data.success) {
          setWorkers(response.data.data.data || []);
          setTotalPages(response.data.data.last_page || 1);
        } else {
          setError('Failed to load workers');
        }
      } catch (error: any) {
        console.error('Failed to load workers:', error);
        setError(error.response?.data?.message || 'Failed to load workers');
      } finally {
        setLoading(false);
      }
    };

    loadWorkers();
  }, [filters, currentPage]);

  const handleReserveWorker = async (workerId: string) => {
    try {
      const response = await customerAPI.reserveWorker(workerId);
      
      if (response.data.success) {
        setToast({
          type: 'success',
          message: t('customer.reserved'),
        });
        
        // Navigate to reservation page
        navigate(`/reservation/${response.data.data.reservation.id}`);
      }
    } catch (error: any) {
      setToast({
        type: 'error',
        message: error.response?.data?.message || 'Failed to reserve worker',
      });
    }
  };

  const handleFilterChange = (newFilters: Filters) => {
    setFilters(newFilters);
    setCurrentPage(1); // Reset to first page when filters change
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };

  if (loading && workers.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">
          {t('customer.workers')}
        </h1>
      </div>

      {/* Filters */}
      <FilterPanel
        filters={filters}
        nationalities={nationalities}
        professions={professions}
        onFilterChange={handleFilterChange}
      />

      {/* Error Message */}
      {error && (
        <div className="rounded-md bg-red-50 p-4">
          <div className="text-sm text-red-700">{error}</div>
        </div>
      )}

      {/* Workers Grid */}
      {workers.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {workers.map((worker) => (
            <WorkerCard
              key={worker.id}
              worker={worker}
              onReserve={() => handleReserveWorker(worker.id)}
            />
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

export default WorkersPage;