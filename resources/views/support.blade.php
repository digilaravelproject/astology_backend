@section('title', 'Help & Support - ' . config('app.name', 'Surya Path'))

@include('layouts.header')

<style>
    .dynamic-content p {
        margin-bottom: 1.25rem;
        line-height: 1.7;
    }
    .dynamic-content h2, .dynamic-content h3, .dynamic-content h4 {
        color: #D1A167;
        font-family: 'Poppins', Arial, sans-serif !important;
        font-weight: bold;
        margin-top: 1.75rem;
        margin-bottom: 0.75rem;
    }
    .dynamic-content h2 { font-size: 1.5rem; }
    .dynamic-content h3 { font-size: 1.25rem; }
    .dynamic-content h4 { font-size: 1.15rem; }
</style>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 relative z-10">
    <div class="bg-white dark:bg-[#1C1217]/95 backdrop-blur-md rounded-3xl p-6 sm:p-10 border border-amber-200/80 dark:border-surya-red/30 shadow-xl space-y-6 text-center">
        
        <h1 class="text-3xl sm:text-4xl font-serif font-bold text-surya-red dark:text-surya-gold border-b border-amber-200/40 dark:border-white/10 pb-4">
            Help & Support
        </h1>
        
        <p class="text-base sm:text-lg text-slate-700 dark:text-amber-100/90 leading-relaxed font-semibold italic">
            Have queries or facing an issue? Send us a message and we'll reply shortly.
        </p>

        <div class="max-w-md mx-auto p-6 rounded-2xl bg-amber-500/5 dark:bg-[#D1A167]/5 border border-surya-gold/30 text-left space-y-6 mt-4">
            <h4 class="font-bold font-serif text-lg text-surya-red dark:text-surya-gold mb-3 text-center">
                {{ isset($page) ? $page->title : 'Contact Details' }}
            </h4>

            @if(isset($page) && !empty($page->content))
                <div class="dynamic-content text-slate-700 dark:text-amber-100/80">
                    {!! $page->content !!}
                </div>
            @else
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center text-red-500 text-xl">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div>
                        <h6 class="font-bold text-slate-900 dark:text-white text-sm">Email Support</h6>
                        <p class="text-xs text-slate-500 dark:text-amber-100/70 mt-0.5">support@suryapath.com</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 text-xl">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div>
                        <h6 class="font-bold text-slate-900 dark:text-white text-sm">WhatsApp Support</h6>
                        <p class="text-xs text-slate-500 dark:text-amber-100/70 mt-0.5">+91 12345 67890</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-500 text-xl">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h6 class="font-bold text-slate-900 dark:text-white text-sm">Operation Hours</h6>
                        <p class="text-xs text-slate-500 dark:text-amber-100/70 mt-0.5">Mon - Sun (24/7 Hours Support)</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@include('layouts.footer')
