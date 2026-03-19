@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ $remedy->exists ? 'Edit Remedy' : 'Create Remedy' }}</h1>
            <p class="text-sm text-gray font-medium">Use this form to add or update remedies available to users.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.remedies.index') }}" class="bg-white border border-gray-lighter text-dark px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-chevron-left"></i> Back to list
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-lighter rounded-[32px] shadow-sm p-8">
        <form action="{{ $remedy->exists ? route('admin.remedies.update', $remedy->id) : route('admin.remedies.store') }}" method="POST">
            @csrf
            @if($remedy->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Title</label>
                        <input name="title" value="{{ old('title', $remedy->title) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="Enter remedy title">
                        @error('title')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Description</label>
                        <textarea name="description" rows="6" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="Describe the remedy...">{{ old('description', $remedy->description) }}</textarea>
                        @error('description')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $remedy->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-lighter rounded focus:ring-primary">
                        <label for="is_active" class="text-sm font-bold text-gray">Active</label>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-light/50 p-6 rounded-3xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Quick Info</div>
                        <div class="text-sm font-bold text-dark">ID</div>
                        <div class="text-xs text-gray">{{ $remedy->exists ? $remedy->id : 'Will be assigned on save' }}</div>
                        <div class="mt-4 text-sm font-bold text-dark">Created</div>
                        <div class="text-xs text-gray">{{ $remedy->exists ? $remedy->created_at?->format('M d, Y H:i') : '-' }}</div>
                        <div class="mt-4 text-sm font-bold text-dark">Updated</div>
                        <div class="text-xs text-gray">{{ $remedy->exists ? $remedy->updated_at?->format('M d, Y H:i') : '-' }}</div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="bg-primary text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20">{{ $remedy->exists ? 'Update Remedy' : 'Create Remedy' }}</button>
                        <a href="{{ route('admin.remedies.index') }}" class="bg-white border border-gray-lighter text-dark px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
