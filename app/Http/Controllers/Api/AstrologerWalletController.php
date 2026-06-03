<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AstrologerWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AstrologerWalletController extends Controller
{
    protected $walletService;

    public function __construct(AstrologerWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get wallet balance and key metrics for the authenticated astrologer.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $summary = $this->walletService->getWalletSummary($request->user());
            return response()->json([
                'status' => 'success',
                'data' => $summary
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch wallet summary.'
            ], 500);
        }
    }

    /**
     * Get earning history for the astrologer with optional filters (today, weekly, monthly).
     */
    public function earnings(Request $request): JsonResponse
    {
        try {
            $earnings = $this->walletService->getEarningsHistory($request->user(), $request->input('filter'));
            return response()->json([
                'status' => 'success',
                'data' => $earnings
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch earning history.'
            ], 500);
        }
    }

    /**
     * Get withdrawal history for the astrologer.
     */
    public function withdrawals(Request $request): JsonResponse
    {
        try {
            $withdrawals = $this->walletService->getWithdrawalHistory($request->user());
            return response()->json([
                'status' => 'success',
                'data' => $withdrawals
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch withdrawal history.'
            ], 500);
        }
    }

    /**
     * Request a withdrawal to a selected bank account.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'bank_account_id' => ['required', 'integer'],
        ]);

        try {
            $result = $this->walletService->requestWithdrawal(
                $request->user(),
                (float)$request->input('amount'),
                (int)$request->input('bank_account_id')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Withdrawal request submitted successfully.',
                'data' => $result
            ], 201);
        } catch (Exception $e) {
            $code = $e->getCode();
            // Handle expected validation errors
            if ($code === 422) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?? 'Failed to submit withdrawal request.'
            ], 500);
        }
    }
}
