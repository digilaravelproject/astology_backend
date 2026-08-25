<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKundliRequest;
use App\Http\Requests\UpdateKundliRequest;
use App\Services\KundliService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KundliController extends Controller
{
    protected KundliService $kundliService;

    public function __construct(KundliService $kundliService)
    {
        $this->kundliService = $kundliService;
    }

    /**
     * Create a new Kundli
     */
    public function store(StoreKundliRequest $request)
    {
        try {
            $kundli = $this->kundliService->createKundli($request->validated(), Auth::id());

            return ApiResponse::success($kundli, 'Kundli created successfully', 201);
        } catch (\Throwable $e) {
            Log::error('Kundli store failed: ' . $e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all Kundlis for authenticated user
     */
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $kundlis = $this->kundliService->getUserKundlis(Auth::id(), $perPage);

            return ApiResponse::success($kundlis, 'Kundlis retrieved successfully');
        } catch (\Throwable $e) {
            Log::error('Kundli index failed: ' . $e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific Kundli by ID (only if owned by user)
     */
    public function show($id)
    {
        try {
            $kundli = $this->kundliService->findUserKundli((int) $id, Auth::id());

            if (! $kundli) {
                return ApiResponse::error('Kundli not found', 404);
            }

            return ApiResponse::success($kundli, 'Kundli retrieved successfully');
        } catch (\Throwable $e) {
            Log::error('Kundli show failed: ' . $e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Update a Kundli (only if owned by user)
     */
    public function update(UpdateKundliRequest $request, $id)
    {
        try {
            $kundli = $this->kundliService->findUserKundli((int) $id, Auth::id());

            if (! $kundli) {
                return ApiResponse::error('Kundli not found', 404);
            }

            $updated = $this->kundliService->updateKundli($kundli, $request->validated());

            return ApiResponse::success($updated, 'Kundli updated successfully');
        } catch (\Throwable $e) {
            Log::error('Kundli update failed: ' . $e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a Kundli (Both User and Astrologer with strict consultation-bound check)
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $kundliId = (int) $id;

            if ($user && ($user->user_type === 'astrologer' || $user->relationLoaded('astrologer') ? (bool) $user->astrologer : false)) {
                $result = $this->kundliService->deleteByAstrologer($kundliId, $user->id);
            } else {
                $result = $this->kundliService->deleteByUser($kundliId, Auth::id());
            }

            if (! $result['success']) {
                return ApiResponse::error($result['message'], $result['status_code']);
            }

            return ApiResponse::success(null, $result['message']);
        } catch (\Throwable $e) {
            Log::error('Kundli delete failed: ' . $e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
