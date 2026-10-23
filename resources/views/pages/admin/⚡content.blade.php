<?php

use App\Models\Setting;
use App\Support\PageSpeed;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Website content')] class extends Component {
    /**
     * Empty rows added by the "Add" buttons, and the most rows each list may have.
     *
     * @var array<string, array{template: array<string, string>, max: int}>
     */
    private const array ROW_LISTS = [
        'features' => ['template' => ['title' => '', 'text' => ''], 'max' => 8],
        'process_steps' => ['template' => ['title' => '', 'text' => '', 'note' => ''], 'max' => 6],
        'why_points' => ['template' => ['title' => '', 'text' => ''], 'max' => 8],
        'stats' => ['template' => ['value' => '', 'label' => ''], 'max' => 3],
    ];

    /** @var array<string, string> */
    public array $content = [];

    /** @var array<string, list<array<string, string>>> */
    public array $rows = [];

    public string $heroWords = '';

    public string $technologies = '';

    /** @var list<array{label: string, value: string}> */
    public array $speedScores = [];

    /**
     * Load the saved content.
     */
    public function mount(): void
    {
        $this->content = array_diff_key(Setting::values(), array_flip(Setting::BUSINESS_KEYS), Setting::LIST_DEFAULTS);

        foreach (array_keys(self::ROW_LISTS) as $list) {
            $this->rows[$list] = Setting::list($list);
        }

        $this->heroWords = implode("\n", Setting::list('hero_words'));
        $this->technologies = implode("\n", Setting::list('technologies'));
        $this->speedScores = Setting::list('speed_scores');
    }

    /**
     * Add an empty row to a list.
     */
    public function addRow(string $list): void
    {
        abort_unless(isset(self::ROW_LISTS[$list]), 404);

        if (count($this->rows[$list]) < self::ROW_LISTS[$list]['max']) {
            $this->rows[$list][] = self::ROW_LISTS[$list]['template'];
        }
    }

    /**
     * Fill the speed card with real Google PageSpeed results for the website on the card.
     */
    public function fetchSpeedScores(): void
    {
        $domain = trim($this->content['speed_card_url'] ?? '');

        if ($domain === '' || str_contains($domain, 'example.')) {
            $this->addError('content.speed_card_url', __('Type the domain of a website you built first, e.g. briefmediablog.com.'));

            return;
        }

        $url = preg_match('#^https?://#i', $domain) ? $domain : 'https://'.$domain;

        set_time_limit(120);

        try {
            $result = PageSpeed::run($url);
        } catch (\RuntimeException $exception) {
            $this->addError('content.speed_card_url', $exception->getMessage());

            return;
        }

        $this->content['speed_card_url'] = (string) (parse_url($url, PHP_URL_HOST) ?: $domain);
        $this->speedScores = $result['scores'];
        $this->rows['stats'] = $result['stats'];

        Flux::toast(variant: 'success', text: __('Real scores loaded from Google. Press Save to show them on your website.'));
    }

    /**
     * Remove a row from a list.
     */
    public function removeRow(string $list, int $index): void
    {
        abort_unless(isset(self::ROW_LISTS[$list]), 404);

        unset($this->rows[$list][$index]);
        $this->rows[$list] = array_values($this->rows[$list]);
    }

    /**
     * Save the content.
     */
    public function save(): void
    {
        $rules = [
            'content.*' => ['nullable', 'string', 'max:400'],
            'heroWords' => ['required', 'string', 'max:300'],
            'technologies' => ['nullable', 'string', 'max:1000'],
            'speedScores' => ['array', 'size:4'],
            'speedScores.*.label' => ['required', 'string', 'max:30'],
            'speedScores.*.value' => ['required', 'integer', 'min:0', 'max:100'],
            'rows.features.*.title' => ['required', 'string', 'max:60'],
            'rows.features.*.text' => ['nullable', 'string', 'max:160'],
            'rows.process_steps.*.title' => ['required', 'string', 'max:60'],
            'rows.process_steps.*.text' => ['nullable', 'string', 'max:200'],
            'rows.process_steps.*.note' => ['nullable', 'string', 'max:60'],
            'rows.why_points.*.title' => ['required', 'string', 'max:60'],
            'rows.why_points.*.text' => ['nullable', 'string', 'max:200'],
            'rows.stats.*.value' => ['required', 'string', 'max:20'],
            'rows.stats.*.label' => ['required', 'string', 'max:60'],
        ];

        foreach (['hero_title', 'services_title', 'process_title', 'why_title', 'contact_title', 'form_title'] as $required) {
            $rules["content.{$required}"] = ['required', 'string', 'max:200'];
        }

        $this->validate($rules, [
            'content.*.required' => 'This heading is needed.',
            'rows.*.*.title.required' => 'Give this item a title, or remove it.',
            'speedScores.*.value.integer' => 'Use a number from 0 to 100.',
        ]);

        Setting::put([
            ...array_diff_key(array_intersect_key($this->content, Setting::DEFAULTS), array_flip(Setting::BUSINESS_KEYS)),
            ...array_map(fn (array $rows): array => array_map(fn (array $row): array => array_map('trim', $row), $rows), $this->rows),
            'hero_words' => $this->lines($this->heroWords),
            'technologies' => $this->lines($this->technologies),
            'speed_scores' => array_map(fn (array $score): array => ['label' => trim($score['label']), 'value' => (string) (int) $score['value']], $this->speedScores),
        ]);

        Flux::toast(variant: 'success', text: __('Website content saved.'));
    }

    /**
     * Split text into trimmed, non-empty lines.
     *
     * @return list<string>
     */
    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Website content') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Change the words on your home page. Services, works, testimonials and questions have their own pages.') }}</flux:text>
        </div>
        <flux:button icon="arrow-top-right-on-square" :href="route('home')" target="_blank">{{ __('View home page') }}</flux:button>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Top of the page') }}</flux:heading>
            <flux:input wire:model="content.hero_badge" :label="__('Small label above the heading')" />
            <flux:input wire:model="content.hero_title" :label="__('Main heading')" required />
            <flux:textarea wire:model="heroWords" :label="__('Changing words at the end of the heading')" :description="__('One per line. They take turns, e.g. success, sales, customers.')" rows="4" />
            <flux:textarea wire:model="content.hero_text" :label="__('Text under the heading')" rows="2" />
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="content.hero_primary_button" :label="__('Main button')" />
                <flux:input wire:model="content.hero_secondary_button" :label="__('Second button')" />
            </div>
            <flux:input wire:model="content.hero_note" :label="__('Small note under the buttons')" />
        </flux:card>

        <flux:card class="grid gap-5">
            <div>
                <flux:heading size="lg">{{ __('Speed report card') }}</flux:heading>
                <flux:text class="mt-1">{{ __('The Google PageSpeed card beside the heading. Type the domain of a website you built, then get its real scores from Google.') }}</flux:text>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="content.speed_card_url" :label="__('Website shown on the card')" placeholder="briefmediablog.com" />
                <flux:input wire:model="content.speed_card_footer" :label="__('Line at the bottom')" />
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <flux:button icon="bolt" wire:click="fetchSpeedScores" wire:loading.attr="disabled" wire:target="fetchSpeedScores" data-test="fetch-speed">
                    <span wire:loading.remove wire:target="fetchSpeedScores">{{ __('Get my real scores from Google') }}</span>
                    <span wire:loading wire:target="fetchSpeedScores">{{ __('Testing the website… this takes up to a minute') }}</span>
                </flux:button>
                <flux:text class="text-sm">{{ __('Fills in the scores and numbers below. Then press Save.') }}</flux:text>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($speedScores as $index => $score)
                    <div class="grid gap-2" wire:key="score-{{ $index }}">
                        <flux:input wire:model="speedScores.{{ $index }}.label" size="sm" :aria-label="__('Score name')" />
                        <flux:input wire:model="speedScores.{{ $index }}.value" type="number" min="0" max="100" size="sm" :aria-label="__('Score')" />
                    </div>
                @endforeach
            </div>
            <flux:text>{{ __('Three numbers under the scores') }}</flux:text>
            @foreach ($rows['stats'] as $index => $row)
                <div class="flex items-start gap-2" wire:key="stats-{{ $index }}">
                    <flux:input wire:model="rows.stats.{{ $index }}.value" :placeholder="__('e.g. 0.7 s')" class="max-w-32" :aria-label="__('Number')" />
                    <flux:input wire:model="rows.stats.{{ $index }}.label" :placeholder="__('e.g. page opens in Nigeria')" :aria-label="__('What it means')" />
                    <flux:button icon="x-mark" variant="ghost" wire:click="removeRow('stats', {{ $index }})" :aria-label="__('Remove')" />
                </div>
            @endforeach
            @if (count($rows['stats']) < 3)
                <div><flux:button size="sm" icon="plus" wire:click="addRow('stats')">{{ __('Add a number') }}</flux:button></div>
            @endif
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Services section') }}</flux:heading>
            <flux:input wire:model="content.services_eyebrow" :label="__('Small label')" />
            <flux:input wire:model="content.services_title" :label="__('Heading')" required />
            <flux:textarea wire:model="content.services_text" :label="__('Text')" rows="2" />
            <flux:text><flux:link :href="route('admin.services')" wire:navigate>{{ __('Edit the service cards and prices →') }}</flux:link></flux:text>
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('“Every website we build is” band') }}</flux:heading>
            <flux:input wire:model="content.features_title" :label="__('Heading')" />
            @foreach ($rows['features'] as $index => $row)
                <div class="flex items-start gap-2" wire:key="features-{{ $index }}">
                    <flux:input wire:model="rows.features.{{ $index }}.title" :placeholder="__('Title')" class="max-w-48" :aria-label="__('Title')" />
                    <flux:input wire:model="rows.features.{{ $index }}.text" :placeholder="__('Short text')" :aria-label="__('Text')" />
                    <flux:button icon="x-mark" variant="ghost" wire:click="removeRow('features', {{ $index }})" :aria-label="__('Remove')" />
                </div>
            @endforeach
            <div><flux:button size="sm" icon="plus" wire:click="addRow('features')">{{ __('Add a point') }}</flux:button></div>
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Work section') }}</flux:heading>
            <flux:input wire:model="content.work_eyebrow" :label="__('Small label')" />
            <flux:input wire:model="content.work_title" :label="__('Heading')" />
            <flux:textarea wire:model="content.work_text" :label="__('Text')" rows="2" />
            <flux:text><flux:link :href="route('admin.works.index')" wire:navigate>{{ __('Add or edit your works →') }}</flux:link></flux:text>
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('How we work') }}</flux:heading>
            <flux:input wire:model="content.process_eyebrow" :label="__('Small label')" />
            <flux:input wire:model="content.process_title" :label="__('Heading')" required />
            <flux:textarea wire:model="content.process_text" :label="__('Text')" rows="2" />
            @foreach ($rows['process_steps'] as $index => $row)
                <div class="grid gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="steps-{{ $index }}">
                    <div class="flex items-center justify-between">
                        <flux:text>{{ __('Step :number', ['number' => $index + 1]) }}</flux:text>
                        <flux:button size="xs" icon="x-mark" variant="ghost" wire:click="removeRow('process_steps', {{ $index }})" :aria-label="__('Remove step')" />
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <flux:input wire:model="rows.process_steps.{{ $index }}.title" :placeholder="__('Title')" :aria-label="__('Title')" />
                        <flux:input wire:model="rows.process_steps.{{ $index }}.note" :placeholder="__('Small note, e.g. 7 to 14 days')" :aria-label="__('Note')" />
                    </div>
                    <flux:input wire:model="rows.process_steps.{{ $index }}.text" :placeholder="__('What happens')" :aria-label="__('Text')" />
                </div>
            @endforeach
            <div><flux:button size="sm" icon="plus" wire:click="addRow('process_steps')">{{ __('Add a step') }}</flux:button></div>
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Why choose us') }}</flux:heading>
            <flux:input wire:model="content.why_eyebrow" :label="__('Small label')" />
            <flux:input wire:model="content.why_title" :label="__('Heading')" required />
            @foreach ($rows['why_points'] as $index => $row)
                <div class="flex items-start gap-2" wire:key="why-{{ $index }}">
                    <flux:input wire:model="rows.why_points.{{ $index }}.title" :placeholder="__('Title')" class="max-w-56" :aria-label="__('Title')" />
                    <flux:input wire:model="rows.why_points.{{ $index }}.text" :placeholder="__('Short text')" :aria-label="__('Text')" />
                    <flux:button icon="x-mark" variant="ghost" wire:click="removeRow('why_points', {{ $index }})" :aria-label="__('Remove')" />
                </div>
            @endforeach
            <div><flux:button size="sm" icon="plus" wire:click="addRow('why_points')">{{ __('Add a reason') }}</flux:button></div>
            <flux:input wire:model="content.technologies_title" :label="__('Technologies heading')" />
            <flux:textarea wire:model="technologies" :label="__('Technologies')" :description="__('One per line. They scroll across the page under the top section.')" rows="5" />
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Testimonials, blog and questions') }}</flux:heading>
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="content.testimonials_eyebrow" :label="__('Testimonials small label')" />
                <flux:input wire:model="content.testimonials_title" :label="__('Testimonials heading')" />
                <flux:input wire:model="content.blog_eyebrow" :label="__('Blog small label')" />
                <flux:input wire:model="content.blog_title" :label="__('Blog heading on the home page')" />
                <flux:input wire:model="content.faq_eyebrow" :label="__('Questions small label')" />
                <flux:input wire:model="content.faq_title" :label="__('Questions heading')" />
            </div>
            <flux:text>
                <flux:link :href="route('admin.testimonials')" wire:navigate>{{ __('Edit testimonials →') }}</flux:link>
                <span class="mx-2">·</span>
                <flux:link :href="route('admin.faqs')" wire:navigate>{{ __('Edit questions →') }}</flux:link>
                <span class="mx-2">·</span>
                <flux:link :href="route('admin.posts.index')" wire:navigate>{{ __('Write articles →') }}</flux:link>
            </flux:text>
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Contact section and form') }}</flux:heading>
            <flux:input wire:model="content.contact_eyebrow" :label="__('Small label')" />
            <flux:input wire:model="content.contact_title" :label="__('Heading')" required />
            <flux:textarea wire:model="content.contact_text" :label="__('Text')" rows="2" />
            <flux:input wire:model="content.form_title" :label="__('Form heading')" required />
            <flux:textarea wire:model="content.form_note" :label="__('Note under the form button')" rows="2" />
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="content.cta_title" :label="__('Bottom banner heading (work pages)')" />
                <flux:input wire:model="content.cta_text" :label="__('Bottom banner text')" />
            </div>
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Google') }}</flux:heading>
            <flux:input wire:model="content.seo_title" :label="__('Home page title in Google')" :description="__('Shown after your business name.')" />
            <flux:textarea wire:model="content.seo_description" :label="__('Home page description in Google')" :description="__('About 150 characters.')" rows="2" />
        </flux:card>

        <div class="sticky bottom-4 z-10">
            <flux:button variant="primary" type="submit" class="shadow-lg">{{ __('Save website content') }}</flux:button>
        </div>
    </form>
</div>
