@extends('admin.layouts.app')

@section('content')
<div x-data="{ openModal: null }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Gallery - {{ $astrologer->user->name }}</h1>
            <p class="text-sm text-gray font-medium">{{ $astrologer->user->email }}</p>
            <div class="text-xs text-gray mt-1">Manage gallery images for this astrologer</div>
        </div>
        <a href="{{ route('admin.astrologers.gallery.index') }}" class="bg-light text-dark px-5 py-2.5 rounded-xl font-bold hover:bg-gray-lighter transition-all flex items-center gap-2 w-fit">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-lighter">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-1">Total Images</div>
                    <h3 class="text-3xl font-black text-primary">{{ $galleries->total() }}</h3>
                </div>
                <i class="fas fa-images text-4xl text-primary/20"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-lighter">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-1">Active</div>
                    <h3 class="text-3xl font-black text-success">
                        @php
                            $activeCount = 0;
                            foreach($galleries as $g) {
                                if($g->status === 'active') $activeCount++;
                            }
                            echo $activeCount;
                        @endphp
                    </h3>
                </div>
                <i class="fas fa-check-circle text-4xl text-success/20"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-lighter">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-1">Pending</div>
                    <h3 class="text-3xl font-black text-accent">
                        @php
                            $pendingCount = 0;
                            foreach($galleries as $g) {
                                if($g->status === 'pending') $pendingCount++;
                            }
                            echo $pendingCount;
                        @endphp
                    </h3>
                </div>
                <i class="fas fa-clock text-4xl text-accent/20"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-lighter">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-1">Visible</div>
                    <h3 class="text-3xl font-black text-info">
                        @php
                            $visibleCount = 0;
                            foreach($galleries as $g) {
                                if($g->is_visible) $visibleCount++;
                            }
                            echo $visibleCount;
                        @endphp
                    </h3>
                </div>
                <i class="fas fa-eye text-4xl text-info/20"></i>
            </div>
        </div>
    </div>

    <!-- Gallery Table -->
    @if($galleries->count())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-light/50 border-b border-gray-lighter">
                        <tr>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Image</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Visibility</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Remarks</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Uploaded</th>
                            <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-lighter">
                        @forelse($galleries as $gallery)
                            <tr class="hover:bg-light/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden border border-gray-lighter">
                                        <img src="{{ Storage::url($gallery->image_path) }}" alt="Gallery" class="w-full h-full object-cover hover:scale-110 transition-transform cursor-pointer" onclick="window.open('{{ Storage::url($gallery->image_path) }}', '_blank')">
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($gallery->status === 'active')
                                        <span class="px-2.5 py-1 bg-success/10 text-success text-[10px] font-black rounded-lg uppercase tracking-widest border border-success/20">Active</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-accent/10 text-accent text-[10px] font-black rounded-lg uppercase tracking-widest border border-accent/20">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($gallery->is_visible)
                                        <span class="px-2.5 py-1 bg-info/10 text-info text-[10px] font-black rounded-lg uppercase tracking-widest border border-info/20">Visible</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-gray-lighter text-gray text-[10px] font-black rounded-lg uppercase tracking-widest">Hidden</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($gallery->remarks)
                                        <div class="text-[10px] text-danger italic max-w-[150px]">{{ Str::limit($gallery->remarks, 50) }}</div>
                                    @else
                                        <div class="text-[10px] text-gray-light">-</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-gray">{{ $gallery->created_at->format('d M Y H:i') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2 translate-x-2 group-hover:translate-x-0 transition-transform">
                                        @if($gallery->status === 'pending')
                                            <form action="{{ route('admin.astrologers.gallery.approve', $gallery->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="w-9 h-9 rounded-xl bg-success/10 text-success hover:bg-success hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="Approve">
                                                    <i class="fas fa-check text-xs"></i>
                                                </button>
                                            </form>
                                            <button type="button" @click="openModal = {{ $gallery->id }}" class="w-9 h-9 rounded-xl bg-accent/10 text-accent hover:bg-accent hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="Disapprove">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        @endif
                                        <a href="{{ Storage::url($gallery->image_path) }}" target="_blank" class="w-9 h-9 rounded-xl bg-info/10 text-info hover:bg-info hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="View">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        <form action="{{ route('admin.astrologers.gallery.destroy', $gallery->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this image?');">
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
                                <td colspan="6" class="px-6 py-10 text-center text-gray">No gallery images found for this astrologer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
                <div class="text-xs font-bold text-gray uppercase tracking-widest">
                    Showing {{ $galleries->firstItem() ?? 0 }} to {{ $galleries->lastItem() ?? 0 }} of {{ $galleries->total() }} images
                </div>
                <div>
                    {{ $galleries->links() }}
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter p-8 text-center">
            <i class="fas fa-image text-4xl text-gray-light mb-4"></i>
            <p class="text-gray font-medium">No gallery images found for this astrologer</p>
        </div>
    @endif

    <!-- Disapprove Modals (Hidden by default) -->
    @foreach($galleries as $gallery)
        @if($gallery->status === 'pending')
            <div x-show="openModal === {{ $gallery->id }}" @click.self="openModal = null" 
                 class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" 
                 style="display: none;" x-transition>
                <div class="bg-white rounded-2xl shadow-lg border border-gray-lighter max-w-md w-full mx-4">
                    <div class="border-b border-gray-lighter bg-light/50 p-6">
                        <h3 class="font-black text-dark text-lg">Disapprove Image</h3>
                    </div>
                    <form action="{{ route('admin.astrologers.gallery.disapprove', $gallery->id) }}" method="POST">
                        @csrf
                        <div class="p-6">
                            <label class="block text-[11px] font-black text-gray uppercase mb-2">Remarks</label>
                            <textarea name="remarks" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm" rows="3" required placeholder="Provide reason for disapproving..."></textarea>
                        </div>
                        <div class="border-t border-gray-lighter bg-light/50 p-4 flex justify-end gap-3">
                            <button type="button" @click="openModal = null" class="px-4 py-2 bg-light text-dark rounded-xl font-bold hover:bg-gray-lighter transition-all">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-accent text-white rounded-xl font-bold hover:bg-accent-dark transition-all">
                                Disapprove
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection
