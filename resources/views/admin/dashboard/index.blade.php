@extends('admin.layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Dashboard</h1>
    <p class="text-sm text-gray">Welcome back, {{ $admin->name }}! Here's your platform overview.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">

    <!-- Total Users -->
    <div class="bg-white p-6 rounded-lg shadow-md border-t-[3px] border-secondary hover:-translate-y-1 hover:shadow-lg transition-all duration-300">
        <div class="w-12 h-12 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center text-2xl mb-4">
            <i class="fas fa-users"></i>
        </div>
        <div class="text-xs font-semibold text-gray uppercase tracking-wide mb-2">Total Users</div>
        <div class="text-4xl font-bold bg-linear-to-r from-primary-light to-primary bg-clip-text text-transparent">
            {{ $totalUsers }}
        </div>
    </div>

    <!-- Total Astrologers -->
    <div class="bg-white p-6 rounded-lg shadow-md border-t-[3px] border-primary-light hover:-translate-y-1 hover:shadow-lg transition-all duration-300">
        <div class="w-12 h-12 rounded-lg bg-primary-light/10 text-primary-light flex items-center justify-center text-2xl mb-4">
            <i class="fas fa-star"></i>
        </div>
        <div class="text-xs font-semibold text-gray uppercase tracking-wide mb-2">Total Astrologers</div>
        <div class="text-4xl font-bold bg-linear-to-r from-primary-light to-primary bg-clip-text text-transparent">
            {{ $totalAstrologers }}
        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="bg-white p-6 rounded-lg shadow-md border-t-[3px] border-accent hover:-translate-y-1 hover:shadow-lg transition-all duration-300">
        <div class="w-12 h-12 rounded-lg bg-accent/10 text-accent flex items-center justify-center text-2xl mb-4">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="text-xs font-semibold text-gray uppercase tracking-wide mb-2">Pending Approvals</div>
        <div class="text-4xl font-bold bg-linear-to-r from-primary-light to-primary bg-clip-text text-transparent">
            {{ $pendingAstrologers }}
        </div>
    </div>

    <!-- Approved Astrologers -->
    <div class="bg-white p-6 rounded-lg shadow-md border-t-[3px] border-success hover:-translate-y-1 hover:shadow-lg transition-all duration-300">
        <div class="w-12 h-12 rounded-lg bg-success/10 text-success flex items-center justify-center text-2xl mb-4">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="text-xs font-semibold text-gray uppercase tracking-wide mb-2">Approved Astrologers</div>
        <div class="text-4xl font-bold bg-linear-to-r from-primary-light to-primary bg-clip-text text-transparent">
            {{ $approvedAstrologers }}
        </div>
    </div>
</div>

<!-- Recent Data Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    <!-- Recent Users -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-lighter flex justify-between items-center">
            <h3 class="text-base font-semibold text-dark">Recent Users</h3>
            <a href="{{ route('admin.users.index') }}?type=user" class="text-primary text-sm font-semibold hover:text-primary-light transition-colors">
                View All &rarr;
            </a>
        </div>

        @if($recentUsers->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-light">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Name</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Phone</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentUsers as $user)
                    <tr class="border-b border-gray-lighter last:border-0 hover:bg-info/5 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-linear-to-br from-primary-light to-primary text-white flex items-center justify-center font-semibold text-sm">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-dark">{{ $user->name }}</div>
                                    <div class="text-xs text-text-muted">{{ $user->email ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-text-secondary">{{ $user->phone ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm text-text-secondary">{{ $user->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="py-10 text-center text-text-muted">
            <div class="text-4xl mb-3 opacity-50"><i class="fas fa-clipboard-list"></i></div>
            <p>No users yet</p>
        </div>
        @endif
    </div>

    <!-- Recent Astrologers -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-lighter flex justify-between items-center">
            <h3 class="text-base font-semibold text-dark">Recent Astrologers</h3>
            <a href="{{ route('admin.users.index') }}?type=astrologer" class="text-primary text-sm font-semibold hover:text-primary-light transition-colors">
                View All &rarr;
            </a>
        </div>

        @if($recentAstrologers->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-light">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Name</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Experience</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentAstrologers as $astrologer)
                    <tr class="border-b border-gray-lighter last:border-0 hover:bg-info/5 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-linear-to-br from-primary-light to-primary text-white flex items-center justify-center font-semibold text-sm">
                                    {{ strtoupper(substr($astrologer->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-dark">{{ $astrologer->user->name }}</div>
                                    <div class="text-xs text-text-muted">{{ $astrologer->user->phone }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-text-secondary">{{ $astrologer->years_of_experience }} years</td>
                        <td class="px-6 py-4">
                            @if($astrologer->status === 'pending')
                                <span class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold bg-accent/10 text-accent">
                                    Pending
                                </span>
                            @elseif($astrologer->status === 'approved')
                                <span class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold bg-success/10 text-success">
                                    Approved
                                </span>
                            @else
                                <span class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold bg-secondary/10 text-secondary">
                                    {{ ucfirst($astrologer->status) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="py-10 text-center text-text-muted">
            <div class="text-4xl mb-3 opacity-50"><i class="fas fa-star"></i></div>
            <p>No astrologers yet</p>
        </div>
        @endif
    </div>
</div>
@endsection
