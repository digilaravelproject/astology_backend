<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\RazorpayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PlanController extends Controller
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }
    public function index(Request $request): JsonResponse
    {
        $plans = Plan::where('status', 'active')->orderBy('price')->get();

        $activePlan = null;
        $user = $request->user();
        
        if ($user) {
            // Refresh user to ensure fresh data from DB
            $user->refresh();
            
            // Load plan relationship with all data
            if ($user->plan_id) {
                $activePlan = $user->load('plan')->plan;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'plans' => $plans,
                'active_plan' => $activePlan,
            ],
        ]);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $plan]);
    }

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->plan_id) {
            return response()->json(['status' => 'error', 'message' => 'No active plan.'], 404);
        }

        // Refresh and load plan relationship
        $user->refresh()->load('plan');

        return response()->json(['status' => 'success', 'data' => [
            'plan' => $user->plan,
            'started_at' => $user->plan_started_at,
            'expires_at' => $user->plan_expires_at,
        ]]);
    }

    public function upgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        try {
            DB::beginTransaction();

            $plan = Plan::findOrFail($validated['plan_id']);

            if ($plan->status !== 'active') {
                return response()->json(['status' => 'error', 'message' => 'Plan not active.'], 422);
            }

            if ($user->plan_id === $plan->id) {
                return response()->json(['status' => 'error', 'message' => 'You already have this plan.'], 409);
            }

            $wallet = \App\Models\Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            // Create Razorpay order
            $amountInPaise = (int)($plan->price * 100); // Convert to paise
            $razorpayResult = $this->razorpayService->createOrder(
                $amountInPaise,
                'INR',
                'plan_' . $user->id . '_' . $plan->id . '_' . time(),
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                ]
            );

            if ($razorpayResult['status'] !== 'success') {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $razorpayResult['message'] ?? 'Failed to create Razorpay order.',
                ], 422);
            }

            $razorpayOrder = $razorpayResult['data'];

            // Create transaction record in database
            $transaction = \App\Models\WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'plan_id' => $plan->id,
                'transaction_type' => 'debit',
                'amount' => $plan->price,
                'status' => 'pending',
                'payment_provider' => 'razorpay',
                'provider_order_id' => $razorpayOrder['id'],
                'description' => 'Plan upgrade to ' . $plan->name,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Razorpay order created. Proceed to payment.',
                'data' => [
                    'plan' => $plan,
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                    'razorpay_order' => [
                        'id' => $razorpayOrder['id'],
                        'amount' => $razorpayOrder['amount'],
                        'currency' => $razorpayOrder['currency'],
                        'key_id' => config('razorpay.key_id'),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Plan upgrade error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to initiate plan upgrade.'], 500);
        }
    }

    public function verifyUpgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        try {
            DB::beginTransaction();

            // Verify Razorpay signature
            $isSignatureValid = $this->razorpayService->verifySignature(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature']
            );

            if (!$isSignatureValid) {
                return response()->json(['status' => 'error', 'message' => 'Payment signature verification failed.'], 422);
            }

            // Find and lock the transaction
            $transaction = \App\Models\WalletTransaction::where('provider_order_id', $validated['razorpay_order_id'])
                ->where('payment_provider', 'razorpay')
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Pending payment transaction not found or already processed.'], 404);
            }

            $wallet = $transaction->wallet;

            // Update transaction as completed
            $transaction->provider_payment_id = $validated['razorpay_payment_id'];
            $transaction->status = 'completed';
            $transaction->meta = [
                'signature' => $validated['razorpay_signature'],
                'verified_at' => now()->toDateTimeString(),
            ];
            $transaction->save();

            $plan = Plan::find($transaction->plan_id);
            if (!$plan) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Plan not found while finalizing upgrade.'], 404);
            }

            $now = Carbon::now();
            $expires = $now->copy()->addDays($plan->duration_days);

            // Update user within transaction
            $user->update([
                'plan_id' => $plan->id,
                'plan_started_at' => $now,
                'plan_expires_at' => $expires,
            ]);

            DB::commit();

            // Refresh user to get updated plan relationship
            $user->refresh()->load('plan');

            return response()->json([
                'status' => 'success',
                'message' => 'Plan upgraded successfully.',
                'data' => [
                    'active_plan' => $user->plan,
                    'plan_started_at' => $now,
                    'plan_expires_at' => $expires,
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Verify plan upgrade error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to finalize plan upgrade.'], 500);
        }
    }
}
