@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">View Feedback</h1>
            <p class="text-sm text-gray font-medium">Submitted on {{ $feedback->created_at->format('M d, Y \a\t h:i A') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.feedbacks.index') }}" class="bg-gray-lighter text-dark px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray transition-all flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <form action="{{ route('admin.feedbacks.destroy', $feedback->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-red-600 transition-all flex items-center gap-2">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- User Info Card -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
                <div class="bg-light/30 p-6 border-b border-gray-lighter">
                    <h2 class="text-lg font-bold text-dark">User Information</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-start gap-4 mb-8">
                        <div class="w-20 h-20 rounded-full bg-light border border-gray-lighter flex items-center justify-center overflow-hidden flex-shrink-0">
                            @if($feedback->user->profile_photo)
                                <img src="{{ $feedback->user->profile_photo }}" alt="" class="w-full h-full object-cover">
                            @else
                                <i class="fas fa-user text-primary text-2xl"></i>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-dark">{{ $feedback->user->name }}</h3>
                            <p class="text-gray text-sm">{{ $feedback->user->email }}</p>
                            @if($feedback->user->phone)
                                <p class="text-gray text-sm">{{ $feedback->user->phone }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rating Card -->
        <div>
            <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
                <div class="bg-light/30 p-6 border-b border-gray-lighter">
                    <h2 class="text-lg font-bold text-dark">Rating</h2>
                </div>
                <div class="p-6 text-center">
                    @if($feedback->rating)
                        <div class="flex justify-center gap-2 text-4xl mb-4">
                            @for($i = 1; $i <= 5; $i++)
                                <span>{{ $i <= $feedback->rating ? '⭐' : '☆' }}</span>
                            @endfor
                        </div>
                        <p class="text-2xl font-bold text-dark">{{ $feedback->rating }}/5</p>
                    @else
                        <p class="text-gray text-lg">No rating provided</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Comment -->
    <div class="mt-8">
        <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
            <div class="bg-light/30 p-6 border-b border-gray-lighter">
                <h2 class="text-lg font-bold text-dark">Feedback Comment</h2>
            </div>
            <div class="p-8">
                <p class="text-dark leading-relaxed whitespace-pre-wrap">{{ $feedback->comment }}</p>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="mt-8">
        <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
            <div class="bg-light/30 p-6 border-b border-gray-lighter">
                <h2 class="text-lg font-bold text-dark">Timeline</h2>
            </div>
            <div class="p-6">
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-4 h-4 rounded-full bg-primary mt-1"></div>
                    </div>
                    <div class="flex-1 pb-8">
                        <p class="font-semibold text-dark">Feedback Submitted</p>
                        <p class="text-gray text-sm">{{ $feedback->created_at->format('M d, Y \a\t h:i A') }}</p>
                    </div>
                </div>
                @if($feedback->updated_at !== $feedback->created_at)
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-4 h-4 rounded-full bg-gray-lighter"></div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-dark">Last Updated</p>
                            <p class="text-gray text-sm">{{ $feedback->updated_at->format('M d, Y \a\t h:i A') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
