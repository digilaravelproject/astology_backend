@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ isset($level) && $level->exists ? 'Edit Level' : 'Create Level' }}</h1>
            <p class="text-sm text-gray font-medium">Define busy minute thresholds and maximum rate increase for astrologers.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.price-increase-levels.index') }}" class="bg-white border border-gray-lighter text-dark px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-chevron-left"></i> Back to list
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-lighter rounded-[32px] shadow-sm p-8">
        <form action="{{ isset($level) && $level->exists ? route('admin.price-increase-levels.update', $level->id) : route('admin.price-increase-levels.store') }}" method="POST">
            @csrf
            @if(isset($level) && $level->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Level Name</label>
                        <input name="name" value="{{ old('name', $level->name ?? '') }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="e.g. Beginner, Intermediate, Pro">
                        @error('name')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Level Number</label>
                        <input name="level_number" value="{{ old('level_number', $level->level_number ?? '') }}" type="number" min="1" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="e.g. 1, 2, 3">
                        @error('level_number')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        <div class="text-gray text-xs mt-2">Determines unlock order. Must be unique.</div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Required Busy Minutes</label>
                        <input name="required_busy_minutes" value="{{ old('required_busy_minutes', $level->required_busy_minutes ?? '') }}" type="number" min="0" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="e.g. 1000">
                        @error('required_busy_minutes')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        <div class="text-gray text-xs mt-2">Total completed call + chat duration in minutes.</div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Max Increase Amount ($)</label>
                        <input name="max_increase_amount" value="{{ old('max_increase_amount', $level->max_increase_amount ?? '') }}" type="number" step="0.01" min="0" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="e.g. 5.00">
                        @error('max_increase_amount')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        <div class="text-gray text-xs mt-2">Maximum rate increase allowed at this level.</div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $level->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-lighter rounded focus:ring-primary">
                        <label for="is_active" class="text-sm font-bold text-gray">Active</label>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-light/50 p-6 rounded-3xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Quick Info</div>
                        <div class="text-sm font-bold text-dark">ID</div>
                        <div class="text-xs text-gray">{{ isset($level) && $level->exists ? $level->id : 'Will be assigned on save' }}</div>
                        @if(isset($level) && $level->exists)
                            <div class="mt-4 text-sm font-bold text-dark">Created</div>
                            <div class="text-xs text-gray">{{ $level->created_at?->format('M d, Y H:i') }}</div>
                            <div class="mt-4 text-sm font-bold text-dark">Updated</div>
                            <div class="text-xs text-gray">{{ $level->updated_at?->format('M d, Y H:i') }}</div>
                            <div class="mt-4 text-sm font-bold text-dark">Requests</div>
                            <div class="text-xs text-gray">{{ $level->requests()->count() }} total</div>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="bg-primary text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20">
                            {{ isset($level) && $level->exists ? 'Update Level' : 'Create Level' }}
                        </button>
                        <a href="{{ route('admin.price-increase-levels.index') }}" class="bg-white border border-gray-lighter text-dark px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
