<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <main class="text-[13px] leading-[20px] w-full max-w-[335px] lg:max-w-4xl p-6 lg:p-20 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-lg">
            @if ($product)
                <h1 class="mb-1 font-medium">{{ $product }}</h1>
                <p class="text-[#706f6c] dark:text-[#A1A09A]">Top product in {{ $shopDomain }}</p>
            @else
                <p class="text-[#706f6c] dark:text-[#A1A09A]">No products found in {{ $shopDomain }}.</p>
            @endif
        </main>
    </body>
</html>
