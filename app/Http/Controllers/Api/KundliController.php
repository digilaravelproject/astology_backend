<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Kundli;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class KundliController extends Controller
{
    /**
     * Create a new Kundli
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'gender' => 'required|in:male,female,other',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'birth_place' => 'nullable|string|max:500',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'datetime' => 'required|date_format:Y-m-d H:i:s',
            ]);

            // Associate with authenticated user
            $validated['user_id'] = Auth::id();

            $kundli = Kundli::create($validated);

            return ApiResponse::success($kundli, 'Kundli created successfully', 201);
        } catch (ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all Kundlis for authenticated user
     */
    public function index(Request $request)
    {
        try {
            $per_page = $request->get('per_page', 15);
            $kundlis = Kundli::where('user_id', Auth::id())
                ->paginate($per_page);

            return ApiResponse::success($kundlis, 'Kundlis retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific Kundli by ID (only if owned by user)
     */
    public function show($id)
    {
        try {
            $kundli = Kundli::where('user_id', Auth::id())->find($id);

            if (! $kundli) {
                return ApiResponse::error('Kundli not found', 404);
            }

            return ApiResponse::success($kundli, 'Kundli retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Update a Kundli (only if owned by user)
     */
    public function update(Request $request, $id)
    {
        try {
            $kundli = Kundli::where('user_id', Auth::id())->find($id);

            if (! $kundli) {
                return ApiResponse::error('Kundli not found', 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'gender' => 'sometimes|in:male,female,other',
                'birth_date' => 'sometimes|date',
                'birth_time' => 'sometimes|date_format:H:i:s',
                'birth_place' => 'sometimes|nullable|string|max:500',
                'latitude' => 'sometimes|numeric|between:-90,90',
                'longitude' => 'sometimes|numeric|between:-180,180',
                'datetime' => 'sometimes|date_format:Y-m-d H:i:s',
            ]);

            $kundli->update($validated);

            return ApiResponse::success($kundli, 'Kundli updated successfully');
        } catch (ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a Kundli (only if owned by user)
     */
    public function destroy($id)
    {
        try {
            $kundli = Kundli::where('user_id', Auth::id())->find($id);

            if (! $kundli) {
                return ApiResponse::error('Kundli not found', 404);
            }

            $kundli->delete();

            return ApiResponse::success(null, 'Kundli deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
