{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        @if ($lastUpdated)
            <lastmod>{{ $lastUpdated->toAtomString() }}</lastmod>
        @endif
    </url>
    <url>
        <loc>{{ route('work.index') }}</loc>
        @if ($lastUpdated)
            <lastmod>{{ $lastUpdated->toAtomString() }}</lastmod>
        @endif
    </url>
    @foreach ($works as $work)
        <url>
            <loc>{{ route('work.show', $work) }}</loc>
            <lastmod>{{ $work->updated_at?->toAtomString() }}</lastmod>
        </url>
    @endforeach
    @if ($posts->isNotEmpty())
        <url>
            <loc>{{ route('blog.index') }}</loc>
            <lastmod>{{ $posts->max('updated_at')?->toAtomString() }}</lastmod>
        </url>
        @foreach ($posts as $post)
            <url>
                <loc>{{ route('blog.show', $post) }}</loc>
                <lastmod>{{ $post->updated_at?->toAtomString() }}</lastmod>
            </url>
        @endforeach
    @endif
    <url>
        <loc>{{ route('about') }}</loc>
    </url>
    <url>
        <loc>{{ route('privacy') }}</loc>
    </url>
</urlset>
