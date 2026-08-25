@extends('admin.layouts.app')

@section('content')
<div class="space-y-8">
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ $word->id ? 'Edit' : 'Add' }} Founder Word</h1>
            <p class="text-sm text-gray font-medium">{{ $word->id ? 'Update the founder' : 'Create a new founder' }} message across all {{ count($languages) }} supported languages.</p>
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

                    <!-- Language Switcher Scrollable Tabs -->
                    <div>
                        <div class="flex items-center justify-between mb-2.5">
                            <label class="block text-xs font-black uppercase text-gray tracking-wider">Select Language to Edit Content ({{ count($languages) }} Languages)</label>
                            <span class="text-[11px] text-gray font-semibold">Click tabs to switch & type</span>
                        </div>
                        <div class="p-1.5 bg-light/70 border border-gray-lighter rounded-2xl flex gap-1.5 overflow-x-auto no-scrollbar">
                            @foreach($languages as $index => $lang)
                                @php
                                    $isDefault = ($lang->code === 'en' || $index === 0);
                                    $hasData = !empty(old("translations.{$lang->code}.title", $word->translations[$lang->code]['title'] ?? ($word->{'title_'.$lang->code} ?? ($isDefault ? $word->title : ''))));
                                @endphp
                                <button type="button" 
                                        onclick="switchLangTab('{{ $lang->code }}', '{{ $lang->name }}')" 
                                        id="tab-btn-{{ $lang->code }}" 
                                        class="lang-tab-btn flex-shrink-0 px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 {{ $isDefault ? 'bg-white text-primary shadow-sm active-tab' : 'text-gray hover:text-dark hover:bg-white/50' }}">
                                    <span>{{ $lang->name }}</span>
                                    <span class="text-[9px] px-1 py-0.5 rounded font-mono uppercase {{ $isDefault ? 'bg-primary/10 text-primary' : 'bg-gray-lighter text-gray' }}">{{ $lang->code }}</span>
                                    @if($isDefault)
                                        <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-primary text-white font-bold">Default</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Language Content Tab Panes -->
                    @foreach($languages as $index => $lang)
                        @php
                            $isDefault = ($lang->code === 'en' || $index === 0);
                            $savedTitle = old("translations.{$lang->code}.title", $word->translations[$lang->code]['title'] ?? ($word->{'title_'.$lang->code} ?? ($isDefault ? ($word->title_en ?: $word->title) : '')));
                            $savedMessage = old("translations.{$lang->code}.message", $word->translations[$lang->code]['message'] ?? ($word->{'message_'.$lang->code} ?? ($isDefault ? ($word->message_en ?: $word->message) : '')));
                        @endphp
                        <div id="tab-content-{{ $lang->code }}" class="lang-tab-pane space-y-5 {{ $isDefault ? '' : 'hidden' }}">
                            <div class="p-3.5 rounded-2xl text-xs font-semibold flex items-center justify-between {{ $isDefault ? 'bg-primary/5 border border-primary/15 text-primary' : 'bg-light/60 border border-gray-lighter text-dark' }}">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-language text-base"></i>
                                    <span>Editing <strong>{{ $lang->name }} ({{ strtoupper($lang->code) }})</strong> Content</span>
                                </div>
                                @if($isDefault)
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-primary/10 text-primary">Required Primary Anchor</span>
                                @else
                                    <span class="text-[10px] text-gray">Auto-fallbacks to English if left empty</span>
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-black text-gray mb-2">
                                    Title ({{ $lang->name }}) 
                                    @if($isDefault)<span class="text-danger">*</span>@endif
                                </label>
                                <input type="text" 
                                       name="translations[{{ $lang->code }}][title]" 
                                       id="input_title_{{ $lang->code }}" 
                                       value="{{ $savedTitle }}" 
                                       placeholder="Enter founder word title in {{ $lang->name }}..." 
                                       class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm font-medium">
                            </div>

                            <div>
                                <label class="block text-sm font-black text-gray mb-2">
                                    Message ({{ $lang->name }}) 
                                    @if($isDefault)<span class="text-danger">*</span>@endif
                                </label>
                                <textarea name="translations[{{ $lang->code }}][message]" 
                                          id="input_msg_{{ $lang->code }}" 
                                          rows="7" 
                                          class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm leading-relaxed" 
                                          placeholder="Enter founder message / quote in {{ $lang->name }}...">{{ $savedMessage }}</textarea>
                            </div>
                        </div>
                    @endforeach

                    <!-- Common Media & Active State -->
                    <div class="pt-4 border-t border-gray-lighter space-y-5">
                        <div>
                            <label class="block text-sm font-black text-gray mb-2">Image (Founder Photo)</label>
                            <input type="file" name="image" accept="image/*" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl focus:outline-none focus:border-primary/50 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark" id="image_input">
                            @error('image')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                            <div class="text-gray text-xs mt-2">Supported formats: JPEG, PNG, JPG, GIF, WebP (Max: 2MB)</div>
                        </div>

                        @if($word->id && $word->image)
                            <div class="bg-light/50 rounded-2xl p-3 border border-gray-lighter flex items-center gap-4">
                                <img src="{{ Storage::url($word->image_url) }}" alt="{{ $word->title }}" class="w-20 h-20 object-cover rounded-xl border border-gray-lighter">
                                <div>
                                    <div class="text-xs font-black text-dark">Current Uploaded Image</div>
                                    <div class="text-[11px] text-gray mt-0.5">Will be preserved unless a new file is uploaded.</div>
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
                                Live App Preview
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
                                <span class="text-primary font-bold"><i class="fas fa-globe"></i> {{ count($languages) }} Languages</span>
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
    let currentLang = '{{ $languages[0]->code ?? "en" }}';
    let currentLangName = '{{ $languages[0]->name ?? "English" }}';

    function switchLangTab(code, name) {
        currentLang = code;
        currentLangName = name;

        // Update tab buttons style
        document.querySelectorAll('.lang-tab-btn').forEach(btn => {
            btn.classList.remove('bg-white', 'text-primary', 'shadow-sm', 'active-tab');
            btn.classList.add('text-gray');
        });
        const activeBtn = document.getElementById('tab-btn-' + code);
        if (activeBtn) {
            activeBtn.classList.add('bg-white', 'text-primary', 'shadow-sm', 'active-tab');
            activeBtn.classList.remove('text-gray');
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

    // Attach real-time input event listeners to all languages
    document.querySelectorAll('[id^="input_title_"], [id^="input_msg_"]').forEach(el => {
        el.addEventListener('input', updateLivePreview);
    });

    // Initial preview setup
    updateLivePreview();
</script>
@endsection
