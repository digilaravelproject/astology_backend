@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">User Management</h1>
            <p class="text-sm text-gray font-medium">Manage all registered users on the platform.</p>
            <div class="text-xs text-gray mt-1">Total users (type = user): <span class="font-bold text-dark">{{ $totalUsers }}</span></div>
        </div>
        <a href="{{ route('admin.users.create') }}" class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-primary/20 hover:bg-primary-dark transition-all flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.users.index') }}" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-lighter mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Search User</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-light"></i>
                    <input name="search" type="text" placeholder="Search by name, email or phone..." value="{{ request('search') }}" class="w-full pl-11 pr-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">User Type</label>
                <select name="type" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm appearance-none">
                    <option value="user" {{ request('type', 'user') === 'user' ? 'selected' : '' }}>Regular User</option>
                    <option value="astrologer" {{ request('type') === 'astrologer' ? 'selected' : '' }}>Astrologer</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray uppercase mb-1 ml-1">Status</label>
                <select name="status" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm appearance-none">
                    <option value="" {{ request('status') === null ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-dark text-white py-3 rounded-xl font-bold hover:bg-black transition-all">Filter Results</button>
            </div>
        </div>
    </form>

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
                    @forelse($users as $user)
                        @php
                            $walletBalance = optional($user->wallet)->balance ?? 0;
                            $statusLabel = $user->profile_completed ? 'Active' : 'Inactive';
                        @endphp
                        <tr class="hover:bg-light/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-linear-to-br from-primary/20 to-primary/40 text-primary flex items-center justify-center font-black text-sm group-hover:scale-110 transition-transform">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-dark group-hover:text-primary transition-colors">{{ $user->name }}</div>
                                        <div class="text-[10px] font-bold text-gray uppercase tracking-tighter">ID: #USR-{{ $user->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-semibold text-dark">{{ $user->email ?? '-' }}</div>
                                <div class="text-[10px] text-gray">{{ $user->phone ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-xs font-black text-dark">₹{{ number_format($walletBalance, 2) }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $user->user_type === 'astrologer' ? 'bg-primary/10 text-primary' : 'bg-info/10 text-info' }}">
                                    {{ ucfirst($user->user_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-gray">{{ $user->created_at?->format('d M Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 {{ $statusLabel === 'Active' ? 'bg-success/10 text-success' : 'bg-gray-lighter text-gray' }} text-[10px] font-black rounded-full uppercase">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2 translate-x-2 group-hover:translate-x-0 transition-transform">
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info hover:text-white transition-all flex items-center justify-center" title="View Details">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="w-8 h-8 rounded-lg bg-secondary/10 text-secondary hover:bg-secondary hover:text-white transition-all flex items-center justify-center" title="Edit User">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-danger/10 text-danger hover:bg-danger hover:text-white transition-all flex items-center justify-center" title="Delete User">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-5 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-xs font-bold text-gray uppercase tracking-widest">
                Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} entries
            </div>
            <div>
                {{ $users->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
