    <!-- SECTION 8: FOOTER (PREMIUM DESIGNS) -->
    <footer id="contact"
        class="bg-[#12050B]/95 backdrop-blur-md text-amber-100 pt-14 pb-8 border-t border-surya-gold/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-10 text-xs">

                <!-- Col 1: Brand Info -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo.jpg') }}" alt="Surya Path Logo"
                            class="w-10 h-10 object-contain rounded-xl border border-surya-gold/50 shadow">
                        <div class="flex flex-col">
                            <span class="font-serif text-lg font-bold tracking-tight text-white">Surya Path</span>
                            <span class="text-[9px] tracking-widest uppercase font-semibold text-surya-gold -mt-1">Vedic
                                Astrology</span>
                        </div>
                    </div>
                    <p class="text-amber-200/80 leading-relaxed font-normal">
                        Surya Path provides 100% accurate Vedic predictions, daily Panchang, Kundli matching, and
                        Numerology guidance verified by senior Acharyas.
                    </p>
                    <div class="flex gap-3 text-white pt-1">
                        <a href="#"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-surya-gold hover:text-slate-950 flex items-center justify-center text-xs transition-colors border border-white/20">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="#"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-surya-gold hover:text-slate-950 flex items-center justify-center text-xs transition-colors border border-white/20">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="#"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-surya-gold hover:text-slate-950 flex items-center justify-center text-xs transition-colors border border-white/20">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                        <a href="#"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-surya-gold hover:text-slate-950 flex items-center justify-center text-xs transition-colors border border-white/20">
                            <i class="fa-brands fa-x-twitter"></i>
                        </a>
                    </div>
                </div>

                <!-- Col 2: Quick Links -->
                <div>
                    <h4
                        class="font-bold text-surya-gold uppercase text-xs tracking-wider mb-4 border-b border-white/10 pb-2">
                        Quick Navigation</h4>
                    <ul class="space-y-2.5 text-amber-200/80 font-medium">
                        <li><a href="{{ url('/') }}#home" class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> Home</a></li>
                        <li><a href="{{ url('/') }}#astrologers"
                                class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> Live
                                Astrologers</a></li>
                        <li><a href="{{ url('/') }}#panchang"
                                class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> Vedic Panchang</a>
                        </li>
                        <li><a href="{{ url('/') }}#numerology"
                                class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> Numerology
                                Reading</a></li>
                        <li><a href="{{ url('/') }}#blogs" class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> Blogs & Articles</a>
                        </li>
                    </ul>
                </div>

                <!-- Col 3: Support & Policies -->
                <div>
                    <h4
                        class="font-bold text-surya-gold uppercase text-xs tracking-wider mb-4 border-b border-white/10 pb-2">
                        Policies & Support</h4>
                    <ul class="space-y-2.5 text-amber-200/80 font-medium">
                        <li><a href="{{ route('about') }}" class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> About Us</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> Privacy Policy</a>
                        </li>
                        <li><a href="{{ route('terms') }}" class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> Terms & Conditions</a>
                        </li>
                        <li><a href="{{ route('payment_policy') }}"
                                class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> Refund Policy</a>
                        </li>
                        <li><a href="{{ route('support') }}" class="hover:text-surya-gold transition-colors flex items-center gap-1.5"><i
                                    class="fa-solid fa-angle-right text-[10px] text-surya-gold"></i> Contact Support</a>
                        </li>
                    </ul>
                </div>

                <!-- Col 4: Download App -->
                <div>
                    <h4
                        class="font-bold text-surya-gold uppercase text-xs tracking-wider mb-4 border-b border-white/10 pb-2">
                        Get App</h4>
                    <p class="text-amber-200/80 mb-4 leading-relaxed">Download Surya Path app on Android & iOS for 100%
                        free daily horoscopes.</p>
                    <div class="space-y-2.5">
                        <a href="#"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-surya-red hover:bg-surya-red-dark text-white font-bold text-xs border border-surya-gold/50 shadow transition-transform hover:scale-105">
                            <i class="fa-brands fa-google-play text-lg text-surya-gold"></i>
                            <div class="text-left leading-tight">
                                <p class="text-[9px] uppercase font-normal text-amber-100">Get it on</p>
                                <p class="text-xs font-bold">Google Play</p>
                            </div>
                        </a>
                        <a href="#"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-surya-gold/40 shadow transition-transform hover:scale-105">
                            <i class="fa-brands fa-apple text-lg text-surya-gold"></i>
                            <div class="text-left leading-tight">
                                <p class="text-[9px] uppercase font-normal text-amber-200/60">Download on</p>
                                <p class="text-xs font-bold">App Store</p>
                            </div>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Bottom Copyright & Credit -->
            <div
                class="pt-6 border-t border-white/10 text-[11px] text-amber-200/60 flex flex-col sm:flex-row justify-between items-center gap-2">
                <p>&copy; 2026 Surya Path. All rights reserved.</p>
                <p class="font-semibold text-amber-100 flex items-center gap-1">
                    <span>Made by</span> <span class="text-surya-gold font-bold">Digi Emperor</span> 👑
                </p>
            </div>
        </div>
    </footer>

    <!-- Floating Back to Top Button -->
    <button x-show="isScrolled" x-cloak @click="window.scrollTo({top: 0, behavior: 'smooth'})"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-6 right-6 z-50 w-11 h-11 rounded-full bg-surya-gold hover:bg-surya-gold-hover text-slate-950 flex items-center justify-center shadow-2xl border border-white hover:scale-110 transition-all cursor-pointer"
        title="Back to Top">
        <i class="fa-solid fa-arrow-up text-sm font-bold"></i>
    </button>

    <!-- Blog Details Tailwind+Alpine Modal -->
    <div x-show="showBlogModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100">

        <div @click.away="showBlogModal = false"
            class="bg-[#FFFDF7] dark:bg-gradient-to-b dark:from-[#2A0F1A] dark:to-[#12050B] text-slate-800 dark:text-white rounded-3xl border border-amber-200 dark:border-surya-gold shadow-2xl max-w-2xl w-full relative overflow-hidden flex flex-col max-h-[85vh]">
            
            <!-- Close Button -->
            <button @click="showBlogModal = false"
                class="absolute top-4 right-4 text-slate-700 dark:text-amber-200 hover:text-surya-red dark:hover:text-white text-lg bg-white/20 p-2 rounded-full z-20 backdrop-blur-md border border-white/10 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="overflow-y-auto">
                <!-- Blog Image -->
                <div class="h-64 relative bg-gradient-to-br from-surya-red via-surya-red-dark to-surya-maroon flex items-center justify-center">
                    <template x-if="activeBlog && activeBlog.image">
                        <img :src="activeBlog.image" :alt="activeBlog.title" class="absolute inset-0 w-full h-full object-cover">
                    </template>
                    <template x-if="!activeBlog || !activeBlog.image">
                        <div class="text-center relative z-10">
                            <div class="w-16 h-16 rounded-full bg-white/10 backdrop-blur-md border border-surya-gold/50 flex items-center justify-center text-3xl text-surya-gold shadow-lg mx-auto">
                                <i class="fa-solid fa-sun"></i>
                            </div>
                        </div>
                    </template>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-4 left-6">
                        <span class="text-[10px] uppercase font-bold text-surya-gold tracking-widest" x-text="activeBlog ? activeBlog.type : ''"></span>
                        <h4 class="text-xl sm:text-2xl font-bold font-serif text-white mt-1 leading-tight" x-text="activeBlog ? activeBlog.title : ''"></h4>
                    </div>
                </div>

                <!-- Blog Content -->
                <div class="p-6 sm:p-8 space-y-4">
                    <div class="flex items-center gap-3 text-[10px] text-slate-500 dark:text-amber-200/60 font-semibold border-b border-amber-200/20 dark:border-white/10 pb-3">
                        <span class="flex items-center gap-1"><i class="fa-regular fa-calendar"></i> <span x-text="activeBlog ? activeBlog.date : ''"></span></span>
                        <span x-text="activeBlog ? 'By ' + activeBlog.author : ''"></span>
                    </div>
                    <div class="text-xs sm:text-sm leading-relaxed text-slate-700 dark:text-amber-100/90 whitespace-pre-wrap font-normal" x-text="activeBlog ? activeBlog.content : ''"></div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
