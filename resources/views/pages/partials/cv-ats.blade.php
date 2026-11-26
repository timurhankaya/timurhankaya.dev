<nav class="ats-controls" aria-label="{{ $locale === 'tr' ? 'CV seçenekleri' : 'CV options' }}">
    <button type="button" onclick="window.print()">{{ $locale === 'tr' ? 'PDF olarak kaydet' : 'Save as PDF' }}</button>
    <a href="{{ $cvUrl }}">{{ $locale === 'tr' ? 'Görsel CV' : 'Designed CV' }}</a>
    @foreach (['en' => 'English', 'tr' => 'Türkçe'] as $language => $label)
        @if ($language !== $locale)
            <a href="{{ \App\Support\Seo::route('cv', [], $language) }}?ats=1" hreflang="{{ $language }}">{{ $label }}</a>
        @endif
    @endforeach
</nav>

<main id="ats-cv" class="ats-page">
    <header>
        <h1>Timurhan Kaya</h1>
        <p class="ats-role">{{ $t['role'] }}</p>
        <address class="ats-contact">
            <p>{{ $t['loc_value'] }}</p>
            <p>{{ $t['email_label'] }}: <!--email_off--><a href="mailto:kayacekovic@gmail.com">kayacekovic@gmail.com</a><!--/email_off--></p>
            <p>LinkedIn: <a href="https://linkedin.com/in/timurhan-kaya">https://linkedin.com/in/timurhan-kaya</a></p>
            <p>GitHub: <a href="https://github.com/timurhankaya">https://github.com/timurhankaya</a></p>
            <p>X: <a href="https://x.com/_tkaya">https://x.com/_tkaya</a></p>
            <p>{{ $locale === 'tr' ? 'Web sitesi' : 'Website' }}: <a href="https://timurhankaya.dev">https://timurhankaya.dev</a></p>
        </address>
    </header>

    <section aria-labelledby="ats-summary">
        <h2 id="ats-summary">{{ $locale === 'tr' ? 'Profesyonel özet' : 'Professional summary' }}</h2>
        <p>{{ $t['profile_text'] }}</p>
    </section>

    <section aria-labelledby="ats-experience">
        <h2 id="ats-experience">{{ $locale === 'tr' ? 'İş deneyimi' : 'Work experience' }}</h2>
        @foreach ($t['exp_items'] as $exp)
            <article>
                <h3>{{ $exp['role'] }} — {{ $exp['company'] }}</h3>
                <p>{{ $exp['period'] }}</p>
                @if ($exp['meta'] !== '')<p>{{ $exp['meta'] }}</p>@endif
                <ul>
                    @foreach ($exp['bullets'] as $bullet)
                        <li>{{ $bullet }}</li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </section>

    <section aria-labelledby="ats-technologies">
        <h2 id="ats-technologies">{{ $t['tech_title'] }}</h2>
        @foreach ($t['tech_groups'] as $group)
            <p><strong>{{ $group['title'] }}:</strong> {{ implode(', ', $group['items']) }}</p>
        @endforeach
    </section>

    <section aria-labelledby="ats-skills">
        <h2 id="ats-skills">{{ $t['skills_title'] }}</h2>
        @foreach ($t['skill_groups'] as $group)
            <p><strong>{{ $group['title'] }}:</strong> {{ implode(', ', array_column($group['items'], 'label')) }}</p>
        @endforeach
    </section>

    <section aria-labelledby="ats-projects">
        <h2 id="ats-projects">{{ $locale === 'tr' ? 'Seçilmiş projeler' : 'Selected projects' }}</h2>
        @foreach ($t['projects'] as $project)
            <article>
                <h3>{{ $project['name'] }}</h3>
                @if ($project['url'])<p>{{ __('brand.case_type') }}: <a href="{{ $project['url'] }}">{{ $project['url'] }}</a></p>@endif
                <p>{{ $project['desc'] }}</p>
                <p>{{ $project['stack'] }}</p>
            </article>
        @endforeach
    </section>

    <section aria-labelledby="ats-education">
        <h2 id="ats-education">{{ $t['edu_title'] }}</h2>
        @foreach ($t['education_items'] as $education)
            <h3>{{ $education['title'] }}</h3>
            <p>{{ $education['subtitle'] }}</p>
        @endforeach
    </section>
</main>
