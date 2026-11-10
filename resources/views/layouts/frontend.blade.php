<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'VlogPlatform') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-link {
            font-weight: 500;
            color: #333 !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #667eea !important;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .vlog-thumbnail {
            aspect-ratio: 16/9;
            object-fit: cover;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            border-radius: 0 0 50% 50% / 100px;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .category-card:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .category-card:hover h5 {
            color: white;
        }

        .badge-featured {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
        }

        .badge-trending {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
        }

        .play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .card:hover .play-overlay {
            opacity: 1;
        }

        .play-icon {
            font-size: 3rem;
            color: white;
        }

        footer {
            background: #1a1a2e;
            color: white;
        }

        .footer-link {
            color: #a0a0a0;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-link:hover {
            color: white;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: translateY(-3px);
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 15px;
            background: #000;
        }

        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .creator-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .action-btn {
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: scale(1.05);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top" style="background: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}" style="font-weight: 800; font-size: 1.8rem; color: #1a1a2e;">
                <i class="bi bi-play-circle-fill" style="color: #667eea;"></i> VlogHub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto ms-4">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}" style="font-weight: 500; color: #1a1a2e;">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('vlogs.index') }}" style="font-weight: 500; color: #1a1a2e;">Explore</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('categories.index') }}" style="font-weight: 500; color: #1a1a2e;">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('creator.dashboard') }}" style="font-weight: 500; color: #1a1a2e;">Creators</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" style="font-weight: 500; color: #1a1a2e;">About</a>
                    </li>
                </ul>
                <form class="d-flex me-3" action="{{ route('search') }}" method="GET">
                    <div class="input-group" style="width: 300px;">
                        <input class="form-control" type="search" name="q" placeholder="Search..." value="{{ request('q') }}" style="border-radius: 8px 0 0 8px; border: 1px solid #e0e0e0; background: #f8f9fa;">
                        <button class="btn" type="submit" style="border-radius: 0 8px 8px 0; background: #667eea; color: white; border: 1px solid #667eea;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                @auth
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" style="border: 1px solid #e0e0e0; background: white; color: #1a1a2e; font-weight: 500;">
                            <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn me-2" style="border: 1px solid #e0e0e0; background: white; color: #1a1a2e; font-weight: 500; border-radius: 8px;">Login</a>
                    <a href="{{ route('register') }}" class="btn" style="background: #667eea; color: white; font-weight: 500; border-radius: 8px;">Sign Up</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3"><i class="bi bi-camera-video-fill"></i> VlogPlatform</h5>
                    <p class="text-muted">Your destination for amazing vlogs and video content from creators around the world.</p>
                    <div class="mt-3">
                        <a href="#" class="social-icon me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-icon me-2"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="social-icon me-2"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 class="mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('home') }}" class="footer-link">Home</a></li>
                        <li class="mb-2"><a href="{{ route('vlogs.index') }}" class="footer-link">Vlogs</a></li>
                        <li class="mb-2"><a href="{{ route('categories.index') }}" class="footer-link">Categories</a></li>
                        <li class="mb-2"><a href="{{ route('search') }}" class="footer-link">Search</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 class="mb-3">Categories</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="footer-link">Travel</a></li>
                        <li class="mb-2"><a href="#" class="footer-link">Food</a></li>
                        <li class="mb-2"><a href="#" class="footer-link">Technology</a></li>
                        <li class="mb-2"><a href="#" class="footer-link">Lifestyle</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h6 class="mb-3">Newsletter</h6>
                    <p class="text-muted">Subscribe to get the latest updates.</p>
                    <div class="input-group mt-3">
                        <input type="email" class="form-control" placeholder="Enter your email">
                        <button class="btn btn-gradient">Subscribe</button>
                    </div>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center text-muted">
                <p class="mb-0">&copy; {{ date('Y') }} VlogPlatform. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>