import React from 'react';
import { useTranslation } from 'react-i18next';

const RequestsPage: React.FC = () => {
  const { t } = useTranslation();

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">
        {t('agency.requests')}
      </h1>
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <p className="text-gray-600">Agency requests page coming soon...</p>
      </div>
    </div>
  );
};

export default RequestsPage;