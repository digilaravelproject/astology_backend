@extends('admin.layouts.app')

@section('content')
<div x-data="{ 
    tab: 'global',
    showAddPackageModal: false,
    showEditPackageModal: false,
    showAssignModal: false,
    currentPackage: { id: '', name: '', default_amount: '', default_duration: '', is_default: false },
    currentAssign: { astrologer_id: '', amount: '', duration: '', commission_percentage: '' }
}">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-text-primary tracking-tight">Prepaid Package Session System</h1>
            <p class="text-sm text-text-muted mt-1">Manage global prepaid packages, configure astrologer rates and custom commission split overrides, and track platform revenue.</p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500 text-lg"></i>
                <span class="text-sm font-semibold text-green-800">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                <span class="text-sm font-semibold text-red-800">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Main Grid Layout -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden min-h-[600px] flex flex-col lg:flex-row">
        <!-- Sidebar Tabs -->
        <div class="lg:w-72 bg-light/20 border-r border-gray-200 p-6 space-y-1">
            <button @click="tab = 'global'" :class="tab === 'global' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-box w-5 text-center"></i> Global Packages
            </button>
            <button @click="tab = 'astrologers'" :class="tab === 'astrologers' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-user-tag w-5 text-center"></i> Astrologer Overrides
            </button>
            <button @click="tab = 'purchases'" :class="tab === 'purchases' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-history w-5 text-center"></i> Sales Ledger & Splits
            </button>
        </div>

        <!-- Content Area -->
        <div class="flex-1 p-8 lg:p-10">

            <!-- TAB 1: Global Packages -->
            <div x-show="tab === 'global'" class="space-y-6">
                <div class="flex justify-between items-center border-b pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-text-primary">System Packages</h3>
                        <p class="text-xs text-text-muted mt-1">Configure global package levels for duration and price templates.</p>
                    </div>
                    <button @click="showAddPackageModal = true" class="px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-primary-dark transition-all flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add Package
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Name</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Default Price</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Duration</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Status</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($packages as $package)
                                <tr class="border-b border-gray-50 hover:bg-light/10 transition-all">
                                    <td class="py-4 px-4 text-sm font-semibold text-text-primary">{{ $package->name }}</td>
                                    <td class="py-4 px-4 text-sm text-text-secondary">{{ number_format($package->default_amount, 2) }}</td>
                                    <td class="py-4 px-4 text-sm text-text-secondary">{{ number_format($package->default_duration / 60, 1) }} mins</td>
                                    <td class="py-4 px-4">
                                        @if($package->is_default)
                                            <span class="px-2.5 py-1 bg-green-50 text-green-700 text-xs font-bold rounded-lg border border-green-100">Default Package</span>
                                        @else
                                            <span class="text-xs text-text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button @click="
                                            currentPackage = { 
                                                id: '{{ $package->id }}', 
                                                name: '{{ $package->name }}', 
                                                default_amount: '{{ $package->default_amount }}', 
                                                default_duration: '{{ $package->default_duration }}', 
                                                is_default: {{ $package->is_default ? 'true' : 'false' }} 
                                            }; 
                                            showEditPackageModal = true;
                                        " class="text-primary hover:text-primary-dark font-bold text-xs">Edit</button>
                                        
                                        @if(!$package->is_default)
                                            <form action="{{ route('admin.packages.destroy', $package->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this global package?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-sm text-text-muted">No global packages configured. Click Add Package to get started.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: Astrologer Overrides -->
            <div x-show="tab === 'astrologers'" class="space-y-6">
                <div class="flex justify-between items-center border-b pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-text-primary">Astrologer Customized Rates</h3>
                        <p class="text-xs text-text-muted mt-1">Configure individual override rates and commissions for specific astrologers.</p>
                    </div>
                    <button @click="showAssignModal = true" class="px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-primary-dark transition-all flex items-center gap-2">
                        <i class="fas fa-user-plus"></i> Configure Custom Rate
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Astrologer</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Custom Price</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Duration</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Commission Override</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($astrologerPackages as $ap)
                                <tr class="border-b border-gray-50 hover:bg-light/10 transition-all">
                                    <td class="py-4 px-4 text-sm font-semibold text-text-primary">{{ $ap->astrologer->name }}</td>
                                    <td class="py-4 px-4 text-sm text-text-secondary">{{ number_format($ap->amount, 2) }}</td>
                                    <td class="py-4 px-4 text-sm text-text-secondary">{{ number_format($ap->duration / 60, 1) }} mins</td>
                                    <td class="py-4 px-4">
                                        @if(!is_null($ap->commission_percentage))
                                            <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-bold rounded-lg">{{ $ap->commission_percentage }}% (Astro Share)</span>
                                        @else
                                            <span class="text-xs text-text-muted">Global Fallback (50%)</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button @click="
                                            currentAssign = { 
                                                astrologer_id: '{{ $ap->astrologer_id }}', 
                                                amount: '{{ $ap->amount }}', 
                                                duration: '{{ $ap->duration }}', 
                                                commission_percentage: '{{ $ap->commission_percentage }}' 
                                            }; 
                                            showAssignModal = true;
                                        " class="text-primary hover:text-primary-dark font-bold text-xs">Edit Override</button>
                                        
                                        <form action="{{ route('admin.packages.remove-override', $ap->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to remove this custom rate and fallback to the global default?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-sm text-text-muted">No custom overrides set. All astrologers currently inherit the global default package.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: Sales Ledger & Splits -->
            <div x-show="tab === 'purchases'" class="space-y-6">
                <div class="border-b pb-4">
                    <h3 class="text-lg font-bold text-text-primary">Sales Ledger & Platform Splits</h3>
                    <p class="text-xs text-text-muted mt-1">Real-time ledger audit trail showing transaction splits between platform and astrologers.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">User</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Astrologer</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Purchase Price</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Astro Share</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Platform Revenue</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Remaining Time</th>
                                <th class="py-3.5 px-4 text-xs font-bold text-text-secondary uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                <tr class="border-b border-gray-50 hover:bg-light/10 transition-all">
                                    <td class="py-4 px-4 text-sm text-text-secondary">{{ $purchase->user->name ?? 'Deleted User' }}</td>
                                    <td class="py-4 px-4 text-sm font-semibold text-text-primary">{{ $purchase->astrologer->name ?? 'Deleted Astrologer' }}</td>
                                    <td class="py-4 px-4 text-sm font-bold text-text-primary">{{ number_format($purchase->purchase_price, 2) }}</td>
                                    <td class="py-4 px-4 text-sm text-green-600 font-semibold">{{ number_format($purchase->astrologer_earnings, 2) }} <span class="text-[10px] text-text-muted">({{ $purchase->commission_percentage }}%)</span></td>
                                    <td class="py-4 px-4 text-sm text-primary font-semibold">{{ number_format($purchase->admin_earnings, 2) }} <span class="text-[10px] text-text-muted">({{ 100 - $purchase->commission_percentage }}%)</span></td>
                                    <td class="py-4 px-4 text-sm">
                                        @if($purchase->status === 'exhausted')
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs font-bold rounded-lg border border-gray-200">Exhausted</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg border border-blue-100">{{ number_format($purchase->remaining_duration / 60, 1) }} mins left</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-xs text-text-muted">{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-sm text-text-muted">No packages purchased yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $purchases->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL: Add Global Package -->
    <div x-show="showAddPackageModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/50" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 space-y-6">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-bold text-text-primary">Create Global Package</h3>
                <button @click="showAddPackageModal = false" class="text-text-muted hover:text-text-primary"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="{{ route('admin.packages.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Package Name</label>
                    <input type="text" name="name" required placeholder="e.g. Silver Consultation Pack" class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Price</label>
                        <input type="number" step="0.01" name="default_amount" required placeholder="e.g. 50.00" class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Duration (Minutes)</label>
                        <input type="number" name="default_duration_minutes" required placeholder="e.g. 60 for 1 hr" class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_default" value="1" id="add_is_default" class="rounded text-primary border-gray-300 focus:ring-primary">
                    <label for="add_is_default" class="text-sm font-semibold text-text-secondary">Mark as default package for new registrations</label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="showAddPackageModal = false" class="px-5 py-2.5 border text-text-secondary rounded-xl hover:bg-light transition-all text-sm font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl hover:bg-primary-dark transition-all text-sm font-bold">Save Package</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Edit Global Package -->
    <div x-show="showEditPackageModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/50" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 space-y-6">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-bold text-text-primary">Edit Global Package</h3>
                <button @click="showEditPackageModal = false" class="text-text-muted hover:text-text-primary"><i class="fas fa-times"></i></button>
            </div>
            
            <form :action="'/admin/packages/' + currentPackage.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Package Name</label>
                    <input type="text" name="name" x-model="currentPackage.name" required class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Price</label>
                        <input type="number" step="0.01" name="default_amount" x-model="currentPackage.default_amount" required class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Duration (Minutes)</label>
                        <input type="number" name="default_duration_minutes" :value="currentPackage.default_duration / 60" required class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_default" value="1" id="edit_is_default" x-model="currentPackage.is_default" class="rounded text-primary border-gray-300 focus:ring-primary">
                    <label for="edit_is_default" class="text-sm font-semibold text-text-secondary">Mark as default package for new registrations</label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="showEditPackageModal = false" class="px-5 py-2.5 border text-text-secondary rounded-xl hover:bg-light transition-all text-sm font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl hover:bg-primary-dark transition-all text-sm font-bold">Update Package</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Assign/Configure Astrologer Override -->
    <div x-show="showAssignModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/50" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 space-y-6">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-bold text-text-primary">Configure Custom Astrologer Rate</h3>
                <button @click="showAssignModal = false" class="text-text-muted hover:text-text-primary"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="{{ route('admin.packages.assign') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Select Astrologer</label>
                    <select name="astrologer_id" x-model="currentAssign.astrologer_id" required class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <option value="">-- Select Astrologer --</option>
                        @foreach($astrologers as $astro)
                            <option value="{{ $astro->id }}">{{ $astro->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Override Price</label>
                        <input type="number" step="0.01" name="amount" x-model="currentAssign.amount" required placeholder="e.g. 75.00" class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Override Duration (Minutes)</label>
                        <input type="number" name="duration_minutes" :value="currentAssign.duration / 60" required placeholder="e.g. 60" class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Commission Split Override (Astrologer Share %)</label>
                    <input type="number" name="commission_percentage" x-model="currentAssign.commission_percentage" placeholder="e.g. 60 (Leaves 40% for Admin)" class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    <p class="text-[10px] text-text-muted mt-1">Leave empty to use the system default commission settings (50%).</p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="showAssignModal = false" class="px-5 py-2.5 border text-text-secondary rounded-xl hover:bg-light transition-all text-sm font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl hover:bg-primary-dark transition-all text-sm font-bold">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
