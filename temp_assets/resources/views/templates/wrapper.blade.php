<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        @php
            $themeMeta = null;
            $pwaSettings = null;
            try {
                $settingsRepository = app(\Pterodactyl\Repositories\Eloquent\SettingsRepository::class);
                $raw = $settingsRepository->get('settings::app:theme:hyperv1', '{}');
                $decoded = json_decode($raw ?: '{}', true, 512, JSON_THROW_ON_ERROR);
                $themeMeta = $decoded['site']['meta'] ?? null;
                
                $addonsRaw = $settingsRepository->get('settings::app:addons:hyperv1', '{}');
                $addonsDecoded = json_decode($addonsRaw ?: '{}', true, 512, JSON_THROW_ON_ERROR);
                $pwaSettings = $addonsDecoded['addons']['pwa'] ?? null;
                $adsSettings = $addonsDecoded['addons']['ads-layout'] ?? null;
            } catch (\Throwable $e) {}
            
            $pwaEnabled = $pwaSettings['enabled'] ?? false;
            
            if ($pwaEnabled && !empty($pwaSettings['app_name'])) {
                $appTitle = $pwaSettings['app_name'];
            } elseif ($themeMeta && !empty($themeMeta['title'])) {
                $appTitle = $themeMeta['title'];
            } else {
                $appTitle = config('app.name', 'Pterodactyl');
            }
        @endphp

        <title>{{ $appTitle }}</title>

        @section('meta')
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="csrf-token" content="{{ csrf_token() }}">
            <meta name="robots" content="index, follow">
            <meta name="application-name" content="{{ $appTitle }}">

            @if($themeMeta && isset($themeMeta['faviconUrl']) && !empty($themeMeta['faviconUrl']))
                <link rel="icon" href="{{ $themeMeta['faviconUrl'] }}">
            @else
                <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
                <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
                <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
                <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#bc6e3c">
                <link rel="shortcut icon" href="/favicons/favicon.ico">
                <meta name="msapplication-config" content="/favicons/browserconfig.xml">
            @endif

            @if($pwaEnabled)
                <link rel="manifest" href="/api/public/pwa/manifest.json">
                <meta name="mobile-web-app-capable" content="yes">
                <meta name="apple-mobile-web-app-capable" content="yes">
                <meta name="apple-mobile-web-app-status-bar-style" content="{{ $pwaSettings['status_bar_style'] ?? 'default' }}">
                <meta name="apple-mobile-web-app-title" content="{{ $pwaSettings['app_short_name'] ?? $appTitle }}">
            @else
                <link rel="manifest" href="/favicons/manifest.json">
            @endif

            @if($themeMeta && isset($themeMeta['description']) && !empty($themeMeta['description']))
                <meta name="description" content="{{ Str::limit($themeMeta['description'], 300) }}">
            @endif

            @if($themeMeta && isset($themeMeta['image']) && !empty($themeMeta['image']))
                <meta property="og:image" content="{{ $themeMeta['image'] }}">
                <meta property="og:image:width" content="1200">
                <meta property="og:image:height" content="630">
                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:image" content="{{ $themeMeta['image'] }}">
            @endif

            @if($themeMeta && isset($themeMeta['title']) && !empty($themeMeta['title']))
                <meta property="og:title" content="{{ $themeMeta['title'] }}">
                <meta name="twitter:title" content="{{ $themeMeta['title'] }}">
            @else
                <meta property="og:title" content="{{ config('app.name', 'Pterodactyl') }}">
                <meta name="twitter:title" content="{{ config('app.name', 'Pterodactyl') }}">
            @endif

            @if($themeMeta && isset($themeMeta['description']) && !empty($themeMeta['description']))
                <meta property="og:description" content="{{ Str::limit($themeMeta['description'], 300) }}">
                <meta name="twitter:description" content="{{ Str::limit($themeMeta['description'], 300) }}">
            @endif

            @if($themeMeta && isset($themeMeta['color']) && !empty($themeMeta['color']))
                <meta name="theme-color" content="{{ $themeMeta['color'] }}">
            @elseif($pwaEnabled && isset($pwaSettings['theme_color']) && !empty($pwaSettings['theme_color']))
                <meta name="theme-color" content="{{ $pwaSettings['theme_color'] }}">
            @else
                <meta name="theme-color" content="#df3050">
            @endif

            <meta property="og:type" content="website">
            <meta property="og:url" content="{{ url()->current() }}">
        @show

        @section('user-data')
            @if(!is_null(Auth::user()))
                <script>
                    window.PterodactylUser = {!! json_encode(Auth::user()->toVueObject()) !!};
                </script>
            @endif
            @if(!empty($siteConfiguration))
                <script>
                    window.SiteConfiguration = {!! json_encode($siteConfiguration) !!};
                </script>
            @endif

            @php
                $settingsRepository = app(\Pterodactyl\Repositories\Eloquent\SettingsRepository::class);
                $raw = $settingsRepository->get('settings::app:theme:hyperv1', '{}');
                $themeData = json_decode($raw ?: '{}', true, 512, JSON_THROW_ON_ERROR);

                // Inject Addon Config values into Theme Config response
                if (!isset($addonsDecoded)) {
                    $addonsRawLocal = $settingsRepository->get('settings::app:addons:hyperv1', '{}');
                    $addonsDecoded = json_decode($addonsRawLocal ?: '{}', true) ?: [];
                }
                $themeSettingsAddon = $addonsDecoded['addons']['theme-settings'] ?? [];
                $userPermissions = $themeSettingsAddon['userPermissions'] ?? [];
                $defaults = $themeSettingsAddon['defaults'] ?? [];

                if (!isset($themeData['site'])) {
                    $themeData['site'] = [];
                }

                $themeData['site']['userPermissions'] = [
                    'colors' => isset($userPermissions['colors']) ? (bool) $userPermissions['colors'] : true,
                    'background' => isset($userPermissions['background']) ? (bool) $userPermissions['background'] : true,
                    'notifications' => isset($userPermissions['notifications']) ? (bool) $userPermissions['notifications'] : true,
                    'privacy' => isset($userPermissions['privacy']) ? (bool) $userPermissions['privacy'] : true,
                ];
                $themeData['site']['defaults'] = [
                    'privacy' => [
                        'blur' => isset($defaults['privacy']['blur']) ? (bool) $defaults['privacy']['blur'] : false,
                    ],
                ];
            @endphp
            @if(!empty($themeData))
                <script>
                    window.HyperV1ThemeData = {!! json_encode($themeData) !!};
                </script>
            @endif
        @show
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500&display=swap" media="print" onload="this.media='all'">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono&family=IBM+Plex+Sans:wght@500&display=swap" media="print" onload="this.media='all'">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" media="print" onload="this.media='all'">
        
        <noscript>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500&display=swap">
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono&family=IBM+Plex+Sans:wght@500&display=swap">
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap">
        </noscript>

        @php
            $settingsRepository = app(\Pterodactyl\Repositories\Eloquent\SettingsRepository::class);
            $raw = $settingsRepository->get('settings::app:theme:hyperv1', '{}');
            $decoded = json_decode($raw ?: '{}', true, 512, JSON_THROW_ON_ERROR);
            $themeVars = $decoded['variables'] ?? null;
            $themeEnforce = $decoded['enforce'] ?? false;
        @endphp

        @if($themeVars && is_array($themeVars) && count($themeVars) > 0)
            <style id="hyper-theme-vars">
                :root {
                    @foreach($themeVars as $key => $value)
                        @if(Str::startsWith($key, '--hyper-') && !empty($value) && !Str::endsWith($key, '-rgb'))
                            {{ $key }}: {{ $value }}{{ $themeEnforce ? ' !important' : '' }};
                        @endif
                    @endforeach
                    @if(isset($themeVars['--hyper-primary']) && preg_match('/^#([a-f0-9]{6})$/i', $themeVars['--hyper-primary'], $m))
                        --hyper-primary-rgb: {{ hexdec(substr($m[1], 0, 2)) }}, {{ hexdec(substr($m[1], 2, 2)) }}, {{ hexdec(substr($m[1], 4, 2)) }}{{ $themeEnforce ? ' !important' : '' }};
                    @endif
                    @if(isset($themeVars['--hyper-background']) && preg_match('/^#([a-f0-9]{6})$/i', $themeVars['--hyper-background'], $m))
                        --hyper-background-rgb: {{ hexdec(substr($m[1], 0, 2)) }}, {{ hexdec(substr($m[1], 2, 2)) }}, {{ hexdec(substr($m[1], 4, 2)) }}{{ $themeEnforce ? ' !important' : '' }};
                    @endif
                }
            </style>
            @if(isset($themeVars['--hyper-font-url']) && !empty($themeVars['--hyper-font-url']))
                <link rel="stylesheet" href="{{ $themeVars['--hyper-font-url'] }}" media="print" onload="this.media='all'">
                <noscript>
                    <link rel="stylesheet" href="{{ $themeVars['--hyper-font-url'] }}">
                </noscript>
            @endif
        @endif

        @yield('assets')

        @include('layouts.scripts')

        @if(!empty($adsSettings['header_script']))
            {!! $adsSettings['header_script'] !!}
        @endif
    </head>
    <body class="{{ $css['body'] ?? 'bg-neutral-50' }}">
        @section('content')
            @yield('above-container')
            @yield('container')
            @yield('below-container')
        @show
        @section('scripts')
            {{-- Load JavaScript runtime and bundle with defer to avoid render blocking --}}
            <script defer src="{!! $asset->url('runtime.js') !!}" crossorigin="anonymous" integrity="{!! $asset->integrity('runtime.js') !!}"></script>
            <script defer src="{!! $asset->url('main.js') !!}" crossorigin="anonymous" integrity="{!! $asset->integrity('main.js') !!}"></script>
        @show
        
        {{-- Service Worker for Asset Caching / PWA --}}
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    @if($pwaEnabled)
                    navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
                        .then(function(registration) {
                            fetch('/api/public/pwa/sw-config.js')
                                .then(function(response) { return response.json(); })
                                .then(function(config) {
                                    if (registration.active) {
                                        registration.active.postMessage({
                                            type: 'PWA_CONFIG',
                                            config: config
                                        });
                                    }
                                })
                                .catch(function(err) {
                                    console.warn('PWA config fetch failed:', err);
                                });
                        })
                        .catch(function(err) {
                            console.warn('Service Worker registration failed:', err);
                        });
                    @else
                    navigator.serviceWorker.register('/service-worker.js', { scope: '/' });
                    @endif
                });
            }
        </script>

        @if(!empty($adsSettings['body_script']))
            {!! $adsSettings['body_script'] !!}
        @endif
    </body>
</html>
