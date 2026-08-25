@extends('admin.layouts.app')

@section('content')
<div class="space-y-6 max-w-full overflow-hidden">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-dark tracking-tight">{{ $word->id ? 'Edit' : 'Add' }} Founder Word</h1>
            <p class="text-xs sm:text-sm text-gray font-medium mt-1">Manage founder message across all {{ count($languages) }} supported languages.</p>
        </div>
        <a href="{{ route('admin.founder_words.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-lighter rounded-2xl text-xs sm:text-sm font-black text-gray hover:bg-light transition-all shadow-sm w-fit">
            <i class="fas fa-arrow-left mr-2 text-xs"></i> Back to List
        </a>
    </div>

    @if($errors->any())
        <div class="bg-danger/10 border border-danger/20 text-danger px-5 py-4 rounded-2xl sm:rounded-3xl shadow-sm text-xs sm:text-sm">
            <ul class="list-disc list-inside space-y-1 font-semibold">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl sm:rounded-[32px] border border-gray-lighter shadow-sm p-4 sm:p-6 lg:p-8">
        <form method="POST" action="{{ $word->id ? route('admin.founder_words.update', $word->id) : route('admin.founder_words.store') }}" enctype="multipart/form-data">
            @csrf
            @if($word->id)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
                <!-- Left Form Column -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- Language Switcher Scrollable Tabs -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-[11px] sm:text-xs font-black uppercase text-gray tracking-wider">
                                Content Languages ({{ count($languages) }})
                            </label>
                            <span class="text-[10px] text-primary font-bold flex items-center gap-1">
                                <i class="fas fa-arrows-alt-h sm:hidden"></i> Swipe for more
                            </span>
                        </div>

                        <!-- Horizontal Scroll Tabs Bar -->
                        <div class="relative">
                            <div class="p-1.5 bg-light/80 border border-gray-lighter rounded-2xl flex gap-1.5 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 touch-pan-x" style="-webkit-overflow-scrolling: touch;">
                                @foreach($languages as $index => $lang)
                                    @php
                                        $isDefault = ($lang->code === 'en' || $index === 0);
                                    @endphp
                                    <button type="button" 
                                            onclick="switchLangTab('{{ $lang->code }}', '{{ $lang->name }}')" 
                                            id="tab-btn-{{ $lang->code }}" 
                                            class="lang-tab-btn flex-shrink-0 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 {{ $isDefault ? 'bg-white text-primary shadow-sm active-tab' : 'text-gray hover:text-dark hover:bg-white/60' }}">
                                        <span>{{ $lang->name }}</span>
                                        <span class="text-[9px] px-1 py-0.5 rounded font-mono uppercase {{ $isDefault ? 'bg-primary/10 text-primary' : 'bg-gray-200 text-gray' }}">{{ $lang->code }}</span>
                                        @if($isDefault)
                                            <span class="text-[8px] px-1.5 py-0.5 rounded-full bg-primary text-white font-bold hidden sm:inline-block">Default</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Language Content Panes -->
                    @foreach($languages as $index => $lang)
                        @php
                            $isDefault = ($lang->code === 'en' || $index === 0);
                            $savedTitle = old("translations.{$lang->code}.title", $word->translations[$lang->code]['title'] ?? ($word->{'title_'.$lang->code} ?? ($isDefault ? ($word->title_en ?: $word->title) : '')));
                            $savedMessage = old("translations.{$lang->code}.message", $word->translations[$lang->code]['message'] ?? ($word->{'message_'.$lang->code} ?? ($isDefault ? ($word->message_en ?: $word->message) : '')));
                        @endphp
                        <div id="tab-content-{{ $lang->code }}" class="lang-tab-pane space-y-4 sm:space-y-5 {{ $isDefault ? '' : 'hidden' }}">
                            <div class="p-3 sm:p-3.5 rounded-xl sm:rounded-2xl text-xs font-semibold flex flex-col sm:flex-row sm:items-center justify-between gap-2 {{ $isDefault ? 'bg-primary/5 border border-primary/15 text-primary' : 'bg-light/60 border border-gray-lighter text-dark' }}">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-language text-base text-primary"></i>
                                    <span>Language: <strong>{{ $lang->name }} ({{ strtoupper($lang->code) }})</strong></span>
                                </div>
                                @if($isDefault)
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-primary/10 text-primary w-fit">Primary Language (Default)</span>
                                @else
                                    <span class="text-[10px] text-gray w-fit">Falls back to English if empty</span>
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-black text-gray mb-1.5 sm:mb-2">
                                    Title ({{ $lang->name }}) 
                                    @if($isDefault)<span class="text-danger">*</span>@endif
                                </label>
                                <input type="text" 
                                       name="translations[{{ $lang->code }}][title]" 
                                       id="input_title_{{ $lang->code }}" 
                                       value="{{ $savedTitle }}" 
                                       placeholder="e.g. ASTRO VINOD MISHRA" 
                                       class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 border border-gray-lighter rounded-xl sm:rounded-2xl focus:outline-none focus:border-primary/50 text-xs sm:text-sm font-medium transition-all">
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-black text-gray mb-1.5 sm:mb-2">
                                    Message / Quote ({{ $lang->name }}) 
                                    @if($isDefault)<span class="text-danger">*</span>@endif
                                </label>
                                <textarea name="translations[{{ $lang->code }}][message]" 
                                          id="input_msg_{{ $lang->code }}" 
                                          rows="6" 
                                          class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 border border-gray-lighter rounded-xl sm:rounded-2xl focus:outline-none focus:border-primary/50 text-xs sm:text-sm leading-relaxed transition-all" 
                                          placeholder="Enter founder message / quote in {{ $lang->name }}...">{{ $savedMessage }}</textarea>
                            </div>
                        </div>
                    @endforeach

                    <!-- Common Media & Active State -->
                    <div class="pt-4 border-t border-gray-lighter space-y-4 sm:space-y-5">
                        <div>
                            <label class="block text-xs sm:text-sm font-black text-gray mb-1.5 sm:mb-2">Image (Founder Photo)</label>
                            <input type="file" name="image" accept="image/*" class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-lighter rounded-xl sm:rounded-2xl focus:outline-none focus:border-primary/50 text-xs sm:text-sm file:mr-3 sm:file:mr-4 file:py-1.5 file:px-3 sm:file:py-2 sm:file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark cursor-pointer" id="image_input">
                            @error('image')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                            <div class="text-gray text-[11px] mt-1.5">Supported formats: JPEG, PNG, JPG, GIF, WebP (Max: 2MB)</div>
                        </div>

                        @if($word->id && $word->image)
                            <div class="bg-light/50 rounded-2xl p-3 border border-gray-lighter flex items-center gap-3 sm:gap-4">
                                <img src="{{ Storage::url($word->image_url) }}" alt="{{ $word->title }}" class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-xl border border-gray-lighter flex-shrink-0">
                                <div>
                                    <div class="text-xs font-black text-dark">Current Uploaded Image</div>
                                    <div class="text-[11px] text-gray mt-0.5">Will remain active unless replaced.</div>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center gap-3 pt-1">
                            <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $word->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 sm:w-5 sm:h-5 text-primary border-gray-lighter rounded focus:ring-primary cursor-pointer">
                            <label for="is_active" class="text-xs sm:text-sm font-bold text-gray cursor-pointer">Active (Visible on frontend & mobile app)</label>
                        </div>
                    </div>
                </div>

                <!-- Right Live Preview Column -->
                <div class="lg:col-span-5">
                    <div class="lg:sticky lg:top-6 bg-light/60 rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-gray-lighter shadow-sm space-y-4 sm:space-y-5">
                        <div class="flex items-center justify-between">
                            <div class="text-[10px] sm:text-[11px] font-black text-gray uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Live App Preview
                            </div>
                            <span id="preview_lang_badge" class="px-2.5 py-0.5 sm:py-1 rounded-full text-[10px] font-black uppercase bg-primary text-white">English</span>
                        </div>

                        <!-- Card Mockup -->
                        <div class="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-5 border border-gray-lighter shadow-sm space-y-3 sm:space-y-4">
                            <div class="w-full max-h-72 rounded-2xl overflow-hidden bg-light/60 border border-gray-lighter flex items-center justify-center p-1.5" id="preview_image_container">
                                <img id="live_img_preview" 
                                     src="{{ $word->image ? Storage::url($word->image_url) : '' }}" 
                                     alt="Founder Preview" 
                                     class="w-full max-h-64 object-contain rounded-xl transition-all {{ ($word->id && $word->image) ? '' : 'hidden' }}">
                                <div id="no_img_placeholder" class="py-8 text-gray text-xs flex flex-col items-center gap-2 {{ ($word->id && $word->image) ? 'hidden' : '' }}">
                                    <i class="fas fa-image text-2xl text-gray-300"></i>
                                    <span class="text-[11px] font-semibold text-gray-400">Founder Photo Preview</span>
                                </div>
                            </div>

                            <div>
                                <div class="text-[10px] sm:text-xs font-black uppercase text-primary tracking-wider mb-1">Founder's Word</div>
                                <h3 class="text-sm sm:text-base font-black text-dark break-words" id="live_title_preview">ASTRO VINOD MISHRA</h3>
                            </div>

                            <div class="p-3 sm:p-3.5 bg-light/50 rounded-xl border border-gray-lighter/80">
                                <p class="text-xs text-gray-700 leading-relaxed italic break-words whitespace-pre-line" id="live_msg_preview">
                                    Welcome! Our app provides authentic astrology guidance...
                                </p>
                            </div>

                            <div class="pt-2.5 border-t border-gray-lighter flex items-center justify-between text-[10px] sm:text-[11px] text-gray">
                                <span>Status: <strong class="text-emerald-600">Active</strong></span>
                                <span class="text-primary font-bold"><i class="fas fa-globe mr-1"></i>{{ count($languages) }} Languages</span>
                            </div>
                        </div>

                        @if($word->id)
                            <div class="pt-3 border-t border-gray-lighter space-y-1.5 text-xs text-gray">
                                <div class="flex justify-between"><span>ID:</span><strong class="text-dark">#{{ $word->id }}</strong></div>
                                <div class="flex justify-between"><span>Created:</span><span class="text-dark">{{ $word->created_at?->format('M d, Y H:i') }}</span></div>
                                <div class="flex justify-between"><span>Updated:</span><span class="text-dark">{{ $word->updated_at?->format('M d, Y H:i') }}</span></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Submit Action Buttons -->
            <div class="mt-6 sm:mt-8 pt-6 border-t border-gray-lighter flex flex-col sm:flex-row items-center gap-3">
                <button type="submit" class="w-full sm:w-auto px-8 py-3.5 bg-primary text-white rounded-xl sm:rounded-2xl font-black uppercase tracking-wider text-xs sm:text-sm hover:bg-primary-dark shadow-md shadow-primary/20 transition-all text-center">
                    {{ $word->id ? 'Update' : 'Create' }} Founder Word (Save All)
                </button>
                <a href="{{ route('admin.founder_words.index') }}" class="w-full sm:w-auto px-6 py-3.5 border border-gray-lighter rounded-xl sm:rounded-2xl text-gray font-black hover:bg-light transition-all text-xs sm:text-sm text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    let currentLang = '{{ $languages[0]->code ?? "en" }}';
    let currentLangName = '{{ $languages[0]->name ?? "English" }}';

    function switchLangTab(code, name) {
        currentLang = code;
        currentLangName = name;

        // Update tab buttons
        document.querySelectorAll('.lang-tab-btn').forEach(btn => {
            btn.classList.remove('bg-white', 'text-primary', 'shadow-sm', 'active-tab');
            btn.classList.add('text-gray');
        });
        const activeBtn = document.getElementById('tab-btn-' + code);
        if (activeBtn) {
            activeBtn.classList.add('bg-white', 'text-primary', 'shadow-sm', 'active-tab');
            activeBtn.classList.remove('text-gray');
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

        // Show/hide content panes
        document.querySelectorAll('.lang-tab-pane').forEach(pane => pane.classList.add('hidden'));
        const activePane = document.getElementById('tab-content-' + code);
        if (activePane) {
            activePane.classList.remove('hidden');
        }

        // Update preview badge
        const badge = document.getElementById('preview_lang_badge');
        if (badge) {
            badge.textContent = name;
        }

        updateLivePreview();
    }

    function updateLivePreview() {
        const titleInput = document.getElementById('input_title_' + currentLang);
        const msgInput = document.getElementById('input_msg_' + currentLang);
        const defaultTitleInput = document.getElementById('input_title_en') || document.querySelector('[id^="input_title_"]');
        const defaultMsgInput = document.getElementById('input_msg_en') || document.querySelector('[id^="input_msg_"]');

        const fallbackTitle = defaultTitleInput ? defaultTitleInput.value : '';
        const fallbackMsg = defaultMsgInput ? defaultMsgInput.value : '';

        const titleVal = (titleInput && titleInput.value.trim()) ? titleInput.value : (fallbackTitle || 'Enter Title...');
        const msgVal = (msgInput && msgInput.value.trim()) ? msgInput.value : (fallbackMsg || 'Enter Founder Message...');

        document.getElementById('live_title_preview').textContent = titleVal;
        document.getElementById('live_msg_preview').textContent = msgVal;
    }

    document.querySelectorAll('[id^="input_title_"], [id^="input_msg_"]').forEach(el => {
        el.addEventListener('input', updateLivePreview);
    });

    const imageInput = document.getElementById('image_input');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    const img = document.getElementById('live_img_preview');
                    const placeholder = document.getElementById('no_img_placeholder');
                    if (img) {
                        img.src = evt.target.result;
                        img.classList.remove('hidden');
                    }
                    if (placeholder) {
                        placeholder.classList.add('hidden');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    updateLivePreview();
</script>
@endsection
