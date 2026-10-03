<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#030712">

        <title inertia>{{ config('app.name', 'Tarombo Batak - Pohon Silsilah Digital') }}</title>

        <!-- Primary SEO Meta Tags -->
        <meta name="description" content="Tarombo Batak - Platform visualisasi dan penelusuran silsilah marga Batak digital secara interaktif, lengkap, dan terstruktur dari Si Raja Batak hingga generasi penerus.">
        <meta name="keywords" content="tarombo batak, silsilah batak, pohon silsilah batak, marga batak, tarombo si raja batak, batak toba, silsilah marga batak online, asal usul marga batak">
        <meta name="author" content="Tarombo Batak">
        <meta name="robots" content="index, follow, max-image-preview:large">

        <!-- Open Graph / Facebook / WhatsApp -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Tarombo Batak">
        <meta property="og:title" content="Tarombo Batak - Pohon Silsilah Marga Batak Digital">
        <meta property="og:description" content="Jelajahi garis keturunan dan pohon silsilah suku Batak secara interaktif. Temukan asal-usul marga, generasi leluhur, serta silsilah keluarga Batak Anda.">
        <meta property="og:image" content="{{ asset('images/bg-batak-parallax.jpg') }}">
        <meta property="og:locale" content="id_ID">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Tarombo Batak - Pohon Silsilah Marga Batak Digital">
        <meta name="twitter:description" content="Jelajahi garis keturunan dan pohon silsilah suku Batak secara interaktif. Temukan asal-usul marga, generasi leluhur, serta silsilah keluarga Batak Anda.">
        <meta name="twitter:image" content="{{ asset('images/bg-batak-parallax.jpg') }}">

        <!-- Structured Data (JSON-LD) for Google -->
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Tarombo Batak',
            'url' => url('/'),
            'description' => 'Platform visualisasi silsilah marga Batak secara interaktif dan digital.',
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
