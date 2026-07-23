
        // 1. Initialize Entrance Animations
        AOS.init({ duration: 800, once: true, offset: 50 });

        // 2. Active Menu Tab Highlighting logic (ScrollSpy equivalent)
        const sections = document.querySelectorAll("section[id]");
        const navLinks = document.querySelectorAll(".navbar-nav .nav-link");
        const nav = document.getElementById('mainNavbar');

        window.addEventListener("scroll", () => {
            let scrollY = window.pageYOffset;
            
            // Adjust Navbar shadow (Cached query optimization)
            if (nav) {
                if (scrollY > 50) {
                    nav.style.boxShadow = '0 5px 20px rgba(0,0,0,0.08)';
                } else {
                    nav.style.boxShadow = '0 2px 15px rgba(0,0,0,0.05)';
                }
            }

            // Section active state
            sections.forEach(current => {
                const sectionHeight = current.offsetHeight;
                const sectionTop = current.offsetTop - 100; // offset for sticky nav
                const sectionId = current.getAttribute("id");

                if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                    navLinks.forEach(link => {
                        link.classList.remove("active");
                        if (link.getAttribute("href") === "#" + sectionId) {
                            link.classList.add("active");
                        }
                    });
                }
            });
        });
        
        // 3. Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const targetId = this.getAttribute('href');
                if(!targetId.startsWith('#') || targetId === '#') return;
                e.preventDefault();
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 70, // offset for fixed header
                        behavior: 'smooth'
                    });
                }
            });
        });

        // 4. Initialize Swiper Carousels
        let blogSwiper;
        function initBlogSwiper() {
            const visibleSlides = Array.from(document.querySelectorAll('.blog-slide')).filter(s => s.style.display !== 'none');
            
            if (window.blogSwiperInstance) {
                window.blogSwiperInstance.destroy(true, true);
            }

            blogSwiper = new Swiper('.blog-swiper', {
                slidesPerView: 1,
                spaceBetween: 24,
                loop: visibleSlides.length >= 4,
                autoplay: {
                    delay: 3500,
                    disableOnInteraction: false,
                },
                breakpoints: {
                    576: { slidesPerView: 2 },
                    992: { slidesPerView: 3 },
                    1200: { slidesPerView: 4 }
                },
                pagination: {
                    el: '.blog-swiper-pagination',
                    clickable: true,
                }
            });
            window.blogSwiperInstance = blogSwiper;
        }

        // Initialize blog swiper initially
        initBlogSwiper();

        // Blog dynamic filtering
        function filterBlogs(category, btn) {
            // Update active button state
            document.querySelectorAll('.blog-filter-btn').forEach(b => {
                b.classList.remove('active');
            });
            btn.classList.add('active');
            
            // Filter cards
            const slides = document.querySelectorAll('.blog-slide');
            slides.forEach(slide => {
                const cat = slide.getAttribute('data-category');
                if (category === 'all' || cat === category) {
                    slide.style.display = '';
                    slide.classList.add('swiper-slide');
                } else {
                    slide.style.display = 'none';
                    slide.classList.remove('swiper-slide');
                }
            });
            
            // Re-initialize Swiper
            initBlogSwiper();
        }

        // Blog details modal trigger
        function openBlogModal(card) {
            const title = card.getAttribute('data-title');
            const content = card.getAttribute('data-content');
            const image = card.getAttribute('data-image');
            const type = card.getAttribute('data-type');
            const author = card.getAttribute('data-author');
            const date = card.getAttribute('data-date');
            
            // Decode entities if needed (Laravel's e() helper outputs html entities)
            const doc = new DOMParser().parseFromString(title, "text/html");
            const decodedTitle = doc.documentElement.textContent;
            
            const docContent = new DOMParser().parseFromString(content, "text/html");
            const decodedContent = docContent.documentElement.textContent;
            
            document.getElementById('modalBlogTitle').innerText = decodedTitle;
            
            // Check if HTML content
            if (decodedContent.includes('<p>') || decodedContent.includes('<br>') || decodedContent.includes('<div>')) {
                document.getElementById('modalBlogContent').innerHTML = decodedContent;
            } else {
                document.getElementById('modalBlogContent').innerText = decodedContent;
            }
            
            document.getElementById('modalBlogImage').src = image;
            document.getElementById('modalBlogBadge').innerText = type;
            document.getElementById('modalBlogAuthor').innerHTML = '<i class="fas fa-user-feather me-2 text-danger"></i>By ' + author;
            document.getElementById('modalBlogDate').innerHTML = '<i class="far fa-calendar-alt me-2 text-danger"></i>' + date;
            
            // Trigger Bootstrap Modal
            const modal = new bootstrap.Modal(document.getElementById('blogDetailsModal'));
            modal.show();
        }

        const testimonialSwiper = new Swiper('.testimonial-swiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            breakpoints: {
                768: { slidesPerView: 2 },
                992: { slidesPerView: 3 }
            },
            pagination: {
                el: '.testimonial-swiper-pagination',
                clickable: true,
            }
        });

        // 5. Numerology Data & Logic
        const numerologyData = {
            1: {
                ruler: 'Sun (Surya)',
                traits: 'Number 1 is ruled by the Sun, representing leadership, independence, creativity, and a pioneering spirit. You are highly ambitious, self-motivated, and possess strong willpower. You prefer to lead rather than follow.',
                colors: 'Yellow, Gold, Orange',
                qualities: 'Leadership, Ambition, Willpower, Originality'
            },
            2: {
                ruler: 'Moon (Chandra)',
                traits: 'Number 2 is ruled by the Moon, representing sensitivity, intuition, diplomacy, and cooperation. You are peace-loving, imaginative, and excel at partnership and teamwork. You have a nurturing and gentle nature.',
                colors: 'White, Silver, Light Green',
                qualities: 'Diplomacy, Cooperation, Intuition, Empathy'
            },
            3: {
                ruler: 'Jupiter (Guru)',
                traits: 'Number 3 is ruled by Jupiter, the planet of expansion, wisdom, and creativity. You are optimistic, highly expressive, social, and enjoy communicating your thoughts. You have natural artistic talents and a joyful outlook on life.',
                colors: 'Yellow, Purple, Gold',
                qualities: 'Creativity, Optimism, Communication, Wisdom'
            },
            4: {
                ruler: 'Rahu',
                traits: 'Number 4 is ruled by Rahu, representing structure, practicality, discipline, and hard work. You are realistic, organized, and value stability. You are a builder of strong foundations and have an unconventional perspective.',
                colors: 'Blue, Grey, Khaki',
                qualities: 'Discipline, Practicality, Reliability, Focus'
            },
            5: {
                ruler: 'Mercury (Budh)',
                traits: 'Number 5 is ruled by Mercury, representing movement, versatility, adventure, and quick wit. You love freedom, change, and exploration. You are an excellent communicator, highly adaptable, and learn things quickly.',
                colors: 'Green, Turquoise, Light Grey',
                qualities: 'Adaptability, Communication, Versatility, Dynamism'
            },
            6: {
                ruler: 'Venus (Shukra)',
                traits: 'Number 6 is ruled by Venus, the planet of love, beauty, harmony, and responsibility. You are highly nurturing, family-oriented, artistic, and have a deep sense of justice and duty. You seek peace and balance in relationships.',
                colors: 'Pink, White, Light Blue',
                qualities: 'Nurturing, Harmony, Responsibility, Artistry'
            },
            7: {
                ruler: 'Ketu',
                traits: 'Number 7 is ruled by Ketu, representing analytical minds, spirituality, wisdom, and introspection. You are a natural researcher, seeking deep truths and mystery. You have a philosophical nature and value solitude.',
                colors: 'Light Green, Light Yellow, Pastel shades',
                qualities: 'Analysis, Intuition, Spirituality, Truth-seeking'
            },
            8: {
                ruler: 'Saturn (Shani)',
                traits: 'Number 8 is ruled by Saturn, representing authority, material success, realism, and karmic lessons. You are highly disciplined, resilient, organized, and possess excellent executive capabilities. Success comes with persistence.',
                colors: 'Dark Blue, Black, Purple',
                qualities: 'Organization, Resilience, Authority, Perseverance'
            },
            9: {
                ruler: 'Mars (Mangal)',
                traits: 'Number 9 is ruled by Mars, representing courage, humanitarianism, compassion, and passion. You are altruistic, protective of the weak, and possess great physical and mental endurance. You seek to leave a positive impact.',
                colors: 'Red, Orange, Rose',
                qualities: 'Courage, Compassion, Humanitarianism, Endurance'
            }
        };

        function selectNumberDetails(num) {
            document.querySelectorAll('.num-selector-btn').forEach(btn => {
                btn.classList.remove('active-num');
            });
            
            const activeBtn = document.getElementById('btn-num-' + num);
            if(activeBtn) {
                activeBtn.classList.add('active-num');
            }
            
            const data = numerologyData[num];
            if(data) {
                document.getElementById('selectedNumCircle').innerText = num;
                document.getElementById('selectedNumTitle').innerText = 'Number ' + num + ' Profile';
                document.getElementById('selectedNumRuler').innerText = 'Ruler: ' + data.ruler;
                document.getElementById('selectedNumDesc').innerText = data.traits;
                document.getElementById('selectedNumColors').innerText = data.colors;
                document.getElementById('selectedNumQualities').innerText = data.qualities;
            }
        }

        function calculateNumerology(e) {
            e.preventDefault();
            const dobStr = document.getElementById('numDate').value;
            if(!dobStr) return;
            
            const dob = new Date(dobStr);
            const day = dob.getDate();
            const month = dob.getMonth() + 1;
            const year = dob.getFullYear();
            
            let driver = reduceToSingleDigit(day);
            let totalSum = day + month + year;
            let destiny = reduceToSingleDigit(totalSum);
            
            document.getElementById('driverVal').innerText = driver;
            document.getElementById('destinyVal').innerText = destiny;
            
            const data = numerologyData[driver];
            if(data) {
                document.getElementById('rulerText').innerText = 'Ruler: ' + data.ruler;
                document.getElementById('traitsText').innerText = data.traits;
                document.getElementById('luckyColors').innerText = data.colors;
                document.getElementById('rulerPlanet').innerText = data.ruler.split(' ')[0];
            }
            
            document.getElementById('numerologyResult').classList.remove('d-none');
        }

        function calculateNumerologyQuick(e) {
            e.preventDefault();
            const name = document.getElementById('numNameQuick').value.trim();
            if(!name) return;
            
            const charMap = {
                a:1, j:1, s:1,
                b:2, k:2, t:2,
                c:3, l:3, u:3,
                d:4, m:4, v:4,
                e:5, n:5, w:5,
                f:6, o:6, x:6,
                g:7, p:7, y:7,
                h:8, q:8, z:8,
                i:9, r:9
            };
            
            let sum = 0;
            const cleanName = name.toLowerCase().replace(/[^a-z]/g, '');
            for(let i=0; i<cleanName.length; i++) {
                const char = cleanName[i];
                if(charMap[char]) {
                    sum += charMap[char];
                }
            }
            
            const nameNum = reduceToSingleDigit(sum);
            
            const targetSection = document.getElementById('numerology-section');
            if(targetSection) {
                window.scrollTo({
                    top: targetSection.offsetTop - 70,
                    behavior: 'smooth'
                });
                
                selectNumberDetails(nameNum);
                
                setTimeout(() => {
                    Swal.fire({
                        title: 'Name Number Calculated!',
                        html: `Your Name Number is <strong style="color: #ad003a; font-size: 1.5rem;">${nameNum}</strong>!<br>Explore your full profile below.`,
                        icon: 'success',
                        confirmButtonText: 'Explore Profile',
                        confirmButtonColor: '#ad003a',
                        customClass: {
                            popup: 'rounded-4 shadow-lg border-0',
                            title: 'font-family-serif fw-bold',
                            confirmButton: 'px-4 py-2 rounded-pill fw-semibold'
                        }
                    });
                }, 800);
            }
        }

        function reduceToSingleDigit(num) {
            let sum = num;
            while(sum > 9) {
                let tempSum = 0;
                let str = sum.toString();
                for(let i=0; i<str.length; i++) {
                    tempSum += parseInt(str[i]);
                }
                sum = tempSum;
            }
            return sum;
        }

        // Auto select number 1 on load
        selectNumberDetails(1);