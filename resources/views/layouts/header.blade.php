<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <script>
        try {
            document.documentElement.classList.toggle('dark', localStorage.getItem('theme') === 'dark');
        } catch (error) {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'Surya Path') . ' - Trusted Guide for Astrology & Life Guidance')</title>
    <meta name="description"
        content="@yield('meta_description', 'Surya Path Kundli - Your Trusted Guide for Astrology & Life Guidance. Accurate Predictions, Panchang & Muhurat, Expert Astrologers, and Numerology Analysis.')">

    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">

    <!-- Google Fonts (Astrology Premium Vedic Typography: Cinzel Decorative, Marcellus, Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Marcellus&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap"
        rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Flatpickr Custom Beautiful Calendar Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_red.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Tailwind CSS (via CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Configure Custom Tailwind Theme & Font Families -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'surya-red': '#B10000',
                        'surya-red-dark': '#8B0000',
                        'surya-crimson': '#7A0000',
                        'surya-maroon': '#4A0000',
                        'surya-gold': '#E1A61B',
                        'surya-gold-light': '#FCEBB6',
                        'surya-gold-hover': '#C68E0F',
                        'cosmic-dark': '#120A0E',
                        'cosmic-card': '#1C1217',
                        'cosmic-border': '#2F1E26'
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        serif: ['"Marcellus"', '"Cinzel Decorative"', 'serif'],
                    },
                    animation: {
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'float': 'float 5s ease-in-out infinite',
                        'float-reverse': 'floatReverse 6s ease-in-out infinite',
                        'twinkle': 'twinkle 2.5s ease-in-out infinite',
                        'spin-slow': 'spin 25s linear infinite',
                        'spin-reverse': 'spinReverse 35s linear infinite',
                        'shooting-star': 'shootingStar 4s ease-in-out infinite',
                        'orbit-glow': 'orbitGlow 8s ease-in-out infinite',
                        'drift-slow': 'driftSlow 12s ease-in-out infinite',
                        'drift-reverse': 'driftReverse 15s ease-in-out infinite'
                    },
                    keyframes: {
                        marqueeLeft: {
                            '0%': { transform: 'translateX(0%)' },
                            '100%': { transform: 'translateX(-100%)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-14px)' },
                        },
                        floatReverse: {
                            '0%, 100%': { transform: 'translateY(0px) translateX(0px)' },
                            '50%': { transform: 'translateY(14px) translateX(-10px)' },
                        },
                        floatWide: {
                            '0%, 100%': { transform: 'translateY(0px) translateX(0px) scale(1)' },
                            '50%': { transform: 'translateY(-20px) translateX(15px) scale(1.05)' },
                        },
                        driftSlow: {
                            '0%, 100%': { transform: 'translate(0px, 0px)' },
                            '33%': { transform: 'translate(30px, -20px)' },
                            '66%': { transform: 'translate(-20px, 25px)' },
                        },
                        driftReverse: {
                            '0%, 100%': { transform: 'translate(0px, 0px)' },
                            '33%': { transform: 'translate(-35px, 20px)' },
                            '66%': { transform: 'translate(25px, -30px)' },
                        },
                        cosmicOrbit: {
                            '0%': { transform: 'rotate(0deg) translateX(40px) rotate(0deg)' },
                            '100%': { transform: 'rotate(360deg) translateX(40px) rotate(-360deg)' },
                        },
                        twinkle: {
                            '0%, 100%': { opacity: '0.3', transform: 'scale(0.9)' },
                            '50%': { opacity: '1', transform: 'scale(1.2)' },
                        },
                        spinReverse: {
                            '0%': { transform: 'rotate(360deg)' },
                            '100%': { transform: 'rotate(0deg)' },
                        },
                        shootingStar: {
                            '0%': { transform: 'translateX(0) translateY(0) scale(0)', opacity: '0' },
                            '10%': { opacity: '1', transform: 'scale(1)' },
                            '40%': { transform: 'translateX(-350px) translateY(350px) scale(0.5)', opacity: '0' },
                            '100%': { opacity: '0' },
                        },
                        orbitGlow: {
                            '0%, 100%': { filter: 'drop-shadow(0 0 15px rgba(225, 166, 27, 0.4))' },
                            '50%': { filter: 'drop-shadow(0 0 35px rgba(177, 0, 0, 0.7))' },
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Smooth Premium Vedic Font Rendering & Antialiasing */
        html, body, p, span, div, button {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        h1, h2, h3, .font-serif {
            font-family: 'Marcellus', 'Cinzel Decorative', serif !important;
            letter-spacing: 0.025em;
        }

        /* Responsive container styling */
        .max-w-7xl,
        .max-w-4xl,
        .max-w-3xl {
            max-width: 100% !important;
        }
        @media (min-width: 1024px) {
            .max-w-7xl, .max-w-4xl, .max-w-3xl {
                padding-left: 4rem !important;
                padding-right: 4rem !important;
            }
        }
        @media (min-width: 640px) and (max-width: 1023px) {
            .max-w-7xl, .max-w-4xl, .max-w-3xl {
                padding-left: 2rem !important;
                padding-right: 2rem !important;
            }
        }
        @media (max-width: 639px) {
            .max-w-7xl, .max-w-4xl, .max-w-3xl {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
        }

        /* Astrotalk Full-Page Pitch Dark & Warm Radial Glow Background */
        .starry-bg {
            background-color: #080406;
            background-image:
                radial-gradient(circle at 10% 10%, rgba(177, 0, 0, 0.22) 0%, transparent 40%),
                radial-gradient(circle at 90% 30%, rgba(225, 166, 27, 0.20) 0%, transparent 45%),
                radial-gradient(circle at 20% 60%, rgba(122, 0, 0, 0.18) 0%, transparent 45%),
                radial-gradient(circle at 80% 85%, rgba(225, 166, 27, 0.16) 0%, transparent 45%),
                radial-gradient(1.5px 1.5px at 30px 50px, rgba(255, 255, 255, 0.7), rgba(0, 0, 0, 0)),
                radial-gradient(2px 2px at 150px 220px, rgba(225, 166, 27, 0.6), rgba(0, 0, 0, 0)),
                radial-gradient(1.5px 1.5px at 280px 110px, rgba(255, 255, 255, 0.65), rgba(0, 0, 0, 0)),
                radial-gradient(2px 2px at 390px 290px, rgba(225, 166, 27, 0.5), rgba(0, 0, 0, 0));
            background-repeat: repeat;
            background-size: 100% 100%, 100% 100%, 100% 100%, 100% 100%, 300px 300px, 300px 300px, 300px 300px, 300px 300px;
            background-attachment: fixed;
        }

        html:not(.dark) .starry-bg,
        html:not(.dark) body {
            background-color: #FFFDF7;
            background-image:
                radial-gradient(circle at 15% 15%, rgba(225, 166, 27, 0.16) 0%, transparent 45%),
                radial-gradient(circle at 85% 45%, rgba(177, 0, 0, 0.10) 0%, transparent 45%),
                radial-gradient(circle at 50% 85%, rgba(225, 166, 27, 0.12) 0%, transparent 45%),
                radial-gradient(2px 2px at 50px 70px, rgba(177, 0, 0, 0.2), rgba(0, 0, 0, 0)),
                radial-gradient(2px 2px at 180px 200px, rgba(225, 166, 27, 0.35), rgba(0, 0, 0, 0));
            background-repeat: repeat;
            background-size: 100% 100%, 100% 100%, 100% 100%, 300px 300px, 300px 300px;
            background-attachment: fixed;
        }

        /* Mobile Responsive Font Size Adjustments */
        @media (max-width: 639px) {
            html, body {
                font-size: 15px !important;
            }
            [class*="text-[9px]"], [class*="text-[10px]"] {
                font-size: 12px !important;
            }
            .text-xs {
                font-size: 13.5px !important;
                line-height: 1.4 !important;
            }
            .text-sm {
                font-size: 15px !important;
                line-height: 1.5 !important;
            }
            .text-base {
                font-size: 16.5px !important;
            }
            p {
                line-height: 1.6 !important;
            }
        }

        .crimson-gradient-bg {
            background: linear-gradient(135deg, #B10000 0%, #7A0000 50%, #4A0000 100%);
        }

        .gold-border-glow {
            border: 2px solid #E1A61B;
            box-shadow: 0 0 20px rgba(225, 166, 27, 0.35);
        }

        /* Gold Red Shaded Headline Gradient */
        .surya-headline-gradient {
            background: linear-gradient(135deg, #B10000 0%, #E1A61B 50%, #8B0000 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        html.dark .surya-headline-gradient {
            background: linear-gradient(135deg, #E1A61B 0%, #FFFDF7 50%, #E1A61B 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Marquee horizontal animation */
        @keyframes marquee-left {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .animate-marquee-left {
            display: inline-flex;
            animation: marquee-left 25s linear infinite;
            width: max-content;
        }
    </style>
</head>

<body
    class="starry-bg text-slate-800 dark:text-slate-100 transition-colors duration-300 antialiased selection:bg-surya-red selection:text-white"
    x-data="{ 
        mobileMenuOpen: false, 
        darkMode: document.documentElement.classList.contains('dark'),
        isScrolled: false,
        activeBlog: null,
        showBlogModal: false,
        toggleTheme() {
            this.darkMode = !this.darkMode;
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            try {
                localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            } catch (error) {}
        }
    }" @scroll.window="isScrolled = (window.pageYOffset > 20)">

    <!-- SECTION 1: NAVIGATION HEADER (SMOOTH SCROLL BACKGROUND OVERLAY) -->
    <header class="sticky top-0 z-50 transition-all duration-300"
        :class="isScrolled ? 'bg-[#FFFDF7]/95 dark:bg-[#120A0E]/95 backdrop-blur-md border-b border-amber-200/40 dark:border-surya-gold/20 shadow-lg' : 'bg-transparent border-b border-transparent'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo & Name -->
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('images/logo.jpg') }}" alt="Surya Path Logo"
                    class="w-12 h-12 object-contain rounded-xl border border-surya-gold/50 shadow-md group-hover:scale-105 transition-transform duration-300">
                <div class="flex flex-col">
                    <span class="font-serif text-2xl font-bold tracking-tight text-surya-red dark:text-surya-gold">Surya
                        Path</span>
                    <span class="text-[10px] tracking-widest uppercase font-semibold text-surya-gold -mt-1">Vedic
                        Astrology</span>
                </div>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-6 font-medium text-sm">
                <a href="{{ url('/') }}#home"
                    class="text-slate-700 dark:text-slate-200 hover:text-surya-red dark:hover:text-surya-gold transition-colors font-semibold">Home</a>
                <a href="{{ url('/') }}#panchang"
                    class="flex items-center gap-1 text-slate-700 dark:text-slate-200 hover:text-surya-red dark:hover:text-surya-gold transition-colors">
                    Panchang
                </a>
                <a href="{{ url('/') }}#numerology"
                    class="flex items-center gap-1 text-slate-700 dark:text-slate-200 hover:text-surya-red dark:hover:text-surya-gold transition-colors">
                    Numerology
                </a>
                <a href="{{ url('/') }}#blogs"
                    class="text-slate-700 dark:text-slate-200 hover:text-surya-red dark:hover:text-surya-gold transition-colors">Blogs</a>
                <a href="{{ route('about') }}"
                    class="text-slate-700 dark:text-slate-200 hover:text-surya-red dark:hover:text-surya-gold transition-colors font-semibold">About Us</a>
                <a href="{{ route('support') }}"
                    class="text-slate-700 dark:text-slate-200 hover:text-surya-red dark:hover:text-surya-gold transition-colors">Contact
                    Us</a>
            </nav>

            <!-- CTA Right Action Buttons -->
            <div class="hidden sm:flex items-center gap-4">
                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                    <button @click="toggleTheme()"
                        class="w-9 h-9 rounded-full border border-amber-300/60 dark:border-surya-gold/40 flex items-center justify-center text-amber-600 dark:text-surya-gold hover:bg-amber-100/50 dark:hover:bg-surya-gold/10 transition-all"
                        title="Toggle Light/Dark Theme">
                        <i class="fa-solid" :class="darkMode ? 'fa-sun text-surya-gold' : 'fa-moon text-slate-700'"></i>
                    </button>
                    <?php /*<button
                        class="w-9 h-9 rounded-full border border-amber-300/60 dark:border-surya-gold/40 flex items-center justify-center hover:text-surya-gold transition-colors text-xs font-bold">अA</button>
                    <button
                        class="w-9 h-9 rounded-full border border-amber-300/60 dark:border-surya-gold/40 flex items-center justify-center hover:text-surya-gold transition-colors"><i
                            class="fa-solid fa-user text-xs"></i></button> */?>
                </div>
                <a href="{{ url('/') }}#astrologers"
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-full bg-surya-gold hover:bg-surya-gold-hover text-slate-950 font-bold text-xs shadow-lg shadow-surya-gold/30 hover:scale-105 transition-all">
                    Talk Now | First Chat Free &rarr;
                </a>
            </div>

            <!-- Mobile Action Bar -->
            <div class="flex items-center gap-2 sm:hidden">
                <button @click="toggleTheme()"
                    class="w-9 h-9 rounded-full border border-amber-300/60 dark:border-surya-gold/40 flex items-center justify-center text-amber-600 dark:text-surya-gold bg-amber-50/50 dark:bg-white/5"
                    title="Toggle Light/Dark Theme">
                    <i class="fa-solid" :class="darkMode ? 'fa-sun text-surya-gold' : 'fa-moon text-slate-700'"></i>
                </button>
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="p-2 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none">
                    <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark text-xl' : 'fa-bars text-xl'"></i>
                </button>
            </div>
            
            <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="hidden md:hidden sm:block p-2 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none">
                <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark text-xl' : 'fa-bars text-xl'"></i>
            </button>
        </div>

        <!-- Mobile Menu Drawer -->
        <div x-show="mobileMenuOpen" x-cloak
            class="md:hidden bg-white dark:bg-cosmic-card border-b border-amber-200 dark:border-cosmic-border px-4 pt-2 pb-6 space-y-3">
            
            <div class="flex items-center justify-between px-3 py-2 border-b border-amber-100 dark:border-white/10 mb-1">
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Appearance Theme</span>
                <button @click="toggleTheme()"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-amber-300/60 dark:border-surya-gold/40 text-xs font-bold text-slate-800 dark:text-surya-gold bg-amber-50 dark:bg-white/5">
                    <i class="fa-solid" :class="darkMode ? 'fa-sun text-surya-gold' : 'fa-moon text-slate-700'"></i>
                    <span x-text="darkMode ? 'Dark Mode' : 'Light Mode'"></span>
                </button>
            </div>

            <a href="{{ url('/') }}#home" @click="mobileMenuOpen = false"
                class="block px-3 py-2 rounded-md font-medium">Home</a>
            <a href="{{ url('/') }}#panchang" @click="mobileMenuOpen = false"
                class="block px-3 py-2 rounded-md font-medium">Panchang</a>
            <a href="{{ url('/') }}#numerology" @click="mobileMenuOpen = false"
                class="block px-3 py-2 rounded-md font-medium">Numerology</a>
            <a href="{{ url('/') }}#blogs" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md font-medium">Blogs</a>
            <a href="{{ route('about') }}" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md font-medium">About Us</a>
            <a href="{{ route('support') }}" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md font-medium">Contact Us</a>
            <a href="{{ url('/') }}#astrologers" @click="mobileMenuOpen = false"
                class="w-full text-center block px-6 py-3 rounded-full bg-surya-red text-white font-medium">Talk Now</a>
        </div>
    </header>
