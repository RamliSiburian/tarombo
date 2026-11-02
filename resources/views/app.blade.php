<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#030712">

        <title inertia>{{ config('app.name', 'Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

        <!-- Primary SEO Meta Tags -->
        <meta name="description" content="Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia - Platform visualisasi dan penelusuran silsilah marga Sihotang secara digital, interaktif, lengkap, dan terstruktur dari Raja Sigodang Ulu Sihotang hingga generasi penerus.">
        <meta name="keywords" content="tarombo sihotang, silsilah sihotang, pohon silsilah sihotang, marga sihotang, raja sigodang ulu sihotang, sihotang dohot boruna, punguan sihotang, silsilah keluarga sihotang">
        <meta name="author" content="Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia">
        <meta name="robots" content="index, follow, max-image-preview:large">

        <!-- Open Graph / Facebook / WhatsApp -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Punguan Sihotang Dohot Boruna Se-Indonesia">
        <meta property="og:title" content="Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia">
        <meta property="og:description" content="Jelajahi garis keturunan dan pohon silsilah marga Sihotang secara interaktif. Temukan asal-usul, generasi leluhur, serta silsilah keluarga Sihotang Anda.">
        <meta property="og:image" content="{{ asset('images/logo.png') }}">
        <meta property="og:locale" content="id_ID">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia">
        <meta name="twitter:description" content="Jelajahi garis keturunan dan pohon silsilah marga Sihotang secara interaktif. Temukan asal-usul, generasi leluhur, serta silsilah keluarga Sihotang Anda.">
        <meta name="twitter:image" content="{{ asset('images/logo.png') }}">

        <!-- Structured Data (JSON-LD) for Google -->
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia',
            'url' => url('/'),
            'description' => 'Platform visualisasi silsilah marga Sihotang secara interaktif dan digital.',
            'inLanguage' => 'id-ID',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-950 text-gray-100">
        @inertia
    </body>
</html>
