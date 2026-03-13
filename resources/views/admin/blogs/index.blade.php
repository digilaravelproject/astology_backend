@extends('admin.layouts.app')

@section('content')
<div x-data="{ blogModal: false, selectedBlog: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Content Repository</h1>
            <p class="text-sm text-gray font-medium">Intellectual property and educational outreach management.</p>
        </div>
        <div class="flex gap-3">
            <button class="bg-white border border-gray-lighter text-dark px-5 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-layer-group mr-2"></i> Categories
            </button>
            <a href="{{ route('admin.blogs.create') }}" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <i class="fas fa-feather-alt"></i> Draft New Entry
            </a>
        </div>
    </div>

    <!-- Content Intelligence -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-primary/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Publications</div>
            <div class="text-3xl font-black text-dark">142</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-arrow-up"></i> 8.4% Awareness
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-success/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Avg. Retention</div>
            <div class="text-3xl font-black text-dark">4m 12s</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-bolt"></i> Optimal Length
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-info/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Top Category</div>
            <div class="text-3xl font-black text-dark">Vedic</div>
            <div class="mt-2 flex items-center gap-1.5 text-info font-black text-[9px] uppercase">
                <i class="fas fa-crown"></i> High Engagement
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-warning/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Saved Drafts</div>
            <div class="text-3xl font-black text-dark">24</div>
            <div class="mt-2 flex items-center gap-1.5 text-danger font-black text-[9px] uppercase">
                <i class="fas fa-clock"></i> Action Needed
            </div>
        </div>
    </div>

    <!-- Filter Console -->
    <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm mb-8 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Content Filter</label>
            <div class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray transition-colors group-focus-within:text-dark"></i>
                <input type="text" placeholder="Search by title, keyword, or author..." class="w-full bg-light/50 border border-gray-lighter pl-11 pr-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all focus:bg-white focus:shadow-sm">
            </div>
        </div>
        <div class="w-full sm:w-48">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Metadata Tags</label>
            <select class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                <option>All Segments</option>
                <option>Astrology 101</option>
                <option>Horoscope</option>
                <option>Vedic Rituals</option>
                <option>Tarot Insights</option>
            </select>
        </div>
        <button class="bg-dark text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 transform active:scale-95 h-[52px]">Synchronize</button>
    </div>

    <!-- Repository Grid/Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Article Manifest</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Metadata</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Curator</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Metrics</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Ops</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @php
                        $blogs = [
                            ['id' => 'B-7412', 'title' => 'Jupiter Transit 2024: Economic Impact', 'cat' => 'Vedic', 'author' => 'Dr. Vinay Bajrangi', 'date' => 'Oct 14, 2024', 'views' => '12.4K', 'status' => 'Live', 'thumb' => 'https://images.unsplash.com/photo-1543722530-d2c3201371e7?w=100&h=100&fit=crop'],
                            ['id' => 'B-7413', 'title' => 'Vastu Tips for Modern Minimalist Homes', 'cat' => 'Vastu', 'author' => 'Anjali Sharma', 'date' => 'Oct 13, 2024', 'views' => '8.2K', 'status' => 'Live', 'thumb' => 'https://images.unsplash.com/photo-1518780664697-55e3ad937233?w=100&h=100&fit=crop'],
                            ['id' => 'B-7414', 'title' => 'Understanding Rahu-Ketu Mystery', 'cat' => 'Vedic', 'author' => 'Pt. Rahul Vyas', 'date' => 'Oct 12, 2024', 'views' => '15.1K', 'status' => 'Live', 'thumb' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=100&h=100&fit=crop'],
                            ['id' => 'B-7415', 'title' => 'Tarot Card of the Month: The Star', 'cat' => 'Tarot', 'author' => 'Aarti Sharma', 'date' => 'Oct 11, 2024', 'views' => '5.6K', 'status' => 'Draft', 'thumb' => 'https://images.unsplash.com/photo-1601024445121-e5b82f020549?w=100&h=100&fit=crop'],
                            ['id' => 'B-7416', 'title' => 'Mercury Retrograde Survival Guide', 'cat' => 'Horoscope', 'author' => 'Vikram Joshi', 'date' => 'Oct 10, 2024', 'views' => '22K', 'status' => 'Live', 'thumb' => 'https://images.unsplash.com/photo-1506318137071-a8e063b46254?w=100&h=100&fit=crop'],
                            ['id' => 'B-7417', 'title' => 'Healing Benefits of Rudraksha Beads', 'cat' => 'Rituals', 'author' => 'Pt. Gajanand', 'date' => 'Oct 09, 2024', 'views' => '4.8K', 'status' => 'Review', 'thumb' => 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=100&h=100&fit=crop'],
                            ['id' => 'B-7418', 'title' => 'Secret of Seven Chakras Activation', 'cat' => 'Education', 'author' => 'Meera Bai', 'date' => 'Oct 08, 2024', 'views' => '321', 'status' => 'Draft', 'thumb' => 'https://images.unsplash.com/photo-1493612276216-ee3925520721?w=100&h=100&fit=crop'],
                            ['id' => 'B-7419', 'title' => 'Astrology for Career Decisions', 'cat' => 'Education', 'author' => 'Sanjay Dutt', 'date' => 'Oct 07, 2024', 'views' => '9.2K', 'status' => 'Live', 'thumb' => 'https://images.unsplash.com/photo-1454165833267-02300a724292?w=100&h=100&fit=crop'],
                        ];
                    @endphp

                    @foreach($blogs as $blog)
                    <tr class="hover:bg-light/30 transition-all group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-4">
                                <img src="{{ $blog['thumb'] }}" class="w-14 h-14 rounded-2xl object-cover shadow-sm group-hover:scale-105 transition-all duration-300">
                                <div>
                                    <div class="text-sm font-black text-dark line-clamp-1 group-hover:text-primary transition-colors cursor-pointer" @click="selectedBlog = {{ json_encode($blog) }}; blogModal = true">{{ $blog['title'] }}</div>
                                    <div class="text-[9px] font-bold text-gray uppercase mt-1 tracking-widest">{{ $blog['id'] }} • 5 Min Read</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="px-3 py-1 bg-light border border-gray-lighter text-[9px] font-black text-dark uppercase rounded-lg inline-block">{{ $blog['cat'] }}</div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-xs font-bold text-dark italic underline decoration-primary/20 decoration-2 underline-offset-4">{{ $blog['author'] }}</div>
                            <div class="text-[9px] font-bold text-gray-light uppercase mt-1">{{ $blog['date'] }}</div>
                        </td>
                        <td class="px-6 py-5 border-l border-gray-lighter/30">
                            <div class="text-sm font-black text-dark">{{ $blog['views'] }}</div>
                            <div class="text-[8px] font-black text-success uppercase">Averaging 2.4K/day</div>
                        </td>
                        <td class="px-6 py-5">
                            @if($blog['status'] == 'Live') <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20">Active</span>
                            @elseif($blog['status'] == 'Draft') <span class="px-3 py-1 bg-gray/10 text-gray text-[9px] font-black uppercase rounded-full border border-gray/20">Staged</span>
                            @else <span class="px-3 py-1 bg-info/10 text-info text-[9px] font-black uppercase rounded-full border border-info/20">Auditing</span> @endif
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-dark hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <button class="w-10 h-10 bg-white border border-gray-lighter text-danger rounded-xl flex items-center justify-center hover:bg-danger hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-6 border-t border-gray-lighter flex justify-between items-center bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing 8 of 142 Assets</div>
            <div class="flex items-center gap-1.5">
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-gray hover:text-dark hover:border-dark transition-all"><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="w-10 h-10 rounded-xl bg-dark text-white font-black text-xs">1</button>
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-dark font-black text-xs hover:bg-dark hover:text-white transition-all">2</button>
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-gray hover:text-dark hover:border-dark transition-all"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Blog Preview Modal -->
    <div x-show="blogModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-4xl rounded-[40px] shadow-[0_40px_120px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col md:flex-row h-full max-h-[85vh]" @click.away="blogModal = false">
            <!-- Left Side Image -->
            <div class="w-full md:w-1/3 bg-dark relative overflow-hidden">
                <img :src="selectedBlog.thumb ? selectedBlog.thumb.replace('w=100&h=100', 'w=800&h=1200') : ''" class="absolute inset-0 w-full h-full object-cover opacity-60">
                <div class="absolute inset-0 bg-linear-to-t from-dark via-transparent to-transparent flex flex-col justify-end p-8">
                    <div class="px-4 py-2 bg-primary/20 backdrop-blur-md border border-primary/30 rounded-full inline-block text-[10px] font-black text-white uppercase tracking-widest w-fit mb-4" x-text="selectedBlog.cat"></div>
                    <h4 class="text-2xl font-black text-white leading-tight uppercase tracking-tighter" x-text="selectedBlog.title"></h4>
                </div>
            </div>

            <!-- Right Side Content -->
            <div class="w-full md:w-2/3 flex flex-col">
                <div class="p-8 border-b border-gray-lighter flex justify-between items-center bg-light/30">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full border-2 border-primary overflow-hidden">
                            <img src="https://i.pravatar.cc/150?u=admin" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <div class="text-xs font-black text-dark uppercase tracking-widest" x-text="selectedBlog.author"></div>
                            <div class="text-[9px] font-bold text-gray uppercase mt-1" x-text="'Verified Curator • ' + selectedBlog.date"></div>
                        </div>
                    </div>
                    <button @click="blogModal = false" class="w-12 h-12 bg-white hover:bg-gray-lighter text-gray rounded-2xl flex items-center justify-center transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-10 flex-1 overflow-y-auto custom-scrollbar">
                    <div class="prose prose-sm max-w-none">
                        <p class="text-dark font-black text-lg mb-6 leading-relaxed">Celestial movements are signaling a profound shift in market dynamics as Jupiter prepares to transit. This period marks a pivotal moment for those seeking clarity in fiscal growth.</p>
                        <div class="grid grid-cols-2 gap-6 mb-8">
                            <div class="bg-light/40 p-6 rounded-3xl border border-gray-lighter">
                                <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Impact Radius</div>
                                <div class="text-sm font-black text-dark">Global Financial Hubs</div>
                            </div>
                            <div class="bg-light/40 p-6 rounded-3xl border border-gray-lighter">
                                <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Key Remedy</div>
                                <div class="text-sm font-black text-dark">Yellow Sapphire / Rituals</div>
                            </div>
                        </div>
                        <p class="text-gray-light leading-relaxed mb-6 font-medium">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                        <blockquote class="border-l-4 border-primary pl-6 py-2 my-8">
                            <p class="text-dark italic font-bold text-base">"The stars only incline, they do not compel. True power lies in the alignment of one's will with cosmic potential."</p>
                        </blockquote>
                        <p class="text-gray-light leading-relaxed font-medium">Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                    </div>
                </div>

                <div class="p-8 border-t border-gray-lighter bg-light/30 flex justify-between items-center">
                    <div class="flex gap-4">
                        <div class="text-center">
                            <div class="text-lg font-black text-dark" x-text="selectedBlog.views"></div>
                            <div class="text-[8px] font-black text-gray uppercase tracking-widest">Impressions</div>
                        </div>
                        <div class="w-px h-8 bg-gray-lighter mx-2"></div>
                        <div class="text-center">
                            <div class="text-lg font-black text-dark">943</div>
                            <div class="text-[8px] font-black text-gray uppercase tracking-widest">Shares</div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button class="px-6 py-4 bg-white border border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-gray-lighter transition-all">Go to Live Link</button>
                        <button class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20">Edit Entry</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
