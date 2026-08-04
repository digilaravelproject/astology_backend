<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Astrology</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #2d0815;
            background-image:
                radial-gradient(circle at 18% 15%, rgba(209, 161, 103, 0.4), transparent 30%),
                radial-gradient(circle at 82% 82%, rgba(139, 13, 49, 0.7), transparent 42%),
                linear-gradient(135deg, #650923 0%, #2d0815 52%, #18040b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: #fffaf0;
            border-radius: 20px;
            border: 1px solid rgba(209, 161, 103, 0.72);
            box-shadow: 0 24px 70px rgba(24, 4, 11, 0.55), 0 0 0 4px rgba(209, 161, 103, 0.08);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #8B0D31 0%, #650923 58%, #3D0717 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '♈♉♊♋♌♍♎♏♐♑♒♓';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            font-size: 80px;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            color: #fff8e8;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            color: #D1A167;
            letter-spacing: 0.04em;
        }

        .login-logo {
            width: 76px;
            height: 76px;
            margin: 0 auto 15px;
            padding: 5px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid #D1A167;
            border-radius: 16px;
            object-fit: contain;
            box-shadow: 0 8px 24px rgba(24, 4, 11, 0.35), 0 0 22px rgba(209, 161, 103, 0.28);
            position: relative;
            z-index: 1;
        }

        .login-form {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .form-group input:focus {
            outline: none;
            border-color: #8B0D31;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 13, 49, 0.12);
        }

        .form-group input::placeholder {
            color: #999;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .remember-forgot a {
            color: #8B0D31;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .remember-forgot a:hover {
            color: #650923;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .form-group.checkbox-group label {
            margin-bottom: 0;
            font-weight: 400;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #A72A4F 0%, #8B0D31 52%, #650923 100%);
            color: white;
            border: 1px solid #D1A167;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(101, 9, 35, 0.32);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(101, 9, 35, 0.42);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #8B0D31;
        }

        .error-list {
            margin: 0;
            padding-left: 20px;
        }

        .error-list li {
            margin-bottom: 5px;
        }

        .success-message {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #6c3;
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }

            .login-container {
                border-radius: 15px;
            }

            .login-header {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .login-form {
                padding: 30px 20px;
            }

            .remember-forgot {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="{{ asset('images/logo.jpeg') }}"
                 alt="Surya Path Logo"
                 class="login-logo">
            <h1>Admin Panel</h1>
            <p>Astrology Management System</p>
        </div>

        <form action="{{ route('admin.login.submit') }}" method="POST" class="login-form">
            @csrf

            @if ($errors->any())
                <div class="error-message">
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="success-message">
                    {{ session('success') }}
                </div>
            @endif

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" 
                       value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" 
                       required>
            </div>

            <div class="remember-forgot">
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Remember me</label>
                </div>
                <a href="#">Forgot Password?</a>
            </div>

            <button type="submit" class="login-btn">Login to Dashboard</button>
        </form>
    </div>
</body>
</html>
