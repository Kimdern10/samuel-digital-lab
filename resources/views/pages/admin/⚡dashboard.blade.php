<?php

use App\Enums\QuoteStatus;
use App\Models\Post;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Work;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /**
     * Count the things the owner cares about at a glance.
     *
     * @return array{new_quotes: int, total_quotes: int, published_works: int, draft_works: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'new_quotes' => QuoteRequest::query()->where('status', QuoteStatus::New)->count(),
            'total_quotes' => QuoteRequest::query()->count(),
            'published_works' => Work::query()->published()->count(),
            'draft_works' => Work::query()->where('is_published', false)->count(),
        ];
    }

    /**
     * What is still worth doing before and after the website goes live.
     *
     * @return list<array{done: bool, title: string, hint: string, url: string}>
     */
    #[Computed]
    public function checklist(): array
    {
        $settings = Setting::values();
        $publishedWorks = Work::query()->published();

        return [
            [
                'done' => auth()->user()?->two_factor_confirmed_at !== null,
                'title' => __('Turn on two-step login'),
                'hint' => __('Keeps your admin safe even if someone learns your password.'),
                'url' => route('security.edit'),
            ],
            [
                'done' => filled($settings['about_photo_path']),
                'title' => __('Add your photo and story to About me'),
                'hint' => __('Clients trust a real face. A starter story is ready for you to edit.'),
                'url' => route('admin.about'),
            ],
            [
                'done' => ! str_contains($settings['speed_card_url'], 'example.'),
                'title' => __('Show real speed scores on the home page'),
                'hint' => __('Website content → Speed report card → Get my real scores from Google.'),
                'url' => route('admin.content'),
            ],
            [
                'done' => (clone $publishedWorks)->count() >= 3,
                'title' => __('Add at least 3 works'),
                'hint' => __('Clients want to see a few real projects before they call.'),
                'url' => route('admin.works.index'),
            ],
            [
                'done' => (clone $publishedWorks)->count() > 0 && (clone $publishedWorks)->whereNull('live_url')->doesntExist(),
                'title' => __('Add the domain of every work'),
                'hint' => __('Shows the website name and a live preview on each project.'),
                'url' => route('admin.works.index'),
            ],
            [
                'done' => Testimonial::query()->visible()->exists(),
                'title' => __('Add a testimonial'),
                'hint' => __('A sentence or two from a happy client builds trust fast.'),
                'url' => route('admin.testimonials'),
            ],
            [
                'done' => Post::query()->published()->exists(),
                'title' => __('Publish your first article'),
                'hint' => __('A draft about website prices is ready for you to check.'),
                'url' => route('admin.posts.index'),
            ],
            [
                'done' => collect(['facebook_url', 'instagram_url', 'x_url', 'linkedin_url', 'tiktok_url'])->contains(fn (string $key): bool => filled($settings[$key])),
                'title' => __('Add your social media links'),
                'hint' => __('Shown in the footer and to Google.'),
                'url' => route('admin.settings'),
            ],
            [
                'done' => filled($settings['google_analytics_id']),
                'title' => __('Turn on visitor statistics'),
                'hint' => __('See how many people visit and what they read.'),
                'url' => route('admin.settings'),
            ],
            [
                'done' => filled($settings['privacy_policy']),
                'title' => __('Check and save your privacy policy'),
                'hint' => __('A starter version is ready; make sure it is true for you.'),
                'url' => route('admin.settings'),
            ],
            [
                'done' => ! in_array(config('mail.default'), ['log', 'array'], true),
                'title' => __('Set up real email sending'),
                'hint' => __('Done on your hosting (MAIL_ settings in .env) so quote emails reach your inbox.'),
                'url' => route('admin.settings'),
            ],
        ];
    }

    /**
     * Open quote requests whose follow-up date has arrived.
     *
     * @return Collection<int, QuoteRequest>
     */
    #[Computed]
    public function followUps(): Collection
    {
        return QuoteRequest::query()
            ->whereNotNull('follow_up_on')
            ->whereDate('follow_up_on', '<=', today())
            ->whereIn('status', [QuoteStatus::New, QuoteStatus::Contacted])
            ->orderBy('follow_up_on')
            ->get();
    }

    /**
     * The latest quote requests.
     *
     * @return Collection<int, QuoteRequest>
     */
    #[Computed]
    public function latestQuotes(): Collection
    {
        return QuoteRequest::query()->latest()->take(5)->get();
    }
}; ?>

<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:text class="mt-1">{{ __('Here is what is happening on your website.') }}</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button icon="arrow-top-right-on-square" :href="route('home')" target="_blank">{{ __('View website') }}</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('admin.works.create')" wire:navigate>{{ __('Add a work') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['route' => 'admin.content', 'icon' => 'pencil-square', 'title' => __('Website content'), 'text' => __('Headings and text on the home page')],
            ['route' => 'admin.services', 'icon' => 'tag', 'title' => __('Services & prices'), 'text' => __('The service cards and prices')],
            ['route' => 'admin.about', 'icon' => 'user-circle', 'title' => __('About me'), 'text' => __('Your photo, story and skills')],
            ['route' => 'admin.settings', 'icon' => 'cog-6-tooth', 'title' => __('Business details'), 'text' => __('Logo, WhatsApp, email and socials')],
        ] as $shortcut)
            <a href="{{ route($shortcut['route']) }}" wire:navigate class="group flex items-start gap-3 rounded-xl border border-zinc-200 p-4 transition hover:border-[#0b7a4b] hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/60">
                <span class="flex size-10 flex-none items-center justify-center rounded-lg bg-emerald-50 text-[#0b7a4b] dark:bg-emerald-900/30 dark:text-emerald-400"><flux:icon :name="$shortcut['icon']" class="size-5" /></span>
                <span>
                    <span class="block font-medium">{{ $shortcut['title'] }}</span>
                    <span class="block text-sm text-zinc-500">{{ $shortcut['text'] }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card>
            <flux:text>{{ __('New quote requests') }}</flux:text>
            <flux:heading size="xl" class="mt-2 font-mono">{{ $this->stats['new_quotes'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('All quote requests') }}</flux:text>
            <flux:heading size="xl" class="mt-2 font-mono">{{ $this->stats['total_quotes'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('Published works') }}</flux:text>
            <flux:heading size="xl" class="mt-2 font-mono">{{ $this->stats['published_works'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('Drafts') }}</flux:text>
            <flux:heading size="xl" class="mt-2 font-mono">{{ $this->stats['draft_works'] }}</flux:heading>
        </flux:card>
    </div>

    @if ($this->followUps->isNotEmpty())
        <flux:callout icon="bell-alert" color="amber" data-test="follow-ups">
            <flux:callout.heading>{{ trans_choice('{1} 1 quote request to follow up today|[2,*] :count quote requests to follow up today', $this->followUps->count()) }}</flux:callout.heading>
            <flux:callout.text>
                <ul class="mt-2 grid gap-1">
                    @foreach ($this->followUps as $quote)
                        <li wire:key="follow-up-{{ $quote->id }}">
                            <flux:link :href="route('admin.quotes.show', $quote)" wire:navigate>{{ $quote->name }}@if ($quote->business) ({{ $quote->business }})@endif</flux:link>
                            · {{ $quote->service }}
                            @if ($quote->follow_up_on->isPast() && ! $quote->follow_up_on->isToday())
                                · <span class="text-red-600 dark:text-red-400">{{ __('overdue since :date', ['date' => $quote->follow_up_on->format('j M')]) }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </flux:callout.text>
        </flux:callout>
    @endif

    @php($done = collect($this->checklist)->where('done', true)->count())
    @if ($done < count($this->checklist))
        <flux:card class="grid gap-4" data-test="launch-checklist">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <flux:heading size="lg" level="2">{{ __('Launch checklist') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Things that make your website win more clients. They tick themselves off as you do them.') }}</flux:text>
                </div>
                <flux:badge color="green">{{ __(':done of :total done', ['done' => $done, 'total' => count($this->checklist)]) }}</flux:badge>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                <div class="h-full rounded-full bg-[#0b7a4b]" style="width: {{ round($done / count($this->checklist) * 100) }}%"></div>
            </div>
            <ul class="grid gap-1">
                @foreach ($this->checklist as $item)
                    <li wire:key="check-{{ $loop->index }}">
                        <a href="{{ $item['url'] }}" wire:navigate class="flex items-start gap-3 rounded-lg p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                            @if ($item['done'])
                                <flux:icon.check-circle variant="solid" class="mt-0.5 size-5 flex-none text-[#0b7a4b] dark:text-emerald-400" />
                            @else
                                <span class="mt-0.5 size-5 flex-none rounded-full border-2 border-zinc-300 dark:border-zinc-600"></span>
                            @endif
                            <span class="min-w-0">
                                <span class="block text-sm font-medium {{ $item['done'] ? 'text-zinc-400 line-through' : '' }}">{{ $item['title'] }}</span>
                                @unless ($item['done'])
                                    <span class="block text-sm text-zinc-500">{{ $item['hint'] }}</span>
                                @endunless
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </flux:card>
    @endif

    <div>
        <div class="mb-3 flex items-center justify-between">
            <flux:heading size="lg" level="2">{{ __('Latest quote requests') }}</flux:heading>
            <flux:link :href="route('admin.quotes.index')" wire:navigate>{{ __('See all') }}</flux:link>
        </div>

        @if ($this->latestQuotes->isEmpty())
            <flux:callout icon="inbox">{{ __('No quote requests yet. They will appear here when visitors use the form on your home page.') }}</flux:callout>
        @else
            <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @foreach ($this->latestQuotes as $quote)
                    <a href="{{ route('admin.quotes.show', $quote) }}" wire:navigate wire:key="quote-{{ $quote->id }}" class="flex items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ $quote->name }} @if ($quote->business)<span class="text-zinc-500">· {{ $quote->business }}</span>@endif</div>
                            <div class="truncate text-sm text-zinc-500">{{ $quote->service }} · {{ $quote->created_at->diffForHumans() }}</div>
                        </div>
                        <flux:badge size="sm" :color="$quote->status->color()">{{ $quote->status->label() }}</flux:badge>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
