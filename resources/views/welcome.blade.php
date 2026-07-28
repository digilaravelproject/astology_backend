@include('layouts.header')


    <!-- SECTION 2: HERO SECTION (ASTROTALK INSPIRED WITH COSMIC RED-GOLD GLOW & FLOATING ANIMATIONS) -->
    <section id="home" class="relative overflow-hidden pt-4 pb-20 lg:pt-8 lg:pb-28">

        <!-- Animated Ambient Glowing Orbs & Realistic Moving Brahmand Stars -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
            <!-- Glowing Red & Gold Nebula Orbs (Drifting through Space) -->
            <div
                class="absolute -top-10 left-10 w-96 h-96 rounded-full bg-surya-red/25 dark:bg-surya-red/30 blur-3xl animate-drift-slow">
            </div>
            <div
                class="absolute top-1/2 right-10 w-80 h-80 rounded-full bg-surya-gold/20 dark:bg-surya-gold/25 blur-3xl animate-drift-reverse">
            </div>

            <!-- Moving Brahmand Stars & Planets (Continuous Orbital & Floating Motion) -->
            <div
                class="absolute top-12 left-1/4 w-3 h-3 bg-surya-gold rounded-full shadow-[0_0_12px_#E1A61B] animate-drift-slow opacity-90">
            </div>
            <div
                class="absolute top-1/3 left-12 w-2 h-2 bg-white rounded-full shadow-[0_0_8px_#fff] animate-drift-reverse opacity-90">
            </div>
            <div
                class="absolute top-1/4 right-1/3 w-3 h-3 bg-amber-200 rounded-full shadow-[0_0_14px_#FDE68A] animate-float-wide opacity-85">
            </div>
            <div
                class="absolute top-24 right-24 w-2 h-2 bg-white rounded-full shadow-[0_0_6px_#fff] animate-drift-slow opacity-90">
            </div>
            <div
                class="absolute bottom-20 left-1/3 w-2.5 h-2.5 bg-surya-gold rounded-full shadow-[0_0_10px_#E1A61B] animate-drift-reverse opacity-90">
            </div>
            <div
                class="absolute bottom-32 right-1/4 w-2 h-2 bg-white rounded-full shadow-[0_0_8px_#fff] animate-float-wide opacity-85">
            </div>
            <div
                class="absolute top-2/3 left-20 w-1.5 h-1.5 bg-amber-300 rounded-full shadow-[0_0_6px_#FCD34D] animate-drift-slow opacity-80">
            </div>

            <!-- Orbiting Planet/Cosmic Bodies in Motion -->
            <div
                class="absolute top-1/2 left-1/2 w-4 h-4 bg-gradient-to-r from-surya-gold to-amber-200 rounded-full shadow-[0_0_12px_#E1A61B] animate-cosmic-orbit opacity-85">
            </div>
            <div class="absolute top-1/3 right-1/3 w-3 h-3 bg-gradient-to-r from-surya-red to-surya-gold rounded-full shadow-[0_0_10px_#B10000] animate-cosmic-orbit opacity-80"
                style="animation-duration: 28s;"></div>

            <!-- Dynamic Shooting Stars (Cosmic Meteor Streams Crossing Space) -->
            <div class="absolute top-10 right-10 w-36 h-0.5 bg-gradient-to-r from-surya-gold via-white to-transparent animate-shooting-star opacity-90"
                style="animation-delay: 1s;"></div>
            <div class="absolute top-1/3 right-1/4 w-32 h-0.5 bg-gradient-to-r from-white via-amber-200 to-transparent animate-shooting-star opacity-85"
                style="animation-delay: 3.5s;"></div>
            <div class="absolute top-1/2 left-1/3 w-40 h-0.5 bg-gradient-to-r from-surya-gold via-amber-100 to-transparent animate-shooting-star opacity-80"
                style="animation-delay: 6s;"></div>

            <!-- Floating Constellation Sparkle Icons in Physical Motion -->
            <div class="absolute top-16 left-1/2 text-surya-gold/80 dark:text-surya-gold text-base animate-drift-slow">✦
            </div>
            <div
                class="absolute bottom-24 right-12 text-amber-200/70 dark:text-surya-gold/80 text-sm animate-drift-reverse">
                ✧</div>
            <div class="absolute top-1/2 left-8 text-surya-gold/70 dark:text-surya-gold/90 text-sm animate-float-wide">✦
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

            <!-- Live Ticker Bar (Astrotalk Style) -->
            <div
                class="inline-flex items-center gap-3 px-4 py-1.5 rounded-full bg-amber-100/90 dark:bg-surya-maroon/60 text-amber-900 dark:text-surya-gold text-xs font-semibold border border-surya-gold/40 shadow-sm mb-6 animate-float">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-ping"></span>
                <i class="fa-solid fa-headset text-surya-gold"></i>
                <span>1,240+ Vedic Astrologers Online Now</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">

                <!-- Left Content -->
                <div class="lg:col-span-6 space-y-6 text-left">

                    <h1
                        class="text-4xl sm:text-5xl lg:text-6xl font-serif font-extrabold surya-headline-gradient leading-tight tracking-tight">
                        Surya Path <br />Kundli Guidance
                    </h1>

                    <p class="text-lg font-semibold text-slate-700 dark:text-slate-200 max-w-lg">
                        Connect with India's Top Astrologers for Instant Live Guidance on Career, Marriage, Love &
                        Health.
                    </p>

                    <!-- Feature Bullet Grid -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2 text-sm font-medium text-slate-800 dark:text-slate-200">
                        <div
                            class="flex items-center gap-2.5 p-2 rounded-lg bg-amber-50/60 dark:bg-white/5 border border-amber-200/50 dark:border-white/10 hover:border-surya-gold/50 transition-colors">
                            <i class="fa-solid fa-circle-check text-surya-red dark:text-surya-gold text-base"></i>
                            <span>100% Accurate Predictions</span>
                        </div>
                        <div
                            class="flex items-center gap-2.5 p-2 rounded-lg bg-amber-50/60 dark:bg-white/5 border border-amber-200/50 dark:border-white/10 hover:border-surya-gold/50 transition-colors">
                            <i class="fa-solid fa-circle-check text-surya-red dark:text-surya-gold text-base"></i>
                            <span>Daily Panchang & Muhurat</span>
                        </div>
                        <div
                            class="flex items-center gap-2.5 p-2 rounded-lg bg-amber-50/60 dark:bg-white/5 border border-amber-200/50 dark:border-white/10 hover:border-surya-gold/50 transition-colors">
                            <i class="fa-solid fa-circle-check text-surya-red dark:text-surya-gold text-base"></i>
                            <span>500+ Verified Astrologers</span>
                        </div>
                        <div
                            class="flex items-center gap-2.5 p-2 rounded-lg bg-amber-50/60 dark:bg-white/5 border border-amber-200/50 dark:border-white/10 hover:border-surya-gold/50 transition-colors">
                            <i class="fa-solid fa-circle-check text-surya-red dark:text-surya-gold text-base"></i>
                            <span>Numerology & Gun Milan</span>
                        </div>
                    </div>

                    <!-- App Download Buttons -->
                    <div class="flex flex-wrap items-center gap-4 pt-4">
                        <a href="#download"
                            class="inline-flex items-center gap-3 px-6 py-3 rounded-xl bg-gradient-to-r from-surya-red to-surya-red-dark text-white font-semibold hover:from-surya-red-dark hover:to-surya-red transition-all shadow-lg shadow-surya-red/30 border border-surya-gold/40 hover:scale-105">
                            <i class="fa-brands fa-android text-2xl text-surya-gold"></i>
                            <div class="text-left leading-tight">
                                <p class="text-[9px] uppercase font-medium text-amber-200">Download for</p>
                                <p class="text-sm font-bold">Android</p>
                            </div>
                        </a>
                        <a href="#download"
                            class="inline-flex items-center gap-3 px-6 py-3 rounded-xl bg-white dark:bg-cosmic-card text-slate-900 dark:text-white border-2 border-surya-gold/50 hover:bg-amber-50 dark:hover:bg-cosmic-border transition-all shadow-lg hover:scale-105">
                            <i class="fa-brands fa-apple text-2xl text-surya-red dark:text-surya-gold"></i>
                            <div class="text-left leading-tight">
                                <p class="text-[9px] uppercase font-medium text-slate-500 dark:text-slate-400">Download
                                    on</p>
                                <p class="text-sm font-bold">App Store</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Right Hero Visual (Astrotalk Floating Cards & Giant Rotating Zodiac Wheel) -->
                <div class="lg:col-span-6 flex justify-center lg:justify-end relative items-center">

                    <!-- Right Side Giant Rotating Half/Full Golden Zodiac Wheel Accent -->
                    <div
                        class="absolute -right-20 lg:-right-32 top-1/2 -translate-y-1/2 w-[340px] sm:w-[460px] h-[340px] sm:h-[460px] pointer-events-none opacity-40 dark:opacity-60 z-0">
                        <svg class="w-full h-full animate-spin-slow text-surya-gold filter drop-shadow-[0_0_20px_rgba(225,166,27,0.5)]"
                            viewBox="0 0 200 200" fill="none" stroke="currentColor">
                            <!-- Dual Concentric Astrological Outer Rings -->
                            <circle cx="100" cy="100" r="96" stroke-width="2.5" />
                            <circle cx="100" cy="100" r="88" stroke-width="1" stroke-dasharray="4 3" />
                            <circle cx="100" cy="100" r="70" stroke-width="1.5" />
                            <circle cx="100" cy="100" r="50" stroke-width="1" stroke-dasharray="2 2" />

                            <!-- 12 Astrological Houses Radial Ray Lines -->
                            <line x1="100" y1="4" x2="100" y2="30" stroke-width="2" />
                            <line x1="100" y1="170" x2="100" y2="196" stroke-width="2" />
                            <line x1="4" y1="100" x2="30" y2="100" stroke-width="2" />
                            <line x1="170" y1="100" x2="196" y2="100" stroke-width="2" />

                            <line x1="32" y1="32" x2="51" y2="51" stroke-width="1.5" />
                            <line x1="168" y1="168" x2="149" y2="149" stroke-width="1.5" />
                            <line x1="168" y1="32" x2="149" y2="51" stroke-width="1.5" />
                            <line x1="32" y1="168" x2="51" y2="149" stroke-width="1.5" />

                            <line x1="13" y1="65" x2="35" y2="70" stroke-width="1" />
                            <line x1="187" y1="135" x2="165" y2="130" stroke-width="1" />
                            <line x1="65" y1="13" x2="70" y2="35" stroke-width="1" />
                            <line x1="135" y1="187" x2="130" y2="165" stroke-width="1" />

                            <!-- 12 Zodiac Constellation Dots & Stars -->
                            <circle cx="100" cy="17" r="4" fill="currentColor" stroke="none" />
                            <circle cx="183" cy="100" r="4" fill="currentColor" stroke="none" />
                            <circle cx="100" cy="183" r="4" fill="currentColor" stroke="none" />
                            <circle cx="17" cy="100" r="4" fill="currentColor" stroke="none" />

                            <circle cx="158" cy="42" r="3" fill="currentColor" stroke="none" />
                            <circle cx="158" cy="158" r="3" fill="currentColor" stroke="none" />
                            <circle cx="42" cy="158" r="3" fill="currentColor" stroke="none" />
                            <circle cx="42" cy="42" r="3" fill="currentColor" stroke="none" />
                        </svg>
                    </div>

                    <!-- Floating Astrologer Live Pill (Top Left) -->
                    <div
                        class="absolute -top-4 -left-4 sm:left-4 z-20 bg-white/95 dark:bg-cosmic-card/95 backdrop-blur-md p-3 rounded-2xl border-2 border-surya-gold shadow-xl flex items-center gap-3 animate-float">
                        <div class="relative">
                            <img src="{{ asset('images/logo.jpg') }}" class="w-10 h-10 rounded-full border border-surya-gold object-cover">
                            <span
                                class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></span>
                        </div>
                        <div class="text-left text-xs">
                            <p class="font-bold text-slate-900 dark:text-white">Pt. Sharma Acharya</p>
                            <p class="text-[10px] text-surya-red dark:text-surya-gold font-semibold">Vedic & Prashna
                                Specialist</p>
                        </div>
                    </div>

                    <!-- Main Banner Container with Deep Shaded Gradient -->
                    <div
                        class="relative z-10 w-full max-w-md bg-gradient-to-br from-surya-red via-surya-red-dark to-surya-maroon p-6 sm:p-8 rounded-3xl shadow-2xl text-white overflow-hidden border-2 border-surya-gold/70 group hover:shadow-surya-red/40 transition-shadow animate-orbit-glow">
                        <div
                            class="absolute -right-16 -bottom-16 w-72 h-72 bg-surya-gold/25 rounded-full blur-3xl animate-pulse-slow">
                        </div>

                        <div class="relative z-10 flex flex-col items-center text-center space-y-6">

                            <!-- Dual Counter-Rotating Zodiac Ring Accent -->
                            <div class="relative w-44 h-44 flex items-center justify-center">
                                <div
                                    class="absolute inset-0 border-2 border-dashed border-surya-gold/60 rounded-full animate-spin-slow">
                                </div>
                                <div
                                    class="absolute inset-2 border border-dotted border-amber-200/50 rounded-full animate-spin-reverse">
                                </div>
                                <div
                                    class="w-32 h-32 rounded-2xl overflow-hidden border-2 border-surya-gold shadow-2xl p-1 bg-surya-red animate-float">
                                    <img src="{{ asset('images/logo.jpg') }}" alt="Surya Path Icon"
                                        class="w-full h-full object-cover rounded-xl">
                                </div>
                            </div>

                            <div class="space-y-1">
                                <h3 class="font-serif font-bold text-2xl text-surya-gold tracking-wide">Surya Path App
                                </h3>
                                <p class="text-xs text-amber-100">Live Kundli, Panchang & Astrologer Guidance</p>
                            </div>

                            <div
                                class="w-full bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-surya-gold/40 text-slate-900 grid grid-cols-2 gap-3 shadow-inner">
                                <div
                                    class="bg-white p-3 rounded-xl shadow-sm text-left hover:scale-105 transition-transform">
                                    <p class="text-[10px] font-bold text-surya-red uppercase flex items-center gap-1">
                                        <i class="fa-solid fa-sun text-surya-gold"></i> Daily Rashi
                                    </p>
                                    <p class="text-xs font-semibold mt-0.5">Today's Rashifal</p>
                                </div>
                                <div
                                    class="bg-white p-3 rounded-xl shadow-sm text-left hover:scale-105 transition-transform">
                                    <p class="text-[10px] font-bold text-surya-red uppercase flex items-center gap-1">
                                        <i class="fa-solid fa-om text-surya-gold"></i> Kundli Chart
                                    </p>
                                    <p class="text-xs font-semibold mt-0.5">Gun Milan 36 Points</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Badge (Bottom Right) -->
                    <div
                        class="absolute -bottom-4 -right-2 z-20 bg-surya-gold text-slate-950 px-4 py-2 rounded-2xl shadow-xl font-bold text-xs flex items-center gap-2 border border-white animate-float-reverse">
                        <i class="fa-solid fa-crown text-surya-red text-sm"></i>
                        <span>First Consult @ ₹1</span>
                    </div>

                </div>
            </div>

            <!-- Astrotalk-Inspired Trust Stats Counter Grid (Clean Modern Typography) -->
            <div
                class="grid grid-cols-2 md:grid-cols-4 gap-8 pt-12 mt-4 border-t border-amber-200/20 dark:border-white/10 text-center sm:text-left">
                <div class="space-y-1">
                    <p
                        class="text-3xl lg:text-4xl font-extrabold font-serif text-slate-900 dark:text-white tracking-tight">
                        5Cr<span class="text-surya-gold">+</span></p>
                    <p class="text-xs font-medium text-slate-600 dark:text-amber-200/70 uppercase tracking-wider">Users
                        Guided</p>
                </div>
                <div class="space-y-1">
                    <p
                        class="text-3xl lg:text-4xl font-extrabold font-serif text-slate-900 dark:text-white tracking-tight">
                        50,000<span class="text-surya-gold">+</span></p>
                    <p class="text-xs font-medium text-slate-600 dark:text-amber-200/70 uppercase tracking-wider">
                        Verified Astrologers</p>
                </div>
                <div class="space-y-1">
                    <p
                        class="text-3xl lg:text-4xl font-extrabold font-serif text-slate-900 dark:text-white tracking-tight">
                        13<span class="text-surya-gold">+</span></p>
                    <p class="text-xs font-medium text-slate-600 dark:text-amber-200/70 uppercase tracking-wider">
                        Languages</p>
                </div>
                <div class="space-y-1">
                    <p
                        class="text-3xl lg:text-4xl font-extrabold font-serif text-slate-900 dark:text-white tracking-tight">
                        60<span class="text-surya-gold">+</span></p>
                    <p class="text-xs font-medium text-slate-600 dark:text-amber-200/70 uppercase tracking-wider">
                        Countries</p>
                </div>
            </div>

            <!-- Live Consultation Stream Ticker (Continuous Smooth Right-to-Left Scroll Marquee) -->
            <div
                class="mt-8 py-3 px-5 rounded-2xl bg-amber-500/10 dark:bg-[#25101A]/80 backdrop-blur-md border border-amber-200/40 dark:border-surya-red/30 text-xs text-slate-700 dark:text-slate-200 flex items-center gap-3 overflow-hidden shadow-inner">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-ping shrink-0 z-10"></span>
                <div class="overflow-hidden relative w-full">
                    <div class="inline-flex items-center gap-12 font-medium whitespace-nowrap animate-marquee-left">
                        <span><strong class="text-slate-900 dark:text-white font-bold">Mumbai:</strong> Rahul just
                            started a chat with <span class="text-surya-red dark:text-surya-gold font-bold">Acharya
                                Prem</span> · 2m ago</span>
                        <span class="text-surya-gold">✦</span>
                        <span><strong class="text-slate-900 dark:text-white font-bold">Hyderabad:</strong> Neha booked
                            Saturn puja with <span class="text-surya-red dark:text-surya-gold font-bold">Pt. Ram
                                Naresh</span> · just now</span>
                        <span class="text-surya-gold">✦</span>
                        <span><strong class="text-slate-900 dark:text-white font-bold">Delhi:</strong> Ananya got Kundli
                            analysis from <span class="text-surya-red dark:text-surya-gold font-bold">Saanvi
                                Sharma</span> · 1m ago</span>
                        <span class="text-surya-gold">✦</span>
                        <span><strong class="text-slate-900 dark:text-white font-bold">Bangalore:</strong> Vikram
                            consulted on Career with <span class="text-surya-red dark:text-surya-gold font-bold">Dr. K.
                                Shastri</span> · 3m ago</span>
                        <span class="text-surya-gold">✦</span>
                        <span><strong class="text-slate-900 dark:text-white font-bold">Mumbai:</strong> Rahul just
                            started a chat with <span class="text-surya-red dark:text-surya-gold font-bold">Acharya
                                Prem</span> · 2m ago</span>
                        <span class="text-surya-gold">✦</span>
                        <span><strong class="text-slate-900 dark:text-white font-bold">Hyderabad:</strong> Neha booked
                            Saturn puja with <span class="text-surya-red dark:text-surya-gold font-bold">Pt. Ram
                                Naresh</span> · just now</span>
                    </div>
                </div>
            </div>

            <!-- 4 Astrotalk Quick Primary Action Cards Grid (Rich Red-Shaded Theme Tone) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 pt-8">
                <!-- Quick Card 1: Chat with Astrologer -->
                <a href="#astrologers"
                    class="group bg-gradient-to-b from-amber-50 to-amber-100/50 dark:from-[#25101A] dark:to-[#180A12] p-5 rounded-2xl border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 shadow-md hover:shadow-surya-red/20 hover:-translate-y-1">
                    <div
                        class="w-11 h-11 rounded-xl bg-surya-gold text-slate-950 flex items-center justify-center text-lg mb-3.5 group-hover:scale-110 transition-transform shadow">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <h3
                        class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-surya-gold transition-colors">
                        Chat with Astrologer</h3>
                    <p class="text-xs text-slate-600 dark:text-amber-100/70 mt-1 leading-relaxed">Instant text
                        consultation with top Acharyas.</p>
                </a>

                <!-- Quick Card 2: Call Astrologer -->
                <a href="#astrologers"
                    class="group bg-gradient-to-b from-amber-50 to-amber-100/50 dark:from-[#25101A] dark:to-[#180A12] p-5 rounded-2xl border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 shadow-md hover:shadow-surya-red/20 hover:-translate-y-1">
                    <div
                        class="w-11 h-11 rounded-xl bg-surya-gold text-slate-950 flex items-center justify-center text-lg mb-3.5 group-hover:scale-110 transition-transform shadow">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <h3
                        class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-surya-gold transition-colors">
                        Call Astrologer</h3>
                    <p class="text-xs text-slate-600 dark:text-amber-100/70 mt-1 leading-relaxed">One-on-one private
                        voice call in seconds.</p>
                </a>

                <!-- Quick Card 3: Daily Horoscope -->
                <a href="#horoscope"
                    class="group bg-gradient-to-b from-amber-50 to-amber-100/50 dark:from-[#25101A] dark:to-[#180A12] p-5 rounded-2xl border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 shadow-md hover:shadow-surya-red/20 hover:-translate-y-1">
                    <div
                        class="w-11 h-11 rounded-xl bg-surya-gold text-slate-950 flex items-center justify-center text-lg mb-3.5 group-hover:scale-110 transition-transform shadow">
                        <i class="fa-solid fa-sun"></i>
                    </div>
                    <h3
                        class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-surya-gold transition-colors">
                        Daily Horoscope</h3>
                    <p class="text-xs text-slate-600 dark:text-amber-100/70 mt-1 leading-relaxed">Your personalized
                        daily astrological reading.</p>
                </a>

                <!-- Quick Card 4: Get Free Kundli -->
                <a href="#panchang"
                    class="group bg-gradient-to-b from-amber-50 to-amber-100/50 dark:from-[#25101A] dark:to-[#180A12] p-5 rounded-2xl border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 shadow-md hover:shadow-surya-red/20 hover:-translate-y-1">
                    <div
                        class="w-11 h-11 rounded-xl bg-surya-gold text-slate-950 flex items-center justify-center text-lg mb-3.5 group-hover:scale-110 transition-transform shadow">
                        <i class="fa-solid fa-om"></i>
                    </div>
                    <h3
                        class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-surya-gold transition-colors">
                        Get Free Kundli</h3>
                    <p class="text-xs text-slate-600 dark:text-amber-100/70 mt-1 leading-relaxed">Detailed birth chart &
                        planetary analysis.</p>
                </a>
            </div>
        </div>
    </section>

    <!-- SECTION: TALK TO INDIA'S TOP RATED ASTROLOGERS (ASTROTALK INSPIRED DESIGN) -->
        <!-- SECTION: TALK TO INDIA'S TOP RATED ASTROLOGERS (ASTROTALK INSPIRED DESIGN) -->
    <section id="astrologers" class="py-14 border-t border-amber-200/30 dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Section Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
                <div class="space-y-2">
                    <span
                        class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-surya-red dark:text-surya-gold bg-surya-gold/10 px-3 py-1 rounded-full border border-surya-gold/30">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Live Now · 1,240 Online
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-serif font-bold text-slate-900 dark:text-white">
                        Talk to India's <span class="surya-headline-gradient">Top Rated</span> Astrologers
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-amber-100/70 max-w-2xl leading-relaxed">
                        Every astrologer below has cleared a 4-step verification — qualification, panel interview, live
                        audits, and a 30-day probation.
                    </p>
                </div>

                <a href="#astrologers"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-surya-gold hover:bg-surya-gold-hover text-slate-950 font-bold text-xs transition-transform hover:scale-105 shadow-md shrink-0">
                    <span>View all astrologers</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>

            <!-- Astrologers Card Grid (4 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
@php
    $displayAstrologers = [];
    if (isset($astrologers) && $astrologers->count() > 0) {
        $displayAstrologers = $astrologers->toArray();
    }
    
    $fallbackAstrologers = [
        [
            'name' => 'Valdikk',
            'badge' => 'TOP CHOICE',
            'badge_bg' => 'bg-amber-100 text-surya-red dark:bg-surya-gold/20 dark:text-surya-gold',
            'badge_border' => 'border-surya-gold/40',
            'exp' => '9 yrs exp · Hindi, Marwari',
            'tags' => ['Vedic', 'Tarot', 'Face Reading'],
            'rating' => '5.0',
            'orders' => '10k+ orders',
            'price' => '60',
            'photo' => 'images/logo.jpg'
        ],
        [
            'name' => 'Pt. Surinder',
            'badge' => 'RISING STAR',
            'badge_bg' => 'bg-surya-gold text-slate-950',
            'badge_border' => 'border-transparent',
            'exp' => '25 yrs exp · English, Hindi',
            'tags' => ['Vedic', 'Vastu', 'Lal Kitab'],
            'rating' => '5.0',
            'orders' => '15k+ orders',
            'price' => '165',
            'photo' => 'images/logo.jpg'
        ],
        [
            'name' => 'Svarnika',
            'badge' => 'CELEBRITY',
            'badge_bg' => 'bg-rose-500 text-white',
            'badge_border' => 'border-transparent',
            'exp' => '6 yrs exp · Hindi, English',
            'tags' => ['Vedic', 'Vastu', 'Prashna'],
            'rating' => '5.0',
            'orders' => '10k+ orders',
            'price' => '31',
            'photo' => 'images/logo.jpg'
        ],
        [
            'name' => 'Ananjay',
            'badge' => 'CELEBRITY',
            'badge_bg' => 'bg-rose-500 text-white',
            'badge_border' => 'border-transparent',
            'exp' => '5 yrs exp · English, Hindi',
            'tags' => ['Vedic', 'Nadi', 'Life Coach'],
            'rating' => '5.0',
            'orders' => '50k+ orders',
            'price' => '57',
            'photo' => 'images/logo.jpg'
        ]
    ];
    
    for ($i = count($displayAstrologers); $i < 4; $i++) {
        $displayAstrologers[] = null;
    }
@endphp

@foreach($displayAstrologers as $index => $astro)
    @php
        $fallback = $fallbackAstrologers[$index] ?? $fallbackAstrologers[0];
        
        if ($astro) {
            $userObj = isset($astro['user']) ? $astro['user'] : null;
            $name = $userObj ? $userObj['name'] : 'Astrologer';
            $expNum = isset($astro['years_of_experience']) ? $astro['years_of_experience'] : 5;
            
            $langsArr = isset($astro['languages']) ? $astro['languages'] : ['Hindi', 'English'];
            $langs = is_array($langsArr) ? implode(', ', $langsArr) : $langsArr;
            $exp = $expNum . ' yrs exp · ' . $langs;
            
            $tagsArr = isset($astro['areas_of_expertise']) ? $astro['areas_of_expertise'] : ['Vedic', 'Kundli'];
            $tags = is_array($tagsArr) ? array_slice($tagsArr, 0, 3) : array_slice(explode(',', $tagsArr), 0, 3);
            
            $rating = '5.0';
            $orders = '10k+ orders';
            $price = isset($astro['chat_rate_per_minute']) ? round($astro['chat_rate_per_minute']) : '50';
            
            $photo = (isset($astro['profile_photo']) && !empty($astro['profile_photo'])) 
                ? asset('storage/' . $astro['profile_photo']) 
                : asset('images/logo.jpg');
                
            $badge = $fallback['badge'];
            $badge_bg = $fallback['badge_bg'];
            $badge_border = $fallback['badge_border'];
        } else {
            $name = $fallback['name'];
            $badge = $fallback['badge'];
            $badge_bg = $fallback['badge_bg'];
            $badge_border = $fallback['badge_border'];
            $exp = $fallback['exp'];
            $tags = $fallback['tags'];
            $rating = $fallback['rating'];
            $orders = $fallback['orders'];
            $price = $fallback['price'];
            $photo = asset($fallback['photo']);
        }
    @endphp
    
    <div
        class="group bg-white dark:bg-gradient-to-b dark:from-[#25101A] dark:to-[#180A12] p-5 rounded-2xl border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 shadow-md hover:shadow-xl hover:-translate-y-1 relative">
        <span
            class="absolute top-4 right-4 text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $badge_bg }} border {{ $badge_border }}">{{ $badge }}</span>

        <div class="flex items-center gap-3.5 mb-4">
            <div class="relative">
                <img src="{{ $photo }}" alt="{{ $name }}"
                    class="w-14 h-14 rounded-full object-cover border-2 border-surya-gold shadow">
                <span
                    class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-500 rounded-full border-2 border-white dark:border-[#180A12]"></span>
            </div>
            <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                    {{ $name }} <i class="fa-solid fa-circle-check text-green-500 text-xs"></i>
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-amber-100/70">{{ $exp }}</p>
            </div>
        </div>

        <div
            class="flex flex-wrap gap-1.5 mb-4 text-[10px] font-semibold text-slate-700 dark:text-amber-100">
            @foreach($tags as $tag)
                <span
                    class="px-2 py-0.5 rounded-md bg-amber-50 dark:bg-white/5 border border-amber-200/50 dark:border-white/10">{{ $tag }}</span>
            @endforeach
        </div>

        <div
            class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-white/10 text-xs">
            <div>
                <div class="flex items-center gap-1 text-surya-gold text-xs font-bold">
                    <i class="fa-solid fa-star"></i>
                    <span>{{ $rating }}</span>
                    <span class="text-[10px] text-slate-400 dark:text-amber-200/50 font-normal">({{ $orders }})</span>
                </div>
                <p class="font-extrabold text-base text-slate-900 dark:text-white mt-0.5">₹{{ $price }}<span
                        class="text-xs font-normal text-slate-500 dark:text-amber-200/60">/min</span></p>
            </div>

            <a href="#astrologers"
                class="px-4 py-2 rounded-xl bg-surya-red text-white hover:bg-surya-red-dark font-bold text-xs transition-colors shadow flex items-center gap-1">
                <span>Chat</span> <i class="fa-solid fa-comments text-[10px] text-surya-gold"></i>
            </a>
        </div>
    </div>
@endforeach

            </div>

        </div>
    </section>
<!-- SECTION 3: OUR POWERFUL FEATURES -->
    <section class="py-12 border-y border-amber-200/40 dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2
                    class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 dark:text-white flex items-center justify-center gap-3">
                    <span class="text-surya-gold">✦</span> Our Powerful Features <span class="text-surya-gold">✦</span>
                </h2>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
                <!-- Feature 1 -->
                <div
                    class="bg-amber-50/60 dark:bg-[#25101A]/80 backdrop-blur-md p-4 rounded-xl text-center border border-amber-200/60 dark:border-surya-red/30 hover:border-surya-gold transition-all">
                    <div
                        class="w-10 h-10 mx-auto rounded-full bg-surya-red/10 text-surya-red dark:text-surya-gold flex items-center justify-center text-lg mb-2">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <h3 class="font-bold text-xs text-slate-900 dark:text-white">Daily Panchang</h3>
                    <p class="text-[10px] text-slate-500 dark:text-amber-100/70 mt-1">Tithi, Muhurat & Yoga</p>
                </div>
                <!-- Feature 2 -->
                <div
                    class="bg-amber-50/60 dark:bg-[#25101A]/80 backdrop-blur-md p-4 rounded-xl text-center border border-amber-200/60 dark:border-surya-red/30 hover:border-surya-gold transition-all">
                    <div
                        class="w-10 h-10 mx-auto rounded-full bg-surya-red/10 text-surya-red dark:text-surya-gold flex items-center justify-center text-lg mb-2">
                        <i class="fa-solid fa-compass"></i>
                    </div>
                    <h3 class="font-bold text-xs text-slate-900 dark:text-white">Kundli Analysis</h3>
                    <p class="text-[10px] text-slate-500 dark:text-amber-100/70 mt-1">Detailed Birth Chart</p>
                </div>
                <!-- Feature 3 -->
                <div
                    class="bg-amber-50/60 dark:bg-[#25101A]/80 backdrop-blur-md p-4 rounded-xl text-center border border-amber-200/60 dark:border-surya-red/30 hover:border-surya-gold transition-all">
                    <div
                        class="w-10 h-10 mx-auto rounded-full bg-surya-red/10 text-surya-red dark:text-surya-gold flex items-center justify-center text-lg mb-2">
                        <i class="fa-solid fa-hashtag"></i>
                    </div>
                    <h3 class="font-bold text-xs text-slate-900 dark:text-white">Numerology</h3>
                    <p class="text-[10px] text-slate-500 dark:text-amber-100/70 mt-1">Life Path & Destiny</p>
                </div>
                <!-- Feature 4 -->
                <div
                    class="bg-amber-50/60 dark:bg-[#25101A]/80 backdrop-blur-md p-4 rounded-xl text-center border border-amber-200/60 dark:border-surya-red/30 hover:border-surya-gold transition-all">
                    <div
                        class="w-10 h-10 mx-auto rounded-full bg-surya-red/10 text-surya-red dark:text-surya-gold flex items-center justify-center text-lg mb-2">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <h3 class="font-bold text-xs text-slate-900 dark:text-white">Kundli Matching</h3>
                    <p class="text-[10px] text-slate-500 dark:text-amber-100/70 mt-1">Match Compatibility</p>
                </div>
                <!-- Feature 5 -->
                <div
                    class="bg-amber-50/60 dark:bg-[#25101A]/80 backdrop-blur-md p-4 rounded-xl text-center border border-amber-200/60 dark:border-surya-red/30 hover:border-surya-gold transition-all">
                    <div
                        class="w-10 h-10 mx-auto rounded-full bg-surya-red/10 text-surya-red dark:text-surya-gold flex items-center justify-center text-lg mb-2">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h3 class="font-bold text-xs text-slate-900 dark:text-white">Talk to Astrologers</h3>
                    <p class="text-[10px] text-slate-500 dark:text-amber-100/70 mt-1">Direct Consultation</p>
                </div>
                <!-- Feature 6 -->
                <div
                    class="bg-amber-50/60 dark:bg-[#25101A]/80 backdrop-blur-md p-4 rounded-xl text-center border border-amber-200/60 dark:border-surya-red/30 hover:border-surya-gold transition-all">
                    <div
                        class="w-10 h-10 mx-auto rounded-full bg-surya-red/10 text-surya-red dark:text-surya-gold flex items-center justify-center text-lg mb-2">
                        <i class="fa-solid fa-star-of-david"></i>
                    </div>
                    <h3 class="font-bold text-xs text-slate-900 dark:text-white">Daily Horoscope</h3>
                    <p class="text-[10px] text-slate-500 dark:text-amber-100/70 mt-1">Read Rashifal</p>
                </div>
                <!-- Feature 7 -->
                <div
                    class="bg-amber-50/60 dark:bg-[#25101A]/80 backdrop-blur-md p-4 rounded-xl text-center border border-amber-200/60 dark:border-surya-red/30 hover:border-surya-gold transition-all">
                    <div
                        class="w-10 h-10 mx-auto rounded-full bg-surya-red/10 text-surya-red dark:text-surya-gold flex items-center justify-center text-lg mb-2">
                        <i class="fa-solid fa-newspaper"></i>
                    </div>
                    <h3 class="font-bold text-xs text-slate-900 dark:text-white">Blogs & Articles</h3>
                    <p class="text-[10px] text-slate-500 dark:text-amber-100/70 mt-1">Read Astrology Tips</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: TODAY'S PANCHANG & NUMEROLOGY CALCULATOR -->
    <section id="panchang" class="py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                class="crimson-gradient-bg p-8 sm:p-10 rounded-3xl text-white shadow-xl border border-surya-gold/40 relative overflow-hidden">
                <!-- Background Decorative Accent Glow -->
                <div
                    class="absolute -right-20 -bottom-20 w-80 h-80 bg-surya-gold/15 rounded-full blur-3xl pointer-events-none">
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">

                    <!-- Left: Today's Panchang (Exact Screenshot Items & Timings) -->
                    <div class="lg:col-span-7 space-y-6">
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-white">Today's Panchang</h2>
                            <p class="text-xs text-surya-gold flex items-center gap-2 mt-1 font-semibold">
                                <i class="fa-solid fa-calendar-days"></i> 22 May 2025, Wednesday
                            </p>
                        </div>

                        <!-- Panchang Details Grid (8 Items matched to screenshot) -->
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <!-- Item 1: Sunrise -->
                            <div
                                class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-surya-gold/30">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-500/20 text-surya-gold flex items-center justify-center shrink-0 text-sm">
                                    <i class="fa-solid fa-sun"></i>
                                </div>
                                <div>
                                    <p class="text-surya-gold/80 text-[10px] uppercase font-semibold">Sunrise</p>
                                    <p class="font-bold text-sm text-white">05:45 AM</p>
                                </div>
                            </div>

                            <!-- Item 2: Sunset -->
                            <div
                                class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-surya-gold/30">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-500/20 text-surya-gold flex items-center justify-center shrink-0 text-sm">
                                    <i class="fa-solid fa-moon"></i>
                                </div>
                                <div>
                                    <p class="text-surya-gold/80 text-[10px] uppercase font-semibold">Sunset</p>
                                    <p class="font-bold text-sm text-white">07:12 PM</p>
                                </div>
                            </div>

                            <!-- Item 3: Tithi -->
                            <div
                                class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-surya-gold/30">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-500/20 text-surya-gold flex items-center justify-center shrink-0 text-sm">
                                    <i class="fa-solid fa-book-open"></i>
                                </div>
                                <div>
                                    <p class="text-surya-gold/80 text-[10px] uppercase font-semibold">Tithi</p>
                                    <p class="font-bold text-xs text-white">Shukla Paksha Panchami</p>
                                </div>
                            </div>

                            <!-- Item 4: Nakshatra -->
                            <div
                                class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-surya-gold/30">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-500/20 text-surya-gold flex items-center justify-center shrink-0 text-sm">
                                    <i class="fa-solid fa-star"></i>
                                </div>
                                <div>
                                    <p class="text-surya-gold/80 text-[10px] uppercase font-semibold">Nakshatra</p>
                                    <p class="font-bold text-xs text-white">Hasta</p>
                                </div>
                            </div>

                            <!-- Item 5: Yoga -->
                            <div
                                class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-surya-gold/30">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-500/20 text-surya-gold flex items-center justify-center shrink-0 text-sm">
                                    <i class="fa-solid fa-om"></i>
                                </div>
                                <div>
                                    <p class="text-surya-gold/80 text-[10px] uppercase font-semibold">Yoga</p>
                                    <p class="font-bold text-xs text-white">Siddhi</p>
                                </div>
                            </div>

                            <!-- Item 6: Rahu Kaal -->
                            <div
                                class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-surya-gold/30">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-500/20 text-surya-gold flex items-center justify-center shrink-0 text-sm">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <p class="text-surya-gold/80 text-[10px] uppercase font-semibold">Rahu Kaal</p>
                                    <p class="font-bold text-xs text-white">12:30 PM - 02:00 PM</p>
                                </div>
                            </div>

                            <!-- Item 7: Abhijit Muhurat -->
                            <div
                                class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-surya-gold/30">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-500/20 text-surya-gold flex items-center justify-center shrink-0 text-sm">
                                    <i class="fa-solid fa-hands-praying"></i>
                                </div>
                                <div>
                                    <p class="text-surya-gold/80 text-[10px] uppercase font-semibold">Abhijit Muhurat
                                    </p>
                                    <p class="font-bold text-xs text-white">11:48 AM - 12:40 PM</p>
                                </div>
                            </div>

                            <!-- Item 8: Choghadiya -->
                            <div
                                class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-surya-gold/30">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-500/20 text-surya-gold flex items-center justify-center shrink-0 text-sm">
                                    <i class="fa-solid fa-compass"></i>
                                </div>
                                <div>
                                    <p class="text-surya-gold/80 text-[10px] uppercase font-semibold">Choghadiya</p>
                                    <p class="font-bold text-xs text-white">Shubh</p>
                                </div>
                            </div>
                        </div>

                        <a href="#panchang"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-white text-surya-red hover:bg-surya-gold hover:text-slate-950 font-bold text-xs transition-colors shadow">
                            View Full Panchang <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Right: Numerology Calculator Card (Dual Mode: Name / Date of Birth) -->
                    <div class="lg:col-span-5 bg-white text-slate-900 p-6 rounded-3xl shadow-2xl border-2 border-surya-gold/60 relative overflow-hidden"
                        x-data="{ 
                            calcMode: 'name', 
                            fullName: '', 
                            birthDate: '', 
                            calculatedNumber: null,
                            showModal: false,
                            resultName: '',
                            calculateNumber() {
                                let total = 0;
                                if (this.calcMode === 'dob' && this.birthDate) {
                                    const digits = this.birthDate.replace(/\D/g, '');
                                    for (let char of digits) total += parseInt(char, 10);
                                    this.resultName = 'Life Path Number';
                                } else if (this.calcMode === 'name' && this.fullName) {
                                    const letterValues = { A:1, B:2, C:3, D:4, E:5, F:8, G:3, H:5, I:1, J:1, K:2, L:3, M:4, N:5, O:7, P:8, Q:1, R:2, S:3, T:4, U:6, V:6, W:6, X:5, Y:1, Z:7 };
                                    const cleanName = this.fullName.toUpperCase().replace(/[^A-Z]/g, '');
                                    for (let char of cleanName) {
                                        total += letterValues[char] || 0;
                                    }
                                    this.resultName = 'Name Vibration Number';
                                }
                                if (total > 0) {
                                    while (total > 9 && total !== 11 && total !== 22) {
                                        total = String(total).split('').reduce((sum, d) => sum + parseInt(d, 10), 0);
                                    }
                                    this.calculatedNumber = total;
                                    
                                    // Dispatch event to sync Number Vibration section
                                    window.dispatchEvent(new CustomEvent('number-calculated', { detail: { num: total } }));
                                    
                                    // Open Result Popup Modal
                                    this.showModal = true;

                                    // Smooth scroll down to Numerology Details section
                                    setTimeout(() => {
                                        document.getElementById('numerology')?.scrollIntoView({ behavior: 'smooth' });
                                    }, 200);
                                }
                            }
                        }">

                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="font-serif font-bold text-xl text-surya-red">Numerology Calculator</h3>
                                <p class="text-[11px] text-slate-500 font-medium">Vedic Cosmic Number Analysis</p>
                            </div>

                            <!-- 3D Rotating Golden Zodiac Wheel Accent (Detailed Metallic Gold Vector Wheel) -->
                            <div class="relative w-16 h-16 flex items-center justify-center shrink-0">
                                <!-- Outer Rotating Gold Zodiac Ring (12 Constellation Spokes & Rays) -->
                                <svg class="w-full h-full animate-spin-slow text-amber-500 filter drop-shadow-sm"
                                    viewBox="0 0 100 100" fill="none" stroke="currentColor">
                                    <!-- Outer Gold Sun Ring -->
                                    <circle cx="50" cy="50" r="48" stroke-width="2" />
                                    <circle cx="50" cy="50" r="44" stroke-width="1" stroke-dasharray="3 3" />
                                    <circle cx="50" cy="50" r="34" stroke-width="1.5" />

                                    <!-- 12 Zodiac Houses Radial Spokes -->
                                    <line x1="50" y1="6" x2="50" y2="16" stroke-width="2" />
                                    <line x1="50" y1="84" x2="50" y2="94" stroke-width="2" />
                                    <line x1="6" y1="50" x2="16" y2="50" stroke-width="2" />
                                    <line x1="84" y1="50" x2="94" y2="50" stroke-width="2" />

                                    <line x1="19" y1="19" x2="26" y2="26" stroke-width="1.5" />
                                    <line x1="81" y1="81" x2="74" y2="74" stroke-width="1.5" />
                                    <line x1="81" y1="19" x2="74" y2="26" stroke-width="1.5" />
                                    <line x1="19" y1="81" x2="26" y2="74" stroke-width="1.5" />

                                    <line x1="10" y1="35" x2="19" y2="38" stroke-width="1" />
                                    <line x1="90" y1="65" x2="81" y2="62" stroke-width="1" />
                                    <line x1="35" y1="10" x2="38" y2="19" stroke-width="1" />
                                    <line x1="65" y1="90" x2="62" y2="81" stroke-width="1" />

                                    <!-- 12 Zodiac House Accent Stars -->
                                    <circle cx="50" cy="11" r="2" fill="currentColor" stroke="none" />
                                    <circle cx="89" cy="50" r="2" fill="currentColor" stroke="none" />
                                    <circle cx="50" cy="89" r="2" fill="currentColor" stroke="none" />
                                    <circle cx="11" cy="50" r="2" fill="currentColor" stroke="none" />
                                    <circle cx="77" cy="23" r="1.5" fill="currentColor" stroke="none" />
                                    <circle cx="77" cy="77" r="1.5" fill="currentColor" stroke="none" />
                                    <circle cx="23" cy="77" r="1.5" fill="currentColor" stroke="none" />
                                    <circle cx="23" cy="23" r="1.5" fill="currentColor" stroke="none" />
                                </svg>
                                <!-- Inner Result Badge -->
                                <div
                                    class="absolute inset-0 m-auto w-7 h-7 rounded-full bg-surya-red text-surya-gold flex items-center justify-center font-serif text-xs font-extrabold border-2 border-surya-gold shadow-md">
                                    <span x-show="calculatedNumber !== null" x-text="calculatedNumber"></span>
                                    <i x-show="calculatedNumber === null" class="fa-solid fa-star text-[10px]"></i>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-slate-600 mb-4 leading-relaxed">
                            Calculate your numbers and discover your future life path vibration.
                        </p>

                        <!-- Tab Selection: Name vs Date of Birth -->
                        <div
                            class="flex rounded-xl bg-slate-100 p-1 border border-slate-200 mb-4 text-xs font-semibold">
                            <button @click="calcMode = 'name'; calculatedNumber = null"
                                class="flex-1 py-1.5 rounded-lg transition-all text-center"
                                :class="calcMode === 'name' ? 'bg-surya-red text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                                By Full Name
                            </button>
                            <button @click="calcMode = 'dob'; calculatedNumber = null"
                                class="flex-1 py-1.5 rounded-lg transition-all text-center"
                                :class="calcMode === 'dob' ? 'bg-surya-red text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                                By Date of Birth
                            </button>
                        </div>

                        <!-- Form Input -->
                        <form @submit.prevent="calculateNumber()" class="space-y-4">
                            <!-- Name Input Mode -->
                            <div x-show="calcMode === 'name'">
                                <label
                                    class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Enter
                                    Your Full Name</label>
                                <input type="text" x-model="fullName" placeholder="e.g. Ramesh Sharma"
                                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:border-surya-red focus:ring-1 focus:ring-surya-red">
                            </div>

                            <!-- Date of Birth Input Mode (Custom Flatpickr Beautiful Calendar) -->
                            <div x-show="calcMode === 'dob'"
                                x-init="flatpickr($refs.datePicker, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'F j, Y', maxDate: 'today', onChange: (selectedDates, dateStr) => { birthDate = dateStr } })">
                                <label
                                    class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Select
                                    Date of Birth</label>
                                <div class="relative">
                                    <input x-ref="datePicker" type="text" placeholder="Select your birth date..."
                                        class="w-full px-3.5 py-2.5 pl-10 text-sm rounded-xl border border-slate-300 focus:outline-none focus:border-surya-red focus:ring-1 focus:ring-surya-red bg-white cursor-pointer shadow-sm">
                                    <i
                                        class="fa-solid fa-calendar-days text-surya-red absolute left-3.5 top-3 text-sm pointer-events-none"></i>
                                </div>
                            </div>

                            <!-- Calculate Button -->
                            <button type="submit"
                                class="w-full py-3 rounded-xl bg-surya-red hover:bg-surya-red-dark text-white font-bold text-xs tracking-wider uppercase transition-colors shadow-lg shadow-surya-red/30 border border-surya-gold/50 flex items-center justify-center gap-2">
                                <span>Calculate Now</span>
                                <i class="fa-solid fa-wand-magic-sparkles text-surya-gold"></i>
                            </button>
                        </form>

                        <!-- Interactive Popup Modal for Calculated Numerology Result -->
                        <div x-show="showModal" x-cloak
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100">

                            <div
                                class="bg-gradient-to-b from-[#2A0F1A] to-[#12050B] text-white p-6 sm:p-8 rounded-3xl border-2 border-surya-gold shadow-2xl max-w-md w-full relative text-center space-y-4">
                                <!-- Close Button -->
                                <button @click="showModal = false"
                                    class="absolute top-4 right-4 text-amber-200 hover:text-white text-lg">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>

                                <div
                                    class="w-16 h-16 rounded-full bg-surya-red text-surya-gold border-2 border-surya-gold mx-auto flex items-center justify-center font-serif text-3xl font-extrabold shadow-xl animate-bounce">
                                    <span x-text="calculatedNumber"></span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-[10px] uppercase font-bold text-surya-gold tracking-widest"
                                        x-text="resultName + ' Calculated!'"></span>
                                    <h4 class="text-xl font-bold font-serif text-white">Your Numerology Number is <span
                                            class="text-surya-gold" x-text="calculatedNumber"></span></h4>
                                </div>

                                <p
                                    class="text-xs text-amber-100/80 leading-relaxed bg-white/5 p-4 rounded-xl border border-white/10">
                                    We have automatically loaded your detailed planetary vibration, ruling planet, lucky
                                    colors, and compatibility details in the Numerology Vibrations section below!
                                </p>

                                <button @click="showModal = false"
                                    class="w-full py-2.5 rounded-xl bg-surya-gold hover:bg-surya-gold-hover text-slate-950 font-bold text-xs uppercase tracking-wider transition-colors shadow-lg">
                                    Explore Detailed Vibration
                                </button>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: EXPLORE NUMEROLOGY VIBRATIONS -->
    <section id="numerology" class="py-14 border-b border-amber-200/30 dark:border-white/10" x-data="{ 
            selectedNumber: 1,
            numProfiles: {
                1: { ruler: 'Sun (Surya)', desc: 'Number 1 is ruled by the Sun, representing leadership, independence, creativity, and a pioneering spirit. You possess strong determination and natural authority.', colors: 'Red, Gold, Orange', match: 'Numbers 1, 2, 4, 7' },
                2: { ruler: 'Moon (Chandra)', desc: 'Number 2 is ruled by the Moon, symbolizing sensitivity, intuition, harmony, and diplomacy. You are highly imaginative, empathetic, and a great peacemaker.', colors: 'White, Cream, Light Green', match: 'Numbers 1, 3, 7' },
                3: { ruler: 'Jupiter (Guru)', desc: 'Number 3 is ruled by Jupiter, embodying wisdom, optimism, expression, and joy. You possess strong communication skills, artistic flair, and spiritual intellect.', colors: 'Yellow, Gold, Saffron', match: 'Numbers 2, 3, 6, 9' },
                4: { ruler: 'Rahu', desc: 'Number 4 is ruled by Rahu, representing practical discipline, hard work, structure, and unexpected breakthroughs. You are methodical, grounded, and highly dependable.', colors: 'Blue, Electric Blue, Grey', match: 'Numbers 1, 4, 6, 8' },
                5: { ruler: 'Mercury (Budh)', desc: 'Number 5 is ruled by Mercury, symbolizing intelligence, agility, versatility, and freedom. You thrive on adventure, sharp business sense, and quick adaptability.', colors: 'Green, Turquoise, Light Grey', match: 'Numbers 1, 5, 6' },
                6: { ruler: 'Venus (Shukra)', desc: 'Number 6 is ruled by Venus, representing beauty, romance, luxury, responsibility, and artistic charm. You are loving, protective, and deeply attached to family & aesthetics.', colors: 'Pink, White, Royal Blue', match: 'Numbers 3, 6, 9' },
                7: { ruler: 'Ketu', desc: 'Number 7 is ruled by Ketu, embodying mysticism, analytical research, spiritual depth, and introspection. You are a natural seeker of truth with strong intuitive powers.', colors: 'White, Light Yellow, Pastels', match: 'Numbers 1, 2, 7' },
                8: { ruler: 'Saturn (Shani)', desc: 'Number 8 is ruled by Saturn, representing karma, authority, ambition, resilience, and wealth mastery. You possess immense perseverance and executive leadership.', colors: 'Dark Blue, Black, Violet', match: 'Numbers 4, 8, 6' },
                9: { ruler: 'Mars (Mangal)', desc: 'Number 9 is ruled by Mars, symbolizing courage, passion, humanitarian energy, and vitality. You possess bold leadership, determination, and a protective warrior spirit.', colors: 'Red, Maroon, Coral', match: 'Numbers 3, 6, 9' }
            }
        }" @number-calculated.window="selectedNumber = $event.detail.num">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-10">
                <h2 class="text-3xl sm:text-4xl font-serif font-bold text-slate-900 dark:text-white">
                    Explore <span class="surya-headline-gradient">Numerology Vibrations</span>
                </h2>
                <p class="text-xs text-slate-600 dark:text-amber-100/70 mt-2">
                    Numerology is an ancient study of numbers that helps reveal a person's character, life purpose, and
                    hidden destiny traits. Select a number below to check its vibration.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- Left: Number Profile Card (Dynamic Data according to Selected Number 1-9) -->
                <div
                    class="lg:col-span-5 bg-amber-50/60 dark:bg-[#25101A]/80 backdrop-blur-md p-6 rounded-2xl border border-surya-gold/40 shadow-lg transition-all duration-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-full bg-surya-red text-surya-gold border-2 border-surya-gold font-serif font-bold text-xl flex items-center justify-center shadow-lg"
                            x-text="selectedNumber"></div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Number <span
                                    x-text="selectedNumber"></span> Profile</h3>
                            <p class="text-xs text-surya-gold font-semibold"
                                x-text="'Ruler: ' + numProfiles[selectedNumber].ruler"></p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-amber-100/80 leading-relaxed mb-4"
                        x-text="numProfiles[selectedNumber].desc">
                    </p>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-amber-200/50 dark:border-white/10">
                            <span class="text-slate-500 dark:text-amber-200/60">Lucky Colors:</span>
                            <span class="font-semibold text-slate-800 dark:text-white"
                                x-text="numProfiles[selectedNumber].colors"></span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500 dark:text-amber-200/60">Best Match:</span>
                            <span class="font-semibold text-slate-800 dark:text-white"
                                x-text="numProfiles[selectedNumber].match"></span>
                        </div>
                    </div>
                </div>

                <!-- Right: Number Vibration Selectors (1-9) -->
                <div
                    class="lg:col-span-7 bg-amber-50/60 dark:bg-[#25101A]/80 backdrop-blur-md p-6 rounded-2xl border border-surya-gold/40 space-y-6 shadow-lg">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Number Vibrations (1–9)</h3>

                    <div class="grid grid-cols-5 sm:grid-cols-9 gap-3">
                        <template x-for="num in [1,2,3,4,5,6,7,8,9]" :key="num">
                            <button @click="selectedNumber = num"
                                class="w-10 h-10 rounded-full font-bold text-sm transition-all flex items-center justify-center shadow-sm"
                                :class="selectedNumber === num ? 'bg-surya-red text-surya-gold border-2 border-surya-gold scale-110 shadow-md' : 'bg-white dark:bg-[#180A12] text-slate-800 dark:text-amber-100 border border-amber-200 dark:border-surya-red/30 hover:border-surya-gold'">
                                <span x-text="num"></span>
                            </button>
                        </template>
                    </div>

                    <div
                        class="bg-amber-100/60 dark:bg-amber-950/40 p-4 rounded-xl text-xs text-slate-700 dark:text-amber-200 border border-surya-gold/40">
                        <p class="font-bold text-surya-red dark:text-surya-gold mb-1">How to Find your Life Path Number?
                        </p>
                        <p>Add all digits of your birth date together (e.g. 15/08/1995 -> 1+5+0+8+1+9+9+5 = 38 -> 3+8 =
                            11 -> 1+1 = 2).</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- DAILY HOROSCOPE SECTION (ASTROTALK INSPIRED DESIGN) -->
    <section id="horoscope" class="py-14 border-t border-amber-200/30 dark:border-white/10" x-data="{ 
            activeRashi: 'Sagittarius',
            activeTab: 'Today',
            zodiacs: [
                { name: 'Aries', hindi: 'Mesh', date: 'Mar 21 - Apr 19', icon: '♈', mood: 'Enthusiastic ⚡', luckyNum: 9, color: 'Red', colorBg: 'bg-red-500', desc: 'Aries feels energetic today. Career growth is strongly favored by Mars transit. Practice mindfulness in financial investments.', love: 80, career: 92, health: 85, money: 75 },
                { name: 'Taurus', hindi: 'Vrishabh', date: 'Apr 20 - May 20', icon: '♉', mood: 'Calm 🌿', luckyNum: 6, color: 'Green', colorBg: 'bg-emerald-500', desc: 'Taurus experiences peaceful harmony today. Venus brings romantic warmth and steady business gains. Keep physical exercise routine active.', love: 95, career: 82, health: 88, money: 90 },
                { name: 'Gemini', hindi: 'Mithun', date: 'May 21 - Jun 20', icon: '♊', mood: 'Curious 💡', luckyNum: 5, color: 'Yellow', colorBg: 'bg-yellow-400', desc: 'Mercury sharpens your intellect today. Excellent time for negotiations, presentations, and resolving long-pending disputes.', love: 75, career: 88, health: 80, money: 82 },
                { name: 'Cancer', hindi: 'Kark', date: 'Jun 21 - Jul 22', icon: '♋', mood: 'Intuitive 🌕', luckyNum: 2, color: 'Silver', colorBg: 'bg-slate-300', desc: 'The Moon heightens your emotional intuition. Family bonds strengthen and an unexpected career opportunity may emerge.', love: 90, career: 78, health: 86, money: 85 },
                { name: 'Leo', hindi: 'Singh', date: 'Jul 23 - Aug 22', icon: '♌', mood: 'Confident 👑', luckyNum: 1, color: 'Gold', colorBg: 'bg-amber-500', desc: 'Sun empowers your natural leadership. You will shine in team efforts and receive appreciation from senior mentors.', love: 82, career: 96, health: 90, money: 88 },
                { name: 'Virgo', hindi: 'Kanya', date: 'Aug 23 - Sep 22', icon: '♍', mood: 'Organized 📊', luckyNum: 5, color: 'Olive', colorBg: 'bg-lime-600', desc: 'Precision and hard work bring fruitful outcomes today. Financial planning pays off and personal wellness reaches peak levels.', love: 78, career: 90, health: 92, money: 86 },
                { name: 'Libra', hindi: 'Tula', date: 'Sep 23 - Oct 22', icon: '♎', mood: 'Harmonious ⚖️', luckyNum: 7, color: 'Pink', colorBg: 'bg-pink-400', desc: 'Balance returns to your social and romantic life. Creative ventures flourish and financial stability remains solid.', love: 92, career: 84, health: 85, money: 80 },
                { name: 'Scorpio', hindi: 'Vrishchik', date: 'Oct 23 - Nov 21', icon: '♏', mood: 'Determined 🦂', luckyNum: 8, color: 'Maroon', colorBg: 'bg-rose-900', desc: 'Deep focus enables you to overcome complex challenges today. Keep personal secrets safe and trust your inner instincts.', love: 85, career: 89, health: 82, money: 91 },
                { name: 'Sagittarius', hindi: 'Dhanu', date: 'Nov 22 - Dec 21', icon: '♐', mood: 'Focused 🎯', luckyNum: 3, color: 'Saffron', colorBg: 'bg-amber-500', desc: 'Sagittarius is feeling thoughtful today. Planetary alignments favor clear communication in relationships and strategic financial decisions.', love: 85, career: 90, health: 78, money: 72 },
                { name: 'Capricorn', hindi: 'Makar', date: 'Dec 22 - Jan 19', icon: '♑', mood: 'Ambitious 🏔️', luckyNum: 4, color: 'Navy', colorBg: 'bg-blue-900', desc: 'Saturn supports steady, disciplined effort. Long-term goals gain momentum and investment decisions yield positive returns.', love: 80, career: 94, health: 84, money: 92 },
                { name: 'Aquarius', hindi: 'Kumbh', date: 'Jan 20 - Feb 18', icon: '♒', mood: 'Innovative ♒', luckyNum: 8, color: 'Electric Blue', colorBg: 'bg-cyan-500', desc: 'Out-of-the-box ideas bring applause. Networking connects you with influential people who support your visions.', love: 78, career: 91, health: 88, money: 84 },
                { name: 'Pisces', hindi: 'Meen', date: 'Feb 19 - Mar 20', icon: '♓', mood: 'Empathetic 🌊', luckyNum: 3, color: 'Sea Green', colorBg: 'bg-emerald-400', desc: 'Jupiter enhances your spiritual wisdom and artistic expression. Romantic connections deepen with mutual trust.', love: 94, career: 82, health: 86, money: 80 }
            ],
            getActiveRashiData() {
                return this.zodiacs.find(z => z.name === this.activeRashi) || this.zodiacs[8];
            }
        }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Section Header & Timeframe Tabs -->
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
                <div>
                    <span
                        class="text-[11px] font-bold uppercase tracking-wider text-surya-red dark:text-surya-gold">Your
                        Daily Horoscope</span>
                    <h2 class="text-3xl sm:text-4xl font-serif font-bold text-slate-900 dark:text-white mt-1">
                        Your daily <span class="surya-headline-gradient">horoscope</span> reading
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-amber-100/70 mt-1">Pick your Raashi to see today's
                        pillars at a glance.</p>
                </div>

                <!-- Timeframe Pills (Today, Tomorrow, Week, Month) -->
                <div
                    class="flex bg-white dark:bg-[#180A12] p-1 rounded-full border border-amber-200 dark:border-surya-red/30 shadow-sm text-xs font-semibold self-start md:self-auto">
                    <template x-for="tab in ['Today', 'Tomorrow', 'Week', 'Month']" :key="tab">
                        <button @click="activeTab = tab" class="px-4 py-1.5 rounded-full transition-all text-center"
                            :class="activeTab === tab ? 'bg-surya-red text-white shadow-sm font-bold' : 'text-slate-600 dark:text-amber-100/70 hover:text-slate-900 dark:hover:text-white'">
                            <span x-text="tab"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- 12 Zodiac Raashi Grid (Astrotalk Clean Card Grid) -->
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-12 gap-3 mb-8">
                <template x-for="z in zodiacs" :key="z.name">
                    <button @click="activeRashi = z.name"
                        class="group relative flex flex-col items-center justify-center p-3 rounded-2xl border transition-all duration-300 cursor-pointer text-center"
                        :class="activeRashi === z.name ? 'bg-gradient-to-b from-surya-gold to-amber-600 text-slate-950 border-2 border-surya-gold shadow-lg shadow-surya-gold/20 scale-105 font-bold' : 'bg-white dark:bg-[#25101A]/80 text-slate-800 dark:text-amber-100 border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold hover:bg-amber-100/50 dark:hover:bg-[#341724]'">

                        <!-- Icon Circle Badge -->
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg mb-1.5 transition-transform group-hover:scale-110 shadow-sm"
                            :class="activeRashi === z.name ? 'bg-slate-950 text-surya-gold' : 'bg-surya-gold/15 dark:bg-surya-gold/10 text-surya-red dark:text-surya-gold border border-surya-gold/30'">
                            <span x-text="z.icon"></span>
                        </div>

                        <span class="text-xs font-bold leading-tight" x-text="z.name"></span>
                        <span class="text-[10px] opacity-80 font-medium"
                            :class="activeRashi === z.name ? 'text-slate-900' : 'text-surya-gold'"
                            x-text="z.hindi"></span>
                    </button>
                </template>
            </div>

            <!-- Reading Card Container (Astrotalk Split Layout: Left Details + Right Pillars) -->
            <div
                class="bg-amber-50/70 dark:bg-[#25101A]/80 backdrop-blur-md p-6 sm:p-8 rounded-3xl border border-surya-gold/40 shadow-xl grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">

                <!-- Left: Horoscope Reading Content -->
                <div class="lg:col-span-7 space-y-5">
                    <div
                        class="flex flex-wrap sm:flex-nowrap items-start sm:items-center justify-between gap-3 border-b border-amber-200/60 dark:border-white/10 pb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-2xl bg-surya-red text-surya-gold flex items-center justify-center text-2xl font-bold shadow-md border border-surya-gold/50 shrink-0">
                                <span x-text="getActiveRashiData().icon"></span>
                            </div>
                            <div>
                                <h3
                                    class="text-xl font-bold font-serif text-slate-900 dark:text-white flex flex-wrap items-center gap-1.5">
                                    <span x-text="getActiveRashiData().name"></span>
                                    <span class="text-xs text-surya-gold font-normal whitespace-nowrap"
                                        x-text="'(' + getActiveRashiData().hindi + ')'"></span>
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-amber-100/60 whitespace-nowrap"
                                    x-text="getActiveRashiData().date"></p>
                            </div>
                        </div>

                        <span
                            class="text-[10px] font-semibold bg-white dark:bg-white/10 px-3 py-1 rounded-full border border-amber-200 dark:border-white/10 text-slate-600 dark:text-amber-200 shrink-0 self-start sm:self-center"
                            x-text="activeTab + ' · 27 Jul 2026'"></span>
                    </div>

                    <p class="text-xs text-slate-700 dark:text-amber-100/90 leading-relaxed font-normal"
                        x-text="getActiveRashiData().desc">
                    </p>

                    <!-- Mood, Lucky Number, Lucky Color Tags -->
                    <div class="flex flex-wrap items-center gap-4 text-xs font-semibold pt-1">
                        <div
                            class="flex items-center gap-1.5 bg-white dark:bg-white/5 px-3 py-1.5 rounded-xl border border-amber-200/60 dark:border-white/10">
                            <span class="text-slate-500 dark:text-amber-200/60">Mood:</span>
                            <span class="text-slate-800 dark:text-white" x-text="getActiveRashiData().mood"></span>
                        </div>
                        <div
                            class="flex items-center gap-1.5 bg-white dark:bg-white/5 px-3 py-1.5 rounded-xl border border-amber-200/60 dark:border-white/10">
                            <span class="text-slate-500 dark:text-amber-200/60">Lucky #:</span>
                            <span class="text-surya-red dark:text-surya-gold font-bold"
                                x-text="getActiveRashiData().luckyNum"></span>
                        </div>
                        <div
                            class="flex items-center gap-1.5 bg-white dark:bg-white/5 px-3 py-1.5 rounded-xl border border-amber-200/60 dark:border-white/10">
                            <span class="text-slate-500 dark:text-amber-200/60">Color:</span>
                            <span class="w-3.5 h-3.5 rounded-full border border-white"
                                :class="getActiveRashiData().colorBg"></span>
                            <span class="text-slate-800 dark:text-white" x-text="getActiveRashiData().color"></span>
                        </div>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex items-center gap-3 pt-2">
                        <a href="#astrologers"
                            class="px-5 py-2.5 rounded-full bg-surya-gold hover:bg-surya-gold-hover text-slate-950 font-bold text-xs transition-colors shadow flex items-center gap-2">
                            <span>Get detailed horoscope</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                        <a href="#astrologers"
                            class="px-5 py-2.5 rounded-full bg-white dark:bg-white/10 hover:bg-slate-100 dark:hover:bg-white/20 text-slate-800 dark:text-white border border-amber-200 dark:border-white/20 font-semibold text-xs transition-colors">
                            Talk to a specialist
                        </a>
                    </div>
                </div>

                <!-- Right: Dynamic Life Pillars Progress Bars (Love, Career, Health, Money) -->
                <div
                    class="lg:col-span-5 bg-white dark:bg-[#180A12] p-6 rounded-2xl border border-amber-200 dark:border-surya-red/30 space-y-4 shadow-sm">
                    <h4
                        class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-amber-200/60 border-b border-slate-100 dark:border-white/10 pb-2 flex items-center justify-between">
                        <span>Today's Life Pillars</span>
                        <span class="text-surya-red dark:text-surya-gold font-serif text-[11px]"
                            x-text="getActiveRashiData().name + ' Score'"></span>
                    </h4>

                    <!-- Pillar 1: LOVE -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs font-semibold">
                            <span class="text-slate-600 dark:text-amber-200/80">LOVE</span>
                            <span class="text-surya-red dark:text-surya-gold font-bold"
                                x-text="getActiveRashiData().love + '%'"></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-white/10 h-2 rounded-full overflow-hidden">
                            <div class="bg-rose-500 h-full rounded-full transition-all duration-500"
                                :style="'width: ' + getActiveRashiData().love + '%'"></div>
                        </div>
                    </div>

                    <!-- Pillar 2: CAREER -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs font-semibold">
                            <span class="text-slate-600 dark:text-amber-200/80">CAREER</span>
                            <span class="text-surya-red dark:text-surya-gold font-bold"
                                x-text="getActiveRashiData().career + '%'"></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-white/10 h-2 rounded-full overflow-hidden">
                            <div class="bg-amber-500 h-full rounded-full transition-all duration-500"
                                :style="'width: ' + getActiveRashiData().career + '%'"></div>
                        </div>
                    </div>

                    <!-- Pillar 3: HEALTH -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs font-semibold">
                            <span class="text-slate-600 dark:text-amber-200/80">HEALTH</span>
                            <span class="text-surya-red dark:text-surya-gold font-bold"
                                x-text="getActiveRashiData().health + '%'"></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-white/10 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-500"
                                :style="'width: ' + getActiveRashiData().health + '%'"></div>
                        </div>
                    </div>

                    <!-- Pillar 4: MONEY -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs font-semibold">
                            <span class="text-slate-600 dark:text-amber-200/80">MONEY</span>
                            <span class="text-surya-red dark:text-surya-gold font-bold"
                                x-text="getActiveRashiData().money + '%'"></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-white/10 h-2 rounded-full overflow-hidden">
                            <div class="bg-amber-600 h-full rounded-full transition-all duration-500"
                                :style="'width: ' + getActiveRashiData().money + '%'"></div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- SECTION 6: WHAT OUR CUSTOMERS SAY (PREMIUM ASTROTALK REVIEWS DESIGN) -->
    <section class="py-14 border-t border-amber-200/30 dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center max-w-2xl mx-auto mb-10">
                <span
                    class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-surya-red dark:text-surya-gold bg-surya-gold/10 px-3 py-1 rounded-full border border-surya-gold/30 mb-2">
                    <i class="fa-solid fa-heart text-surya-gold"></i> Trusted by 5 Crore+ Users
                </span>
                <h2 class="text-3xl sm:text-4xl font-serif font-bold text-slate-900 dark:text-white">
                    What Our <span class="surya-headline-gradient">Customers</span> Say
                </h2>
                <p class="text-xs text-slate-600 dark:text-amber-100/70 mt-1">Real stories from people who found clarity
                    and guidance through Surya Path Astrology.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @if(isset($feedbacks) && $feedbacks->count() > 0)
                    @foreach($feedbacks->take(3) as $feedback)
                        <div
                            class="bg-amber-50/70 dark:bg-[#25101A]/80 backdrop-blur-md p-6 rounded-3xl border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 shadow-lg relative flex flex-col justify-between group">
                            <i
                                class="fa-solid fa-quote-right text-3xl text-surya-gold/20 absolute top-6 right-6 pointer-events-none"></i>

                            <div>
                                <div class="flex items-center gap-1 text-surya-gold text-xs mb-3">
                                    @for ($i = 0; $i < ($feedback->rating ?? 5); $i++)
                                        <i class="fa-solid fa-star"></i>
                                    @endfor
                                    @for ($i = ($feedback->rating ?? 5); $i < 5; $i++)
                                        <i class="fa-regular fa-star"></i>
                                    @endfor
                                    <span class="text-xs font-bold text-slate-700 dark:text-amber-100 ml-1">{{ number_format($feedback->rating ?? 5.0, 1) }}</span>
                                </div>
                                <p class="text-xs text-slate-700 dark:text-amber-100/90 leading-relaxed italic mb-6">
                                    "{{ $feedback->comment }}"
                                </p>
                            </div>

                            <div class="flex items-center gap-3 pt-4 border-t border-amber-200/50 dark:border-white/10">
                                <img src="{{ $feedback->user->profile_photo_url ?? asset('images/logo.jpg') }}" alt="{{ $feedback->user->name ?? 'Guest' }}"
                                    class="w-10 h-10 rounded-full object-cover border-2 border-surya-gold shadow-sm">
                                <div>
                                    <h3 class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-1">
                                        {{ $feedback->user->name ?? 'Guest' }} <i class="fa-solid fa-circle-check text-green-500 text-[10px]"></i>
                                    </h3>
                                    <p class="text-[10px] text-slate-500 dark:text-amber-200/60">{{ $feedback->user->city ?? 'Verified User' }} · Verified Consultation</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Fallback Review Card 1 -->
                    <div
                        class="bg-amber-50/70 dark:bg-[#25101A]/80 backdrop-blur-md p-6 rounded-3xl border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 shadow-lg relative flex flex-col justify-between group">
                        <i
                            class="fa-solid fa-quote-right text-3xl text-surya-gold/20 absolute top-6 right-6 pointer-events-none"></i>

                        <div>
                            <div class="flex items-center gap-1 text-surya-gold text-xs mb-3">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i
                                    class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i
                                    class="fa-solid fa-star"></i>
                                <span class="text-xs font-bold text-slate-700 dark:text-amber-100 ml-1">5.0</span>
                            </div>
                            <p class="text-xs text-slate-700 dark:text-amber-100/90 leading-relaxed italic mb-6">
                                "The Kundli service was amazingly detailed & accurate. Pt. Sharma solved all my marriage
                                compatibility queries with immense patience!"
                            </p>
                        </div>

                        <div class="flex items-center gap-3 pt-4 border-t border-amber-200/50 dark:border-white/10">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Riya Sharma"
                                class="w-10 h-10 rounded-full object-cover border-2 border-surya-gold shadow-sm">
                            <div>
                                <h3 class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-1">
                                    Riya Sharma <i class="fa-solid fa-circle-check text-green-500 text-[10px]"></i>
                                </h3>
                                <p class="text-[10px] text-slate-500 dark:text-amber-200/60">Delhi, India · Verified
                                    Consultation</p>
                            </div>
                        </div>
                    </div>

                    <!-- Fallback Review Card 2 -->
                    <div
                        class="bg-amber-50/70 dark:bg-[#25101A]/80 backdrop-blur-md p-6 rounded-3xl border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 shadow-lg relative flex flex-col justify-between group">
                        <i
                            class="fa-solid fa-quote-right text-3xl text-surya-gold/20 absolute top-6 right-6 pointer-events-none"></i>

                        <div>
                            <div class="flex items-center gap-1 text-surya-gold text-xs mb-3">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i
                                    class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i
                                    class="fa-solid fa-star"></i>
                                <span class="text-xs font-bold text-slate-700 dark:text-amber-100 ml-1">5.0</span>
                            </div>
                            <p class="text-xs text-slate-700 dark:text-amber-100/90 leading-relaxed italic mb-6">
                                "Best astrology app! Got instant consultation for my career move. The gemstones
                                recommendation worked wonders for me."
                            </p>
                        </div>

                        <div class="flex items-center gap-3 pt-4 border-t border-amber-200/50 dark:border-white/10">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Anamika Sen"
                                class="w-10 h-10 rounded-full object-cover border-2 border-surya-gold shadow-sm">
                            <div>
                                <h3 class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-1">
                                    Anamika Sen <i class="fa-solid fa-circle-check text-green-500 text-[10px]"></i>
                                </h3>
                                <p class="text-[10px] text-slate-500 dark:text-amber-200/60">Kolkata, India · Verified Chat
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Fallback Review Card 3 -->
                    <div
                        class="bg-amber-50/70 dark:bg-[#25101A]/80 backdrop-blur-md p-6 rounded-3xl border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 shadow-lg relative flex flex-col justify-between group">
                        <i
                            class="fa-solid fa-quote-right text-3xl text-surya-gold/20 absolute top-6 right-6 pointer-events-none"></i>

                        <div>
                            <div class="flex items-center gap-1 text-surya-gold text-xs mb-3">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i
                                    class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i
                                    class="fa-solid fa-star"></i>
                                <span class="text-xs font-bold text-slate-700 dark:text-amber-100 ml-1">5.0</span>
                            </div>
                            <p class="text-xs text-slate-700 dark:text-amber-100/90 leading-relaxed italic mb-6">
                                "Super fast Panchang & daily predictions. Highly trusted platform for genuine Vedic
                                astrologer guidance."
                            </p>
                        </div>

                        <div class="flex items-center gap-3 pt-4 border-t border-amber-200/50 dark:border-white/10">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Anushka Patel"
                                class="w-10 h-10 rounded-full object-cover border-2 border-surya-gold shadow-sm">
                            <div>
                                <h3 class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-1">
                                    Anushka Patel <i class="fa-solid fa-circle-check text-green-500 text-[10px]"></i>
                                </h3>
                                <p class="text-[10px] text-slate-500 dark:text-amber-200/60">Ahmedabad, India · Verified
                                    User</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </section>

    <!-- SECTION 7: LATEST BLOGS & ARTICLES (ASTROTALK HIGH-QUALITY DESIGN) -->
    <section id="blogs" class="py-14 border-t border-amber-200/30 dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-10 gap-4">
                <div>
                    <span
                        class="text-[11px] font-bold uppercase tracking-wider text-surya-red dark:text-surya-gold bg-surya-gold/10 px-3 py-1 rounded-full border border-surya-gold/30">Astrology
                        Knowledge Base</span>
                    <h2 class="text-3xl sm:text-4xl font-serif font-bold text-slate-900 dark:text-white mt-2">
                        Latest Vedic <span class="surya-headline-gradient">Blogs & Articles</span>
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-amber-100/70 mt-1">Read expert guidance on planetary
                        transits, Mahadasha, Vastu remedies & horoscopes.</p>
                </div>

                <a href="#blogs"
                    class="inline-flex items-center gap-2 text-xs font-bold text-surya-red dark:text-surya-gold hover:underline shrink-0">
                    <span>View all articles</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @if(isset($blogs) && $blogs->count() > 0)
                    @foreach($blogs->take(4) as $blog)
                        @php
                            $cat = strtolower($blog->type ?? '');
                            $badgeBg = 'bg-surya-gold text-slate-950';
                            $iconClass = 'fa-sun';
                            
                            if (str_contains($cat, 'ritual') || str_contains($cat, 'update') || str_contains($cat, 'vedic')) {
                                $badgeBg = 'bg-surya-red text-white';
                                $iconClass = 'fa-sun';
                            } elseif (str_contains($cat, 'match') || str_contains($cat, 'education') || str_contains($cat, 'kundli')) {
                                $badgeBg = 'bg-rose-500 text-white';
                                $iconClass = 'fa-moon';
                            } elseif (str_contains($cat, 'gem') || str_contains($cat, 'article')) {
                                $badgeBg = 'bg-amber-500 text-white';
                                $iconClass = 'fa-gem';
                            } elseif (str_contains($cat, 'vastu') || str_contains($cat, 'news')) {
                                $badgeBg = 'bg-emerald-600 text-white';
                                $iconClass = 'fa-compass';
                            }
                        @endphp
                        <div
                            class="group bg-white dark:bg-[#1C1217] backdrop-blur-md rounded-3xl overflow-hidden border border-amber-200/80 dark:border-surya-red/30 hover:border-surya-gold transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between">
                            <div>
                                <!-- Thumbnail Box with Decorative Gradient Icon -->
                                <div
                                    class="h-44 bg-gradient-to-br from-[#2A101C] via-[#1A0812] to-[#12050B] relative overflow-hidden flex items-center justify-center p-4 text-center">
                                    <div
                                        class="absolute inset-0 opacity-20 bg-[radial-gradient(#E1A61B_1px,transparent_1px)] [background-size:12px_12px]">
                                    </div>
                                    @if($blog->blog_image)
                                        <img src="{{ asset('storage/' . $blog->blog_image) }}" alt="{{ $blog->title }}" class="absolute inset-0 w-full h-full object-cover opacity-35 group-hover:scale-105 transition-transform duration-300">
                                    @endif
                                    <div
                                        class="w-14 h-14 rounded-full bg-[#12050B]/60 backdrop-blur-md border border-surya-gold/50 flex items-center justify-center text-2xl text-surya-gold shadow-lg group-hover:scale-110 transition-transform relative z-10">
                                        <i class="fa-solid {{ $iconClass }}"></i>
                                    </div>
                                    <span
                                        class="absolute top-3 left-3 text-[9px] font-bold uppercase tracking-wider {{ $badgeBg }} px-2.5 py-0.5 rounded-full shadow relative z-10">{{ $blog->type ? ucfirst($blog->type) : 'Vedic' }}</span>
                                </div>

                                <div class="p-5 space-y-3">
                                    <div
                                        class="flex items-center justify-between text-[10px] text-slate-400 dark:text-amber-200/60 font-semibold">
                                        <span class="flex items-center gap-1"><i class="fa-regular fa-calendar"></i> {{ $blog->created_at ? $blog->created_at->format('M d, Y') : 'Jul 25, 2026' }}</span>
                                        <span>5 min read</span>
                                    </div>
                                    <h3
                                        class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-surya-red dark:group-hover:text-surya-gold transition-colors leading-snug">
                                        {{ $blog->title }}
                                    </h3>
                                    <p
                                        class="text-xs text-slate-600 dark:text-amber-100/70 line-clamp-2 leading-relaxed font-normal">
                                        {{ Str::limit(strip_tags($blog->content), 120) }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="px-5 py-3.5 flex items-center justify-between border-t border-amber-200/50 dark:border-white/10 mt-3 text-xs bg-amber-100/40 dark:bg-white/5">
                                <span class="text-[11px] text-slate-700 dark:text-amber-100 font-semibold">By {{ $blog->author ?? 'Pt. Acharya' }}</span>
                                <a href="javascript:void(0)"
                                    @click="activeBlog = { 
                                        title: '{{ addslashes($blog->title) }}', 
                                        content: '{{ addslashes(strip_tags($blog->content)) }}', 
                                        image: '{{ $blog->blog_image ? asset('storage/' . $blog->blog_image) : '' }}', 
                                        type: '{{ $blog->type ? ucfirst($blog->type) : 'Vedic' }}', 
                                        date: '{{ $blog->created_at ? $blog->created_at->format('M d, Y') : 'Jul 25, 2026' }}', 
                                        author: '{{ $blog->author ?? 'Pt. Acharya' }}' 
                                    }; showBlogModal = true;"
                                    class="font-bold text-surya-red dark:text-surya-gold flex items-center gap-1.5 group-hover:translate-x-1 transition-transform">
                                    <span>Read More</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Fallback Blog 1 -->
                    <div
                        class="group bg-white dark:bg-[#1C1217] backdrop-blur-md rounded-3xl overflow-hidden border border-amber-200/80 dark:border-surya-red/30 shadow-md hover:border-surya-gold transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between">
                        <div>
                            <div
                                class="h-44 bg-gradient-to-br from-[#2A101C] via-[#1A0812] to-[#12050B] relative overflow-hidden flex items-center justify-center p-4 text-center">
                                <div
                                    class="absolute inset-0 opacity-20 bg-[radial-gradient(#E1A61B_1px,transparent_1px)] [background-size:12px_12px]">
                                </div>
                                <div
                                    class="w-14 h-14 rounded-full bg-[#12050B]/60 backdrop-blur-md border border-surya-gold/50 flex items-center justify-center text-2xl text-surya-gold shadow-lg group-hover:scale-110 transition-transform relative z-10">
                                    <i class="fa-solid fa-sun"></i>
                                </div>
                                <span
                                    class="absolute top-3 left-3 text-[9px] font-bold uppercase tracking-wider bg-surya-red text-white px-2.5 py-0.5 rounded-full shadow relative z-10">Vedic Rituals</span>
                            </div>

                            <div class="p-5 space-y-3">
                                <div
                                    class="flex items-center justify-between text-[10px] text-slate-400 dark:text-amber-200/60 font-semibold">
                                    <span class="flex items-center gap-1"><i class="fa-regular fa-calendar"></i> Jul 25, 2026</span>
                                    <span>5 min read</span>
                                </div>
                                <h3
                                    class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-surya-red dark:group-hover:text-surya-gold transition-colors leading-snug">
                                    Shani Mahadasha Remedies: How to Attain Peace & Career Success
                                </h3>
                                <p
                                    class="text-xs text-slate-600 dark:text-amber-100/70 line-clamp-2 leading-relaxed font-normal">
                                    Discover ancient Vedic mantras and gemstones to alleviate Saturn effect during Mahadasha period.
                                </p>
                            </div>
                        </div>

                        <div
                            class="px-5 py-3.5 flex items-center justify-between border-t border-amber-200/50 dark:border-white/10 mt-3 text-xs bg-amber-100/40 dark:bg-white/5">
                            <span class="text-[11px] text-slate-700 dark:text-amber-100 font-semibold">By Pt. Acharya</span>
                            <a href="javascript:void(0)"
                                @click="activeBlog = { 
                                    title: 'Shani Mahadasha Remedies: How to Attain Peace & Career Success', 
                                    content: 'Discover ancient Vedic mantras and gemstones to alleviate Saturn effect during Mahadasha period.', 
                                    image: '', 
                                    type: 'Vedic Rituals', 
                                    date: 'Jul 25, 2026', 
                                    author: 'Pt. Acharya' 
                                }; showBlogModal = true;"
                                class="font-bold text-surya-red dark:text-surya-gold flex items-center gap-1.5 group-hover:translate-x-1 transition-transform">
                                <span>Read More</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Fallback Blog 2 -->
                    <div
                        class="group bg-white dark:bg-[#1C1217] backdrop-blur-md rounded-3xl overflow-hidden border border-amber-200/80 dark:border-surya-red/30 shadow-md hover:border-surya-gold transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between">
                        <div>
                            <div
                                class="h-44 bg-gradient-to-br from-[#2A101C] via-[#1A0812] to-[#12050B] relative overflow-hidden flex items-center justify-center p-4 text-center">
                                <div
                                    class="absolute inset-0 opacity-20 bg-[radial-gradient(#E1A61B_1px,transparent_1px)] [background-size:12px_12px]">
                                </div>
                                <div
                                    class="w-14 h-14 rounded-full bg-[#12050B]/60 backdrop-blur-md border border-surya-gold/50 flex items-center justify-center text-2xl text-surya-gold shadow-lg group-hover:scale-110 transition-transform relative z-10">
                                    <i class="fa-solid fa-moon"></i>
                                </div>
                                <span
                                    class="absolute top-3 left-3 text-[9px] font-bold uppercase tracking-wider bg-rose-500 text-white px-2.5 py-0.5 rounded-full shadow relative z-10">Kundli Matching</span>
                            </div>

                            <div class="p-5 space-y-3">
                                <div
                                    class="flex items-center justify-between text-[10px] text-slate-400 dark:text-amber-200/60 font-semibold">
                                    <span class="flex items-center gap-1"><i class="fa-regular fa-calendar"></i> Jul 22, 2026</span>
                                    <span>4 min read</span>
                                </div>
                                <h3
                                    class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-surya-red dark:group-hover:text-surya-gold transition-colors leading-snug">
                                    Gun Milan Secrets: Why 36 Points Kundli Matching Matters in Marriage
                                </h3>
                                <p
                                    class="text-xs text-slate-600 dark:text-amber-100/70 line-clamp-2 leading-relaxed font-normal">
                                    Learn how Nadi Koota, Bhakoot, and Gana influence long-term marital bliss and harmony.
                                </p>
                            </div>
                        </div>

                        <div
                            class="px-5 py-3.5 flex items-center justify-between border-t border-amber-200/50 dark:border-white/10 mt-3 text-xs bg-amber-100/40 dark:bg-white/5">
                            <span class="text-[11px] text-slate-700 dark:text-amber-100 font-semibold">By Svarnika</span>
                            <a href="javascript:void(0)"
                                @click="activeBlog = { 
                                    title: 'Gun Milan Secrets: Why 36 Points Kundli Matching Matters in Marriage', 
                                    content: 'Learn how Nadi Koota, Bhakoot, and Gana influence long-term marital bliss and harmony.', 
                                    image: '', 
                                    type: 'Kundli Matching', 
                                    date: 'Jul 22, 2026', 
                                    author: 'Svarnika' 
                                }; showBlogModal = true;"
                                class="font-bold text-surya-red dark:text-surya-gold flex items-center gap-1.5 group-hover:translate-x-1 transition-transform">
                                <span>Read More</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Fallback Blog 3 -->
                    <div
                        class="group bg-white dark:bg-[#1C1217] backdrop-blur-md rounded-3xl overflow-hidden border border-amber-200/80 dark:border-surya-red/30 shadow-md hover:border-surya-gold transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between">
                        <div>
                            <div
                                class="h-44 bg-gradient-to-br from-[#2A101C] via-[#1A0812] to-[#12050B] relative overflow-hidden flex items-center justify-center p-4 text-center">
                                <div
                                    class="absolute inset-0 opacity-20 bg-[radial-gradient(#E1A61B_1px,transparent_1px)] [background-size:12px_12px]">
                                </div>
                                <div
                                    class="w-14 h-14 rounded-full bg-[#12050B]/60 backdrop-blur-md border border-surya-gold/50 flex items-center justify-center text-2xl text-surya-gold shadow-lg group-hover:scale-110 transition-transform relative z-10">
                                    <i class="fa-solid fa-gem"></i>
                                </div>
                                <span
                                    class="absolute top-3 left-3 text-[9px] font-bold uppercase tracking-wider bg-amber-500 text-white px-2.5 py-0.5 rounded-full shadow relative z-10">Gemstones</span>
                            </div>

                            <div class="p-5 space-y-3">
                                <div
                                    class="flex items-center justify-between text-[10px] text-slate-400 dark:text-amber-200/60 font-semibold">
                                    <span class="flex items-center gap-1"><i class="fa-regular fa-calendar"></i> Jul 20, 2026</span>
                                    <span>6 min read</span>
                                </div>
                                <h3
                                    class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-surya-red dark:group-hover:text-surya-gold transition-colors leading-snug">
                                    Complete Guide to Wearing Yellow Sapphire (Pukhraj) for Wealth
                                </h3>
                                <p
                                    class="text-xs text-slate-600 dark:text-amber-100/70 line-clamp-2 leading-relaxed font-normal">
                                    Which finger, metal, and auspicious day to wear Jupiter Pukhraj stone for maximum prosperity.
                                </p>
                            </div>
                        </div>

                        <div
                            class="px-5 py-3.5 flex items-center justify-between border-t border-amber-200/50 dark:border-white/10 mt-3 text-xs bg-amber-100/40 dark:bg-white/5">
                            <span class="text-[11px] text-slate-700 dark:text-amber-100 font-semibold">By Vaidikk</span>
                            <a href="javascript:void(0)"
                                @click="activeBlog = { 
                                    title: 'Complete Guide to Wearing Yellow Sapphire (Pukhraj) for Wealth', 
                                    content: 'Which finger, metal, and auspicious day to wear Jupiter Pukhraj stone for maximum prosperity.', 
                                    image: '', 
                                    type: 'Gemstones', 
                                    date: 'Jul 20, 2026', 
                                    author: 'Vaidikk' 
                                }; showBlogModal = true;"
                                class="font-bold text-surya-red dark:text-surya-gold flex items-center gap-1.5 group-hover:translate-x-1 transition-transform">
                                <span>Read More</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Fallback Blog 4 -->
                    <div
                        class="group bg-white dark:bg-[#1C1217] backdrop-blur-md rounded-3xl overflow-hidden border border-amber-200/80 dark:border-surya-red/30 shadow-md hover:border-surya-gold transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between">
                        <div>
                            <div
                                class="h-44 bg-gradient-to-br from-[#2A101C] via-[#1A0812] to-[#12050B] relative overflow-hidden flex items-center justify-center p-4 text-center">
                                <div
                                    class="absolute inset-0 opacity-20 bg-[radial-gradient(#E1A61B_1px,transparent_1px)] [background-size:12px_12px]">
                                </div>
                                <div
                                    class="w-14 h-14 rounded-full bg-[#12050B]/60 backdrop-blur-md border border-surya-gold/50 flex items-center justify-center text-2xl text-surya-gold shadow-lg group-hover:scale-110 transition-transform relative z-10">
                                    <i class="fa-solid fa-compass"></i>
                                </div>
                                <span
                                    class="absolute top-3 left-3 text-[9px] font-bold uppercase tracking-wider bg-emerald-600 text-white px-2.5 py-0.5 rounded-full shadow relative z-10">Vastu Tips</span>
                            </div>

                            <div class="p-5 space-y-3">
                                <div
                                    class="flex items-center justify-between text-[10px] text-slate-400 dark:text-amber-200/60 font-semibold">
                                    <span class="flex items-center gap-1"><i class="fa-regular fa-calendar"></i> Jul 18, 2026</span>
                                    <span>3 min read</span>
                                </div>
                                <h3
                                    class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-surya-red dark:group-hover:text-surya-gold transition-colors leading-snug">
                                    7 Vastu Shastra Rules for Main Entrance to Attract Positive Energy
                                </h3>
                                <p
                                    class="text-xs text-slate-600 dark:text-amber-100/70 line-clamp-2 leading-relaxed font-normal">
                                    Simple home remedies and direction placement tips to eliminate negative vibes at your house entry.
                                </p>
                            </div>
                        </div>

                        <div
                            class="px-5 py-3.5 flex items-center justify-between border-t border-amber-200/50 dark:border-white/10 mt-3 text-xs bg-amber-100/40 dark:bg-white/5">
                            <span class="text-[11px] text-slate-700 dark:text-amber-100 font-semibold">By Pt. Surinder</span>
                            <a href="javascript:void(0)"
                                @click="activeBlog = { 
                                    title: '7 Vastu Shastra Rules for Main Entrance to Attract Positive Energy', 
                                    content: 'Simple home remedies and direction placement tips to eliminate negative vibes at your house entry.', 
                                    image: '', 
                                    type: 'Vastu Tips', 
                                    date: 'Jul 18, 2026', 
                                    author: 'Pt. Surinder' 
                                }; showBlogModal = true;"
                                class="font-bold text-surya-red dark:text-surya-gold flex items-center gap-1.5 group-hover:translate-x-1 transition-transform">
                                <span>Read More</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                @endif
            </div>/div>
        </div>
    </section>

        <!-- FAQ SECTION (PREMIUM ASTROTALK DESIGN) -->
    <section class="py-14 border-t border-amber-200/30 dark:border-white/10" x-data="{ openFaq: 1 }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span
                    class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-surya-red dark:text-surya-gold bg-surya-gold/10 px-3 py-1 rounded-full border border-surya-gold/30 mb-2">
                    Have Questions?
                </span>
                <h2 class="text-3xl sm:text-4xl font-serif font-bold text-slate-900 dark:text-white">
                    Frequently Asked <span class="surya-headline-gradient">Questions</span>
                </h2>
                <p class="text-xs text-slate-600 dark:text-amber-100/70 mt-1">Get quick answers to common questions
                    about Kundli, Astrologers, and Surya Path services.</p>
            </div>

            <div class="space-y-4">
@php
    $faqs = [];
    if (isset($faq) && !empty($faq->content)) {
        preg_match_all('/<h[3-6]>(.*?)<\/h[3-6]>\s*(?:<p>(.*?)<\/p>)?/is', $faq->content, $matches, PREG_SET_ORDER);
        if (!empty($matches)) {
            foreach ($matches as $match) {
                if (!empty($match[1]) && !empty($match[2])) {
                    $faqs[] = [
                        'question' => trim(strip_tags($match[1])),
                        'answer' => trim($match[2]),
                    ];
                }
            }
        }
    }
    
    if (empty($faqs)) {
        $faqs = [
            [
                'question' => 'How accurate are Surya Path Kundli & Panchang predictions?',
                'answer' => 'Our predictions are 100% based on authentic Vedic astronomical algorithms and real-time planetary ephemeris calculations. Every calculation is verified by certified senior Vedic Acharyas.'
            ],
            [
                'question' => 'Are my personal consultation chats and call details confidential?',
                'answer' => 'Yes, 100%. All chats and voice calls on Surya Path are strictly encrypted end-to-end. Your birth details, personal questions, and conversation records remain 100% private between you and your astrologer.'
            ],
            [
                'question' => 'How can I talk to an astrologer on Surya Path?',
                'answer' => "Simply browse our 'Talk to India's Top Rated Astrologers' grid, choose an astrologer by their rating, experience, or language, and click 'Chat' or 'Call' for an instant consultation."
            ],
            [
                'question' => 'How do I download the Surya Path Mobile App?',
                'answer' => 'You can download the official Surya Path App directly from the Google Play Store for Android smartphones or the Apple App Store for iPhones.'
            ]
        ];
    }
@endphp

@foreach($faqs as $index => $item)
                <div class="bg-amber-50/70 dark:bg-[#25101A]/80 backdrop-blur-md rounded-2xl border transition-all duration-300 overflow-hidden shadow-sm"
                    :class="openFaq === {{ $index + 1 }} ? 'border-surya-gold ring-1 ring-surya-gold/40 shadow-lg' : 'border-amber-200/80 dark:border-surya-red/30'">
                    <button @click="openFaq = (openFaq === {{ $index + 1 }} ? 0 : {{ $index + 1 }})"
                        class="w-full text-left p-5 font-bold text-sm sm:text-base text-slate-900 dark:text-white flex justify-between items-center gap-4 cursor-pointer">
                        <span>{{ $item['question'] }}</span>
                        <div class="w-7 h-7 rounded-full bg-surya-gold/10 text-surya-red dark:text-surya-gold flex items-center justify-center shrink-0 text-xs transition-transform duration-300"
                            :class="openFaq === {{ $index + 1 }} ? 'rotate-180 bg-surya-red text-white dark:bg-surya-gold dark:text-slate-950' : ''">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </button>
                    <div x-show="openFaq === {{ $index + 1 }}" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="px-6 pb-6 pt-0 text-sm text-slate-700 dark:text-amber-100 leading-relaxed border-t border-amber-100 dark:border-white/10 mt-1">
                        <p class="pt-4 font-normal">{!! $item['answer'] !!}</p>
                    </div>
                </div>
@endforeach
            </div>

        </div>
    </section>

    @include('layouts.footer')
