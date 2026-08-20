<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
@if (! empty($url['lastmod']))
        <lastmod>{{ $url['lastmod']->toAtomString() }}</lastmod>
@endif
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        <priority>{{ number_format($url['priority'], 1, '.', '') }}</priority>
        {{-- Chaque page existe dans les deux langues, à la même adresse,
             distinguée par le paramètre `lang`. --}}
        <xhtml:link rel="alternate" hreflang="fr" href="{{ $url['loc'] }}"/>
        <xhtml:link rel="alternate" hreflang="en" href="{{ $url['loc'] }}{{ str_contains($url['loc'], '?') ? '&' : '?' }}lang=en"/>
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $url['loc'] }}"/>
    </url>
@endforeach
</urlset>
