<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Writers Room | {{ $title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,620&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/writersroom.css') }}">
    @stack('styles')
</head>

<body>
    <div class="app-shell">
        <main class="app-main">
            <div class="app-content">
                {{ $slot }}
            </div>
            @include('components.footer')
        </main>
        @include('components.nav')
    </div>
    @stack('scripts')
</body>

</html>
