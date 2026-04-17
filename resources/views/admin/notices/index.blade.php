@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Notices & Announcements</h1>
            <p class="text-sm text-gray font-medium">Create and manage in-app notices for users and astrologers.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.notices.create') }}" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <i class="fas fa-plus"></i> Create Notice
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Notices</div>
            <div class="text-3xl font-black text-dark">{{ \App\Models\Notice::count() }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Active</div>
            <div class="text-3xl font-black text-success">{{ \App\Models\Notice::where('is_active', true)->count() }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Urgent</div>
            <div class="text-3xl font-black text-danger">{{ \App\Models\Notice::where('is_urgent', true)->count() }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm mb-8">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <input type="text" name="search" placeholder="Search notices..." value="{{ request('search') }}" class="flex-1 px-4 py-3 border border-gray-lighter rounded-xl text-sm focus:outline-none focus:border-primary">
            
            <select name="status" class="px-4 py-3 border border-gray-lighter rounded-xl text-sm focus:outline-none focus:border-primary">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>

            <select name="urgency" class="px-4 py-3 border border-gray-lighter rounded-xl text-sm focus:outline-none focus:border-primary">
                <option value="">All Urgency</option>
                <option value="urgent" {{ request('urgency') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                <option value="normal" {{ request('urgency') === 'normal' ? 'selected' : '' }}>Normal</option>
            </select>
            
            <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all">Search</button>
            <a href="{{ route('admin.notices.index') }}" class="px-6 py-3 bg-light text-dark rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-lighter transition-all">Reset</a>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Title</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Tag</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Urgency</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Created</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($notices as $notice)
                        <tr class="hover:bg-light/30 transition-all">
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">{{ $notice->title }}</div>
                                <div class="text-[10px] text-gray mt-1">{{ Str::limit($notice->body, 60) }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 bg-primary/10 text-primary text-[9px] font-black uppercase rounded-full border border-primary/20">{{ $notice->tag ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-5">
                                @if($notice->is_active)
                                    <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20">Active</span>
                                @else
                                    <span class="px-3 py-1 bg-gray/10 text-gray text-[9px] font-black uppercase rounded-full border border-gray/20">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                @if($notice->is_urgent)
                                    <span class="px-3 py-1 bg-danger/10 text-danger text-[9px] font-black uppercase rounded-full border border-danger/20">Urgent</span>
                                @else
                                    <span class="px-3 py-1 bg-gray/10 text-gray text-[9px] font-black uppercase rounded-full border border-gray/20">Normal</span>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">{{ $notice->created_at->format('M d, Y') }}</div>
                                <div class="text-[10px] text-gray mt-1">{{ $notice->created_at->format('H:i A') }}</div>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.notices.show', $notice->id) }}" class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-primary hover:text-white transition-all shadow-sm" title="View">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.notices.edit', $notice->id) }}" class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-primary hover:text-white transition-all shadow-sm" title="Edit">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.notices.destroy', $notice->id) }}" method="POST" onsubmit="return confirm('Delete this notice?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-10 h-10 bg-white border border-gray-lighter text-danger rounded-xl flex items-center justify-center hover:bg-danger hover:text-white transition-all shadow-sm" title="Delete">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray">No notices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing {{ $notices->firstItem() ?? 0 }} to {{ $notices->lastItem() ?? 0 }} of {{ $notices->total() }} notices</div>
            <div>{{ $notices->withQueryString()->links() }}</div>
        </div>
    </div>
</div>
@endsection
