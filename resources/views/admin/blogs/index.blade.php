@extends('admin.layouts.app')

@section('content')
<div x-data="{ blogModal: false, selectedBlog: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Content Repository</h1>
            <p class="text-sm text-gray font-medium">Intellectual property and educational outreach management.</p>
        </div>
        <div class="flex gap-3">
            <button class="bg-white border border-gray-lighter text-dark px-5 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-layer-group mr-2"></i> Categories
            </button>
            <a href="{{ route('admin.blogs.create') }}" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <i class="fas fa-feather-alt"></i> Draft New Entry
            </a>
        </div>
    </div>

    <!-- Content Intelligence -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-primary/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Publications</div>
            <div class="text-3xl font-black text-dark">{{ $total ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-arrow-up"></i> {{ $total > 0 ? number_format(($active / max($total,1)) * 100, 1) : 0 }}% Active
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-success/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Active Posts</div>
            <div class="text-3xl font-black text-dark">{{ $active ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-check-circle"></i> Published
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-info/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Drafts</div>
            <div class="text-3xl font-black text-dark">{{ $drafts ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-info font-black text-[9px] uppercase">
                <i class="fas fa-edit"></i> Needs Review
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-warning/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Recent Posts</div>
            <div class="text-3xl font-black text-dark">{{ $blogs->count() }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-warning font-black text-[9px] uppercase">
                <i class="fas fa-clock"></i> This Page
            </div>
        </div>
    </div>

    <!-- Filter Console -->
    <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm mb-8 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Content Filter</label>
            <form method="GET" action="{{ route('admin.blogs.index') }}" class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray transition-colors group-focus-within:text-dark"></i>
                <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by title, keyword, or author..." class="w-full bg-light/50 border border-gray-lighter pl-11 pr-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all focus:bg-white focus:shadow-sm">
            </form>
        </div>
        <div class="w-full sm:w-48">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Type</label>
            <form method="GET" action="{{ route('admin.blogs.index') }}">
                <select name="type" onchange="this.form.submit()" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                    <option value="">All Types</option>
                    @foreach(\App\Models\Blog::types() as $value => $label)
                        <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="w-full sm:w-48">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Status</label>
            <form method="GET" action="{{ route('admin.blogs.index') }}">
                <select name="status" onchange="this.form.submit()" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                    <option value="" {{ request('status') === null ? 'selected' : '' }}>All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </form>
        </div>
        <button onclick="location.href='{{ route('admin.blogs.create') }}'" class="bg-dark text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 transform active:scale-95 h-[52px]">New Post</button>
    </div>

    <!-- Repository Grid/Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Article Manifest</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Metadata</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Curator</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Metrics</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Ops</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($blogs as $blog)
                        <tr class="hover:bg-light/30 transition-all group">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-2xl bg-light border border-gray-lighter flex items-center justify-center">
                                        <span class="text-xs font-black text-gray">B-{{ $blog->id }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-dark line-clamp-1 group-hover:text-primary transition-colors cursor-pointer"
                                             @click="selectedBlog = {{ json_encode($blog) }}; blogModal = true">{{ $blog->title }}</div>
                                        <div class="text-[9px] font-bold text-gray uppercase mt-1 tracking-widest">{{ $blog->subtitle ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest">Type</div>
                                <div class="text-sm font-black text-dark">{{ \App\Models\Blog::types()[$blog->type] ?? ucfirst($blog->type) }}</div>
                                <div class="mt-2 text-[9px] text-gray">Tags: {{ $blog->blog_tags ? implode(', ', $blog->blog_tags) : '-' }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-xs font-bold text-dark italic underline decoration-primary/20 decoration-2 underline-offset-4">{{ $blog->author ?? '-' }}</div>
                                <div class="text-[9px] font-bold text-gray-light uppercase mt-1">{{ $blog->created_at?->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-5 border-l border-gray-lighter/30">
                                <div class="text-sm font-black text-dark">{{ $blog->created_at?->diffForHumans() }}</div>
                                <div class="text-[8px] font-black text-success uppercase">Updated {{ $blog->updated_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-5">
                                @if($blog->is_active)
                                    <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20">Active</span>
                                @else
                                    <span class="px-3 py-1 bg-gray/10 text-gray text-[9px] font-black uppercase rounded-full border border-gray/20">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.blogs.edit', $blog->id) }}" class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-dark hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.blogs.destroy', $blog->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-10 h-10 bg-white border border-gray-lighter text-danger rounded-xl flex items-center justify-center hover:bg-danger hover:text-white transition-all shadow-sm">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray">No blog posts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing {{ $blogs->firstItem() ?? 0 }} to {{ $blogs->lastItem() ?? 0 }} of {{ $blogs->total() }} posts</div>
            <div>
                {{ $blogs->withQueryString()->links() }}
            </div>
        </div>
    </div>

    <!-- Blog Preview Modal -->
    <div x-show="blogModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-4xl rounded-[40px] shadow-[0_40px_120px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col md:flex-row h-full max-h-[85vh]" @click.away="blogModal = false">
            <!-- Left Side Image -->
            <div class="w-full md:w-1/3 bg-dark relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-dark via-transparent to-black opacity-80"></div>
                    <div class="absolute inset-0 flex flex-col justify-end p-8">
                        <div class="px-4 py-2 bg-primary/20 backdrop-blur-md border border-primary/30 rounded-full inline-block text-[10px] font-black text-white uppercase tracking-widest w-fit mb-4" x-text="selectedBlog.author || 'Author' "></div>
                </div>
            </div>

            <!-- Right Side Content -->
            <div class="w-full md:w-2/3 flex flex-col">
                <div class="p-8 border-b border-gray-lighter flex justify-between items-center bg-light/30">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full border-2 border-primary overflow-hidden">
                            <img src="https://i.pravatar.cc/150?u=admin" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <div class="text-xs font-black text-dark uppercase tracking-widest" x-text="selectedBlog.author"></div>
                            <div class="text-[9px] font-bold text-gray uppercase mt-1" x-text="'Verified Curator • ' + selectedBlog.date"></div>
                        </div>
                    </div>
                    <button @click="blogModal = false" class="w-12 h-12 bg-white hover:bg-gray-lighter text-gray rounded-2xl flex items-center justify-center transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-10 flex-1 overflow-y-auto custom-scrollbar">
                    <div class="prose prose-sm max-w-none">
                        <p class="text-dark font-black text-lg mb-6 leading-relaxed">Celestial movements are signaling a profound shift in market dynamics as Jupiter prepares to transit. This period marks a pivotal moment for those seeking clarity in fiscal growth.</p>
                        <div class="grid grid-cols-2 gap-6 mb-8">
                            <div class="bg-light/40 p-6 rounded-3xl border border-gray-lighter">
                                <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Impact Radius</div>
                                <div class="text-sm font-black text-dark">Global Financial Hubs</div>
                            </div>
                            <div class="bg-light/40 p-6 rounded-3xl border border-gray-lighter">
                                <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Key Remedy</div>
                                <div class="text-sm font-black text-dark">Yellow Sapphire / Rituals</div>
                            </div>
                        </div>
                        <p class="text-gray-light leading-relaxed mb-6 font-medium" x-text="selectedBlog.content || 'No content available yet.'"></p>
                    </div>
                </div>

                <div class="p-8 border-t border-gray-lighter bg-light/30 flex justify-between items-center">
                    <div class="flex gap-4">
                        <div class="text-center">
                            <div class="text-lg font-black text-dark" x-text="selectedBlog.created_at ? new Date(selectedBlog.created_at).toLocaleDateString() : '-' "></div>
                            <div class="text-[8px] font-black text-gray uppercase tracking-widest">Created</div>
                        </div>
                        <div class="w-px h-8 bg-gray-lighter mx-2"></div>
                        <div class="text-center">
                            <div class="text-lg font-black text-dark" x-text="selectedBlog.updated_at ? new Date(selectedBlog.updated_at).toLocaleDateString() : '-' "></div>
                            <div class="text-[8px] font-black text-gray uppercase tracking-widest">Updated</div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a :href="selectedBlog.id ? '/admin/blogs/' + selectedBlog.id + '/edit' : '#'
                        " class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20">Edit Entry</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
