@extends('admin.layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Dashboard Overview</h1>
        <p class="text-sm text-gray font-medium">Monitoring platform performance and user activity.</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="px-3 py-1.5 bg-white border border-gray-lighter rounded-lg text-xs font-bold text-gray uppercase shadow-sm">
            <i class="fas fa-calendar-alt mr-2 text-primary"></i> {{ now()->format('d M Y') }}
        </span>
    </div>
</div>

<!-- Row 1 - Core Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Users -->
    <div class="bg-white p-6 rounded-2xl shadow-md border-b-4 border-primary hover:shadow-xl transition-all group">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary text-2xl group-hover:scale-110 transition-transform">
                <i class="fas fa-users"></i>
            </div>
            <span class="text-xs font-bold text-success bg-success/10 px-2 py-1 rounded-full">+12.5%</span>
        </div>
        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Total Users</div>
        <div class="text-3xl font-black text-dark">{{ number_format($totalUsers) }}</div>
    </div>

    <!-- Total Astrologers -->
    <div class="bg-white p-6 rounded-2xl shadow-md border-b-4 border-secondary hover:shadow-xl transition-all group">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-secondary/10 rounded-xl flex items-center justify-center text-secondary text-2xl group-hover:scale-110 transition-transform">
                <i class="fas fa-user-tie"></i>
            </div>
            <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-1 rounded-full">{{ $approvedAstrologers }} Active</span>
        </div>
        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Total Astrologers</div>
        <div class="text-3xl font-black text-dark">{{ number_format($totalAstrologers) }}</div>
    </div>

    <!-- Today Revenue -->
    <div class="bg-white p-6 rounded-2xl shadow-md border-b-4 border-success hover:shadow-xl transition-all group">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-success/10 rounded-xl flex items-center justify-center text-success text-2xl group-hover:scale-110 transition-transform">
                <i class="fas fa-wallet"></i>
            </div>
            <span class="text-xs font-bold text-success bg-success/10 px-2 py-1 rounded-full"><i class="fas fa-arrow-up"></i> 8%</span>
        </div>
        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Today Revenue</div>
        <div class="text-3xl font-black text-dark">₹{{ number_format($todayRevenue, 2) }}</div>
    </div>

    <!-- Today Orders -->
    <div class="bg-white p-6 rounded-2xl shadow-md border-b-4 border-info hover:shadow-xl transition-all group">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-info/10 rounded-xl flex items-center justify-center text-info text-2xl group-hover:scale-110 transition-transform">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <span class="text-xs font-bold text-info bg-info/10 px-2 py-1 rounded-full">{{ number_format($todayOrders) }} Total</span>
        </div>
        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Today Orders</div>
        <div class="text-3xl font-black text-dark">{{ number_format($todayOrders) }}</div>
    </div>
</div>

<!-- Row 2 - Secondary Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <!-- Active Subscriptions -->
    <div class="bg-light/40 p-5 rounded-xl border border-gray-lighter flex items-center gap-4">
        <div class="w-10 h-10 bg-white shadow-sm rounded-lg flex items-center justify-center text-primary"><i class="fas fa-gem"></i></div>
        <div>
            <div class="text-xs font-bold text-gray uppercase tracking-tighter">Active Subscriptions</div>
            <div class="text-xl font-black text-dark">{{ number_format($activeSubscriptions) }}</div>
        </div>
    </div>
    <!-- Live Astrologers -->
    <div class="bg-light/40 p-5 rounded-xl border border-gray-lighter flex items-center gap-4">
        <div class="relative">
            <div class="w-10 h-10 bg-white shadow-sm rounded-lg flex items-center justify-center text-success"><i class="fas fa-broadcast-tower"></i></div>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-success border-2 border-white rounded-full animate-pulse"></span>
        </div>
        <div>
            <div class="text-xs font-bold text-gray uppercase tracking-tighter">Live Now</div>
            <div class="text-xl font-black text-dark">{{ $liveNow }}</div>
        </div>
    </div>
    <!-- Pending Payouts -->
    <div class="bg-light/40 p-5 rounded-xl border border-gray-lighter flex items-center gap-4">
        <div class="w-10 h-10 bg-white shadow-sm rounded-lg flex items-center justify-center text-danger"><i class="fas fa-hand-holding-usd"></i></div>
        <div>
            <div class="text-xs font-bold text-gray uppercase tracking-tighter">Pending Payouts</div>
            <div class="text-xl font-black text-danger">₹{{ number_format($pendingPayouts, 2) }}</div>
        </div>
    </div>
    <!-- Total Wallet -->
    <div class="bg-light/40 p-5 rounded-xl border border-gray-lighter flex items-center gap-4">
        <div class="w-10 h-10 bg-white shadow-sm rounded-lg flex items-center justify-center text-info"><i class="fas fa-piggy-bank"></i></div>
        <div>
            <div class="text-xs font-bold text-gray uppercase tracking-tighter">Total Wallet Balance</div>
            <div class="text-xl font-black text-dark">₹{{ number_format($totalWalletBalance, 2) }}</div>
        </div>
    </div>
</div>

<!-- Row 3 - Recent Orders & Top Astrologers -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Recent Orders Table -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-lighter flex justify-between items-center">
            <h3 class="font-black text-dark uppercase tracking-wide text-sm">Recent Orders</h3>
            <a href="{{ route('admin.orders.index') }}" class="text-primary text-xs font-bold hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-light/50">
                    <tr>
                        <th class="px-6 py-3 text-[10px] font-black text-gray uppercase">Order ID</th>
                        <th class="px-6 py-3 text-[10px] font-black text-gray uppercase">Customer</th>
                        <th class="px-6 py-3 text-[10px] font-black text-gray uppercase">Astrologer</th>
                        <th class="px-6 py-3 text-[10px] font-black text-gray uppercase">Amount</th>
                        <th class="px-6 py-3 text-[10px] font-black text-gray uppercase text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($recentOrders as $order)
                    <tr class="hover:bg-light/30 transition-colors">
                        <td class="px-6 py-4 text-xs font-bold text-dark">{{ $order['id'] }}</td>
                        <td class="px-6 py-4 text-xs font-semibold text-gray">{{ $order['consumer_name'] }}</td>
                        <td class="px-6 py-4 text-xs font-semibold text-primary">{{ $order['provider_name'] }}</td>
                        <td class="px-6 py-4 text-xs font-black text-dark">₹{{ number_format($order['amount'], 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 bg-success/10 text-success text-[10px] font-black rounded uppercase">{{ $order['status'] }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray text-xs">No orders found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Astrologers -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-lighter">
            <h3 class="font-black text-dark uppercase tracking-wide text-sm">Top 5 Astrologers</h3>
        </div>
        <div class="p-6 space-y-6">
            @forelse($topAstrologers as $astrologer)
            <div class="flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-black group-hover:bg-primary group-hover:text-white transition-all">{{ substr($astrologer->name, 0, 1) }}</div>
                    <div>
                        <div class="text-xs font-black text-dark">{{ $astrologer->name }}</div>
                        <div class="text-[10px] text-gray italic">{{ number_format($astrologer->total_sessions) }} Orders</div>
                    </div>
                </div>
                <div class="text-xs font-black text-success">₹{{ number_format($astrologer->total_earned / 100000, 1) }}L</div>
            </div>
            @empty
            <div class="text-center text-gray text-xs py-6">No astrologers found</div>
            @endforelse
        </div>
    </div>
</div>

<!-- Row 4 - Registrations & Subscriptions -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Recent User Registrations -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-lighter flex justify-between items-center bg-light/20">
            <h3 class="font-black text-dark uppercase tracking-wide text-sm">New Registrations</h3>
            <span class="text-[10px] font-black text-primary bg-primary/10 px-2 py-0.5 rounded">Last 5</span>
        </div>
        <div class="p-4 space-y-3">
            @forelse($newRegistrations as $user)
            <div class="flex items-center justify-between p-3 hover:bg-light/50 rounded-xl transition-all border border-transparent hover:border-gray-lighter">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-linear-to-br from-primary-light to-primary text-white flex items-center justify-center text-[10px] font-black">{{ substr($user->name, 0, 1) }}</div>
                    <div>
                        <div class="text-xs font-bold text-dark">{{ $user->name }}</div>
                        <div class="text-[10px] text-gray">{{ $user->email }}</div>
                    </div>
                </div>
                <div class="text-[10px] font-bold text-gray uppercase tracking-tighter">{{ $user->created_at->format('d M Y') }}</div>
            </div>
            @empty
            <div class="text-center text-gray text-xs py-6">No new registrations</div>
            @endforelse
        </div>
    </div>

    <!-- Expiring Soon -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-lighter flex justify-between items-center bg-danger/5">
            <h3 class="font-black text-dark uppercase tracking-wide text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-danger text-xs animate-pulse"></i> Expiring Subscriptions
            </h3>
        </div>
        <div class="p-4 space-y-3">
            @forelse($expiringSubscriptions as $subscription)
            <div class="flex items-center justify-between p-3 rounded-xl border-l-[3px] border-danger/30 bg-light/20">
                <div>
                    <div class="text-xs font-black text-dark">{{ $subscription->plan->name ?? 'N/A' }}</div>
                    <div class="text-[10px] text-gray font-semibold italic">User: {{ $subscription->name }}</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-black text-danger uppercase tracking-tighter">Expires in</div>
                    <div class="text-xs font-bold text-dark">{{ $subscription->plan_expires_at->diffInDays() }} Days</div>
                </div>
            </div>
            @empty
            <div class="text-center text-gray text-xs py-6">No expiring subscriptions</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
