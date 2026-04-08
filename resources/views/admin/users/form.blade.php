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

<div class="space-y-8">
    <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        <div class="bg-white rounded-3xl shadow-sm border border-gray-lighter overflow-hidden">
            <div class="p-8 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Full Name</label>
                        <input type="text" name="name" value="{{ old('name', optional($user)->name ?? '') }}" required
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('name') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">User Type</label>
                        <select id="user_type" name="user_type" onchange="toggleAstrologerFields()"
                                class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white">
                            <option value="user" {{ old('user_type', optional($user)->user_type ?? 'user') === 'user' ? 'selected' : '' }}>Regular User</option>
                            <option value="astrologer" {{ old('user_type', optional($user)->user_type ?? '') === 'astrologer' ? 'selected' : '' }}>Astrologer</option>
                        </select>
                        @error('user_type') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Email</label>
                        <input type="email" name="email" value="{{ old('email', optional($user)->email ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('email') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', optional($user)->phone ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('phone') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Password</label>
                        <input type="password" name="password" {{ isset($user) ? '' : 'required' }}
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @if(isset($user)) <div class="text-xs text-gray">Leave blank to keep existing password.</div> @endif
                        @error('password') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Profile Photo</label>
                        <input type="file" name="profile_photo" accept="image/*"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @if(isset($user) && $user->profile_photo)
                            <div class="text-xs text-gray mt-2">Current photo: <a href="{{ $user->profile_photo }}" target="_blank" class="text-primary">View</a></div>
                        @endif
                        @error('profile_photo') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">City</label>
                        <input type="text" name="city" value="{{ old('city', optional($user)->city ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('city') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Country</label>
                        <input type="text" name="country" value="{{ old('country', optional($user)->country ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('country') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Gender</label>
                        <select name="gender" class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white">
                            <option value="">-- Select Gender --</option>
                            <option value="male" {{ old('gender', optional($user)->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', optional($user)->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional(optional($user)->date_of_birth)->format('Y-m-d') ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('date_of_birth') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Time of Birth</label>
                        <input type="time" name="time_of_birth" value="{{ old('time_of_birth', optional(optional($user)->time_of_birth)->format('H:i') ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('time_of_birth') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-black text-gray uppercase tracking-widest">Place of Birth</label>
                    <input type="text" name="place_of_birth" value="{{ old('place_of_birth', optional($user)->place_of_birth ?? '') }}"
                           class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                    @error('place_of_birth') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-black text-gray uppercase tracking-widest">Languages</label>
                    <input type="text" name="languages" value="{{ old('languages', is_array(optional($user)->languages ?? null) ? implode(', ', optional($user)->languages) : (optional($user)->languages ?? '')) }}"
                           placeholder="English, Hindi, Sanskrit"
                           class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                    @error('languages') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="px-8 py-5 bg-light/30 border-t border-gray-lighter flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-primary px-6 py-3 text-sm font-black text-white uppercase tracking-[0.15em] hover:bg-primary-dark transition-all">
                    <i class="fas fa-save"></i>
                    {{ isset($user) ? 'Update User' : 'Create User' }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-lighter px-6 py-3 text-sm font-semibold text-gray hover:bg-white transition-all">
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
        document.getElementById('astrologer_fields').style.display = userType === 'astrologer' ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', toggleAstrologerFields);
</script>
@endsection
