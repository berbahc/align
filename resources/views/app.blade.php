<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Der Grund, bevor das Stylesheet da ist — sonst blitzt beim Laden Weiß
             auf. Die beiden Werte sind `--canvas` aus `app.css`, hell und dunkel,
             und müssen mit ihm mitwandern. --}}
        <style>
            html {
                background-color: #F3EDE4;
            }

            html.dark {
                background-color: #100E0B;
            }
        </style>

        {{-- Hier ist `prefers-color-scheme` richtig und die `.dark`-Klasse falsch:
             die Tab-Leiste gehört dem Betriebssystem, nicht der App-Einstellung.
             `favicon.ico` bleibt der Fallback für Browser, die `media` ignorieren.

             Die Namen sagen, worauf die Kachel gehört: Auf eine helle Tab-Leiste
             die dunkle, auf eine dunkle die helle. --}}
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" href="/icon-on-light.png" media="(prefers-color-scheme: light)">
        <link rel="icon" type="image/png" href="/icon-on-dark.png" media="(prefers-color-scheme: dark)">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
