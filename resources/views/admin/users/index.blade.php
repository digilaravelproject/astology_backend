@extends('admin.layouts.app')

@section('content')
<div x-data="{ userModal: false, selectedUser: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">User Management</h1>
            <p class="text-sm text-gray font-medium">Manage all registered users on the platform.</p>
        </div>
        <button class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-primary/20 hover:bg-primary-dark transition-all flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Add New User
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-lighter mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Search User</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-light"></i>
                    <input type="text" placeholder="Search by name, email or phone..." class="w-full pl-11 pr-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Status</label>
                <select class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm appearance-none">
                    <option>All Status</option>
                    <option>Active</option>
                    <option>Inactive</option>
                    <option>Blocked</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full bg-dark text-white py-3 rounded-xl font-bold hover:bg-black transition-all">Filter Results</button>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/50 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">User Info</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-center">Wallet</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-center">Type</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @php
                        $sampleUsers = [
                            ['id' => 101, 'name' => 'Rahul Sharma', 'email' => 'rahul.s@gmail.com', 'phone' => '+91 98765 43210', 'wallet' => '₹1,250', 'joined' => '12 Jan 2024', 'status' => 'Active', 'city' => 'Delhi', 'orders' => 15, 'type' => 'User'],
                            ['id' => 102, 'name' => 'Priya Patel', 'email' => 'priya.p@yahoo.com', 'phone' => '+91 87654 32109', 'wallet' => '₹450', 'joined' => '15 Jan 2024', 'status' => 'Active', 'city' => 'Mumbai', 'orders' => 8, 'type' => 'User'],
                            ['id' => 103, 'name' => 'Sneha Gupta', 'email' => 'sneha.g@outlook.com', 'phone' => '+91 99887 76655', 'wallet' => '₹2,800', 'joined' => '02 Feb 2024', 'status' => 'Active', 'city' => 'Pune', 'orders' => 24, 'type' => 'User'],
                            ['id' => 104, 'name' => 'Vikram Joshi', 'email' => 'vikram.j@gmail.com', 'phone' => '+91 88776 65544', 'wallet' => '₹150', 'joined' => '10 Feb 2024', 'status' => 'Active', 'city' => 'Jaipur', 'orders' => 1, 'type' => 'Astrologer'],
                            ['id' => 105, 'name' => 'Anjali Verma', 'email' => 'anjali.v@gmail.com', 'phone' => '+91 77665 54433', 'wallet' => '₹920', 'joined' => '14 Feb 2024', 'status' => 'Active', 'city' => 'Lucknow', 'orders' => 12, 'type' => 'User'],
                            ['id' => 106, 'name' => 'Raj Malhotra', 'email' => 'raj.m@yahoo.com', 'phone' => '+91 99001 12233', 'wallet' => '₹55', 'joined' => '22 Feb 2024', 'status' => 'Inactive', 'city' => 'Chandigarh', 'orders' => 5, 'type' => 'User'],
                            ['id' => 107, 'name' => 'Pooja Reddy', 'email' => 'pooja.r@gmail.com', 'phone' => '+91 88990 01122', 'wallet' => '₹1,500', 'joined' => '01 Mar 2024', 'status' => 'Active', 'city' => 'Hyderabad', 'orders' => 18, 'type' => 'User'],
                            ['id' => 108, 'name' => 'Arjun Nair', 'email' => 'arjun.n@outlook.com', 'phone' => '+91 77889 88776', 'wallet' => '₹300', 'joined' => '05 Mar 2024', 'status' => 'Blocked', 'city' => 'Chennai', 'orders' => 2, 'type' => 'User'],
                            ['id' => 109, 'name' => 'Kavita Joshi', 'email' => 'kavita.j@gmail.com', 'phone' => '+91 91122 33445', 'wallet' => '₹2,100', 'joined' => '10 Mar 2024', 'status' => 'Active', 'city' => 'Ahmedabad', 'orders' => 31, 'type' => 'User'],
                            ['id' => 110, 'name' => 'Sanjay Dutt', 'email' => 'sanjay.d@gmail.com', 'phone' => '+91 92233 44556', 'wallet' => '₹0', 'joined' => '11 Mar 2024', 'status' => 'Active', 'city' => 'Kolkata', 'orders' => 0, 'type' => 'User'],
                            ['id' => 111, 'name' => 'Meera Bai', 'email' => 'meera.b@yahoo.com', 'phone' => '+91 93344 55667', 'wallet' => '₹780', 'joined' => '12 Mar 2024', 'status' => 'Active', 'city' => 'Indore', 'orders' => 9, 'type' => 'User'],
                            ['id' => 112, 'name' => 'Deepak Chopra', 'email' => 'deepak.c@outlook.com', 'phone' => '+91 94455 66778', 'wallet' => '₹120', 'joined' => '12 Mar 2024', 'status' => 'Active', 'city' => 'Surat', 'orders' => 0, 'type' => 'User'],
                            ['id' => 113, 'name' => 'Shweta Tiwari', 'email' => 'shweta.t@gmail.com', 'phone' => '+91 95566 77889', 'wallet' => '₹3,400', 'joined' => '13 Mar 2024', 'status' => 'Active', 'city' => 'Bhopal', 'orders' => 42, 'type' => 'User'],
                            ['id' => 114, 'name' => 'Aman Gupta', 'email' => 'aman.g@boat.com', 'phone' => '+91 96677 88990', 'wallet' => '₹500', 'joined' => '13 Mar 2024', 'status' => 'Active', 'city' => 'Gurgaon', 'orders' => 7, 'type' => 'User'],
                            ['id' => 115, 'name' => 'Amit Kumar', 'email' => 'amit.k@gmail.com', 'phone' => '+91 76543 21098', 'wallet' => '₹0', 'joined' => '20 Jan 2024', 'status' => 'Inactive', 'city' => 'Bangalore', 'orders' => 3, 'type' => 'User'],
                        ];
                    @endphp

                    @foreach($sampleUsers as $user)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-linear-to-br from-primary/20 to-primary/40 text-primary flex items-center justify-center font-black text-sm group-hover:scale-110 transition-transform">
                                    {{ substr($user['name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-dark group-hover:text-primary transition-colors">{{ $user['name'] }}</div>
                                    <div class="text-[10px] font-bold text-gray uppercase tracking-tighter">ID: #USR-{{ $user['id'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-semibold text-dark">{{ $user['email'] }}</div>
                            <div class="text-[10px] text-gray">{{ $user['phone'] }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="text-xs font-black text-dark">{{ $user['wallet'] }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $user['type'] == 'Astrologer' ? 'bg-primary/10 text-primary' : 'bg-info/10 text-info' }}">
                                {{ $user['type'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs font-semibold text-gray">{{ $user['joined'] }}</td>
                        <td class="px-6 py-4">
                            @if($user['status'] == 'Active')
                                <span class="px-2.5 py-1 bg-success/10 text-success text-[10px] font-black rounded-full uppercase">Active</span>
                            @elseif($user['status'] == 'Inactive')
                                <span class="px-2.5 py-1 bg-gray-lighter text-gray text-[10px] font-black rounded-full uppercase">Inactive</span>
                            @else
                                <span class="px-2.5 py-1 bg-danger/10 text-danger text-[10px] font-black rounded-full uppercase">Blocked</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2 translate-x-2 group-hover:translate-x-0 transition-transform">
                                <button @click="selectedUser = {{ json_encode($user) }}; userModal = true" class="w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info hover:text-white transition-all flex items-center justify-center" title="View Details">
                                    <i class="fas fa-eye text-xs"></i>
                                </button>
                                <button class="w-8 h-8 rounded-lg bg-secondary/10 text-secondary hover:bg-secondary hover:text-white transition-all flex items-center justify-center" title="Edit User">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <button class="w-8 h-8 rounded-lg bg-danger/10 text-danger hover:bg-danger hover:text-white transition-all flex items-center justify-center" title="Delete User">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-5 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-xs font-bold text-gray uppercase tracking-widest">Showing 1 to 15 of 150 entries</div>
            <div class="flex items-center gap-1">
                <button class="w-8 h-8 rounded-lg border border-gray-lighter flex items-center justify-center text-gray hover:bg-primary hover:text-white transition-all disabled:opacity-50" disabled><i class="fas fa-chevron-left text-[10px]"></i></button>
                <button class="w-8 h-8 rounded-lg bg-primary text-white font-black text-xs">1</button>
                <button class="w-8 h-8 rounded-lg border border-gray-lighter flex items-center justify-center text-dark font-black text-xs hover:bg-primary hover:text-white transition-all">2</button>
                <button class="w-8 h-8 rounded-lg border border-gray-lighter flex items-center justify-center text-dark font-black text-xs hover:bg-primary hover:text-white transition-all">3</button>
                <span class="px-2 text-gray text-xs font-bold">...</span>
                <button class="w-8 h-8 rounded-lg border border-gray-lighter flex items-center justify-center text-dark font-black text-xs hover:bg-primary hover:text-white transition-all">10</button>
                <button class="w-8 h-8 rounded-lg border border-gray-lighter flex items-center justify-center text-gray hover:bg-primary hover:text-white transition-all"><i class="fas fa-chevron-right text-[10px]"></i></button>
            </div>
        </div>
    </div>

    <!-- User Detail Modal -->
    <div x-show="userModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/60 shadow-2xl backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-2xl rounded-[32px] shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden" @click.away="userModal = false">
            <div class="relative h-40 bg-linear-to-r from-primary to-primary-dark">
                <button @click="userModal = false" class="absolute top-6 right-6 w-10 h-10 bg-white/20 hover:bg-white/40 text-white rounded-full flex items-center justify-center transition-all">
                    <i class="fas fa-times"></i>
                </button>
                <div class="absolute -bottom-14 left-10 p-1.5 bg-white rounded-3xl shadow-xl">
                    <div class="w-28 h-28 rounded-[22px] bg-linear-to-br from-primary-light to-primary text-white flex items-center justify-center text-5xl font-black shadow-inner" x-text="selectedUser.name ? selectedUser.name.charAt(0) : ''"></div>
                </div>
            </div>
            
            <div class="pt-20 px-10 pb-10">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h2 class="text-3xl font-black text-dark leading-tight" x-text="selectedUser.name"></h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="px-2 py-0.5 bg-info/10 text-info text-[9px] font-black uppercase rounded" x-text="selectedUser.type"></span>
                            <span class="text-xs font-bold text-gray-light" x-text="'ID: #USR-' + selectedUser.id"></span>
                        </div>
                    </div>
                    <span class="px-5 py-2 rounded-full text-[11px] font-black uppercase tracking-widest shadow-md"
                          :class="selectedUser.status === 'Active' ? 'bg-success/10 text-success' : (selectedUser.status === 'Inactive' ? 'bg-gray-lighter text-gray' : 'bg-danger/10 text-danger')"
                          x-text="selectedUser.status"></span>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-10">
                    <div class="p-5 bg-light/30 rounded-3xl border border-gray-lighter hover:border-primary/20 transition-colors">
                        <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Email Address</div>
                        <div class="text-sm font-bold text-dark truncate" x-text="selectedUser.email"></div>
                    </div>
                    <div class="p-5 bg-light/30 rounded-3xl border border-gray-lighter hover:border-primary/20 transition-colors">
                        <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Phone Number</div>
                        <div class="text-sm font-bold text-dark" x-text="selectedUser.phone"></div>
                    </div>
                    <div class="p-5 bg-primary/5 rounded-3xl border border-primary/10 hover:shadow-inner transition-all">
                        <div class="text-[10px] font-black text-primary uppercase mb-2 tracking-widest">Wallet Balance</div>
                        <div class="text-2xl font-black text-dark" x-text="selectedUser.wallet"></div>
                    </div>
                    <div class="p-5 bg-light/30 rounded-3xl border border-gray-lighter hover:border-primary/20 transition-colors">
                        <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Member Since</div>
                        <div class="text-sm font-black text-dark" x-text="selectedUser.joined"></div>
                    </div>
                </div>

                <div class="flex items-center gap-6 mb-10 p-6 bg-dark/2 rounded-3xl border border-dashed border-gray-lighter">
                    <div class="text-center flex-1">
                        <div class="text-[10px] font-black text-gray uppercase mb-1 tracking-widest">Total Orders</div>
                        <div class="text-2xl font-black text-dark" x-text="selectedUser.orders"></div>
                    </div>
                    <div class="w-px h-12 bg-gray-lighter"></div>
                    <div class="text-center flex-1">
                        <div class="text-[10px] font-black text-gray uppercase mb-1 tracking-widest">Current City</div>
                        <div class="text-lg font-black text-dark" x-text="selectedUser.city"></div>
                    </div>
                </div>

                <div class="flex gap-4">
                    <button class="flex-1 bg-primary text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-primary-dark transition-all shadow-xl shadow-primary/20 transform active:scale-95">Edit Profile</button>
                    <button class="flex-1 bg-dark text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 transform active:scale-95">Message User</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
