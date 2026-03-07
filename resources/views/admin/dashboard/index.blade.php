@extends('admin.layouts.app')

@section('styles')
<style>
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-top: 3px solid #d63384;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
    }

    .stat-card.total-users {
        border-top-color: #4ecdc4;
    }

    .stat-card.total-astrologers {
        border-top-color: #f95a8f;
    }

    .stat-card.pending {
        border-top-color: #ffa500;
    }

    .stat-card.approved {
        border-top-color: #51cf66;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
    }

    .total-users .stat-icon {
        background: rgba(78, 205, 196, 0.1);
        color: #4ecdc4;
    }

    .total-astrologers .stat-icon {
        background: rgba(249, 90, 143, 0.1);
        color: #f95a8f;
    }

    .pending .stat-icon {
        background: rgba(255, 165, 0, 0.1);
        color: #ffa500;
    }

    .approved .stat-icon {
        background: rgba(81, 207, 102, 0.1);
        color: #51cf66;
    }

    .stat-label {
        font-size: 13px;
        color: #666;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-number {
        font-size: 36px;
        font-weight: 700;
        background: linear-gradient(135deg, #f95a8f 0%, #d63384 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Content Grid */
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 40px;
    }

    /* Table Section */
    .table-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .table-header {
        padding: 20px 25px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-header h3 {
        font-size: 16px;
        font-weight: 600;
        color: #222;
    }

    .view-all-btn {
        color: #d63384;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .view-all-btn:hover {
        color: #f95a8f;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table thead {
        background: #f9f9f9;
    }

    table th {
        padding: 15px 25px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e0e0e0;
    }

    table td {
        padding: 15px 25px;
        border-bottom: 1px solid #e0e0e0;
        font-size: 14px;
    }

    table tbody tr:hover {
        background: #f5f9ff;
    }

    table tbody tr:last-child td {
        border-bottom: none;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pending {
        background: rgba(255, 165, 0, 0.1);
        color: #ffa500;
    }

    .status-approved {
        background: rgba(81, 207, 102, 0.1);
        color: #51cf66;
    }

    .status-verified {
        background: rgba(78, 205, 196, 0.1);
        color: #4ecdc4;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f95a8f 0%, #d63384 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }

    .user-details h4 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .user-details p {
        font-size: 12px;
        color: #999;
    }

    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }

    .empty-state-icon {
        font-size: 40px;
        margin-bottom: 10px;
        opacity: 0.5;
    }

    @media (max-width: 1024px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        table {
            font-size: 12px;
        }

        table th, table td {
            padding: 10px 12px;
        }

        .stat-number {
            font-size: 28px;
        }
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p>Welcome back, {{ $admin->name }}! Here's your platform overview.</p>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card total-users">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-number">{{ $totalUsers }}</div>
    </div>

    <div class="stat-card total-astrologers">
        <div class="stat-icon"><i class="fas fa-star"></i></div>
        <div class="stat-label">Total Astrologers</div>
        <div class="stat-number">{{ $totalAstrologers }}</div>
    </div>

    <div class="stat-card pending">
        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-label">Pending Approvals</div>
        <div class="stat-number">{{ $pendingAstrologers }}</div>
    </div>

    <div class="stat-card approved">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-label">Approved Astrologers</div>
        <div class="stat-number">{{ $approvedAstrologers }}</div>
    </div>
</div>

<!-- Recent Data -->
<div class="content-grid">
    <!-- Recent Users -->
    <div class="table-section">
        <div class="table-header">
            <h3>Recent Users</h3>
            <a href="{{ route('admin.users.index') }}?type=user" class="view-all-btn">View All &rarr;</a>
        </div>

        @if($recentUsers->count() > 0)
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUsers as $user)
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                        <div class="user-details">
                                            <h4>{{ $user->name }}</h4>
                                            <p>{{ $user->email ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->phone ?? 'N/A' }}</td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-clipboard-list"></i></div>
                <p>No users yet</p>
            </div>
        @endif
    </div>

    <!-- Recent Astrologers -->
    <div class="table-section">
        <div class="table-header">
            <h3>Recent Astrologers</h3>
            <a href="{{ route('admin.users.index') }}?type=astrologer" class="view-all-btn">View All &rarr;</a>
        </div>

        @if($recentAstrologers->count() > 0)
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Experience</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentAstrologers as $astrologer)
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">{{ strtoupper(substr($astrologer->user->name, 0, 1)) }}</div>
                                        <div class="user-details">
                                            <h4>{{ $astrologer->user->name }}</h4>
                                            <p>{{ $astrologer->user->phone }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $astrologer->years_of_experience }} years</td>
                                <td>
                                    <span class="status-badge status-{{ $astrologer->status }}">
                                        {{ ucfirst($astrologer->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-star"></i></div>
                <p>No astrologers yet</p>
            </div>
        @endif
    </div>
</div>
@endsection
