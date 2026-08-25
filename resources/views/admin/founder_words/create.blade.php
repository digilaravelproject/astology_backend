@extends('admin.layouts.app')

@section('content')
<div class="space-y-8">
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ $word->id ? 'Edit' : 'Add' }} Founder Word</h1>
            <p class="text-sm text-gray font-medium">{{ $word->id ? 'Update the founder' : 'Create a new founder' }} message in multiple languages (English, Hindi, Marathi).</p>
        </div>
        <a href="{{ route('admin.founder_words.index') }}" class="px-4 py-2.5 bg-white border border-gray-lighter rounded-2xl text-sm font-black text-gray hover:bg-light transition-all">Back to Founder Words</a>
    </div>

    @if($errors->any())
        <div class="bg-danger/10 border border-danger/20 text-danger px-6 py-4 rounded-3xl shadow-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm p-8">
        <form method="POST" action="{{ $word->id ? route('admin.founder_words.update', $word->id) : route('admin.founder_words.store') }}" enctype="multipart/form-data">
            @csrf
            @if($word->id)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left Form Column -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- Language Switcher Tabs -->
                    <div>
                        <label class="block text-xs font-black uppercase text-gray tracking-wider mb-2.5">Select Language to Edit Content</label>
                        <div class="inline-flex p-1.5 bg-light/70 border border-gray-lighter rounded-2xl gap-1 w-full sm:w-auto">
                            <button type="button" onclick="switchLangTab('en')" id="tab-btn-en" class="lang-tab-btn flex-1 sm:flex-initial px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 bg-white text-primary shadow-sm">
                                <span>🇬🇧</span>
                                <span>English</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-primary/10 text-primary font-bold">Default</span>
                            </button>
                            <button type="button" onclick="switchLangTab('hi')" id="tab-btn-hi" class="lang-tab-btn flex-1 sm:flex-initial px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 text-gray hover:text-dark">
                                <span>🇮🇳</span>
                                <span>Hindi (हिन्दी)</span>
                            </button>
                            <button type="button" onclick="switchLangTab('mr')" id="tab-btn-mr" class="lang-tab-btn flex-1 sm:flex-initial px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 text-gray hover:text-dark">
                                <span>🇮🇳</span>
                                <span>Marathi (मराठी)</span>
                            </button>
                        </div>
                    </div>

                    <!-- English Content Tab -->
                    <div id="tab-content-en" class="lang-tab-pane space-y-5">
                        <div class="bg-primary/5 border border-primary/15 rounded-2xl px-4 py-3 text-xs text-primary font-semibold flex items-center gap-2">
                            <i class="fas fa-info-circle"></i> English content serves as the default fallback for all languages.
                        </div>
                        <div>
                            <label class="block text-sm font-black text-gray mb-2">Title (English) <span class="text-danger">*</span></label>
                            <input type="text" name="title_en" id="input_title_en" value="{{ old('title_en', $word->title_en ?: $word->title) }}" placeholder="e.g. ASTRO VINOD MISHRA" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm font-medium {{ $errors->has('title_en') ? 'border-danger' : '' }}">
                            @error('title_en')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-black text-gray mb-2">Message (English) <span class="text-danger">*</span></label>
                            <textarea name="message_en" id="input_msg_en" rows="7" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm leading-relaxed {{ $errors->has('message_en') ? 'border-danger' : '' }}" placeholder="Enter the founder's message in English...">{{ old('message_en', $word->message_en ?: $word->message) }}</textarea>
                            @error('message_en')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Hindi Content Tab -->
                    <div id="tab-content-hi" class="lang-tab-pane space-y-5 hidden">
                        <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl px-4 py-3 text-xs text-amber-800 font-semibold flex items-center gap-2">
                            <i class="fas fa-language"></i> Hindi content displayed to users who select Hindi in app.
                        </div>
                        <div>
                            <label class="block text-sm font-black text-gray mb-2">Title (Hindi - हिन्दी)</label>
                            <input type="text" name="title_hi" id="input_title_hi" value="{{ old('title_hi', $word->title_hi) }}" placeholder="e.g. एस्ट्रो विनोद मिश्रा" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm font-medium {{ $errors->has('title_hi') ? 'border-danger' : '' }}">
                            @error('title_hi')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-black text-gray mb-2">Message (Hindi - हिन्दी)</label>
                            <textarea name="message_hi" id="input_msg_hi" rows="7" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm leading-relaxed {{ $errors->has('message_hi') ? 'border-danger' : '' }}" placeholder="संस्थापक का संदेश हिन्दी में दर्ज करें...">{{ old('message_hi', $word->message_hi) }}</textarea>
                            @error('message_hi')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Marathi Content Tab -->
                    <div id="tab-content-mr" class="lang-tab-pane space-y-5 hidden">
                        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl px-4 py-3 text-xs text-emerald-800 font-semibold flex items-center gap-2">
                            <i class="fas fa-language"></i> Marathi content displayed to users who select Marathi in app.
                        </div>
                        <div>
                            <label class="block text-sm font-black text-gray mb-2">Title (Marathi - मराठी)</label>
                            <input type="text" name="title_mr" id="input_title_mr" value="{{ old('title_mr', $word->title_mr) }}" placeholder="e.g. ॲस्ट्रो विनोद मिश्रा" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm font-medium {{ $errors->has('title_mr') ? 'border-danger' : '' }}">
                            @error('title_mr')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-black text-gray mb-2">Message (Marathi - मराठी)</label>
                            <textarea name="message_mr" id="input_msg_mr" rows="7" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm leading-relaxed {{ $errors->has('message_mr') ? 'border-danger' : '' }}" placeholder="संस्थापकांचा संदेश मराठीत प्रविष्ट करा...">{{ old('message_mr', $word->message_mr) }}</textarea>
                            @error('message_mr')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Common Media & Active State -->
                    <div class="pt-4 border-t border-gray-lighter space-y-5">
                        <div>
                            <label class="block text-sm font-black text-gray mb-2">Image</label>
                            <input type="file" name="image" accept="image/*" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark" id="image_input">
                            @error('image')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                            <div class="text-gray text-xs mt-2">Supported formats: JPEG, PNG, JPG, GIF, WebP (Max: 2MB)</div>
                        </div>

                        @if($word->id && $word->image)
                            <div class="bg-light/50 rounded-2xl p-3 border border-gray-lighter flex items-center gap-4">
                                <img src="{{ Storage::url($word->image_url) }}" alt="{{ $word->title }}" class="w-20 h-20 object-cover rounded-xl border border-gray-lighter">
                                <div>
                                    <div class="text-xs font-black text-dark">Current Uploaded Image</div>
                                    <div class="text-[11px] text-gray mt-0.5">Will be preserved unless a new image is selected.</div>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center gap-3 pt-2">
                            <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $word->is_active ?? true) ? 'checked' : '' }} class="w-5 h-5 text-primary border-gray-lighter rounded focus:ring-primary">
                            <label for="is_active" class="text-sm font-bold text-gray cursor-pointer">Active (Visible on frontend/mobile app)</label>
                        </div>
                    </div>
                </div>

                <!-- Right Live Preview Column -->
                <div class="lg:col-span-5">
                    <div class="sticky top-6 bg-light/60 rounded-3xl p-6 border border-gray-lighter shadow-sm space-y-5">
                        <div class="flex items-center justify-between">
                            <div class="text-[11px] font-black text-gray uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Live Mobile Preview
                            </div>
                            <span id="preview_lang_badge" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-primary text-white">English</span>
                        </div>

                        <!-- Card Mockup -->
                        <div class="bg-white rounded-2xl p-5 border border-gray-lighter shadow-sm space-y-4">
                            @if($word->id && $word->image)
                                <div class="w-full h-36 rounded-xl overflow-hidden bg-gray-lighter">
                                    <img src="{{ Storage::url($word->image_url) }}" alt="Preview" class="w-full h-full object-cover">
                                </div>
                            @endif

                            <div>
                                <div class="text-xs font-black uppercase text-primary tracking-wider mb-1">Founder's Word</div>
                                <h3 class="text-base font-black text-dark" id="live_title_preview">ASTRO VINOD MISHRA</h3>
                            </div>

                            <div class="p-3.5 bg-light/50 rounded-xl border border-gray-lighter/80">
                                <p class="text-xs text-gray-700 leading-relaxed italic" id="live_msg_preview">
                                    Welcome! Our app provides authentic astrology guidance...
                                </p>
                            </div>

                            <div class="pt-3 border-t border-gray-lighter flex items-center justify-between text-[11px] text-gray">
                                <span>Status: <strong class="text-emerald-600">Active</strong></span>
                                <span>Multi-Language Ready</span>
                            </div>
                        </div>

                        @if($word->id)
                            <div class="pt-4 border-t border-gray-lighter space-y-2 text-xs">
                                <div class="flex justify-between"><span class="font-bold text-gray">Entry ID:</span><span class="font-black text-dark">#{{ $word->id }}</span></div>
                                <div class="flex justify-between"><span class="font-bold text-gray">Created:</span><span class="text-dark">{{ $word->created_at?->format('M d, Y H:i') }}</span></div>
                                <div class="flex justify-between"><span class="font-bold text-gray">Last Updated:</span><span class="text-dark">{{ $word->updated_at?->format('M d, Y H:i') }}</span></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-lighter flex flex-wrap items-center gap-3">
                <button type="submit" class="px-8 py-3.5 bg-primary text-white rounded-2xl font-black uppercase tracking-wider hover:bg-primary-dark shadow-md shadow-primary/20 transition-all">
                    {{ $word->id ? 'Update' : 'Create' }} Founder Word (Save All Languages)
                </button>
                <a href="{{ route('admin.founder_words.index') }}" class="px-6 py-3.5 border border-gray-lighter rounded-2xl text-gray font-black hover:bg-light transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    let currentLang = 'en';

    function switchLangTab(lang) {
        currentLang = lang;

        // Update tab buttons
        document.querySelectorAll('.lang-tab-btn').forEach(btn => {
            btn.classList.remove('bg-white', 'text-primary', 'shadow-sm');
            btn.classList.add('text-gray');
        });
        const activeBtn = document.getElementById('tab-btn-' + lang);
        if (activeBtn) {
            activeBtn.classList.add('bg-white', 'text-primary', 'shadow-sm');
            activeBtn.classList.remove('text-gray');
        }

        // Show/hide content panes
        document.querySelectorAll('.lang-tab-pane').forEach(pane => pane.classList.add('hidden'));
        const activePane = document.getElementById('tab-content-' + lang);
        if (activePane) {
            activePane.classList.remove('hidden');
        }

        // Update preview badge
        const badge = document.getElementById('preview_lang_badge');
        if (lang === 'en') {
            badge.textContent = 'English';
            badge.className = 'px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-primary text-white';
        } else if (lang === 'hi') {
            badge.textContent = 'Hindi';
            badge.className = 'px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-600 text-white';
        } else if (lang === 'mr') {
            badge.textContent = 'Marathi';
            badge.className = 'px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-600 text-white';
        }

        updateLivePreview();
    }

    function updateLivePreview() {
        const titleInput = document.getElementById('input_title_' + currentLang);
        const msgInput = document.getElementById('input_msg_' + currentLang);
        const fallbackTitle = document.getElementById('input_title_en').value;
        const fallbackMsg = document.getElementById('input_msg_en').value;

        const titleVal = (titleInput && titleInput.value.trim()) ? titleInput.value : (fallbackTitle || 'Enter Title...');
        const msgVal = (msgInput && msgInput.value.trim()) ? msgInput.value : (fallbackMsg || 'Enter Founder Message...');

        document.getElementById('live_title_preview').textContent = titleVal;
        document.getElementById('live_msg_preview').textContent = msgVal;
    }

    ['en', 'hi', 'mr'].forEach(lang => {
        const t = document.getElementById('input_title_' + lang);
        const m = document.getElementById('input_msg_' + lang);
        if (t) t.addEventListener('input', updateLivePreview);
        if (m) m.addEventListener('input', updateLivePreview);
    });

    // Initial update
    updateLivePreview();
</script>
@endsection
