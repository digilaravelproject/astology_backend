@extends('admin.layouts.app')

@section('content')
<div x-data="{ historyModal: false, selectedAstro: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1 text-center md:text-left underline decoration-primary/30 decoration-8 underline-offset-8">Provider Performance</h1>
            <p class="text-sm text-gray font-medium text-center md:text-left mt-2">Macro-level analysis of order distribution and fiscal throughput per partner.</p>
        </div>
        <div class="flex gap-2 justify-center">
            <button class="bg-white border border-gray-lighter text-dark px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-chart-pie mr-2"></i> Comparative Analysis
            </button>
        </div>
    </div>

    <!-- Performance Matrix Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-8 rounded-[40px] border border-gray-lighter shadow-sm group hover:shadow-2xl transition-all relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px] -mr-8 -mt-8 group-hover:bg-primary/10 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-4">Top Revenue Generator</div>
            <div class="text-xl font-black text-dark mb-1">{{ $topRevenue['name'] }}</div>
            <div class="text-3xl font-black text-primary">{{ $topRevenue['rev_display'] }}</div>
            <div class="mt-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                <span class="text-[9px] font-bold text-gray uppercase tracking-tighter">Lifetime Settlement</span>
            </div>
        </div>
        <div class="bg-white p-8 rounded-[40px] border border-gray-lighter shadow-sm group hover:shadow-2xl transition-all relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-success/5 rounded-bl-[100px] -mr-8 -mt-8 group-hover:bg-success/10 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-4">Highest Volume</div>
            <div class="text-xl font-black text-dark mb-1">{{ $highestVolume['name'] }}</div>
            <div class="text-3xl font-black text-success">{{ number_format($highestVolume['total']) }} Orders</div>
            <div class="mt-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                <span class="text-[9px] font-bold text-gray uppercase tracking-tighter">Peak channel performance</span>
            </div>
        </div>
        <div class="bg-white p-8 rounded-[40px] border border-gray-lighter shadow-sm group hover:shadow-2xl transition-all relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-info/5 rounded-bl-[100px] -mr-8 -mt-8 group-hover:bg-info/10 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-4">Avg. Order Value</div>
            <div class="text-xl font-black text-dark mb-1">Platform-Wide</div>
            <div class="text-3xl font-black text-info">₹{{ number_format($avgOrderValue, 2) }}</div>
            <div class="mt-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-info animate-pulse"></span>
                <span class="text-[9px] font-bold text-gray uppercase tracking-tighter">Growth: +5% MoM</span>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm mb-8 flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-[250px] relative group">
            <i class="fas fa-filter absolute left-5 top-1/2 -translate-y-1/2 text-gray group-focus-within:text-primary transition-colors"></i>
            <input type="text" placeholder="Filter by Provider Name or Expert ID..." class="w-full bg-light/50 border-none pl-14 pr-4 py-4 rounded-2xl text-xs font-bold focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all">
        </div>
        <div class="flex gap-2">
            <button class="px-6 py-4 bg-light text-gray rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-lighter transition-all">This Month</button>
            <button class="px-6 py-4 bg-dark text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/20">All Time</button>
        </div>
    </div>

    <!-- Performance Table -->
    <div class="bg-white rounded-[40px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-8 py-6 text-[10px] font-black text-gray uppercase tracking-widest">Expert Partner</th>
                        <th class="px-8 py-6 text-[10px] font-black text-gray uppercase tracking-widest">Total Ops</th>
                        <th class="px-8 py-6 text-[10px] font-black text-gray uppercase tracking-widest">Monthly High</th>
                        <th class="px-8 py-6 text-[10px] font-black text-gray uppercase tracking-widest">Weekly Run</th>
                        <th class="px-8 py-6 text-[10px] font-black text-gray uppercase tracking-widest">Real-time</th>
                        <th class="px-8 py-6 text-[10px] font-black text-success uppercase tracking-widest">Revenue Alpha</th>
                        <th class="px-8 py-6 text-[10px] font-black text-gray uppercase tracking-widest text-right">Insight</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($astrologers as $astro)
                    <tr class="hover:bg-light/30 transition-all group">
                        <td class="px-8 py-6">
                            <div class="text-sm font-black text-dark group-hover:text-primary transition-colors flex items-center gap-2 italic">
                                <span class="w-2 h-2 rounded-full bg-primary/20"></span>
                                {{ $astro['name'] }}
                            </div>
                        </td>
                        <td class="px-8 py-6 text-sm font-black text-dark/70">{{ number_format($astro['total']) }}</td>
                        <td class="px-8 py-6 text-sm font-black text-dark/70">{{ number_format($astro['month']) }}</td>
                        <td class="px-8 py-6 text-sm font-black text-dark/70">{{ number_format($astro['week']) }}</td>
                        <td class="px-8 py-6">
                            <span class="px-3 py-1.5 bg-primary/5 text-primary text-[10px] font-black rounded-lg border border-primary/10">{{ number_format($astro['today']) }} Now</span>
                        </td>
                        <td class="px-8 py-6 text-sm font-black text-success">{{ $astro['rev_display'] }}</td>
                        <td class="px-8 py-6 text-right">
                            <a href="{{ route('admin.orders.by-astrologer.provider', $astro['id']) }}" class="w-12 h-12 bg-white border-2 border-gray-lighter text-dark rounded-2xl inline-flex items-center justify-center hover:border-dark hover:bg-dark hover:text-white transition-all shadow-sm transform active:scale-90">
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-8 py-10 text-center text-gray text-xs">No astrologers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Session History Modal -->
    <div x-show="historyModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/90 backdrop-blur-xl"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-8"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-8"
         style="display: none;">
        
        <div class="bg-white w-full max-w-4xl rounded-[50px] shadow-[0_40px_100px_rgba(0,0,0,0.6)] overflow-hidden" @click.away="historyModal = false">
            <div class="p-10 border-b border-gray-lighter bg-light/30 flex justify-between items-center relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                <div class="flex items-center gap-6 relative z-10">
                    <div class="w-20 h-20 bg-dark text-white rounded-3xl flex items-center justify-center text-3xl font-black shadow-2xl transform -rotate-6">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <h3 class="text-3xl font-black text-dark tracking-tighter" x-text="selectedAstro.name"></h3>
                        <p class="text-[11px] font-black text-primary uppercase tracking-[0.2em] mt-2">Historical Settlement Log</p>
                    </div>
                </div>
                <button @click="historyModal = false" class="w-14 h-14 bg-white border border-gray-lighter hover:bg-gray-lighter text-dark rounded-2xl flex items-center justify-center transition-all shadow-xl relative z-10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-10 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <div class="grid grid-cols-3 gap-6 mb-10">
                    <div class="bg-light/30 border border-gray-lighter p-6 rounded-[32px]">
                        <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2 text-center">Lifetime Cap</div>
                        <div class="text-2xl font-black text-dark text-center" x-text="selectedAstro.rev"></div>
                    </div>
                    <div class="bg-light/30 border border-gray-lighter p-6 rounded-[32px]">
                        <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2 text-center">Global Orders</div>
                        <div class="text-2xl font-black text-dark text-center" x-text="selectedAstro.total"></div>
                    </div>
                    <div class="bg-light/30 border border-gray-lighter p-6 rounded-[32px]">
                        <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2 text-center">Avg. Basket</div>
                        <div class="text-2xl font-black text-dark text-center">₹425.00</div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6 border-l-4 border-primary pl-4">Recent 5 Sessions</h4>
                    @for($i = 1; $i <= 5; $i++)
                    <div class="group bg-white hover:bg-dark border border-gray-lighter p-6 rounded-[32px] flex justify-between items-center transition-all shadow-sm hover:shadow-2xl">
                        <div class="flex items-center gap-6">
                            <div class="w-12 h-12 bg-light group-hover:bg-white/10 rounded-2xl flex items-center justify-center text-dark group-hover:text-white transition-all">
                                <i class="fas fa-{{ ['phone','comment','video','phone','comment'][$i-1] }} text-sm"></i>
                            </div>
                            <div>
                                <div class="text-sm font-black text-dark group-hover:text-white transition-all">Client #{{ 5420 + $i }}</div>
                                <div class="text-[10px] font-bold text-gray uppercase mt-1">Oct {{ 14 - $i }}, 2024 • 15:30 PM</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-dark group-hover:text-primary transition-all">₹{{ [450, 300, 1200, 150, 600][$i-1] }}</div>
                            <div class="text-[10px] font-black text-success uppercase tracking-widest">Settled</div>
                        </div>
                    </div>
                    @endfor
                </div>
            </div>
            
            <div class="p-10 bg-light/30 border-t border-gray-lighter flex justify-center overflow-hidden relative">
                <button @click="historyModal = false" class="px-16 py-5 bg-dark text-white text-xs font-black uppercase tracking-[0.3em] rounded-3xl hover:bg-primary transition-all shadow-[0_20px_40px_rgba(0,0,0,0.3)] transform active:scale-95">Complete Performance Audit</button>
            </div>
        </div>
    </div>
</div>
@endsection
