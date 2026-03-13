@extends('admin.layouts.app')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ isset($user) ? 'Edit User' : 'Add New User' }}</h1>
        <p class="text-sm text-gray">{{ isset($user) ? 'Update user details and access.' : 'Fill in the details to create a new user.' }}</p>
    </div>
    <a href="{{ route('admin.users.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-lighter text-gray text-sm font-semibold rounded-lg hover:bg-light transition-all duration-300">
        <i class="fas fa-arrow-left"></i>
        <span>Back to List</span>
    </a>
</div>

<div class="max-w-3xl">
    <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}" method="POST">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 md:p-8 space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div class="space-y-2">
                        <label for="name" class="block text-xs font-semibold text-gray uppercase tracking-wide">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                               value="{{ old('name', $user->name ?? '') }}"
                               required
                               class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                        @error('name') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- User Type -->
                    <div class="space-y-2">
                        <label for="user_type" class="block text-xs font-semibold text-gray uppercase tracking-wide">User Type <span class="text-danger">*</span></label>
                        <select id="user_type" name="user_type" required
                                onchange="toggleAstrologerFields()"
                                class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20 bg-white">
                            <option value="user" {{ old('user_type', $user->user_type ?? 'user') == 'user' ? 'selected' : '' }}>Regular User</option>
                            <option value="astrologer" {{ old('user_type', $user->user_type ?? '') == 'astrologer' ? 'selected' : '' }}>Astrologer</option>
                        </select>
                        @error('user_type') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Email -->
                    <div class="space-y-2">
                        <label for="email" class="block text-xs font-semibold text-gray uppercase tracking-wide">Email Address</label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email', $user->email ?? '') }}"
                               class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                        @error('email') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Phone -->
                    <div class="space-y-2">
                        <label for="phone" class="block text-xs font-semibold text-gray uppercase tracking-wide">Phone Number</label>
                        <input type="text" id="phone" name="phone"
                               value="{{ old('phone', $user->phone ?? '') }}"
                               class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                        @error('phone') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label for="password" class="block text-xs font-semibold text-gray uppercase tracking-wide">
                        Password {{ !isset($user) ? '*' : '' }}
                    </label>
                    <input type="password" id="password" name="password"
                           {{ !isset($user) ? 'required' : '' }}
                           class="w-full px-4 py-1.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                    @if(isset($user))
                        <p class="text-[11px] text-gray italic">Leave blank to keep the current password.</p>
                    @endif
                    @error('password') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>

                <!-- Astrologer Fields (Dynamic) -->
                <div id="astrologer_fields"
                     class="p-5 rounded-xl border border-dashed border-primary/30 bg-primary/5 space-y-4"
                     style="display: {{ old('user_type', $user->user_type ?? 'user') == 'astrologer' ? 'block' : 'none' }};">

                    <div class="flex items-center gap-2 text-primary font-semibold text-sm mb-2">
                        <i class="fas fa-magic"></i>
                        <span>Astrologer Details</span>
                    </div>

                    <div class="space-y-2">
                        <label for="years_of_experience" class="block text-xs font-semibold text-gray-dark">Years of Experience</label>
                        <input type="number" id="years_of_experience" name="years_of_experience"
                               value="{{ old('years_of_experience', $user->astrologer->years_of_experience ?? 0) }}"
                               min="0"
                               class="w-full max-w-[200px] px-4 py-2 border border-gray-lighter rounded-lg text-sm outline-none focus:border-primary">
                        @error('years_of_experience') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    @if(isset($user) && $user->astrologer)
                    <div class="pt-2 flex items-center gap-2">
                        <span class="text-xs font-semibold text-gray-dark">Current Status:</span>
                        @php $status = $user->astrologer->status; @endphp
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                            {{ $status === 'approved' ? 'bg-success/10 text-success' : ($status === 'rejected' ? 'bg-danger/10 text-danger' : 'bg-accent/10 text-accent') }}">
                            {{ $status }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-5 bg-light/30 border-t border-gray-lighter flex items-center gap-4">
                <button type="submit"
                        class="px-8 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary-dark transition-all duration-300 shadow-md hover:shadow-lg flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>{{ isset($user) ? 'Update User' : 'Create User' }}</span>
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="px-6 py-2.5 border border-gray-lighter text-gray text-sm font-semibold rounded-lg hover:bg-light transition-all duration-300">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function toggleAstrologerFields() {
        const userType = document.getElementById('user_type').value;
        const astroFields = document.getElementById('astrologer_fields');
        if (userType === 'astrologer') {
            astroFields.style.display = 'block';
        } else {
            astroFields.style.display = 'none';
        }
    }

    // Run on load to set correct initial state
    document.addEventListener('DOMContentLoaded', function() {
        toggleAstrologerFields();
    });
</script>
@endsection
