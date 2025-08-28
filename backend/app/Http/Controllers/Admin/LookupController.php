<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Country;
use App\Models\City;
use App\Models\District;
use App\Models\Nationality;
use App\Models\Profession;
use App\Models\Package;
use Illuminate\Support\Facades\Validator;

class LookupController extends Controller
{
    /**
     * Get all countries
     */
    public function countries(): JsonResponse
    {
        try {
            $countries = Country::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Countries retrieved successfully',
                'data' => $countries
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving countries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new country
     */
    public function storeCountry(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:countries,name',
                'code' => 'required|string|max:3|unique:countries,code',
                'phone_code' => 'required|string|max:10',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $country = Country::create($request->all());

            return response()->json([
                'status' => 201,
                'message' => 'Country created successfully',
                'data' => $country
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating country: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all cities
     */
    public function cities(): JsonResponse
    {
        try {
            $cities = City::with('country')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Cities retrieved successfully',
                'data' => $cities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving cities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new city
     */
    public function storeCity(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'country_id' => 'required|exists:countries,id',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate city name in the same country
            $existingCity = City::where('name', $request->name)
                ->where('country_id', $request->country_id)
                ->first();

            if ($existingCity) {
                return response()->json([
                    'status' => 422,
                    'message' => 'City already exists in this country'
                ], 422);
            }

            $city = City::create($request->all());
            $city->load('country');

            return response()->json([
                'status' => 201,
                'message' => 'City created successfully',
                'data' => $city
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating city: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all districts
     */
    public function districts(): JsonResponse
    {
        try {
            $districts = District::with(['city', 'city.country'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Districts retrieved successfully',
                'data' => $districts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving districts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new district
     */
    public function storeDistrict(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'city_id' => 'required|exists:cities,id',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate district name in the same city
            $existingDistrict = District::where('name', $request->name)
                ->where('city_id', $request->city_id)
                ->first();

            if ($existingDistrict) {
                return response()->json([
                    'status' => 422,
                    'message' => 'District already exists in this city'
                ], 422);
            }

            $district = District::create($request->all());
            $district->load(['city', 'city.country']);

            return response()->json([
                'status' => 201,
                'message' => 'District created successfully',
                'data' => $district
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating district: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all nationalities
     */
    public function nationalities(): JsonResponse
    {
        try {
            $nationalities = Nationality::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Nationalities retrieved successfully',
                'data' => $nationalities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving nationalities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new nationality
     */
    public function storeNationality(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:nationalities,name',
                'code' => 'required|string|max:3|unique:nationalities,code',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $nationality = Nationality::create($request->all());

            return response()->json([
                'status' => 201,
                'message' => 'Nationality created successfully',
                'data' => $nationality
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating nationality: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all professions
     */
    public function professions(): JsonResponse
    {
        try {
            $professions = Profession::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Professions retrieved successfully',
                'data' => $professions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving professions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new profession
     */
    public function storeProfession(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:professions,name',
                'description' => 'nullable|string|max:1000',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $profession = Profession::create($request->all());

            return response()->json([
                'status' => 201,
                'message' => 'Profession created successfully',
                'data' => $profession
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating profession: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all packages
     */
    public function packages(): JsonResponse
    {
        try {
            $packages = Package::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Packages retrieved successfully',
                'data' => $packages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving packages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new package
     */
    public function storePackage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:packages,name',
                'description' => 'nullable|string|max:1000',
                'price' => 'required|numeric|min:0.01',
                'duration_days' => 'required|integer|min:1',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $package = Package::create($request->all());

            return response()->json([
                'status' => 201,
                'message' => 'Package created successfully',
                'data' => $package
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating package: ' . $e->getMessage()
            ], 500);
        }
    }
}