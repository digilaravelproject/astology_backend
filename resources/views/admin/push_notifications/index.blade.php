@extends('admin.layouts.app')

@section('title', 'Push Notification Campaigns')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-paper-plane text-primary"></i>
                Push Notification Broadcasts
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Send targeted custom push notifications to all users, astrologers, or specific individuals.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.push-notifications.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-xl shadow-md shadow-primary/20 transition-all duration-200">
                <i class="fas fa-plus"></i>
                <span>Compose Broadcast</span>
            </a>
        </div>
    </div>

    <!-- Alert / Toast Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl flex items-center gap-3 text-emerald-800 dark:text-emerald-300">
            <i class="fas fa-check-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl flex items-center gap-3 text-rose-800 dark:text-rose-300">
            <i class="fas fa-exclamation-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Metric Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Campaigns</span>
                <div class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ number_format($stats['total_campaigns']) }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Delivered Messages</span>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ number_format($stats['total_delivered']) }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Dead / Inactive Tokens</span>
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-0.5">{{ number_format($stats['total_failed']) }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-card-dark p-4 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
        <form method="GET" action="{{ route('admin.push-notifications.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or body..."
                       class="w-full px-4 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div>
                <select name="target_type" class="w-full px-4 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <option value="">All Audience Types</option>
                    <option value="all" {{ request('target_type') === 'all' ? 'selected' : '' }}>Broadcast to Everyone</option>
                    <option value="users" {{ request('target_type') === 'users' ? 'selected' : '' }}>Consumers Only</option>
                    <option value="astrologers" {{ request('target_type') === 'astrologers' ? 'selected' : '' }}>Astrologers Only</option>
                    <option value="single_user" {{ request('target_type') === 'single_user' ? 'selected' : '' }}>Single Specific User</option>
                </select>
            </div>

            <div>
                <select name="status" class="w-full px-4 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-4 bg-gray-900 dark:bg-gray-700 text-white text-xs font-bold rounded-xl hover:bg-gray-800 transition-all">
                    Filter
                </button>
                <a href="{{ route('admin.push-notifications.index') }}" class="py-2 px-4 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-bold rounded-xl hover:bg-gray-200 transition-all">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white dark:bg-card-dark rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50/75 dark:bg-gray-800/50 text-gray-400 font-bold uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="px-6 py-4">Title & Message</th>
                        <th class="px-6 py-4">Audience</th>
                        <th class="px-6 py-4">Recipients</th>
                        <th class="px-6 py-4">Delivered / Failed</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Created At</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-600 dark:text-gray-300 font-medium">
                    @forelse($broadcasts as $item)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-all">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $item->title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2 max-w-md">{{ $item->body }}</div>
                                @if($item->image_url)
                                    <div class="mt-1 flex items-center gap-1 text-[11px] text-primary">
                                        <i class="fas fa-image"></i> Has attached banner
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($item->target_type === 'all')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                        All Users & Astrologers
                                    </span>
                                @elseif($item->target_type === 'users')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                                        Consumers Only
                                    </span>
                                @elseif($item->target_type === 'astrologers')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                                        Astrologers Only
                                    </span>
                                @else
                                    <div class="text-xs">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                                            Single User
                                        </span>
                                        <div class="text-[11px] text-gray-500 mt-1">
                                            {{ $item->targetUser?->name ?? 'User #' . $item->target_user_id }}
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($item->total_recipients) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold flex items-center gap-1">
                                        <i class="fas fa-check text-[10px]"></i> {{ number_format($item->successful_count) }}
                                    </span>
                                    <span class="text-gray-300">/</span>
                                    <span class="text-rose-500 font-bold flex items-center gap-1">
                                        <i class="fas fa-times text-[10px]"></i> {{ number_format($item->failed_count) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($item->status === 'completed')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                                        Completed
                                    </span>
                                @elseif($item->status === 'processing')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 animate-pulse">
                                        Processing
                                    </span>
                                @elseif($item->status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                        Pending
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                {{ $item->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('admin.push-notifications.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this broadcast log?');" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-rose-600 transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-3 block text-gray-300 dark:text-gray-600"></i>
                                No push notification broadcasts found. Click "Compose Broadcast" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($broadcasts->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $broadcasts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
