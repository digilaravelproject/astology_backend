@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Feedback</h1>
            <p class="text-sm text-gray font-medium">View and manage user feedback submissions.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Feedback</div>
            <div class="text-3xl font-black text-dark">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">With Rating</div>
            <div class="text-3xl font-black text-dark">{{ $stats['with_rating'] }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">5-Star Reviews</div>
            <div class="text-3xl font-black text-primary">{{ $stats['5_star'] }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Avg Rating</div>
            <div class="text-3xl font-black text-dark">{{ $stats['avg_rating'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <!-- Filters -->
        <div class="p-6 border-b border-gray-lighter">
            <form method="GET" action="{{ route('admin.feedbacks.index') }}" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search by user name, email or feedback..." 
                        value="{{ request('search') }}"
                        class="w-full px-4 py-3 rounded-2xl border border-gray-lighter focus:outline-none focus:border-primary" style="width: 100%;">
                </div>
                <div class="md:w-48">
                    <select name="rating" 
                        class="w-full px-4 py-3 rounded-2xl border border-gray-lighter focus:outline-none focus:border-primary">
                        <option value="">All Ratings</option>
                        <option value="5" {{ request('rating') == 5 ? 'selected' : '' }}>⭐⭐⭐⭐⭐ (5 Stars)</option>
                        <option value="4" {{ request('rating') == 4 ? 'selected' : '' }}>⭐⭐⭐⭐ (4 Stars)</option>
                        <option value="3" {{ request('rating') == 3 ? 'selected' : '' }}>⭐⭐⭐ (3 Stars)</option>
                        <option value="2" {{ request('rating') == 2 ? 'selected' : '' }}>⭐⭐ (2 Stars)</option>
                        <option value="1" {{ request('rating') == 1 ? 'selected' : '' }}>⭐ (1 Star)</option>
                    </select>
                </div>
                <button type="submit" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all">
                    Filter
                </button>
                @if(request()->has('search') || request()->has('rating'))
                    <a href="{{ route('admin.feedbacks.index') }}" class="bg-gray-lighter text-dark px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray transition-all">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">User</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Rating</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Feedback</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Date</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($feedbacks as $feedback)
                        <tr class="hover:bg-light/30 transition-all">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-light border border-gray-lighter flex items-center justify-center overflow-hidden">
                                        @if($feedback->user->profile_photo)
                                            <img src="{{ $feedback->user->profile_photo }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <i class="fas fa-user text-primary text-lg"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-semibold text-dark">{{ $feedback->user->name }}</div>
                                        <div class="text-xs text-gray">{{ $feedback->user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                @if($feedback->rating)
                                    <div class="flex gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="text-lg {{ $i <= $feedback->rating ? '⭐' : '☆' }}"></span>
                                        @endfor
                                    </div>
                                @else
                                    <span class="text-gray text-sm">No rating</span>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-dark truncate max-w-xs">{{ substr($feedback->comment, 0, 50) }}{{ strlen($feedback->comment) > 50 ? '...' : '' }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-gray text-sm">{{ $feedback->created_at->format('M d, Y') }}</span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.feedbacks.show', $feedback->id) }}" 
                                        class="text-primary hover:text-primary-dark transition-all font-semibold text-sm flex items-center gap-1">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <form action="{{ route('admin.feedbacks.destroy', $feedback->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition-all font-semibold text-sm flex items-center gap-1">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-gray text-4xl mb-3 block"></i>
                                <p class="text-gray font-medium">No feedback found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($feedbacks->hasPages())
            <div class="px-6 py-5 border-t border-gray-lighter">
                {{ $feedbacks->links('pagination::simple-bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
