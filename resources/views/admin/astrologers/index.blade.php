@extends('admin.layouts.app')

@section('content')
<div x-data="{ astroModal: false, activeTab: 'profile', selectedAstro: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Astrologer Partners</h1>
            <p class="text-sm text-gray font-medium">Manage and verify astrologer professionals on the platform.</p>
        </div>
        <button class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-primary/20 hover:bg-primary-dark transition-all flex items-center gap-2">
            <i class="fas fa-user-tie"></i> Add New Astrologer
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-lighter mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Search Professionals</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-light"></i>
                    <input type="text" placeholder="Search by name, expertise or language..." class="w-full pl-11 pr-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Expertise</label>
                <select class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm appearance-none">
                    <option>All Specialities</option>
                    <option>Vedic Astrology</option>
                    <option>Tarot Card</option>
                    <option>Vastu</option>
                    <option>Numerology</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full bg-dark text-white py-3 rounded-xl font-bold hover:bg-black transition-all">Filter Experts</button>
            </div>
        </div>
    </div>

    <!-- Astrologers Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/50 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Astrologer</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Expertise & Exp.</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-center">Languages</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-center">Stats</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @php
                        $astrologers = [
                            ['id' => 101, 'name' => 'Pt. Rahul Vyas', 'speciality' => 'Vedic Astrology', 'exp' => '12 Years', 'langs' => 'Hindi, Sanskrit', 'orders' => '1,240', 'avg_rating' => '4.8', 'status' => 'Verified', 'revenue' => '₹4,50,000'],
                            ['id' => 102, 'name' => 'Aarti Sharma', 'speciality' => 'Tarot & Numerology', 'exp' => '8 Years', 'langs' => 'English, Hindi', 'orders' => '850', 'avg_rating' => '4.9', 'status' => 'Verified', 'revenue' => '₹2,80,000'],
                            ['id' => 103, 'name' => 'Vikram Joshi', 'speciality' => 'Vastu Expert', 'exp' => '15 Years', 'langs' => 'Hindi, Gujarati', 'orders' => '2,100', 'avg_rating' => '4.7', 'status' => 'Pending', 'revenue' => '₹7,20,000'],
                            ['id' => 104, 'name' => 'Meera Bai', 'speciality' => 'Palmistry', 'exp' => '20 Years', 'langs' => 'Sanskrit, Hindi', 'orders' => '3,450', 'avg_rating' => '5.0', 'status' => 'Verified', 'revenue' => '₹12,40,000'],
                            ['id' => 105, 'name' => 'Sanjay Dutt', 'speciality' => 'KP Astrology', 'exp' => '5 Years', 'langs' => 'English, Punjabi', 'orders' => '230', 'avg_rating' => '4.5', 'status' => 'Flagged', 'revenue' => '₹45,000'],
                            ['id' => 106, 'name' => 'Pooja Reddy', 'speciality' => 'Nadi Astrology', 'exp' => '10 Years', 'langs' => 'Telugu, Hindi', 'orders' => '1,100', 'avg_rating' => '4.8', 'status' => 'Verified', 'revenue' => '₹3,90,000'],
                            ['id' => 107, 'name' => 'Arjun Nair', 'speciality' => 'Psychic Reader', 'exp' => '7 Years', 'langs' => 'Malayalam, English', 'orders' => '640', 'avg_rating' => '4.6', 'status' => 'Verified', 'revenue' => '₹1,85,000'],
                            ['id' => 108, 'name' => 'Sneha Gupta', 'speciality' => 'Lal Kitab', 'exp' => '9 Years', 'langs' => 'Hindi, Marwari', 'orders' => '1,560', 'avg_rating' => '4.9', 'status' => 'Verified', 'revenue' => '₹5,60,000'],
                            ['id' => 109, 'name' => 'Kavita Joshi', 'speciality' => 'Medical Astrology', 'exp' => '11 Years', 'langs' => 'Hindi, Marathi', 'orders' => '1,280', 'avg_rating' => '4.7', 'status' => 'Verified', 'revenue' => '₹4,10,000'],
                            ['id' => 110, 'name' => 'Deepak Chopra', 'speciality' => 'Face Reading', 'exp' => '6 Years', 'langs' => 'English, Hindi', 'orders' => '420', 'avg_rating' => '4.4', 'status' => 'Inactive', 'revenue' => '₹95,000'],
                            ['id' => 111, 'name' => 'Shweta Tiwari', 'speciality' => 'Western Astrology', 'exp' => '13 Years', 'langs' => 'English, Bengali', 'orders' => '2,400', 'avg_rating' => '4.9', 'status' => 'Verified', 'revenue' => '₹8,40,000'],
                            ['id' => 112, 'name' => 'Pt. Gajanand', 'speciality' => 'Horary Astrology', 'exp' => '25 Years', 'langs' => 'Hindi, Sanskrit', 'orders' => '5,600', 'avg_rating' => '5.0', 'status' => 'Verified', 'revenue' => '₹22,00,000'],
                        ];
                    @endphp

                    @foreach($astrologers as $astro)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-primary/10 to-primary/30 p-1 border border-primary/20">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($astro['name']) }}&background=E8C461&color=fff&bold=true&rounded=true" class="w-full h-full object-cover rounded-xl" alt="">
                                </div>
                                <div>
                                    <div class="text-sm font-black text-dark group-hover:text-primary transition-colors">{{ $astro['name'] }}</div>
                                    <div class="text-[11px] font-bold text-gray-light">ID: #AST-{{ $astro['id'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-black text-dark">{{ $astro['speciality'] }}</div>
                            <div class="text-[10px] font-bold text-gray uppercase">{{ $astro['exp'] }} Exp</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex flex-wrap justify-center gap-1">
                                @foreach(explode(',', $astro['langs']) as $lang)
                                    <span class="px-2 py-0.5 bg-light border border-gray-lighter text-[9px] font-black text-gray rounded uppercase">{{ trim($lang) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1 text-xs font-black text-dark tracking-tighter">
                                <i class="fas fa-shopping-bag text-[10px] text-primary"></i> {{ $astro['orders'] }}
                            </div>
                            <div class="flex items-center justify-center gap-1 text-[10px] font-black text-accent mt-0.5">
                                <i class="fas fa-star text-[9px]"></i> {{ $astro['avg_rating'] }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($astro['status'] == 'Verified')
                                <span class="px-2.5 py-1 bg-success/10 text-success text-[10px] font-black rounded-lg uppercase tracking-widest border border-success/20">Verified</span>
                            @elseif($astro['status'] == 'Pending')
                                <span class="px-2.5 py-1 bg-accent/10 text-accent text-[10px] font-black rounded-lg uppercase tracking-widest border border-accent/20">Pending</span>
                            @elseif($astro['status'] == 'Flagged')
                                <span class="px-2.5 py-1 bg-danger/10 text-danger text-[10px] font-black rounded-lg uppercase tracking-widest border border-danger/20">Flagged</span>
                            @else
                                <span class="px-2.5 py-1 bg-gray-lighter text-gray text-[10px] font-black rounded-lg uppercase tracking-widest">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2 translate-x-2 group-hover:translate-x-0 transition-transform">
                                <button @click="selectedAstro = {{ json_encode($astro) }}; astroModal = true" class="w-9 h-9 rounded-xl bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="Manage Profile">
                                    <i class="fas fa-user-edit text-xs"></i>
                                </button>
                                <button class="w-9 h-9 rounded-xl bg-info/10 text-info hover:bg-info hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="View Portfolio">
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </button>
                                <button class="w-9 h-9 rounded-xl bg-danger/10 text-danger hover:bg-danger hover:text-white transition-all flex items-center justify-center transform active:scale-90" title="Restrict">
                                    <i class="fas fa-ban text-xs"></i>
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
            <div class="text-[11px] font-black text-gray uppercase tracking-widest">12 of 150 Expert Partners</div>
            <div class="flex items-center gap-2">
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center text-gray hover:bg-primary hover:text-white transition-all transform active:scale-90 shadow-sm"><i class="fas fa-chevron-left text-xs"></i></button>
                <div class="flex items-center bg-white border border-gray-lighter rounded-xl px-2 h-10">
                    <button class="w-7 h-7 bg-primary text-white rounded-lg text-xs font-black shadow-sm">1</button>
                    <button class="w-7 h-7 text-dark rounded-lg text-xs font-black hover:bg-light transition-colors ml-1">2</button>
                    <button class="w-7 h-7 text-dark rounded-lg text-xs font-black hover:bg-light transition-colors ml-1">3</button>
                </div>
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center text-gray hover:bg-primary hover:text-white transition-all transform active:scale-90 shadow-sm"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Multi-section Astrologer Modal -->
    <div x-show="astroModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/80 shadow-2xl backdrop-blur-xl"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-4xl rounded-[40px] shadow-[0_30px_60px_rgba(0,0,0,0.5)] overflow-hidden border border-white/20" @click.away="astroModal = false">
            <div class="flex h-[600px]">
                <!-- Modal Sidebar/Tabs -->
                <div class="w-64 bg-light/30 border-r border-gray-lighter p-8 flex flex-col gap-2">
                    <div class="mb-8 flex flex-col items-center text-center">
                        <div class="w-20 h-20 rounded-3xl bg-primary/10 p-1 border border-primary/20 mb-4 shadow-inner">
                            <img src="https://ui-avatars.com/api/?name=Astro&background=E8C461&color=fff&bold=true&rounded=true" class="w-full h-full object-cover rounded-2xl" x-bind:src="'https://ui-avatars.com/api/?name=' + urlencode(selectedAstro.name || '') + '&background=E8C461&color=fff&bold=true&rounded=true'">
                        </div>
                        <h4 class="text-lg font-black text-dark" x-text="selectedAstro.name"></h4>
                        <span class="text-[10px] font-black text-primary uppercase tracking-widest mt-1" x-text="selectedAstro.speciality"></span>
                    </div>

                    <button @click="activeTab = 'profile'" :class="activeTab === 'profile' ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-gray hover:bg-white hover:text-dark'" class="w-full text-left px-5 py-3.5 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all flex items-center gap-3">
                        <i class="fas fa-user-circle"></i> Basic Info
                    </button>
                    <button @click="activeTab = 'docs'" :class="activeTab === 'docs' ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-gray hover:bg-white hover:text-dark'" class="w-full text-left px-5 py-3.5 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all flex items-center gap-3">
                        <i class="fas fa-file-contract"></i> Verification
                    </button>
                    <button @click="activeTab = 'bank'" :class="activeTab === 'bank' ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-gray hover:bg-white hover:text-dark'" class="w-full text-left px-5 py-3.5 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all flex items-center gap-3">
                        <i class="fas fa-university"></i> Bank Details
                    </button>
                    <button @click="activeTab = 'stats'" :class="activeTab === 'stats' ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-gray hover:bg-white hover:text-dark'" class="w-full text-left px-5 py-3.5 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all flex items-center gap-3">
                        <i class="fas fa-chart-pie"></i> Performance
                    </button>

                    <div class="mt-auto pt-6 border-t border-gray-lighter">
                        <button class="w-full bg-danger/10 text-danger py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-danger hover:text-white transition-all">Report Abuse</button>
                    </div>
                </div>

                <!-- Modal Content Area -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="p-8 border-b border-gray-lighter flex justify-between items-center bg-white">
                        <h3 class="text-2xl font-black text-dark uppercase tracking-tighter" x-text="activeTab.toUpperCase() + ' SETTINGS'"></h3>
                        <button @click="astroModal = false" class="w-10 h-10 bg-light hover:bg-gray-lighter text-gray rounded-xl flex items-center justify-center transition-all transform hover:rotate-90">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="p-10 flex-1 overflow-y-auto custom-scrollbar">
                        <!-- Profile Tab -->
                        <div x-show="activeTab === 'profile'" class="space-y-8">
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <label class="block text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Full Display Name</label>
                                    <input type="text" x-model="selectedAstro.name" class="w-full px-5 py-3.5 bg-light border border-gray-lighter rounded-2xl text-sm font-bold text-dark focus:outline-none focus:border-primary/50">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Experience</label>
                                    <input type="text" x-model="selectedAstro.exp" class="w-full px-5 py-3.5 bg-light border border-gray-lighter rounded-2xl text-sm font-bold text-dark">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-[10px] font-black text-gray uppercase mb-2 tracking-widest">About / Bio</label>
                                    <textarea rows="4" class="w-full px-5 py-3.5 bg-light border border-gray-lighter rounded-2xl text-sm font-bold text-dark focus:outline-none focus:border-primary/50">Expert in Vedic rituals and predictive astrology with over a decade of helping clients navigate life's challenges through celestial guidance.</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Docs Tab -->
                        <div x-show="activeTab === 'docs'" class="space-y-6">
                            <div class="p-6 bg-success/5 border border-success/10 rounded-3xl flex items-center justify-between mb-8">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-success/20 text-success rounded-2xl flex items-center justify-center"><i class="fas fa-check-double text-xl"></i></div>
                                    <div>
                                        <div class="text-sm font-black text-dark">KYC Status: Verified</div>
                                        <div class="text-[10px] font-bold text-gray-light uppercase">Last update: 15 Jan 2024</div>
                                    </div>
                                </div>
                                <button class="text-[10px] font-black text-primary underline uppercase tracking-widest">Re-verify</button>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-5 border border-gray-lighter rounded-3xl flex items-center justify-between hover:border-primary/20 transition-all cursor-pointer">
                                    <div class="flex items-center gap-4">
                                        <i class="far fa-id-card text-2xl text-gray-light"></i>
                                        <div class="text-[11px] font-black text-dark">Aadhar Card.pdf</div>
                                    </div>
                                    <i class="fas fa-download text-gray-light hover:text-primary"></i>
                                </div>
                                <div class="p-5 border border-gray-lighter rounded-3xl flex items-center justify-between hover:border-primary/20 transition-all cursor-pointer">
                                    <div class="flex items-center gap-4">
                                        <i class="far fa-file-alt text-2xl text-gray-light"></i>
                                        <div class="text-[11px] font-black text-dark">Vedic_Cert.jpg</div>
                                    </div>
                                    <i class="fas fa-download text-gray-light hover:text-primary"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Tab -->
                        <div x-show="activeTab === 'bank'" class="grid grid-cols-2 gap-8">
                            <div class="col-span-2 p-8 bg-linear-to-br from-dark to-black rounded-[32px] text-white shadow-2xl relative overflow-hidden group">
                                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="flex justify-between items-start mb-12">
                                    <i class="fas fa-sim-card text-4xl text-warning/40"></i>
                                    <i class="fab fa-cc-visa text-4xl opacity-50"></i>
                                </div>
                                <div class="text-2xl font-black tracking-[0.2em] mb-8">HDFC BANK **** 4492</div>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <div class="text-[8px] font-black text-white/40 uppercase mb-1">Account Holder</div>
                                        <div class="text-sm font-black tracking-widest uppercase" x-text="selectedAstro.name"></div>
                                    </div>
                                    <div>
                                        <div class="text-[8px] font-black text-white/40 uppercase mb-1">IFSC CODE</div>
                                        <div class="text-sm font-black tracking-widest uppercase">HDFC0001245</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Tab -->
                        <div x-show="activeTab === 'stats'" class="space-y-8">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="bg-primary/5 p-6 rounded-3xl border border-primary/10 text-center">
                                    <div class="text-[10px] font-black text-primary uppercase mb-1 tracking-widest">Platform Revenue</div>
                                    <div class="text-2xl font-black text-dark" x-text="selectedAstro.revenue"></div>
                                </div>
                                <div class="bg-info/5 p-6 rounded-3xl border border-info/10 text-center">
                                    <div class="text-[10px] font-black text-info uppercase mb-1 tracking-widest">Total Consultations</div>
                                    <div class="text-2xl font-black text-dark" x-text="selectedAstro.orders"></div>
                                </div>
                            </div>
                            <div class="p-8 border border-gray-lighter rounded-[32px]">
                                <h4 class="text-xs font-black text-dark uppercase mb-6 tracking-widest">Recent Activity Trend</h4>
                                <div class="flex items-end gap-3 h-32 px-4">
                                    @foreach([40, 70, 55, 90, 65, 80, 100] as $h)
                                        <div class="flex-1 bg-primary/20 rounded-t-lg transition-all hover:bg-primary" style="height: {{ $h }}%"></div>
                                    @endforeach
                                </div>
                                <div class="flex justify-between mt-4 px-2">
                                    <div class="text-[8px] font-black text-gray uppercase">Mon</div>
                                    <div class="text-[8px] font-black text-gray uppercase">Tue</div>
                                    <div class="text-[8px] font-black text-gray uppercase">Wed</div>
                                    <div class="text-[8px] font-black text-gray uppercase">Thu</div>
                                    <div class="text-[8px] font-black text-gray uppercase">Fri</div>
                                    <div class="text-[8px] font-black text-gray uppercase">Sat</div>
                                    <div class="text-[8px] font-black text-gray uppercase">Sun</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 bg-light/30 border-t border-gray-lighter flex justify-end gap-4">
                         <button @click="astroModal = false" class="px-8 py-4 bg-white border border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-gray-lighter transition-all font-mono">Cancel Changes</button>
                         <button class="px-12 py-4 bg-primary text-white text-[11px] font-black uppercase rounded-2xl hover:bg-primary-dark transition-all shadow-xl shadow-primary/20 transform active:scale-95">Save & Verify Profile</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function urlencode(str) {
        return encodeURIComponent(str);
    }
</script>
@endsection
