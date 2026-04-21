@php
    $backUrl = \App\Support\Seo::route('games.imposter.index');
    $roomUrl = \App\Support\Seo::route('games.imposter.room', ['roomCode' => $roomCode]);
@endphp

<div class="flex flex-col gap-4"
    x-data="{
        roleVisible: true,
        playBongg() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                if (ctx.state === 'suspended') ctx.resume();
                
                const gain = ctx.createGain();
                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(1.0, ctx.currentTime + 0.05); // Vurucu başlangıç
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8); // Uzun rahatsız edici azalış
                gain.connect(ctx.destination);

                // Dissonant (uyumsuz) ve rahatsız edici bir akor yaratıyoruz
                const freqs = [350, 380, 410, 800]; 
                freqs.forEach(f => {
                    const osc = ctx.createOscillator();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(f * 2, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(f / 3, ctx.currentTime + 0.8);
                    osc.connect(gain);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.8);
                });
            } catch (e) {
                console.error('Audio failed:', e);
            }
        }
    }"
    x-on:play-bongg.window="playBongg()"
>
    @if ($roomMissing)
        <x-games.room-missing-state
            :back-url="$backUrl"
            :back-label="__('imposter.back')"
            :room-code-label="__('imposter.roomCode')"
            :title="__('imposter.roomNotFound')"
            :description="__('imposter.roomNotFoundDesc')"
            accent="cyan"
        />
    @else
        <button
            type="button"
            wire:click="leave"
            class="touch-manipulation inline-flex w-fit items-center justify-center gap-2 rounded-2xl border border-zinc-200 bg-white/75 px-5 py-2.5 text-sm font-semibold text-zinc-900 shadow-sm backdrop-blur transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:border-white/10 dark:bg-zinc-950/40 dark:text-white dark:hover:bg-zinc-950/55 dark:focus-visible:ring-cyan-300/30"
        >
            <span aria-hidden="true" class="text-zinc-500 dark:text-zinc-400">&larr;</span>
            {{ $isJoined ? __('imposter.leave') : __('imposter.back') }}
        </button>
        <div
            class="relative overflow-hidden rounded-[2.5rem] border border-zinc-200 bg-white/80 shadow-sm backdrop-blur dark:border-white/10 dark:bg-zinc-950/80 imposter-arena"
            data-imposter-arena
            @if ($status === \App\Enums\ImposterRoomStatus::Lobby)
                wire:poll.2s="refreshRoom"
            @else
                wire:poll.5s="refreshRoom"
            @endif
        >
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute inset-0 imposter-stage opacity-[0.40]"></div>
                <div class="absolute inset-0 window-scanline"></div>
                <div class="absolute inset-0 cyber-noise"></div>
                <div class="absolute inset-0 imposter-vignette"></div>
            </div>

            {{-- Toolbar --}}
            <div class="relative border-b border-zinc-200/50 bg-white/40 px-4 py-3 backdrop-blur sm:px-6 z-20 dark:border-white/5 dark:bg-zinc-950/60">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            wire:click="openPlayers"
                            class="touch-manipulation inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white/75 px-4 py-2 text-xs font-semibold text-zinc-700 backdrop-blur transition hover:bg-white hover:border-cyan-300 hover:text-zinc-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:border-white/10 dark:bg-zinc-950/40 dark:text-zinc-300 dark:hover:bg-zinc-950/60 dark:hover:border-cyan-400/30 dark:hover:text-white dark:focus-visible:ring-cyan-300/30"
                            title="{{ __('imposter.players') }}"
                        >
                            <span class="inline-flex h-2 w-2 rounded-full bg-cyan-400 shadow-[0_0_0_4px_rgba(34,211,238,0.18)]"></span>
                            @if ($status === \App\Enums\ImposterRoomStatus::Lobby)
                                {{ __('imposter.roomCode') }}
                                <span class="font-mono tracking-widest text-zinc-950 dark:text-white">{{ $roomCode }}</span>
                            @else
                                {{ __('imposter.players') }}
                            @endif
                            <svg class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </button>

                        @if ($status === \App\Enums\ImposterRoomStatus::Lobby)
                            <button
                                type="button"
                                class="touch-manipulation inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white/75 px-3 py-2 text-xs font-semibold text-zinc-900 shadow-sm backdrop-blur transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:border-white/10 dark:bg-zinc-950/40 dark:text-white dark:hover:bg-zinc-950/55 dark:focus-visible:ring-cyan-300/30"
                                data-copy-text="{{ $roomCode }}"
                                data-copy-label="{{ __('imposter.copyCode') }}"
                                data-copy-label-success="{{ __('portfolio.ui.copied') }}"
                            >
                                {{ __('imposter.copyCode') }}
                            </button>

                            <button
                                type="button"
                                class="touch-manipulation inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white/75 px-3 py-2 text-xs font-semibold text-zinc-900 shadow-sm backdrop-blur transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:border-white/10 dark:bg-zinc-950/40 dark:text-white dark:hover:bg-zinc-950/55 dark:focus-visible:ring-cyan-300/30"
                                data-copy-text="{{ $roomUrl }}"
                                data-copy-label="{{ __('imposter.copyLink') }}"
                                data-copy-label-success="{{ __('portfolio.ui.copied') }}"
                            >
                                {{ __('imposter.copyLink') }}
                            </button>

                            <div x-data="{ openQr: false }" class="relative z-50">
                                <button
                                    type="button"
                                    @click="openQr = !openQr"
                                    class="touch-manipulation inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white/75 p-2 text-zinc-600 shadow-sm backdrop-blur transition hover:bg-white hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:border-white/10 dark:bg-zinc-950/40 dark:text-zinc-400 dark:hover:bg-zinc-950/55 dark:hover:text-white dark:focus-visible:ring-cyan-300/30"
                                    title="QR Kod"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                </button>
                                
                                <div
                                    x-show="openQr"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    @click.away="openQr = false"
                                    x-cloak
                                    class="absolute left-0 mt-2 z-50 w-max rounded-2xl border border-zinc-200/80 bg-white/95 p-3 shadow-xl backdrop-blur-xl dark:border-white/10 dark:bg-zinc-900/90"
                                >
                                    <div class="relative">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($roomUrl) }}&color=09090b" alt="QR Code" class="h-48 w-48 rounded-xl bg-white dark:hidden">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($roomUrl) }}&color=fafafa&bgcolor=09090b" alt="QR Code" class="hidden h-48 w-48 rounded-xl bg-zinc-950 dark:block">
                                        
                                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <div class="rounded-lg bg-zinc-950 p-1.5 text-white shadow-sm dark:bg-white dark:text-zinc-950">
                                                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 10h.01"/><path d="M15 10h.01"/><path d="M12 2a8 8 0 0 0-8 8v12l3-3 2.5 2.5L12 19l2.5 2.5L17 19l3 3V10a8 8 0 0 0-8-8z"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($roomPassword !== null)
                                <div x-data="{ showPassword: false }" class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white/75 pl-3 pr-2 py-1.5 text-xs font-semibold text-zinc-600 shadow-sm backdrop-blur dark:border-white/10 dark:bg-zinc-950/40 dark:text-zinc-300">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    <span class="font-mono tracking-wide" x-text="showPassword ? '{{ $roomPassword }}' : '••••••••'"></span>
                                    <button type="button" x-on:click="showPassword = !showPassword" class="ml-1 p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 focus:outline-none" title="Şifreyi Göster/Gizle">
                                        <svg x-show="!showPassword" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <svg x-cloak x-show="showPassword" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($isHost && $status === \App\Enums\ImposterRoomStatus::Lobby)

                            <button
                                type="button"
                                wire:click="start"
                                class="touch-manipulation inline-flex items-center justify-center gap-2 rounded-2xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm ring-1 ring-zinc-950/10 transition hover:bg-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:bg-white dark:text-zinc-950 dark:ring-white/10 dark:hover:bg-zinc-100 dark:focus-visible:ring-cyan-300/30"
                            >
                                {{ __('imposter.start') }}
                                <span aria-hidden="true" class="text-white/70 dark:text-zinc-500">&rarr;</span>
                            </button>

                        @elseif ($isHost && $status === \App\Enums\ImposterRoomStatus::Started)
                            <button
                                type="button"
                                wire:click="$set('confirmImposterGuessed', true)"
                                class="touch-manipulation inline-flex items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-2.5 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60 dark:border-rose-400/20 dark:bg-rose-950/30 dark:text-rose-300 dark:hover:bg-rose-900/40 dark:focus-visible:ring-rose-300/30"
                            >
                                {{ __('imposter.host.imposterGuessed') }}
                            </button>
                            <button
                                type="button"
                                wire:click="startVoting"
                                class="touch-manipulation inline-flex items-center justify-center gap-2 rounded-2xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm ring-1 ring-zinc-950/10 transition hover:bg-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:bg-white dark:text-zinc-950 dark:ring-white/10 dark:hover:bg-zinc-100 dark:focus-visible:ring-cyan-300/30"
                            >
                                {{ __('imposter.voting.start') }}
                                <span aria-hidden="true" class="text-white/70 dark:text-zinc-500">&rarr;</span>
                            </button>
                        @elseif ($isHost && $status === \App\Enums\ImposterRoomStatus::Voting)
                            <button
                                type="button"
                                wire:click="$set('confirmImposterGuessed', true)"
                                class="touch-manipulation inline-flex items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-2.5 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60 dark:border-rose-400/20 dark:bg-rose-950/30 dark:text-rose-300 dark:hover:bg-rose-900/40 dark:focus-visible:ring-rose-300/30"
                            >
                                {{ __('imposter.host.imposterGuessed') }}
                            </button>
                            <button
                                type="button"
                                wire:click="revealVotes"
                                class="touch-manipulation inline-flex items-center justify-center gap-2 rounded-2xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm ring-1 ring-zinc-950/10 transition hover:bg-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:bg-white dark:text-zinc-950 dark:ring-white/10 dark:hover:bg-zinc-100 dark:focus-visible:ring-cyan-300/30"
                            >
                                {{ __('imposter.voting.reveal') }}
                                <span aria-hidden="true" class="text-white/70 dark:text-zinc-500">&rarr;</span>
                            </button>
                        @elseif ($isHost && $status === \App\Enums\ImposterRoomStatus::Results)
                            <button
                                type="button"
                                wire:click="newRound"
                                class="touch-manipulation inline-flex items-center justify-center gap-2 rounded-2xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm ring-1 ring-zinc-950/10 transition hover:bg-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:bg-white dark:text-zinc-950 dark:ring-white/10 dark:hover:bg-zinc-100 dark:focus-visible:ring-cyan-300/30"
                            >
                                {{ __('imposter.voting.newRound') }}
                                <span aria-hidden="true" class="text-white/70 dark:text-zinc-500">&rarr;</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <div class="relative p-4 sm:p-6 lg:p-8">
                @if ($error)
                    <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50/80 px-4 py-3 text-sm font-semibold text-rose-700 backdrop-blur dark:border-rose-400/20 dark:bg-rose-950/30 dark:text-rose-200">
                        {{ $error }}
                    </div>
                @endif

                @if (in_array($status, [\App\Enums\ImposterRoomStatus::Started, \App\Enums\ImposterRoomStatus::Voting, \App\Enums\ImposterRoomStatus::Results], true) && $isJoined)
                    {{-- Role card (inline, toggleable) — only for joined players --}}
                    <div
                        class="mb-6 overflow-hidden rounded-3xl border shadow-xl backdrop-blur {{ $myRole === 'imposter' ? 'border-fuchsia-200/60 bg-white/92 shadow-fuchsia-950/10 dark:border-fuchsia-400/15 dark:bg-zinc-950/88 dark:shadow-fuchsia-950/20' : ($myRole === 'crew' ? 'border-sky-200/50 bg-white/92 shadow-sky-950/10 dark:border-sky-400/15 dark:bg-zinc-950/88 dark:shadow-sky-950/20' : 'border-zinc-200 bg-white/92 shadow-zinc-950/20 dark:border-white/10 dark:bg-zinc-950/88 dark:shadow-black/35') }}"
                        role="region"
                        aria-label="{{ __('imposter.yourRole') }}"
                    >
                        <button
                            type="button"
                            x-on:click.stop.prevent="roleVisible = !roleVisible"
                            class="touch-manipulation flex w-full cursor-pointer items-start justify-between gap-4 border-b px-5 py-4 text-left backdrop-blur transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 focus-visible:ring-inset dark:focus-visible:ring-cyan-300/40 {{ $myRole === 'imposter' ? 'border-fuchsia-200/40 bg-fuchsia-50/40 dark:border-fuchsia-400/10 dark:bg-fuchsia-950/15' : ($myRole === 'crew' ? 'border-sky-200/40 bg-sky-50/40 dark:border-sky-400/10 dark:bg-sky-950/15' : 'border-zinc-200/70 bg-white/70 dark:border-white/10 dark:bg-zinc-950/55') }}"
                            :aria-expanded="roleVisible ? 'true' : 'false'"
                            aria-controls="role-card-body"
                            id="role-card-toggle"
                        >
                            <div>
                                <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('imposter.yourRole') }}</p>
                                <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">{{ __('imposter.privateRole') }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="hidden text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 sm:inline">
                                    <span x-show="roleVisible">{{ __('imposter.roleHide') }}</span>
                                    <span x-show="!roleVisible" x-cloak>{{ __('imposter.roleShow') }}</span>
                                </span>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-zinc-200/80 bg-white/80 text-zinc-600 transition dark:border-white/10 dark:bg-zinc-800/60 dark:text-zinc-400" aria-hidden="true">
                                    <svg x-show="roleVisible" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    <svg x-show="!roleVisible" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                                </span>
                            </div>
                        </button>

                        <div id="role-card-body" x-show="roleVisible" role="region" aria-labelledby="role-card-toggle">
                            <div class="border-t px-5 py-4 {{ $myRole === 'imposter' ? 'border-fuchsia-200/30 dark:border-fuchsia-400/10' : ($myRole === 'crew' ? 'border-sky-200/30 dark:border-sky-400/10' : 'border-zinc-200/50 dark:border-white/10') }}">
                                @if ($myRole === 'imposter')
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                        <div class="min-w-0 shrink-0 space-y-1">
                                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500/80 dark:text-zinc-500">{{ __('imposter.youAre') }}</p>
                                            <p class="cyber-title text-2xl font-black tracking-tighter text-fuchsia-600 dark:text-fuchsia-400 sm:text-3xl">{{ __('imposter.roleImposter') }}</p>
                                        </div>
                                        <div class="inline-flex w-fit shrink-0 items-center gap-2 rounded-2xl border border-fuchsia-200/60 bg-fuchsia-100/50 px-4 py-2.5 text-sm font-bold text-fuchsia-700 shadow-sm backdrop-blur dark:border-fuchsia-400/20 dark:bg-fuchsia-950/30 dark:text-fuchsia-300">
                                            <span class="text-lg">🤫</span>
                                            {{ __('imposter.noWord') }}
                                        </div>
                                    </div>
                                @elseif ($myRole === 'crew')
                                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                        <div class="min-w-0 shrink-0 space-y-1 text-left">
                                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500/80 dark:text-zinc-500">{{ __('imposter.youAre') }}</p>
                                            <p class="cyber-title text-2xl font-black leading-tight tracking-tighter text-sky-600 dark:text-sky-400 sm:text-3xl">{{ __('imposter.roleCrew') }}</p>
                                        </div>
                                        <div class="relative w-full max-w-full shrink-0 overflow-hidden rounded-[1.75rem] border border-sky-200/60 bg-sky-50/50 p-4 shadow-sm backdrop-blur transition dark:border-sky-400/10 dark:bg-sky-950/10 sm:w-fit sm:max-w-[min(100%,18rem)] sm:rounded-[2rem] sm:p-5">
                                            <div class="relative z-10">
                                                <p class="font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-sky-600/70 dark:text-sky-400/60">{{ __('imposter.yourWord') }}</p>
                                                <p class="cyber-title mt-1.5 break-words text-xl font-black tracking-tight text-zinc-950 dark:text-white sm:text-2xl">{{ $myWord ?: '...' }}</p>
                                            </div>
                                            {{-- Subtle accent --}}
                                            <div class="pointer-events-none absolute -right-4 -top-4 h-16 w-16 rounded-full bg-sky-500/5 blur-2xl dark:bg-sky-400/10"></div>
                                        </div>
                                    </div>
                                    <div class="mt-5 flex items-center gap-2.5 rounded-2xl border border-zinc-200/50 bg-zinc-50/50 px-4 py-3 text-sm font-medium text-zinc-600 dark:border-white/5 dark:bg-white/5 dark:text-zinc-300">
                                        <span class="text-base">💡</span>
                                        <p>{{ $myWord ? __('imposter.describeWithoutSaying') : __('imposter.waitingForWord') }}</p>
                                    </div>
                                @else
                                    <div class="flex items-center gap-3 py-2 text-zinc-500 dark:text-zinc-400">
                                        <div class="h-2 w-2 animate-pulse rounded-full bg-zinc-400"></div>
                                        <p class="text-sm font-medium uppercase tracking-wide">{{ __('imposter.waitingForRole') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif


                @if ($status === \App\Enums\ImposterRoomStatus::Lobby)
                    <div class="grid gap-4 lg:grid-cols-12">
                        <div class="lg:col-span-7">
                            {{-- Rules --}}
                            <div class="rounded-3xl border border-zinc-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/[0.08] dark:bg-zinc-900/60">
                                <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('imposter.howToPlay') }}</p>
                                <div class="mt-4 space-y-3">
                                    @foreach ([__('imposter.rulesLine1'), __('imposter.rulesLine2'), __('imposter.rulesLine3')] as $i => $rule)
                                        <div class="flex gap-3 text-sm text-zinc-700 dark:text-zinc-200">
                                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-zinc-200/80 bg-white/80 font-mono text-[11px] font-semibold text-zinc-500 dark:border-white/10 dark:bg-zinc-800/60 dark:text-zinc-400">{{ $i + 1 }}</span>
                                            <p class="pt-0.5">{{ $rule }}</p>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-4 border-t border-zinc-200/40 pt-3 text-xs text-zinc-500 dark:border-white/[0.06] dark:text-zinc-400">{{ __('imposter.rulesTip') }}</p>
                            </div>

                            {{-- Lobby status --}}
                            <div class="mt-4 rounded-3xl border border-zinc-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/[0.08] dark:bg-zinc-900/60">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_0_4px_rgba(52,211,153,0.16)]"></span>
                                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('imposter.lobby') }}</p>
                                    @if ($isJoined)
                                        <button wire:click="bongg" type="button" class="ml-auto flex shrink-0 items-center gap-1.5 rounded-xl border border-cyan-200/50 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100 focus:outline-none dark:border-cyan-400/20 dark:bg-cyan-950/40 dark:text-cyan-300 dark:hover:bg-cyan-900/60" title="Oyuncuları Dürtt!">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                            Bongg!
                                        </button>
                                    @endif
                                </div>
                                <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                                    {{ $isJoined ? __('imposter.waitingForStartDesc') : __('imposter.joinThisRoomDesc') }}
                                </p>

                                @if (count($players) > 0)
                                    <div class="mt-4 space-y-1">
                                        @foreach ($players as $player)
                                            @php $playerBg = \App\View\PlayerColors::all($player['color'])['bg']; @endphp
                                            <div class="flex min-w-0 items-center gap-2 rounded-2xl px-2 py-1 {{ $player['isMe'] ? $playerBg : 'bg-zinc-50 dark:bg-white/5' }}">
                                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg {{ \App\View\PlayerColors::avatar($player['color']) }}">{{ $player['emoji'] }}</span>
                                                <span class="min-w-0 truncate text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $player['name'] }}</span>
                                                @if ($player['isHost'])
                                                    <span class="ml-auto shrink-0 h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                                @elseif ($player['isMe'])
                                                    <span class="ml-auto shrink-0 rounded-full bg-cyan-400/15 px-1.5 py-0.5 font-mono text-[9px] font-semibold text-cyan-600 dark:text-cyan-300">Ben</span>
                                                @elseif ($isHost && $status === \App\Enums\ImposterRoomStatus::Lobby)
                                                    <button
                                                        type="button"
                                                        wire:click="openKickConfirm('{{ $player['id'] }}', '{{ addslashes($player['name']) }}')"
                                                        class="ml-auto flex h-7 w-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-950/30"
                                                        title="{{ __('imposter.kick') }}"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="lg:col-span-5">
                            <div class="rounded-3xl border border-zinc-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/[0.08] dark:bg-zinc-900/60">
                                @if (! $isJoined)
                                    <h3 class="cyber-title text-base font-bold text-zinc-950 dark:text-white">{{ __('imposter.joinThisRoom') }}</h3>
                                    <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ __('imposter.joinThisRoomDesc') }}</p>

                                    <div class="mt-6 space-y-3">
                                        <div class="-mt-4">
                                            <livewire:games.identity />
                                        </div>

                                        @if ($isPasswordProtected)
                                            <label class="block">
                                                <span class="mb-1.5 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ __('imposter.password') }}</span>
                                                <input
                                                    wire:model.live="joinPassword"
                                                    type="password"
                                                    autocomplete="off"
                                                    spellcheck="false"
                                                    class="w-full rounded-2xl border bg-white/70 px-4 py-3 text-sm font-semibold text-zinc-950 shadow-sm outline-none transition placeholder:text-zinc-400 @error('joinPassword') border-rose-500 focus:border-rose-500 focus-visible:ring-rose-400/60 dark:border-rose-500/50 dark:focus:border-rose-500/50 dark:focus-visible:ring-rose-300/30 @else border-zinc-200 focus:border-zinc-300 focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:border-white/10 dark:focus:border-white/20 dark:focus-visible:ring-cyan-300/30 @enderror dark:bg-zinc-950/35 dark:text-white dark:placeholder:text-zinc-500"
                                                    placeholder="{{ __('imposter.placeholder.password') }}"
                                                />
                                                @error('joinPassword')
                                                    <span class="mt-1.5 block text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</span>
                                                @enderror
                                            </label>
                                        @endif

                                        @if ($error)
                                            <div class="rounded-2xl border border-rose-200 bg-rose-50/80 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-400/20 dark:bg-rose-950/30 dark:text-rose-200">{{ $error }}</div>
                                        @endif

                                        <button
                                            type="button"
                                            wire:click="join"
                                            @disabled(!$hasIdentity)
                                            class="touch-manipulation inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-zinc-950 px-6 py-3 text-sm font-semibold text-white shadow-sm ring-1 ring-zinc-950/10 transition hover:bg-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-zinc-950 dark:ring-white/10 dark:hover:bg-zinc-100 dark:focus-visible:ring-cyan-300/30"
                                        >
                                            {{ __('imposter.join') }}
                                            <span aria-hidden="true" class="text-white/70 dark:text-zinc-500">&rarr;</span>
                                        </button>
                                    </div>
                                @else
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-2.5 w-2.5 animate-pulse rounded-full bg-cyan-400"></span>
                                        <h3 class="cyber-title text-base font-bold text-zinc-950 dark:text-white">{{ __('imposter.waitingForStart') }}</h3>
                                    </div>
                                    <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ __('imposter.waitingForStartDesc') }}</p>
                                @endif
                            </div>
                            @if ($isJoined)
                                <livewire:games.identity />
                            @endif
                        </div>
                    </div>
                @else
                    <div>
                        @if (! $isJoined && in_array($status, [\App\Enums\ImposterRoomStatus::Started, \App\Enums\ImposterRoomStatus::Voting, \App\Enums\ImposterRoomStatus::Results], true))
                            {{-- Visitor arrived after game started --}}
                            <div class="rounded-3xl border border-amber-200/60 bg-amber-50/60 p-6 shadow-sm backdrop-blur-md dark:border-amber-400/20 dark:bg-amber-950/25">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-amber-200/80 bg-amber-100/80 dark:border-amber-400/20 dark:bg-amber-900/30">
                                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <div>
                                        <h3 class="cyber-title text-base font-bold text-zinc-950 dark:text-white">{{ __('imposter.gameAlreadyStarted') }}</h3>
                                        <p class="mt-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ __('imposter.gameAlreadyStartedDesc') }}</p>
                                    </div>
                                </div>
                            </div>
                        @elseif ($status === \App\Enums\ImposterRoomStatus::Started && $isJoined)
                            <div class="rounded-3xl border border-zinc-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/[0.08] dark:bg-zinc-900/60">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-2.5 w-2.5 animate-pulse rounded-full bg-cyan-400"></span>
                                    <p class="cyber-title text-base font-bold text-zinc-950 dark:text-white">{{ __('imposter.gameStarted') }}</p>
                                </div>
                                <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ __('imposter.gameStartedDesc') }}</p>

                                @if ($starterName && count($players) !== 3)
                                    <div class="mt-5 rounded-3xl border border-cyan-200/50 bg-gradient-to-br from-cyan-50/60 to-white/70 p-6 shadow-sm backdrop-blur-md dark:border-cyan-400/15 dark:from-cyan-950/30 dark:to-zinc-900/60">
                                        <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-600/70 dark:text-cyan-300/60">{{ __('imposter.starterPlayer') }}</p>
                                        <p class="cyber-title mt-2 text-xl font-bold text-zinc-950 dark:text-white">{{ $starterName }}</p>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{!! __('imposter.starterPlayerDesc', ['name' => '<span class="font-semibold text-zinc-900 dark:text-white">' . e($starterName) . '</span>']) !!}</p>
                                    </div>
                                @endif
                            </div>
                        @elseif ($status === \App\Enums\ImposterRoomStatus::Voting && $isJoined)
                            <div class="rounded-3xl border border-zinc-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/8 dark:bg-zinc-900/60">
                                {{-- Turn Banner --}}
                                <div class="mb-5">
                                    @if ($currentVoterId === $myPlayerId)
                                        <div class="rounded-2xl border border-cyan-200/50 bg-cyan-50/60 p-4 shadow-sm dark:border-cyan-400/20 dark:bg-cyan-950/30">
                                            <p class="flex items-center gap-2 text-sm font-semibold text-cyan-700 dark:text-cyan-300">
                                                <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-cyan-500"></span>
                                                {{ __('imposter.voting.yourTurn') }}
                                            </p>
                                        </div>
                                    @elseif (count($voterQueueNames) > 0)
                                        <div class="flex items-center justify-between rounded-2xl border border-zinc-200/50 bg-zinc-50/60 p-4 shadow-sm dark:border-white/5 dark:bg-zinc-800/30">
                                            <div>
                                                <p class="mb-1 font-mono text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Seçim Sırası</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                    {!! implode(' <span class="mx-1 text-zinc-400 dark:text-zinc-600">&rarr;</span> ', array_map('e', $voterQueueNames)) !!}
                                                </p>
                                            </div>
                                            <button wire:click="bongg" type="button" class="ml-4 flex shrink-0 items-center gap-1.5 rounded-xl border border-cyan-200/50 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100 focus:outline-none dark:border-cyan-400/20 dark:bg-cyan-950/40 dark:text-cyan-300 dark:hover:bg-cyan-900/60" title="Oyuncuyu Dürtt!">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                                Bongg!
                                            </button>
                                        </div>
                                    @else
                                        <div class="rounded-2xl border border-zinc-200/50 bg-zinc-50/60 p-4 shadow-sm dark:border-white/5 dark:bg-zinc-800/30">
                                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('imposter.voting.allVoted') }}</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="cyber-title text-base font-bold text-zinc-950 dark:text-white">{{ __('imposter.voting.title') }}</p>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('imposter.voting.lead') }}</p>
                                    </div>
                                    @if ($myVote)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                            {{ __('imposter.voting.voted') }}
                                        </span>
                                    @endif
                                </div>

                                @if ($myPlayerId !== null)
                                    @php
                                        $votesReceived = (int) ($voteCounts[$myPlayerId] ?? 0);
                                        $votersOfMe = $voteMap[$myPlayerId] ?? [];
                                    @endphp
                                    @if ($votesReceived > 0)
                                        <div class="mt-4 rounded-2xl border border-cyan-200/50 bg-cyan-50/50 px-4 py-3 dark:border-cyan-400/20 dark:bg-cyan-950/25">
                                            <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-600/80 dark:text-cyan-300/80">{{ __('imposter.voting.votesReceived') }}</p>
                                            <p class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ __('imposter.voting.votesReceivedCount', ['count' => $votesReceived]) }}</p>
                                            @if (count($votersOfMe) > 0)
                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    @foreach ($votersOfMe as $voterName)
                                                        <span class="inline-flex items-center gap-1 rounded-full border border-cyan-200/60 bg-white/70 px-2.5 py-0.5 text-[11px] font-semibold text-cyan-700 dark:border-cyan-400/20 dark:bg-cyan-500/10 dark:text-cyan-200">{{ $voterName }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endif

                                <div class="mt-5 space-y-2">
                                    @foreach ($players as $player)
@if (! $player['isMe'])
                                            @php 
                                                $playerVotes = (int) ($voteCounts[$player['id']] ?? 0); 
                                                $canVote = ($currentVoterId === $myPlayerId);
                                            @endphp
                                            <button
                                                type="button"
                                                wire:click="vote('{{ $player['id'] }}')"
                                                @if(!$canVote) disabled @endif
                                                class="touch-manipulation group/vote flex w-full items-center justify-between gap-4 rounded-2xl border px-4 py-3.5 text-left text-sm font-semibold shadow-sm backdrop-blur transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:focus-visible:ring-cyan-300/30 disabled:opacity-50 disabled:cursor-not-allowed {{ $myVote === $player['id'] ? 'border-cyan-400/40 bg-cyan-50/50 text-cyan-900 ring-1 ring-cyan-400/30 dark:border-cyan-400/15 dark:bg-cyan-500/6 dark:text-cyan-100 dark:ring-cyan-300/10' : 'border-zinc-200/80 bg-white/60 text-zinc-900 hover:bg-white/80 dark:border-white/8 dark:bg-zinc-800/40 dark:text-white dark:hover:bg-zinc-800/60' }}"
                                            >
                                                <span class="flex min-w-0 items-center gap-3">
                                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg {{ \App\View\PlayerColors::avatar($player['color']) }}">{{ $player['emoji'] }}</span>
                                                    <span class="min-w-0 truncate">{{ $player['name'] }}</span>
                                                    <span class="inline-flex min-w-7 shrink-0 items-center justify-center rounded-full bg-zinc-200/80 px-2 py-0.5 font-mono text-[11px] font-semibold text-zinc-600 dark:bg-zinc-700/60 dark:text-zinc-300" aria-label="{{ __('imposter.voting.voteCountLabel', ['count' => $playerVotes]) }}">{{ $playerVotes }}</span>
                                                </span>
                                                <span class="font-mono text-[11px] font-semibold {{ $myVote === $player['id'] ? 'text-cyan-600 dark:text-cyan-300' : 'text-zinc-400 dark:text-zinc-500' }}">{{ $myVote === $player['id'] ? __('imposter.voting.selected') : __('imposter.voting.select') }}</span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($status === \App\Enums\ImposterRoomStatus::Results && $isJoined)
                            @php $maxVotes = max(1, max(array_values($voteCounts) ?: [0])); @endphp
                            <div class="space-y-4">

                                {{-- Winner Banner --}}
                                @if ($winner === 'crew')
                                    <div class="relative overflow-hidden rounded-3xl border border-emerald-200/60 bg-linear-to-br from-emerald-50/80 to-white/90 p-6 shadow-lg backdrop-blur-md dark:border-emerald-400/20 dark:from-emerald-950/40 dark:to-zinc-900/70">
                                        <div class="pointer-events-none absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle at 70% 50%, #34d399 0%, transparent 60%)"></div>
                                        <div class="relative flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
                                            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-3xl shadow-sm dark:bg-emerald-900/40">🎉</span>
                                            <div>
                                                <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-600/80 dark:text-emerald-300/70">{{ __('imposter.results.winner') }}</p>
                                                <p class="cyber-title mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ __('imposter.results.crewWins') }}</p>
                                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('imposter.results.crewWinsDesc', ['name' => $imposterName ?? '?']) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @elseif ($winner === 'imposter')
                                    <div class="relative overflow-hidden rounded-3xl border border-fuchsia-200/60 bg-linear-to-br from-fuchsia-50/80 to-white/90 p-6 shadow-lg backdrop-blur-md dark:border-fuchsia-400/20 dark:from-fuchsia-950/40 dark:to-zinc-900/70">
                                        <div class="pointer-events-none absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle at 30% 50%, #e879f9 0%, transparent 60%)"></div>
                                        <div class="relative flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
                                            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-fuchsia-100 text-3xl shadow-sm dark:bg-fuchsia-900/40">🕵️</span>
                                            <div>
                                                <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-fuchsia-600/80 dark:text-fuchsia-300/70">{{ __('imposter.results.winner') }}</p>
                                                <p class="cyber-title mt-1 text-2xl font-bold text-fuchsia-700 dark:text-fuchsia-300">{{ __('imposter.results.imposterWins') }}</p>
                                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                                    @if ($hasImposterGuessed)
                                                        {{ __('imposter.results.imposterWinsGuessedDesc') }}
                                                    @else
                                                        {{ __('imposter.results.imposterWinsDesc', ['name' => $imposterName ?? '?']) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif



                                {{-- Reveal cards --}}
                                <div class="grid gap-4 sm:grid-cols-2">
                                    @if ($imposterName)
                                        <div class="rounded-3xl border border-fuchsia-200/50 bg-linear-to-br from-fuchsia-50/60 to-white/70 p-6 shadow-sm backdrop-blur-md dark:border-fuchsia-400/15 dark:from-fuchsia-950/30 dark:to-zinc-900/60">
                                            <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-fuchsia-600/70 dark:text-fuchsia-300/60">{{ __('imposter.voting.imposterWas') }}</p>
                                            <p class="cyber-title mt-2 text-xl font-bold text-fuchsia-700 dark:text-fuchsia-300">{{ $imposterName }}</p>
                                        </div>
                                    @endif

                                    @if (is_string($revealedWord) && $revealedWord !== '')
                                        <div class="rounded-3xl border border-cyan-200/50 bg-linear-to-br from-cyan-50/60 to-white/70 p-6 shadow-sm backdrop-blur-md dark:border-cyan-400/15 dark:from-cyan-950/30 dark:to-zinc-900/60">
                                            <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-600/70 dark:text-cyan-300/60">{{ __('imposter.voting.wordWas') }}</p>
                                            <p class="cyber-title mt-2 text-xl font-bold text-zinc-950 dark:text-white">{{ $revealedWord }}</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Vote results --}}
                                <div class="rounded-3xl border border-zinc-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/[0.08] dark:bg-zinc-900/60">
                                    <p class="cyber-title text-base font-bold text-zinc-950 dark:text-white">{{ __('imposter.voting.resultsTitle') }}</p>

                                    <div class="mt-5 space-y-2">
                                        @foreach ($players as $player)
                                            @php
                                                $votes = (int) ($voteCounts[$player['id']] ?? 0);
                                                $voters = $voteMap[$player['id']] ?? [];
                                            @endphp
                                            <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white/60 px-4 py-3.5 text-sm font-semibold shadow-sm backdrop-blur-md dark:border-white/[0.08] dark:bg-zinc-800/40">
                                                @if ($votes > 0)
                                                    <div class="absolute inset-y-0 left-0 bg-cyan-400/8 dark:bg-cyan-400/6" style="width: {{ round(($votes / $maxVotes) * 100) }}%"></div>
                                                @endif
                                                <div class="relative flex items-center justify-between gap-4">
                                                    <span class="flex min-w-0 items-center gap-3">
                                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg {{ \App\View\PlayerColors::avatar($player['color']) }}">{{ $player['emoji'] }}</span>
                                                        <span class="min-w-0 truncate text-zinc-900 dark:text-white">{{ $player['name'] }}</span>
                                                    </span>
                                                    <span class="inline-flex min-w-7 items-center justify-center rounded-full {{ $votes > 0 ? 'bg-cyan-400/10 text-cyan-700 dark:text-cyan-200' : 'text-zinc-400 dark:text-zinc-500' }} font-mono text-xs font-semibold">{{ $votes }}</span>
                                                </div>
                                                @if (count($voters) > 0)
                                                    <div class="relative mt-2 flex flex-wrap gap-1.5">
                                                        @foreach ($voters as $voterName)
                                                            <span class="inline-flex items-center gap-1 rounded-full border border-zinc-200/60 bg-white/50 px-2 py-0.5 text-[11px] font-semibold text-zinc-500 dark:border-white/[0.06] dark:bg-zinc-800/30 dark:text-zinc-400">
                                                                {{ $voterName }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-3xl border border-zinc-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/[0.08] dark:bg-zinc-900/60">
                                <h3 class="cyber-title text-base font-bold text-zinc-950 dark:text-white">{{ __('imposter.joinThisRoom') }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ __('imposter.roomLead') }}</p>

                                <div class="mt-6 space-y-3">
                                    <div class="-mt-4">
                                        <livewire:games.identity />
                                    </div>

                                    @if ($isPasswordProtected)
                                        <label class="block">
                                            <span class="mb-1.5 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ __('imposter.password') }}</span>
                                            <input
                                                wire:model.live="joinPassword"
                                                type="password"
                                                autocomplete="off"
                                                spellcheck="false"
                                                class="w-full rounded-2xl border bg-white/70 px-4 py-3 text-sm font-semibold text-zinc-950 shadow-sm outline-none transition placeholder:text-zinc-400 @error('joinPassword') border-rose-500 focus:border-rose-500 focus-visible:ring-rose-400/60 dark:border-rose-500/50 dark:focus:border-rose-500/50 dark:focus-visible:ring-rose-300/30 @else border-zinc-200 focus:border-zinc-300 focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:border-white/10 dark:focus:border-white/20 dark:focus-visible:ring-cyan-300/30 @enderror dark:bg-zinc-950/35 dark:text-white dark:placeholder:text-zinc-500"
                                                placeholder="{{ __('imposter.placeholder.password') }}"
                                            />
                                            @error('joinPassword')
                                                <span class="mt-1.5 block text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</span>
                                            @enderror
                                        </label>
                                    @endif

                                    @if ($error)
                                        <div class="rounded-2xl border border-rose-200 bg-rose-50/80 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-400/20 dark:bg-rose-950/30 dark:text-rose-200">{{ $error }}</div>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="join"
                                        @disabled(!\App\Support\GameIdentity::exists())
                                        class="touch-manipulation inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-zinc-950 px-8 py-4 text-base font-semibold text-white shadow-lg ring-1 ring-zinc-950/10 transition hover:bg-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-zinc-950 dark:ring-white/10 dark:hover:bg-zinc-100 dark:focus-visible:ring-cyan-300/40"
                                    >
                                        {{ __('imposter.join') }}
                                        <span aria-hidden="true" class="text-white/70 dark:text-zinc-500">→</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Players modal --}}
        @if ($showPlayers)
            <div class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center" wire:keydown.window.escape="closePlayers">
                <button
                    type="button"
                    wire:click="closePlayers"
                    class="absolute inset-0 cursor-default bg-black/45 backdrop-blur-sm"
                    aria-label="Close players"
                    aria-hidden="true"
                    tabindex="-1"
                ></button>

                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="{{ __('imposter.players') }}"
                    class="relative w-full max-w-md overflow-hidden rounded-3xl border border-zinc-200 bg-white/95 shadow-2xl shadow-zinc-950/20 dark:border-white/10 dark:bg-zinc-950/95 dark:shadow-black/30"
                >
                    <div class="flex items-center justify-between border-b border-zinc-200/70 bg-white/70 px-5 py-4 backdrop-blur dark:border-white/10 dark:bg-zinc-950/55">
                        <div class="flex items-center gap-3">
                            <h3 class="cyber-title text-base font-bold text-zinc-950 dark:text-white">{{ __('imposter.players') }}</h3>
                            <span class="rounded-full bg-cyan-400/10 px-2.5 py-1 font-mono text-[11px] font-semibold text-cyan-700 dark:text-cyan-200">{{ count($players) }}</span>
                        </div>
                        <button
                            type="button"
                            wire:click="closePlayers"
                            class="touch-manipulation inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-zinc-200 bg-white/70 text-sm font-semibold text-zinc-900 shadow-sm backdrop-blur transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60 dark:border-white/10 dark:bg-zinc-950/40 dark:text-white dark:hover:bg-zinc-950/55 dark:focus-visible:ring-cyan-300/30"
                            aria-label="Close players"
                        >
                            &times;
                        </button>
                    </div>

                    <div class="max-h-[72svh] overflow-auto overscroll-contain p-4">
                        <div class="space-y-2">
                            @forelse ($players as $player)
                                <div class="flex items-center justify-between rounded-2xl border bg-white/70 px-4 py-3.5 text-sm font-semibold shadow-sm backdrop-blur {{ $player['isMe'] ? 'border-cyan-400/30 ring-1 ring-cyan-400/20 dark:border-cyan-400/15 dark:ring-cyan-300/10' : 'border-zinc-200 dark:border-white/10' }} text-zinc-900 dark:bg-zinc-800/50 dark:text-white">
                                    <span class="flex min-w-0 items-center gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg {{ \App\View\PlayerColors::avatar($player['color']) }}">{{ $player['emoji'] }}</span>
                                        <span class="min-w-0 truncate">{{ $player['name'] }}</span>
                                    </span>
                                    <div class="flex items-center gap-2">
                                        @if ($player['isHost'])
                                            <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">{{ __('imposter.host') }}</span>
                                        @endif
                                        @if ($player['isMe'])
                                            <span class="rounded-full bg-cyan-400/10 px-2.5 py-1 text-xs font-semibold text-cyan-700 dark:text-cyan-200">Ben</span>
                                        @endif
                                        @if ($isHost && ! $player['isMe'] && $status === \App\Enums\ImposterRoomStatus::Lobby)
                                            <button
                                                type="button"
                                                wire:click="openKickConfirm('{{ $player['id'] }}', '{{ addslashes($player['name']) }}')"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-950/30"
                                                title="{{ __('imposter.kick') }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="py-6 text-center text-sm text-zinc-600 dark:text-zinc-300">{{ __('imposter.noOneYet') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif

    @endif

    {{-- Custom Modal for Imposter Guessed --}}
    @if ($confirmImposterGuessed)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm transition-opacity dark:bg-zinc-950/60">
            <div class="w-full max-w-sm scale-100 transform overflow-hidden rounded-3xl bg-white p-6 text-left align-middle shadow-xl transition-all ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">
                <h3 class="text-lg font-semibold leading-6 text-zinc-900 dark:text-white">
                    {{ __('imposter.modal.confirmGuessed.title') }}
                </h3>
                <div class="mt-2">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('imposter.modal.confirmGuessed.desc') }}
                    </p>
                </div>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row-reverse" wire:loading.class="pointer-events-none opacity-50 block cursor-progress" wire:target="imposterGuessed">
                    <button type="button" wire:click="imposterGuessed" class="inline-flex w-full justify-center rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600 sm:w-auto">
                        {{ __('imposter.modal.confirmGuessed.confirm') }}
                    </button>
                    <button type="button" wire:click="$set('confirmImposterGuessed', false)" class="inline-flex w-full justify-center rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 transition hover:bg-zinc-50 sm:w-auto dark:bg-zinc-800 dark:text-white dark:ring-white/20 dark:hover:bg-zinc-700">
                        {{ __('imposter.modal.confirmGuessed.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
    {{-- Custom Modal for Kick Confirmation --}}
    @if ($confirmKickPlayerId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm transition-opacity dark:bg-zinc-950/60">
            <div class="w-full max-w-sm scale-100 transform overflow-hidden rounded-3xl bg-white p-6 text-left align-middle shadow-xl transition-all ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10" @click.away="$wire.closeKickConfirm()">
                <h3 class="text-lg font-semibold leading-6 text-zinc-900 dark:text-white">
                    {{ __('imposter.modal.kickConfirm.title') }}
                </h3>
                <div class="mt-2">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {!! __('imposter.modal.kickConfirm.desc', ['name' => '<span class="font-bold text-zinc-900 dark:text-white">' . e($confirmKickPlayerName) . '</span>']) !!}
                    </p>
                </div>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row-reverse">
                    <button type="button" wire:click="kickPlayer('{{ $confirmKickPlayerId }}')" class="inline-flex w-full justify-center rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600 sm:w-auto">
                        {{ __('imposter.modal.kickConfirm.confirm') }}
                    </button>
                    <button type="button" wire:click="closeKickConfirm" class="inline-flex w-full justify-center rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 transition hover:bg-zinc-50 sm:w-auto dark:bg-zinc-800 dark:text-white dark:ring-white/20 dark:hover:bg-zinc-700">
                        {{ __('imposter.modal.kickConfirm.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
