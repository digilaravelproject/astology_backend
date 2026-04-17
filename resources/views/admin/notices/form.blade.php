@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.notices.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Notices
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">{{ isset($notice) && $notice->id ? 'Edit' : 'New' }} Notice</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Create system announcements and notifications</p>
        </div>
        <div class="flex gap-4">
            <button form="noticeForm" type="submit" class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 flex items-center gap-3">
                <i class="fas fa-save"></i> {{ isset($notice) && $notice->id ? 'Update' : 'Create' }} Notice
            </button>
        </div>
    </div>

    <!-- Form -->
    <form id="noticeForm" action="{{ isset($notice) && $notice->id ? route('admin.notices.update', $notice->id) : route('admin.notices.store') }}" method="POST" class="max-w-[1000px]">
        @csrf
        @if(isset($notice) && $notice->id)
            @method('PUT')
        @endif

        <div class="space-y-10">
            <!-- Section 1: Core Content -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> 01. Notice Content
                </h2>
                
                <div class="space-y-8">
                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Title</label>
                        <input type="text" name="title" value="{{ old('title', $notice->title ?? '') }}" placeholder="Notice title..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-lg font-black text-dark placeholder:text-gray-light focus:bg-white focus:border-primary/20 focus:ring-0 transition-all" required>
                        @error('title') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Description/Body</label>
                        <textarea name="body" rows="8" placeholder="Detailed notice content..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-medium text-dark placeholder:text-gray-light focus:bg-white focus:border-primary/20 focus:ring-0 transition-all resize-none" required>{{ old('body', $notice->body ?? '') }}</textarea>
                        @error('body') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Settings -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-info/30"></span> 02. Notice Settings
                </h2>
                
                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Category/Tag</label>
                            <select name="tag" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark focus:bg-white focus:border-info/20 focus:ring-0 transition-all" required>
                                <option value="">Select Tag</option>
                                <option value="announcement" {{ old('tag', $notice->tag ?? '') === 'announcement' ? 'selected' : '' }}>Announcement</option>
                                <option value="maintenance" {{ old('tag', $notice->tag ?? '') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="feature" {{ old('tag', $notice->tag ?? '') === 'feature' ? 'selected' : '' }}>Feature Update</option>
                                <option value="security" {{ old('tag', $notice->tag ?? '') === 'security' ? 'selected' : '' }}>Security Alert</option>
                                <option value="policy" {{ old('tag', $notice->tag ?? '') === 'policy' ? 'selected' : '' }}>Policy Change</option>
                                <option value="update" {{ old('tag', $notice->tag ?? '') === 'update' ? 'selected' : '' }}>System Update</option>
                            </select>
                            @error('tag') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Icon/Emoji</label>
                            <input type="text" name="icon" value="{{ old('icon', $notice->icon ?? '📢') }}" placeholder="e.g. 📢, ⚠️, ✨..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-lg font-black text-dark placeholder:text-gray-light focus:bg-white focus:border-info/20 focus:ring-0 transition-all">
                            @error('icon') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Urgency Level</label>
                            <select name="is_urgent" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark focus:bg-white focus:border-info/20 focus:ring-0 transition-all">
                                <option value="0" {{ old('is_urgent', $notice->is_urgent ?? 0) == 0 ? 'selected' : '' }}>Normal Priority</option>
                                <option value="1" {{ old('is_urgent', $notice->is_urgent ?? 0) == 1 ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Status</label>
                            <select name="is_active" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark focus:bg-white focus:border-info/20 focus:ring-0 transition-all">
                                <option value="0" {{ old('is_active', $notice->is_active ?? 0) == 0 ? 'selected' : '' }}>Draft/Inactive</option>
                                <option value="1" {{ old('is_active', $notice->is_active ?? 0) == 1 ? 'selected' : '' }}>Active/Published</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Metadata -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> 03. Additional Info
                </h2>
                
                <div class="space-y-8">
                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Target Audience (Optional)</label>
                        <select name="target_audience" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark focus:bg-white focus:border-success/20 focus:ring-0 transition-all">
                            <option value="">All Users</option>
                            <option value="users" {{ old('target_audience', $notice->target_audience ?? '') === 'users' ? 'selected' : '' }}>Users Only</option>
                            <option value="astrologers" {{ old('target_audience', $notice->target_audience ?? '') === 'astrologers' ? 'selected' : '' }}>Astrologers Only</option>
                            <option value="admins" {{ old('target_audience', $notice->target_audience ?? '') === 'admins' ? 'selected' : '' }}>Admins Only</option>
                        </select>
                    </div>

                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Internal Notes</label>
                        <textarea name="notes" rows="4" placeholder="Add any internal notes or context..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-medium text-dark placeholder:text-gray-light focus:bg-white focus:border-success/20 focus:ring-0 transition-all resize-none">{{ old('notes', $notice->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
