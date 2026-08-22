@extends('admin.layouts.app')

@section('content')
<div x-data="{ 
    monitorModal: false, 
    selectedAstro: {
        id: 0,
        name: '',
        status: 'Available',
        workflow: '',
        session_type: null,
        client_name: null,
        elapsed_time: '00:00',
        rating: 4.8,
        level_name: '',
        rate_per_minute: 0
    },
    openMonitor(astro) {
        this.selectedAstro = astro;
        this.monitorModal = true;
    }
}">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1 justify-center md:justify-start">
                <h1 class="text-2xl md:text-3xl font-bold text-dark underline decoration-danger/30 decoration-4">Live Astrologers</h1>
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-danger border border-white"></span>
                </span>
            </div>
            <p class="text-sm text-gray font-medium text-center md:text-left">Real-time oversight of astrologer availability, ongoing consultations, and active flow.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 justify-center">
            <!-- Filter Tabs -->
            <div class="flex bg-white border border-gray-lighter p-1 rounded-2xl shadow-sm">
                <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($statusFilter ?? 'all') === 'all' ? 'bg-dark text-white shadow' : 'text-gray hover:text-dark' }}">
                    All
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'online']) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($statusFilter ?? '') === 'online' ? 'bg-dark text-white shadow' : 'text-gray hover:text-dark' }}">
                    Online
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'busy']) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($statusFilter ?? '') === 'busy' ? 'bg-dark text-white shadow' : 'text-gray hover:text-dark' }}">
                    In-Session
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'available']) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($statusFilter ?? '') === 'available' ? 'bg-dark text-white shadow' : 'text-gray hover:text-dark' }}">
                    Ready
                </a>
            </div>
            <button onclick="window.location.reload()" class="bg-dark text-white px-4 py-2.5 rounded-xl font-bold hover:bg-black transition-all flex items-center gap-2 text-xs shadow-md">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-6 bg-danger/10 border border-danger/20 text-danger px-6 py-4 rounded-3xl shadow-sm text-sm font-semibold">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Live Pulse Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[28px] border-b-4 border-danger shadow-sm group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest">Total Live</div>
                <i class="fas fa-users text-danger/30 text-xl"></i>
            </div>
            <div class="text-3xl font-black text-dark">{{ $stats['total_live'] ?? 0 }}</div>
            <div class="mt-2 text-[9px] font-black text-gray-light uppercase tracking-tighter">Online right now</div>
        </div>

        <div class="bg-white p-6 rounded-[28px] border-b-4 border-info shadow-sm group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest">In Session</div>
                <i class="fas fa-phone-alt text-info/30 text-xl"></i>
            </div>
            <div class="text-3xl font-black text-dark">{{ $stats['in_session'] ?? 0 }}</div>
            <div class="mt-2 text-[9px] font-black text-info uppercase tracking-tighter">
                {{ $stats['active_calls'] ?? 0 }} Calls | {{ $stats['active_chats'] ?? 0 }} Chats
            </div>
        </div>

        <div class="bg-white p-6 rounded-[28px] border-b-4 border-success shadow-sm group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest">Idle / Ready</div>
                <i class="fas fa-check-circle text-success/30 text-xl"></i>
            </div>
            <div class="text-3xl font-black text-dark">{{ $stats['idle_ready'] ?? 0 }}</div>
            <div class="mt-2 text-[9px] font-black text-success uppercase tracking-tighter">Ready for incoming requests</div>
        </div>

        <div class="bg-white p-6 rounded-[28px] border-b-4 border-primary shadow-sm group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest">Rev. Velocity</div>
                <i class="fas fa-bolt text-primary/30 text-xl"></i>
            </div>
            <div class="text-3xl font-black text-dark">₹{{ number_format($stats['revenue_velocity'] ?? 0, 2) }}<span class="text-xs text-gray font-bold">/min</span></div>
            <div class="mt-2 text-[9px] font-black text-primary uppercase tracking-tighter">Active billing rate</div>
        </div>
    </div>

    <!-- Live Control Grid -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="p-6 border-b border-gray-lighter bg-light/10 flex flex-col md:flex-row justify-between gap-4 items-center">
            <div class="flex items-center gap-3">
                <div class="w-1.5 h-6 bg-danger rounded-full"></div>
                <h3 class="text-xs font-black text-dark uppercase tracking-widest">Active Partner Activity</h3>
            </div>
            <form method="GET" action="{{ route('admin.astrologers.live') }}" class="flex gap-2 w-full md:w-auto">
                <input type="hidden" name="status" value="{{ $statusFilter ?? 'all' }}">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name, email..." class="bg-white border border-gray-lighter px-4 py-2 rounded-xl text-[11px] font-bold focus:outline-none focus:border-dark w-full md:w-64 transition-all">
                <button type="submit" class="px-4 py-2 bg-dark text-white text-[11px] font-black uppercase rounded-xl hover:bg-black transition-all shadow-sm">Search</button>
                @if(!empty($search))
                    <a href="{{ route('admin.astrologers.live', ['status' => $statusFilter ?? 'all']) }}" class="px-3 py-2 bg-light border border-gray-lighter text-gray text-[11px] font-bold rounded-xl hover:bg-gray-lighter transition-all">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Partner Identity</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-center">Pulse Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Current Workflow</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-center">Billing Rate</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Command</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($filteredList as $astro)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-dark text-white flex items-center justify-center font-black shadow-lg relative group-hover:bg-primary transition-colors">
                                    {{ strtoupper(substr($astro->name, 0, 1)) }}
                                    <span class="absolute -top-1 -right-1 w-3 h-3 {{ $astro->status == 'Busy' ? 'bg-danger animate-pulse' : ($astro->status == 'Available' ? 'bg-success' : 'bg-gray') }} border-2 border-white rounded-full"></span>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-dark flex items-center gap-2">
                                        {{ $astro->name }}
                                        <span class="text-[9px] font-black text-accent bg-accent/5 px-1.5 rounded flex items-center gap-0.5"><i class="fas fa-star text-warning"></i> {{ number_format($astro->rating, 1) }}</span>
                                    </div>
                                    <div class="text-[9px] font-black text-gray-light uppercase tracking-widest">{{ $astro->level_name }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-5 text-center">
                            @if($astro->status == 'Busy')
                                <span class="px-3 py-1 bg-danger/10 text-danger text-[9px] font-black uppercase rounded-full border border-danger/20 flex items-center gap-1.5 w-fit mx-auto">
                                    <span class="w-1.5 h-1.5 bg-danger rounded-full animate-ping"></span> In-Session
                                </span>
                            @elseif($astro->status == 'Available')
                                <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20 flex items-center gap-1.5 w-fit mx-auto">
                                    <span class="w-1.5 h-1.5 bg-success rounded-full"></span> Ready
                                </span>
                            @else
                                <span class="px-3 py-1 bg-gray/10 text-gray text-[9px] font-black uppercase rounded-full border border-gray/20 flex items-center gap-1.5 w-fit mx-auto">
                                    <span class="w-1.5 h-1.5 bg-gray rounded-full"></span> Offline
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-5">
                            <div class="text-xs font-black text-dark">{{ $astro->workflow }}</div>
                            @if($astro->status == 'Busy')
                                <div class="text-[10px] font-mono text-danger font-semibold flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-clock text-[9px]"></i> Elapsed: {{ $astro->elapsed_time }}
                                </div>
                            @else
                                <div class="text-[10px] font-bold text-gray-light uppercase tracking-tighter italic">
                                    {{ $astro->is_online ? 'Standing by for user requests' : 'Offline' }}
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-5 text-center">
                            @if($astro->rate_per_minute > 0)
                                <div class="text-xs font-black text-primary">₹{{ number_format($astro->rate_per_minute, 2) }}/min</div>
                            @else
                                <div class="text-xs font-bold text-gray">—</div>
                            @endif
                        </td>

                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openMonitor({{ json_encode($astro) }})" 
                                        class="px-3 py-1.5 bg-light border border-gray-lighter text-dark rounded-xl text-xs font-bold hover:bg-dark hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-tv mr-1 text-[10px]"></i> Inspect
                                </button>
                                <a href="{{ route('admin.astrologers.show', $astro->id) }}" 
                                   class="w-8 h-8 bg-light border border-gray-lighter text-gray rounded-xl flex items-center justify-center hover:bg-dark hover:text-white transition-all" title="View Profile">
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray">
                            <i class="fas fa-broadcast-tower text-4xl text-gray-light mb-3 block"></i>
                            <p class="font-bold text-sm text-dark">No astrologers match this filter</p>
                            <p class="text-xs text-gray mt-1">Try switching tabs between All, Online, In-Session, and Ready.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Monitoring Modal -->
    <div x-show="monitorModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-2xl rounded-[40px] shadow-2xl overflow-hidden" @click.away="monitorModal = false">
            <!-- Modal Header -->
            <div class="p-8 border-b border-gray-lighter flex justify-between items-center bg-light/30">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-dark text-white rounded-2xl flex items-center justify-center text-xl font-black" x-text="selectedAstro.name ? selectedAstro.name.charAt(0).toUpperCase() : 'A'"></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-black text-dark uppercase tracking-tighter" x-text="selectedAstro.name"></h3>
                            <span class="text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest" 
                                  :class="selectedAstro.status === 'Busy' ? 'bg-danger text-white' : (selectedAstro.status === 'Available' ? 'bg-success text-white' : 'bg-gray text-white')"
                                  x-text="selectedAstro.status"></span>
                        </div>
                        <p class="text-[10px] font-black text-gray uppercase tracking-widest mt-1" x-text="selectedAstro.level_name"></p>
                    </div>
                </div>
                <button @click="monitorModal = false" class="w-10 h-10 bg-white hover:bg-gray-lighter text-gray rounded-2xl flex items-center justify-center transition-all shadow-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-8 space-y-6">
                <div class="p-5 bg-light/40 border border-gray-lighter rounded-2xl">
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-1">Active Workflow</div>
                    <div class="text-sm font-black text-dark" x-text="selectedAstro.workflow"></div>
                    <template x-if="selectedAstro.status === 'Busy'">
                        <div class="mt-3 flex items-center justify-between text-xs border-t border-gray-lighter pt-3">
                            <span class="text-gray font-semibold">Active Elapsed Time:</span>
                            <span class="font-mono font-bold text-danger" x-text="selectedAstro.elapsed_time"></span>
                        </div>
                    </template>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-white border border-gray-lighter rounded-2xl">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-1">Call Channel</div>
                        <div class="text-xs font-bold" :class="selectedAstro.call_enabled ? 'text-success' : 'text-gray'" x-text="selectedAstro.call_enabled ? 'Enabled' : 'Disabled'"></div>
                    </div>
                    <div class="p-4 bg-white border border-gray-lighter rounded-2xl">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-1">Chat Channel</div>
                        <div class="text-xs font-bold" :class="selectedAstro.chat_enabled ? 'text-success' : 'text-gray'" x-text="selectedAstro.chat_enabled ? 'Enabled' : 'Disabled'"></div>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-light/30 border-t border-gray-lighter flex justify-end gap-3">
                <button @click="monitorModal = false" class="px-8 py-3 bg-dark text-white text-xs font-black uppercase rounded-2xl hover:bg-black transition-all">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
