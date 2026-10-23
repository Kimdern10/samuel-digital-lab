@props(['work', 'headingLevel' => 'h3'])

<a {{ $attributes->merge(['class' => 'work-card', 'href' => route('work.show', $work)]) }}>
    <div class="work-card__media">
        @if ($preview = $work->previewImageUrl())
            <img class="work-card__img" src="{{ $preview }}" alt="" width="800" height="500" loading="lazy" decoding="async">
        @else
            <div class="work-card__placeholder" aria-hidden="true">{{ $work->title }}</div>
        @endif
    </div>
    <div class="work-card__body">
        @if ($work->category)
            <span class="work-card__tag">{{ $work->category }}</span>
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
