<footer class="brand-footer">
    <div class="brand-footer-top">
        <a class="brand-logo" href="{{ \App\Support\Seo::route('home') }}" aria-label="tkaya. — Timurhan Kaya">tkaya<span>.</span></a>
        <p>{{ __('brand.footer') }}</p>
        <a class="brand-text-link" href="#content">{{ __('brand.back_top') }} <x-arrow dir="n" /></a>
    </div>
    <div class="brand-footer-bottom">
        <p>© {{ now()->year }} Timurhan Kaya</p>
        <div><a href="{{ \App\Support\Seo::route('contact') }}">{{ __('portfolio.nav.contact') }}</a><a href="https://github.com/timurhankaya" target="_blank" rel="me noopener noreferrer">GitHub <x-arrow /></a><a href="https://www.linkedin.com/in/timurhan-kaya/" target="_blank" rel="me noopener noreferrer">LinkedIn <x-arrow /></a></div>
    </div>
</footer>
