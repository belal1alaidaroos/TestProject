<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\City;
use App\Models\District;
use App\Models\Profession;
use App\Models\Nationality;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LookupsController extends Controller
{
    /**
     * Get all countries
     */
    public function getCountries(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Country::query();

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name_en', 'like', '%' . $request->search . '%')
                      ->orWhere('name_ar', 'like', '%' . $request->search . '%');
                });
            }

            $perPage = $request->per_page ?? 10;
            $countries = $query->orderBy('name_en')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $countries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load countries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new country
     */
    public function createCountry(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255|unique:countries,name_en',
            'name_ar' => 'required|string|max:255|unique:countries,name_ar',
            'code' => 'required|string|max:3|unique:countries,code',
            'phone_code' => 'nullable|string|max:10',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $country = Country::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Country created successfully',
                'data' => $country
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create country',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a country
     */
    public function updateCountry(Request $request, string $countryId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'sometimes|required|string|max:255|unique:countries,name_en,' . $countryId,
            'name_ar' => 'sometimes|required|string|max:255|unique:countries,name_ar,' . $countryId,
            'code' => 'sometimes|required|string|max:3|unique:countries,code,' . $countryId,
            'phone_code' => 'nullable|string|max:10',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $country = Country::findOrFail($countryId);
            $country->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Country updated successfully',
                'data' => $country
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update country',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a country
     */
    public function deleteCountry(string $countryId): JsonResponse
    {
        try {
            $country = Country::findOrFail($countryId);
            
            // Check if country has cities
            if ($country->cities()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete country with associated cities'
                ], 400);
            }

            $country->delete();

            return response()->json([
                'success' => true,
                'message' => 'Country deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete country',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cities by country
     */
    public function getCities(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'nullable|exists:countries,id',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = City::with('country');

            if ($request->country_id) {
                $query->where('country_id', $request->country_id);
            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name_en', 'like', '%' . $request->search . '%')
                      ->orWhere('name_ar', 'like', '%' . $request->search . '%');
                });
            }

            $perPage = $request->per_page ?? 10;
            $cities = $query->orderBy('name_en')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load cities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new city
     */
    public function createCity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|exists:countries,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $city = City::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'City created successfully',
                'data' => $city->load('country')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create city',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a city
     */
    public function updateCity(Request $request, string $cityId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'sometimes|required|exists:countries,id',
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $city = City::findOrFail($cityId);
            $city->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'City updated successfully',
                'data' => $city->load('country')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update city',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a city
     */
    public function deleteCity(string $cityId): JsonResponse
    {
        try {
            $city = City::findOrFail($cityId);
            
            // Check if city has districts
            if ($city->districts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete city with associated districts'
                ], 400);
            }

            $city->delete();

            return response()->json([
                'success' => true,
                'message' => 'City deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete city',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get districts by city
     */
    public function getDistricts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'nullable|exists:cities,id',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = District::with(['city.country']);

            if ($request->city_id) {
                $query->where('city_id', $request->city_id);
            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name_en', 'like', '%' . $request->search . '%')
                      ->orWhere('name_ar', 'like', '%' . $request->search . '%');
                });
            }

            $perPage = $request->per_page ?? 10;
            $districts = $query->orderBy('name_en')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $districts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load districts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new district
     */
    public function createDistrict(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $district = District::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'District created successfully',
                'data' => $district->load(['city.country'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create district',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a district
     */
    public function updateDistrict(Request $request, string $districtId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'sometimes|required|exists:cities,id',
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $district = District::findOrFail($districtId);
            $district->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'District updated successfully',
                'data' => $district->load(['city.country'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update district',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a district
     */
    public function deleteDistrict(string $districtId): JsonResponse
    {
        try {
            $district = District::findOrFail($districtId);
            $district->delete();

            return response()->json([
                'success' => true,
                'message' => 'District deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete district',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get professions
     */
    public function getProfessions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Profession::query();

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name_en', 'like', '%' . $request->search . '%')
                      ->orWhere('name_ar', 'like', '%' . $request->search . '%');
                });
            }

            $perPage = $request->per_page ?? 10;
            $professions = $query->orderBy('name_en')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $professions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load professions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new profession
     */
    public function createProfession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255|unique:professions,name_en',
            'name_ar' => 'required|string|max:255|unique:professions,name_ar',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $profession = Profession::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Profession created successfully',
                'data' => $profession
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create profession',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a profession
     */
    public function updateProfession(Request $request, string $professionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'sometimes|required|string|max:255|unique:professions,name_en,' . $professionId,
            'name_ar' => 'sometimes|required|string|max:255|unique:professions,name_ar,' . $professionId,
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $profession = Profession::findOrFail($professionId);
            $profession->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Profession updated successfully',
                'data' => $profession
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profession',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a profession
     */
    public function deleteProfession(string $professionId): JsonResponse
    {
        try {
            $profession = Profession::findOrFail($professionId);
            $profession->delete();

            return response()->json([
                'success' => true,
                'message' => 'Profession deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete profession',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get nationalities
     */
    public function getNationalities(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Nationality::query();

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name_en', 'like', '%' . $request->search . '%')
                      ->orWhere('name_ar', 'like', '%' . $request->search . '%');
                });
            }

            $perPage = $request->per_page ?? 10;
            $nationalities = $query->orderBy('name_en')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $nationalities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load nationalities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new nationality
     */
    public function createNationality(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255|unique:nationalities,name_en',
            'name_ar' => 'required|string|max:255|unique:nationalities,name_ar',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $nationality = Nationality::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Nationality created successfully',
                'data' => $nationality
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create nationality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a nationality
     */
    public function updateNationality(Request $request, string $nationalityId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'sometimes|required|string|max:255|unique:nationalities,name_en,' . $nationalityId,
            'name_ar' => 'sometimes|required|string|max:255|unique:nationalities,name_ar,' . $nationalityId,
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $nationality = Nationality::findOrFail($nationalityId);
            $nationality->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Nationality updated successfully',
                'data' => $nationality
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update nationality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a nationality
     */
    public function deleteNationality(string $nationalityId): JsonResponse
    {
        try {
            $nationality = Nationality::findOrFail($nationalityId);
            $nationality->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nationality deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete nationality',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}