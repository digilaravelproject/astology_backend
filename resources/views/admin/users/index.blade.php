@extends('admin.layouts.app')

@section('styles')
<style>
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }

    .filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
        flex: 1;
        min-width: 200px;
    }

    .form-group label {
        font-size: 13px;
        font-weight: 600;
        color: #555;
    }

    .form-control {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: #d63384;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: #d63384;
        color: white;
    }

    .btn-primary:hover {
        background: #c22975;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid #ddd;
        color: #555;
    }

    .btn-outline:hover {
        background: #f5f5f5;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .btn-danger {
        background: #ff6b6b;
        color: white;
    }
    
    .btn-danger:hover {
        background: #ee5a52;
    }
    
    .btn-success {
        background: #51cf66;
        color: white;
    }
    
    .btn-success:hover {
        background: #40c057;
    }

    .users-table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 15px 20px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    th {
        background: #f8f9fa;
        font-size: 13px;
        font-weight: 600;
        color: #555;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    td {
        font-size: 14px;
        vertical-align: middle;
    }

    tbody tr:hover {
        background: #fcfcfc;
    }

    .badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-user {
        background: #e3f2fd;
        color: #1976d2;
    }

    .badge-astrologer {
        background: #f3e5f5;
        color: #7b1fa2;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pending { background: rgba(255, 165, 0, 0.1); color: #ffa500; }
    .status-approved { background: rgba(81, 207, 102, 0.1); color: #51cf66; }
    .status-rejected { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

    .actions {
        display: flex;
        gap: 8px;
    }

    .pagination-wrapper {
        padding: 20px;
        display: flex;
        justify-content: center;
    }
    
    .pagination {
        display: flex;
        list-style: none;
        gap: 5px;
    }
    
    .pagination li a, .pagination li span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #333;
    }
    
    .pagination li.active span {
        background: #d63384;
        color: white;
        border-color: #d63384;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1>Manage Users</h1>
        <p>Total {{ $users->total() }} records found</p>
    </div>
    <div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add User
        </a>
    </div>
</div>

<div class="filter-section">
    <form action="{{ route('admin.users.index') }}" method="GET" class="filter-form">
        <div class="form-group">
            <label>Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name, Email or Phone" value="{{ request('search') }}">
        </div>

        <div class="form-group">
            <label>User Type</label>
            <select name="type" class="form-control">
                <option value="">All Types</option>
                <option value="user" {{ request('type') == 'user' ? 'selected' : '' }}>User</option>
                <option value="astrologer" {{ request('type') == 'astrologer' ? 'selected' : '' }}>Astrologer</option>
            </select>
        </div>

        <div class="form-group">
            <label>Astrologer Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Clear</a>
    </form>
</div>

<div class="users-table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User Details</th>
                <th>Type</th>
                <th>Astrologer Info</th>
                <th>Joined Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>#{{ $user->id }}</td>
                    <td>
                        <div style="font-weight: 600;">{{ $user->name }}</div>
                        <div style="font-size: 13px; color: #666;">📞 {{ $user->phone ?? 'N/A' }}</div>
                        <div style="font-size: 13px; color: #666;">✉️ {{ $user->email ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <span class="badge badge-{{ $user->user_type }}">
                            {{ ucfirst($user->user_type) }}
                        </span>
                    </td>
                    <td>
                        @if($user->user_type === 'astrologer' && $user->astrologer)
                            <div style="margin-bottom: 5px;">
                                Exp: {{ $user->astrologer->years_of_experience ?? 0 }} yrs
                            </div>
                            <span class="status-badge status-{{ $user->astrologer->status }}">
                                {{ ucfirst($user->astrologer->status) }}
                            </span>
                        @else
                            <span style="color: #aaa;">-</span>
                        @endif
                    </td>
                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="actions">
                            @if($user->user_type === 'astrologer' && $user->astrologer)
                                <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" style="margin: 0;">
                                    @csrf
                                    @if($user->astrologer->status !== 'approved')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    @if($user->astrologer->status !== 'rejected')
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-sm btn-danger" style="background:#dc3545" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </form>
                            @endif
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                        No users found matching the criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="pagination-wrapper">
        {{ $users->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
