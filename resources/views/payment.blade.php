<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Policy - {{ config('app.name', 'Surya Path') }}</title>

    <!-- Google Fonts: Playfair Display (Serif) & Poppins (Sans-serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    
    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary-red: #ad003a; 
            --primary-hover: #8c002e;
            --accent-gold: #f4b400;
            --text-dark: #222222;
            --text-gray: #666666;
            --bg-light: #ffffff;
            --bg-offwhite: #fafafa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            background-color: var(--bg-offwhite);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            color: var(--primary-red);
            font-weight: 700;
        }

        /* --- Navbar --- */
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 12px 0;
            transition: all 0.3s;
        }
        .navbar-brand img { height: 45px; border-radius: 50%; }
        .brand-text { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; color: var(--primary-red); line-height: 1; margin-left: 8px;}
        .brand-sub { font-family: 'Poppins', sans-serif; font-size: 0.8rem; color: var(--text-dark); display: block; font-weight: 500;}
        
        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            font-size: 0.95rem;
            margin: 0 12px;
            transition: color 0.3s;
        }
        .nav-link:hover { color: var(--primary-red) !important; }
        
        .btn-download-nav {
            background-color: var(--primary-red);
            color: white !important;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: 0.3s;
            text-decoration: none;
        }
        .btn-download-nav:hover { background-color: var(--primary-hover); transform: translateY(-2px); }

        /* --- Footer Section --- */
        .footer-section {
            background-color: #1a0008 !important; 
            border-top: 3px solid var(--primary-red);
            color: #ffffff;
        }
        .footer-logo-img { width: 45px; height: 45px; border-radius: 50%; border: 2px solid white; margin-right: 15px; }
        .footer-btn-premium {
            background-color: var(--primary-red);
            color: white !important;
            border-radius: 8px;
            padding: 8px 15px;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .footer-btn-premium:hover {
            background-color: var(--primary-hover);
            color: white !important;
            transform: translateY(-2px);
        }
        .footer-btn-premium i {
            font-size: 1.3rem;
        }
        .footer-btn-premium small {
            display: block;
            font-size: 0.65rem;
            line-height: 1.1;
        }
        .footer-btn-premium strong {
            font-size: 0.85rem;
        }
        .hover-gold {
            transition: color 0.2s;
        }
        .hover-gold:hover {
            color: var(--accent-gold) !important;
        }

        /* --- Inner Content Layout --- */
        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #eaeaea;
            padding: 50px;
            margin-top: 130px;
            margin-bottom: 80px;
        }
        .dynamic-content {
            color: var(--text-dark);
            line-height: 1.8;
            font-size: 1.05rem;
        }
        .dynamic-content p {
            margin-bottom: 1.5rem;
        }

        /* --- Inner Content Responsiveness --- */
        @media (max-width: 768px) {
            .content-card {
                padding: 25px 15px !important;
                margin-top: 100px !important;
                margin-bottom: 40px !important;
            }
            .content-card h1.display-5 {
                font-size: 2.2rem !important;
            }
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <img src="{{ asset('images/logo.jpg') }}" alt="Surya Path Logo">
                <div>
                    <div class="brand-text">Surya Path</div>
                    <span class="brand-sub">Kundli</span>
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars fs-4 text-dark"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ url('/#home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/#panchang-section') }}">Panchang</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/#numerology-section') }}">Numerology</a></li>
                    <!-- <li class="nav-item"><a class="nav-link" href="{{ url('/#astrologers-section') }}">Astrologers</a></li> -->
                    <li class="nav-item"><a class="nav-link" href="{{ url('/#blogs-section') }}">Blogs</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('about') }}">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('support') }}">Contact Us</a></li>
                </ul>
                <a href="{{ url('/#download') }}" class="btn btn-download-nav">Download App</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="content-card" data-aos="fade-up">
                    @if(isset($page) && $page->content)
                        <h1 class="display-5 fw-bold text-center mb-4">{{ $page->title }}</h1>
                        <div class="dynamic-content">
                            {!! $page->content !!}
                        </div>
                    @else
                        <h1 class="display-5 fw-bold text-center mb-4">Payment Policy</h1>
                        <p class="lead text-center mb-5 text-muted">Please read the billing rules, card security policies, and refund criteria for consultations.</p>

                        <div class="dynamic-content">
                            <h4 class="fw-bold mt-4 mb-3" style="color: var(--primary-red);">1. Payment Methods</h4>
                            <p class="text-muted">
                                We accept credit cards, debit cards, UPI, net banking, and popular mobile wallets. All transactions are securely routed through our PCI-DSS compliant payment gateway partners to ensure complete details safety.
                            </p>

                            <h4 class="fw-bold mt-4 mb-3" style="color: var(--primary-red);">2. Platform Wallet Recharge</h4>
                            <p class="text-muted">
                                To consult an astrologer via call or chat, you must recharge your platform wallet beforehand. Debits from your wallet are automatically calculated per-minute based on the consultant's rates.
                            </p>

                            <h4 class="fw-bold mt-4 mb-3" style="color: var(--primary-red);">3. Refund Conditions</h4>
                            <p class="text-muted">
                                Refunds are eligible only in case of complete technical failure, dropped calls caused by server faults, or verified astrologer misconduct. Wallet recharges are non-refundable to bank accounts but can be adjusted as credits inside your wallet balance.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Section -->
    <footer class="footer-section pt-5 pb-3">
        <div class="container">
            <div class="row g-4">
                <!-- Column 1: Brand & Logo -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ asset('images/logo.jpg') }}" alt="Surya Path Logo" class="footer-logo-img">
                        <div>
                            <h4 class="m-0 text-white font-family-serif">Surya Path</h4>
                            <span class="small" style="color: var(--accent-gold); font-family: 'Poppins', sans-serif;">Kundli & Astrology</span>
                        </div>
                    </div>
                    <p class="text-white-50 small" style="line-height: 1.6;">
                        Your trusted guide for accurate predictions, daily Panchang, numerology insights, and expert astrologer consultations.
                    </p>
                    <div class="d-flex gap-2 mt-3">
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <!-- Column 2: Quick Links -->
                <div class="col-lg-2 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <h5 class="text-white mb-3" style="font-family: 'Poppins', sans-serif; font-size: 1.1rem; font-weight: 600;">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ url('/#home') }}" class="text-white-50 text-decoration-none hover-gold small">Home</a></li>
                        <li class="mb-2"><a href="{{ url('/#panchang-section') }}" class="text-white-50 text-decoration-none hover-gold small">Panchang</a></li>
                        <li class="mb-2"><a href="{{ url('/#numerology-section') }}" class="text-white-50 text-decoration-none hover-gold small">Numerology</a></li>
                        <!-- <li class="mb-2"><a href="{{ url('/#astrologers-section') }}" class="text-white-50 text-decoration-none hover-gold small">Astrologers</a></li> -->
                        <li class="mb-2"><a href="{{ url('/#blogs-section') }}" class="text-white-50 text-decoration-none hover-gold small">Blogs</a></li>
                    </ul>
                </div>
                
                <!-- Column 3: Policies & Support -->
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <h5 class="text-white mb-3" style="font-family: 'Poppins', sans-serif; font-size: 1.1rem; font-weight: 600;">Company</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('about') }}" class="text-white-50 text-decoration-none hover-gold small">About Us</a></li>
                        <li class="mb-2"><a href="{{ route('privacy') }}" class="text-white-50 text-decoration-none hover-gold small">Privacy Policy</a></li>
                        <li class="mb-2"><a href="{{ route('terms') }}" class="text-white-50 text-decoration-none hover-gold small">Terms & Conditions</a></li>
                        <li class="mb-2"><a href="{{ route('payment_policy') }}" class="text-white-50 text-decoration-none hover-gold small">Payment Policy</a></li>
                        <li class="mb-2"><a href="{{ route('support') }}" class="text-white-50 text-decoration-none hover-gold small">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Column 4: App Download -->
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <h5 class="text-white mb-3" style="font-family: 'Poppins', sans-serif; font-size: 1.1rem; font-weight: 600;">Download App</h5>
                    <p class="text-white-50 small mb-3">Get the Surya Path App for real-time consultation.</p>
                    <div class="d-flex flex-column gap-2">
                        <a href="#" class="footer-btn-premium">
                            <i class="fab fa-android me-2"></i>
                            <div class="text-start">
                                <small>Download for</small>
                                <strong>Android</strong>
                            </div>
                        </a>
                        <a href="#" class="footer-btn-premium mt-1">
                            <i class="fab fa-apple me-2"></i>
                            <div class="text-start">
                                <small>Download on the</small>
                                <strong>App Store</strong>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
            <hr class="my-4 border-secondary opacity-25">
            
            <div class="row align-items-center text-center text-md-start small text-white-50">
                <div class="col-md-6 mb-2 mb-md-0">
                    &copy; 2026 {{ config('app.name', 'Surya Path') }}. All rights reserved.
                </div>
                <div class="col-md-6 text-md-end">
                    Made with <i class="fas fa-heart text-danger"></i> in India.
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true, offset: 50 });
    </script>
</body>
</html>
