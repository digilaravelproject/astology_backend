@extends('admin.layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary">{{ $page->title }}</h1>
        <p class="text-text-muted text-sm mt-1">
            <span class="inline-block px-3 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full mt-2">
                @php
                    $typeLabel = [
                        'faq' => 'FAQs',
                        'privacy_policy' => 'Privacy Policy',
                        'terms_and_conditions' => 'Terms & Conditions',
                        'payment_policy' => 'Payment Policy'
                    ][$page->type] ?? ucfirst(str_replace('_', ' ', $page->type));
                @endphp
                {{ $typeLabel }}
            </span>
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.static_pages.edit', $page->id) }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-all">
            <i class="fas fa-edit mr-2"></i> Edit
        </a>
        <a href="{{ route('admin.static_pages.index') }}" class="px-4 py-2 bg-gray-200 text-text-primary rounded-lg hover:bg-gray-300 transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-8 max-w-4xl prose prose-sm max-w-none">
    {!! $page->content !!}
</div>

<div class="bg-white rounded-lg shadow p-6 max-w-4xl mt-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <p class="text-xs text-text-muted">Status</p>
            <p class="font-semibold text-text-primary">
                @if($page->is_active)
                    <span class="text-success">Active</span>
                @else
                    <span class="text-warning">Inactive</span>
                @endif
            </p>
        </div>
        <div>
            <p class="text-xs text-text-muted">Created</p>
            <p class="font-semibold text-text-primary">{{ $page->created_at->format('M d, Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted">Updated</p>
            <p class="font-semibold text-text-primary">{{ $page->updated_at->format('M d, Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted">Page ID</p>
            <p class="font-semibold text-text-primary">{{ $page->id }}</p>
        </div>
    </div>
</div>
@endsection
