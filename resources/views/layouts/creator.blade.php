<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'VlogHub') }} - Creator</title>

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
        }

        .creator-sidebar {
            background: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }

        .creator-nav-link {
            color: #1a1a2e;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .creator-nav-link:hover,
        .creator-nav-link.active {
            background: #667eea;
            color: white;
        }

        .main-content {
            padding: 30px;
        }

        .btn-creator {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-creator:hover {
            background: #5568d3;
            color: white;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="creator-sidebar" style="width: 250px;">
            <div class="p-4">
                <h5 class="mb-4" style="color: #667eea; font-weight: 700;">
                    <i class="bi bi-play-circle-fill"></i> VlogHub
                </h5>
                <div class="mb-4 pb-3" style="border-bottom: 1px solid #e0e0e0;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #667eea; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div>
                            <h6 class="mb-0" style="font-size: 0.9rem;">{{ auth()->user()->name }}</h6>
                            <small class="text-muted">Creator</small>
                        </div>
                    </div>
                </div>
                <nav>
                    <a href="{{ route('creator.dashboard') }}" class="creator-nav-link {{ request()->is('creator') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="{{ route('creator.vlogs.create') }}" class="creator-nav-link {{ request()->is('creator/vlogs/create') ? 'active' : '' }}">
                        <i class="bi bi-plus-circle"></i> Upload Vlog
                    </a>
                    <a href="{{ route('creator.vlogs.index') }}" class="creator-nav-link {{ request()->is('creator/vlogs*') ? 'active' : '' }}">
                        <i class="bi bi-camera-video"></i> My Vlogs
                    </a>
                    <a href="{{ route('creator.profile.edit') }}" class="creator-nav-link {{ request()->is('creator/profile') ? 'active' : '' }}">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <hr>
                    <a href="{{ route('home') }}" class="creator-nav-link">
                        <i class="bi bi-house"></i> View Site
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="creator-nav-link w-100 text-start" style="border: none; background: none; cursor: pointer;">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 main-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
