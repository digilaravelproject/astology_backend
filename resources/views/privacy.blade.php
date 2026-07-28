@section('title', 'Privacy Policy - ' . config('app.name', 'Surya Path'))

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
        @if(isset($page) && !empty($page->content))
            <h1 class="text-3xl sm:text-4xl font-serif font-bold text-surya-red dark:text-surya-gold border-b border-amber-200/40 dark:border-white/10 pb-4">
                {{ $page->title }}
            </h1>
            <div class="dynamic-content">
                {!! $page->content !!}
            </div>
        @else
            <h1 class="text-3xl sm:text-4xl font-serif font-bold text-surya-red dark:text-surya-gold border-b border-amber-200/40 dark:border-white/10 pb-4">
                Privacy Policy
            </h1>
            
            <p class="text-base sm:text-lg text-slate-700 dark:text-amber-100/90 leading-relaxed font-semibold italic">
                Your privacy is sacred. Learn how we secure your data at Surya Path.
            </p>

            <div class="dynamic-content text-slate-700 dark:text-amber-100/80 text-sm sm:text-base">
                <h4 class="fw-bold mt-4 mb-3">1. Information Collection</h4>
                <p>
                    At Surya Path, we collect the necessary coordinates to calculate precise astrological charts. This includes your <strong>Date of Birth</strong>, <strong>Time of Birth</strong>, and <strong>Place of Birth</strong>. We do not access or share these details with any external third parties.
                </p>

                <h4 class="fw-bold mt-4 mb-3">2. Call & Chat Confidentiality</h4>
                <p>
                    All voice calls and chat log histories between you and the astrologer are end-to-end encrypted and strictly confidential. Astrologers do not have access to your personal contact numbers, email addresses, or payment details.
                </p>

                <h4 class="fw-bold mt-4 mb-3">3. Payment Security</h4>
                <p>
                    All wallet transactions, credit cards, or net banking entries are processed through fully PCI-DSS compliant secure payment gateway providers. We do not store your bank account passwords or card numbers on our servers.
                </p>

                <h4 class="fw-bold mt-4 mb-3">4. Cookies & Usage Data</h4>
                <p>
                    We collect minor usage logs and cookie session tags to maintain your login credentials and improve responsiveness of the landing page. You can configure your browser to reject cookies, though it may disrupt session logins.
                </p>

                <p class="mt-8 text-center font-bold text-surya-red dark:text-surya-gold">
                    If you have queries regarding data deletion, contact us at support@suryapath.com
                </p>
            </div>
        @endif
    </div>
</div>

@include('layouts.footer')
