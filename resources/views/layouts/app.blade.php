<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Splitify: hitung patungan tanpa ribet')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;600;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/splitify.css') }}">
</head>
<body>
    <div class="wrap">
        <div class="brand">
            <a href="{{ route('patungan.create') }}">Splitify</a>
            <span>Pencatat dan penghitung patungan</span>
        </div>
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>