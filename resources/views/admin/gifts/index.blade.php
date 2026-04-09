@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Manage Gifts</h1>
            <p class="text-sm text-gray font-medium">Create and manage gift items that users can send to astrologers.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.gifts.create') }}" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <i class="fas fa-gift"></i> Create Gift
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Gifts</div>
            <div class="text-3xl font-black text-dark">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Active Gifts</div>
            <div class="text-3xl font-black text-dark">{{ $stats['active'] }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Inactive Gifts</div>
            <div class="text-3xl font-black text-dark">{{ $stats['inactive'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Gift</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Price</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($gifts as $gift)
                        <tr class="hover:bg-light/30 transition-all">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-3xl bg-light border border-gray-lighter flex items-center justify-center text-2xl">
                                        {!! $gift->icon_url ? '<img src="' . e($gift->icon_url) . '" alt="icon" class="w-10 h-10 object-contain">' : '<i class="fas fa-gift text-primary"></i>' !!}
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-dark">{{ $gift->title }}</div>
                                        <div class="text-[10px] text-gray mt-1">{{ Str::limit($gift->description, 80) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">₹{{ number_format($gift->price, 2) }}</div>
                            </td>
                            <td class="px-6 py-5">
                                @if($gift->is_active)
                                    <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20">Active</span>
                                @else
                                    <span class="px-3 py-1 bg-gray/10 text-gray text-[9px] font-black uppercase rounded-full border border-gray/20">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.gifts.edit', $gift->id) }}" class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-dark hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.gifts.destroy', $gift->id) }}" method="POST" onsubmit="return confirm('Delete this gift?');">
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
                            <td colspan="4" class="px-6 py-10 text-center text-gray">No gifts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing {{ $gifts->firstItem() ?? 0 }} to {{ $gifts->lastItem() ?? 0 }} of {{ $gifts->total() }} gifts</div>
            <div>{{ $gifts->withQueryString()->links() }}</div>
        </div>
    </div>
</div>
@endsection
