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
    <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}" method="POST">
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
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Profile Photo URL</label>
                        <input type="url" name="profile_photo" value="{{ old('profile_photo', optional($user)->profile_photo ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
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
                        <input type="text" name="gender" value="{{ old('gender', optional($user)->gender ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Email Verified At</label>
                        <input type="datetime-local" name="email_verified_at" value="{{ old('email_verified_at', optional(optional($user)->email_verified_at)->format('Y-m-d\TH:i') ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('email_verified_at') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Profile Completed</label>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="profile_completed" value="1" {{ old('profile_completed', optional($user)->profile_completed ?? false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray text-primary focus:ring-primary" />
                            <span class="text-sm text-gray">Profile complete</span>
                        </div>
                        @error('profile_completed') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Plan</label>
                        <select name="plan_id" class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white">
                            <option value="">None</option>
                            @foreach($plans as $planId => $planName)
                                <option value="{{ $planId }}" {{ old('plan_id', optional($user)->plan_id ?? '') == $planId ? 'selected' : '' }}>{{ $planName }}</option>
                            @endforeach
                        </select>
                        @error('plan_id') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Plan Started At</label>
                        <input type="datetime-local" name="plan_started_at" value="{{ old('plan_started_at', optional(optional($user)->plan_started_at)->format('Y-m-d\TH:i') ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('plan_started_at') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Plan Expires At</label>
                        <input type="datetime-local" name="plan_expires_at" value="{{ old('plan_expires_at', optional(optional($user)->plan_expires_at)->format('Y-m-d\TH:i') ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('plan_expires_at') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Online Status</label>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="is_online" name="is_online" value="1" {{ old('is_online', optional($user)->is_online ?? false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray text-primary focus:ring-primary" />
                            <label for="is_online" class="text-sm text-gray">Is online</label>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Busy Status</label>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="is_busy" name="is_busy" value="1" {{ old('is_busy', optional($user)->is_busy ?? false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray text-primary focus:ring-primary" />
                            <label for="is_busy" class="text-sm text-gray">Is busy</label>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Last Seen At</label>
                        <input type="datetime-local" name="last_seen_at" value="{{ old('last_seen_at', optional(optional($user)->last_seen_at)->format('Y-m-d\TH:i') ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('last_seen_at') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">Busy Session ID</label>
                        <input type="number" id="busy_session_id" name="busy_session_id" value="{{ old('busy_session_id', optional($user)->busy_session_id ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('busy_session_id') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-black text-gray uppercase tracking-widest">FCM Token</label>
                    <textarea name="fcm_token" rows="2" class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white">{{ old('fcm_token', optional($user)->fcm_token ?? '') }}</textarea>
                    @error('fcm_token') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">OTP</label>
                        <input type="text" id="otp" name="otp" value="{{ old('otp', optional($user)->otp ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('otp') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-gray uppercase tracking-widest">OTP Expires At</label>
                        <input type="datetime-local" id="otp_expires_at" name="otp_expires_at" value="{{ old('otp_expires_at', optional(optional($user)->otp_expires_at)->format('Y-m-d\TH:i') ?? '') }}"
                               class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                        @error('otp_expires_at') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-black text-gray uppercase tracking-widest">OTP Verified At</label>
                    <input type="datetime-local" id="otp_verified_at" name="otp_verified_at" value="{{ old('otp_verified_at', optional(optional($user)->otp_verified_at)->format('Y-m-d\TH:i') ?? '') }}"
                           class="w-full rounded-2xl border border-gray-lighter bg-light/50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white" />
                    @error('otp_verified_at') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                </div>

                <div id="astrologer_fields" class="p-5 rounded-3xl border border-dashed border-primary/30 bg-primary/5 space-y-4" style="display: {{ old('user_type', optional($user)->user_type ?? 'user') === 'astrologer' ? 'block' : 'none' }};">
                    <div class="flex items-center gap-2 text-primary font-black uppercase tracking-widest text-xs">
                        <i class="fas fa-magic"></i>
                        <span>Astrologer Profile</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <label class="block text-xs font-black text-gray uppercase tracking-widest">Years of Experience</label>
                            <input type="number" name="years_of_experience" min="0" value="{{ old('years_of_experience', optional(optional($user)->astrologer)->years_of_experience ?? 0) }}"
                                   class="w-full rounded-2xl border border-gray-lighter bg-white px-4 py-3 text-sm outline-none focus:border-primary" />
                            @error('years_of_experience') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                        </div>
                        @if(isset($user) && $user->astrologer)
                        <div class="space-y-3">
                            <label class="block text-xs font-black text-gray uppercase tracking-widest">Astrologer Status</label>
                            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-lighter text-sm font-black {{ $user->astrologer->status === 'approved' ? 'text-success' : ($user->astrologer->status === 'rejected' ? 'text-danger' : 'text-accent') }}">
                                {{ ucfirst($user->astrologer->status) }}
                            </div>
                        </div>
                        @endif
                    </div>
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
