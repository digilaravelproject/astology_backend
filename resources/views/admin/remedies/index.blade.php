@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Remedies</h1>
            <p class="text-sm text-gray font-medium">Manage remedy entries and control their visibility.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.remedies.create') }}" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Remedy
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-primary/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Remedies</div>
            <div class="text-3xl font-black text-dark">{{ $total ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-check-circle"></i> {{ $active ?? 0 }} Active
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-success/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Inactive Remedies</div>
            <div class="text-3xl font-black text-dark">{{ $inactive ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-warning font-black text-[9px] uppercase">
                <i class="fas fa-times-circle"></i> Needs Attention
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-info/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">On This Page</div>
            <div class="text-3xl font-black text-dark">{{ $remedies->count() }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-info font-black text-[9px] uppercase">
                <i class="fas fa-list"></i> Current View
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-warning/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Latest Created</div>
            <div class="text-3xl font-black text-dark">{{ $remedies->first()?->created_at?->diffForHumans() ?? '-' }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-warning font-black text-[9px] uppercase">
                <i class="fas fa-clock"></i> Recent
            </div>
        </div>
    </div>

    <!-- Filter Console -->
    <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm mb-8 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Search Remedies</label>
            <form method="GET" action="{{ route('admin.remedies.index') }}" class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray transition-colors group-focus-within:text-dark"></i>
                <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by title or description..." class="w-full bg-light/50 border border-gray-lighter pl-11 pr-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all focus:bg-white focus:shadow-sm">
            </form>
        </div>
        <div class="w-full sm:w-48">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Status</label>
            <form method="GET" action="{{ route('admin.remedies.index') }}">
                <select name="status" onchange="this.form.submit()" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                    <option value="" {{ request('status') === null ? 'selected' : '' }}>All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </form>
        </div>
        <button onclick="location.href='{{ route('admin.remedies.create') }}'" class="bg-dark text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 transform active:scale-95 h-[52px]">New Remedy</button>
    </div>

    <!-- Remedies Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">ID</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Image</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Title</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Description</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Created</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Updated</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($remedies as $remedy)
                        <tr class="hover:bg-light/30 transition-all group">
                            <td class="px-6 py-5 text-sm font-black text-dark">R-{{ $remedy->id }}</td>
                            <td class="px-6 py-5">
                                @if($remedy->image)
                                    <img src="{{ $remedy->image_url }}" alt="{{ $remedy->title }}" class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-lighter flex items-center justify-center">
                                        <i class="fas fa-image text-gray text-xs"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark line-clamp-1">{{ $remedy->title }}</div>
                                <div class="text-[9px] font-bold text-gray mt-1">{{ $remedy->created_at?->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-5 text-sm text-gray">
                                {{ \Illuminate\Support\Str::limit($remedy->description ?? '-', 80) }}
                            </td>
                            <td class="px-6 py-5">
                                @if($remedy->is_active)
                                    <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20">Active</span>
                                @else
                                    <span class="px-3 py-1 bg-gray/10 text-gray text-[9px] font-black uppercase rounded-full border border-gray/20">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-gray">{{ $remedy->created_at?->format('M d, Y H:i') ?? '-' }}</td>
                            <td class="px-6 py-5 text-sm font-bold text-gray">{{ $remedy->updated_at?->format('M d, Y H:i') ?? '-' }}</td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.remedies.edit', $remedy->id) }}" class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-dark hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.remedies.toggle-status', $remedy->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-10 h-10 bg-white border border-gray-lighter text-secondary rounded-xl flex items-center justify-center hover:bg-dark hover:text-white transition-all shadow-sm" title="Toggle status">
                                            @if($remedy->is_active)
                                                <i class="fas fa-user-slash text-xs"></i>
                                            @else
                                                <i class="fas fa-user-check text-xs"></i>
                                            @endif
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.remedies.destroy', $remedy->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this remedy?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-10 h-10 bg-white border border-gray-lighter text-danger rounded-xl flex items-center justify-center hover:bg-danger hover:text-white transition-all shadow-sm">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray">No remedies found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing {{ $remedies->firstItem() ?? 0 }} to {{ $remedies->lastItem() ?? 0 }} of {{ $remedies->total() }} remedies</div>
            <div>
                {{ $remedies->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
