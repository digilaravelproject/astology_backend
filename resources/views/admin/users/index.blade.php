@extends('admin.layouts.app')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Manage Users</h1>
        <p class="text-sm text-gray">Total {{ $users->total() }} records found</p>
    </div>
    <a href="{{ route('admin.users.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-dark transition-all duration-300 hover:-translate-y-0.5 shadow-md hover:shadow-lg">
        <i class="fas fa-plus"></i>
        <span>Add User</span>
    </a>
</div>

<!-- Filter Section -->
<div class="bg-white p-5 rounded-xl shadow-md mb-6">
    <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col lg:flex-row gap-4 items-end">

        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-semibold text-gray uppercase tracking-wide mb-2">Search</label>
            <input type="text"
                   name="search"
                   placeholder="Name, Email or Phone"
                   value="{{ request('search') }}"
                   class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
        </div>

        <!-- User Type -->
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-semibold text-gray uppercase tracking-wide mb-2">User Type</label>
            <select name="type"
                    class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20 bg-white">
                <option value="">All Types</option>
                <option value="user" {{ request('type') == 'user' ? 'selected' : '' }}>User</option>
                <option value="astrologer" {{ request('type') == 'astrologer' ? 'selected' : '' }}>Astrologer</option>
            </select>
        </div>

        <!-- Astrologer Status -->
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-semibold text-gray uppercase tracking-wide mb-2">Astrologer Status</label>
            <select name="status"
                    class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20 bg-white">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <!-- Buttons -->
        <div class="flex gap-3">
            <button type="submit"
                    class="px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-dark transition-all duration-300">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
            <a href="{{ route('admin.users.index') }}"
               class="px-5 py-2.5 border border-gray-lighter text-gray text-sm font-semibold rounded-lg hover:bg-light transition-all duration-300">
                <i class="fas fa-times mr-1"></i> Clear
            </a>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-light border-b border-gray-lighter">
                <tr>
                    <th class="px-5 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">ID</th>
                    <th class="px-5 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">User Details</th>
                    <th class="px-5 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Type</th>
                    <th class="px-5 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Astrologer Info</th>
                    <th class="px-5 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Joined</th>
                    <th class="px-5 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-lighter">
                @forelse($users as $user)
                <tr class="hover:bg-info/5 transition-colors duration-200">
                    <!-- ID -->
                    <td class="px-5 py-4 text-sm font-medium text-dark">#{{ $user->id }}</td>

                    <!-- User Details -->
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-linear-to-br from-primary-light to-primary text-white flex items-center justify-center font-semibold text-sm shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-dark">{{ $user->name }}</div>
                                <div class="text-xs text-text-muted flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-phone text-[10px]"></i> {{ $user->phone ?? 'N/A' }}
                                </div>
                                <div class="text-xs text-text-muted flex items-center gap-1">
                                    <i class="fas fa-envelope text-[10px]"></i> {{ $user->email ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Type Badge -->
                    <td class="px-5 py-4">
                        @if($user->user_type === 'astrologer')
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                                <i class="fas fa-star text-[10px]"></i> Astrologer
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-info/10 text-info">
                                <i class="fas fa-user text-[10px]"></i> User
                            </span>
                        @endif
                    </td>

                    <!-- Astrologer Info -->
                    <td class="px-5 py-4">
                        @if($user->user_type === 'astrologer' && $user->astrologer)
                            <div class="text-sm text-text-secondary mb-1">
                                <i class="fas fa-briefcase text-xs text-gray-light mr-1"></i>
                                {{ $user->astrologer->years_of_experience ?? 0 }} yrs exp
                            </div>
                            @php $status = $user->astrologer->status; @endphp
                            @if($status === 'pending')
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold bg-accent/10 text-accent">
                                    <i class="fas fa-clock text-[10px] mr-1"></i>Pending
                                </span>
                            @elseif($status === 'approved')
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold bg-success/10 text-success">
                                    <i class="fas fa-check text-[10px] mr-1"></i>Approved
                                </span>
                            @elseif($status === 'rejected')
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold bg-danger/10 text-danger">
                                    <i class="fas fa-times text-[10px] mr-1"></i>Rejected
                                </span>
                            @endif
                        @else
                            <span class="text-gray-light">—</span>
                        @endif
                    </td>

                    <!-- Joined Date -->
                    <td class="px-5 py-4 text-sm text-text-secondary">
                        <i class="fas fa-calendar text-xs text-gray-light mr-1"></i>
                        {{ $user->created_at->format('M d, Y') }}
                    </td>

                    <!-- Actions -->
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            @if($user->user_type === 'astrologer' && $user->astrologer)
                                @if($user->astrologer->status !== 'approved')
                                    <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit"
                                                title="Approve"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-success/10 text-success hover:bg-success hover:text-white transition-all duration-300">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($user->astrologer->status !== 'rejected')
                                    <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit"
                                                title="Reject"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-danger/10 text-danger hover:bg-danger hover:text-white transition-all duration-300">
                                            <i class="fas fa-ban text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif

                            <!-- Edit -->
                            <a href="{{ route('admin.users.edit', $user->id) }}"
                               title="Edit"
                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-info/10 text-info hover:bg-info hover:text-white transition-all duration-300">
                                <i class="fas fa-edit text-xs"></i>
                            </a>

                            <!-- Delete -->
                            <form action="{{ route('admin.users.destroy', $user->id) }}"
                                  method="POST"
                                  class="inline"
                                  x-data
                                  @submit.prevent="if(confirm('Are you sure you want to delete this user?')) $el.submit()">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        title="Delete"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-danger/10 text-danger hover:bg-danger hover:text-white transition-all duration-300">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-16 text-center">
                        <div class="text-5xl text-gray-lighter mb-4">
                            <i class="fas fa-users-slash"></i>
                        </div>
                        <p class="text-gray font-medium">No users found matching the criteria.</p>
                        <a href="{{ route('admin.users.index') }}" class="inline-block mt-4 text-primary text-sm font-semibold hover:underline">
                            Clear filters &rarr;
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
    <div class="px-5 py-4 border-t border-gray-lighter bg-light/50">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
