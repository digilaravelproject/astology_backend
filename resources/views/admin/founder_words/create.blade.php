@extends('admin.layouts.app')

@section('content')
<div class="space-y-8">
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ $word->id ? 'Edit' : 'Add' }} Founder Word</h1>
            <p class="text-sm text-gray font-medium">{{ $word->id ? 'Update the founder' : 'Create a new founder' }} message that will be displayed on the frontend.</p>
        </div>
        <a href="{{ route('admin.founder_words.index') }}" class="px-4 py-2.5 bg-white border border-gray-lighter rounded-2xl text-sm font-black text-gray hover:bg-light transition-all">Back to Founder Words</a>
    </div>

    @if($errors->any())
        <div class="bg-danger/10 border border-danger/20 text-danger px-6 py-4 rounded-3xl shadow-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm p-8">
        <form method="POST" action="{{ $word->id ? route('admin.founder_words.update', $word->id) : route('admin.founder_words.store') }}">
            @csrf
            @if($word->id)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-black text-gray mb-2">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $word->title) }}" placeholder="Enter founder word title" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 {{ $errors->has('title') ? 'border-danger' : '' }}">
                    @error('title')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-black text-gray mb-2">Message <span class="text-danger">*</span></label>
                    <textarea name="message" rows="8" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 {{ $errors->has('message') ? 'border-danger' : '' }}" placeholder="Enter the founder's message or quote">{{ old('message', $word->message) }}</textarea>
                    @error('message')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $word->is_active) ? 'checked' : '' }} class="w-5 h-5 text-primary border-gray-lighter rounded focus:ring-primary">
                    <label for="is_active" class="text-sm font-bold text-gray">Active (Visible on frontend)</label>
                </div>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-2xl font-black uppercase hover:bg-primary-dark transition-all">{{ $word->id ? 'Update' : 'Create' }} Founder Word</button>
                <a href="{{ route('admin.founder_words.index') }}" class="px-6 py-3 border border-gray-lighter rounded-2xl text-gray font-black hover:bg-light transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
