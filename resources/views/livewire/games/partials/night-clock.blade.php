@if ($nightEndsAt !== null)
    <div class="vk-night-clock" wire:key="night-clock-{{ $nightNumber }}-{{ $nightEndsAt }}"
        x-data="{
            remaining: {{ max(0, $nightEndsAt - $serverNow) }},
            deadline: {{ $nightEndsAt }},
            offset: Date.now() - {{ $serverNow }} * 1000,
            interval: null,
            init() { this.tick(); this.interval = setInterval(() => this.tick(), 250); },
            tick() {
                this.remaining = Math.max(0, Math.ceil((this.deadline * 1000 - (Date.now() - this.offset)) / 1000));
            },
            destroy() { clearInterval(this.interval); }
        }"
        :class="{ 'is-ending': remaining <= 10 }">
        <div class="vk-night-clock-time">
            <x-games.icon name="moon" />
            <span class="brand-label">{{ __('games.night_clock.label') }}</span>
            <span class="vk-night-seconds" aria-hidden="true"><span x-text="remaining">{{ max(0, $nightEndsAt - $serverNow) }}</span>s</span>
            <span class="sr-only">{{ __('games.night_clock.duration') }}</span>
        </div>
        <p>{{ __('games.night_clock.description') }}</p>
    </div>
@endif
