@extends('admin.layouts.app')

@section('content')
<div class="space-y-8">
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Founder Words</h1>
            <p class="text-sm text-gray font-medium">Manage founder messages and quotes that appear on the platform.</p>
        </div>
        <a href="{{ route('admin.founder_words.create') }}" class="px-4 py-2.5 bg-primary text-white rounded-2xl text-sm font-black uppercase hover:bg-primary-dark transition-all">+ Add Founder Word</a>
    </div>

    @if(session('success'))
        <div class="bg-success/10 border border-success/20 text-success px-6 py-4 rounded-3xl shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Words</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['total']) }}</div>
            <div class="mt-3 text-sm text-gray">All founder word entries in the system.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Active</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['active']) }}</div>
            <div class="mt-3 text-sm text-gray">Published and visible on frontend.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Inactive</div>
            <div class="text-4xl font-black text-dark">{{ number_format($stats['inactive']) }}</div>
            <div class="mt-3 text-sm text-gray">Hidden entries not visible to users.</div>
        </div>
    </div>

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm p-6">
        <form method="GET" action="{{ route('admin.founder_words.index') }}" class="flex flex-col lg:flex-row gap-4 items-end">
            <div class="flex-1">
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Search words</label>
                <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by title or message..." class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
            </div>
            <div class="flex-0 w-full lg:w-auto">
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Status</label>
                <select name="status" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex gap-2 w-full lg:w-auto">
                <button type="submit" class="flex-1 lg:flex-0 bg-dark text-white py-3 px-6 rounded-xl font-bold hover:bg-black transition-all">Filter</button>
                <a href="{{ route('admin.founder_words.index') }}" class="flex-1 lg:flex-0 bg-gray-lighter text-dark py-3 px-6 rounded-xl font-bold hover:bg-gray-200 transition-all">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/50 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Image</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Title</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Message Preview</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Created</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($words as $word)
                        <tr class="hover:bg-light/30 transition-colors">
                            <td class="px-6 py-4">
                                @if($word->image)
                                    <img src="{{ $word->image_url }}" alt="{{ $word->title }}" class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-lighter flex items-center justify-center">
                                        <i class="fas fa-image text-gray text-xs"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-black text-dark">{{ $word->title }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray">{{ Str::limit($word->message, 80) }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $word->is_active ? 'bg-success/10 text-success border border-success/20' : 'bg-gray-lighter text-gray border border-gray-200' }}">
                                    {{ $word->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray">{{ $word->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('admin.founder_words.edit', $word->id) }}" class="px-4 py-2 rounded-2xl bg-primary text-white text-[10px] font-black uppercase transition-all hover:opacity-90">Edit</a>
                                    <form action="{{ route('admin.founder_words.destroy', $word->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this founder word?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-4 py-2 rounded-2xl bg-danger/10 text-danger text-[10px] font-black uppercase transition-all hover:bg-danger/20">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray">No founder words found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="px-6 py-6 border-t border-gray-lighter bg-light/20 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-xs font-bold text-gray uppercase tracking-widest">Showing {{ $words->firstItem() ?? 0 }} to {{ $words->lastItem() ?? 0 }} of {{ $words->total() }} words</div>
        <div>{{ $words->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
