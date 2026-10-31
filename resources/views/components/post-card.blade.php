@props(['post', 'headingLevel' => 'h3'])

<a wire:navigate.hover {{ $attributes->merge(['class' => 'work-card post-card', 'href' => route('blog.show', $post)]) }}>
    <div class="work-card__media {{ $post->cover_path ? 'photo photo--card' : '' }}" @if ($post->cover_path) style="--photo: url('{{ $post->coverThumbUrl() }}')" @endif>
        @if ($post->cover_path)
            <img class="work-card__img" src="{{ $post->coverThumbUrl() }}" alt="" loading="lazy" decoding="async">
        @else
            <div class="work-card__placeholder" aria-hidden="true">{{ $post->title }}</div>
        @endif
    </div>
    <div class="work-card__body">
        <span class="post-card__meta">@if ($post->category)<span class="post-card__topic">{{ $post->category }}</span> · @endif{{ ($post->published_at ?? $post->created_at)?->format('j M Y') }} · {{ $post->readingMinutes() }} min read</span>
        <{{ $headingLevel }}>{{ $post->title }}</{{ $headingLevel }}>
        <p>{{ $post->summary() }}</p>
        <span class="link">Read article →</span>
    </div>
</a>
