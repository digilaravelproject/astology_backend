@extends('admin.layouts.app')

@section('content')
<div x-data="{ tab: 'general' }">
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Command Center</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Governing platform logic, financial protocols, and administrative access.</p>
        </div>
        <div class="flex gap-4">
            <button class="px-8 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all">Audit Logs</button>
            <button class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-primary transition-all shadow-xl shadow-dark/20 flex items-center gap-3">
                <i class="fas fa-save"></i> Commit Changes
            </button>
        </div>
    </div>

    <!-- Governance Tabs -->
    <div class="bg-white rounded-[48px] border border-gray-lighter shadow-sm overflow-hidden min-h-[600px] flex flex-col lg:flex-row">
        <!-- Sidebar Navigation -->
        <div class="lg:w-80 bg-light/30 border-r border-gray-lighter p-8 space-y-2">
            <button @click="tab = 'general'" :class="tab === 'general' ? 'bg-white text-primary shadow-sm border-gray-lighter' : 'text-gray hover:bg-white/50 border-transparent'" class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border">
                <i class="fas fa-cog w-5 text-center"></i> Platform Core
            </button>
            <button @click="tab = 'commission'" :class="tab === 'commission' ? 'bg-white text-primary shadow-sm border-gray-lighter' : 'text-gray hover:bg-white/50 border-transparent'" class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border">
                <i class="fas fa-percentage w-5 text-center"></i> Commission Logic
            </button>
            <button @click="tab = 'wallet'" :class="tab === 'wallet' ? 'bg-white text-primary shadow-sm border-gray-lighter' : 'text-gray hover:bg-white/50 border-transparent'" class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border">
                <i class="fas fa-wallet w-5 text-center"></i> Financial Rules
            </button>
            <button @click="tab = 'payment'" :class="tab === 'payment' ? 'bg-white text-primary shadow-sm border-gray-lighter' : 'text-gray hover:bg-white/50 border-transparent'" class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border">
                <i class="fas fa-credit-card w-5 text-center"></i> Payment Gateway
            </button>
            <button @click="tab = 'astro'" :class="tab === 'astro' ? 'bg-white text-primary shadow-sm border-gray-lighter' : 'text-gray hover:bg-white/50 border-transparent'" class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border">
                <i class="fas fa-star w-5 text-center"></i> Astro Governance
            </button>
            <button @click="tab = 'security'" :class="tab === 'security' ? 'bg-white text-primary shadow-sm border-gray-lighter' : 'text-gray hover:bg-white/50 border-transparent'" class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border">
                <i class="fas fa-shield-alt w-5 text-center"></i> Platform Guard
            </button>
            <button @click="tab = 'admin'" :class="tab === 'admin' ? 'bg-white text-primary shadow-sm border-gray-lighter' : 'text-gray hover:bg-white/50 border-transparent'" class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border">
                <i class="fas fa-users-cog w-5 text-center"></i> RBAC Management
            </button>
        </div>

        <!-- Content Area -->
        <div class="flex-1 p-12">
            <!-- General Settings -->
            <div x-show="tab === 'general'" class="space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div>
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Application Identity</label>
                        <input type="text" value="Astology Premium" class="w-full bg-light/30 border-2 border-transparent px-6 py-4 rounded-2xl text-xs font-black text-dark focus:bg-white focus:border-primary/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Support Ecosystem</label>
                        <input type="email" value="ops@astologyapp.com" class="w-full bg-light/30 border-2 border-transparent px-6 py-4 rounded-2xl text-xs font-black text-dark focus:bg-white focus:border-primary/20 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">SEO Meta Base</label>
                    <textarea rows="3" class="w-full bg-light/30 border-2 border-transparent px-6 py-4 rounded-2xl text-xs font-bold text-dark focus:bg-white focus:border-primary/20 transition-all">Connect with India's top astrologers for personalized readings, daily horoscopes, and spiritual guidance.</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-6 bg-light/30 rounded-3xl border border-gray-lighter text-center">
                        <div class="w-12 h-12 bg-white rounded-xl mx-auto mb-3 flex items-center justify-center shadow-sm text-primary"><i class="fas fa-image"></i></div>
                        <div class="text-[9px] font-black text-dark uppercase tracking-widest">Favicon Asset</div>
                        <button class="mt-2 text-[8px] font-black text-primary uppercase underline">Replace</button>
                    </div>
                    <div class="p-6 bg-light/30 rounded-3xl border border-gray-lighter text-center">
                        <div class="w-12 h-12 bg-white rounded-xl mx-auto mb-3 flex items-center justify-center shadow-sm text-primary"><i class="fas fa-signature"></i></div>
                        <div class="text-[9px] font-black text-dark uppercase tracking-widest">Logo Branding</div>
                        <button class="mt-2 text-[8px] font-black text-primary uppercase underline">Replace</button>
                    </div>
                    <div class="p-6 bg-light/30 rounded-3xl border border-gray-lighter text-center">
                        <div class="w-12 h-12 bg-white rounded-xl mx-auto mb-3 flex items-center justify-center shadow-sm text-primary"><i class="fas fa-share-alt"></i></div>
                        <div class="text-[9px] font-black text-dark uppercase tracking-widest">Social Preview</div>
                        <button class="mt-2 text-[8px] font-black text-primary uppercase underline">Replace</button>
                    </div>
                </div>
            </div>

            <!-- Commission Logic -->
            <div x-show="tab === 'commission'" class="space-y-10">
                <div class="p-10 bg-primary/5 rounded-[40px] border border-primary/10 flex flex-col items-center text-center">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center text-3xl text-primary shadow-xl mb-6"><i class="fas fa-percentage"></i></div>
                    <h3 class="text-xl font-black text-dark uppercase tracking-tighter mb-2">Global Commission Intake</h3>
                    <p class="text-xs text-gray font-medium max-w-[400px] mb-8">Set the baseline percentage the platform retains from every spiritual transaction.</p>
                    <div class="flex items-center gap-4">
                        <div class="text-5xl font-black text-dark tracking-tighter">20<span class="text-primary">%</span></div>
                        <div class="flex flex-col gap-1">
                            <button class="w-8 h-8 bg-white border border-gray-lighter rounded-lg text-[10px] hover:bg-primary hover:text-white transition-all"><i class="fas fa-chevron-up"></i></button>
                            <button class="w-8 h-8 bg-white border border-gray-lighter rounded-lg text-[10px] hover:bg-primary hover:text-white transition-all"><i class="fas fa-chevron-down"></i></button>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <h4 class="text-[10px] font-black text-gray uppercase tracking-widest px-4">Segment Based overrides</h4>
                    <div class="bg-light/30 p-6 rounded-3xl border border-gray-lighter flex justify-between items-center">
                        <span class="text-xs font-black text-dark uppercase tracking-widest">E-commerce Marketplace</span>
                        <div class="flex items-center gap-3">
                            <input type="number" value="15" class="w-16 bg-white border border-gray-lighter rounded-xl px-2 py-2 text-center font-black text-xs">
                            <span class="text-xs font-bold text-gray">%</span>
                        </div>
                    </div>
                    <div class="bg-light/30 p-6 rounded-3xl border border-gray-lighter flex justify-between items-center">
                        <span class="text-xs font-black text-dark uppercase tracking-widest">Premium Yearly Plans</span>
                        <div class="flex items-center gap-3">
                            <input type="number" value="10" class="w-16 bg-white border border-gray-lighter rounded-xl px-2 py-2 text-center font-black text-xs">
                            <span class="text-xs font-bold text-gray">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RBAC Management -->
            <div x-show="tab === 'admin'" class="space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-dark uppercase tracking-tighter">System Governance</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Administrative Role Based Access Control</p>
                    </div>
                    <button class="px-6 py-3 bg-dark text-white text-[10px] font-black uppercase rounded-2xl hover:bg-primary transition-all shadow-lg flex items-center gap-2">
                        <i class="fas fa-user-shield"></i> Invite Operator
                    </button>
                </div>
                <div class="bg-light/20 rounded-[32px] border border-gray-lighter overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-light/50 border-b border-gray-lighter">
                            <tr>
                                <th class="px-6 py-4 text-[9px] font-black text-gray uppercase tracking-widest text-left">Operator Identity</th>
                                <th class="px-6 py-4 text-[9px] font-black text-gray uppercase tracking-widest text-left">Clearance Level</th>
                                <th class="px-6 py-4 text-[9px] font-black text-gray uppercase tracking-widest text-right">Ops</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-lighter">
                            @php
                                $admins = [
                                    ['name' => 'Super Admin', 'email' => 'master@astology.com', 'role' => 'System Architect'],
                                    ['name' => 'Rahul Sharma', 'email' => 'rahul.ops@astology.com', 'role' => 'Financial Auditor'],
                                    ['name' => 'Meena Iyer', 'email' => 'meena.content@astology.com', 'role' => 'Editorial Lead'],
                                    ['name' => 'Vikram Goel', 'email' => 'vikram.support@astology.com', 'role' => 'Escalation Mgr'],
                                ];
                            @endphp
                            @foreach($admins as $admin)
                            <tr class="hover:bg-white transition-all">
                                <td class="px-6 py-4">
                                    <div class="text-xs font-black text-dark">{{ $admin['name'] }}</div>
                                    <div class="text-[8px] font-bold text-gray mt-0.5">{{ $admin['email'] }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-dark text-white text-[8px] font-black uppercase rounded-full tracking-widest">{{ $admin['role'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="w-8 h-8 rounded-lg border border-gray-lighter text-gray hover:text-dark hover:border-dark transition-all"><i class="fas fa-edit text-[10px]"></i></button>
                                    <button class="w-8 h-8 rounded-lg border border-gray-lighter text-gray hover:text-danger hover:border-danger transition-all ml-1"><i class="fas fa-trash-alt text-[10px]"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Placeholder for other tabs -->
            <div x-show="['wallet', 'payment', 'astro', 'security'].includes(tab)" class="py-20 text-center flex flex-col items-center justify-center">
                <div class="w-24 h-24 bg-light rounded-full flex items-center justify-center mb-6 text-gray-lighter text-4xl">
                    <i class="fas fa-microchip"></i>
                </div>
                <h3 class="text-xl font-black text-dark uppercase tracking-tighter italic">Logic Cluster Active</h3>
                <p class="text-sm text-gray font-medium max-w-[360px] leading-relaxed mt-2 opacity-60">High-sensitivity configuration parameters are currently in read-only audit mode for the active session.</p>
                <button class="mt-8 px-8 py-3.5 bg-light border border-gray-lighter text-dark text-[10px] font-black uppercase rounded-2xl hover:bg-dark hover:text-white transition-all">Request Write Access</button>
            </div>
        </div>
    </div>
</div>
@endsection
