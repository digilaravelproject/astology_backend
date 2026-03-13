@extends('admin.layouts.app')

@section('content')
<div x-data="{ activeReport: 'financial' }">
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Intelligence Center</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Synthesizing platform telemetry into actionable strategic insights.</p>
        </div>
        <div class="flex gap-4">
            <button class="px-6 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all flex items-center gap-3">
                <i class="fas fa-calendar-alt text-primary"></i> Last 30 Days
            </button>
            <div class="flex items-center bg-dark rounded-2xl p-1 shadow-xl shadow-dark/20">
                <button @click="activeReport = 'financial'" :class="activeReport === 'financial' ? 'bg-primary text-white' : 'text-gray-light hover:text-white'" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Financials</button>
                <button @click="activeReport = 'operations'" :class="activeReport === 'operations' ? 'bg-primary text-white' : 'text-gray-light hover:text-white'" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Operations</button>
                <button @click="activeReport = 'growth'" :class="activeReport === 'growth' ? 'bg-primary text-white' : 'text-gray-light hover:text-white'" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Growth</button>
            </div>
        </div>
    </div>

    <!-- Cluster Analytics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-10">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Net GPV</div>
            <div class="text-2xl font-black text-dark tracking-tighter">₹ 14.8M</div>
            <div class="text-[8px] font-bold text-success uppercase mt-1">+12.4%</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Avg. Basket</div>
            <div class="text-2xl font-black text-dark tracking-tighter">₹ 1,240</div>
            <div class="text-[8px] font-bold text-success uppercase mt-1">+4.1%</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Active DAU</div>
            <div class="text-2xl font-black text-dark tracking-tighter">8.4K</div>
            <div class="text-[8px] font-bold text-success uppercase mt-1">+8.2%</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Churn Rate</div>
            <div class="text-2xl font-black text-dark tracking-tighter">1.8%</div>
            <div class="text-[8px] font-bold text-info uppercase mt-1">-0.3% Delta</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Astro Load</div>
            <div class="text-2xl font-black text-dark tracking-tighter">72%</div>
            <div class="text-[8px] font-bold text-warning uppercase mt-1">High Demand</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Rating Avg</div>
            <div class="text-2xl font-black text-dark tracking-tighter">4.82</div>
            <div class="text-[8px] font-bold text-success uppercase mt-1">Excellent</div>
        </div>
    </div>

    <!-- Report Cluster: Financial Performance -->
    <div x-show="activeReport === 'financial'" class="space-y-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <!-- Revenue Breakdown -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-black text-dark uppercase tracking-tighter">Revenue Distribution</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Inter-segment earning logic</p>
                    </div>
                    <button class="w-10 h-10 rounded-xl bg-light flex items-center justify-center text-gray hover:text-dark transition-all"><i class="fas fa-file-export"></i></button>
                </div>
                <div class="space-y-6">
                    <div class="p-6 bg-light/30 rounded-3xl border border-gray-lighter flex items-center justify-between group hover:border-primary/30 transition-all cursor-pointer">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary"><i class="fas fa-comments"></i></div>
                            <div>
                                <div class="text-sm font-black text-dark">Live Consultation</div>
                                <div class="text-[9px] font-bold text-gray uppercase tracking-widest mt-1">64% of Total GPV</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-dark">₹ 9.47M</div>
                            <div class="text-[9px] font-black text-success uppercase tracking-widest">+18.2%</div>
                        </div>
                    </div>
                    <div class="p-6 bg-light/30 rounded-3xl border border-gray-lighter flex items-center justify-between group hover:border-success/30 transition-all cursor-pointer">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-success/10 rounded-2xl flex items-center justify-center text-success"><i class="fas fa-box"></i></div>
                            <div>
                                <div class="text-sm font-black text-dark">Product Marketplace</div>
                                <div class="text-[9px] font-bold text-gray uppercase tracking-widest mt-1">22% of Total GPV</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-dark">₹ 3.25M</div>
                            <div class="text-[9px] font-black text-success uppercase tracking-widest">+4.5%</div>
                        </div>
                    </div>
                    <div class="p-6 bg-light/30 rounded-3xl border border-gray-lighter flex items-center justify-between group hover:border-info/30 transition-all cursor-pointer">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-info/10 rounded-2xl flex items-center justify-center text-info"><i class="fas fa-crown"></i></div>
                            <div>
                                <div class="text-sm font-black text-dark">Subscriptions (MRR)</div>
                                <div class="text-[9px] font-bold text-gray uppercase tracking-widest mt-1">14% of Total GPV</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-dark">₹ 2.07M</div>
                            <div class="text-[9px] font-black text-success uppercase tracking-widest">+8.9%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settlement Pipeline -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-black text-dark uppercase tracking-tighter">Settlement Pipeline</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Provider payout status mapping</p>
                    </div>
                    <button class="w-10 h-10 rounded-xl bg-light flex items-center justify-center text-gray hover:text-dark transition-all"><i class="fas fa-clock"></i></button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-gray-lighter">
                            <tr>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-left">Partner Entity</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-center">Accrued</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-right">Protocol</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-lighter">
                            @for ($i = 0; $i < 5; $i++)
                            <tr class="group hover:bg-light/30 transition-all">
                                <td class="py-4">
                                    <div class="text-xs font-black text-dark">Astro Guru {{ $i + 1 }}</div>
                                    <div class="text-[8px] font-bold text-gray uppercase mt-0.5">UID: AS-9{{ $i }}82</div>
                                </td>
                                <td class="py-4 text-center">
                                    <div class="text-xs font-black text-dark">₹ {{ number_format(random_int(45000, 120000)) }}</div>
                                </td>
                                <td class="py-4 text-right px-2">
                                    <span class="text-[8px] font-black uppercase px-2 py-1 bg-warning/10 text-warning rounded-lg border border-warning/20">Pending</span>
                                </td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button class="w-full mt-6 py-4 border-2 border-dashed border-gray-lighter text-dark text-[10px] font-black uppercase rounded-[24px] hover:border-primary/30 hover:bg-primary/5 transition-all">Review All Settlements</button>
            </div>
        </div>
    </div>

    <!-- Report Cluster: Operational Health -->
    <div x-show="activeReport === 'operations'" class="space-y-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Real-time Queue Depth -->
            <div class="lg:col-span-1 bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h3 class="text-xl font-black text-dark uppercase tracking-tighter mb-8 italic">Queue Pressure</h3>
                <div class="relative w-48 h-48 mx-auto mb-8">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="14" fill="transparent" class="text-light" />
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="14" fill="transparent" stroke-dasharray="552.92" stroke-dashoffset="138.23" class="text-warning" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-4xl font-black text-dark tracking-tighter">75%</div>
                        <div class="text-[8px] font-black text-gray uppercase tracking-widest mt-1">Utilization</div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center text-[10px] font-black text-gray uppercase">
                        <span>Active Sessions</span>
                        <span class="text-dark">142</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-black text-gray uppercase">
                        <span>Wait Time (Avg)</span>
                        <span class="text-warning">14 min</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-black text-gray uppercase">
                        <span>Drop Rate</span>
                        <span class="text-danger">2.4%</span>
                    </div>
                </div>
            </div>

            <!-- Consultation Logistics -->
            <div class="lg:col-span-2 bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-black text-dark uppercase tracking-tighter">Logistics Log</h3>
                    <div class="flex gap-2">
                        <button class="px-3 py-1.5 bg-light rounded-lg text-[9px] font-black uppercase text-dark">Chat</button>
                        <button class="px-3 py-1.5 bg-light rounded-lg text-[9px] font-black uppercase text-gray">Call</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-gray-lighter">
                            <tr>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-left">Session ID</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-left">Entity Pair</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-center">Duration</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-right">Metrics</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-lighter">
                             @php
                                $logistics = [
                                    ['id' => '#LOG-9411', 'user' => 'K. Sharma', 'astro' => 'Pt. Rahul', 'time' => '12:04', 'rating' => 4.8],
                                    ['id' => '#LOG-9412', 'user' => 'A. Singh', 'astro' => 'Astro Meena', 'time' => '08:22', 'rating' => 4.9],
                                    ['id' => '#LOG-9413', 'user' => 'R. Gupta', 'astro' => 'Pt. Naveen', 'time' => '15:10', 'rating' => 4.5],
                                    ['id' => '#LOG-9414', 'user' => 'S. Jain', 'astro' => 'Astro Priya', 'time' => '05:45', 'rating' => 5.0],
                                    ['id' => '#LOG-9415', 'user' => 'D. Verma', 'astro' => 'Pt. Sanjay', 'time' => '22:18', 'rating' => 4.7],
                                ];
                            @endphp
                            @foreach($logistics as $log)
                            <tr class="group hover:bg-light/30 transition-all">
                                <td class="py-4 font-black text-xs text-dark">{{ $log['id'] }}</td>
                                <td class="py-4">
                                    <div class="text-[10px] font-black text-dark">{{ $log['user'] }} <i class="fas fa-arrows-alt-h mx-1 text-gray-lighter"></i> {{ $log['astro'] }}</div>
                                </td>
                                <td class="py-4 text-center">
                                    <div class="text-[10px] font-bold text-gray-light uppercase">{{ $log['time'] }}</div>
                                </td>
                                <td class="py-4 text-right">
                                    <div class="flex items-center justify-end gap-1 text-primary">
                                        <i class="fas fa-star text-[8px]"></i>
                                        <span class="text-xs font-black text-dark">{{ $log['rating'] }}</span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-8 flex justify-between items-center">
                    <div class="text-[9px] font-black text-gray uppercase tracking-widest">Aggregated from 1,248 packets</div>
                    <div class="flex gap-2">
                        <button class="w-8 h-8 rounded-lg border border-gray-lighter flex items-center justify-center text-xs text-gray"><i class="fas fa-chevron-left"></i></button>
                        <button class="w-8 h-8 rounded-lg border border-gray-lighter flex items-center justify-center text-xs text-gray"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Cluster: Growth Ecosystem -->
    <div x-show="activeReport === 'growth'" class="space-y-10">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            <!-- Acquisition Velocity -->
            <div class="lg:col-span-3 bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-10">
                    <div>
                        <h3 class="text-xl font-black text-dark uppercase tracking-tighter">Ecosystem Expansion</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">User lifecycle transition mapping</p>
                    </div>
                    <select class="bg-light border-none px-4 py-2 rounded-xl text-[10px] font-black uppercase text-dark cursor-pointer">
                        <option>Weekly View</option>
                        <option>Monthly View</option>
                    </select>
                </div>
                
                <div class="flex items-end gap-2 h-48 mb-8">
                    @php $heights = [45, 60, 40, 85, 70, 95, 80, 50, 65, 90, 100, 75]; @endphp
                    @foreach($heights as $height)
                    <div class="flex-1 bg-primary/20 hover:bg-primary transition-all rounded-t-xl group relative" style="height: {{ $height }}%">
                        <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-dark text-white text-[8px] font-black px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-all">+{{ $height * 8 }}</div>
                    </div>
                    @endforeach
                </div>
                
                <div class="grid grid-cols-3 gap-6 pt-8 border-t border-gray-lighter">
                    <div>
                        <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Conversion Alpha</div>
                        <div class="text-2xl font-black text-dark tracking-tighter">6.42%</div>
                        <div class="text-[8px] font-black text-success uppercase mt-1">+0.8%</div>
                    </div>
                    <div>
                        <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Activation Logic</div>
                        <div class="text-2xl font-black text-dark tracking-tighter">82%</div>
                        <div class="text-[8px] font-black text-info uppercase mt-1">Optimal</div>
                    </div>
                    <div>
                        <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Referral Velocity</div>
                        <div class="text-2xl font-black text-dark tracking-tighter">12%</div>
                        <div class="text-[8px] font-black text-success uppercase mt-1">+2.4%</div>
                    </div>
                </div>
            </div>

            <!-- Channel Performance -->
            <div class="lg:col-span-1 bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm flex flex-col justify-between">
                <h3 class="text-xl font-black text-dark uppercase tracking-tighter mb-8">Inflow Tech</h3>
                <div class="space-y-8">
                    <div>
                        <div class="flex justify-between text-[10px] font-black text-gray uppercase mb-2">
                            <span>Direct Search</span>
                            <span class="text-dark">42%</span>
                        </div>
                        <div class="h-2 w-full bg-light rounded-full overflow-hidden">
                            <div class="h-full bg-primary w-[42%]"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[10px] font-black text-gray uppercase mb-2">
                            <span>Social Graft</span>
                            <span class="text-dark">28%</span>
                        </div>
                        <div class="h-2 w-full bg-light rounded-full overflow-hidden">
                            <div class="h-full bg-success w-[28%]"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[10px] font-black text-gray uppercase mb-2">
                            <span>Paid Intake</span>
                            <span class="text-dark">18%</span>
                        </div>
                        <div class="h-2 w-full bg-light rounded-full overflow-hidden">
                            <div class="h-full bg-warning w-[18%]"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[10px] font-black text-gray uppercase mb-2">
                            <span>Organic Hub</span>
                            <span class="text-dark">12%</span>
                        </div>
                        <div class="h-2 w-full bg-light rounded-full overflow-hidden">
                            <div class="h-full bg-info w-[12%]"></div>
                        </div>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-lighter">
                    <button class="w-full py-4 bg-light text-dark text-[10px] font-black uppercase rounded-2xl hover:bg-dark hover:text-white transition-all shadow-sm flex items-center justify-center gap-2">
                        <i class="fas fa-bullhorn text-primary"></i> Campaign ROI
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
