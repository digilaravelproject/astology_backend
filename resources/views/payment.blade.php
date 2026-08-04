@section('title', 'Payment Policy - ' . config('app.name', 'Surya Path'))

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
                Payment Policy & Refunds
            </h1>
            
            <p class="text-base sm:text-lg text-slate-700 dark:text-amber-100/90 leading-relaxed font-semibold italic">
                Please read the billing rules, card security policies, and refund criteria for consultations.
            </p>

            <div class="dynamic-content text-slate-700 dark:text-amber-100/80 text-sm sm:text-base">
                <h4 class="fw-bold mt-4 mb-3">1. Payment Methods</h4>
                <p>
                    We accept credit cards, debit cards, UPI, net banking, and popular mobile wallets. All transactions are securely routed through our PCI-DSS compliant payment gateway partners to ensure complete details safety.
                </p>

                <h4 class="fw-bold mt-4 mb-3">2. Platform Wallet Recharge</h4>
                <p>
                    To consult an astrologer via call or chat, you must recharge your platform wallet beforehand. Debits from your wallet are automatically calculated per-minute based on the consultant's rates.
                </p>

                <h4 class="fw-bold mt-4 mb-3">3. Refund Conditions</h4>
                <p>
                    Refunds are eligible only in case of complete technical failure, dropped calls caused by server faults, or verified astrologer misconduct. Wallet recharges are non-refundable to bank accounts but can be adjusted as credits inside your wallet balance.
                </p>

                <p class="mt-8 text-center font-bold text-surya-red dark:text-surya-gold">
                    For support queries regarding wallet balances, contact us at billing@suryapath.com
                </p>
            </div>
        @endif
    </div>
</div>

@include('layouts.footer')
