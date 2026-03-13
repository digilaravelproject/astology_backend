@extends('admin.layouts.app')

@section('content')
<div x-data="{ 
    features: ['Unlimited Chat Sessions', 'Priority Astrologer Access', 'Daily Horoscope Alerts'],
    newFeature: '',
    regularPrice: 599,
    discount: 10,
    get finalPrice() {
        return Math.round(this.regularPrice - (this.regularPrice * this.discount / 100));
    },
    addFeature() {
        if (this.newFeature.trim() !== '') {
            this.features.push(this.newFeature.trim());
            this.newFeature = '';
        }
    },
    removeFeature(index) {
        this.features.splice(index, 1);
    }
}">
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.plans.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Subscription Ledger
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Plan Architect</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Engineering value propositions for the spiritual marketplace.</p>
        </div>
        <div class="flex gap-4">
            <button class="px-8 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all">Discard Draft</button>
            <button class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-primary transition-all shadow-xl shadow-dark/20 flex items-center gap-3">
                <i class="fas fa-save"></i> Deploy Logic
            </button>
        </div>
    </div>

    <!-- Plan Form -->
    <form action="#" method="POST" class="max-w-[1100px]">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
            
            <!-- Main Config -->
            <div class="lg:col-span-3 space-y-10">
                
                <!-- Section 1: Monetization Architecture -->
                <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden group hover:shadow-xl transition-all duration-500">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px] group-hover:scale-110 transition-transform"></div>
                    <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                        <span class="w-8 h-px bg-primary/30"></span> 01. Monetization Core
                    </h2>
                    
                    <div class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="group">
                                <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3 group-focus-within:text-dark">Tier Identifier</label>
                                <input type="text" placeholder="e.g. Platinum Elite..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-black text-dark focus:bg-white focus:border-primary/20 transition-all">
                            </div>
                            <div class="group">
                                <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Billing Cycle</label>
                                <div class="relative">
                                    <select class="w-full bg-light/30 border-none px-6 py-5 rounded-[24px] text-xs font-black text-dark appearance-none cursor-pointer focus:bg-white transition-all">
                                        <option>Monthly (30 Days)</option>
                                        <option>Quarterly (90 Days)</option>
                                        <option>Annually (365 Days)</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none"></i>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-light/20 p-8 rounded-[32px] border border-gray-lighter">
                            <div>
                                <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Standard MSRP</label>
                                <div class="relative mt-1">
                                    <span class="absolute left-6 top-1/2 -translate-y-1/2 text-gray font-black">₹</span>
                                    <input type="number" x-model="regularPrice" class="w-full bg-white border-none pl-10 pr-6 py-4 rounded-2xl text-sm font-black text-dark focus:ring-2 focus:ring-primary/20">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Loyalty Rebate (%)</label>
                                <div class="relative mt-1">
                                    <input type="number" x-model="discount" class="w-full bg-white border-none px-6 py-4 rounded-2xl text-sm font-black text-dark focus:ring-2 focus:ring-primary/20">
                                    <span class="absolute right-6 top-1/2 -translate-y-1/2 text-gray font-black">%</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Computed Settlement</label>
                                <div class="mt-1 h-[52px] flex items-center px-6 bg-dark text-primary text-sm font-black rounded-2xl tracking-tighter">
                                    ₹ <span x-text="finalPrice"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Service Deliverables -->
                <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                    <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                        <span class="w-8 h-px bg-info/30"></span> 02. Service Deliverables
                    </h2>
                    
                    <div class="space-y-6">
                        <template x-for="(feature, index) in features" :key="index">
                            <div class="flex items-center gap-4 group">
                                <div class="w-12 h-12 rounded-2xl bg-success/5 border border-success/10 text-success flex items-center justify-center text-xs">
                                    <i class="fas fa-check-double scale-90"></i>
                                </div>
                                <div class="flex-1 bg-light/30 px-6 py-4 rounded-[20px] text-xs font-bold text-dark border border-transparent group-hover:border-info/20 group-hover:bg-white transition-all" x-text="feature"></div>
                                <button @click.prevent="removeFeature(index)" class="w-12 h-12 bg-white border border-gray-lighter text-gray hover:text-danger hover:border-danger rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0 transition-all">
                                    <i class="fas fa-trash-alt scale-75"></i>
                                </button>
                            </div>
                        </template>

                        <div class="mt-8 relative group">
                            <i class="fas fa-plus absolute left-6 top-1/2 -translate-y-1/2 text-gray scale-75 group-focus-within:text-dark transition-colors"></i>
                            <input type="text" x-model="newFeature" @keydown.enter.prevent="addFeature" placeholder="Define new benefit..." class="w-full bg-light/50 border-2 border-dashed border-gray-lighter pl-14 pr-6 py-5 rounded-[24px] text-xs font-black text-dark focus:bg-white focus:border-dark focus:ring-0 transition-all placeholder:italic">
                            <button @click.prevent="addFeature" class="absolute right-4 top-1/2 -translate-y-1/2 bg-dark text-white px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-info transition-all opacity-0 group-focus-within:opacity-100 transform group-focus-within:translate-y-[-50%]">Inject Benefit</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Logic -->
            <div class="lg:col-span-2 space-y-10">
                <!-- Visual Branding -->
                <div class="bg-white p-8 rounded-[48px] border border-gray-lighter shadow-sm">
                    <h2 class="text-[10px] font-black text-dark uppercase tracking-[0.3em] mb-6 whitespace-nowrap overflow-hidden">Tier Visualization</h2>
                    <div class="p-8 rounded-[32px] bg-linear-to-br from-dark to-black text-white relative overflow-hidden aspect-4/3 flex flex-col justify-between">
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary/20 blur-[100px]"></div>
                        <div class="flex justify-between items-start z-10">
                            <div>
                                <div class="text-[9px] font-black text-primary uppercase tracking-[0.4em] mb-2">Selected Tier</div>
                                <h3 class="text-2xl font-black italic tracking-tighter uppercase">Elite Access</h3>
                            </div>
                            <div class="w-12 h-12 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center">
                                <i class="fas fa-gem text-primary"></i>
                            </div>
                        </div>
                        <div class="z-10">
                            <div class="text-4xl font-black tracking-tighter mb-1">₹ <span x-text="finalPrice"></span></div>
                            <div class="text-[10px] font-black text-white/50 uppercase tracking-widest italic">Billed every 30 days</div>
                        </div>
                    </div>
                </div>

                <!-- Strategic Settings -->
                <div class="bg-white p-8 rounded-[48px] border border-gray-lighter shadow-sm space-y-8">
                    <div>
                        <h2 class="text-[10px] font-black text-dark uppercase tracking-[0.3em] mb-4">Availability Engine</h2>
                        <div class="flex items-center justify-between p-5 bg-light/30 rounded-3xl border border-gray-lighter group hover:border-success/30 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-success/10 text-success flex items-center justify-center text-[10px]">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <span class="text-[10px] font-black text-dark uppercase tracking-widest">Publically Tradable</span>
                            </div>
                            <button type="button" class="w-12 h-7 bg-success rounded-full relative p-1 transition-all">
                                <div class="w-5 h-5 bg-white rounded-full ml-auto shadow-sm"></div>
                            </button>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-[10px] font-black text-dark uppercase tracking-[0.3em] mb-4">Trial Dynamics</h2>
                        <div class="flex items-center justify-between p-5 bg-light/30 rounded-3xl border border-gray-lighter group hover:border-info/30 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-info/10 text-info flex items-center justify-center text-[10px]">
                                    <i class="fas fa-vial"></i>
                                </div>
                                <span class="text-[10px] font-black text-dark uppercase tracking-widest">Enable 7-Day Trial</span>
                            </div>
                            <button type="button" class="w-12 h-7 bg-gray-lighter rounded-full relative p-1 transition-all">
                                <div class="w-5 h-5 bg-white rounded-full shadow-sm"></div>
                            </button>
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <div class="p-6 bg-warning/5 rounded-3xl border border-warning/10 border-dashed">
                             <div class="text-[10px] font-black text-warning uppercase tracking-widest mb-2 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle"></i> Strategic Note
                             </div>
                             <p class="text-[10px] font-medium text-gray leading-relaxed italic">Once deployed, price modifications for existing subscribers will require a manual settlement migration.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
