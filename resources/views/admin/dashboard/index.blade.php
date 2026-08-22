@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <!-- Page Header & Period Filter Toolbar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-lighter pb-5 sm:pb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">
                    Live Platform Analytics
                </span>
                <span class="text-text-muted text-[11px] sm:text-xs">• Real-Time DB Feed</span>
            </div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-text-primary tracking-tight">
                Dashboard Overview
            </h1>
            <p class="text-xs sm:text-sm text-text-secondary mt-1">
                Showing statistics for <strong class="text-text-primary">{{ $periodLabel }}</strong>.
            </p>
        </div>

        <!-- Date Range Filter Pills (Touch-scrollable on mobile) -->
        <div class="flex items-center gap-1.5 sm:gap-2 bg-light/60 p-1.5 rounded-2xl border border-gray-200 shadow-xs overflow-x-auto max-w-full no-scrollbar">
            @php
                $filters = [
                    'today'      => 'Today',
                    'yesterday'  => 'Yesterday',
                    'this_week'  => 'This Week',
                    'this_month' => 'This Month',
                    'this_year'  => 'This Year',
                    'all'        => 'All Time',
                ];
            @endphp
            @foreach($filters as $key => $label)
                <a href="{{ route('admin.dashboard', ['period' => $key]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all duration-150 {{ $period === $key ? 'bg-primary text-white shadow-xs' : 'text-text-secondary hover:text-text-primary hover:bg-white' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Row 1: Core Performance Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Total Users Card -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-xs hover:border-primary/40 transition-all group">
            <div class="flex justify-between items-start mb-3 sm:mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary text-lg sm:text-xl group-hover:scale-110 transition-transform shrink-0">
                    <i class="fas fa-users"></i>
                </div>
                @if($period !== 'all')
                    <span class="text-[11px] sm:text-xs font-bold px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full flex items-center gap-1 {{ $userGrowthPct['is_positive'] ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-rose-700 bg-rose-50 border border-rose-200' }}">
                        <i class="fas {{ $userGrowthPct['is_positive'] ? 'fa-arrow-up' : 'fa-arrow-down' }} text-[10px]"></i>
                        {{ $userGrowthPct['formatted'] }}
                    </span>
                @else
                    <span class="text-[11px] sm:text-xs font-bold text-primary bg-primary/10 px-2.5 py-0.5 rounded-full border border-primary/20">All Time</span>
                @endif
            </div>
            <div class="text-text-muted text-[11px] sm:text-xs font-bold uppercase tracking-wider mb-1">Total Users</div>
            <div class="text-2xl sm:text-3xl font-extrabold text-text-primary tracking-tight">{{ number_format($totalUsers) }}</div>
            <div class="text-[11px] text-text-muted mt-2 truncate">
                <strong class="text-text-primary">+{{ number_format($currPeriodUsers) }}</strong> new in {{ strtolower($periodLabel) }}
            </div>
        </div>

        <!-- Total Astrologers Card -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-xs hover:border-secondary/40 transition-all group">
            <div class="flex justify-between items-start mb-3 sm:mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-secondary/10 rounded-2xl flex items-center justify-center text-secondary text-lg sm:text-xl group-hover:scale-110 transition-transform shrink-0">
                    <i class="fas fa-user-astronaut"></i>
                </div>
                <span class="text-[11px] sm:text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ $onlineAstrologers }} Online
                </span>
            </div>
            <div class="text-text-muted text-[11px] sm:text-xs font-bold uppercase tracking-wider mb-1">Astrologers</div>
            <div class="text-2xl sm:text-3xl font-extrabold text-text-primary tracking-tight">{{ number_format($totalAstrologers) }}</div>
            <div class="text-[11px] text-text-muted mt-2 flex flex-wrap items-center gap-1.5 sm:gap-2">
                <span>{{ $approvedAstrologers }} Approved</span>
                <span>•</span>
                <span class="{{ $pendingAstrologers > 0 ? 'text-amber-600 font-bold' : '' }}">{{ $pendingAstrologers }} Pending</span>
            </div>
        </div>

        <!-- Revenue Card (Period-Aware) -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-xs hover:border-emerald-500/40 transition-all group">
            <div class="flex justify-between items-start mb-3 sm:mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-lg sm:text-xl group-hover:scale-110 transition-transform shrink-0">
                    <i class="fas fa-wallet"></i>
                </div>
                @if($period !== 'all')
                    <span class="text-[11px] sm:text-xs font-bold px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full flex items-center gap-1 {{ $revenueGrowthPct['is_positive'] ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-rose-700 bg-rose-50 border border-rose-200' }}">
                        <i class="fas {{ $revenueGrowthPct['is_positive'] ? 'fa-arrow-up' : 'fa-arrow-down' }} text-[10px]"></i>
                        {{ $revenueGrowthPct['formatted'] }}
                    </span>
                @else
                    <span class="text-[11px] sm:text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">Revenue</span>
                @endif
            </div>
            <div class="text-text-muted text-[11px] sm:text-xs font-bold uppercase tracking-wider mb-1">{{ $periodLabel }} Revenue</div>
            <div class="text-2xl sm:text-3xl font-extrabold text-text-primary tracking-tight">₹{{ number_format($periodRevenue, 2) }}</div>
            <div class="text-[11px] text-text-muted mt-2 truncate">
                Completed Calls + Chats + SuperChats
            </div>
        </div>

        <!-- Orders Card (Period-Aware) -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-xs hover:border-blue-500/40 transition-all group">
            <div class="flex justify-between items-start mb-3 sm:mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-lg sm:text-xl group-hover:scale-110 transition-transform shrink-0">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                @if($period !== 'all')
                    <span class="text-[11px] sm:text-xs font-bold px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full flex items-center gap-1 {{ $orderGrowthPct['is_positive'] ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-rose-700 bg-rose-50 border border-rose-200' }}">
                        <i class="fas {{ $orderGrowthPct['is_positive'] ? 'fa-arrow-up' : 'fa-arrow-down' }} text-[10px]"></i>
                        {{ $orderGrowthPct['formatted'] }}
                    </span>
                @else
                    <span class="text-[11px] sm:text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200">Orders</span>
                @endif
            </div>
            <div class="text-text-muted text-[11px] sm:text-xs font-bold uppercase tracking-wider mb-1">{{ $periodLabel }} Orders</div>
            <div class="text-2xl sm:text-3xl font-extrabold text-text-primary tracking-tight">{{ number_format($periodOrders) }}</div>
            <div class="text-[11px] text-text-muted mt-2 truncate">
                Total completed consultations
            </div>
        </div>
    </div>

    <!-- Row 2: Secondary Real-Time KPI Mini Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Active Subscriptions -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center gap-3.5 sm:gap-4">
            <div class="w-10 h-10 sm:w-11 sm:h-11 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 shrink-0">
                <i class="fas fa-gem text-base sm:text-lg"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-bold text-text-muted uppercase tracking-wider truncate">Active Subscriptions</div>
                <div class="text-lg sm:text-xl font-extrabold text-text-primary">{{ number_format($activeSubscriptions) }}</div>
            </div>
        </div>

        <!-- Live Now (Video + Audio + Chat) -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center gap-3.5 sm:gap-4">
            <div class="relative w-10 h-10 sm:w-11 sm:h-11 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600 shrink-0">
                <i class="fas fa-broadcast-tower text-base sm:text-lg"></i>
                @if($liveNow > 0)
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-500 border-2 border-white rounded-full animate-pulse"></span>
                @endif
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-bold text-text-muted uppercase tracking-wider truncate">Live Consultations Now</div>
                <div class="text-lg sm:text-xl font-extrabold text-text-primary flex flex-wrap items-baseline gap-1.5">
                    {{ $liveNow }}
                    <span class="text-[10px] sm:text-[11px] font-medium text-text-muted">({{ $liveStreams }} Live, {{ $liveCalls }} Call, {{ $liveChats }} Chat)</span>
                </div>
            </div>
        </div>

        <!-- Pending Payouts -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center gap-3.5 sm:gap-4">
            <div class="w-10 h-10 sm:w-11 sm:h-11 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600 shrink-0">
                <i class="fas fa-hand-holding-usd text-base sm:text-lg"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-bold text-text-muted uppercase tracking-wider truncate">Pending Astrologer Payouts</div>
                <div class="text-lg sm:text-xl font-extrabold text-rose-600 truncate">₹{{ number_format($pendingPayouts, 2) }}</div>
            </div>
        </div>

        <!-- Platform Wallet Balance -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center gap-3.5 sm:gap-4">
            <div class="w-10 h-10 sm:w-11 sm:h-11 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 shrink-0">
                <i class="fas fa-piggy-bank text-base sm:text-lg"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-bold text-text-muted uppercase tracking-wider truncate">Total Wallet Balance</div>
                <div class="text-lg sm:text-xl font-extrabold text-text-primary truncate">₹{{ number_format($totalWalletBalance, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Row 3: Interactive Revenue & Orders Trend Chart -->
    <div class="bg-white rounded-2xl border border-gray-200 p-4 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 mb-4 sm:mb-6 border-b border-gray-100 pb-3 sm:pb-4">
            <div>
                <h2 class="text-sm sm:text-base font-bold text-text-primary flex items-center gap-2">
                    <i class="fas fa-chart-line text-primary"></i>
                    7-Day Revenue & Orders Trend
                </h2>
                <p class="text-[11px] sm:text-xs text-text-muted">Daily performance tracking over the last 7 days</p>
            </div>
            <div class="flex items-center gap-3 sm:gap-4 text-xs font-bold">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary shrink-0"></span>
                    <span class="text-text-secondary text-[11px] sm:text-xs">Revenue (₹)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-text-secondary text-[11px] sm:text-xs">Orders</span>
                </div>
            </div>
        </div>

        <div class="h-60 sm:h-72 md:h-80 w-full relative">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>

    <!-- Row 4: Recent Orders & Top Astrologers -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
        <!-- Recent Orders Table -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-light/20">
                <h3 class="font-bold text-text-primary text-xs sm:text-sm uppercase tracking-wide flex items-center gap-2">
                    <i class="fas fa-clock text-primary text-xs"></i>
                    Recent Consultations
                </h3>
                <a href="{{ route('admin.orders.index') }}" class="text-primary text-xs font-bold hover:underline flex items-center gap-1">
                    <span>View All</span>
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            </div>
            <div class="overflow-x-auto -webkit-overflow-scrolling-touch">
                <table class="w-full text-left min-w-[520px]">
                    <thead class="bg-light/40 border-b border-gray-100">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 text-[10px] sm:text-[11px] font-bold text-text-muted uppercase tracking-wider">Type / ID</th>
                            <th class="px-4 sm:px-6 py-3 text-[10px] sm:text-[11px] font-bold text-text-muted uppercase tracking-wider">Customer</th>
                            <th class="px-4 sm:px-6 py-3 text-[10px] sm:text-[11px] font-bold text-text-muted uppercase tracking-wider">Astrologer</th>
                            <th class="px-4 sm:px-6 py-3 text-[10px] sm:text-[11px] font-bold text-text-muted uppercase tracking-wider">Amount</th>
                            <th class="px-4 sm:px-6 py-3 text-[10px] sm:text-[11px] font-bold text-text-muted uppercase tracking-wider text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentOrders as $order)
                        <tr class="hover:bg-light/30 transition-colors">
                            <td class="px-4 sm:px-6 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-[10px] shrink-0">
                                        <i class="fas {{ $order['icon'] }}"></i>
                                    </span>
                                    <span class="text-xs font-bold text-text-primary">{{ $order['id'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5 text-xs font-semibold text-text-secondary truncate max-w-[140px]">{{ $order['consumer_name'] }}</td>
                            <td class="px-4 sm:px-6 py-3.5 text-xs font-bold text-primary truncate max-w-[140px]">{{ $order['provider_name'] }}</td>
                            <td class="px-4 sm:px-6 py-3.5 text-xs font-black text-text-primary">₹{{ number_format($order['amount'], 2) }}</td>
                            <td class="px-4 sm:px-6 py-3.5 text-center">
                                <span class="px-2 sm:px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold rounded-full uppercase whitespace-nowrap">
                                    {{ $order['status'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-text-muted text-xs">No recent orders recorded</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top 5 Astrologers -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-200 shadow-xs p-5 sm:p-6">
            <div class="border-b border-gray-100 pb-3 sm:pb-4 mb-4 sm:mb-5 flex items-center justify-between">
                <h3 class="font-bold text-text-primary text-xs sm:text-sm uppercase tracking-wide flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-500"></i>
                    Top 5 Astrologers
                </h3>
                <span class="text-[10px] sm:text-[11px] text-text-muted font-medium">By Earnings</span>
            </div>
            <div class="space-y-3 sm:space-y-4">
                @forelse($topAstrologers as $index => $astrologer)
                <div class="flex items-center justify-between p-2 sm:p-2.5 rounded-xl hover:bg-light/40 transition-colors gap-2">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-black text-xs sm:text-sm shrink-0 border border-primary/20">
                            {{ substr($astrologer->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-text-primary truncate">{{ $astrologer->name }}</div>
                            <div class="text-[10px] text-text-muted truncate">{{ number_format($astrologer->total_sessions) }} Completed Orders</div>
                        </div>
                    </div>
                    <div class="text-xs font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 sm:px-2.5 py-1 rounded-xl shrink-0 whitespace-nowrap">
                        {{ $astrologer->formatted_earnings }}
                    </div>
                </div>
                @empty
                <div class="text-center text-text-muted text-xs py-6">No astrologer earnings recorded yet</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Row 5: Registrations & Expiring Subscriptions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
        <!-- New User Registrations -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-light/20">
                <h3 class="font-bold text-text-primary text-xs sm:text-sm uppercase tracking-wide flex items-center gap-2">
                    <i class="fas fa-user-plus text-primary text-xs"></i>
                    New User Registrations
                </h3>
                <span class="text-[10px] font-bold text-primary bg-primary/10 px-2.5 py-0.5 rounded-full border border-primary/20">Latest 5</span>
            </div>
            <div class="p-3.5 sm:p-4 space-y-2">
                @forelse($newRegistrations as $user)
                <div class="flex items-center justify-between p-2.5 sm:p-3 rounded-xl hover:bg-light/40 transition-all border border-transparent hover:border-gray-100 gap-2">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-gradient-to-br from-primary to-primary-dark text-white flex items-center justify-center text-xs font-bold shrink-0">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-text-primary truncate">{{ $user->name }}</div>
                            <div class="text-[10px] text-text-muted truncate">{{ $user->email ?? $user->mobile ?? 'Registered' }}</div>
                        </div>
                    </div>
                    <div class="text-[10px] sm:text-[11px] font-medium text-text-muted shrink-0">{{ $user->created_at->format('d M Y') }}</div>
                </div>
                @empty
                <div class="text-center text-text-muted text-xs py-6">No users found</div>
                @endforelse
            </div>
        </div>

        <!-- Expiring Subscriptions -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-rose-50/50">
                <h3 class="font-bold text-text-primary text-xs sm:text-sm uppercase tracking-wide flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-rose-500 text-xs animate-pulse"></i>
                    Expiring Subscriptions
                </h3>
                <span class="text-[10px] font-bold text-rose-700 bg-rose-100/80 px-2.5 py-0.5 rounded-full border border-rose-200">Next 7 Days</span>
            </div>
            <div class="p-3.5 sm:p-4 space-y-2">
                @forelse($expiringSubscriptions as $subscription)
                <div class="flex items-center justify-between p-2.5 sm:p-3 rounded-xl border border-rose-100 bg-rose-50/20 gap-2">
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-text-primary truncate">{{ $subscription->plan->name ?? 'Subscription Plan' }}</div>
                        <div class="text-[10px] text-text-muted truncate">User: <strong>{{ $subscription->name }}</strong></div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Expires in</div>
                        <div class="text-xs font-extrabold text-text-primary">{{ $subscription->plan_expires_at ? max(0, (int) now()->diffInDays($subscription->plan_expires_at)) : 0 }} Days</div>
                    </div>
                </div>
                @empty
                <div class="text-center text-text-muted text-xs py-6">No subscriptions expiring in the next 7 days</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('revenueTrendChart');
    if (!ctx) return;

    const labels = @json($chartLabels);
    const revenueData = @json($chartRevenue);
    const ordersData = @json($chartOrders);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenue (₹)',
                    data: revenueData,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.08)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#f59e0b',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    yAxisID: 'yRevenue',
                },
                {
                    label: 'Orders',
                    data: ordersData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2,
                    borderDash: [4, 4],
                    pointBackgroundColor: '#10b981',
                    pointRadius: 2.5,
                    pointHoverRadius: 4.5,
                    yAxisID: 'yOrders',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (context) {
                            if (context.dataset.label.includes('Revenue')) {
                                return ' Revenue: ₹' + Number(context.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                            return ' Orders: ' + context.raw;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 10, weight: '500' },
                        maxRotation: 0,
                        autoSkip: true,
                    }
                },
                yRevenue: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: {
                        color: 'rgba(243, 244, 246, 0.8)',
                    },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 9 },
                        callback: function (value) {
                            if (value >= 1000) return '₹' + (value / 1000) + 'k';
                            return '₹' + value;
                        }
                    }
                },
                yOrders: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        color: '#10b981',
                        font: { size: 9 },
                        precision: 0,
                    }
                }
            }
        }
    });
});
</script>
@endsection
