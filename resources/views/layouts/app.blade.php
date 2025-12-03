<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laporan Absensi') - HCIS</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    @stack('styles')
</head>

<body>
    <div class="topbar">
        <div class="topbar__brand">
            <img src="{{ asset('images/logo.png') }}" alt="Logo HRIS" class="logo">
            <div>
                <span class="brand-logo">HARMONIS</span>
                <span class="brand-sub">
                    HUMAN RESOURCES MANAGEMENT<br>
                    &amp; OPTIMIZATION INFORMATION SYSTEM
                </span>
            </div>
        </div>

        @if (session()->has('credentials'))
            <div class="topbar__actions">
                <span class="user">👤 <strong>{{ $username ?? session('username') }}</strong></span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn--secondary">🚪 LOGOUT</button>
                </form>
            </div>
        @endif
    </div>
    @yield('content')

    @stack('scripts')
</body>

</html>
