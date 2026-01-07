<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <!-- Page Content (NO NAVBAR) -->
            <main>
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>

        <!-- Toast container -->
        <div id="toast" class="fixed right-4 bottom-4 z-50"></div>

        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script>
            // set default headers for AJAX requests
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrf) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;

            function toast(message, timeout = 3000) {
                const el = document.createElement('div');
                el.className = 'bg-black text-white px-4 py-2 rounded mb-2 opacity-90';
                el.textContent = message;
                document.getElementById('toast').appendChild(el);
                setTimeout(() => el.remove(), timeout);
            }
        </script>
    </body>
</html>
