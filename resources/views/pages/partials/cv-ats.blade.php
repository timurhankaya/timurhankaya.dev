@php
    $technicalSkills = collect($t['projects'])
        ->flatMap(fn (array $project) => explode(' · ', $project['stack']))
        ->prepend('Laravel')->prepend('PHP')->filter()->unique()->values()->implode(', ');
@endphp
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
            <p>{{ $t['email_label'] }}: <a href="mailto:kayacekovic@gmail.com">kayacekovic@gmail.com</a></p>
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
                <p>{{ $exp['meta'] }}</p>
                <ul>
                    @foreach ($exp['bullets'] as $bullet)
                        <li>{{ $bullet }}</li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </section>

    <section aria-labelledby="ats-skills">
        <h2 id="ats-skills">{{ $locale === 'tr' ? 'Teknik beceriler' : 'Technical skills' }}</h2>
        <p>{{ $technicalSkills }}</p>
        @foreach ($t['skill_groups'] as $group)
            <p><strong>{{ $group['title'] }}:</strong> {{ implode(', ', array_column($group['items'], 'label')) }}</p>
        @endforeach
    </section>

    <section aria-labelledby="ats-projects">
        <h2 id="ats-projects">{{ $locale === 'tr' ? 'Seçilmiş projeler' : 'Selected projects' }}</h2>
        @foreach ($t['projects'] as $project)
            <article>
                <h3>{{ $project['name'] }}</h3>
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
