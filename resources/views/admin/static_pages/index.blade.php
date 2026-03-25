@extends('admin.layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary">Static Pages</h1>
        <p class="text-text-muted text-sm mt-1">Manage FAQs, policies, and other static content</p>
    </div>
    <a href="{{ route('admin.static_pages.create') }}" class="px-4 py-2 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-all">
        <i class="fas fa-plus mr-2"></i> New Page
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted text-sm">Total Pages</p>
                <h3 class="text-2xl font-bold text-text-primary">{{ $total }}</h3>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <i class="fas fa-file-alt text-primary text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted text-sm">Active</p>
                <h3 class="text-2xl font-bold text-success">{{ $active }}</h3>
            </div>
            <div class="w-12 h-12 bg-success/10 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-success text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted text-sm">Inactive</p>
                <h3 class="text-2xl font-bold text-warning">{{ $inactive }}</h3>
            </div>
            <div class="w-12 h-12 bg-warning/10 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-circle text-warning text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" class="flex items-end gap-4 flex-wrap">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-text-primary mb-2">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search pages..." 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-text-primary mb-2">Page Type</label>
            <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">All Types</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-text-primary mb-2">Status</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-all font-medium">
            Filter
        </button>
        <a href="{{ route('admin.static_pages.index') }}" class="px-6 py-2 bg-gray-200 text-text-primary rounded-lg hover:bg-gray-300 transition-all font-medium">
            Reset
        </a>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-text-primary">Page Type</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-text-primary">Title</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-text-primary">Preview</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-text-primary">Status</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-text-primary">Last Updated</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-text-primary">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($pages as $page)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="inline-block px-3 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full">
                                {{ $types[$page->type] ?? ucfirst(str_replace('_', ' ', $page->type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-text-primary">{{ $page->title }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-text-muted line-clamp-2">
                                {{ Str::limit(strip_tags($page->content), 100) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($page->is_active)
                                <span class="inline-block px-3 py-1 bg-success/10 text-success text-xs font-semibold rounded-full">
                                    Active
                                </span>
                            @else
                                <span class="inline-block px-3 py-1 bg-warning/10 text-warning text-xs font-semibold rounded-full">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-text-muted">
                            {{ $page->updated_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.static_pages.edit', $page->id) }}" 
                                   class="px-3 py-1 text-xs bg-primary/10 text-primary rounded hover:bg-primary hover:text-white transition-all">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('admin.static_pages.destroy', $page->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Are you sure?')" 
                                            class="px-3 py-1 text-xs bg-danger/10 text-danger rounded hover:bg-danger hover:text-white transition-all">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                <p class="text-text-muted font-medium">No pages found</p>
                                <p class="text-text-muted text-sm mt-1">Create your first static page to get started</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($pages->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $pages->links() }}
        </div>
    @endif
</div>
@endsection
