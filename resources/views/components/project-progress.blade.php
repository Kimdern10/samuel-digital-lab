@props(['stage'])

<div {{ $attributes->class('grid gap-2') }}>
    <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $stage->percent() }}" aria-label="{{ __('Progress') }}">
        <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $stage->percent() }}%"></div>
    </div>
    <ol class="flex justify-between gap-2 text-xs">
        @foreach (\App\Enums\ProjectStage::cases() as $case)
            <li @class([
                'font-semibold text-emerald-600 dark:text-emerald-400' => $case === $stage,
                'text-zinc-700 dark:text-zinc-300' => $case !== $stage && $case->percent() < $stage->percent(),
                'text-zinc-400' => $case->percent() > $stage->percent(),
            ])>{{ $case->percent() <= $stage->percent() ? '✓ ' : '' }}{{ $case->label() }}</li>
        @endforeach
    </ol>
</div>
