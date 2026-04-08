@extends('admin.layouts.app')

@section('content')
<div class="space-y-8">
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Astrologer Community</h1>
            <p class="text-sm text-gray font-medium">Review followers, likes, blocks, and community reports from the admin panel.</p>
        </div>
        <a href="{{ route('admin.astrologers.index') }}" class="px-4 py-2.5 bg-white border border-gray-lighter rounded-2xl text-sm font-black text-gray hover:bg-light transition-all">Back to Astrologers</a>
    </div>

    @if(session('success'))
        <div class="bg-success/10 border border-success/20 text-success px-6 py-4 rounded-3xl shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Community Records</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['total']) }}</div>
            <div class="mt-3 text-sm text-gray">All follower relationships for astrologers.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Liked Followers</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['liked']) }}</div>
            <div class="mt-3 text-sm text-gray">Members marked as liked by astrologers.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Blocked Members</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['blocked']) }}</div>
            <div class="mt-3 text-sm text-gray">Community members blocked by astrologers.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Reported Members</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['reported']) }}</div>
            <div class="mt-3 text-sm text-gray">Records with a report reason filled.</div>
        </div>
    </div>

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm p-6">
        <form method="GET" action="{{ route('admin.astrologers.community') }}" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Search followers or astrologers</label>
                <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by follower name, email, astrologer name..." class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Astrologer</label>
                <select name="astrologer_id" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                    <option value="">All Astrologers</option>
                    @foreach($astrologers as $astro)
                        <option value="{{ $astro->id }}" {{ request('astrologer_id') == $astro->id ? 'selected' : '' }}>{{ $astro->user?->name ?? 'Astrologer #' . $astro->id }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full bg-dark text-white py-3 rounded-xl font-bold hover:bg-black transition-all">Filter</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/50 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Astrologer</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Follower</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-center">Liked</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-center">Blocked</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Report Reason</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($communityRecords as $record)
                        <tr class="hover:bg-light/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-sm font-black text-dark">{{ $record->astrologer?->user?->name ?? 'Unknown Astrologer' }}</div>
                                <div class="text-[10px] text-gray uppercase tracking-widest">ID #{{ $record->astrologer_id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-black text-dark">{{ $record->user?->name ?? 'Guest' }}</div>
                                <div class="text-[10px] text-gray">{{ $record->user?->email ?? 'No email' }}</div>
                                <div class="text-[10px] text-gray">{{ $record->user?->phone ?? 'No phone' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $record->is_liked ? 'bg-success/10 text-success border border-success/20' : 'bg-gray-lighter text-gray border border-gray-200' }}">
                                    {{ $record->is_liked ? 'Liked' : 'Not liked' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $record->is_blocked ? 'bg-danger/10 text-danger border border-danger/20' : 'bg-success/10 text-success border border-success/20' }}">
                                    {{ $record->is_blocked ? 'Blocked' : 'Active' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray">{{ $record->report_reason ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray">{{ $record->created_at?->format('d M Y') ?? '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <form action="{{ route('admin.astrologers.community.toggle-like', $record->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 rounded-2xl {{ $record->is_liked ? 'bg-gray-lighter text-dark' : 'bg-accent text-white' }} text-[10px] font-black uppercase transition-all hover:opacity-90">{{ $record->is_liked ? 'Unlike' : 'Like' }}</button>
                                    </form>
                                    <form action="{{ route('admin.astrologers.community.toggle-block', $record->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 rounded-2xl {{ $record->is_blocked ? 'bg-success text-white' : 'bg-danger text-white' }} text-[10px] font-black uppercase transition-all hover:opacity-90">{{ $record->is_blocked ? 'Unblock' : 'Block' }}</button>
                                    </form>
                                    <form action="{{ route('admin.astrologers.community.destroy', $record->id) }}" method="POST" onsubmit="return confirm('Remove this community record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-4 py-2 rounded-2xl bg-danger/10 text-danger text-[10px] font-black uppercase transition-all hover:bg-danger/20">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray">No community records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="px-6 py-6 border-t border-gray-lighter bg-light/20 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-xs font-bold text-gray uppercase tracking-widest">Showing {{ $communityRecords->firstItem() ?? 0 }} to {{ $communityRecords->lastItem() ?? 0 }} of {{ $communityRecords->total() }} records</div>
        <div>{{ $communityRecords->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
