import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { CheckIcon, ChevronRightIcon } from '@heroicons/react/24/outline';
import { api } from '../services/api';

interface Package {
  id: string;
  nationality_id: string;
  profession_id: string;
  contract_duration_id: string;
  price: number;
  currency: string;
  description?: string;
  nationality: {
    id: string;
    name: string;
  };
  profession: {
    id: string;
    name: string;
  };
  contractDuration: {
    id: string;
    name: string;
    months: number;
  };
  formatted_price: string;
  display_name: string;
}

interface Worker {
  id: string;
  worker_number: string;
  name: string;
  date_of_birth: string;
  nationality_id: string;
  profession_id: string;
  experience_years: number;
  experience_summary?: string;
  photo_url?: string;
  age: number;
  nationality: {
    id: string;
    name: string;
  };
  profession: {
    id: string;
    name: string;
  };
}

interface CustomerAddress {
  id: string;
  address_name: string;
  full_address: string;
  city: {
    id: string;
    name: string;
  };
  district: {
    id: string;
    name: string;
  };
  latitude?: number;
  longitude?: number;
  is_default: boolean;
}

interface Discount {
  id: string;
  name: string;
  percentage: number;
  description?: string;
  conditions?: any;
}

interface ContractWizardProps {
  onComplete?: (contractId: string) => void;
}

const ContractWizard: React.FC<ContractWizardProps> = ({ onComplete }) => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  
  const [currentStep, setCurrentStep] = useState(1);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Step 1: Package Selection
  const [nationalities, setNationalities] = useState<any[]>([]);
  const [professions, setProfessions] = useState<any[]>([]);
  const [contractDurations, setContractDurations] = useState<any[]>([]);
  const [selectedNationality, setSelectedNationality] = useState<string>('');
  const [selectedProfession, setSelectedProfession] = useState<string>('');
  const [selectedDuration, setSelectedDuration] = useState<string>('');
  const [packages, setPackages] = useState<Package[]>([]);
  const [selectedPackage, setSelectedPackage] = useState<Package | null>(null);

  // Step 2: Worker Selection
  const [workers, setWorkers] = useState<Worker[]>([]);
  const [selectedWorker, setSelectedWorker] = useState<Worker | null>(null);

  // Step 3: Summary & Address
  const [addresses, setAddresses] = useState<CustomerAddress[]>([]);
  const [selectedAddress, setSelectedAddress] = useState<CustomerAddress | null>(null);
  const [showNewAddressForm, setShowNewAddressForm] = useState(false);
  const [newAddress, setNewAddress] = useState({
    address_name: '',
    city_id: '',
    district_id: '',
    full_address: '',
    latitude: '',
    longitude: '',
  });

  // Step 4: Payment
  const [discounts, setDiscounts] = useState<Discount[]>([]);
  const [selectedDiscount, setSelectedDiscount] = useState<Discount | null>(null);
  const [finalPrice, setFinalPrice] = useState(0);

  const steps = [
    { id: 1, name: t('contractWizard.step1'), description: t('contractWizard.step1Desc') },
    { id: 2, name: t('contractWizard.step2'), description: t('contractWizard.step2Desc') },
    { id: 3, name: t('contractWizard.step3'), description: t('contractWizard.step3Desc') },
    { id: 4, name: t('contractWizard.step4'), description: t('contractWizard.step4Desc') },
  ];

  useEffect(() => {
    loadInitialData();
  }, []);

  useEffect(() => {
    if (selectedNationality && selectedProfession && selectedDuration) {
      loadPackages();
    }
  }, [selectedNationality, selectedProfession, selectedDuration]);

  useEffect(() => {
    if (selectedPackage) {
      loadWorkers();
    }
  }, [selectedPackage]);

  useEffect(() => {
    if (selectedPackage) {
      calculateFinalPrice();
    }
  }, [selectedPackage, selectedDiscount]);

  const loadInitialData = async () => {
    try {
      setIsLoading(true);
      
      const [nationalitiesRes, professionsRes, durationsRes, addressesRes, discountsRes] = await Promise.all([
        api.get('/lookups/nationalities'),
        api.get('/lookups/professions'),
        api.get('/lookups/contract-durations'),
        api.get('/customer/addresses'),
        api.get('/customer/discounts'),
      ]);

      setNationalities(nationalitiesRes.data.data);
      setProfessions(professionsRes.data.data);
      setContractDurations(durationsRes.data.data);
      setAddresses(addressesRes.data.data);
      setDiscounts(discountsRes.data.data);

      // Set default address if available
      const defaultAddress = addressesRes.data.data.find((addr: CustomerAddress) => addr.is_default);
      if (defaultAddress) {
        setSelectedAddress(defaultAddress);
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('contractWizard.loadError'));
    } finally {
      setIsLoading(false);
    }
  };

  const loadPackages = async () => {
    try {
      const response = await api.get('/packages', {
        params: {
          nationality_id: selectedNationality,
          profession_id: selectedProfession,
          contract_duration_id: selectedDuration,
        },
      });

      setPackages(response.data.data);
    } catch (error: any) {
      setError(error.response?.data?.message || t('contractWizard.packagesError'));
    }
  };

  const loadWorkers = async () => {
    if (!selectedPackage) return;

    try {
      const response = await api.get('/workers', {
        params: {
          nationality_id: selectedPackage.nationality_id,
          profession_id: selectedPackage.profession_id,
          status: 'Ready',
        },
      });

      setWorkers(response.data.data);
    } catch (error: any) {
      setError(error.response?.data?.message || t('contractWizard.workersError'));
    }
  };

  const calculateFinalPrice = () => {
    if (!selectedPackage) return;

    let price = selectedPackage.price;
    
    if (selectedDiscount) {
      const discountAmount = (price * selectedDiscount.percentage) / 100;
      price -= discountAmount;
    }

    setFinalPrice(price);
  };

  const handleNext = () => {
    if (currentStep < 4) {
      setCurrentStep(currentStep + 1);
      setError(null);
    }
  };

  const handlePrevious = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
      setError(null);
    }
  };

  const handleCreateAddress = async () => {
    try {
      const response = await api.post('/customer/addresses', newAddress);
      
      if (response.data.success) {
        const newAddressData = response.data.data;
        setAddresses(prev => [...prev, newAddressData]);
        setSelectedAddress(newAddressData);
        setShowNewAddressForm(false);
        setNewAddress({
          address_name: '',
          city_id: '',
          district_id: '',
          full_address: '',
          latitude: '',
          longitude: '',
        });
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('contractWizard.addressError'));
    }
  };

  const handleCreateContract = async () => {
    if (!selectedPackage || !selectedWorker || !selectedAddress) {
      setError(t('contractWizard.missingData'));
      return;
    }

    try {
      setIsLoading(true);
      
      const contractData = {
        package_id: selectedPackage.id,
        worker_id: selectedWorker.id,
        delivery_address_id: selectedAddress.id,
        applied_discount_id: selectedDiscount?.id,
        original_amount: selectedPackage.price,
        discount_amount: selectedDiscount ? (selectedPackage.price * selectedDiscount.percentage) / 100 : 0,
        total_amount: finalPrice,
        currency: selectedPackage.currency,
      };

      const response = await api.post('/contracts', contractData);
      
      if (response.data.success) {
        const contractId = response.data.data.id;
        onComplete?.(contractId);
        navigate(`/contracts/${contractId}`);
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('contractWizard.contractError'));
    } finally {
      setIsLoading(false);
    }
  };

  const canProceedToNext = () => {
    switch (currentStep) {
      case 1:
        return selectedPackage !== null;
      case 2:
        return selectedWorker !== null;
      case 3:
        return selectedAddress !== null;
      case 4:
        return true;
      default:
        return false;
    }
  };

  const renderStep1 = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            {t('contractWizard.nationality')}
          </label>
          <select
            value={selectedNationality}
            onChange={(e) => setSelectedNationality(e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{t('contractWizard.selectNationality')}</option>
            {nationalities.map((nationality) => (
              <option key={nationality.id} value={nationality.id}>
                {nationality.name}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            {t('contractWizard.profession')}
          </label>
          <select
            value={selectedProfession}
            onChange={(e) => setSelectedProfession(e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{t('contractWizard.selectProfession')}</option>
            {professions.map((profession) => (
              <option key={profession.id} value={profession.id}>
                {profession.name}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            {t('contractWizard.duration')}
          </label>
          <select
            value={selectedDuration}
            onChange={(e) => setSelectedDuration(e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{t('contractWizard.selectDuration')}</option>
            {contractDurations.map((duration) => (
              <option key={duration.id} value={duration.id}>
                {duration.name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {packages.length > 0 && (
        <div>
          <h3 className="text-lg font-medium text-gray-900 mb-4">
            {t('contractWizard.availablePackages')}
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {packages.map((pkg) => (
              <div
                key={pkg.id}
                onClick={() => setSelectedPackage(pkg)}
                className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                  selectedPackage?.id === pkg.id
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-200 hover:border-gray-300'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <h4 className="font-medium text-gray-900">{pkg.display_name}</h4>
                  <span className="text-lg font-bold text-blue-600">{pkg.formatted_price}</span>
                </div>
                {pkg.description && (
                  <p className="text-sm text-gray-600">{pkg.description}</p>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );

  const renderStep2 = () => (
    <div className="space-y-6">
      <h3 className="text-lg font-medium text-gray-900">
        {t('contractWizard.selectWorker')}
      </h3>
      
      {workers.length === 0 ? (
        <div className="text-center py-8">
          <p className="text-gray-500">{t('contractWizard.noWorkersAvailable')}</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {workers.map((worker) => (
            <div
              key={worker.id}
              onClick={() => setSelectedWorker(worker)}
              className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                selectedWorker?.id === worker.id
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-200 hover:border-gray-300'
              }`}
            >
              <div className="flex items-center space-x-3">
                {worker.photo_url ? (
                  <img
                    src={worker.photo_url}
                    alt={worker.name}
                    className="w-16 h-16 rounded-full object-cover"
                  />
                ) : (
                  <div className="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
                    <span className="text-gray-500 text-lg">{worker.name.charAt(0)}</span>
                  </div>
                )}
                
                <div className="flex-1">
                  <h4 className="font-medium text-gray-900">{worker.name}</h4>
                  <p className="text-sm text-gray-600">
                    {worker.nationality.name} • {worker.profession.name}
                  </p>
                  <p className="text-sm text-gray-500">
                    {t('contractWizard.age')}: {worker.age} • {t('contractWizard.experience')}: {worker.experience_years} {t('contractWizard.years')}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );

  const renderStep3 = () => (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium text-gray-900 mb-4">
          {t('contractWizard.summary')}
        </h3>
        
        <div className="bg-gray-50 rounded-lg p-4 space-y-3">
          <div className="flex justify-between">
            <span className="text-gray-600">{t('contractWizard.selectedPackage')}:</span>
            <span className="font-medium">{selectedPackage?.display_name}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">{t('contractWizard.selectedWorker')}:</span>
            <span className="font-medium">{selectedWorker?.name}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">{t('contractWizard.packagePrice')}:</span>
            <span className="font-medium">{selectedPackage?.formatted_price}</span>
          </div>
        </div>
      </div>

      <div>
        <h3 className="text-lg font-medium text-gray-900 mb-4">
          {t('contractWizard.deliveryAddress')}
        </h3>
        
        <div className="space-y-4">
          {addresses.map((address) => (
            <div
              key={address.id}
              onClick={() => setSelectedAddress(address)}
              className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                selectedAddress?.id === address.id
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-200 hover:border-gray-300'
              }`}
            >
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium text-gray-900">{address.address_name}</h4>
                  <p className="text-sm text-gray-600">{address.full_address}</p>
                  <p className="text-sm text-gray-500">
                    {address.district.name}, {address.city.name}
                  </p>
                </div>
                {address.is_default && (
                  <span className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                    {t('contractWizard.default')}
                  </span>
                )}
              </div>
            </div>
          ))}
          
          <button
            type="button"
            onClick={() => setShowNewAddressForm(true)}
            className="w-full border-2 border-dashed border-gray-300 rounded-lg p-4 text-center text-gray-600 hover:border-gray-400 hover:text-gray-800 transition-colors"
          >
            {t('contractWizard.addNewAddress')}
          </button>
        </div>

        {showNewAddressForm && (
          <div className="mt-4 p-4 border border-gray-200 rounded-lg">
            <h4 className="font-medium text-gray-900 mb-4">{t('contractWizard.newAddress')}</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <input
                type="text"
                placeholder={t('contractWizard.addressName')}
                value={newAddress.address_name}
                onChange={(e) => setNewAddress(prev => ({ ...prev, address_name: e.target.value }))}
                className="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <input
                type="text"
                placeholder={t('contractWizard.fullAddress')}
                value={newAddress.full_address}
                onChange={(e) => setNewAddress(prev => ({ ...prev, full_address: e.target.value }))}
                className="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div className="mt-4 flex space-x-2">
              <button
                type="button"
                onClick={handleCreateAddress}
                className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
              >
                {t('contractWizard.saveAddress')}
              </button>
              <button
                type="button"
                onClick={() => setShowNewAddressForm(false)}
                className="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400"
              >
                {t('contractWizard.cancel')}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );

  const renderStep4 = () => (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium text-gray-900 mb-4">
          {t('contractWizard.payment')}
        </h3>
        
        <div className="bg-gray-50 rounded-lg p-4 space-y-3">
          <div className="flex justify-between">
            <span className="text-gray-600">{t('contractWizard.originalPrice')}:</span>
            <span className="font-medium">{selectedPackage?.formatted_price}</span>
          </div>
          
          {discounts.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                {t('contractWizard.applyDiscount')}
              </label>
              <select
                value={selectedDiscount?.id || ''}
                onChange={(e) => {
                  const discount = discounts.find(d => d.id === e.target.value);
                  setSelectedDiscount(discount || null);
                }}
                className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">{t('contractWizard.noDiscount')}</option>
                {discounts.map((discount) => (
                  <option key={discount.id} value={discount.id}>
                    {discount.name} ({discount.percentage}%)
                  </option>
                ))}
              </select>
            </div>
          )}
          
          <div className="border-t pt-3">
            <div className="flex justify-between">
              <span className="text-lg font-medium text-gray-900">{t('contractWizard.finalPrice')}:</span>
              <span className="text-lg font-bold text-blue-600">
                {selectedPackage?.currency === 'SAR' ? 'ر.س' : '$'} {finalPrice.toFixed(2)}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p className="text-sm text-yellow-800">
          {t('contractWizard.paymentNote')}
        </p>
      </div>
    </div>
  );

  const renderCurrentStep = () => {
    switch (currentStep) {
      case 1:
        return renderStep1();
      case 2:
        return renderStep2();
      case 3:
        return renderStep3();
      case 4:
        return renderStep4();
      default:
        return null;
    }
  };

  return (
    <div className="max-w-4xl mx-auto">
      {/* Progress Bar */}
      <div className="mb-8">
        <nav aria-label="Progress">
          <ol className="flex items-center">
            {steps.map((step, stepIdx) => (
              <li key={step.name} className={`relative ${stepIdx !== steps.length - 1 ? 'pr-8 sm:pr-20' : ''} flex-1`}>
                <div className="absolute inset-0 flex items-center" aria-hidden="true">
                  <div className={`h-0.5 w-full ${
                    step.id < currentStep ? 'bg-blue-600' : 'bg-gray-200'
                  }`} />
                </div>
                <div className={`relative flex h-8 w-8 items-center justify-center rounded-full ${
                  step.id <= currentStep ? 'bg-blue-600' : 'bg-gray-200'
                }`}>
                  {step.id < currentStep ? (
                    <CheckIcon className="h-5 w-5 text-white" aria-hidden="true" />
                  ) : (
                    <span className={`text-sm font-medium ${
                      step.id === currentStep ? 'text-white' : 'text-gray-500'
                    }`}>
                      {step.id}
                    </span>
                  )}
                </div>
              </li>
            ))}
          </ol>
        </nav>
        
        <div className="mt-4">
          <h2 className="text-lg font-medium text-gray-900">
            {steps[currentStep - 1].name}
          </h2>
          <p className="text-sm text-gray-500">
            {steps[currentStep - 1].description}
          </p>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
          <p className="text-sm text-red-600">{error}</p>
        </div>
      )}

      {/* Step Content */}
      <div className="bg-white rounded-lg shadow p-6">
        {renderCurrentStep()}
      </div>

      {/* Navigation Buttons */}
      <div className="mt-8 flex justify-between">
        <button
          type="button"
          onClick={handlePrevious}
          disabled={currentStep === 1}
          className="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {t('contractWizard.previous')}
        </button>

        {currentStep < 4 ? (
          <button
            type="button"
            onClick={handleNext}
            disabled={!canProceedToNext()}
            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
          >
            <span>{t('contractWizard.next')}</span>
            <ChevronRightIcon className="w-4 h-4" />
          </button>
        ) : (
          <button
            type="button"
            onClick={handleCreateContract}
            disabled={!canProceedToNext() || isLoading}
            className="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? t('contractWizard.creating') : t('contractWizard.createContract')}
          </button>
        )}
      </div>
    </div>
  );
};

export default ContractWizard;