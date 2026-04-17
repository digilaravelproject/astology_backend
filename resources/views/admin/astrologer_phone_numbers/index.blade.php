@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Phone Number Verification</h1>
            <p class="text-sm text-gray font-medium">Manage and verify astrologer contact numbers.</p>
        </div>
    </div>

    <!-- Phone Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-primary/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Numbers</div>
            <div class="text-3xl font-black text-dark">{{ $total ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-primary font-black text-[9px] uppercase">
                <i class="fas fa-phone"></i> All Records
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-success/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Verified</div>
            <div class="text-3xl font-black text-dark">{{ $verified ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-check-circle"></i> Confirmed
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-warning/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Unverified</div>
            <div class="text-3xl font-black text-dark">{{ $unverified ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-warning font-black text-[9px] uppercase">
                <i class="fas fa-exclamation-circle"></i> Pending
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-info/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Default</div>
            <div class="text-3xl font-black text-dark">{{ $default ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-info font-black text-[9px] uppercase">
                <i class="fas fa-star"></i> Primary
            </div>
        </div>
    </div>

    <!-- Filter Console -->
    <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm mb-8">
        <form method="GET" action="{{ route('admin.astrologer-phone-numbers.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Search</label>
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray transition-colors group-focus-within:text-dark"></i>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by astrologer or phone number..." class="w-full bg-light/50 border border-gray-lighter pl-11 pr-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all focus:bg-white focus:shadow-sm">
                </div>
            </div>
            <div class="w-full sm:w-40">
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Status</label>
                <select name="status" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="unverified" {{ request('status') === 'unverified' ? 'selected' : '' }}>Unverified</option>
                </select>
            </div>
            <button type="submit" class="bg-dark text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 transform active:scale-95 h-[52px]">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
            <a href="{{ route('admin.astrologer-phone-numbers.index') }}" class="bg-light text-dark px-6 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-lighter transition-all h-[52px] flex items-center">
                <i class="fas fa-redo mr-2"></i> Reset
            </a>
        </form>
    </div>

    <!-- Phone Numbers Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Astrologer</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Phone Number</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Country</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Verification</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Default</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($phones as $phone)
                    <tr class="border-b border-gray-lighter hover:bg-light/20 transition-all">
                        <td class="px-6 py-5">
                            <div class="font-bold text-dark">{{ $phone->astrologer->name ?? 'N/A' }}</div>
                            <div class="text-[10px] text-gray">{{ $phone->astrologer->email ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="font-mono font-black text-dark">{{ $phone->country_code }}{{ $phone->phone_number }}</div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-medium text-dark">{{ $phone->country_name ?? $phone->country_code }}</div>
                        </td>
                        <td class="px-6 py-5">
                            @if($phone->otp_verified_at)
                                <span class="inline-block px-3 py-1.5 bg-success/10 text-success text-[10px] font-black uppercase rounded-full border border-success/20">
                                    <i class="fas fa-check-circle mr-1"></i> Verified
                                </span>
                            @else
                                <span class="inline-block px-3 py-1.5 bg-warning/10 text-warning text-[10px] font-black uppercase rounded-full border border-warning/20">
                                    <i class="fas fa-clock mr-1"></i> Unverified
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            @if($phone->is_default)
                                <span class="inline-block px-3 py-1.5 bg-primary/10 text-primary text-[10px] font-black uppercase rounded-full border border-primary/20">
                                    <i class="fas fa-star mr-1"></i> Default
                                </span>
                            @else
                                <span class="inline-block px-2 py-1 text-[9px] text-gray font-bold">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.astrologer-phone-numbers.show', $phone->id) }}" class="inline-flex items-center justify-center w-9 h-9 bg-primary/10 text-primary rounded-lg hover:bg-primary hover:text-white transition-all" title="View">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-4xl text-gray-lighter mb-2"><i class="fas fa-inbox"></i></div>
                            <p class="text-gray font-bold">No phone numbers found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($phones->hasPages())
        <div class="px-6 py-6 border-t border-gray-lighter flex items-center justify-between">
            <div class="text-[10px] font-bold text-gray uppercase tracking-widest">
                Showing {{ $phones->firstItem() ?? 0 }} to {{ $phones->lastItem() ?? 0 }} of {{ $phones->total() }}
            </div>
            <div class="flex gap-2">
                {{ $phones->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
