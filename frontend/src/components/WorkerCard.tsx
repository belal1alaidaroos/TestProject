import React from 'react';
import { useTranslation } from 'react-i18next';

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

interface WorkerCardProps {
  worker: Worker;
  onReserve: () => void;
}

const WorkerCard: React.FC<WorkerCardProps> = ({ worker, onReserve }) => {
  const { t, i18n } = useTranslation();
  const currentLanguage = i18n.language;

  const getAge = (dateOfBirth: string) => {
    const today = new Date();
    const birthDate = new Date(dateOfBirth);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    
    return age;
  };

  const getDisplayName = () => {
    return currentLanguage === 'ar' ? worker.name_ar : worker.name_en;
  };

  const getNationalityName = () => {
    return currentLanguage === 'ar' ? worker.nationality.name_ar : worker.nationality.name_en;
  };

  const getProfessionName = () => {
    return currentLanguage === 'ar' ? worker.profession.name_ar : worker.profession.name_en;
  };

  const getAgencyName = () => {
    return currentLanguage === 'ar' ? worker.agency.name_ar : worker.agency.name_en;
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Ready':
        return 'bg-green-100 text-green-800';
      case 'ReservedAwaitingContract':
        return 'bg-yellow-100 text-yellow-800';
      case 'ReservedAwaitingPayment':
        return 'bg-orange-100 text-orange-800';
      case 'AssignedToContract':
        return 'bg-blue-100 text-blue-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    return t(`status.${status.toLowerCase()}`);
  };

  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200">
      {/* Worker Photo */}
      <div className="aspect-w-3 aspect-h-4 bg-gray-200">
        {worker.photo_file_id ? (
          <img
            src={`/api/files/${worker.photo_file_id}`}
            alt={getDisplayName()}
            className="w-full h-48 object-cover"
          />
        ) : (
          <div className="w-full h-48 bg-gray-200 flex items-center justify-center">
            <svg className="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
            </svg>
          </div>
        )}
      </div>

      {/* Worker Info */}
      <div className="p-4 space-y-3">
        {/* Name and Status */}
        <div className="flex justify-between items-start">
          <h3 className="text-lg font-semibold text-gray-900 truncate">
            {getDisplayName()}
          </h3>
          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(worker.status)}`}>
            {getStatusText(worker.status)}
          </span>
        </div>

        {/* Age and Experience */}
        <div className="flex justify-between text-sm text-gray-600">
          <span>{t('customer.age')}: {getAge(worker.date_of_birth)}</span>
          <span>{t('customer.experience')}: {worker.experience_years} {t('common.years')}</span>
        </div>

        {/* Nationality */}
        <div className="text-sm text-gray-600">
          <span className="font-medium">{t('customer.nationality')}:</span> {getNationalityName()}
        </div>

        {/* Profession */}
        <div className="text-sm text-gray-600">
          <span className="font-medium">{t('customer.profession')}:</span> {getProfessionName()}
        </div>

        {/* Agency */}
        <div className="text-sm text-gray-600">
          <span className="font-medium">{t('customer.agency')}:</span> {getAgencyName()}
        </div>

        {/* Reserve Button */}
        <button
          onClick={onReserve}
          disabled={worker.status !== 'Ready'}
          className={`w-full mt-3 px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${
            worker.status === 'Ready'
              ? 'bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          }`}
        >
          {worker.status === 'Ready' ? t('customer.reserve') : t('customer.reserved')}
        </button>
      </div>
    </div>
  );
};

export default WorkerCard;