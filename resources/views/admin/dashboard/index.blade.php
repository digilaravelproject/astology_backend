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
            <i class="fas fa-calendar-alt mr-2 text-primary"></i> 13 Mar 2024
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
        <div class="text-3xl font-black text-dark">2,847</div>
    </div>

    <!-- Total Astrologers -->
    <div class="bg-white p-6 rounded-2xl shadow-md border-b-4 border-secondary hover:shadow-xl transition-all group">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-secondary/10 rounded-xl flex items-center justify-center text-secondary text-2xl group-hover:scale-110 transition-transform">
                <i class="fas fa-user-tie"></i>
            </div>
            <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-1 rounded-full">89 Active</span>
        </div>
        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Total Astrologers</div>
        <div class="text-3xl font-black text-dark">156</div>
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
        <div class="text-3xl font-black text-dark">₹45,230</div>
    </div>

    <!-- Today Orders -->
    <div class="bg-white p-6 rounded-2xl shadow-md border-b-4 border-info hover:shadow-xl transition-all group">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-info/10 rounded-xl flex items-center justify-center text-info text-2xl group-hover:scale-110 transition-transform">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <span class="text-xs font-bold text-info bg-info/10 px-2 py-1 rounded-full">127 Total</span>
        </div>
        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Today Orders</div>
        <div class="text-3xl font-black text-dark">127</div>
    </div>
</div>

<!-- Row 2 - Secondary Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <!-- Active Subscriptions -->
    <div class="bg-light/40 p-5 rounded-xl border border-gray-lighter flex items-center gap-4">
        <div class="w-10 h-10 bg-white shadow-sm rounded-lg flex items-center justify-center text-primary"><i class="fas fa-gem"></i></div>
        <div>
            <div class="text-xs font-bold text-gray uppercase tracking-tighter">Active Subscriptions</div>
            <div class="text-xl font-black text-dark">1,234</div>
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
            <div class="text-xl font-black text-dark">23</div>
        </div>
    </div>
    <!-- Pending Payouts -->
    <div class="bg-light/40 p-5 rounded-xl border border-gray-lighter flex items-center gap-4">
        <div class="w-10 h-10 bg-white shadow-sm rounded-lg flex items-center justify-center text-danger"><i class="fas fa-hand-holding-usd"></i></div>
        <div>
            <div class="text-xs font-bold text-gray uppercase tracking-tighter">Pending Payouts</div>
            <div class="text-xl font-black text-danger">₹1,23,450</div>
        </div>
    </div>
    <!-- Total Wallet -->
    <div class="bg-light/40 p-5 rounded-xl border border-gray-lighter flex items-center gap-4">
        <div class="w-10 h-10 bg-white shadow-sm rounded-lg flex items-center justify-center text-info"><i class="fas fa-piggy-bank"></i></div>
        <div>
            <div class="text-xs font-bold text-gray uppercase tracking-tighter">Total Wallet Balance</div>
            <div class="text-xl font-black text-dark">₹8,45,670</div>
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
                    @foreach(['ORD-5501' => ['Rahul Sharma', 'Aarti Sharma', '₹500', 'Success'], 'ORD-5502' => ['Priya Patel', 'Vikram Joshi', '₹800', 'Ongoing'], 'ORD-5503' => ['Amit Kumar', 'Sneha Gupta', '₹300', 'Failed'], 'ORD-5504' => ['Sneha Gupta', 'Aarti Sharma', '₹450', 'Success'], 'ORD-5505' => ['Vikram Singh', 'Raj Malhotra', '₹600', 'Success']] as $id => $data)
                    <tr class="hover:bg-light/30 transition-colors">
                        <td class="px-6 py-4 text-xs font-bold text-dark">{{ $id }}</td>
                        <td class="px-6 py-4 text-xs font-semibold text-gray">{{ $data[0] }}</td>
                        <td class="px-6 py-4 text-xs font-semibold text-primary">{{ $data[1] }}</td>
                        <td class="px-6 py-4 text-xs font-black text-dark">{{ $data[2] }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($data[3] == 'Success')
                                <span class="px-2 py-1 bg-success/10 text-success text-[10px] font-black rounded uppercase">Success</span>
                            @elseif($data[3] == 'Ongoing')
                                <span class="px-2 py-1 bg-info/10 text-info text-[10px] font-black rounded uppercase">Ongoing</span>
                            @else
                                <span class="px-2 py-1 bg-danger/10 text-danger text-[10px] font-black rounded uppercase">Failed</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
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
            @foreach(['Aarti Sharma' => ['124 Orders', '₹2.4L'], 'Vikram Joshi' => ['98 Orders', '₹1.8L'], 'Sneha Gupta' => ['85 Orders', '₹1.5L'], 'Raj Malhotra' => ['72 Orders', '₹1.2L'], 'Pooja Reddy' => ['65 Orders', '₹1.1L']] as $name => $stats)
            <div class="flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-black group-hover:bg-primary group-hover:text-white transition-all">{{ substr($name, 0, 1) }}</div>
                    <div>
                        <div class="text-xs font-black text-dark">{{ $name }}</div>
                        <div class="text-[10px] text-gray italic">{{ $stats[0] }}</div>
                    </div>
                </div>
                <div class="text-xs font-black text-success">{{ $stats[1] }}</div>
            </div>
            @endforeach
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
            @foreach(['Anjali Verma' => 'anjali.v@gmail.com', 'Raj Malhotra' => 'raj.m@yahoo.com', 'Pooja Reddy' => 'pooja.r@gmail.com', 'Arjun Nair' => 'arjun.n@outlook.com', 'Kavita Joshi' => 'kavita.j@gmail.com'] as $user => $email)
            <div class="flex items-center justify-between p-3 hover:bg-light/50 rounded-xl transition-all border border-transparent hover:border-gray-lighter">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-linear-to-br from-primary-light to-primary text-white flex items-center justify-center text-[10px] font-black">{{ substr($user, 0, 1) }}</div>
                    <div>
                        <div class="text-xs font-bold text-dark">{{ $user }}</div>
                        <div class="text-[10px] text-gray">{{ $email }}</div>
                    </div>
                </div>
                <div class="text-[10px] font-bold text-gray uppercase tracking-tighter">12 Mar 2024</div>
            </div>
            @endforeach
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
            @php
                $expiringItems = [
                    ['plan' => 'Premium Monthly', 'user' => 'Rahul Sharma', 'days' => '2 Days'],
                    ['plan' => 'Basic Yearly', 'user' => 'Priya Patel', 'days' => '4 Days'],
                    ['plan' => 'Premium Yearly', 'user' => 'Amit Kumar', 'days' => '5 Days'],
                    ['plan' => 'Basic Monthly', 'user' => 'Sneha Gupta', 'days' => '6 Days'],
                    ['plan' => 'Premium Monthly', 'user' => 'Vikram Singh', 'days' => '7 Days'],
                ];
            @endphp
            @foreach($expiringItems as $item)
            <div class="flex items-center justify-between p-3 rounded-xl border-l-[3px] border-danger/30 bg-light/20">
                <div>
                    <div class="text-xs font-black text-dark">{{ $item['plan'] }}</div>
                    <div class="text-[10px] text-gray font-semibold italic">User: {{ $item['user'] }}</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-black text-danger uppercase tracking-tighter">Expires in</div>
                    <div class="text-xs font-bold text-dark">{{ $item['days'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
