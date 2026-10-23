@php
    $businessName = \App\Models\Setting::get('business_name');
    $logoUrl = \App\Models\Setting::logoUrl();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-950">
        <div class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
            {{-- Brand panel --}}
            <div class="relative hidden overflow-hidden bg-[#06110b] p-12 text-white lg:flex lg:flex-col">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="absolute -left-32 -top-40 size-[520px] rounded-full bg-[#0b7a4b] opacity-60 blur-[90px]"></div>
                    <div class="absolute -bottom-48 right-0 size-[420px] rounded-full bg-[#16a765] opacity-30 blur-[90px]"></div>
                    <div class="absolute right-[-80px] top-1/3 size-[260px] rounded-full bg-[#f5b82e] opacity-20 blur-[80px]"></div>
                    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.05)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.05)_1px,transparent_1px)] bg-[size:56px_56px] [mask-image:radial-gradient(ellipse_70%_60%_at_40%_40%,#000_30%,transparent_75%)]"></div>
                </div>

                <a href="{{ route('home') }}" class="relative flex items-center gap-3 text-lg font-extrabold tracking-tight">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="" class="h-11 w-auto max-w-48 rounded-xl bg-white object-contain px-2 py-1">
                    @else
                        <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#16a765] to-[#075a37] text-sm font-extrabold">SD</span>
                    @endif
                    {{ $businessName }}
                </a>

                <div class="relative my-auto max-w-md py-12">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-emerald-200">
                        <span class="size-2 rounded-full bg-emerald-400"></span> {{ __('Owner area') }}
                    </span>
                    <h2 class="mt-6 text-5xl font-extrabold leading-[1.05] tracking-tight">
                        {{ __('Run your website') }}<br>
                        <span class="bg-gradient-to-r from-emerald-400 to-amber-400 bg-clip-text text-transparent">{{ __('from one place.') }}</span>
                    </h2>
                    <ul class="mt-8 grid gap-4 text-[15px] text-emerald-50/80">
                        @foreach ([__('Add your works with photos and a live preview'), __('Reply to quote requests on WhatsApp in one tap'), __('Change prices, text, questions and your logo any time')] as $point)
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex size-6 flex-none items-center justify-center rounded-full bg-emerald-400/15 text-emerald-300">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <a href="{{ route('home') }}" class="relative inline-flex items-center gap-2 text-sm font-semibold text-emerald-100/70 hover:text-white">← {{ __('Back to the website') }}</a>
            </div>

            {{-- Form --}}
            <div class="flex items-center justify-center px-6 py-12 sm:px-10">
                <div class="flex w-full max-w-sm flex-col gap-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 text-lg font-extrabold tracking-tight lg:hidden">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="" class="h-11 w-auto max-w-48 rounded-xl bg-white object-contain px-2 py-1 ring-1 ring-zinc-200">
                        @else
                            <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#16a765] to-[#075a37] text-sm font-extrabold text-white">SD</span>
                        @endif
                        {{ $businessName }}
                    </a>
                    {{ $slot }}
                    <a href="{{ route('home') }}" class="text-center text-sm text-zinc-500 hover:text-zinc-800 lg:hidden dark:hover:text-zinc-200">← {{ __('Back to the website') }}</a>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
