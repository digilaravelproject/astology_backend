@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.kundlis.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Kundlis
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">{{ isset($kundli) && $kundli->id ? 'Edit' : 'New' }} Kundli</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Create or edit horoscope records</p>
        </div>
        <div class="flex gap-4">
            <button form="kundliForm" type="submit" class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 flex items-center gap-3">
                <i class="fas fa-save"></i> {{ isset($kundli) && $kundli->id ? 'Update' : 'Create' }} Kundli
            </button>
        </div>
    </div>

    <!-- Form -->
    <form id="kundliForm" action="{{ isset($kundli) && $kundli->id ? route('admin.kundlis.update', $kundli->id) : route('admin.kundlis.store') }}" method="POST" class="max-w-[1000px]">
        @csrf
        @if(isset($kundli) && $kundli->id)
            @method('PUT')
        @endif

        <div class="space-y-10">
            <!-- Section 1: Personal Info -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> 01. Personal Information
                </h2>
                
                <div class="space-y-8">
                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Name</label>
                        <input type="text" name="name" value="{{ old('name', $kundli->name ?? '') }}" placeholder="Full name..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-lg font-black text-dark placeholder:text-gray-light focus:bg-white focus:border-primary/20 focus:ring-0 transition-all" required>
                        @error('name') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Gender</label>
                            <select name="gender" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-black text-dark focus:bg-white focus:border-primary/20 focus:ring-0 transition-all" required>
                                <option value="">Select Gender</option>
                                <option value="male" {{ old('gender', $kundli->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $kundli->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', $kundli->gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Birth Information -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-info/30"></span> 02. Birth Details
                </h2>
                
                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Birth Date</label>
                            <input type="date" name="birth_date" value="{{ old('birth_date', $kundli->birth_date ?? '') }}" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark focus:bg-white focus:border-info/20 focus:ring-0 transition-all" required>
                            @error('birth_date') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Birth Time (24-hour)</label>
                            <input type="time" name="birth_time" value="{{ old('birth_time', $kundli->birth_time ?? '') }}" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark focus:bg-white focus:border-info/20 focus:ring-0 transition-all">
                            @error('birth_time') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Birth Location</label>
                        <input type="text" name="birth_location" value="{{ old('birth_location', $kundli->birth_location ?? '') }}" placeholder="City, Country..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark placeholder:text-gray-light focus:bg-white focus:border-info/20 focus:ring-0 transition-all">
                        @error('birth_location') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Geographical Coordinates -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> 03. Location Coordinates
                </h2>
                
                <div class="space-y-6">
                    <p class="text-[10px] text-gray font-bold mb-6">Enter precise coordinates for accurate astrological calculations</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Latitude</label>
                            <input type="number" name="latitude" step="0.000001" value="{{ old('latitude', $kundli->latitude ?? '') }}" placeholder="e.g. 28.7041" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark placeholder:text-gray-light focus:bg-white focus:border-success/20 focus:ring-0 transition-all">
                            @error('latitude') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Longitude</label>
                            <input type="number" name="longitude" step="0.000001" value="{{ old('longitude', $kundli->longitude ?? '') }}" placeholder="e.g. 77.1025" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark placeholder:text-gray-light focus:bg-white focus:border-success/20 focus:ring-0 transition-all">
                            @error('longitude') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Additional Notes -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-warning uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-warning/30"></span> 04. Additional Information
                </h2>
                
                <div class="space-y-8">
                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Notes</label>
                        <textarea name="notes" rows="6" placeholder="Any additional astrological notes or observations..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-medium text-dark placeholder:text-gray-light focus:bg-white focus:border-warning/20 focus:ring-0 transition-all resize-none">{{ old('notes', $kundli->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
