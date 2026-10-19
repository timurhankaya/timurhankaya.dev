@php($onGamesPage = request()->routeIs('*.games.index'))
@php($gameHeading = $onGamesPage ? 'h2' : 'h3')
<div @class(['brand-games-grid', 'is-games-page' => $onGamesPage])>
    <div class="brand-game-entry">
        <a class="brand-game brand-game-imposter" href="{{ \App\Support\Seo::route('games.imposter.index') }}">
            <div class="brand-game-top"><span class="brand-label">{{ __('brand.multiplayer') }}</span><span class="brand-game-arrow" aria-hidden="true"><x-arrow /></span></div>
            <div class="brand-game-drawing" aria-hidden="true"><span class="brand-playing-card"><span>?</span></span><span class="brand-playing-card"><span>?</span></span><span class="brand-playing-card brand-playing-card-secret">!</span></div>
            <div class="brand-game-copy"><p class="brand-label brand-game-category">01 / {{ __('brand.game_category_one') }}</p><{{ $gameHeading }}>Word Imposter</{{ $gameHeading }}><p>{{ __('brand.imposter') }}</p>@if ($onGamesPage)<p class="game-card-facts"><span><x-games.icon name="players" />{{ __('games.min_players', ['count' => 3]) }}</span><span class="game-card-roles" aria-hidden="true"><x-games.icon name="crew" /><x-games.icon name="imposter" /></span></p>@endif<span class="brand-game-cta">{{ __('brand.play') }} <x-arrow dir="e" /></span></div>
        </a>
    </div>
    <div class="brand-game-entry">
        <a class="brand-game brand-game-vampire" href="{{ \App\Support\Seo::route('games.vampire.index') }}">
            <div class="brand-game-top"><span class="brand-label">{{ __('brand.multiplayer') }}</span><span class="brand-game-arrow" aria-hidden="true"><x-arrow /></span></div>
            <div class="brand-game-drawing brand-moon-scene" aria-hidden="true"><span class="brand-moon"></span><span class="brand-star brand-star-one">✦</span><span class="brand-star brand-star-two">✦</span><span class="brand-star brand-star-three">✧</span></div>
            <div class="brand-game-copy"><p class="brand-label brand-game-category">02 / {{ __('brand.game_category_two') }}</p><{{ $gameHeading }}>{{ __('vampire.title') }}</{{ $gameHeading }}><p>{{ __('brand.vampire') }}</p>@if ($onGamesPage)<p class="game-card-facts"><span><x-games.icon name="players" />{{ __('games.min_players', ['count' => 4]) }}</span><span class="game-card-roles" aria-hidden="true"><x-games.icon name="vampire" /><x-games.icon name="villager" /><x-games.icon name="doctor" /><x-games.icon name="detective" /><x-games.icon name="hunter" /></span></p>@endif<span class="brand-game-cta">{{ __('brand.play') }} <x-arrow dir="e" /></span></div>
        </a>
    </div>
</div>
