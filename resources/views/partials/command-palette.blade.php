@php
    use App\Enums\PostType;
    use App\Support\Anchors;
    use App\Support\Seo;

    $path = fn (string $url): string => (parse_url($url, PHP_URL_PATH) ?: '/').(parse_url($url, PHP_URL_FRAGMENT) ? '#'.parse_url($url, PHP_URL_FRAGMENT) : '');
    // Same destinations, labels and order as the header navigation.
    $navigate = [
        ['url' => Seo::route('home'), 'label' => __('portfolio.nav.home')],
        ['url' => Anchors::home('about'), 'label' => __('brand.about_label')],
        ['url' => Seo::route('games.index'), 'label' => __('portfolio.nav.games')],
        ['url' => Seo::route('games.imposter.index'), 'label' => __('imposter.title'), 'search' => __('portfolio.nav.games'), 'icon' => 'imposter'],
        ['url' => Seo::route('games.vampire.index'), 'label' => __('vampire.title'), 'search' => __('portfolio.nav.games'), 'icon' => 'moon'],
        ['url' => Anchors::home('work-together'), 'label' => __('brand.work_together_nav')],
        ['url' => Anchors::home('experience'), 'label' => __('portfolio.nav.experience')],
        ['url' => Seo::route('contact'), 'label' => __('portfolio.nav.contact')],
        ['url' => Seo::route('cv'), 'label' => __('portfolio.nav.cv_pdf'), 'external' => true],
    ];
    // Every published story is searchable by title, listed under its own page: articles newest first,
    // case studies in the same project order as the case-study page.
    $posts = \App\Models\Post::published()->latest('published_at')->orderBy('id')->get()->groupBy(fn ($post) => $post->type->value);
    $story = fn ($post, string $kind) => ['url' => $post->publicUrl(), 'label' => $post->text('title'), 'hint' => $kind, 'sub' => true];
    $order = array_flip(\App\Services\Portfolio\ProjectCatalog::STORY_ORDER);
    $writing = [
        ['url' => Seo::route('blog.index'), 'label' => __('brand.blog'), 'search' => __('brand.writing_menu')],
        ...$posts->get(PostType::Article->value, collect())->map(fn ($post) => $story($post, __('brand.blog'))),
        ['url' => Seo::route('case-studies.index'), 'label' => __('brand.case_type'), 'search' => __('brand.writing_menu')],
        ...$posts->get(PostType::CaseStudy->value, collect())->sortBy([fn ($a, $b) => ($order[$a->project_key] ?? 99) <=> ($order[$b->project_key] ?? 99), ['sort_order', 'asc']])
            ->map(fn ($post) => $story($post, __('brand.case_type'))),
    ];
@endphp

<div id="command-palette" class="brand-palette hidden" aria-hidden="true" data-command-palette>
    <button type="button" class="brand-palette-scrim" aria-label="{{ __('brand.close') }}" tabindex="-1" data-command-palette-close></button>
    <div class="brand-palette-panel" role="dialog" aria-modal="true" aria-labelledby="command-palette-title">
        <h2 id="command-palette-title" class="sr-only">{{ __('portfolio.command_palette.title') }}</h2>
        <label class="brand-palette-search">
            <span class="sr-only">{{ __('portfolio.command_palette.title') }}</span>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" inputmode="search" autocomplete="off" placeholder="{{ __('portfolio.command_palette.placeholder') }}" data-command-palette-input />
            <kbd>Esc</kbd>
        </label>

        <div class="brand-palette-list">
            @foreach ([__('portfolio.command_palette.navigate') => $navigate, __('brand.writing_menu') => $writing] as $group => $commands)
                <p class="brand-palette-group">{{ $group }}</p>
                <div data-command-palette-list>
                    @foreach ($commands as $command)
                        <a
                            href="{{ $command['url'] }}"
                            @class(['brand-palette-item', 'is-sub' => isset($command['icon']) || ($command['sub'] ?? false)])
                            @if ($command['external'] ?? false) target="_blank" rel="noopener noreferrer" @endif
                            data-command
                            data-command-label="{{ trim(($command['search'] ?? '').' '.$command['label'].' '.($command['hint'] ?? '')) }}"
                        >
                            <span>
                                @if (($command['icon'] ?? null) === 'imposter')
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 10h.01"/><path d="M15 10h.01"/><path d="M12 2a8 8 0 0 0-8 8v12l3-3 2.5 2.5L12 19l2.5 2.5L17 19l3 3V10a8 8 0 0 0-8-8z"/></svg>
                                @elseif (($command['icon'] ?? null) === 'moon')
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                                @endif
                                <span class="brand-palette-label">{{ $command['label'] }}</span>
                            </span>
                            <span class="brand-palette-hint">{{ $command['hint'] ?? $path($command['url']) }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach

            <p class="brand-palette-group">{{ __('portfolio.command_palette.actions') }}</p>
            <div data-command-palette-list>
                <button type="button" class="brand-palette-item" data-command data-command-label="{{ __('portfolio.command_palette.copy_email') }}" data-command-copy="kayacekovic@gmail.com">
                    <span><span class="brand-palette-label">{{ __('portfolio.command_palette.copy_email') }}</span></span>
                </button>
                <button type="button" class="brand-palette-item" data-command data-command-label="{{ __('portfolio.command_palette.copy_link') }}" data-command-copy-current-url="1">
                    <span><span class="brand-palette-label">{{ __('portfolio.command_palette.copy_link') }}</span></span>
                </button>
            </div>
            <p class="brand-palette-empty" hidden data-command-palette-empty>{{ __('portfolio.command_palette.empty') }}</p>
        </div>

        <p class="brand-palette-foot">{{ __('portfolio.command_palette.subtitle') }}</p>
    </div>
</div>
