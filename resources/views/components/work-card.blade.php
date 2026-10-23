@props(['work', 'headingLevel' => 'h3'])

<a class="work-card" href="{{ route('work.show', $work) }}">
    @if ($work->cover_path)
        <img class="work-card__img" src="{{ $work->coverThumbUrl() }}" alt="" width="800" height="500" loading="lazy" decoding="async">
    @else
        <div class="work-card__placeholder" aria-hidden="true">{{ $work->title }}</div>
    @endif
    <div class="work-card__body">
        @if ($work->category)
            <span class="eyebrow">{{ $work->category }}</span>
        @endif
        <{{ $headingLevel }}>{{ $work->title }}</{{ $headingLevel }}>
        @if ($work->summary)
            <p>{{ $work->summary }}</p>
        @endif
        @if (! empty($work->results[0]['value']))
            <span class="work-card__result">{{ $work->results[0]['value'] }} · {{ $work->results[0]['label'] ?? '' }}</span>
        @endif
    </div>
</a>
