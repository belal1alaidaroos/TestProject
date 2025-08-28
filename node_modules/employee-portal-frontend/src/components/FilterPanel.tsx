import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useForm } from 'react-hook-form';

interface Filters {
  nationality_id?: string;
  profession_id?: string;
  min_age?: number;
  max_age?: number;
  experience_years?: number;
}

interface FilterPanelProps {
  filters: Filters;
  nationalities: any[];
  professions: any[];
  onFilterChange: (filters: Filters) => void;
}

const FilterPanel: React.FC<FilterPanelProps> = ({
  filters,
  nationalities,
  professions,
  onFilterChange,
}) => {
  const { t, i18n } = useTranslation();
  const [isExpanded, setIsExpanded] = useState(false);
  const currentLanguage = i18n.language;

  const {
    register,
    handleSubmit,
    reset,
    watch,
  } = useForm<Filters>({
    defaultValues: filters,
  });

  const watchedValues = watch();

  const handleFilterSubmit = (data: Filters) => {
    // Remove empty values
    const cleanFilters = Object.fromEntries(
      Object.entries(data).filter(([_, value]) => value !== '' && value !== undefined)
    );
    onFilterChange(cleanFilters);
  };

  const handleClearFilters = () => {
    reset();
    onFilterChange({});
  };

  const getDisplayName = (item: any) => {
    return currentLanguage === 'ar' ? item.name_ar : item.name_en;
  };

  const hasActiveFilters = Object.values(filters).some(value => value !== undefined && value !== '');

  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-medium text-gray-900">
          {t('common.filter')}
        </h3>
        <div className="flex items-center space-x-2">
          {hasActiveFilters && (
            <button
              onClick={handleClearFilters}
              className="text-sm text-gray-500 hover:text-gray-700"
            >
              {t('common.clear')}
            </button>
          )}
          <button
            onClick={() => setIsExpanded(!isExpanded)}
            className="text-sm text-primary-600 hover:text-primary-700"
          >
            {isExpanded ? t('common.hide') : t('common.show')}
          </button>
        </div>
      </div>

      {isExpanded && (
        <form onSubmit={handleSubmit(handleFilterSubmit)} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Nationality Filter */}
            <div>
              <label className="form-label">
                {t('customer.nationality')}
              </label>
              <select
                {...register('nationality_id')}
                className="input-field"
              >
                <option value="">{t('common.all')}</option>
                {nationalities.map((nationality) => (
                  <option key={nationality.id} value={nationality.id}>
                    {getDisplayName(nationality)}
                  </option>
                ))}
              </select>
            </div>

            {/* Profession Filter */}
            <div>
              <label className="form-label">
                {t('customer.profession')}
              </label>
              <select
                {...register('profession_id')}
                className="input-field"
              >
                <option value="">{t('common.all')}</option>
                {professions.map((profession) => (
                  <option key={profession.id} value={profession.id}>
                    {getDisplayName(profession)}
                  </option>
                ))}
              </select>
            </div>

            {/* Min Age Filter */}
            <div>
              <label className="form-label">
                {t('customer.min_age')}
              </label>
              <input
                type="number"
                min="18"
                max="65"
                {...register('min_age', { valueAsNumber: true })}
                className="input-field"
                placeholder="18"
              />
            </div>

            {/* Max Age Filter */}
            <div>
              <label className="form-label">
                {t('customer.max_age')}
              </label>
              <input
                type="number"
                min="18"
                max="65"
                {...register('max_age', { valueAsNumber: true })}
                className="input-field"
                placeholder="65"
              />
            </div>
          </div>

          {/* Experience Filter */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="form-label">
                {t('customer.min_experience')}
              </label>
              <input
                type="number"
                min="0"
                max="20"
                {...register('experience_years', { valueAsNumber: true })}
                className="input-field"
                placeholder="0"
              />
            </div>
          </div>

          {/* Filter Actions */}
          <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={handleClearFilters}
              className="btn-secondary"
            >
              {t('common.clear')}
            </button>
            <button
              type="submit"
              className="btn-primary"
            >
              {t('common.filter')}
            </button>
          </div>
        </form>
      )}

      {/* Active Filters Display */}
      {hasActiveFilters && (
        <div className="mt-4 pt-4 border-t border-gray-200">
          <div className="flex flex-wrap gap-2">
            {filters.nationality_id && (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {t('customer.nationality')}: {nationalities.find(n => n.id === filters.nationality_id)?.name_en}
              </span>
            )}
            {filters.profession_id && (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                {t('customer.profession')}: {professions.find(p => p.id === filters.profession_id)?.name_en}
              </span>
            )}
            {filters.min_age && (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                {t('customer.min_age')}: {filters.min_age}
              </span>
            )}
            {filters.max_age && (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                {t('customer.max_age')}: {filters.max_age}
              </span>
            )}
            {filters.experience_years && (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                {t('customer.experience')}: {filters.experience_years}+ {t('common.years')}
              </span>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default FilterPanel;