@extends('admin.layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark">{{ $video->id ? 'Edit' : 'Create' }} Training Video</h1>
            <p class="text-sm text-gray font-medium">{{ $video->id ? 'Update' : 'Add a new' }} training video record for the admin panel.</p>
        </div>
        <a href="{{ route('admin.training_videos.index') }}" class="px-5 py-3 bg-white border border-gray-lighter rounded-2xl text-sm font-bold text-gray hover:bg-light transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Back to Videos
        </a>
    </div>

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm p-8">
        <form method="POST" action="{{ $video->id ? route('admin.training_videos.update', $video->id) : route('admin.training_videos.store') }}" enctype="multipart/form-data">
            @csrf
            @if($video->id)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-black text-gray mb-2">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $video->title) }}" placeholder="Enter video title" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 {{ $errors->has('title') ? 'border-danger' : '' }}">
                    @error('title')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-black text-gray mb-2">Type</label>
                    <select name="type" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 {{ $errors->has('type') ? 'border-danger' : '' }}">
                        <option value="">Select or enter type</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ old('type', $video->type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                        <option value="">----------</option>
                    </select>
                    @error('type')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-black text-gray mb-2">Description</label>
                    <textarea name="description" rows="5" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 {{ $errors->has('description') ? 'border-danger' : '' }}" placeholder="Enter a brief video description">{{ old('description', $video->description) }}</textarea>
                    @error('description')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-black text-gray mb-2">Upload Video <span class="text-danger">{{ $video->id ? '' : '*' }}</span></label>
                    <input type="file" name="video_file" accept="video/*" class="w-full text-sm text-gray-700 rounded-2xl border border-gray-lighter p-3 bg-white {{ $errors->has('video_file') ? 'border-danger' : '' }}">
                    @error('video_file')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                    @if($video->video_url)
                        <div class="mt-4">
                            <div class="text-xs font-black uppercase tracking-[0.35em] text-gray mb-2">Current video preview</div>
                            <video controls class="w-full rounded-3xl border border-gray-lighter bg-black">
                                <source src="{{ $video->video_url }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-black text-gray mb-2">Upload Thumbnail</label>
                    <input type="file" name="thumbnail_file" accept="image/*" class="w-full text-sm text-gray-700 rounded-2xl border border-gray-lighter p-3 bg-white {{ $errors->has('thumbnail_file') ? 'border-danger' : '' }}">
                    @error('thumbnail_file')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                    @if($video->thumbnail_url)
                        <div class="mt-4">
                            <div class="text-xs font-black uppercase tracking-[0.35em] text-gray mb-2">Current thumbnail</div>
                            <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }} thumbnail" class="w-40 h-24 rounded-3xl object-cover border border-gray-lighter">
                        </div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-black text-gray mb-2">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $video->sort_order ?? 0) }}" min="0" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 {{ $errors->has('sort_order') ? 'border-danger' : '' }}">
                    @error('sort_order')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $video->is_active) ? 'checked' : '' }} class="w-5 h-5 text-primary border-gray-lighter rounded focus:ring-primary">
                    <label for="is_active" class="text-sm font-bold text-gray">Active (Visible on frontend)</label>
                </div>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-2xl font-black uppercase hover:bg-primary-dark transition-all">{{ $video->id ? 'Update' : 'Create' }} Video</button>
                <a href="{{ route('admin.training_videos.index') }}" class="px-6 py-3 border border-gray-lighter rounded-2xl text-gray font-black hover:bg-light transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
