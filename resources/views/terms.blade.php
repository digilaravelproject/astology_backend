@section('title', 'Terms & Conditions - ' . config('app.name', 'Surya Path'))

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
                Terms & Conditions
            </h1>
            
            <p class="text-base sm:text-lg text-slate-700 dark:text-amber-100/90 leading-relaxed font-semibold italic">
                Please read the terms of service governing consultation usage at Surya Path.
            </p>

            <div class="dynamic-content text-slate-700 dark:text-amber-100/80 text-sm sm:text-base">
                <h4 class="fw-bold mt-4 mb-3">1. Nature of Advisory</h4>
                <p>
                    Astrology predictions, birth chart evaluations, and remedies provided on Surya Path are based on ancient celestial sciences and solar models. These predictions are advisory in nature and should not be used as a substitute for professional legal, medical, or financial counsel.
                </p>

                <h4 class="fw-bold mt-4 mb-3">2. Billing & Wallet Debit</h4>
                <p>
                    Users must purchase virtual wallet currency using secure payment channels prior to initiating calls or chat consultations. Debits are calculated on a <strong>per-minute basis</strong> as set by each individual astrologer's listed rates.
                </p>

                <h4 class="fw-bold mt-4 mb-3">3. Refund Policy</h4>
                <p>
                    Refund requests for dropped calls or technical interruptions are evaluated by our billing support. Valid complaints must be filed within 48 hours of consultation completion, and approved adjustments are refunded back to the user's platform wallet balance.
                </p>

                <h4 class="fw-bold mt-4 mb-3">4. Astrologer Conduct</h4>
                <p>
                    Astrologers act as independent advisors. Surya Path does not guarantee the absolute accuracy of predictions or verify personal opinions expressed during private advisory sessions.
                </p>

                <p class="mt-8 text-center font-bold text-surya-red dark:text-surya-gold">
                    For inquiries regarding platform policies, contact us at legal@suryapath.com
                </p>
            </div>
        @endif
    </div>
</div>

@include('layouts.footer')
