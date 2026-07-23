<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Surya Path') }} - Kundli & Astrology</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">

    <!-- Google Fonts: Playfair Display (Serif) & Poppins (Sans-serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">

    <!-- AOS Animation -->
    <link rel="stylesheet" href="{{ asset('css/aos.css') }}">

    <!-- Swiper -->
    <link rel="stylesheet" href="{{ asset('css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body data-bs-spy="scroll" data-bs-target="#mainNavbar" data-bs-offset="100" tabindex="0">
    <div class="site-wrapper">

    <!-- Header / Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#home">
                <img src="{{ asset('images/logo.jpg') }}" alt="Surya Path Logo">
                <div>
                    <div class="brand-text">Surya Path</div>
                    <!-- <span class="brand-sub">Kundli</span> -->
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars fs-4 text-dark"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#panchang-section">Panchang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#numerology-section">Numerology</a></li>
                    <!-- <li class="nav-item"><a class="nav-link" href="#astrologers-section">Astrologers</a></li> -->
                    <li class="nav-item"><a class="nav-link" href="#blogs-section">Blogs</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('about') }}">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('support') }}">Contact Us</a></li>
                </ul>
                <a href="#download" class="btn btn-download-nav">Download App</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <!-- Half Mandala Art in Black (Watermark style) -->
        <div style="position: absolute; right: 0; top: 0; height: 100%; width: 45%; z-index: 1; pointer-events: none; overflow: hidden;" class="d-none d-lg-block">
            <svg viewBox="0 0 100 200" fill="none" stroke="#000000" stroke-width="0.45" stroke-opacity="0.38" style="height: 100%; width: 100%; transform: translateX(20%);">
                <!-- Concentric circular rings -->
                <circle cx="100" cy="100" r="95" stroke-dasharray="1 2"/>
                <circle cx="100" cy="100" r="88"/>
                <circle cx="100" cy="100" r="82" stroke-dasharray="0.5 1"/>
                <circle cx="100" cy="100" r="75"/>
                <circle cx="100" cy="100" r="68" stroke-dasharray="2 1"/>
                <circle cx="100" cy="100" r="60"/>
                <circle cx="100" cy="100" r="52"/>
                <circle cx="100" cy="100" r="45" stroke-dasharray="1 1"/>
                <circle cx="100" cy="100" r="38"/>
                <circle cx="100" cy="100" r="30"/>
                <circle cx="100" cy="100" r="22"/>
                <circle cx="100" cy="100" r="14"/>
                <circle cx="100" cy="100" r="6"/>
                <!-- Rays / spokes -->
                <line x1="100" y1="100" x2="5" y2="100"/>
                <line x1="100" y1="100" x2="100" y2="5"/>
                <line x1="100" y1="100" x2="100" y2="195"/>
                <line x1="100" y1="100" x2="32.8" y2="32.8"/>
                <line x1="100" y1="100" x2="32.8" y2="167.2"/>
                <line x1="100" y1="100" x2="63.3" y2="11.7"/>
                <line x1="100" y1="100" x2="63.3" y2="188.3"/>
                <line x1="100" y1="100" x2="9.8" y2="63.3"/>
                <line x1="100" y1="100" x2="9.8" y2="136.7"/>
                <!-- Mandala petals / arches -->
                <path d="M 100 5 A 95 95 0 0 0 100 195" />
                <path d="M 100 12 A 88 88 0 0 0 100 188" stroke-dasharray="1 1"/>
                <path d="M 100 25 A 75 75 0 0 0 100 175" />
                <path d="M 100 40 A 60 60 0 0 0 100 160" stroke-dasharray="2 2"/>
                <path d="M 100 55 A 45 45 0 0 0 100 145" />
                <path d="M 100 70 A 30 30 0 0 0 100 130" stroke-dasharray="1 1"/>
                <!-- Small dot details -->
                <circle cx="20" cy="100" r="1" fill="#000000" fill-opacity="0.38"/>
                <circle cx="25" cy="70" r="1" fill="#000000" fill-opacity="0.38"/>
                <circle cx="25" cy="130" r="1" fill="#000000" fill-opacity="0.38"/>
                <circle cx="40" cy="40" r="1" fill="#000000" fill-opacity="0.38"/>
                <circle cx="40" cy="160" r="1" fill="#000000" fill-opacity="0.12"/>
                <circle cx="70" cy="25" r="1" fill="#000000" fill-opacity="0.12"/>
                <circle cx="70" cy="175" r="1" fill="#000000" fill-opacity="0.12"/>
            </svg>
        </div>
        <div class="container">
            <div class="row align-items-center" style="position: relative; z-index: 2;">
                <div class="col-lg-6 mb-5 mb-lg-0 animate-hero-text">
                    <div class="trust-badge">
                        <i class="fas fa-star me-2"></i> Trusted by Thousands of Happy Users
                    </div>
                    
                    <h1 class="hero-title">Surya Path<br>Kundli</h1>
                    <p class="hero-subtitle">Your Trusted Guide for Astrology<br>& Life Guidance</p>
                    
                    <ul class="hero-checklist">
                        <li><i class="fas fa-check"></i> Accurate Predictions</li>
                        <li><i class="fas fa-check"></i> Panchang & Muhurat</li>
                        <li><i class="fas fa-check"></i> Expert Astrologers</li>
                        <li><i class="fas fa-check"></i> Numerology Insights</li>
                    </ul>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="#" class="hero-btn-red hover-lift">
                            <i class="fab fa-android hero-btn-icon"></i>
                            <div class="hero-btn-text">
                                <small>Download for</small>
                                <strong>Android</strong>
                            </div>
                        </a>
                        <a href="#" class="hero-btn-white hover-lift">
                            <i class="fab fa-apple hero-btn-icon"></i>
                            <div class="hero-btn-text">
                                <small>Download on the</small>
                                <strong>App Store</strong>
                            </div>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6 text-center hero-mockup-container animate-hero-mockup">
                    <!-- Zodiac Golden BG Wheel -->
                    <!-- <img src="{{ asset('images/zodiac_wheel.png') }}" alt="Sun Mandala" class="hero-sun-bg d-none d-lg-block"> -->
                    <!-- Mobile Mockup Image -->
                    <img src="{{ asset('images/astrology_mockup.png') }}" alt="Surya Path App Mockups" class="img-fluid hero-mockup floating">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="features">
        <div class="container py-4">
            <div class="section-header-container" data-aos="fade-up">
                <div class="section-line left"></div>
                <h2 class="section-title">Our Powerful Features</h2>
                <div class="section-line right"></div>
            </div>

            <div class="features-grid mt-5">
                <!-- 1 -->
                <div class="feature-box hover-lift" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon-circle"><i class="fas fa-calendar-alt"></i></div>
                    <h5>Daily Panchang</h5>
                    <p>Tithi, Nakshatra, Yoga, Rahu Kaal & more</p>
                </div>
                <!-- 2 -->
                <div class="feature-box hover-lift" data-aos="fade-up" data-aos-delay="150">
                    <div class="feature-icon-circle"><i class="fas fa-dharmachakra"></i></div>
                    <h5>Kundli Analysis</h5>
                    <p>Detailed Kundli with Remedies</p>
                </div>
                <!-- 3 -->
                <div class="feature-box hover-lift" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon-circle"><i class="fas fa-sort-numeric-up-alt"></i></div>
                    <h5>Numerology</h5>
                    <p>Name & Number Predictions</p>
                </div>
                <!-- 4 -->
                <div class="feature-box hover-lift" data-aos="fade-up" data-aos-delay="250">
                    <div class="feature-icon-circle"><i class="fas fa-heart"></i></div>
                    <h5>Kundli Matching</h5>
                    <p>Match Compatibility for Better Relations</p>
                </div>
                <!-- 5 -->
                <div class="feature-box hover-lift" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon-circle"><i class="fas fa-headset"></i></div>
                    <h5>Talk to Astrologers</h5>
                    <p>Chat, Call & Get Advice from Experts</p>
                </div>
                <!-- 6 -->
                <div class="feature-box hover-lift" data-aos="fade-up" data-aos-delay="350">
                    <div class="feature-icon-circle"><i class="fas fa-sun"></i></div>
                    <h5>Daily Horoscope</h5>
                    <p>Rashi Wise Daily Predictions</p>
                </div>
                <!-- 7 -->
                <div class="feature-box hover-lift" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-icon-circle"><i class="fas fa-newspaper"></i></div>
                    <h5>Blog & Articles</h5>
                    <p>Read Astrology Blogs & Articles</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Panchang & Numerology Section -->
    <section id="panchang-section" class="py-5" style="background-color: var(--bg-offwhite);">
        <div class="container py-4">
            <div class="panchang-container shadow-lg" data-aos="zoom-in" data-aos-duration="1000">
                <div class="row align-items-center">
                    
                    <!-- Left: Panchang Data -->
                    <div class="col-lg-5 mb-4 mb-lg-0" data-aos="fade-right" data-aos-delay="200">
                        <h2 class="panchang-title">Today's Panchang</h2>
                        <span class="panchang-date"><i class="far fa-calendar-alt me-2"></i> 22 May 2025, Wednesday</span>
                        
                        <div class="panchang-list">
                            <div class="panchang-item">
                                <i class="fas fa-sun"></i>
                                <div class="panchang-item-text"><small>Sunrise</small><strong>05:45 AM</strong></div>
                            </div>
                            <div class="panchang-item">
                                <i class="fas fa-moon"></i>
                                <div class="panchang-item-text"><small>Sunset</small><strong>07:12 PM</strong></div>
                            </div>
                            <div class="panchang-item">
                                <i class="fas fa-book-open"></i>
                                <div class="panchang-item-text"><small>Tithi</small><strong>Shukla Paksha Panchami</strong></div>
                            </div>
                            <div class="panchang-item">
                                <i class="fas fa-star"></i>
                                <div class="panchang-item-text"><small>Nakshatra</small><strong>Hasta</strong></div>
                            </div>
                            <div class="panchang-item">
                                <i class="fas fa-pray"></i>
                                <div class="panchang-item-text"><small>Yoga</small><strong>Siddhi</strong></div>
                            </div>
                            <div class="panchang-item">
                                <i class="fas fa-clock"></i>
                                <div class="panchang-item-text"><small>Rahu Kaal</small><strong>12:30 PM - 02:00 PM</strong></div>
                            </div>
                            <div class="panchang-item">
                                <i class="fas fa-hands"></i>
                                <div class="panchang-item-text"><small>Abhijit Muhurat</small><strong>11:48 AM - 12:40 PM</strong></div>
                            </div>
                            <div class="panchang-item">
                                <i class="fas fa-compass"></i>
                                <div class="panchang-item-text"><small>Choghadiya</small><strong>Shubh</strong></div>
                            </div>
                        </div>
                        <a href="#" class="btn-white-pill">View Full Panchang</a>
                    </div>

                    <!-- Center: Kalash Image -->
                    <div class="col-lg-3 text-center mb-4 mb-lg-0" data-aos="fade-up" data-aos-delay="400">
                        <img src="{{ asset('images/traditional_kalash.png') }}" alt="Kalash" class="kalash-img floating img-fluid" style="max-height: 250px;">
                    </div>

                    <!-- Right: Numerology Box (Cuts like screenshot 4) -->
                    <div class="col-lg-4">
                        <div class="numerology-card-cut">
                            <div class="row align-items-center">
                                <div class="col-7">
                                    <h4 style="font-family: 'Poppins', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 5px;">Numerology Calculator</h4>
                                    <p class="text-gray" style="font-size: 0.75rem; margin-bottom: 15px; line-height: 1.3;">Calculate your numbers and discover your future.</p>
                                    <form id="numerologyFormQuick" onsubmit="calculateNumerologyQuick(event);">
                                        <input type="text" id="numNameQuick" placeholder="Enter Your Full Name" required style="border: 1px solid #e0e0e0; border-radius: 5px; padding: 8px 12px; width: 100%; font-size: 0.8rem; margin-bottom: 12px; outline: none; background: white; color: var(--text-dark);">
                                        <button type="submit" class="btn-num-calc" style="background: var(--primary-red); color: white; border: none; border-radius: 50px; padding: 8px 20px; font-weight: 600; font-size: 0.8rem; transition: 0.3s;">Calculate Now</button>
                                    </form>
                                </div>
                                <div class="col-5 text-center">
                                    <img src="{{ asset('images/numerology_wheel.png') }}?v=2" alt="Numerology Wheel" class="num-wheel img-fluid" style="max-width: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Numerology Details Section -->
    <section id="numerology-section" class="py-5 bg-white">
        <div class="container py-4">
            <div class="section-header-container" data-aos="fade-up">
                <div class="section-line left"></div>
                <h2 class="section-title">Explore Numerology Vibrations</h2>
                <div class="section-line right"></div>
            </div>
            
            <p class="text-center text-gray mx-auto mb-5" style="max-width: 600px;">
                Numerology is the ancient study of numbers and their cosmic vibrations. Click on any number below to reveal its planetary ruler, key personality traits, and lucky associations.
            </p>

            <div class="row g-4 align-items-stretch">
                <!-- Left: Number Details Card -->
                <div class="col-lg-5 text-dark" data-aos="fade-right" data-aos-delay="200">
                    <div id="numberDetailsCard" class="p-4 rounded border h-100 bg-white" style="box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger text-white fw-bold me-3" style="width: 50px; height: 50px; font-size: 1.5rem;" id="selectedNumCircle">1</div>
                            <div>
                                <h5 class="fw-bold m-0 text-dark" id="selectedNumTitle">Number 1 Profile</h5>
                                <small class="text-danger fw-semibold" id="selectedNumRuler">Ruler: Sun (Surya)</small>
                            </div>
                        </div>
                        <p class="small text-dark mb-3" style="line-height: 1.6;" id="selectedNumDesc">Select a number to view details.</p>
                        <table class="table table-sm table-borderless small mb-0 text-dark">
                            <tr>
                                <td class="fw-bold py-1" style="width: 120px;">Lucky Colors:</td>
                                <td id="selectedNumColors" class="py-1">Yellow, Gold, Orange</td>
                            </tr>
                            <tr>
                                <td class="fw-bold py-1">Best Qualities:</td>
                                <td id="selectedNumQualities" class="py-1">Leadership, ambitious, independent</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Right: Interactive Numbers Grid -->
                <div class="col-lg-7" data-aos="fade-left" data-aos-delay="400">
                    <div class="p-4 rounded border h-100 bg-white" style="box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                        <h4 class="mb-3 text-dark" style="font-family: 'Poppins', sans-serif; font-size: 1.25rem;"><i class="fas fa-dharmachakra me-2 text-danger"></i>Number Vibrations (1–9)</h4>
                        <p class="small text-gray mb-4">Select a number below to read about its traits. You can also calculate your name number using the calculator in Today's Panchang section.</p>
                        
                        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                            @for ($i = 1; $i <= 9; $i++)
                                <button class="num-selector-btn" onclick="selectNumberDetails({{ $i }})" id="btn-num-{{ $i }}">
                                    {{ $i }}
                                </button>
                            @endfor
                        </div>
                        
                        <div class="p-3 rounded" style="background-color: #fffafb; border-left: 4px solid var(--primary-red);">
                            <h6 class="fw-bold mb-1 text-dark">How to find your Life Path Number?</h6>
                            <p class="small text-muted mb-0">Add all the digits of your birth date together (DD/MM/YYYY) and reduce the sum to a single digit. E.g., 28-05-1996 = 2+8+0+5+1+9+9+6 = 40 = 4.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-5" id="astrologers-section">
        <div class="container py-4">
            <div class="panchang-container shadow-lg" data-aos="zoom-in" data-aos-duration="1000" style="background-color: var(--primary-red); color: white;">
                <div class="section-header-container mb-4" data-aos="fade-up" style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                    <div class="section-line left" style="background-color: rgba(255, 255, 255, 0.4);"></div>
                    <h2 class="section-title text-white" style="font-family: 'Playfair Display', serif;">What Our Customers Say</h2>
                    <div class="section-line right" style="background-color: rgba(255, 255, 255, 0.4);"></div>
                </div>

                <!-- Swiper Slider for Testimonials -->
                <div class="swiper testimonial-swiper" data-aos="fade-up">
                    <div class="swiper-wrapper">
                        @if($feedbacks && $feedbacks->count() > 0)
                            @foreach($feedbacks as $feedback)
                                <div class="swiper-slide h-auto">
                                    <div class="testimonial-card hover-lift h-100">
                                        <div class="quote-icon-bg">“</div>
                                        <div class="stars">
                                            @for ($i = 0; $i < ($feedback->rating ?? 5); $i++)
                                                <i class="fas fa-star text-warning"></i>
                                            @endfor
                                            @for ($i = ($feedback->rating ?? 5); $i < 5; $i++)
                                                <i class="far fa-star text-warning"></i>
                                            @endfor
                                        </div>
                                        <p class="testimonial-text">"{{ $feedback->comment }}"</p>
                                        <div class="testimonial-author">
                                            <img src="{{ $feedback->user->profile_photo_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($feedback->user->name ?? 'Guest') }}" alt="{{ $feedback->user->name ?? 'Guest' }}">
                                            <div>
                                                <h6>{{ $feedback->user->name ?? 'Guest' }}</h6>
                                                <small>{{ $feedback->user->city ?? 'Verified User' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Fallback Static Testimonials -->
                            @php
                                $fallbackTestimonials = [
                                    [
                                        'text' => 'Surya Path Kundli से सलाह लेकर मेरी कई समस्याओं का समाधान हुआ। ज्योतिषी बहुत अनुभवी और सहयोगी हैं।',
                                        'name' => 'Rohit Sharma',
                                        'city' => 'Delhi'
                                    ],
                                    [
                                        'text' => 'App का Panchang और Numerology feature बहुत ही उपयोगी है। रोज़ देखना मेरी आदत बन गई है।',
                                        'name' => 'Neha Verma',
                                        'city' => 'Mumbai'
                                    ],
                                    [
                                        'text' => 'कुंडली मिलान और ज्योतिषी से बात करना बहुत आसान है। सटीक मार्गदर्शन के लिए धन्यवाद Surya Path Kundli।',
                                        'name' => 'Amit Mishra',
                                        'city' => 'Bangalore'
                                    ],
                                    [
                                        'text' => 'Very accurate daily horoscope and vastu predictions. Truly highly recommended app.',
                                        'name' => 'Priya Nair',
                                        'city' => 'Kochi'
                                    ]
                                ];
                            @endphp
                            @foreach($fallbackTestimonials as $t)
                                <div class="swiper-slide h-auto">
                                    <div class="testimonial-card hover-lift h-100">
                                        <div class="quote-icon-bg">“</div>
                                        <div class="stars">
                                            <i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i>
                                        </div>
                                        <p class="testimonial-text">"{{ $t['text'] }}"</p>
                                        <div class="testimonial-author">
                                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode($t['name']) }}" alt="{{ $t['name'] }}">
                                            <div>
                                                <h6>{{ $t['name'] }}</h6>
                                                <small>{{ $t['city'] }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <!-- Pagination -->
                    <div class="swiper-pagination testimonial-swiper-pagination mt-4 position-relative text-center"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blogs Section -->
    <section id="blogs-section" class="py-5" style="background-color: var(--bg-offwhite);">
        <div class="container py-4">
            @php
                $fallbackBlogs = [
                    [
                        'title' => 'Shani Dosha Ke Upay Jo Badal De Aapki Zindagi',
                        'type' => 'Remedies',
                        'image' => 'https://images.unsplash.com/photo-1601049541289-9b1b7bbbfe19?w=500&q=80',
                        'date' => 'May 20, 2025',
                        'desc' => 'Explore the remedies for Saturn effects and bring prosperity to your life.'
                    ],
                    [
                        'title' => 'Rahu in Different Houses Effects and Remedies',
                        'type' => 'Astrology',
                        'image' => 'https://images.unsplash.com/photo-1502481851512-e9e2529beff9?w=500&q=80',
                        'date' => 'May 18, 2025',
                        'desc' => 'Understand how Rahu placement influences your zodiac houses and health.'
                    ],
                    [
                        'title' => 'How to Use Panchang in Daily Life',
                        'type' => 'Panchang',
                        'image' => 'https://images.unsplash.com/photo-1518241353330-0f7941c2d9b5?w=500&q=80',
                        'date' => 'May 15, 2025',
                        'desc' => 'Start using Tithi, Nakshatra, and Rahu Kaal coordinates daily.'
                    ],
                    [
                        'title' => 'Life Path Number 1 Personality & Career',
                        'type' => 'Numerology',
                        'image' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=500&q=80',
                        'date' => 'May 12, 2025',
                        'desc' => 'Find out the career possibilities and core traits of life path number 1.'
                    ],
                    [
                        'title' => 'Vastu Tips for Career Growth & Wealth',
                        'type' => 'Vastu',
                        'image' => 'https://images.unsplash.com/photo-1582407947304-fd86f028f716?w=500&q=80',
                        'date' => 'May 10, 2025',
                        'desc' => 'Simple Vastu directions and adjustments to attract financial success.'
                    ],
                    [
                        'title' => 'Understanding Kundli Matching Compatibility',
                        'type' => 'Matchmaking',
                        'image' => 'https://images.unsplash.com/photo-1515934751635-c81c6bc9a2d8?w=500&q=80',
                        'date' => 'May 08, 2025',
                        'desc' => 'Analyze the Ashtakoot points to ensure marital stability.'
                    ]
                ];

                $categories = ['all' => 'All'];
                if ($blogs && $blogs->count() > 0) {
                    foreach ($blogs as $blog) {
                        $type = strtolower($blog->type ?? 'article');
                        $categories[$type] = ucfirst($type);
                    }
                } else {
                    foreach ($fallbackBlogs as $blog) {
                        $type = strtolower($blog['type'] ?? 'astrology');
                        $categories[$type] = ucfirst($type);
                    }
                }
            @endphp

            <div class="blog-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4" data-aos="fade-up">
                <div>
                    <h2 class="m-0">Latest Blogs</h2>
                </div>
            </div>
            <!-- Swiper Slider for Blogs -->
            <div class="swiper blog-swiper mt-4" data-aos="fade-up">
                <div class="swiper-wrapper">
                    @if($blogs && $blogs->count() > 0)
                        @foreach($blogs as $blog)
                            <div class="swiper-slide h-auto blog-slide" data-category="{{ strtolower($blog->type ?? 'article') }}">
                                <div class="blog-card hover-lift h-100" style="cursor: pointer;" onclick="openBlogModal(this)"
                                     data-title="{{ e($blog->title) }}"
                                     data-content="{{ e($blog->content) }}"
                                     data-image="{{ $blog->blog_image_url ?? 'https://images.unsplash.com/photo-1601049541289-9b1b7bbbfe19?w=500&q=80' }}"
                                     data-type="{{ $blog->type ? ucfirst($blog->type) : 'Astrology' }}"
                                     data-author="{{ $blog->author ?? 'Surya Path Admin' }}"
                                     data-date="{{ $blog->created_at ? $blog->created_at->format('M d, Y') : 'May 20, 2025' }}">
                                    <div class="blog-img-wrapper">
                                        <img src="{{ asset('storage/' . $blog->blog_image) }}" alt="{{ $blog->title }}">
                                        <span class="blog-badge bg-danger">{{ $blog->type ? ucfirst($blog->type) : 'Astrology' }}</span>
                                    </div>
                                    <div class="blog-content">
                                        <h5 class="blog-title">{{ $blog->title }}</h5>
                                        <p class="blog-desc small text-gray">{{ Str::limit(strip_tags($blog->content), 80) }}</p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div class="blog-date">{{ $blog->created_at ? $blog->created_at->format('M d, Y') : 'May 20, 2025' }}</div>
                                            <span class="small text-danger fw-bold">Read More <i class="fas fa-arrow-right ms-1"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach($fallbackBlogs as $blog)
                            <div class="swiper-slide h-auto blog-slide" data-category="{{ strtolower($blog['type']) }}">
                                <div class="blog-card hover-lift h-100" style="cursor: pointer;" onclick="openBlogModal(this)"
                                     data-title="{{ e($blog['title']) }}"
                                     data-content="{{ e($blog['desc']) }}"
                                     data-image="{{ $blog['image'] }}"
                                     data-type="{{ $blog['type'] }}"
                                     data-author="Surya Path Admin"
                                     data-date="{{ $blog['date'] }}">
                                    <div class="blog-img-wrapper">
                                        <img src="{{ $blog['image'] }}" alt="{{ $blog['title'] }}">
                                        <span class="blog-badge bg-danger">{{ $blog['type'] }}</span>
                                    </div>
                                    <div class="blog-content">
                                        <h5 class="blog-title">{{ $blog['title'] }}</h5>
                                        <p class="blog-desc small text-gray">{{ $blog['desc'] }}</p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div class="blog-date">{{ $blog['date'] }}</div>
                                            <span class="small text-danger fw-bold">Read More <i class="fas fa-arrow-right ms-1"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                <!-- Pagination -->
                <div class="swiper-pagination blog-swiper-pagination mt-4 position-relative text-center"></div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer-section pt-5 pb-3" id="download">
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
                        <li class="mb-2"><a href="#home" class="text-white-50 text-decoration-none hover-gold small">Home</a></li>
                        <li class="mb-2"><a href="#panchang-section" class="text-white-50 text-decoration-none hover-gold small">Panchang</a></li>
                        <li class="mb-2"><a href="#numerology-section" class="text-white-50 text-decoration-none hover-gold small">Numerology</a></li>
                        <li class="mb-2"><a href="#blogs-section" class="text-white-50 text-decoration-none hover-gold small">Blogs</a></li>
                    </ul>
                </div>
                
                <!-- Column 3: Policies & Support -->
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <h5 class="text-white mb-3" style="font-family: 'Poppins', sans-serif; font-size: 1.1rem; font-weight: 600;">Policies & Support</h5>
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
    </div> <!-- end of .site-wrapper -->


    <!-- Blog Details Modal -->
    <div class="modal fade" id="blogDetailsModal" tabindex="-1" aria-labelledby="blogDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background-color: var(--bg-light);">
                <div class="modal-header border-0 pb-0 position-relative" style="z-index: 10;">
                    <button type="button" class="btn-close rounded-circle p-2 bg-white shadow-sm" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px; top: 20px; z-index: 10; opacity: 0.9; width: 32px; height: 32px; border: none;"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="ratio ratio-21x9 bg-dark overflow-hidden position-relative" style="height: 350px;">
                        <img id="modalBlogImage" src="" alt="" class="w-full h-full object-cover" style="object-fit: cover; width: 100%; height: 100%;">
                        <div class="position-absolute bottom-0 start-0 w-100 p-4" style="background: linear-gradient(transparent, rgba(0,0,0,0.85)); z-index: 2;">
                            <span id="modalBlogBadge" class="badge bg-danger mb-2 px-3 py-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Article</span>
                            <h2 id="modalBlogTitle" class="text-white m-0 font-family-serif" style="font-size: 1.8rem; text-shadow: 1px 1px 3px rgba(0,0,0,0.6); font-family: 'Playfair Display', serif; font-weight: 700;">Blog Title</h2>
                        </div>
                    </div>
                    <div class="p-4 p-md-5">
                        <div class="d-flex flex-wrap align-items-center gap-3 text-muted small mb-4 pb-3 border-bottom">
                            <span id="modalBlogAuthor"><i class="fas fa-user-feather me-2 text-danger"></i>By Admin</span>
                            <span id="modalBlogDate"><i class="far fa-calendar-alt me-2 text-danger"></i>May 20, 2025</span>
                        </div>
                        <div id="modalBlogContent" class="text-dark" style="font-size: 0.95rem; line-height: 1.8; white-space: pre-line; font-family: 'Poppins', sans-serif;">
                            Blog content goes here...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/aos.js') }}"></script>
    <script src="{{ asset('js/swiper-bundle.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/landing.js') }}"></script>
</body>
</html>