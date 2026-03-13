@extends('admin.layouts.app')

@section('content')
<div x-data="{ monitorModal: false, selectedAstro: {} }">
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
            <p class="text-sm text-gray font-medium text-center md:text-left">Real-time oversight of platform activity and session flow.</p>
        </div>
        <div class="flex gap-2 justify-center">
            <button class="bg-dark text-white px-6 py-2.5 rounded-xl font-bold hover:bg-black transition-all flex items-center gap-2 text-xs shadow-lg shadow-dark/20">
                <i class="fas fa-broadcast-tower"></i> Global Announcement
            </button>
        </div>
    </div>

    <!-- Live Pulse Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[28px] border-b-4 border-danger shadow-sm group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest">Total Live</div>
                <i class="fas fa-users text-danger/30 text-xl"></i>
            </div>
            <div class="text-3xl font-black text-dark">142</div>
            <div class="mt-2 text-[9px] font-black text-gray-light uppercase tracking-tighter">Peak: 185 (8:00 PM)</div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border-b-4 border-info shadow-sm group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest">In Session</div>
                <i class="fas fa-phone-alt text-info/30 text-xl"></i>
            </div>
            <div class="text-3xl font-black text-dark">58</div>
            <div class="mt-2 text-[9px] font-black text-info uppercase tracking-tighter">42 Calls | 16 Chats</div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border-b-4 border-success shadow-sm group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest">Idle / Ready</div>
                <i class="fas fa-check-circle text-success/30 text-xl"></i>
            </div>
            <div class="text-3xl font-black text-dark">84</div>
            <div class="mt-2 text-[9px] font-black text-success uppercase tracking-tighter">Avg wait: 45s</div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border-b-4 border-primary shadow-sm group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest">Rev. Velocity</div>
                <i class="fas fa-bolt text-primary/30 text-xl"></i>
            </div>
            <div class="text-3xl font-black text-dark">₹4,250<span class="text-xs text-gray font-bold">/min</span></div>
            <div class="mt-2 text-[9px] font-black text-primary uppercase tracking-tighter">High demand mode</div>
        </div>
    </div>

    <!-- Live Control Grid -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="p-6 border-b border-gray-lighter bg-light/10 flex flex-col md:flex-row justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-1.5 h-6 bg-danger rounded-full"></div>
                <h3 class="text-xs font-black text-dark uppercase tracking-widest">Active Stream Overview</h3>
            </div>
            <div class="flex gap-2">
                <input type="text" placeholder="Watch specific partner..." class="bg-white border border-gray-lighter px-4 py-2 rounded-xl text-[11px] font-bold focus:outline-none focus:border-dark w-full md:w-64 transition-all">
                <button class="px-4 py-2 bg-light text-dark text-[11px] font-black uppercase rounded-xl hover:bg-dark hover:text-white transition-all shadow-sm">Refresh Stream</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Partner Identity</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Live Runtime</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Pulse Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Current Workflow</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Command</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @php
                        $live = [
                            ['name' => 'Pt. Rahul Vyas', 'since' => '2h 15m', 'status' => 'Busy', 'activity' => 'Video Call: Rajesh K.', 'time' => '12:40', 'avg' => '4.8'],
                            ['name' => 'Aarti Sharma', 'since' => '45m', 'status' => 'Available', 'activity' => 'Waiting for user', 'time' => '00:00', 'avg' => '4.9'],
                            ['name' => 'Vikram Joshi', 'since' => '1h 10m', 'status' => 'Busy', 'activity' => 'Chat: Sneha M.', 'time' => '05:22', 'avg' => '4.3'],
                            ['name' => 'Meera Bai', 'since' => '4h 30m', 'status' => 'Busy', 'activity' => 'Audio Call: Amit G.', 'time' => '08:15', 'avg' => '5.0'],
                            ['name' => 'Sanjay Dutt', 'since' => '15m', 'status' => 'Away', 'activity' => 'Tea Break', 'time' => '00:00', 'avg' => '3.8'],
                            ['name' => 'Pooja Reddy', 'since' => '2h 40m', 'status' => 'Busy', 'activity' => 'Video Call: Priya S.', 'time' => '15:10', 'avg' => '4.7'],
                            ['name' => 'Arjun Nair', 'since' => '55m', 'status' => 'Available', 'activity' => 'Ready for chat', 'time' => '00:00', 'avg' => '4.5'],
                            ['name' => 'Sneha Gupta', 'since' => '3h 12m', 'status' => 'Busy', 'activity' => 'Chat: Rahul T.', 'time' => '03:45', 'avg' => '4.8'],
                            ['name' => 'Kavita Joshi', 'since' => '1h 05m', 'status' => 'Available', 'activity' => 'Reading Kundli', 'time' => '00:00', 'avg' => '4.6'],
                            ['name' => 'Deepak Chopra', 'since' => '25m', 'status' => 'Busy', 'activity' => 'Audio Call: Karan B.', 'time' => '10:30', 'avg' => '4.2'],
                            ['name' => 'Shweta Tiwari', 'since' => '5h 20m', 'status' => 'Busy', 'activity' => 'Video Call: Neha R.', 'time' => '22:15', 'avg' => '4.9'],
                            ['name' => 'Pt. Gajanand', 'since' => '8h 00m', 'status' => 'Busy', 'activity' => 'Audio Call: Vijay L.', 'time' => '06:40', 'avg' => '5.0'],
                        ];
                    @endphp

                    @foreach($live as $astro)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-dark text-white flex items-center justify-center font-black shadow-lg relative group-hover:bg-primary transition-colors">
                                    {{ substr($astro['name'], 0, 1) }}
                                    <span class="absolute -top-1 -right-1 w-3 h-3 {{ $astro['status'] == 'Busy' ? 'bg-danger' : ($astro['status'] == 'Available' ? 'bg-success' : 'bg-warning') }} border-2 border-white rounded-full"></span>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-dark flex items-center gap-2">
                                        {{ $astro['name'] }}
                                        <span class="text-[9px] font-black text-accent bg-accent/5 px-1.5 rounded flex items-center gap-0.5"><i class="fas fa-star"></i> {{ $astro['avg'] }}</span>
                                    </div>
                                    <div class="text-[9px] font-black text-gray-light uppercase tracking-widest">Professional Rank 5</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-black text-dark">{{ $astro['since'] }}</div>
                            <div class="text-[9px] font-bold text-gray uppercase tracking-tighter">Total Session Time Today: 6h</div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($astro['status'] == 'Busy')
                                <span class="px-3 py-1 bg-danger/10 text-danger text-[9px] font-black uppercase rounded-full border border-danger/20 flex items-center gap-1.5 w-fit mx-auto">
                                    <span class="w-1.5 h-1.5 bg-danger rounded-full animate-pulse"></span> In-Session
                                </span>
                            @elseif($astro['status'] == 'Available')
                                <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20 flex items-center gap-1.5 w-fit mx-auto">
                                    <span class="w-1.5 h-1.5 bg-success rounded-full"></span> Available
                                </span>
                            @else
                                <span class="px-3 py-1 bg-warning/10 text-warning text-[9px] font-black uppercase rounded-full border border-warning/20 flex items-center gap-1.5 w-fit mx-auto">
                                    <span class="w-1.5 h-1.5 bg-warning rounded-full"></span> Away
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-xs font-black text-dark">{{ $astro['activity'] }}</div>
                            @if($astro['time'] != '00:00')
                                <div class="text-[10px] font-mono text-gray">Elapsed: {{ $astro['time'] }} / 30:00</div>
                            @else
                                <div class="text-[10px] font-bold text-gray-light uppercase tracking-tighter italic">Idle since 12:42 PM</div>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="selectedAstro = {{ json_encode($astro) }}; monitorModal = true" class="w-10 h-10 bg-light border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-dark hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-podcast text-xs"></i>
                                </button>
                                <button class="px-4 py-2.5 bg-danger/10 text-danger text-[10px] font-black uppercase rounded-xl hover:bg-danger hover:text-white transition-all shadow-sm">Kill</button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Monitoring Modal -->
    <div x-show="monitorModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/90 backdrop-blur-xl"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-dark w-full max-w-4xl rounded-[40px] shadow-[0_40px_100px_rgba(255,0,0,0.2)] overflow-hidden border border-white/5" @click.away="monitorModal = false">
            <!-- Modal Header -->
            <div class="p-8 border-b border-white/5 flex justify-between items-center bg-white/2">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 bg-linear-to-br from-primary/40 to-primary/10 text-white rounded-2xl flex items-center justify-center text-2xl font-black border border-white/10" x-text="selectedAstro.name ? selectedAstro.name[0] : ''"></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-black text-white uppercase tracking-tighter" x-text="'Monitoring: ' + selectedAstro.name"></h3>
                            <span class="bg-danger text-white text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest animate-pulse">Live</span>
                        </div>
                        <p class="text-[10px] font-black text-primary uppercase tracking-widest mt-1" x-text="'Session ID: PR-12' + Math.floor(Math.random() * 899) + '48'"></p>
                    </div>
                </div>
                <button @click="monitorModal = false" class="w-14 h-14 bg-white/5 hover:bg-white/10 text-white/40 rounded-2xl flex items-center justify-center transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-8">
                <!-- Session Diagnostics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
                    <div class="md:col-span-2 aspect-video bg-black rounded-[32px] border border-white/10 relative overflow-hidden flex items-center justify-center shadow-2xl">
                        <div class="absolute inset-0 bg-linear-to-t from-black via-transparent to-transparent opacity-60"></div>
                        <div class="absolute top-6 left-6 flex gap-2">
                            <span class="bg-black/60 backdrop-blur-md text-white text-[9px] font-black px-3 py-1.5 rounded-lg border border-white/10 uppercase tracking-widest">System Monitor</span>
                            <span class="bg-danger/80 backdrop-blur-md text-white text-[9px] font-black px-3 py-1.5 rounded-lg border border-white/10 uppercase tracking-widest">Secured Line</span>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-user-secret text-6xl text-white/5 mb-6"></i>
                            <p class="text-white font-black text-sm uppercase tracking-tighter opacity-40">Encryption Active. Direct Monitor Disabled in Sandbox.</p>
                        </div>
                        <!-- UI Overlay -->
                        <div class="absolute bottom-6 left-6 right-6 flex justify-between items-center">
                            <div class="flex gap-4">
                                <div class="text-center">
                                    <div class="text-[10px] font-black text-white/40 uppercase">Latency</div>
                                    <div class="text-xs font-mono text-success">14ms</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-[10px] font-black text-white/40 uppercase">Jitter</div>
                                    <div class="text-xs font-mono text-success">2ms</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-black text-white/40 uppercase">Bitrate</div>
                                <div class="text-xs font-mono text-white">4.2 Mbps</div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div class="p-6 bg-white/5 border border-white/5 rounded-3xl group hover:bg-white/8 transition-all">
                            <div class="text-[9px] font-black text-white/40 uppercase mb-2">Participant A (H)</div>
                            <div class="text-sm font-black text-white" x-text="selectedAstro.name"></div>
                            <div class="text-[9px] font-bold text-success uppercase mt-1">Uptime: {{ $astro['since'] }}</div>
                        </div>
                        <div class="p-6 bg-white/5 border border-white/5 rounded-3xl group hover:bg-white/8 transition-all">
                            <div class="text-[9px] font-black text-white/40 uppercase mb-2">Participant B (G)</div>
                            <div class="text-sm font-black text-white" x-text="selectedAstro.activity ? selectedAstro.activity.split(': ')[1] : 'Unknown'"></div>
                            <div class="text-[9px] font-bold text-gray-400 uppercase mt-1">Status: Stable</div>
                        </div>
                        <div class="p-6 bg-primary/10 border border-primary/20 rounded-3xl shadow-lg shadow-primary/5">
                            <div class="text-[9px] font-black text-primary uppercase mb-2 font-mono">Real-time Revenue</div>
                            <div class="text-2xl font-black text-white">₹{{ number_format(rand(1240, 5600), 2) }}</div>
                            <div class="text-[9px] font-bold text-primary/60 uppercase mt-1">Accruing...</div>
                        </div>
                    </div>
                </div>

                <!-- Admin Action Console -->
                <div class="p-6 bg-white/5 border border-white/5 rounded-[32px] flex items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <button class="w-14 h-14 bg-white/5 hover:bg-white/10 text-white rounded-2xl flex items-center justify-center transition-all border border-white/10 group">
                            <i class="fas fa-microphone-slash group-hover:text-danger"></i>
                        </button>
                        <button class="w-14 h-14 bg-white/5 hover:bg-white/10 text-white rounded-2xl flex items-center justify-center transition-all border border-white/10 group">
                            <i class="fas fa-comment-dots group-hover:text-primary"></i>
                        </button>
                        <button class="w-14 h-14 bg-white/5 hover:bg-white/10 text-white rounded-2xl flex items-center justify-center transition-all border border-white/10 group">
                            <i class="fas fa-shield-alt group-hover:text-warning"></i>
                        </button>
                    </div>
                    <div class="flex gap-4">
                        <button class="px-10 py-5 bg-white text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-gray-100 transition-all font-mono">Inject Warning</button>
                        <button @click="monitorModal = false" class="px-10 py-5 bg-danger text-white text-[11px] font-black uppercase rounded-2xl hover:bg-danger-dark transition-all shadow-xl shadow-danger/20">Emergency Kill</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
