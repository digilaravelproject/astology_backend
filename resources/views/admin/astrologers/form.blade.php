@extends('admin.layouts.app')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ isset($user) ? 'Edit Astrologer' : 'Add New Astrologer' }}</h1>
        <p class="text-sm text-gray">{{ isset($user) ? 'Update astrologer details and profile.' : 'Fill in the details to create a new astrologer.' }}</p>
    </div>
    <a href="{{ route('admin.astrologers.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-lighter text-gray text-sm font-semibold rounded-lg hover:bg-light transition-all duration-300">
        <i class="fas fa-arrow-left"></i>
        <span>Back to List</span>
    </a>
</div>

<div class="max-w-3xl">
    <form action="{{ isset($user) && $user?->id ? route('admin.astrologers.update', $user->id) : route('admin.astrologers.store') }}" method="POST">
        @csrf
        @if(isset($user) && $user?->id)
            @method('PUT')
        @endif

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 md:p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div class="space-y-2">
                        <label for="name" class="block text-xs font-semibold text-gray uppercase tracking-wide">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                               value="{{ old('name', optional($user)->name ?? '') }}"
                               required
                               class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                        @error('name') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Email -->
                    <div class="space-y-2">
                        <label for="email" class="block text-xs font-semibold text-gray uppercase tracking-wide">Email Address</label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email', optional($user)->email ?? '') }}"
                               class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                        @error('email') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Phone -->
                    <div class="space-y-2">
                        <label for="phone" class="block text-xs font-semibold text-gray uppercase tracking-wide">Phone Number</label>
                        <input type="text" id="phone" name="phone"
                               value="{{ old('phone', optional($user)->phone ?? '') }}"
                               class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                        @error('phone') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Password -->
                    <div class="space-y-2">
                        <label for="password" class="block text-xs font-semibold text-gray uppercase tracking-wide">
                            Password {{ !isset($user) ? '*' : '' }}
                        </label>
                        <input type="password" id="password" name="password"
                               {{ !isset($user) ? 'required' : '' }}
                               class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                        @if(isset($user))
                            <p class="text-[11px] text-gray italic">Leave blank to keep the current password.</p>
                        @endif
                        @error('password') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-5 rounded-xl border border-dashed border-primary/30 bg-primary/5 space-y-6">
                    <div class="flex items-center gap-2 text-primary font-semibold text-sm">
                        <i class="fas fa-magic"></i>
                        <span>Astrologer Details</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="years_of_experience" class="block text-xs font-semibold text-gray-dark">Years of Experience</label>
                            <input type="number" id="years_of_experience" name="years_of_experience"
                                   value="{{ old('years_of_experience', $user->astrologer->years_of_experience ?? '') }}"
                                   min="0"
                                   class="w-full px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                            @error('years_of_experience') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="status" class="block text-xs font-semibold text-gray-dark">Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" required
                                    class="w-full px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                                @php $statusValue = old('status', $user->astrologer->status ?? 'pending'); @endphp
                                <option value="pending" {{ $statusValue === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ $statusValue === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ $statusValue === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                            @error('status') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="areas_of_expertise" class="block text-xs font-semibold text-gray-dark">Areas of Expertise</label>
                            <input type="text" id="areas_of_expertise" name="areas_of_expertise"
                                   value="{{ old('areas_of_expertise', optional($user->astrologer) ? implode(', ', (array) $user->astrologer->areas_of_expertise) : '') }}"
                                   placeholder="e.g. Vedic Astrology, Tarot, Vastu"
                                   class="w-full px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                            <p class="text-[11px] text-gray">Comma separated (stored as array).</p>
                            @error('areas_of_expertise') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="languages" class="block text-xs font-semibold text-gray-dark">Languages</label>
                            <input type="text" id="languages" name="languages"
                                   value="{{ old('languages', optional($user->astrologer) ? implode(', ', (array) $user->astrologer->languages) : '') }}"
                                   placeholder="e.g. English, Hindi"
                                   class="w-full px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                            <p class="text-[11px] text-gray">Comma separated (stored as array).</p>
                            @error('languages') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="bio" class="block text-xs font-semibold text-gray-dark">Short Bio / Description</label>
                        <textarea id="bio" name="bio" rows="4" class="w-full px-4 py-3 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">{{ old('bio', optional($user->astrologer)->bio ?? '') }}</textarea>
                        @error('bio') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="id_proof_number" class="block text-xs font-semibold text-gray-dark">ID Proof Number</label>
                            <input type="text" id="id_proof_number" name="id_proof_number"
                                   value="{{ old('id_proof_number', optional($user->astrologer)->id_proof_number ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                            @error('id_proof_number') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="date_of_birth" class="block text-xs font-semibold text-gray-dark">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth"
                                   value="{{ old('date_of_birth', optional(optional($user->astrologer)->date_of_birth)->format('Y-m-d') ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                            @error('date_of_birth') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="profile_photo" class="block text-xs font-semibold text-gray-dark">Profile Photo URL</label>
                            <input type="text" id="profile_photo" name="profile_photo"
                                   value="{{ old('profile_photo', optional($user->astrologer)->profile_photo ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                            @error('profile_photo') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="id_proof" class="block text-xs font-semibold text-gray-dark">ID Proof URL</label>
                            <input type="text" id="id_proof" name="id_proof"
                                   value="{{ old('id_proof', optional($user->astrologer)->id_proof ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                            @error('id_proof') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="certificate" class="block text-xs font-semibold text-gray-dark">Certificate URL</label>
                        <input type="text" id="certificate" name="certificate"
                               value="{{ old('certificate', optional($user->astrologer)->certificate ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                        @error('certificate') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-5 bg-light/30 border-t border-gray-lighter flex items-center gap-4">
                <button type="submit"
                        class="px-8 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary-dark transition-all duration-300 shadow-md hover:shadow-lg flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>{{ isset($user) ? 'Update Astrologer' : 'Create Astrologer' }}</span>
                </button>
                <a href="{{ route('admin.astrologers.index') }}"
                   class="px-6 py-2.5 border border-gray-lighter text-gray text-sm font-semibold rounded-lg hover:bg-light transition-all duration-300">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
