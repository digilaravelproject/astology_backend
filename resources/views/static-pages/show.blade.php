<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }} - {{ config('app.name') }}</title>
    <meta name="description" content="{{ $page->title }} - {{ config('app.name') }}">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: #e0e0e0;
            line-height: 1.8;
            min-height: 100vh;
        }
        .container { max-width: 900px; margin: 0 auto; padding: 60px 24px; }
        .header {
            text-align: center;
            margin-bottom: 50px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .header .logo {
            font-size: 1.2rem;
            color: #f7971e;
            margin-bottom: 10px;
            display: block;
            text-decoration: none;
        }
        .content {
            background: rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 40px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .content h2 { color: #ffd200; font-size: 1.5rem; margin: 30px 0 15px; }
        .content h3 { color: #f7971e; font-size: 1.2rem; margin: 25px 0 10px; }
        .content p { margin-bottom: 16px; color: #ccc; }
        .content ul, .content ol { margin: 15px 0 15px 25px; color: #ccc; }
        .content li { margin-bottom: 8px; }
        .content a { color: #ffd200; text-decoration: underline; }
        .content strong { color: #fff; }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.9rem;
            color: #888;
        }
        .footer a { color: #f7971e; text-decoration: none; }
        @media (max-width: 768px) {
            .container { padding: 30px 16px; }
            .header h1 { font-size: 1.8rem; }
            .content { padding: 24px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="/" class="logo">&#x2605; Surya Path Kundli</a>
            <h1>{{ $page->title }}</h1>
        </div>

        <div class="content">
            {!! $page->content !!}
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p style="margin-top:8px;">
                <a href="{{ route('page.faq') }}">FAQs</a> &middot;
                <a href="{{ route('page.privacy-policy') }}">Privacy Policy</a> &middot;
                <a href="{{ route('page.terms-and-conditions') }}">Terms &amp; Conditions</a> &middot;
                <a href="{{ route('page.payment-policy') }}">Payment Policy</a> &middot;
                <a href="{{ route('page.about-us') }}">About Us</a> &middot;
                <a href="{{ route('page.customer-support') }}">Customer Support</a>
            </p>
        </div>
    </div>
</body>
</html>
