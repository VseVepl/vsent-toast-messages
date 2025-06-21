<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel Welcome</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            line-height: 1.5;
            background-color: #f7fafc;
            color: #1a202c;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        h1 {
            font-size: 2.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        p {
            font-size: 1.125rem;
            color: #4a5568;
        }
        .laravel-version {
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Laravel!</h1>

        @php
            $frameworkVersion = app()->version();
        @endphp

        <p>This is a sample welcome page for your Laravel {{ $version ?? '12.x' }} application.</p>
        <p>You can start building something amazing!</p>

        <div class="laravel-version">
            Laravel Framework {{ $frameworkVersion }}
        </div>

        {{-- You can pass a specific version from your controller like this: --}}
        {{-- return view('welcome', ['version' => '12.0.1']); --}}
    </div>
</body>
</html>
