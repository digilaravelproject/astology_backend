@extends('admin.layouts.app')

@section('content')
<div x-data="{ reviewsModal: false, selectedAstro: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 text-center md:text-left">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Ratings & Reviews</h1>
            <p class="text-sm text-gray font-medium">Monitor user sentiment and manage professional reputations.</p>
        </div>
        <div class="flex gap-2 justify-center">
            <button class="bg-white border border-gray-lighter text-dark px-4 py-2.5 rounded-xl font-bold hover:bg-light transition-all flex items-center gap-2 text-xs">
                <i class="fas fa-filter"></i> High Sensitivity Filter
            </button>
        </div>
    </div>

    <!-- Rating Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm group">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Platform Average</div>
            <div class="flex items-center gap-2">
                <div class="text-3xl font-black text-dark">4.82</div>
                <div class="flex text-accent text-[10px] gap-0.5">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>
            </div>
            <div class="mt-2 text-[9px] font-bold text-success uppercase">Top 1% in Industry</div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Total Feedbacks</div>
            <div class="text-3xl font-black text-dark">1.2M+</div>
            <div class="mt-2 text-[9px] font-bold text-info uppercase">+1,420 new today</div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">5-Star Ratio</div>
            <div class="text-3xl font-black text-dark">88.4%</div>
            <div class="mt-2 text-[9px] font-bold text-primary uppercase">Elite Satisfaction</div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Flagged Reviews</div>
            <div class="text-3xl font-black text-danger">412</div>
            <div class="mt-2 text-[9px] font-bold text-danger uppercase">Needs Moderation</div>
        </div>
    </div>

    <!-- Reviews Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter text-center">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-left">Astrologer</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Avg Rating</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Sentiment Trend</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Total Logs</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @php
                        $ratings = [
                            ['name' => 'Pt. Rahul Vyas', 'avg' => '4.8', 'reviews' => '1,240', 'five_star' => '85%', 'four_star' => '10%', 'flagged' => '2'],
                            ['name' => 'Aarti Sharma', 'avg' => '4.9', 'reviews' => '850', 'five_star' => '92%', 'four_star' => '6%', 'flagged' => '0'],
                            ['name' => 'Vikram Joshi', 'avg' => '4.3', 'reviews' => '2,100', 'five_star' => '65%', 'four_star' => '20%', 'flagged' => '45'],
                            ['name' => 'Meera Bai', 'avg' => '5.0', 'reviews' => '3,450', 'five_star' => '98%', 'four_star' => '2%', 'flagged' => '0'],
                            ['name' => 'Sanjay Dutt', 'avg' => '3.8', 'reviews' => '620', 'five_star' => '45%', 'four_star' => '25%', 'flagged' => '84'],
                            ['name' => 'Pooja Reddy', 'avg' => '4.7', 'reviews' => '1,100', 'five_star' => '78%', 'four_star' => '15%', 'flagged' => '4'],
                            ['name' => 'Arjun Nair', 'avg' => '4.5', 'reviews' => '420', 'five_star' => '72%', 'four_star' => '18%', 'flagged' => '1'],
                            ['name' => 'Sneha Gupta', 'avg' => '4.8', 'reviews' => '1,560', 'five_star' => '84%', 'four_star' => '12%', 'flagged' => '0'],
                            ['name' => 'Kavita Joshi', 'avg' => '4.6', 'reviews' => '980', 'five_star' => '75%', 'four_star' => '18%', 'flagged' => '3'],
                            ['name' => 'Deepak Chopra', 'avg' => '4.2', 'reviews' => '310', 'five_star' => '60%', 'four_star' => '22%', 'flagged' => '12'],
                            ['name' => 'Shweta Tiwari', 'avg' => '4.9', 'reviews' => '1,840', 'five_star' => '94%', 'four_star' => '4%', 'flagged' => '0'],
                            ['name' => 'Pt. Gajanand', 'avg' => '5.0', 'reviews' => '5,600', 'five_star' => '99%', 'four_star' => '1%', 'flagged' => '1'],
                        ];
                    @endphp

                    @foreach($ratings as $astro)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-linear-to-br from-accent/20 to-accent/40 text-accent flex items-center justify-center font-black text-sm group-hover:scale-110 transition-transform">
                                    {{ substr($astro['name'], 0, 1) }}
                                </div>
                                <div class="text-sm font-bold text-dark group-hover:text-primary transition-colors">{{ $astro['name'] }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="flex items-center justify-center gap-1.5 text-sm font-black text-dark">
                                <i class="fas fa-star text-accent text-[11px]"></i> {{ $astro['avg'] }}
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-1.5 bg-light rounded-full overflow-hidden">
                                    <div class="h-full bg-accent" style="width: {{ $astro['five_star'] }}"></div>
                                </div>
                                <span class="text-[9px] font-black text-gray uppercase tracking-tighter">{{ $astro['five_star'] }} 5-Star</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="text-sm font-black text-dark">{{ $astro['reviews'] }}</div>
                            <div class="text-[9px] font-bold text-danger uppercase tracking-tighter">{{ $astro['flagged'] }} Flagged</div>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <button @click="selectedAstro = {{ json_encode($astro) }}; reviewsModal = true" class="px-4 py-2.5 bg-dark text-white text-[10px] font-black uppercase rounded-xl hover:bg-black transition-all transform active:scale-95 shadow-md">Read Feedback</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-6 border-t border-gray-lighter flex justify-between items-center bg-light/20">
            <div class="text-[11px] font-black text-gray uppercase tracking-widest underline decoration-primary/20 decoration-2 underline-offset-4">Top Rated Partners Highlighted</div>
            <div class="flex items-center gap-1.5">
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-gray hover:text-dark hover:border-dark transition-all"><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="w-10 h-10 rounded-xl bg-dark text-white font-black text-xs">1</button>
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-dark font-black text-xs hover:bg-dark hover:text-white transition-all">2</button>
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-gray hover:text-dark hover:border-dark transition-all"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Review Details Modal -->
    <div x-show="reviewsModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-2xl rounded-[40px] shadow-[0_40px_80px_rgba(0,0,0,0.4)] overflow-hidden" @click.away="reviewsModal = false">
            <div class="p-8 border-b border-gray-lighter flex justify-between items-center bg-light/30">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-accent text-white rounded-2xl flex items-center justify-center text-xl font-black" x-text="selectedAstro.name ? selectedAstro.name[0] : ''"></div>
                    <div>
                        <h3 class="text-2xl font-black text-dark uppercase tracking-tighter" x-text="'Feedback: ' + selectedAstro.name"></h3>
                        <p class="text-[10px] font-black text-accent uppercase tracking-widest mt-1" x-text="selectedAstro.five_star + ' Positive Sentiment'"></p>
                    </div>
                </div>
                <button @click="reviewsModal = false" class="w-10 h-10 bg-white hover:bg-gray-lighter text-gray rounded-xl flex items-center justify-center transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-8 max-h-[500px] overflow-y-auto space-y-6 custom-scrollbar">
                @foreach([
                    ['user' => 'Aditi Rao', 'rating' => 5, 'text' => 'Amazing session! He predicted accurately about my career transition. Very calm and knowledgeable professional.', 'time' => '2 hours ago'],
                    ['user' => 'Karan Johar', 'rating' => 4, 'text' => 'Good experience, but the call got disconnected once. However, the insights were very deep and helpful.', 'time' => 'Yesterday'],
                    ['user' => 'Priya Singh', 'rating' => 5, 'text' => 'Best astrologer on this platform. I have been consulting him for 2 years now. Highly recommended!', 'time' => '3 days ago'],
                    ['user' => 'Raj Malhotra', 'rating' => 3, 'text' => 'Wait time was too much. The prediction was okay, but I expected more details for the price paid.', 'time' => '1 week ago']
                ] as $review)
                <div class="p-6 bg-light/50 rounded-3xl border border-gray-lighter hover:border-accent/20 transition-all group">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-white border border-gray-lighter flex items-center justify-center text-[10px] font-black text-dark">{{ substr($review['user'], 0, 1) }}</div>
                            <div>
                                <div class="text-xs font-black text-dark">{{ $review['user'] }}</div>
                                <div class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">{{ $review['time'] }}</div>
                            </div>
                        </div>
                        <div class="flex text-accent text-[9px] gap-0.5">
                            @for($i=0; $i<$review['rating']; $i++)
                            <i class="fas fa-star"></i>
                            @endfor
                        </div>
                    </div>
                    <p class="text-xs font-bold text-gray leading-relaxed group-hover:text-dark transition-colors">"{{ $review['text'] }}"</p>
                    <div class="mt-4 flex gap-2">
                        <button class="px-3 py-1.5 bg-white border border-gray-lighter text-[9px] font-black text-gray uppercase rounded-lg hover:border-dark transition-all">Moderate</button>
                        <button class="px-3 py-1.5 bg-white border border-gray-lighter text-[9px] font-black text-primary uppercase rounded-lg hover:border-primary transition-all">Reply</button>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="p-8 bg-light/30 border-t border-gray-lighter flex justify-end gap-4">
                <button class="px-8 py-4 bg-white border border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-gray-lighter transition-all">Export All Logs</button>
                <button @click="reviewsModal = false" class="px-12 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 transform active:scale-95">Close View</button>
            </div>
        </div>
    </div>
</div>
@endsection
