@extends('admin.layouts.app')

@section('content')
<div x-data="{
        reviewModal: false,
        selectedReview: null,
        replyText: '',
        openReview(review) {
            this.selectedReview = review;
            this.replyText = review.reply || '';
            this.reviewModal = true;
        },
        closeModal() {
            this.selectedReview = null;
            this.replyText = '';
            this.reviewModal = false;
        }
    }"
     class="space-y-8">

    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Ratings & Reviews</h1>
            <p class="text-sm text-gray font-medium">Manage astrologer feedback, monitor quality, and reply from the admin panel.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.astrologers.index') }}" class="px-4 py-2.5 bg-white border border-gray-lighter rounded-2xl text-sm font-black text-gray hover:bg-light transition-all">Back to Astrologers</a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-success/10 border border-success/20 text-success px-6 py-4 rounded-3xl shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Average Platform Rating</div>
            <div class="flex items-center gap-4">
                <span class="text-4xl font-black text-dark">{{ number_format($platform->average_rating, 1) }}</span>
                <span class="text-xs font-black uppercase tracking-[0.4em] text-gray">/ 5.0</span>
            </div>
            <div class="mt-4 text-sm text-gray">Average rating across all astrologer reviews.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Reviews</div>
            <div class="text-4xl font-black text-dark">{{ number_format($platform->total_reviews) }}</div>
            <div class="mt-4 text-sm text-gray">Total feedback entries submitted by users.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Replied Reviews</div>
            <div class="text-4xl font-black text-dark">{{ number_format($platform->replied_reviews) }}</div>
            <div class="mt-4 text-sm text-gray">Reviews that already have an admin/astrologer reply.</div>
        </div>
        <div class="bg-white rounded-[32px] p-6 border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Critical Feedback</div>
            <div class="text-4xl font-black text-danger">{{ number_format($platform->critical_reviews) }}</div>
            <div class="mt-4 text-sm text-gray">Low-rating reviews with a rating of 2 or below.</div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1 bg-white rounded-[34px] border border-gray-lighter shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-black text-dark">Top Astrologers</h2>
                    <p class="text-xs text-gray mt-1">Based on review count and average score.</p>
                </div>
                <span class="text-[11px] uppercase tracking-[0.4em] text-gray">Live</span>
            </div>
            <div class="space-y-4">
                @foreach($astrologers->sortByDesc('reviews_count')->take(6) as $astro)
                    <div class="p-4 rounded-3xl border border-gray-lighter bg-light/70 hover:border-primary transition-all">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-3xl bg-primary/10 text-primary flex items-center justify-center font-black text-lg">{{ strtoupper(substr($astro->user?->name ?? 'A', 0, 1)) }}</div>
                                <div>
                                    <div class="text-sm font-black text-dark">{{ $astro->user?->name ?? 'Unnamed' }}</div>
                                    <div class="text-[10px] uppercase tracking-[0.35em] text-gray">{{ $astro->reviews_count }} reviews</div>
                                </div>
                            </div>
                            <div class="text-sm font-black text-dark">{{ number_format($astro->reviews_avg_rating ?? 0, 1) }}</div>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-[10px] uppercase tracking-[0.35em] font-black text-gray">Critical</div>
                            <span class="text-xs font-black text-danger">{{ $astro->critical_reviews_count }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="xl:col-span-2 bg-white rounded-[34px] border border-gray-lighter shadow-sm overflow-hidden">
            <div class="px-6 py-6 border-b border-gray-lighter bg-light/60">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black text-dark">Recent Reviews</h2>
                        <p class="text-xs text-gray mt-1">Latest 30 review entries from customers.</p>
                    </div>
                    <div class="text-[10px] uppercase tracking-[0.4em] text-gray">Updated {{ now()->format('d M, Y') }}</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-light/50 border-b border-gray-lighter">
                        <tr>
                            <th class="px-5 py-4 text-[10px] font-black text-gray uppercase tracking-[0.3em]">Astrologer</th>
                            <th class="px-5 py-4 text-[10px] font-black text-gray uppercase tracking-[0.3em]">Reviewer</th>
                            <th class="px-5 py-4 text-[10px] font-black text-gray uppercase tracking-[0.3em]">Rating</th>
                            <th class="px-5 py-4 text-[10px] font-black text-gray uppercase tracking-[0.3em]">Review</th>
                            <th class="px-5 py-4 text-[10px] font-black text-gray uppercase tracking-[0.3em]">Status</th>
                            <th class="px-5 py-4 text-[10px] font-black text-gray uppercase tracking-[0.3em] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-lighter">
                        @forelse($recentReviews as $review)
                            <tr class="hover:bg-light/30 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="text-sm font-black text-dark">{{ $review->astrologer?->user?->name ?? 'Unknown Astrologer' }}</div>
                                    <div class="text-[10px] text-gray uppercase tracking-[0.3em]">ID #{{ $review->astrologer_id }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-black text-dark">{{ $review->user?->name ?? 'Guest' }}</div>
                                    <div class="text-[10px] text-gray uppercase tracking-[0.3em]">{{ $review->created_at?->format('d M Y') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-2xl bg-accent/10 text-accent font-black text-sm">{{ $review->rating }} <i class="fas fa-star text-[10px]"></i></div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray">{{ \Illuminate\Support\Str::limit($review->review, 70) }}</td>
                                <td class="px-5 py-4 text-sm font-black {{ $review->reply ? 'text-success' : 'text-warning' }}">
                                    {{ $review->reply ? 'Replied' : 'Pending' }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2 flex-wrap">
                                        <button type="button" @click="openReview({!! \Illuminate\Support\Js::from([
                                            'id' => $review->id,
                                            'rating' => $review->rating,
                                            'review' => $review->review,
                                            'reply' => $review->reply,
                                            'reviewer' => $review->user?->name ?? 'Guest',
                                            'astrologer' => $review->astrologer?->user?->name ?? 'Unknown',
                                            'created_at' => $review->created_at?->format('d M Y H:i') ?? '',
                                            'reply_at' => $review->reply_at?->format('d M Y H:i') ?? ''
                                        ]) !!})" class="px-4 py-2 rounded-2xl bg-dark text-white text-[10px] font-black uppercase transition-all hover:bg-black">Details</button>
                                        <form action="{{ route('admin.astrologers.reviews.destroy', $review->id) }}" method="POST" onsubmit="return confirm('Delete this review permanently?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-4 py-2 rounded-2xl bg-danger text-white text-[10px] font-black uppercase transition-all hover:bg-danger-dark">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-gray">No reviews found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="reviewModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        <div class="w-full max-w-3xl bg-white rounded-[40px] shadow-[0_30px_70px_rgba(0,0,0,0.25)] overflow-hidden" @click.away="closeModal()">
            <div class="px-8 py-6 border-b border-gray-lighter flex items-start justify-between gap-4">
                <div>
                    <div class="text-[10px] uppercase tracking-[0.4em] text-gray mb-2">Review details</div>
                    <h3 class="text-2xl font-black text-dark" x-text="selectedReview ? selectedReview.reviewer : ''"></h3>
                    <div class="text-xs text-gray mt-1">for <span class="font-black text-dark" x-text="selectedReview ? selectedReview.astrologer : ''"></span></div>
                </div>
                <button type="button" @click="closeModal()" class="w-11 h-11 rounded-2xl bg-gray-lighter text-dark flex items-center justify-center hover:bg-gray transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-3xl border border-gray-lighter bg-light p-5">
                        <div class="text-[10px] uppercase tracking-[0.4em] text-gray mb-3">Rating</div>
                        <div class="flex items-center gap-3">
                            <span class="text-4xl font-black text-dark" x-text="selectedReview ? selectedReview.rating : ''"></span>
                            <i class="fas fa-star text-accent text-2xl"></i>
                        </div>
                    </div>
                    <div class="rounded-3xl border border-gray-lighter bg-light p-5">
                        <div class="text-[10px] uppercase tracking-[0.4em] text-gray mb-3">Submitted</div>
                        <div class="text-sm font-black text-dark" x-text="selectedReview ? selectedReview.created_at : ''"></div>
                        <div class="text-xs text-gray mt-1" x-show="selectedReview && selectedReview.reply_at">Reply sent <span x-text="selectedReview.reply_at"></span></div>
                    </div>
                </div>

                <div class="rounded-3xl border border-gray-lighter p-6">
                    <div class="text-[10px] uppercase tracking-[0.4em] text-gray mb-3">Customer review</div>
                    <p class="text-sm text-gray leading-relaxed" x-text="selectedReview ? selectedReview.review : ''"></p>
                </div>

                <form x-bind:action="selectedReview ? '/admin/astrologers/reviews/' + selectedReview.id + '/reply' : '#'" method="POST" class="space-y-4">
                    @csrf
                    <div class="rounded-3xl border border-gray-lighter p-6 bg-light">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="text-[10px] uppercase tracking-[0.35em] text-gray">Reply</div>
                                <div class="text-sm text-gray">Send or update the response that the astrologer/admin provided.</div>
                            </div>
                        </div>
                        <textarea name="reply" x-model="replyText" rows="5" class="w-full rounded-3xl border border-gray-lighter p-4 text-sm text-dark focus:outline-none focus:border-primary" placeholder="Write reply here..."></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <button type="submit" class="px-8 py-3 bg-primary text-white rounded-3xl font-black uppercase tracking-[0.15em] hover:bg-primary-dark transition-all">Save Reply</button>
                        <button type="button" @click="closeModal()" class="px-8 py-3 border border-gray-lighter rounded-3xl text-dark font-black uppercase tracking-[0.15em] hover:bg-gray-lighter transition-all">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
