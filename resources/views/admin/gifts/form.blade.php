@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ $gift->exists ? 'Edit Gift' : 'Create Gift' }}</h1>
            <p class="text-sm text-gray font-medium">{{ $gift->exists ? 'Update gift details.' : 'Create a new gift item for users to send to astrologers.' }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.gifts.index') }}" class="bg-white border border-gray-lighter text-dark px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-chevron-left"></i> Back to gifts
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-lighter rounded-[32px] shadow-sm p-8">
        <form action="{{ $gift->exists ? route('admin.gifts.update', $gift->id) : route('admin.gifts.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($gift->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Title</label>
                        <input name="title" value="{{ old('title', $gift->title) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="Enter gift title">
                        @error('title')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Price (₹)</label>
                        <input name="price" value="{{ old('price', $gift->price) }}" type="number" step="0.01" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="Enter gift price">
                        @error('price')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Gift Icon</label>
                        <input id="icon_file" name="icon_file" type="file" accept="image/*" class="w-full text-xs text-gray bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 focus:outline-none focus:border-dark" onchange="previewGiftIcon(event)">
                        @error('icon_file')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="flex items-center gap-4">
                        <div id="iconPreview" class="w-24 h-24 rounded-3xl bg-light border border-gray-lighter overflow-hidden flex items-center justify-center">
                            @if(old('icon_url', $gift->icon_url))
                                <img id="currentIconPreview" src="{{ old('icon_url', $gift->icon_url) }}" alt="Gift Icon Preview" class="w-full h-full object-contain">
                            @else
                                <span class="text-xs text-gray">Preview</span>
                            @endif
                        </div>
                        @if($gift->exists && $gift->icon_url)
                            <div class="text-xs text-gray">Current icon shown here. Upload a new file to replace it.</div>
                        @endif
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Description</label>
                        <textarea name="description" rows="5" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="Optional gift description">{{ old('description', $gift->description) }}</textarea>
                        @error('description')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Description</label>
                        <textarea name="description" rows="5" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="Optional gift description">{{ old('description', $gift->description) }}</textarea>
                        @error('description')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $gift->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-lighter rounded focus:ring-primary">
                        <label for="is_active" class="text-sm font-bold text-gray">Active</label>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-light/50 p-6 rounded-3xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Quick Info</div>
                        <div class="text-sm font-bold text-dark">ID</div>
                        <div class="text-xs text-gray">{{ $gift->exists ? $gift->id : 'Will be assigned on save' }}</div>
                        <div class="mt-4 text-sm font-bold text-dark">Created</div>
                        <div class="text-xs text-gray">{{ $gift->exists ? $gift->created_at?->format('M d, Y H:i') : '-' }}</div>
                        <div class="mt-4 text-sm font-bold text-dark">Updated</div>
                        <div class="text-xs text-gray">{{ $gift->exists ? $gift->updated_at?->format('M d, Y H:i') : '-' }}</div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Sort Order</label>
                        <input name="sort_order" value="{{ old('sort_order', $gift->sort_order ?? 0) }}" type="number" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="0">
                        @error('sort_order')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="bg-primary text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20">{{ $gift->exists ? 'Update Gift' : 'Create Gift' }}</button>
                        <a href="{{ route('admin.gifts.index') }}" class="bg-white border border-gray-lighter text-dark px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function previewGiftIcon(event) {
        const input = event.target;
        const preview = document.getElementById('iconPreview');
        const currentImage = document.getElementById('currentIconPreview');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                if (currentImage) {
                    currentImage.src = e.target.result;
                } else {
                    const img = document.createElement('img');
                    img.id = 'currentIconPreview';
                    img.src = e.target.result;
                    img.alt = 'Gift Icon Preview';
                    img.className = 'w-full h-full object-contain';
                    preview.innerHTML = '';
                    preview.appendChild(img);
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
