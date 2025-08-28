import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useParams, useNavigate } from 'react-router-dom';
import { customerAPI } from '../../services/api';
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
  bio_en?: string;
  bio_ar?: string;
  skills?: string[];
  hourly_rate?: number;
  availability?: string;
}

const WorkerDetailPage: React.FC = () => {
  const { t } = useTranslation();
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  
  const [worker, setWorker] = useState<Worker | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Load worker details
  useEffect(() => {
    const loadWorker = async () => {
      if (!id) return;
      
      try {
        setLoading(true);
        setError(null);
        
        const response = await customerAPI.getWorker(id);
        
        if (response.data.success) {
          setWorker(response.data.data);
        } else {
          setError('Failed to load worker details');
        }
      } catch (error: any) {
        console.error('Failed to load worker:', error);
        setError(error.response?.data?.message || 'Failed to load worker details');
      } finally {
        setLoading(false);
      }
    };

    loadWorker();
  }, [id]);

  const handleBack = () => {
    navigate('/workers');
  };

  const handleReserve = () => {
    navigate(`/reservations/new?worker_id=${id}`);
  };

  if (loading) {
    return <LoadingSpinner />;
  }

  if (error || !worker) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="max-w-md w-full bg-white rounded-lg shadow-md p-6">
          <div className="text-center">
            <h2 className="text-2xl font-bold text-gray-900 mb-4">
              {error || 'Worker not found'}
            </h2>
            <button
              onClick={handleBack}
              className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
            >
              {t('common.back')}
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <button
            onClick={handleBack}
            className="flex items-center text-gray-600 hover:text-gray-900 mb-4 transition-colors"
          >
            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
            {t('common.back')}
          </button>
          
          <div className="flex justify-between items-start">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 mb-2">
                {worker.name_en}
              </h1>
              <p className="text-xl text-gray-600">
                {worker.profession.name_en}
              </p>
            </div>
            
            <button
              onClick={handleReserve}
              className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium"
            >
              {t('worker.reserve')}
            </button>
          </div>
        </div>

        {/* Worker Details */}
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <div className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Basic Information */}
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                  {t('worker.basicInfo')}
                </h3>
                
                <div className="space-y-3">
                  <div>
                    <span className="text-sm font-medium text-gray-500">
                      {t('worker.name')}:
                    </span>
                    <p className="text-gray-900">{worker.name_en}</p>
                  </div>
                  
                  <div>
                    <span className="text-sm font-medium text-gray-500">
                      {t('worker.nationality')}:
                    </span>
                    <p className="text-gray-900">{worker.nationality.name_en}</p>
                  </div>
                  
                  <div>
                    <span className="text-sm font-medium text-gray-500">
                      {t('worker.experience')}:
                    </span>
                    <p className="text-gray-900">
                      {worker.experience_years} {t('worker.years')}
                    </p>
                  </div>
                  
                  <div>
                    <span className="text-sm font-medium text-gray-500">
                      {t('worker.agency')}:
                    </span>
                    <p className="text-gray-900">{worker.agency.name_en}</p>
                  </div>
                  
                  {worker.hourly_rate && (
                    <div>
                      <span className="text-sm font-medium text-gray-500">
                        {t('worker.hourlyRate')}:
                      </span>
                      <p className="text-gray-900">${worker.hourly_rate}/hr</p>
                    </div>
                  )}
                </div>
              </div>

              {/* Additional Information */}
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                  {t('worker.additionalInfo')}
                </h3>
                
                <div className="space-y-3">
                  {worker.bio_en && (
                    <div>
                      <span className="text-sm font-medium text-gray-500">
                        {t('worker.bio')}:
                      </span>
                      <p className="text-gray-900">{worker.bio_en}</p>
                    </div>
                  )}
                  
                  {worker.skills && worker.skills.length > 0 && (
                    <div>
                      <span className="text-sm font-medium text-gray-500">
                        {t('worker.skills')}:
                      </span>
                      <div className="flex flex-wrap gap-2 mt-1">
                        {worker.skills.map((skill, index) => (
                          <span
                            key={index}
                            className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"
                          >
                            {skill}
                          </span>
                        ))}
                      </div>
                    </div>
                  )}
                  
                  {worker.availability && (
                    <div>
                      <span className="text-sm font-medium text-gray-500">
                        {t('worker.availability')}:
                      </span>
                      <p className="text-gray-900">{worker.availability}</p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

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

export default WorkerDetailPage;