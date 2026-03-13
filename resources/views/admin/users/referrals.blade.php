@extends('admin.layouts.app')

@section('content')
<div x-data="{ referralModal: false, selectedReferrer: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Referral Tracking</h1>
            <p class="text-sm text-gray font-medium">Monitor viral growth and affiliate performance.</p>
        </div>
        <div class="flex gap-2">
            <button class="bg-white border border-gray-lighter text-dark px-4 py-2.5 rounded-xl font-bold hover:bg-light transition-all flex items-center gap-2">
                <i class="fas fa-cog"></i> Referral Settings
            </button>
            <button class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-primary/20 hover:bg-primary-dark transition-all flex items-center gap-2">
                <i class="fas fa-paper-plane"></i> Invite New Affiliates
            </button>
        </div>
    </div>

    <!-- Growth Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[24px] shadow-sm border border-gray-lighter hover:shadow-lg transition-all group">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest group-hover:text-primary transition-colors">Total Codes</div>
            <div class="text-3xl font-black text-dark">4,285</div>
            <div class="mt-2 text-[10px] font-bold text-success flex items-center gap-1">
                <i class="fas fa-arrow-up"></i> 12% growth
            </div>
        </div>
        <div class="bg-white p-6 rounded-[24px] shadow-sm border border-gray-lighter hover:shadow-lg transition-all group">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest group-hover:text-primary transition-colors">Total Referrals</div>
            <div class="text-3xl font-black text-dark">18,450</div>
            <div class="mt-2 text-[10px] font-bold text-success flex items-center gap-1">
                <i class="fas fa-users"></i> 342 this week
            </div>
        </div>
        <div class="bg-white p-6 rounded-[24px] shadow-sm border border-gray-lighter hover:shadow-lg transition-all group">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest group-hover:text-primary transition-colors">Success Rate</div>
            <div class="text-3xl font-black text-dark">64.2%</div>
            <div class="mt-2 text-[10px] font-bold text-info flex items-center gap-1">
                <i class="fas fa-chart-line"></i> Industry avg: 45%
            </div>
        </div>
        <div class="bg-white p-6 rounded-[24px] shadow-sm border border-gray-lighter hover:shadow-lg transition-all group">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest group-hover:text-primary transition-colors">Total Payouts</div>
            <div class="text-3xl font-black text-dark">₹4,85,000</div>
            <div class="mt-2 text-[10px] font-bold text-accent flex items-center gap-1">
                <i class="fas fa-wallet"></i> ₹12k pending
            </div>
        </div>
    </div>

    <!-- Referrals Table -->
    <div class="bg-white rounded-[24px] shadow-md border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/50 border-b border-gray-lighter text-center">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-left">Main Referrer</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Code</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Direct Links</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Conversions</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Earnings</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @php
                        $referrers = [
                            ['name' => 'Rahul Sharma', 'id' => '101', 'code' => 'RAHUL500', 'links' => 125, 'convs' => 84, 'earnings' => '₹8,400', 'rate' => '67%'],
                            ['name' => 'Priya Patel', 'id' => '102', 'code' => 'PRIYA100', 'links' => 45, 'convs' => 32, 'earnings' => '₹3,200', 'rate' => '71%'],
                            ['name' => 'Sneha Gupta', 'id' => '104', 'code' => 'SNEHA80', 'links' => 89, 'convs' => 52, 'earnings' => '₹5,200', 'rate' => '58%'],
                            ['name' => 'Anjali Verma', 'id' => '106', 'code' => 'ANJALI', 'links' => 67, 'convs' => 45, 'earnings' => '₹4,500', 'rate' => '67%'],
                            ['name' => 'Pooja Reddy', 'id' => '108', 'code' => 'POOJA99', 'links' => 234, 'convs' => 156, 'earnings' => '₹15,600', 'rate' => '66%'],
                            ['name' => 'Kavita Joshi', 'id' => '110', 'code' => 'KAVITA', 'links' => 112, 'convs' => 78, 'earnings' => '₹7,800', 'rate' => '69%'],
                            ['name' => 'Meera Bai', 'id' => '112', 'code' => 'MEERA50', 'links' => 56, 'convs' => 34, 'earnings' => '₹3,400', 'rate' => '60%'],
                            ['name' => 'Shweta Tiwari', 'id' => '114', 'code' => 'SHWETA', 'links' => 342, 'convs' => 210, 'earnings' => '₹21,000', 'rate' => '61%'],
                            ['name' => 'Amit Kumar', 'id' => '103', 'code' => 'AMIT88', 'links' => 23, 'convs' => 12, 'earnings' => '₹1,200', 'rate' => '52%'],
                            ['name' => 'Vikram Singh', 'id' => '105', 'code' => 'VIKRAM', 'links' => 12, 'convs' => 4, 'earnings' => '₹400', 'rate' => '33%'],
                            ['name' => 'Raj Malhotra', 'id' => '107', 'code' => 'RAJ200', 'links' => 98, 'convs' => 65, 'earnings' => '₹6,500', 'rate' => '66%'],
                            ['name' => 'Arjun Nair', 'id' => '109', 'code' => 'ARJUN', 'links' => 44, 'convs' => 28, 'earnings' => '₹2,800', 'rate' => '63%'],
                            ['name' => 'Sanjay Dutt', 'id' => '111', 'code' => 'SANJAY', 'links' => 15, 'convs' => 8, 'earnings' => '₹800', 'rate' => '53%'],
                            ['name' => 'Aman Gupta', 'id' => '115', 'code' => 'AMAN10', 'links' => 76, 'convs' => 42, 'earnings' => '₹4,200', 'rate' => '55%'],
                            ['name' => 'Deepak Chopra', 'id' => '113', 'code' => 'DEEPAK', 'links' => 31, 'convs' => 18, 'earnings' => '₹1,800', 'rate' => '58%'],
                        ];
                    @endphp
                    @foreach($referrers as $user)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-linear-to-br from-primary/10 to-primary/30 text-primary flex items-center justify-center font-black text-sm group-hover:scale-110 transition-transform">
                                    {{ substr($user['name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-dark group-hover:text-primary transition-colors">{{ $user['name'] }}</div>
                                    <div class="text-[10px] font-black text-gray uppercase tracking-tighter">REF-ID: #{{ $user['id'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <span class="px-3 py-1 bg-light border border-gray-lighter text-primary font-mono text-xs font-black rounded-lg">
                                {{ $user['code'] }}
                            </span>
                        </td>
                        <td class="px-6 py-5 text-center text-sm font-bold text-dark">{{ $user['links'] }}</td>
                        <td class="px-6 py-5 text-center">
                            <div class="text-sm font-bold text-success">{{ $user['convs'] }}</div>
                            <div class="text-[9px] font-black text-gray uppercase tracking-tighter">{{ $user['rate'] }} Rate</div>
                        </td>
                        <td class="px-6 py-5 text-center text-sm font-black text-dark">{{ $user['earnings'] }}</td>
                        <td class="px-6 py-5 text-right">
                            <button @click="selectedReferrer = {{ json_encode($user) }}; referralModal = true" class="px-5 py-2.5 bg-dark text-white text-[10px] font-black uppercase rounded-xl hover:bg-black transition-all hover:scale-105 active:scale-95 transform">View Network</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing 15 of 450 referrers</div>
            <div class="flex items-center gap-1">
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center text-gray hover:bg-primary hover:text-white transition-all disabled:opacity-50" disabled><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="w-10 h-10 rounded-xl bg-primary text-white font-black text-xs shadow-md">1</button>
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center text-dark font-black text-xs hover:bg-primary hover:text-white transition-all">2</button>
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center text-dark font-black text-xs hover:bg-primary hover:text-white transition-all">3</button>
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center text-gray hover:bg-primary hover:text-white transition-all"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Referral Network Modal -->
    <div x-show="referralModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/70 shadow-2xl backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-2xl rounded-[32px] shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden" @click.away="referralModal = false">
            <div class="p-8 border-b border-gray-lighter flex justify-between items-center bg-light/30">
                <div>
                    <h3 class="text-2xl font-black text-dark leading-tight" x-text="'Network: ' + selectedReferrer.name"></h3>
                    <p class="text-[11px] font-black text-primary uppercase tracking-widest mt-1" x-text="'Primary Code: ' + selectedReferrer.code"></p>
                </div>
                <button @click="referralModal = false" class="w-12 h-12 bg-light hover:bg-gray-lighter text-gray-light hover:text-dark rounded-2xl flex items-center justify-center transition-all transform hover:rotate-90">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-8 max-h-[500px] overflow-y-auto space-y-4 custom-scrollbar">
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="bg-primary/5 p-4 rounded-2xl text-center border border-primary/10">
                        <div class="text-[9px] font-black text-primary uppercase mb-1">Links Clicked</div>
                        <div class="text-xl font-black text-dark" x-text="selectedReferrer.links"></div>
                    </div>
                    <div class="bg-success/5 p-4 rounded-2xl text-center border border-success/10">
                        <div class="text-[9px] font-black text-success uppercase mb-1">Converted</div>
                        <div class="text-xl font-black text-dark" x-text="selectedReferrer.convs"></div>
                    </div>
                    <div class="bg-info/5 p-4 rounded-2xl text-center border border-info/10">
                        <div class="text-[9px] font-black text-info uppercase mb-1">Avg Earnings</div>
                        <div class="text-xl font-black text-dark">₹100/usr</div>
                    </div>
                </div>

                <h4 class="text-[10px] font-black text-gray uppercase tracking-widest pl-2">Recent Converted Users</h4>
                <div class="space-y-3">
                    @foreach(['Vikram Singh' => '12 Mar, 4:20 PM', 'Anjali Devi' => '11 Mar, 2:15 PM', 'Rajesh Kumar' => '10 Mar, 8:45 AM', 'Soniya Gandhi' => '09 Mar, 10:30 PM'] as $name => $time)
                    <div class="flex items-center justify-between p-4 bg-light/50 rounded-2xl border border-gray-lighter hover:border-primary/20 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-white border border-gray-lighter flex items-center justify-center text-xs font-black text-dark">{{ substr($name, 0, 1) }}</div>
                            <div>
                                <div class="text-sm font-bold text-dark">{{ $name }}</div>
                                <div class="text-[10px] font-semibold text-gray-light uppercase">{{ $time }}</div>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full">Completed</span>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <div class="p-8 bg-light/30 border-t border-gray-lighter flex justify-end gap-3">
                <button class="px-6 py-3.5 bg-white border border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-gray-lighter transition-all">Export Network</button>
                <button @click="referralModal = false" class="px-10 py-3.5 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 transform active:scale-95">Done</button>
            </div>
        </div>
    </div>
</div>
@endsection
