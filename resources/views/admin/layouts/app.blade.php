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
        <aside class="fixed left-0 top-[70px] h-[calc(100vh-70px)] w-[250px] bg-white shadow-sm z-50 transition-transform duration-300 md:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
            <nav class="py-5">
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('admin.dashboard') }}"
                           class="flex items-center gap-4 px-6 py-4 text-[15px] font-medium transition-all duration-300
                                  {{ request()->routeIs('admin.dashboard')
                                     ? 'bg-sidebar-hover text-primary border-r-[3px] border-primary'
                                     : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <i class="fas fa-chart-line w-5 text-center text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.index') }}"
                           class="flex items-center gap-4 px-6 py-4 text-[15px] font-medium transition-all duration-300
                                  {{ request()->routeIs('admin.users.*')
                                     ? 'bg-sidebar-hover text-primary border-r-[3px] border-primary'
                                     : 'text-text-secondary hover:bg-sidebar-hover hover:text-primary' }}">
                            <i class="fas fa-users w-5 text-center text-lg"></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 md:ml-[250px]">
            <div class="p-4 md:p-8 max-w-[1400px] mx-auto">

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
