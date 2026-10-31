<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ \App\Http\Responses\LoginResponse::homeFor(auth()->user()) }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @unless (auth()->user()->is_admin)
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('My account')" class="grid">
                        <flux:sidebar.item icon="home" :href="route('portal')" :current="request()->routeIs('portal', 'portal.projects.*')" wire:navigate.hover>
                            {{ __('My website') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="banknotes" :href="route('portal.invoices')" :current="request()->routeIs('portal.invoices*')" :badge="auth()->user()->invoices()->where('status', \App\Enums\InvoiceStatus::Unpaid)->count() ?: null" wire:navigate.hover>
                            {{ __('Invoices') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="user" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit')" wire:navigate.hover>
                            {{ __('Account') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @else
            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Clients')" class="grid">
                    <flux:sidebar.item icon="users" :href="route('admin.clients')" :current="request()->routeIs('admin.clients')" wire:navigate.hover>
                        {{ __('Clients') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="rectangle-stack" :href="route('admin.projects.index')" :current="request()->routeIs('admin.projects.*')" wire:navigate.hover>
                        {{ __('Projects') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="banknotes" :href="route('admin.invoices.index')" :current="request()->routeIs('admin.invoices.*')" :badge="\App\Models\Invoice::query()->where('status', \App\Enums\InvoiceStatus::Unpaid)->count() ?: null" wire:navigate.hover>
                        {{ __('Invoices') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Website')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate.hover>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="inbox" :href="route('admin.quotes.index')" :current="request()->routeIs('admin.quotes.*')" :badge="\App\Models\QuoteRequest::query()->where('status', \App\Enums\QuoteStatus::New)->count() ?: null" wire:navigate.hover>
                        {{ __('Quote requests') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="phone" :href="route('admin.consultations')" :current="request()->routeIs('admin.consultations')" :badge="\App\Models\Consultation::query()->booked()->where('starts_at', '>=', now())->count() ?: null" wire:navigate.hover>
                        {{ __('Booked calls') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="photo" :href="route('admin.works.index')" :current="request()->routeIs('admin.works.*')" wire:navigate.hover>
                        {{ __('Our work') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="envelope" :href="route('admin.subscribers')" :current="request()->routeIs('admin.subscribers')" wire:navigate.hover>
                        {{ __('Newsletter') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="newspaper" :href="route('admin.posts.index')" :current="request()->routeIs('admin.posts.*')" wire:navigate.hover>
                        {{ __('Blog') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Home page')" class="grid">
                    <flux:sidebar.item icon="pencil-square" :href="route('admin.content')" :current="request()->routeIs('admin.content')" wire:navigate.hover>
                        {{ __('Website content') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-circle" :href="route('admin.about')" :current="request()->routeIs('admin.about')" wire:navigate.hover>
                        {{ __('About me') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('admin.services')" :current="request()->routeIs('admin.services')" wire:navigate.hover>
                        {{ __('Services & prices') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calculator" :href="route('admin.calculator')" :current="request()->routeIs('admin.calculator')" wire:navigate.hover>
                        {{ __('Cost calculator') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chat-bubble-bottom-center-text" :href="route('admin.testimonials')" :current="request()->routeIs('admin.testimonials')" wire:navigate.hover>
                        {{ __('Testimonials') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="question-mark-circle" :href="route('admin.faqs')" :current="request()->routeIs('admin.faqs')" wire:navigate.hover>
                        {{ __('Questions') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')" wire:navigate.hover>
                        {{ __('Business details') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
            @endunless

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="arrow-top-right-on-square" :href="route('home')" target="_blank">
                    {{ __('View website') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate.hover>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
