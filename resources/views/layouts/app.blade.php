<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SwitchMaestro') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm/css/xterm.css" />

        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        
        <div class="flex h-screen bg-gray-100" x-data="{ open: false }">
            
            @include('layouts.sidebar')

            <div class="flex flex-col flex-1 w-full overflow-hidden">
                
                @include('layouts.header')

                <main class="h-full overflow-y-auto">
                    <div class="container px-6 py-8 mx-auto grid">
                        
                        @if (isset($header))
                            <h2 class="my-6 text-2xl font-semibold text-gray-700">
                                {{ $header }}
                            </h2>
                        @endif


                        {{ $slot }}

                    </div>
                </main>

                <footer class="py-4 text-center text-sm text-gray-500 border-t border-gray-200 bg-white shadow-inner-top">
                    © {{ date('Y') }} SwitchMaestro. Praca Inżynierska.
                </footer>
            </div>
        </div>
        @stack('scripts')
        @livewireScripts
    </body>
</html>