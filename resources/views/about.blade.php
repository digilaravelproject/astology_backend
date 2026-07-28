@section('title', 'About Us - ' . config('app.name', 'Surya Path'))

@include('layouts.header')

<style>
    .dynamic-content p {
        margin-bottom: 1.25rem;
        line-height: 1.7;
    }
    .dynamic-content h2, .dynamic-content h3 {
        color: #E1A61B;
        font-family: 'Marcellus', serif !important;
        font-weight: bold;
        margin-top: 1.75rem;
        margin-bottom: 0.75rem;
    }
    .dynamic-content h2 { font-size: 1.5rem; }
    .dynamic-content h3 { font-size: 1.25rem; }
    .dynamic-content ul, .dynamic-content ol {
        margin-left: 1.5rem;
        margin-bottom: 1.25rem;
        list-style-type: disc;
    }
    .dynamic-content li {
        margin-bottom: 0.5rem;
    }
</style>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 relative z-10">
    <div class="bg-white dark:bg-[#1C1217]/95 backdrop-blur-md rounded-3xl p-6 sm:p-10 border border-amber-200/80 dark:border-surya-red/30 shadow-xl space-y-6">
        @if(isset($page) && !empty($page->content))
            <h1 class="text-3xl sm:text-4xl font-serif font-bold text-surya-red dark:text-surya-gold border-b border-amber-200/40 dark:border-white/10 pb-4">
                {{ $page->title }}
            </h1>
            <div class="dynamic-content">
                {!! $page->content !!}
            </div>
        @else
            <h1 class="text-3xl sm:text-4xl font-serif font-bold text-surya-red dark:text-surya-gold border-b border-amber-200/40 dark:border-white/10 pb-4">
                About Surya Path
            </h1>
            
            <p class="text-base sm:text-lg text-slate-700 dark:text-amber-100/90 leading-relaxed font-semibold italic">
                Guiding your life path through solar wisdom and verified celestial consultations.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start pt-4">
                <div class="space-y-4 text-sm sm:text-base text-slate-700 dark:text-amber-100/80 leading-relaxed">
                    <h3 class="text-xl font-serif font-bold text-surya-red dark:text-surya-gold">Our Sacred Journey</h3>
                    <p>
                        Established in 2026, <strong>Surya Path</strong> (Path of the Sun) was founded to deliver accurate, authentic, and logical astrological advisory to users globally.
                    </p>
                    <p>
                        We align ancient astronomical sciences with modern algorithmic matchmaking. By analyzing birth coordinates, solar houses, and planetary transits, our panel helps you navigate professional changes, financial transits, and matchmaking concerns.
                    </p>
                </div>
                
                <div class="p-6 rounded-2xl bg-amber-500/5 dark:bg-[#E1A61B]/5 border border-surya-gold/30">
                    <h5 class="font-bold font-serif text-lg text-surya-red dark:text-surya-gold mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-sun"></i> Why Surya Path?
                    </h5>
                    <ul class="space-y-2.5 text-sm text-slate-700 dark:text-amber-100/80">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-green-500"></i> 100% Secure Consultations</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-green-500"></i> Verified Expert Panelists</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-green-500"></i> Real-time Call & Chat 24/7</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-green-500"></i> Rigorous 5-Stage Vetting Process</li>
                    </ul>
                </div>
            </div>

            <div class="pt-6">
                <h3 class="text-xl font-serif font-bold text-surya-red dark:text-surya-gold mb-4">Our Core Values</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-white/5 border border-amber-200/40 dark:border-white/10">
                        <h6 class="font-bold text-surya-red dark:text-surya-gold mb-2 flex items-center gap-1.5"><i class="fa-solid fa-scroll text-xs"></i> Authenticity</h6>
                        <p class="text-xs text-slate-600 dark:text-amber-100/70 leading-relaxed">Every astrologer undergoes strict reviews and interviews before onboarding.</p>
                    </div>
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-white/5 border border-amber-200/40 dark:border-white/10">
                        <h6 class="font-bold text-surya-red dark:text-surya-gold mb-2 flex items-center gap-1.5"><i class="fa-solid fa-lock text-xs"></i> Privacy</h6>
                        <p class="text-xs text-slate-600 dark:text-amber-100/70 leading-relaxed">End-to-end encryption secures your personal chat history and contact information.</p>
                    </div>
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-white/5 border border-amber-200/40 dark:border-white/10">
                        <h6 class="font-bold text-surya-red dark:text-surya-gold mb-2 flex items-center gap-1.5"><i class="fa-solid fa-hand-holding-heart text-xs"></i> Empathy</h6>
                        <p class="text-xs text-slate-600 dark:text-amber-100/70 leading-relaxed">Our panel is trained to address client struggles with maximum sensitivity and respect.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@include('layouts.footer')
