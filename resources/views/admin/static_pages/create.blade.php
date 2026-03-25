@extends('admin.layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary">
            {{ $page->id ? 'Edit' : 'Create' }} Static Page
        </h1>
        <p class="text-text-muted text-sm mt-1">
            {{ $page->id ? 'Update' : 'Add a new' }} static page like FAQs, policies, or terms
        </p>
    </div>
    <a href="{{ route('admin.static_pages.index') }}" class="px-4 py-2 bg-gray-200 text-text-primary rounded-lg hover:bg-gray-300 transition-all">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
</div>

<div class="bg-white rounded-lg shadow p-8 max-w-4xl">
    <form method="POST" action="{{ $page->id ? route('admin.static_pages.update', $page->id) : route('admin.static_pages.store') }}">
        @csrf
        @if($page->id)
            @method('PUT')
        @endif

        <!-- Page Type -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-text-primary mb-2">
                Page Type <span class="text-danger">*</span>
            </label>
            <select name="type" {{ $page->id ? 'disabled' : '' }} 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary {{ $errors->has('type') ? 'border-danger' : '' }}"
                    required>
                <option value="">Select Page Type</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" {{ old('type', $page->type) == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @if($page->id)
                <input type="hidden" name="type" value="{{ $page->type }}">
                <p class="text-xs text-text-muted mt-2">Page type cannot be changed after creation</p>
            @endif
            @error('type')
                <p class="text-danger text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Title -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-text-primary mb-2">
                Title <span class="text-danger">*</span>
            </label>
            <input type="text" name="title" value="{{ old('title', $page->title) }}" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary {{ $errors->has('title') ? 'border-danger' : '' }}"
                   placeholder="Enter page title" required>
            @error('title')
                <p class="text-danger text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Content -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-text-primary mb-2">
                Content <span class="text-danger">*</span>
            </label>
            <textarea name="content" rows="12"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary font-mono text-sm {{ $errors->has('content') ? 'border-danger' : '' }}"
                      placeholder="Enter page content (HTML allowed)" required>{{ old('content', $page->content) }}</textarea>
            <p class="text-xs text-text-muted mt-2">You can use HTML formatting</p>
            @error('content')
                <p class="text-danger text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Status -->
        <div class="mb-8">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" 
                       {{ old('is_active', $page->is_active) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary">
                <span class="text-sm font-medium text-text-primary">Active (Visible to public)</span>
            </label>
        </div>

        <!-- Buttons -->
        <div class="flex items-center gap-4 pt-6 border-t border-gray-200">
            <button type="submit" class="px-6 py-2 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-all">
                <i class="fas fa-save mr-2"></i> {{ $page->id ? 'Update' : 'Create' }} Page
            </button>
            <a href="{{ route('admin.static_pages.index') }}" class="px-6 py-2 bg-gray-200 text-text-primary font-medium rounded-lg hover:bg-gray-300 transition-all">
                Cancel
            </a>
        </div>
    </form>
</div>

<!-- Preview Section (Optional) -->
<div class="bg-white rounded-lg shadow p-8 max-w-4xl mt-6">
    <h2 class="text-lg font-bold text-text-primary mb-4">Content Preview</h2>
    <div class="bg-gray-50 rounded-lg p-4 prose prose-sm max-w-none">
        {!! old('content', $page->content) !!}
    </div>
</div>
@endsection
