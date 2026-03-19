@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Astrologer Partners</h1>
            <p class="text-sm text-gray font-medium">Manage and verify astrologer professionals on the platform.</p>
            <div class="text-xs text-gray mt-1">Total astrologers: <span class="font-bold text-dark">{{ $totalAstrologers }}</span></div>
        </div>
        <a href="{{ route('admin.astrologers.create') }}" class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-primary/20 hover:bg-primary-dark transition-all flex items-center gap-2">
            <i class="fas fa-user-tie"></i> Add New Astrologer
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.astrologers.index') }}" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-lighter mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Search Professionals</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-light"></i>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by name, email or phone..." class="w-full pl-11 pr-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Status</label>
                <select name="status" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm appearance-none">
                    <option value="" {{ request('status') === null ? 'selected' : '' }}>All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-dark text-white py-3 rounded-xl font-bold hover:bg-black transition-all">Filter Experts</button>
            </div>
        </div>
    </form>

    <!-- Astrologers Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/50 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Astrologer</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Expertise & Exp.</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-center">Languages</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-center">Joined</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($astrologers as $astro)
                        @php
                            $profile = $astro->astrologer;
                            $expertise = $profile?->areas_of_expertise ? implode(', ', $profile->areas_of_expertise) : '-';
                            $languages = $profile?->languages ?? [];
                            $status = $profile?->status ?? 'pending';
                        @endphp
                        <tr class="hover:bg-light/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-primary/10 to-primary/30 p-1 border border-primary/20">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($astro->name) }}&background=E8C461&color=fff&bold=true&rounded=true" class="w-full h-full object-cover rounded-xl" alt="">
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-dark group-hover:text-primary transition-colors">{{ $astro->name }}</div>
                                        <div class="text-[11px] font-bold text-gray-light">ID: #USR-{{ $astro->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-black text-dark">{{ $expertise }}</div>
                                <div class="text-[10px] font-bold text-gray uppercase">{{ $profile?->years_of_experience ? $profile->years_of_experience . ' Years' : '-' }} Exp</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-wrap justify-center gap-1">
                                    @foreach($languages as $lang)
                                        @if($lang)
                                            <span class="px-2 py-0.5 bg-light border border-gray-lighter text-[9px] font-black text-gray rounded uppercase">{{ $lang }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-xs font-semibold text-gray">{{ $astro->created_at?->format('d M Y') }}</td>
                            <td class="px-6 py-4">
                                @if($status === 'approved')
                                    <span class="px-2.5 py-1 bg-success/10 text-success text-[10px] font-black rounded-lg uppercase tracking-widest border border-success/20">Approved</span>
                                @elseif($status === 'pending')
                                    <span class="px-2.5 py-1 bg-accent/10 text-accent text-[10px] font-black rounded-lg uppercase tracking-widest border border-accent/20">Pending</span>
                                @elseif($status === 'rejected')
                                    <span class="px-2.5 py-1 bg-danger/10 text-danger text-[10px] font-black rounded-lg uppercase tracking-widest border border-danger/20">Rejected</span>
                                @else
                                    <span class="px-2.5 py-1 bg-gray-lighter text-gray text-[10px] font-black rounded-lg uppercase tracking-widest">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2 translate-x-2 group-hover:translate-x-0 transition-transform">
                                    <a href="{{ route('admin.astrologers.show', $astro->id) }}" class="w-9 h-9 rounded-xl bg-info/10 text-info hover:bg-info hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="View">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.astrologers.edit', $astro->id) }}" class="w-9 h-9 rounded-xl bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="Edit">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.astrologers.destroy', $astro->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this astrologer?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-9 h-9 rounded-xl bg-danger/10 text-danger hover:bg-danger hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="Delete">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray">No astrologers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-xs font-bold text-gray uppercase tracking-widest">
                Showing {{ $astrologers->firstItem() ?? 0 }} to {{ $astrologers->lastItem() ?? 0 }} of {{ $astrologers->total() }} astrologers
            </div>
            <div>
                {{ $astrologers->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
