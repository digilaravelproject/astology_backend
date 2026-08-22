@extends('admin.layouts.app')

@section('content')
<div x-data="{ 
    performanceModal: false, 
    selectedAstro: {
        name: '',
        level_name: '',
        total_sessions: 0,
        completed_sessions: 0,
        missed_sessions: 0,
        efficiency_rate: 100,
        total_revenue: 0,
        estimated_loss: 0,
        loyal_clients: 0,
        is_online: false,
        is_busy: false
    },
    openModal(astro) {
        this.selectedAstro = astro;
        this.performanceModal = true;
    }
}">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1 text-center md:text-left">Astrologer Performance</h1>
            <p class="text-sm text-gray font-medium text-center md:text-left">Track session efficiency, revenue impact, and user retention in real time.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 justify-center">
            <!-- Time Filter -->
            <div class="flex bg-white border border-gray-lighter p-1 rounded-2xl shadow-sm">
                <a href="{{ request()->fullUrlWithQuery(['range' => 'all', 'page' => 1]) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($timeRange ?? 'all') === 'all' ? 'bg-dark text-white shadow' : 'text-gray hover:text-dark' }}">
                    All Time
                </a>
                <a href="{{ request()->fullUrlWithQuery(['range' => 'today', 'page' => 1]) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($timeRange ?? '') === 'today' ? 'bg-dark text-white shadow' : 'text-gray hover:text-dark' }}">
                    Today
                </a>
                <a href="{{ request()->fullUrlWithQuery(['range' => 'week', 'page' => 1]) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($timeRange ?? '') === 'week' ? 'bg-dark text-white shadow' : 'text-gray hover:text-dark' }}">
                    This Week
                </a>
                <a href="{{ request()->fullUrlWithQuery(['range' => 'month', 'page' => 1]) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($timeRange ?? '') === 'month' ? 'bg-dark text-white shadow' : 'text-gray hover:text-dark' }}">
                    This Month
                </a>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-6 bg-danger/10 border border-danger/20 text-danger px-6 py-4 rounded-3xl shadow-sm text-sm font-semibold">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Performance Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-primary/5 rounded-full blur-xl group-hover:bg-primary/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Avg. Completion</div>
            <div class="text-3xl font-black text-dark">{{ number_format($stats['avg_completion'] ?? 0, 1) }}%</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black {{ ($stats['avg_completion'] ?? 0) >= 80 ? 'text-success bg-success/10' : 'text-danger bg-danger/10' }} py-0.5 px-2 rounded-full">
                    {{ ($stats['avg_completion'] ?? 0) >= 80 ? 'Optimal' : 'Needs Attention' }}
                </span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">session success</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-success/5 rounded-full blur-xl group-hover:bg-success/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Total Revenue</div>
            <div class="text-3xl font-black text-dark">₹{{ number_format($stats['total_revenue'] ?? 0, 2) }}</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-success py-0.5 px-2 bg-success/10 rounded-full">Live</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">completed consultations</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-danger/5 rounded-full blur-xl group-hover:bg-danger/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Missed Sessions</div>
            <div class="text-3xl font-black text-dark">{{ number_format($stats['missed_rate'] ?? 0, 1) }}%</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-danger py-0.5 px-2 bg-danger/10 rounded-full">Rate</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">rejected / missed calls</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-info/5 rounded-full blur-xl group-hover:bg-info/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Active Astrologers</div>
            <div class="text-3xl font-black text-dark">{{ $stats['active_pros'] ?? 0 }} <span class="text-xs text-gray font-normal">/ {{ $stats['total_pros'] ?? 0 }}</span></div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-info py-0.5 px-2 bg-info/10 rounded-full">
                    {{ ($stats['total_pros'] ?? 0) > 0 ? round((($stats['active_pros'] ?? 0) / $stats['total_pros']) * 100) : 0 }}%
                </span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">online now</span>
            </div>
        </div>
    </div>

    <!-- Performance Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <!-- Filter bar -->
        <div class="p-6 border-b border-gray-lighter bg-light/10 flex flex-col md:flex-row justify-between gap-4 items-center">
            <div class="flex items-center gap-3">
                <div class="w-1.5 h-6 bg-primary rounded-full"></div>
                <h3 class="text-xs font-black text-dark uppercase tracking-widest">Astrologer Ranking & Efficiency</h3>
            </div>
            <form method="GET" action="{{ route('admin.astrologers.performance') }}" class="flex gap-2 w-full md:w-auto">
                <input type="hidden" name="range" value="{{ $timeRange ?? 'all' }}">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search astrologer by name, email..." class="bg-white border border-gray-lighter px-4 py-2 rounded-xl text-[11px] font-bold focus:outline-none focus:border-dark w-full md:w-64 transition-all">
                <button type="submit" class="px-4 py-2 bg-dark text-white text-[11px] font-black uppercase rounded-xl hover:bg-black transition-all shadow-sm">Search</button>
                @if(!empty($search))
                    <a href="{{ route('admin.astrologers.performance', ['range' => $timeRange ?? 'all']) }}" class="px-3 py-2 bg-light border border-gray-lighter text-gray text-[11px] font-bold rounded-xl hover:bg-gray-lighter transition-all">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter text-center">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-left">Expert Partner</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Total Sessions</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Efficiency</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Revenue Impact</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Loyal Clients</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Insight</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($performance as $astro)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-dark text-white flex items-center justify-center font-black text-sm shadow-md group-hover:bg-primary transition-colors">
                                    {{ strtoupper(substr($astro->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-dark group-hover:text-primary transition-colors flex items-center gap-2">
                                        {{ $astro->name }}
                                        @if($astro->is_online)
                                            <span class="w-2 h-2 rounded-full bg-success inline-block" title="Online"></span>
                                        @endif
                                    </div>
                                    <div class="text-[9px] font-black text-gray-light uppercase tracking-widest">{{ $astro->level_name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center text-sm font-black text-dark">{{ number_format($astro->total_sessions) }}</td>
                        <td class="px-6 py-5 text-center">
                            <div class="text-sm font-black {{ $astro->efficiency_rate >= 80 ? 'text-success' : 'text-danger' }}">
                                {{ $astro->efficiency_rate }}%
                            </div>
                            <div class="text-[9px] font-bold text-gray uppercase tracking-tighter">{{ $astro->completed_sessions }} Completed</div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="text-sm font-black text-dark">₹{{ number_format($astro->total_revenue, 2) }}</div>
                            <div class="text-[9px] font-bold text-danger uppercase tracking-tighter">₹{{ number_format($astro->estimated_loss, 0) }} leakage</div>
                        </td>
                        <td class="px-6 py-5 text-center text-sm font-black text-gray">{{ number_format($astro->loyal_clients) }}</td>
                        <td class="px-6 py-5 text-right">
                            <button @click="openModal({{ json_encode($astro) }})" 
                                    class="px-4 py-2.5 bg-light border border-gray-lighter text-[10px] font-black text-dark uppercase rounded-xl hover:bg-dark hover:text-white transition-all transform active:scale-95 shadow-sm">
                                Analysis
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray">
                            <i class="fas fa-chart-line text-4xl text-gray-light mb-3 block"></i>
                            <p class="font-bold text-sm text-dark">No performance records found</p>
                            <p class="text-xs text-gray mt-1">Try adjusting your time range filter or search terms.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($performance->hasPages())
        <div class="px-6 py-6 border-t border-gray-lighter flex justify-between items-center bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">
                Showing {{ $performance->firstItem() ?? 0 }} to {{ $performance->lastItem() ?? 0 }} of {{ $performance->total() }} partners
            </div>
            <div>
                {{ $performance->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- Performance Insight Modal -->
    <div x-show="performanceModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-3xl rounded-[40px] shadow-[0_40px_80px_rgba(0,0,0,0.4)] overflow-hidden" @click.away="performanceModal = false">
            <div class="p-8 border-b border-gray-lighter flex justify-between items-center bg-light/30">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-dark text-white rounded-2xl flex items-center justify-center text-xl font-black" x-text="selectedAstro.name ? selectedAstro.name.charAt(0).toUpperCase() : 'A'"></div>
                    <div>
                        <h3 class="text-2xl font-black text-dark uppercase tracking-tighter" x-text="selectedAstro.name + ' Performance Map'"></h3>
                        <p class="text-[10px] font-black text-gray uppercase tracking-widest mt-1" x-text="'Rank Level: ' + selectedAstro.level_name"></p>
                    </div>
                </div>
                <button @click="performanceModal = false" class="w-12 h-12 bg-white hover:bg-gray-lighter text-gray rounded-2xl flex items-center justify-center transition-all shadow-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-8 max-h-[550px] overflow-y-auto custom-scrollbar">
                <!-- Mini Stats Grid -->
                <div class="grid grid-cols-3 gap-6 mb-10">
                    <div class="bg-success/5 p-6 rounded-[28px] border border-success/10 text-center group hover:bg-success/10 transition-all">
                        <div class="text-[9px] font-black text-success uppercase mb-2">Efficiency Rate</div>
                        <div class="text-2xl font-black text-dark" x-text="selectedAstro.efficiency_rate + '%'"></div>
                    </div>
                    <div class="bg-primary/5 p-6 rounded-[28px] border border-primary/10 text-center group hover:bg-primary/10 transition-all">
                        <div class="text-[9px] font-black text-primary uppercase mb-2">Loyal Users</div>
                        <div class="text-2xl font-black text-dark" x-text="selectedAstro.loyal_clients"></div>
                    </div>
                    <div class="bg-danger/5 p-6 rounded-[28px] border border-danger/10 text-center group hover:bg-danger/10 transition-all">
                        <div class="text-[9px] font-black text-danger uppercase mb-2">Revenue Leak</div>
                        <div class="text-2xl font-black text-dark" x-text="'₹' + Number(selectedAstro.estimated_loss).toLocaleString()"></div>
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- Session Breakdown -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="p-5 bg-light/30 border border-gray-lighter rounded-2xl">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-[10px] font-black text-gray uppercase tracking-widest">Completed Sessions</span>
                                <span class="text-xs font-black text-success" x-text="selectedAstro.completed_sessions"></span>
                            </div>
                            <div class="h-2 w-full bg-gray-lighter rounded-full overflow-hidden">
                                <div class="h-full bg-success rounded-full" :style="'width: ' + (selectedAstro.total_sessions > 0 ? (selectedAstro.completed_sessions / selectedAstro.total_sessions * 100) : 100) + '%'"></div>
                            </div>
                        </div>
                        <div class="p-5 bg-light/30 border border-gray-lighter rounded-2xl">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-[10px] font-black text-gray uppercase tracking-widest">Missed / Rejected</span>
                                <span class="text-xs font-black text-danger" x-text="selectedAstro.missed_sessions"></span>
                            </div>
                            <div class="h-2 w-full bg-gray-lighter rounded-full overflow-hidden">
                                <div class="h-full bg-danger rounded-full" :style="'width: ' + (selectedAstro.total_sessions > 0 ? (selectedAstro.missed_sessions / selectedAstro.total_sessions * 100) : 0) + '%'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Impact Breakdown -->
                    <div class="p-8 bg-light/30 border border-gray-lighter rounded-[32px]">
                        <h4 class="text-xs font-black text-dark uppercase mb-6 tracking-widest">Financial & Loyalty Metrics</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center justify-between text-[11px] p-4 bg-white rounded-xl border border-gray-lighter">
                                <span class="font-bold text-gray uppercase tracking-tighter">Gross Earned Revenue</span>
                                <span class="font-black text-dark" x-text="'₹' + Number(selectedAstro.total_revenue).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] p-4 bg-white rounded-xl border border-gray-lighter">
                                <span class="font-bold text-gray uppercase tracking-tighter">Repeat Customer Base</span>
                                <span class="font-black text-dark" x-text="selectedAstro.loyal_clients + ' Clients'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-8 bg-light/30 border-t border-gray-lighter flex justify-end gap-4 overflow-hidden relative">
                <button @click="performanceModal = false" class="px-12 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 transform active:scale-95">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
