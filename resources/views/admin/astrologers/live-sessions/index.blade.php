@extends('admin.layouts.app')

@section('content')
<div x-data="{ openModal: null }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Live Sessions Management</h1>
            <p class="text-sm text-gray font-medium">Manage and monitor all astrologer live sessions.</p>
            <div class="text-xs text-gray mt-1">View session details, update status, and manage schedules</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.astrologers.live-sessions.index') }}" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-lighter mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Search</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-light"></i>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by session title or astrologer..." class="w-full pl-11 pr-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Astrologer</label>
                <select name="astrologer_id" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm appearance-none">
                    <option value="">All Astrologers</option>
                    @foreach($astrologers as $astrologer)
                        <option value="{{ $astrologer->id }}" {{ request('astrologer_id') == $astrologer->id ? 'selected' : '' }}>
                            {{ $astrologer->user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Status</label>
                <select name="status" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm appearance-none">
                    <option value="">All Status</option>
                    <option value="upcoming" {{ request('status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button type="submit" class="flex-1 bg-dark text-white py-3 rounded-xl font-bold hover:bg-black transition-all">Apply Filters</button>
            <a href="{{ route('admin.astrologers.live-sessions.index') }}" class="flex-1 bg-light text-dark py-3 rounded-xl font-bold hover:bg-gray-lighter transition-all text-center">Clear Filters</a>
        </div>
    </form>

    <!-- Live Sessions Table -->
    @if($liveSessions->count())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-light/50 border-b border-gray-lighter">
                        <tr>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Title</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Astrologer</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Scheduled</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-lighter">
                        @forelse($liveSessions as $session)
                            <tr class="hover:bg-light/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-black text-dark group-hover:text-primary transition-colors">{{ Str::limit($session->title, 40) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-dark">{{ $session->astrologer->user->name }}</div>
                                    <div class="text-[10px] text-gray-light">{{ $session->astrologer->user->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($session->session_type === 'public')
                                        <span class="px-2.5 py-1 bg-info/10 text-info text-[10px] font-black rounded-lg uppercase tracking-widest border border-info/20">Public</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-accent/10 text-accent text-[10px] font-black rounded-lg uppercase tracking-widest border border-accent/20">Private</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($session->status === 'upcoming')
                                        <span class="px-2.5 py-1 bg-primary/10 text-primary text-[10px] font-black rounded-lg uppercase tracking-widest border border-primary/20">Upcoming</span>
                                    @elseif($session->status === 'ongoing')
                                        <span class="px-2.5 py-1 bg-success/10 text-success text-[10px] font-black rounded-lg uppercase tracking-widest border border-success/20">Ongoing</span>
                                    @elseif($session->status === 'completed')
                                        <span class="px-2.5 py-1 bg-accent/10 text-accent text-[10px] font-black rounded-lg uppercase tracking-widest border border-accent/20">Completed</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-danger/10 text-danger text-[10px] font-black rounded-lg uppercase tracking-widest border border-danger/20">Cancelled</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-gray">{{ $session->scheduled_at->format('d M Y H:i') }}</td>
                                <td class="px-6 py-4 text-xs font-semibold text-gray">{{ $session->duration_minutes }} min</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2 translate-x-2 group-hover:translate-x-0 transition-transform">
                                        <a href="{{ route('admin.astrologers.live-sessions.show', $session->id) }}" class="w-9 h-9 rounded-xl bg-info/10 text-info hover:bg-info hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="View Details">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        <form action="{{ route('admin.astrologers.live-sessions.destroy', $session->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this session?');">
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
                                <td colspan="7" class="px-6 py-10 text-center text-gray">No live sessions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
                <div class="text-xs font-bold text-gray uppercase tracking-widest">
                    Showing {{ $liveSessions->firstItem() ?? 0 }} to {{ $liveSessions->lastItem() ?? 0 }} of {{ $liveSessions->total() }} sessions
                </div>
                <div>
                    {{ $liveSessions->withQueryString()->links() }}
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter p-8 text-center">
            <i class="fas fa-video text-4xl text-gray-light mb-4"></i>
            <p class="text-gray font-medium">No live sessions found</p>
        </div>
    @endif
</div>
@endsection
