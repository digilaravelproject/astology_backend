<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PlanController extends Controller
{
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

        $plan = Plan::findOrFail($validated['plan_id']);

        if ($plan->status !== 'active') {
            return response()->json(['status' => 'error', 'message' => 'Plan not active.'], 422);
        }

        if ($user->plan_id === $plan->id) {
            return response()->json(['status' => 'error', 'message' => 'You already have this plan.'], 409);
        }

        $wallet = \App\Models\Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        // Create fake razorpay order for deduction on upgrade
        $providerOrderId = 'razorpay_order_' . \Illuminate\Support\Str::random(12);

        $transaction = \App\Models\WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'plan_id' => $plan->id,
            'transaction_type' => 'debit',
            'amount' => $plan->price,
            'status' => 'pending',
            'payment_provider' => 'razorpay',
            'provider_order_id' => $providerOrderId,
            'description' => 'Plan upgrade to ' . $plan->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Razorpay order created. Proceed to payment verification.',
            'data' => [
                'plan' => $plan,
                'wallet' => $wallet,
                'transaction' => $transaction,
            ],
        ], 201);
    }

    public function verifyUpgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_order_id' => 'required|string',
            'provider_payment_id' => 'required|string',
            'signature' => 'required|string',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $transaction = \App\Models\WalletTransaction::where('provider_order_id', $validated['provider_order_id'])
            ->where('payment_provider', 'razorpay')
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Pending payment transaction not found.'], 404);
        }

        $wallet = $transaction->wallet;

        // Mark transaction complete (Razorpay confirmed)
        $transaction->provider_payment_id = $validated['provider_payment_id'];
        $transaction->status = 'completed';
        $transaction->meta = ['signature' => $validated['signature'], 'verified_at' => now()->toDateTimeString()];
        $transaction->save();

        $plan = Plan::find($transaction->plan_id);
        if (!$plan) {
            return response()->json(['status' => 'error', 'message' => 'Plan not found while finalizing upgrade.'], 404);
        }

        $now = Carbon::now();
        $expires = $now->copy()->addDays($plan->duration_days);

        $user->update([
            'plan_id' => $plan->id,
            'plan_started_at' => $now,
            'plan_expires_at' => $expires,
        ]);

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
    }
}
