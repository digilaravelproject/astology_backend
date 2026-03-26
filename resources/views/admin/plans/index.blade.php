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
<div class="space-y-6">
    @foreach($plans as $plan)
    @php
        $cardColor = $plan->status === 'active' ? 'primary' : 'gray';
        $cardIcon = match(strtolower($plan->name)) {
            'silver lite', 'silver' => 'moon',
            'gold pro', 'gold' => 'sun',
            'platinum elite', 'platinum' => 'gem',
            default => 'star'
        };
        $planUsers = $plan->users()->count();
        $period = $plan->duration_days >= 365 ? 'Yearly' : ($plan->duration_days >= 90 ? 'Quarterly' : 'Monthly');
    @endphp
    <div class="bg-white rounded-[24px] border border-gray-lighter shadow-sm group hover:shadow-lg transition-all duration-300 overflow-hidden">
        <!-- Card Content - Horizontal Layout -->
        <div class="flex items-center justify-between p-6 gap-6">
            <!-- Left: Icon & Plan Details -->
            <div class="flex items-center gap-6 flex-1">
                <!-- Icon -->
                <div class="w-14 h-14 rounded-2xl bg-{{ $cardColor }}/10 text-{{ $cardColor }} flex items-center justify-center text-2xl flex-shrink-0 group-hover:scale-110 transition-transform">
                    <i class="fas fa-{{ $cardIcon }}"></i>
                </div>
                
                <!-- Plan Name & Details -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-lg font-black text-dark tracking-tighter uppercase">{{ $plan->name }}</h3>
                        <span class="text-[8px] font-black uppercase px-2 py-1 bg-{{ $plan->status === 'active' ? 'success' : 'gray' }}/10 text-{{ $plan->status === 'active' ? 'success' : 'gray' }} rounded-full border border-{{ $plan->status === 'active' ? 'success' : 'gray' }}/20 flex-shrink-0">
                            {{ ucfirst($plan->status) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray font-medium">{{ $plan->description ?? 'Subscription plan' }}</p>
                    <div class="flex items-center gap-4 mt-2 flex-wrap">
                        <span class="text-[9px] font-bold text-gray uppercase tracking-widest">{{ $planUsers }} Active Users</span>
                        <span class="text-[9px] font-bold text-gray uppercase tracking-widest">{{ $plan->duration_days }} Days</span>
                    </div>
                </div>
            </div>

            <!-- Middle: Price -->
            <div class="text-center flex-shrink-0">
                <div class="flex items-baseline gap-1 justify-center">
                    <span class="text-sm font-black text-dark">₹</span>
                    <span class="text-2xl font-black text-dark tracking-tighter">{{ number_format($plan->price, 2) }}</span>
                </div>
                <span class="text-[9px] font-bold text-gray uppercase tracking-widest block mt-1">/ {{ $period }}</span>
            </div>

            <!-- Right: Features Count & Actions -->
            <div class="flex items-center gap-4 flex-shrink-0">
                <div class="text-center">
                    <div class="text-lg font-black text-{{ $cardColor }}">{{ count($plan->features ?? []) }}</div>
                    <span class="text-[9px] font-bold text-gray uppercase tracking-widest">Features</span>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('admin.plans.edit', $plan->id) }}" class="px-4 py-2 bg-light text-dark text-[9px] font-black uppercase rounded-lg hover:bg-dark hover:text-white transition-all" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Are you sure to delete this plan?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-white border border-gray-lighter text-dark text-[9px] font-black uppercase rounded-lg hover:bg-danger hover:text-white hover:border-danger transition-all" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Add Plan Card -->
    <a href="{{ route('admin.plans.create') }}" class="bg-light/30 rounded-[24px] border-4 border-dashed border-gray-lighter flex items-center justify-center p-8 group hover:border-primary/30 hover:bg-white transition-all duration-500 h-24">
        <div class="flex items-center gap-6 w-full justify-center">
            <div class="w-12 h-12 rounded-full bg-white border border-gray-lighter text-gray group-hover:text-primary group-hover:border-primary group-hover:scale-110 transition-all flex items-center justify-center text-lg flex-shrink-0 shadow-sm">
                <i class="fas fa-plus"></i>
            </div>
            <div class="text-center">
                <div class="text-[11px] font-black text-dark uppercase tracking-[0.2em]">Add New Plan</div>
                <div class="text-[9px] font-semibold text-gray uppercase italic">Create subscription tier</div>
            </div>
        </div>
    </a>
</div>
@endsection
