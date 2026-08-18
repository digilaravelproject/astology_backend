@extends('admin.layouts.app')

@section('title', 'Push Notification Campaigns')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-lighter pb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">
                    FCM Campaigns
                </span>
                <span class="text-text-muted text-xs">• Super Admin Broadcasts</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-text-primary tracking-tight flex items-center gap-3">
                <i class="fas fa-paper-plane text-primary"></i>
                Push Notification Broadcasts
            </h1>
            <p class="text-sm text-text-secondary mt-1">
                Send targeted custom push notifications to all users, astrologers, or specific individuals.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.push-notifications.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md shadow-primary/20 transition-all duration-200 cursor-pointer">
                <i class="fas fa-plus"></i>
                <span>Compose Broadcast</span>
            </a>
        </div>
    </div>

    <!-- Alert / Toast Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-emerald-800 shadow-xs">
            <i class="fas fa-check-circle text-lg text-emerald-600"></i>
            <span class="text-sm font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-3 text-rose-800 shadow-xs">
            <i class="fas fa-exclamation-circle text-lg text-rose-600"></i>
            <span class="text-sm font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Metric Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs flex items-center gap-4 hover:border-primary/30 transition-all">
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-text-muted">Total Campaigns</span>
                <div class="text-2xl font-black text-text-primary mt-0.5">{{ number_format($stats['total_campaigns']) }}</div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs flex items-center gap-4 hover:border-emerald-300 transition-all">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-text-muted">Delivered Messages</span>
                <div class="text-2xl font-black text-emerald-700 mt-0.5">{{ number_format($stats['total_delivered']) }}</div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs flex items-center gap-4 hover:border-rose-300 transition-all">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-text-muted">Dead / Inactive Tokens</span>
                <div class="text-2xl font-black text-rose-700 mt-0.5">{{ number_format($stats['total_failed']) }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
        <form method="GET" action="{{ route('admin.push-notifications.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or body..."
                       class="w-full px-4 py-2.5 text-xs bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
            </div>

            <div>
                <select name="target_type" class="w-full px-4 py-2.5 text-xs bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    <option value="">All Audience Types</option>
                    <option value="all" {{ request('target_type') === 'all' ? 'selected' : '' }}>Broadcast to Everyone</option>
                    <option value="users" {{ request('target_type') === 'users' ? 'selected' : '' }}>Consumers Only</option>
                    <option value="astrologers" {{ request('target_type') === 'astrologers' ? 'selected' : '' }}>Astrologers Only</option>
                    <option value="single_user" {{ request('target_type') === 'single_user' ? 'selected' : '' }}>Single Specific User</option>
                </select>
            </div>

            <div>
                <select name="status" class="w-full px-4 py-2.5 text-xs bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-2.5 bg-primary hover:bg-primary-dark text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-xs cursor-pointer">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'target_type', 'status']))
                    <a href="{{ route('admin.push-notifications.index') }}" class="px-3 py-2.5 bg-light hover:bg-gray-200 text-text-secondary text-xs font-bold rounded-xl transition-all">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Campaigns Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 bg-light/30 text-[11px] font-bold uppercase tracking-wider text-text-muted">
                        <th class="py-4 px-6">Campaign Info</th>
                        <th class="py-4 px-4">Target Audience</th>
                        <th class="py-4 px-4">Delivery Metrics</th>
                        <th class="py-4 px-4">Status</th>
                        <th class="py-4 px-4">Created At</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($campaigns as $camp)
                        <tr class="hover:bg-light/10 transition-colors">
                            <td class="py-4 px-6">
                                <div class="font-bold text-text-primary text-sm flex items-center gap-2">
                                    <span>{{ $camp->title }}</span>
                                    @if($camp->image_url)
                                        <span class="text-amber-600 text-xs" title="Has media banner"><i class="fas fa-image"></i></span>
                                    @endif
                                </div>
                                <div class="text-text-muted text-xs line-clamp-1 mt-0.5 max-w-md">
                                    {{ $camp->body }}
                                </div>
                                @if($camp->action_url)
                                    <div class="text-[11px] text-primary font-mono mt-1 flex items-center gap-1">
                                        <i class="fas fa-link text-[9px]"></i> {{ $camp->action_url }}
                                    </div>
                                @endif
                            </td>

                            <td class="py-4 px-4">
                                @if($camp->target_type === 'all')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-primary/10 text-primary border border-primary/20">
                                        <i class="fas fa-users mr-1"></i> All Users
                                    </span>
                                @elseif($camp->target_type === 'users')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        <i class="fas fa-user mr-1"></i> Consumers
                                    </span>
                                @elseif($camp->target_type === 'astrologers')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-50 text-purple-800 border border-purple-200">
                                        <i class="fas fa-star-and-crescent mr-1"></i> Astrologers
                                    </span>
                                @elseif($camp->target_type === 'single_user')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <i class="fas fa-user-tag mr-1"></i> {{ $camp->targetUser?->name ?? 'Single User' }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-4 font-mono">
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-700 font-bold" title="Delivered"><i class="fas fa-check mr-0.5"></i>{{ $camp->successful_count }}</span>
                                    <span class="text-text-muted">/</span>
                                    <span class="text-rose-700 font-bold" title="Failed/Dead"><i class="fas fa-times mr-0.5"></i>{{ $camp->failed_count }}</span>
                                </div>
                                <div class="text-[10px] text-text-muted mt-0.5">Total: {{ $camp->total_recipients }} Devices</div>
                            </td>

                            <td class="py-4 px-4">
                                @if($camp->status === 'completed')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Completed
                                    </span>
                                @elseif($camp->status === 'processing')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200 flex items-center gap-1 w-max">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-ping"></span> Processing
                                    </span>
                                @elseif($camp->status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Pending
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                        {{ ucfirst($camp->status) }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-4 text-text-muted text-xs">
                                <div>{{ $camp->created_at->format('d M Y, h:i A') }}</div>
                                <div class="text-[10px] text-text-muted mt-0.5">{{ $camp->created_at->diffForHumans() }}</div>
                            </td>

                            <td class="py-4 px-6 text-right">
                                <form action="{{ route('admin.push-notifications.destroy', $camp->id) }}" method="POST" onsubmit="return confirm('Delete this broadcast campaign record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-text-muted hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-text-muted">
                                <div class="w-16 h-16 rounded-2xl bg-light flex items-center justify-center mx-auto mb-3 text-2xl text-text-muted">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <div class="font-bold text-text-primary text-sm">No Broadcast Campaigns Found</div>
                                <p class="text-xs text-text-muted mt-1">Create your first push notification campaign above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
