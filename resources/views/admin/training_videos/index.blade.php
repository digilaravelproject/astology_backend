@extends('admin.layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark">Training Videos</h1>
            <p class="text-sm text-gray font-medium">Manage the video resources shown to astrologers and training users.</p>
        </div>
        <a href="{{ route('admin.training_videos.create') }}" class="px-5 py-3 bg-primary text-white rounded-2xl font-bold hover:bg-primary-dark transition-all">
            <i class="fas fa-plus mr-2"></i> Add Video
        </a>
    </div>

    @if(session('success'))
        <div class="bg-success/10 border border-success/20 text-success px-6 py-4 rounded-3xl shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Videos</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['total']) }}</div>
            <div class="mt-3 text-sm text-gray">Total training videos in the library.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Active Videos</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['active']) }}</div>
            <div class="mt-3 text-sm text-gray">Videos visible to the frontend.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Inactive Videos</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['inactive']) }}</div>
            <div class="mt-3 text-sm text-gray">Draft or hidden videos.</div>
        </div>
    </div>

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('admin.training_videos.index') }}" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-gray uppercase mb-2">Search videos</label>
                <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by title, type or description..." class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-2">Video Type</label>
                <select name="type" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-2">Status</label>
                <select name="status" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="w-full bg-dark text-white px-4 py-3 rounded-2xl font-black uppercase text-[11px] hover:bg-black transition-all">Apply</button>
                <a href="{{ route('admin.training_videos.index') }}" class="w-full bg-white border border-gray-lighter text-dark px-4 py-3 rounded-2xl font-black uppercase text-[11px] hover:bg-light transition-all">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/50 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Title</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Type</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Thumbnail</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Sort Order</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($videos as $video)
                        <tr class="hover:bg-light/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-black text-dark">{{ $video->title }}</div>
                                <div class="text-[11px] text-gray mt-1 line-clamp-2">{{ Str::limit($video->description, 80) }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray">{{ $video->type ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @if($video->thumbnail_url)
                                    <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" class="w-28 h-16 object-cover rounded-2xl border border-gray-lighter">
                                @else
                                    <span class="inline-flex items-center justify-center h-12 px-3 rounded-full bg-gray-lighter text-xs text-gray">No thumbnail</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($video->is_active)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-success/10 text-success text-xs font-black">Active</span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-warning/10 text-warning text-xs font-black">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray">{{ $video->sort_order }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2 flex-wrap">
                                    <a href="{{ route('admin.training_videos.edit', $video->id) }}" class="px-4 py-2 rounded-2xl bg-primary/10 text-primary text-[10px] font-black uppercase hover:bg-primary hover:text-white transition-all">Edit</a>
                                    <form action="{{ route('admin.training_videos.destroy', $video->id) }}" method="POST" onsubmit="return confirm('Delete this video?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-4 py-2 rounded-2xl bg-danger/10 text-danger text-[10px] font-black uppercase hover:bg-danger hover:text-white transition-all">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray">No training videos found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-6 border-t border-gray-lighter bg-light/20 flex justify-between items-center">
            <div class="text-xs font-bold text-gray uppercase tracking-widest">Showing {{ $videos->firstItem() ?? 0 }} to {{ $videos->lastItem() ?? 0 }} of {{ $videos->total() }} videos</div>
            <div>{{ $videos->withQueryString()->links() }}</div>
        </div>
    </div>
</div>
@endsection
