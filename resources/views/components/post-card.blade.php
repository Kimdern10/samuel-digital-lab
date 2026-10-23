@props(['post', 'headingLevel' => 'h3'])

<a {{ $attributes->merge(['class' => 'work-card post-card', 'href' => route('blog.show', $post)]) }}>
    <div class="work-card__media">
        @if ($post->cover_path)
            <img class="work-card__img" src="{{ $post->coverThumbUrl() }}" alt="" width="800" height="450" loading="lazy" decoding="async">
        @else
            <div class="work-card__placeholder" aria-hidden="true">{{ $post->title }}</div>
        @endif
    </div>
    <div class="work-card__body">
        <span class="post-card__meta">{{ ($post->published_at ?? $post->created_at)?->format('j M Y') }} · {{ $post->readingMinutes() }} min read</span>
        <{{ $headingLevel }}>{{ $post->title }}</{{ $headingLevel }}>
        <p>{{ $post->summary() }}</p>
        <span class="link">Read article →</span>
    </div>
</a>
