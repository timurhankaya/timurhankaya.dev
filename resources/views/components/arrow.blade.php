@props(['dir' => 'ne'])
{{-- One stroked path drawn pointing north-east; CSS turns it to the other directions and animates it on hover. --}}
<svg class="brand-arrow" data-dir="{{ $dir }}" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 12 12 4M5.5 4H12v6.5"/></svg>
