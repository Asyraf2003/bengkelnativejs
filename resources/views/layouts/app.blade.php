<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div id="app">
        <main>
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
