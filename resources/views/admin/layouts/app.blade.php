<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Astrology</title>
    <!-- Add FontAwesome for icons if not present, useful for sidebar -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 100;
        }

        .header-logo {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #f95a8f 0%, #d63384 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f95a8f 0%, #d63384 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .admin-name {
            font-size: 14px;
            font-weight: 600;
        }

        .logout-btn {
            padding: 8px 16px;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #ee5a52;
            transform: translateY(-2px);
        }

        /* Layout */
        .wrapper {
            display: flex;
            min-height: 100vh;
            padding-top: 70px; /* Header height */
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: calc(100vh - 70px);
            left: 0;
            top: 70px;
            z-index: 90;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #555;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            gap: 15px;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #fdf2f6;
            color: #d63384;
            border-right: 3px solid #d63384;
        }

        .sidebar-menu a i {
            font-size: 18px;
            width: 20px;
            text-align: center;
            color: inherit;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
        }

        .container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            color: #222;
        }

        .page-header p {
            color: #666;
            font-size: 14px;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-close {
            background: none;
            border: none;
            font-size: 18px;
            color: inherit;
            cursor: pointer;
            opacity: 0.7;
        }
        
        .alert-close:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .header {
                padding: 0 15px;
            }
        }
        
        @yield('styles')
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-logo">
            <!-- Optional: Hamburger icon for mobile view -->
            <i class="fas fa-bars" id="mobile-menu-btn" style="display: none; cursor: pointer; margin-right: 15px; color: #333;"></i>
            ☉ Astrology Admin
        </div>
        <div class="header-right">
            <div class="admin-info">
                @php $admin = Auth::guard('admin')->user(); @endphp
                @if($admin)
                <div class="admin-avatar">{{ strtoupper(substr($admin->name, 0, 1)) }}</div>
                <div>
                    <div class="admin-name">{{ $admin->name }}</div>
                    <div style="font-size: 12px; color: #999;">{{ ucfirst($admin->role) }}</div>
                </div>
                @endif
            </div>
            <form action="{{ route('admin.logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <!-- Add more menu items here in the future -->
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container">
                @if(session('success'))
                    <div class="alert alert-success">
                        <span>{{ session('success') }}</span>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        <span>{{ session('error') }}</span>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <!-- Script for mobile menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if mobile view
            if (window.innerWidth <= 768) {
                document.getElementById('mobile-menu-btn').style.display = 'inline-block';
            }

            document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('show');
            });
            
            // Auto close alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.display = 'none';
                });
            }, 5000);
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('mobile-menu-btn').style.display = 'none';
                document.getElementById('sidebar').classList.remove('show');
            } else {
                document.getElementById('mobile-menu-btn').style.display = 'inline-block';
            }
        });
    </script>
    
    @yield('scripts')
</body>
</html>
