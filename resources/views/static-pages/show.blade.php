@section('title', $page->title . ' - ' . config('app.name', 'Surya Path'))

@include('layouts.header')

<style>
    .dynamic-content p {
        margin-bottom: 1.25rem;
        line-height: 1.7;
    }
    .dynamic-content h2, .dynamic-content h3, .dynamic-content h4 {
        color: #E1A61B;
        font-family: 'Marcellus', serif !important;
        font-weight: bold;
        margin-top: 1.75rem;
        margin-bottom: 0.75rem;
    }
    .dynamic-content h2 { font-size: 1.5rem; }
    .dynamic-content h3 { font-size: 1.25rem; }
    .dynamic-content h4 { font-size: 1.15rem; }
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
        <h1 class="text-3xl sm:text-4xl font-serif font-bold text-surya-red dark:text-surya-gold border-b border-amber-200/40 dark:border-white/10 pb-4">
            {{ $page->title }}
        </h1>
        
        <div class="dynamic-content text-slate-700 dark:text-amber-100/90 leading-relaxed font-normal text-sm sm:text-base">
            {!! $page->content !!}
        </div>
    </div>
</div>

@include('layouts.footer')
