@extends('admin.layouts.app')

@section('content')
<div x-data="{ performanceModal: false, selectedAstro: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1 text-center md:text-left">Astrologer Performance</h1>
            <p class="text-sm text-gray font-medium text-center md:text-left">Track session efficiency, revenue impact, and user retention.</p>
        </div>
        <div class="flex gap-2 justify-center">
            <button class="bg-white border border-gray-lighter text-dark px-4 py-2.5 rounded-xl font-bold hover:bg-light transition-all flex items-center gap-2 text-xs">
                <i class="fas fa-download"></i> Full Analytics Report
            </button>
        </div>
    </div>

    <!-- Performance Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-primary/5 rounded-full blur-xl group-hover:bg-primary/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Avg. Completion</div>
            <div class="text-3xl font-black text-dark">94.2%</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-success py-0.5 px-2 bg-success/10 rounded-full">+2.4%</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">vs last month</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-success/5 rounded-full blur-xl group-hover:bg-success/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Total Revenue</div>
            <div class="text-3xl font-black text-dark">₹24.8L</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-success py-0.5 px-2 bg-success/10 rounded-full">+18%</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">growth rate</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-danger/5 rounded-full blur-xl group-hover:bg-danger/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Missed Sessions</div>
            <div class="text-3xl font-black text-dark">4.8%</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-danger py-0.5 px-2 bg-danger/10 rounded-full">-0.5%</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">improved</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-info/5 rounded-full blur-xl group-hover:bg-info/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Active Pros</div>
            <div class="text-3xl font-black text-dark">128</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-info py-0.5 px-2 bg-info/10 rounded-full">82%</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">online daily</span>
            </div>
        </div>
    </div>

    <!-- Performance Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter text-center">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-left">Expert Partner</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Total Sessions</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Efficiency</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Revenue Impact</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Retention</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Insight</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @php
                        $performance = [
                            ['name' => 'Pt. Rahul Vyas', 'total' => '1,450', 'comp' => '1,420', 'rate' => '98%', 'revenue' => '₹4,50,000', 'loyal' => '342', 'missed' => '30', 'loss' => '₹4,500'],
                            ['name' => 'Aarti Sharma', 'total' => '850', 'comp' => '842', 'rate' => '99%', 'revenue' => '₹2,80,000', 'loyal' => '156', 'missed' => '8', 'loss' => '₹1,200'],
                            ['name' => 'Vikram Joshi', 'total' => '2,100', 'comp' => '1,950', 'rate' => '93%', 'revenue' => '₹7,20,000', 'loyal' => '410', 'missed' => '150', 'loss' => '₹32,000'],
                            ['name' => 'Meera Bai', 'total' => '3,450', 'comp' => '3,440', 'rate' => '99.7%', 'revenue' => '₹12,40,000', 'loyal' => '890', 'missed' => '10', 'loss' => '₹2,500'],
                            ['name' => 'Sanjay Dutt', 'total' => '620', 'comp' => '540', 'rate' => '87%', 'revenue' => '₹1,85,000', 'loyal' => '88', 'missed' => '80', 'loss' => '₹24,000'],
                            ['name' => 'Pooja Reddy', 'total' => '1,100', 'comp' => '1,050', 'rate' => '95%', 'revenue' => '₹3,90,000', 'loyal' => '210', 'missed' => '50', 'loss' => '₹12,000'],
                            ['name' => 'Arjun Nair', 'total' => '420', 'comp' => '412', 'rate' => '98%', 'revenue' => '₹1,45,000', 'loyal' => '76', 'missed' => '8', 'loss' => '₹2,100'],
                            ['name' => 'Sneha Gupta', 'total' => '1,560', 'comp' => '1,520', 'rate' => '97%', 'revenue' => '₹5,60,000', 'loyal' => '320', 'missed' => '40', 'loss' => '₹8,500'],
                            ['name' => 'Kavita Joshi', 'total' => '980', 'comp' => '940', 'rate' => '96%', 'revenue' => '₹3,10,000', 'loyal' => '184', 'missed' => '40', 'loss' => '₹9,200'],
                            ['name' => 'Deepak Chopra', 'total' => '310', 'comp' => '280', 'rate' => '90%', 'revenue' => '₹85,000', 'loyal' => '42', 'missed' => '30', 'loss' => '₹6,400'],
                            ['name' => 'Shweta Tiwari', 'total' => '1,840', 'comp' => '1,810', 'rate' => '98%', 'revenue' => '₹6,40,000', 'loyal' => '412', 'missed' => '30', 'loss' => '₹7,800'],
                            ['name' => 'Pt. Gajanand', 'total' => '5,600', 'comp' => '5,580', 'rate' => '99.6%', 'revenue' => '₹22,00,000', 'loyal' => '1,240', 'missed' => '20', 'loss' => '₹6,500'],
                        ];
                    @endphp

                    @foreach($performance as $astro)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-dark text-white flex items-center justify-center font-black text-sm shadow-md group-hover:bg-primary transition-colors">
                                    {{ substr($astro['name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-dark group-hover:text-primary transition-colors">{{ $astro['name'] }}</div>
                                    <div class="text-[9px] font-black text-gray-light uppercase tracking-widest">Level 4 Partner</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center text-sm font-black text-dark">{{ $astro['total'] }}</td>
                        <td class="px-6 py-5 text-center">
                            <div class="text-sm font-black text-success">{{ $astro['rate'] }}</div>
                            <div class="text-[9px] font-bold text-gray uppercase tracking-tighter">{{ $astro['comp'] }} Completed</div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="text-sm font-black text-dark">{{ $astro['revenue'] }}</div>
                            <div class="text-[9px] font-bold text-danger uppercase tracking-tighter">{{ $astro['loss'] }} leakage</div>
                        </td>
                        <td class="px-6 py-5 text-center text-sm font-black text-gray">{{ $astro['loyal'] }}</td>
                        <td class="px-6 py-5 text-right">
                            <button @click="selectedAstro = {{ json_encode($astro) }}; performanceModal = true" class="px-4 py-2.5 bg-light border border-gray-lighter text-[10px] font-black text-dark uppercase rounded-xl hover:bg-dark hover:text-white transition-all transform active:scale-95 shadow-sm">Analysis</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-6 border-t border-gray-lighter flex justify-between items-center bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing 12 of 128 partners</div>
            <div class="flex items-center gap-1.5">
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center text-gray hover:bg-dark hover:text-white transition-all"><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="w-10 h-10 rounded-xl bg-dark text-white font-black text-xs">1</button>
                <button class="w-10 h-10 rounded-xl border border-gray-lighter text-dark font-black text-xs hover:bg-dark hover:text-white transition-all">2</button>
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center text-gray hover:bg-dark hover:text-white transition-all"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Performance Insight Modal -->
    <div x-show="performanceModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md"
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
                    <div class="w-14 h-14 bg-dark text-white rounded-2xl flex items-center justify-center text-xl font-black" x-text="selectedAstro.name ? selectedAstro.name[0] : ''"></div>
                    <div>
                        <h3 class="text-2xl font-black text-dark uppercase tracking-tighter" x-text="selectedAstro.name + ' Performance Map'"></h3>
                        <p class="text-[10px] font-black text-gray uppercase tracking-widest mt-1">Full detailed report for Oct 2024</p>
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
                        <div class="text-[9px] font-black text-success uppercase mb-2">Success Rate</div>
                        <div class="text-2xl font-black text-dark" x-text="selectedAstro.rate"></div>
                    </div>
                    <div class="bg-primary/5 p-6 rounded-[28px] border border-primary/10 text-center group hover:bg-primary/10 transition-all">
                        <div class="text-[9px] font-black text-primary uppercase mb-2">Loyal Users</div>
                        <div class="text-2xl font-black text-dark" x-text="selectedAstro.loyal"></div>
                    </div>
                    <div class="bg-danger/5 p-6 rounded-[28px] border border-danger/10 text-center group hover:bg-danger/10 transition-all">
                        <div class="text-[9px] font-black text-danger uppercase mb-2">Revenue Leak</div>
                        <div class="text-2xl font-black text-dark" x-text="selectedAstro.loss"></div>
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- Progress Indicators -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-[10px] font-black text-gray uppercase tracking-widest">Call Completion</span>
                                <span class="text-xs font-black text-dark">98%</span>
                            </div>
                            <div class="h-2 w-full bg-light rounded-full overflow-hidden">
                                <div class="h-full bg-success rounded-full" style="width: 98%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-[10px] font-black text-gray uppercase tracking-widest">Chat Responsiveness</span>
                                <span class="text-xs font-black text-dark">92%</span>
                            </div>
                            <div class="h-2 w-full bg-light rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full" style="width: 92%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Retention Breakdown -->
                    <div class="p-8 bg-light/30 border border-gray-lighter rounded-[32px]">
                        <h4 class="text-xs font-black text-dark uppercase mb-6 tracking-widest">User Retention Insight</h4>
                        <div class="flex items-center gap-4">
                            <div class="flex-1 flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full border-4 border-success border-t-transparent animate-spin-slow flex items-center justify-center relative">
                                    <span class="text-sm font-black text-dark absolute">65%</span>
                                </div>
                                <span class="text-[9px] font-black text-gray uppercase mt-3">Re-ordered</span>
                            </div>
                            <div class="flex-3 space-y-4">
                                <div class="flex items-center justify-between text-[11px] p-3 bg-white rounded-xl border border-gray-lighter">
                                    <span class="font-bold text-gray uppercase tracking-tighter">New Users Thresh</span>
                                    <span class="font-black text-dark">₹1,24,000</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] p-3 bg-white rounded-xl border border-gray-lighter">
                                    <span class="font-bold text-gray uppercase tracking-tighter">Loyal User Billing</span>
                                    <span class="font-black text-dark">₹3,26,000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-8 bg-light/30 border-t border-gray-lighter flex justify-end gap-4 overflow-hidden relative">
                <button class="px-8 py-4 bg-white border border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-gray-lighter transition-all font-mono">Download PDF Log</button>
                <button @click="performanceModal = false" class="px-12 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 transform active:scale-95">Acknowledge Report</button>
            </div>
        </div>
    </div>
</div>
@endsection
