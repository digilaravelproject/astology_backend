@extends('admin.layouts.app')

@section('styles')
<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 800px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #444;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: #d63384;
        outline: none;
    }

    .form-text {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: #777;
    }

    .text-danger {
        color: #dc3545;
        font-size: 12px;
        margin-top: 5px;
        display: block;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 15px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-block;
        text-decoration: none;
    }

    .btn-primary {
        background: #d63384;
        color: white;
    }

    .btn-primary:hover {
        background: #c22975;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid #ddd;
        color: #555;
    }

    .btn-outline:hover {
        background: #f5f5f5;
    }

    .form-actions {
        margin-top: 30px;
        display: flex;
        gap: 15px;
    }

    #astrologer_fields {
        background: #fdf8fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px dashed #d63384;
        margin-top: 20px;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1>{{ isset($user) ? 'Edit User' : 'Add New User' }}</h1>
        <p>{{ isset($user) ? 'Update user details and access.' : 'Fill in the details to create a new user.' }}</p>
    </div>
    <div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="form-container">
    <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}" method="POST">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="name">Full Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}">
            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}">
            @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="password">Password {{ !isset($user) ? '<span class="text-danger">*</span>' : '' }}</label>
            <input type="password" id="password" name="password" class="form-control" {{ !isset($user) ? 'required' : '' }}>
            @if(isset($user))
                <small class="form-text">Leave blank to keep the current password.</small>
            @endif
            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="user_type">User Type <span class="text-danger">*</span></label>
            <select id="user_type" name="user_type" class="form-control" required onchange="toggleAstrologerFields()">
                <option value="user" {{ old('user_type', $user->user_type ?? 'user') == 'user' ? 'selected' : '' }}>Regular User</option>
                <option value="astrologer" {{ old('user_type', $user->user_type ?? '') == 'astrologer' ? 'selected' : '' }}>Astrologer</option>
            </select>
            @error('user_type') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div id="astrologer_fields" style="display: {{ old('user_type', $user->user_type ?? 'user') == 'astrologer' ? 'block' : 'none' }};">
            <h4 style="margin-bottom: 15px; color: #d63384;">Astrologer Information</h4>
            <div class="form-group mb-0">
                <label for="years_of_experience">Years of Experience</label>
                <input type="number" id="years_of_experience" name="years_of_experience" class="form-control" 
                       value="{{ old('years_of_experience', $user->astrologer->years_of_experience ?? 0) }}" min="0">
                @error('years_of_experience') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            @if(isset($user) && $user->astrologer)
            <div style="margin-top: 15px;">
                <span style="font-size: 14px; font-weight: 600;">Current Status: </span>
                <span class="badge" style="background: #f8f9fa; padding: 5px 10px; border-radius: 20px; border: 1px solid #ddd;">
                    {{ ucfirst($user->astrologer->status) }}
                </span>
            </div>
            @endif
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ isset($user) ? 'Update User' : 'Create User' }}
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Cancel</a>
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
