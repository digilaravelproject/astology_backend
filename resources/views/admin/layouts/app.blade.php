<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Astrology</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light font-sans text-text-primary" x-data="{ sidebarOpen: false }">

    <!-- Header -->
    <header class="bg-white shadow-header fixed w-full top-0 left-0 z-50 h-[70px] px-4 md:px-8 flex items-center justify-between">
        <!-- Logo & Mobile Menu -->
        <div class="flex items-center gap-4">
            <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray text-xl">
                <i class="fas fa-bars"></i>
            </button>
            <span class="text-xl font-bold bg-linear-to-r from-primary-light to-primary bg-clip-text text-transparent">
                ☉ Astrology Admin
            </span>
        </div>

        <!-- Right Side -->
        <div class="flex items-center gap-4 md:gap-6">
            @php $admin = Auth::guard('admin')->user(); @endphp
            @if($admin)
            <div class="hidden sm:flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-linear-to-br from-primary-light to-primary text-white flex items-center justify-center font-semibold">
                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                </div>
                <div>
                    <div class="text-sm font-semibold text-text-primary">{{ $admin->name }}</div>
                    <div class="text-xs text-text-muted">{{ ucfirst($admin->role) }}</div>
                </div>
            </div>
            @endif
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-danger text-white text-sm font-medium rounded-md hover:bg-danger-dark transition-all duration-300 hover:-translate-y-0.5">
                    Logout
                </button>
            </form>
        </div>
    </header>

    <div class="flex min-h-screen pt-[70px]">

        <!-- Sidebar Overlay (Mobile) -->
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-40 md:hidden">
        </div>

        <!-- Sidebar -->
        <aside class="fixed left-0 top-[70px] h-[calc(100vh-70px)] w-[250px] bg-white shadow-lg z-50 transition-all duration-300 transform md:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full md:translate-x-0'">
            <nav class="py-2 h-full overflow-y-auto">
                <ul class="space-y-1">
                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('admin.dashboard') }}"
                           class="flex items-center gap-4 px-6 py-3 text-[14px] font-medium transition-all duration-300
                                  {{ request()->routeIs('admin.dashboard')
                                     ? 'bg-sidebar-hover text-primary border-r-[3px] border-primary'
                                     : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <i class="fas fa-chart-line w-5 text-center text-base"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- User Management -->
                    <li x-data="{ open: {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.users-wallet') || request()->routeIs('admin.users-referrals') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-6 py-3 text-[14px] font-medium transition-all duration-300
                                       {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.users-wallet') || request()->routeIs('admin.users-referrals')
                                          ? 'bg-sidebar-hover text-primary'
                                          : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-users w-5 text-center text-base"></i>
                                <span>User Management</span>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] transition-transform duration-300" :class="open ? 'rotate-90' : ''"></i>
                        </button>
                        <ul x-show="open" x-collapse class="bg-light/30 border-l-[3px] border-primary/20 ml-6">
                            <li>
                                <a href="{{ route('admin.users.index') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.users.index') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> All Users
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.users.wallet') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.users.wallet') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> User Wallet
                                </a>
                            </li>
                            <?php /*<li>
                                <a href="{{ route('admin.users.referrals') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.users.referrals') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Referral Tracking
                                </a> */?>
                            </li>
                        </ul>
                    </li>

                    <!-- Astrologer Management -->
                    <li x-data="{ open: {{ request()->routeIs('admin.astrologers.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-6 py-3 text-[14px] font-medium transition-all duration-300
                                       {{ request()->routeIs('admin.astrologers.*')
                                          ? 'bg-sidebar-hover text-primary'
                                          : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-user-tie w-5 text-center text-base"></i>
                                <span>Astrologers</span>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] transition-transform duration-300" :class="open ? 'rotate-90' : ''"></i>
                        </button>
                        <ul x-show="open" x-collapse class="bg-light/30 border-l-[3px] border-primary/20 ml-6">
                            <li>
                                <a href="{{ route('admin.astrologers.index') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.astrologers.index') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> All Astrologers
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.astrologers.performance') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.astrologers.performance') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Performance
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.astrologers.reviews') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.astrologers.reviews') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Ratings & Reviews
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.astrologers.live') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.astrologers.live') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Live Now
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Order Management -->
                    <li x-data="{ open: {{ request()->routeIs('admin.orders.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-6 py-3 text-[14px] font-medium transition-all duration-300
                                       {{ request()->routeIs('admin.orders.*')
                                          ? 'bg-sidebar-hover text-primary'
                                          : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-shopping-cart w-5 text-center text-base"></i>
                                <span>Orders</span>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] transition-transform duration-300" :class="open ? 'rotate-90' : ''"></i>
                        </button>
                        <ul x-show="open" x-collapse class="bg-light/30 border-l-[3px] border-primary/20 ml-6">
                            <li>
                                <a href="{{ route('admin.orders.index') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.orders.index') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> All Orders
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.orders.by-astrologer') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.orders.by-astrologer') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> By Astrologer
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Blogs -->
                    <li x-data="{ open: {{ request()->routeIs('admin.blogs.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-6 py-3 text-[14px] font-medium transition-all duration-300
                                       {{ request()->routeIs('admin.blogs.*')
                                          ? 'bg-sidebar-hover text-primary'
                                          : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-blog w-5 text-center text-base"></i>
                                <span>Blogs</span>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] transition-transform duration-300" :class="open ? 'rotate-90' : ''"></i>
                        </button>
                        <ul x-show="open" x-collapse class="bg-light/30 border-l-[3px] border-primary/20 ml-6">
                            <li>
                                <a href="{{ route('admin.blogs.index') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.blogs.index') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> All Blogs
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.blogs.create') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.blogs.create') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Create Blog
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Matrimony -->
                    <li x-data="{ open: {{ request()->routeIs('admin.matrimonies.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-6 py-3 text-[14px] font-medium transition-all duration-300
                                       {{ request()->routeIs('admin.matrimonies.*')
                                          ? 'bg-sidebar-hover text-primary'
                                          : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-heart w-5 text-center text-base"></i>
                                <span>Matrimony</span>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] transition-transform duration-300" :class="open ? 'rotate-90' : ''"></i>
                        </button>
                        <ul x-show="open" x-collapse class="bg-light/30 border-l-[3px] border-primary/20 ml-6">
                            <li>
                                <a href="{{ route('admin.matrimonies.index') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.matrimonies.index') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> All Profiles
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.matrimonies.create') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.matrimonies.create') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Create Profile
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Remedies -->
                    <li x-data="{ open: {{ request()->routeIs('admin.remedies.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-6 py-3 text-[14px] font-medium transition-all duration-300
                                       {{ request()->routeIs('admin.remedies.*')
                                          ? 'bg-sidebar-hover text-primary'
                                          : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-prescription-bottle-alt w-5 text-center text-base"></i>
                                <span>Remedies</span>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] transition-transform duration-300" :class="open ? 'rotate-90' : ''"></i>
                        </button>
                        <ul x-show="open" x-collapse class="bg-light/30 border-l-[3px] border-primary/20 ml-6">
                            <li>
                                <a href="{{ route('admin.remedies.index') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.remedies.index') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> All Remedies
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.remedies.create') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.remedies.create') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Add Remedy
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Plans -->
                    <li x-data="{ open: {{ request()->routeIs('admin.plans.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-6 py-3 text-[14px] font-medium transition-all duration-300
                                       {{ request()->routeIs('admin.plans.*')
                                          ? 'bg-sidebar-hover text-primary'
                                          : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-gem w-5 text-center text-base"></i>
                                <span>Plan Management</span>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] transition-transform duration-300" :class="open ? 'rotate-90' : ''"></i>
                        </button>
                        <ul x-show="open" x-collapse class="bg-light/30 border-l-[3px] border-primary/20 ml-6">
                            <li>
                                <a href="{{ route('admin.plans.index') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.plans.index') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> All Plans
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.plans.create') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.plans.create') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Create Plan
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.plans.subscriptions') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.plans.subscriptions') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Subscriptions
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Static Pages -->
                    <li x-data="{ open: {{ request()->routeIs('admin.static_pages.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-6 py-3 text-[14px] font-medium transition-all duration-300
                                       {{ request()->routeIs('admin.static_pages.*')
                                          ? 'bg-sidebar-hover text-primary'
                                          : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-file-alt w-5 text-center text-base"></i>
                                <span>Static Pages</span>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] transition-transform duration-300" :class="open ? 'rotate-90' : ''"></i>
                        </button>
                        <ul x-show="open" x-collapse class="bg-light/30 border-l-[3px] border-primary/20 ml-6">
                            <li>
                                <a href="{{ route('admin.static_pages.index') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.static_pages.index') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> All Pages
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.static_pages.create') }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold {{ request()->routeIs('admin.static_pages.create') ? 'text-primary' : 'text-gray hover:text-primary' }}">
                                    <i class="fas fa-circle text-[6px]"></i> Create Page
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.static_pages.index', ['type' => 'faq']) }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold text-gray hover:text-primary">
                                    <i class="fas fa-circle text-[6px]"></i> FAQs
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.static_pages.index', ['type' => 'privacy_policy']) }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold text-gray hover:text-primary">
                                    <i class="fas fa-circle text-[6px]"></i> Privacy Policy
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.static_pages.index', ['type' => 'terms_and_conditions']) }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold text-gray hover:text-primary">
                                    <i class="fas fa-circle text-[6px]"></i> Terms & Conditions
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.static_pages.index', ['type' => 'payment_policy']) }}" 
                                   class="flex items-center gap-3 px-6 py-2.5 text-xs font-semibold text-gray hover:text-primary">
                                    <i class="fas fa-circle text-[6px]"></i> Payment Policy
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Reports -->
                    <li>
                        <a href="{{ route('admin.reports.index') }}" 
                           class="flex items-center gap-4 px-6 py-3 text-[14px] font-medium transition-all duration-300
                                  {{ request()->routeIs('admin.reports.index')
                                     ? 'bg-sidebar-hover text-primary border-r-[3px] border-primary'
                                     : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <i class="fas fa-file-invoice-dollar w-5 text-center text-base"></i>
                            <span>Reports & Analytics</span>
                        </a>
                    </li>

                    <!-- Settings -->
                    <li>
                        <a href="{{ route('admin.settings.index') }}" 
                           class="flex items-center gap-4 px-6 py-3 text-[14px] font-medium transition-all duration-300
                                  {{ request()->routeIs('admin.settings.index')
                                     ? 'bg-sidebar-hover text-primary border-r-[3px] border-primary'
                                     : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <i class="fas fa-cog w-5 text-center text-base"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 md:ml-[250px] transition-all duration-300">
            <div class="p-4 md:p-8 max-w-[1600px] mx-auto min-h-screen">

                <!-- Success Alert -->
                @if(session('success'))
                <div x-data="{ show: true }"
                     x-show="show"
                     x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="mb-5 p-4 bg-success/10 text-success-dark border-l-4 border-success rounded-lg flex items-center justify-between">
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                    <button @click="show = false" class="text-lg opacity-70 hover:opacity-100">&times;</button>
                </div>
                @endif

                <!-- Error Alert -->
                @if(session('error'))
                <div x-data="{ show: true }"
                     x-show="show"
                     x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="mb-5 p-4 bg-danger/10 text-danger-dark border-l-4 border-danger rounded-lg flex items-center justify-between">
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                    <button @click="show = false" class="text-lg opacity-70 hover:opacity-100">&times;</button>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @yield('scripts')
</body>
</html>
