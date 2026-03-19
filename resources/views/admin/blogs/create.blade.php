@extends('admin.layouts.app')

@section('content')
<div x-data="{ 
    tags: ['Astrology', 'Jupiter', '2024'], 
    newTag: '',
    addTag() {
        if (this.newTag.trim() !== '' && !this.tags.includes(this.newTag.trim())) {
            this.tags.push(this.newTag.trim());
            this.newTag = '';
        }
    },
    removeTag(index) {
        this.tags.splice(index, 1);
    }
}">
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.blogs.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Registry Overview
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Editorial Suite</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Crafting educational experiences for the global spiritual community.</p>
        </div>
        <div class="flex gap-4">
            <button form="blogForm" type="submit" class="px-8 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all">Save Draft</button>
            <button form="blogForm" type="submit" class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-primary transition-all shadow-xl shadow-dark/20 flex items-center gap-3">
                <i class="fas fa-paper-plane"></i> Publish
            </button>
        </div>
    </div>

    <!-- Blog Form -->
    <form id="blogForm" action="{{ isset($blog) && $blog->id ? route('admin.blogs.update', $blog->id) : route('admin.blogs.store') }}" method="POST" class="max-w-[1200px]">
        @csrf
        @if(isset($blog) && $blog->id)
            @method('PUT')
        @endif
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            
            <!-- Mid Column: Article & SEO -->
            <div class="lg:col-span-3 space-y-10">
                
                <!-- Section 1: Core Identity -->
                <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                    <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                        <span class="w-8 h-px bg-primary/30"></span> 01. Article Identity
                    </h2>
                    
                    <div class="space-y-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3 group-focus-within:text-dark transition-colors">Manifesto Title</label>
                            <input type="text" name="title" value="{{ old('title', $blog->title ?? '') }}" placeholder="e.g. The Quantum Mechanics of Mercury Retrograde..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-lg font-black text-dark placeholder:text-gray-light focus:bg-white focus:border-primary/20 focus:ring-0 transition-all">
                            @error('title') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="group">
                                <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Subtitle</label>
                                <input type="text" name="subtitle" value="{{ old('subtitle', $blog->subtitle ?? '') }}" placeholder="Add a subtitle or summary..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-xs font-black text-dark placeholder:text-gray-light focus:bg-white focus:border-primary/20 focus:ring-0 transition-all">
                                @error('subtitle') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="group">
                                <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Author</label>
                                <input type="text" name="author" value="{{ old('author', $blog->author ?? '') }}" placeholder="e.g. Dr. Vinay Bajrangi" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-xs font-black text-dark placeholder:text-gray-light focus:bg-white focus:border-primary/20 focus:ring-0 transition-all">
                                @error('author') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Narrative Flow -->
                <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                    <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                        <span class="w-8 h-px bg-info/30"></span> 02. Narrative Body
                    </h2>
                    
                    <div>
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Article Content</label>
                        <textarea name="content" rows="16" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-medium text-dark placeholder:text-gray-light focus:bg-white focus:border-primary/20 focus:ring-0 transition-all">{{ old('content', $blog->content ?? '') }}</textarea>
                        @error('content') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Section 3: Metadata Strength (SEO) -->
                <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                    <div class="flex justify-between items-center mb-10">
                        <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] flex items-center gap-3">
                            <span class="w-8 h-px bg-success/30"></span> 03. Visibility Logic (SEO)
                        </h2>
                        <div class="px-4 py-2 bg-success/10 text-success text-[10px] font-black uppercase rounded-full border border-success/20">Strength: 92%</div>
                    </div>
                    
                    <div class="space-y-8">
                        <div>
                            <div class="flex justify-between mb-3">
                                <label class="text-[10px] font-black text-gray uppercase tracking-widest">Meta Descriptor</label>
                                <span class="text-[9px] font-bold text-gray uppercase">142 / 160 Characters</span>
                            </div>
                            <textarea rows="3" placeholder="How will this appear in cosmic search results?..." class="w-full bg-light/30 border-none px-6 py-5 rounded-[24px] text-xs font-bold text-dark focus:bg-white transition-all resize-none"></textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">SEO Permalink</label>
                                <div class="bg-light/30 px-6 py-5 rounded-[24px] text-xs font-bold text-gray flex items-center gap-2 border-2 border-transparent focus-within:border-primary/10 transition-all">
                                    <span>.../journal/</span>
                                    <input type="text" value="jupiter-transit-impact-2024" class="bg-transparent border-none p-0 focus:ring-0 text-dark font-black w-full">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Robots Directive</label>
                                <div class="flex gap-4">
                                    <button type="button" class="flex-1 py-4 bg-dark text-white rounded-2xl text-[10px] font-black uppercase tracking-widest">Index</button>
                                    <button type="button" class="flex-1 py-4 bg-light text-gray rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-gray-lighter transition-all">No-Follow</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Media & Social -->
            <div class="space-y-10">
                <!-- Media Assets -->
                <div class="bg-white p-8 rounded-[48px] border border-gray-lighter shadow-sm">
                    <h2 class="text-[10px] font-black text-dark uppercase tracking-[0.3em] mb-6">Visual Core</h2>
                    <div class="group relative aspect-3/4 bg-light rounded-[32px] overflow-hidden border-2 border-dashed border-gray-lighter hover:border-primary transition-all cursor-pointer">
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center z-10">
                            <div class="w-16 h-16 bg-white shadow-lg rounded-2xl flex items-center justify-center text-primary text-xl mb-4 group-hover:scale-110 transition-all">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <span class="text-[10px] font-black text-dark uppercase tracking-widest">Drop Canvas</span>
                            <span class="text-[9px] text-gray mt-2 italic font-medium">Portrait (3:4) recommended for max impact</span>
                        </div>
                        <div class="absolute inset-0 bg-dark/5 opacity-0 group-hover:opacity-100 transition-all"></div>
                    </div>
                </div>

                <!-- Intelligent Tags -->
                <div class="bg-white p-8 rounded-[48px] border border-gray-lighter shadow-sm">
                    <h2 class="text-[10px] font-black text-dark uppercase tracking-[0.3em] mb-6">Taxonomy</h2>
                    <div class="space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(tag, index) in tags" :key="index">
                                <span class="px-3 py-1.5 bg-primary/10 text-primary text-[9px] font-black uppercase rounded-full border border-primary/20 flex items-center gap-2 group hover:bg-primary hover:text-white transition-all cursor-default">
                                    <span x-text="tag"></span>
                                    <i @click="removeTag(index)" class="fas fa-times cursor-pointer opacity-50 hover:opacity-100"></i>
                                </span>
                            </template>
                        </div>
                        <div class="relative mt-4">
                            <input type="text" x-model="newTag" @keydown.enter.prevent="addTag" placeholder="Add custom tag..." class="w-full bg-light/30 border-none px-5 py-4 rounded-2xl text-[10px] font-black text-dark focus:bg-white transition-all uppercase tracking-widest placeholder:text-gray-light">
                            <button @click.prevent="addTag" class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 bg-dark text-white rounded-xl flex items-center justify-center hover:bg-primary transition-all">
                                <i class="fas fa-plus text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Publish Settings -->
                <div class="bg-white p-8 rounded-[48px] border border-gray-lighter shadow-sm">
                    <h2 class="text-[10px] font-black text-dark uppercase tracking-[0.3em] mb-6">Publication Settings</h2>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs font-black text-gray uppercase tracking-widest mb-2">Status</div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_active" value="1" {{ old('is_active', $blog->is_active ?? false) == 1 ? 'checked' : '' }} class="form-radio h-4 w-4 text-primary" />
                                    <span class="text-xs font-bold text-dark">Active</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_active" value="0" {{ old('is_active', $blog->is_active ?? false) == 0 ? 'checked' : '' }} class="form-radio h-4 w-4 text-gray" />
                                    <span class="text-xs font-bold text-dark">Inactive</span>
                                </label>
                            </div>
                        </div>
                        <div class="text-xs font-black text-gray uppercase tracking-widest">Created: {{ optional($blog->created_at)->format('d M Y') ?? 'N/A' }}</div>
                    </div>
                </div>

                <!-- Engagement Tuning -->
                <div class="bg-white p-8 rounded-[48px] border border-gray-lighter shadow-sm">
                    <h2 class="text-[10px] font-black text-dark uppercase tracking-[0.3em] mb-6">Audience Logic</h2>
                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between mb-3">
                                <label class="text-[9px] font-black text-gray uppercase">Intimacy Level</label>
                                <span class="text-[9px] font-black text-primary uppercase">Expert</span>
                            </div>
                            <input type="range" class="w-full accent-primary h-1 bg-light rounded-lg appearance-none cursor-pointer">
                        </div>
                        <div class="flex items-center justify-between p-4 bg-light/30 rounded-2xl">
                            <span class="text-[10px] font-black text-dark uppercase">Push Alert</span>
                            <div class="relative inline-block w-10 h-6">
                                <input type="checkbox" checked class="peer opacity-0 w-0 h-0">
                                <span class="absolute cursor-pointer top-0 left-0 right-0 bottom-0 bg-gray-lighter transition-all rounded-full before:absolute before:h-4 before:w-4 before:left-1 before:bottom-1 before:bg-white before:transition-all before:rounded-full peer-checked:bg-success peer-checked:before:translate-x-4"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
