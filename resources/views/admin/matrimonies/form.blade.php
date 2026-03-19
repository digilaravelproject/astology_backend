@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">
                {{ $profile->exists ? 'Edit Matrimony Profile' : 'Create Matrimony Profile' }}
            </h1>
            <p class="text-sm text-gray font-medium">Manage the profile details and visibility settings.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.matrimonies.index') }}" class="bg-white border border-gray-lighter text-dark px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-chevron-left"></i> Back to list
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-lighter rounded-[32px] shadow-sm p-8">
        <form id="matrimonyForm" action="{{ $profile->exists ? route('admin.matrimonies.update', $profile->id) : route('admin.matrimonies.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($profile->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Profile Owner</label>
                        <select name="user_id" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                            <option value="">Select user</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id', $profile->user_id) == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email ?? 'no email' }})</option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Created For</label>
                        <input name="created_for" value="{{ old('created_for', $profile->created_for) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="e.g. Myself, My Son, My Daughter">
                        @error('created_for')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">First Name</label>
                            <input name="first_name" value="{{ old('first_name', $profile->first_name) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                            @error('first_name')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Last Name</label>
                            <input name="last_name" value="{{ old('last_name', $profile->last_name) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                            @error('last_name')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Email</label>
                            <input name="email" value="{{ old('email', $profile->email) }}" type="email" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                            @error('email')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Phone</label>
                            <input name="phone" value="{{ old('phone', $profile->phone) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                            @error('phone')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Date of Birth</label>
                            <input name="date_of_birth" value="{{ old('date_of_birth', optional($profile->date_of_birth)->format('Y-m-d')) }}" type="date" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                            @error('date_of_birth')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Gender</label>
                            <select name="gender" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                                <option value="">Select</option>
                                <option value="male" {{ old('gender', $profile->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $profile->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', $profile->gender) === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Height</label>
                            <input name="height" value="{{ old('height', $profile->height) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="e.g. 5'8&quot;">
                            @error('height')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Marital Status</label>
                            <input name="marital_status" value="{{ old('marital_status', $profile->marital_status) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="Single, Married, Divorced">
                            @error('marital_status')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Location</label>
                            <input name="location" value="{{ old('location', $profile->location) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                            @error('location')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Education</label>
                            <input name="education" value="{{ old('education', $profile->education) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                            @error('education')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Job Title</label>
                            <input name="job_title" value="{{ old('job_title', $profile->job_title) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                            @error('job_title')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-gray uppercase tracking-widest">Annual Income</label>
                            <input name="annual_income" value="{{ old('annual_income', $profile->annual_income) }}" type="text" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark" placeholder="e.g. 10,00,000">
                            @error('annual_income')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">About</label>
                        <textarea name="about" rows="4" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">{{ old('about', $profile->about) }}</textarea>
                        @error('about')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-gray uppercase tracking-widest">Profile Photo</label>
                        <div class="flex items-center gap-4">
                            <div class="w-24 h-24 rounded-2xl bg-light border border-gray-lighter overflow-hidden">
                                @if($profile->profile_photo)
                                    <img src="{{ asset('storage/' . $profile->profile_photo) }}" alt="Photo" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray text-xs">No photo</div>
                                @endif
                            </div>
                            <input type="file" name="profile_photo" accept="image/*" class="text-xs text-gray">
                        </div>
                        @error('profile_photo')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $profile->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-lighter rounded focus:ring-primary">
                        <label for="is_active" class="text-sm font-bold text-gray">Active</label>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="bg-primary text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20">{{ $profile->exists ? 'Update Profile' : 'Create Profile' }}</button>
                        <a href="{{ route('admin.matrimonies.index') }}" class="bg-white border border-gray-lighter text-dark px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
