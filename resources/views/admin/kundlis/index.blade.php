@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Kundli Management</h1>
            <p class="text-sm text-gray font-medium">Manage horoscope and birth chart records.</p>
        </div>
        <a href="{{ route('admin.kundlis.create') }}" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2 justify-center md:justify-start">
            <i class="fas fa-plus"></i> New Kundli
        </a>
    </div>

    <!-- Kundli Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-primary/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Kundlis</div>
            <div class="text-3xl font-black text-dark">{{ $total ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-primary font-black text-[9px] uppercase">
                <i class="fas fa-book"></i> All Records
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-info/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Male</div>
            <div class="text-3xl font-black text-dark">{{ $male ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-info font-black text-[9px] uppercase">
                <i class="fas fa-mars"></i> Records
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-danger/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Female</div>
            <div class="text-3xl font-black text-dark">{{ $female ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-danger font-black text-[9px] uppercase">
                <i class="fas fa-venus"></i> Records
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-success/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">This Month</div>
            <div class="text-3xl font-black text-dark">{{ $this_month ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-calendar"></i> Added
            </div>
        </div>
    </div>

    <!-- Filter Console -->
    <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm mb-8">
        <form method="GET" action="{{ route('admin.kundlis.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Search</label>
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray transition-colors group-focus-within:text-dark"></i>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by name..." class="w-full bg-light/50 border border-gray-lighter pl-11 pr-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all focus:bg-white focus:shadow-sm">
                </div>
            </div>
            <div class="w-full sm:w-40">
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Gender</label>
                <select name="gender" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                    <option value="">All Gender</option>
                    <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ request('gender') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <button type="submit" class="bg-dark text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 transform active:scale-95 h-[52px]">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
            <a href="{{ route('admin.kundlis.index') }}" class="bg-light text-dark px-6 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-lighter transition-all h-[52px] flex items-center">
                <i class="fas fa-redo mr-2"></i> Reset
            </a>
        </form>
    </div>

    <!-- Kundli Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Name</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Gender</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Birth Date</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Location</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Created</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kundlis as $kundli)
                    <tr class="border-b border-gray-lighter hover:bg-light/20 transition-all">
                        <td class="px-6 py-5">
                            <div class="font-bold text-dark">{{ $kundli->name }}</div>
                        </td>
                        <td class="px-6 py-5">
                            @if($kundli->gender === 'male')
                                <span class="inline-block px-3 py-1.5 bg-info/10 text-info text-[10px] font-black uppercase rounded-full border border-info/20">
                                    <i class="fas fa-mars mr-1"></i> Male
                                </span>
                            @elseif($kundli->gender === 'female')
                                <span class="inline-block px-3 py-1.5 bg-danger/10 text-danger text-[10px] font-black uppercase rounded-full border border-danger/20">
                                    <i class="fas fa-venus mr-1"></i> Female
                                </span>
                            @else
                                <span class="inline-block px-3 py-1.5 bg-gray/10 text-gray text-[10px] font-black uppercase rounded-full border border-gray/20">
                                    <i class="fas fa-question-circle mr-1"></i> Other
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-sm text-dark font-medium">
                            {{ $kundli->birth_date ? \Carbon\Carbon::parse($kundli->birth_date)->format('M d, Y') : 'N/A' }}
                        </td>
                        <td class="px-6 py-5 text-sm text-gray">
                            {{ $kundli->birth_location ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-5 text-sm text-gray">
                            {{ $kundli->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.kundlis.show', $kundli->id) }}" class="inline-flex items-center justify-center w-9 h-9 bg-primary/10 text-primary rounded-lg hover:bg-primary hover:text-white transition-all" title="View">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                                <a href="{{ route('admin.kundlis.edit', $kundli->id) }}" class="inline-flex items-center justify-center w-9 h-9 bg-info/10 text-info rounded-lg hover:bg-info hover:text-white transition-all" title="Edit">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-4xl text-gray-lighter mb-2"><i class="fas fa-inbox"></i></div>
                            <p class="text-gray font-bold">No kundlis found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($kundlis->hasPages())
        <div class="px-6 py-6 border-t border-gray-lighter flex items-center justify-between">
            <div class="text-[10px] font-bold text-gray uppercase tracking-widest">
                Showing {{ $kundlis->firstItem() ?? 0 }} to {{ $kundlis->lastItem() ?? 0 }} of {{ $kundlis->total() }}
            </div>
            <div class="flex gap-2">
                {{ $kundlis->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
