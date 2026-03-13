@extends('admin.layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
    <div>
        <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Plan Inventory</h1>
        <p class="text-sm text-gray font-medium mt-2">Managing the spiritual service tiers and access protocols.</p>
    </div>
    <div class="flex gap-4">
        <a href="{{ route('admin.plans.subscriptions') }}" class="px-8 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all flex items-center gap-2">
            <i class="fas fa-file-invoice text-primary"></i> Lifecycle Log
        </a>
        <a href="{{ route('admin.plans.create') }}" class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-primary transition-all shadow-xl shadow-dark/20 flex items-center gap-3">
            <i class="fas fa-plus"></i> Engineer Tier
        </a>
    </div>
</div>

<!-- Plan Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
    @php
        $plans = [
            [
                'name' => 'Silver Lite',
                'price' => '499',
                'period' => 'Monthly',
                'color' => 'gray',
                'icon' => 'moon',
                'features' => ['3 Daily Horoscopes', 'Standard Chat Access', 'Community Forum'],
                'status' => 'Active',
                'users' => 452
            ],
            [
                'name' => 'Gold Pro',
                'price' => '999',
                'period' => 'Monthly',
                'color' => 'warning',
                'icon' => 'sun',
                'features' => ['Unlimited Horoscopes', 'Priority Chat (2hr wait)', '1 Free Call Monthly'],
                'status' => 'Active',
                'users' => 812
            ],
            [
                'name' => 'Platinum Elite',
                'price' => '2,499',
                'period' => 'Monthly',
                'color' => 'primary',
                'icon' => 'gem',
                'features' => ['Real-time Alerts', 'Instant Expert Access', 'Weekly Video Consult'],
                'status' => 'Active',
                'users' => 324
            ],
            [
                'name' => 'Astro Annual',
                'price' => '9,999',
                'period' => 'Yearly',
                'color' => 'info',
                'icon' => 'infinity',
                'features' => ['Full Suite Access', 'Dedicated Concierge', 'VVIP Event Access'],
                'status' => 'Inactive',
                'users' => 12
            ]
        ];
    @endphp

    @foreach($plans as $plan)
    <div class="bg-white rounded-[48px] border border-gray-lighter shadow-sm group hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 overflow-hidden flex flex-col h-full">
        <!-- Top Banner -->
        <div class="h-3 shadow-inner bg-{{ $plan['color'] }}"></div>
        
        <div class="p-8 pb-4 flex items-start justify-between">
            <div class="w-14 h-14 rounded-2xl bg-{{ $plan['color'] }}/10 text-{{ $plan['color'] }} flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-{{ $plan['icon'] }}"></i>
            </div>
            <div class="text-right">
                <span class="text-[9px] font-black uppercase px-3 py-1 bg-{{ $plan['status'] == 'Active' ? 'success' : 'gray' }}/10 text-{{ $plan['status'] == 'Active' ? 'success' : 'gray' }} rounded-full border border-{{ $plan['status'] == 'Active' ? 'success' : 'gray' }}/20">
                    {{ $plan['status'] }}
                </span>
                <div class="mt-2 text-[9px] font-bold text-gray uppercase tracking-widest text-right">{{ $plan['users'] }} Active Souls</div>
            </div>
        </div>

        <div class="p-8 pt-4 flex-1">
            <h3 class="text-xl font-black text-dark tracking-tighter uppercase mb-1">{{ $plan['name'] }}</h3>
            <div class="flex items-baseline gap-1 mb-6">
                <span class="text-sm font-black text-dark">₹</span>
                <span class="text-3xl font-black text-dark tracking-tighter">{{ $plan['price'] }}</span>
                <span class="text-[10px] font-bold text-gray uppercase tracking-widest">/ {{ $plan['period'] }}</span>
            </div>

            <div class="space-y-3 mb-8">
                @foreach($plan['features'] as $feature)
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-[10px] text-{{ $plan['color'] }} mt-1"></i>
                    <span class="text-[11px] font-semibold text-gray-light leading-snug">{{ $feature }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="p-8 pt-0 mt-auto grid grid-cols-2 gap-3">
            <button class="px-4 py-3 bg-light text-dark text-[10px] font-black uppercase rounded-2xl hover:bg-dark hover:text-white transition-all">Configure</button>
            <button class="px-4 py-3 bg-white border border-gray-lighter text-dark text-[10px] font-black uppercase rounded-2xl hover:bg-danger hover:text-white hover:border-danger transition-all">Suspend</button>
        </div>
    </div>
    @endforeach

    <!-- Add Plan Card -->
    <a href="{{ route('admin.plans.create') }}" class="bg-light/30 rounded-[48px] border-4 border-dashed border-gray-lighter flex flex-col items-center justify-center p-10 group hover:border-primary/30 hover:bg-white transition-all duration-500 min-h-[400px]">
        <div class="w-16 h-16 rounded-full bg-white border border-gray-lighter text-gray group-hover:text-primary group-hover:border-primary group-hover:scale-110 transition-all flex items-center justify-center text-xl mb-4 shadow-sm">
            <i class="fas fa-plus"></i>
        </div>
        <div class="text-[10px] font-black text-dark uppercase tracking-[0.3em]">Architect New Tier</div>
        <div class="mt-2 text-[9px] font-bold text-gray uppercase italic">Extend platform capabilities</div>
    </a>
</div>
@endsection
