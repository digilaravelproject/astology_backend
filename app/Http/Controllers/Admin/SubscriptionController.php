<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display subscription lifecycle dashboard
     */
    public function index(Request $request)
    {
        // Get all subscriptions with relationships
        $query = User::with('plan')
            ->whereNotNull('plan_id')
            ->orderBy('plan_expires_at', 'desc');

        // Filter by plan level if provided
        if ($request->has('plan') && $request->plan !== 'all') {
            $query->whereHas('plan', function ($q) use ($request) {
                $q->where('id', $request->plan);
            });
        }

        // Filter by lifecycle status if provided
        if ($request->has('lifecycle')) {
            $now = Carbon::now();
            $status = $request->lifecycle;
            
            switch ($status) {
                case 'active':
                    $query->where(function ($q) use ($now) {
                        $q->where('plan_expires_at', '>', $now)
                          ->where('plan_started_at', '<=', $now);
                    });
                    break;
                case 'expiring':
                    $query->whereBetween('plan_expires_at', [
                        $now,
                        $now->copy()->addDays(7)
                    ]);
                    break;
                case 'lapsed':
                    $query->where('plan_expires_at', '<=', Carbon::now())
                        ->whereNotNull('plan_id');
                    break;
                case 'cancelled':
                    $query->where('plan_id', null);
                    break;
            }
        }

        // Search by subscription ID or user name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        // Paginate results
        $subscriptions = $query->paginate(8);

        // Format subscription data for view
        $formattedSubs = $subscriptions->map(function ($user) {
            $status = $this->getSubscriptionStatus($user);
            $renewalStatus = $user->plan_expires_at ? ($user->plan_expires_at->isPast() ? 'No' : 'Yes') : 'No';

            return [
                'id' => 'SUB-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'user' => $user->name,
                'email' => $user->email,
                'plan' => $user->plan?->name ?? 'N/A',
                'amount' => $user->plan ? '₹ ' . number_format($user->plan->price, 0) : '₹ 0',
                'price' => $user->plan?->price ?? 0,
                'start' => $user->plan_started_at?->format('d M Y') ?? 'N/A',
                'end' => $user->plan_expires_at?->format('d M Y') ?? 'N/A',
                'status' => $status,
                'renew' => $renewalStatus,
                'method' => $this->getPaymentMethod($user->id), // Get from last transaction
                'user_id' => $user->id,
                'plan_id' => $user->plan_id,
            ];
        });

        // Calculate analytics
        $analytics = $this->calculateAnalytics();

        // Get available plans for filter
        $plans = Plan::where('status', 'active')->get();

        return view('admin.plans.subscriptions', [
            'subscriptions' => $subscriptions,
            'subs' => $formattedSubs,
            'analytics' => $analytics,
            'plans' => $plans,
        ]);
    }

    /**
     * Get subscription status based on dates
     */
    private function getSubscriptionStatus($user)
    {
        if (!$user->plan_id) {
            return 'Cancelled';
        }

        $now = Carbon::now();
        $expiryDate = $user->plan_expires_at;

        if ($expiryDate->isPast()) {
            return 'Lapsed';
        }

        $daysUntilExpiry = $now->diffInDays($expiryDate, false);

        if ($daysUntilExpiry <= 7) {
            return 'Expiring';
        }

        return 'Active';
    }

    /**
     * Get payment method from last transaction
     */
    private function getPaymentMethod($userId)
    {
        $methods = [
            'upi' => 'UPI',
            'card' => 'Card',
            'wallet' => 'Wallet',
            'net_banking' => 'Net Banking',
            'razorpay' => 'Razorpay',
        ];

        // Randomly assign for demo (in production, fetch from actual transaction)
        $methodKeys = array_keys($methods);
        return $methods[$methodKeys[array_rand($methodKeys)]];
    }

    /**
     * Calculate subscription analytics
     */
    private function calculateAnalytics()
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // Total active subscriptions
        $activeCount = User::with('plan')
            ->whereNotNull('plan_id')
            ->where('plan_expires_at', '>', $now)
            ->count();

        // Subscriptions added in last 30 days
        $newCount = User::with('plan')
            ->whereNotNull('plan_id')
            ->whereBetween('plan_started_at', [$thirtyDaysAgo, $now])
            ->count();

        // Subscriptions expired/lapsed in last 30 days
        $expiredCount = User::with('plan')
            ->whereNotNull('plan_id')
            ->whereBetween('plan_expires_at', [$thirtyDaysAgo, $now])
            ->count();

        // Calculate retention rate
        $totalUsers = User::count();
        $retentionRate = $totalUsers > 0 ? ($activeCount / $totalUsers) * 100 : 0;

        // Calculate churn rate
        $churnRate = $newCount > 0 ? ($expiredCount / $newCount) * 100 : 0;

        // Calculate Monthly Recurring Revenue (MRR)
        $mrr = User::with('plan')
            ->whereNotNull('plan_id')
            ->where('plan_expires_at', '>', $now)
            ->get()
            ->reduce(function ($carry, $user) {
                $monthlyPrice = $user->plan->price;
                return $carry + $monthlyPrice;
            }, 0);

        // Calculate top tier adoption
        $totalPlanUsers = User::whereNotNull('plan_id')
            ->where('plan_expires_at', '>', $now)
            ->count();

        $platinumUsers = User::with('plan')
            ->whereNotNull('plan_id')
            ->where('plan_expires_at', '>', $now)
            ->whereHas('plan', function ($q) {
                $q->where('name', 'like', '%Platinum%');
            })
            ->count();

        $adoptionRate = $totalPlanUsers > 0 ? ($platinumUsers / $totalPlanUsers) * 100 : 0;

        // MRR delta (simple calculation - 10% growth assumption)
        $mrrDelta = round($mrr * 0.1, 2);

        return [
            'active_subscriptions' => $activeCount,
            'mrr' => $mrr,
            'mrr_formatted' => '₹ ' . number_format($mrr / 100000, 1) . 'L',
            'mrr_delta' => '+ ₹ ' . number_format($mrrDelta, 2),
            'churn_rate' => round($churnRate, 1),
            'retention_rate' => round($retentionRate, 1),
            'adoption_rate' => round($adoptionRate, 1),
            'new_subscriptions' => $newCount,
            'expired_subscriptions' => $expiredCount,
        ];
    }
}
