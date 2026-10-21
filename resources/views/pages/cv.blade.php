@php
    $locale = app()->getLocale();
    if (!in_array($locale, ['en', 'tr'])) {
        $locale = 'en';
    }

    if (!is_array($content ?? null) || $content === []) {
        $content = app(\App\Services\Portfolio\PortfolioContentService::class)->get($locale);
    }

    app()->setLocale($locale);

    $workStartYear = 2019;
    $birthDate = \Carbon\CarbonImmutable::create(2002, 4, 12);
    $years = now()->year - $workStartYear;
    $age = $birthDate->diffInYears(\Carbon\CarbonImmutable::now());

    /** @var array<string, mixed> $content */
    $content = is_array($content ?? null) ? $content : [];

    $tx = function (mixed $value) use ($years, $age): string {
        $text = is_string($value) ? $value : '';

        return str_replace([':years', ':age'], [(string) $years, (string) $age], $text);
    };

    $experienceItems = collect((array) data_get($content, 'experience.items', []))
        ->map(fn (array $item): array => [
            'role' => $tx(data_get($item, 'role')),
            'period' => $tx(data_get($item, 'period')),
            'company' => $tx(data_get($item, 'company')),
            'meta' => $tx(data_get($item, 'meta')),
            'bullets' => array_map($tx, array_values((array) data_get($item, 'bullets', []))),
        ])
        ->values()
        ->all();

    $projectItems = collect((array) data_get($content, 'projects.sections', []))
        ->flatMap(fn (array $section): array => array_values((array) data_get($section, 'items', [])))
        ->take(4)
        ->map(fn (array $item): array => [
            'name' => $tx(data_get($item, 'name')),
            'tag' => $tx(data_get($item, 'badge')),
            'desc' => $tx(data_get($content, 'cv.project_descriptions.'.data_get($item, 'key'), data_get($item, 'description'))),
            'stack' => implode(' · ', array_filter(array_map($tx, array_values((array) data_get($item, 'stack', []))))),
        ])
        ->values()
        ->all();

    $skillGroups = collect((array) data_get($content, 'skills.groups', []))
        ->map(fn (array $group): array => [
            'title' => $tx(data_get($group, 'title')),
            'items' => collect((array) data_get($group, 'items', []))
                ->map(fn (array $item): array => [
                    'label' => $tx(data_get($item, 'label')),
                ])
                ->values()
                ->all(),
        ])
        ->values()
        ->all();

    $educationItems = collect((array) data_get($content, 'education.items', []))
        ->map(fn (array $item): array => [
            'title' => $tx(data_get($item, 'title')),
            'subtitle' => $tx(data_get($item, 'subtitle')),
        ])
        ->values()
        ->all();

    $t = [
        'meta_title' => $tx(data_get($content, 'meta.title')),
        'print_btn' => $locale === 'tr' ? '⌘ PDF Olarak Kaydet' : '⌘ Save as PDF',
        'role' => $tx(data_get($content, 'cv.role')),
        'loc_label' => $tx(data_get($content, 'stats.loc_label')),
        'loc_value' => $tx(data_get($content, 'stats.loc_value')),
        'email_label' => $locale === 'tr' ? 'E-posta' : 'Email',
        'profile_title' => $tx(data_get($content, 'about.title')),
        'profile_text' => $tx(data_get($content, 'cv.profile')),
        'exp_title' => $tx(data_get($content, 'experience.title')),
        'exp_items' => $experienceItems,
        'projects_title' => $tx(data_get($content, 'projects.title')),
        'projects' => $projectItems,
        'skills_title' => $tx(data_get($content, 'skills.title')),
        'skill_groups' => $skillGroups,
        'edu_title' => $tx(data_get($content, 'education.title')),
        'education_items' => $educationItems,
        'int_title' => $tx(data_get($content, 'about.interests')),
        'interests' => array_map($tx, array_values((array) data_get($content, 'about.hobbies', []))),
    ];

    $cvUrl = \App\Support\Seo::route('cv', [], $locale);
    $alternateUrls = \App\Support\Seo::alternateUrls(\App\Support\Seo::route('cv'));
    $cvDescription = $t['profile_text'] !== '' ? $t['profile_text'] : $tx(data_get($content, 'meta.description'));
    $cvSkills = collect($t['skill_groups'])
        ->flatMap(fn (array $group): array => array_map(fn (array $item): string => $item['label'], $group['items']))
        ->values()
        ->all();
    $cvPerson = [...\App\Support\StructuredData::person($locale), 'jobTitle' => $t['role'], 'description' => $cvDescription, 'email' => 'kayacekovic@gmail.com', 'knowsAbout' => $cvSkills];
    $cvJsonLd = \App\Support\StructuredData::script(
        \App\Support\StructuredData::webPage('ProfilePage', $cvUrl, $t['meta_title'], \App\Support\Seo::metaDescription($cvDescription), [
            'mainEntity' => $cvPerson,
            'breadcrumb' => ['@id' => $cvUrl.'#breadcrumb'],
        ]),
        \App\Support\StructuredData::breadcrumbs($cvUrl, [[__('brand.home_crumb', [], $locale), \App\Support\Seo::route('home', [], $locale)], [$t['meta_title'], $cvUrl]]),
        $cvPerson,
        [...\App\Support\StructuredData::website($locale), 'description' => \App\Support\Seo::metaDescription($cvDescription)],
    );
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ url('/site.webmanifest') }}">
    <link rel="alternate" type="application/rss+xml" title="{{ __('brand.feed_title') }}" href="{{ \App\Support\Seo::route('feed') }}">
    <title>{{ $t['meta_title'] }}</title>
    <meta name="description" content="{{ \App\Support\Seo::metaDescription($cvDescription) }}">
    <meta name="author" content="Timurhan Kaya">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="{{ $cvUrl }}">
    @foreach ($alternateUrls as $hreflang => $href)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
    @endforeach
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $t['meta_title'] }}">
    <meta property="og:description" content="{{ \App\Support\Seo::metaDescription($cvDescription) }}">
    <meta property="og:url" content="{{ $cvUrl }}">
    <meta property="og:image" content="{{ \App\Support\Seo::imageUrl('og-cv.png') }}">
    <meta property="og:site_name" content="{{ \App\Support\Seo::siteName() }}">
    <meta property="og:locale" content="{{ \App\Support\Seo::ogLocale($locale) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $t['meta_title'] }}">
    <meta name="twitter:description" content="{{ \App\Support\Seo::metaDescription($cvDescription) }}">
    <meta name="twitter:image" content="{{ \App\Support\Seo::imageUrl('og-cv.png') }}">
    {!! $cvJsonLd !!}
    <link rel="stylesheet" href="{{ asset('fonts/typography.css') }}">
    <style>
        :root {
            --ink: #131418;
            --ink-secondary: #42464f;
            --ink-muted: #626874;
            --accent: #ff7a1a;
            --accent-soft: #fff0e5;
            --surface: #ffffff;
            --surface-alt: #f4f5f7;
            --border: #d4d7dd;
            --border-light: #e7e9ed;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html {
            font-size: 12.5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Geist', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--ink);
            background: #0a0a0b;
            line-height: 1.45;
        }

        /* ---- Page ---- */
        .page {
            width: 210mm;
            height: 297mm;
            margin: 20px auto;
            padding: 6mm 8mm;
            background: var(--surface);
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .page::before { display: none; }

        /* ---- Header ---- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-bottom: 3.5mm;
            border-bottom: .5mm solid var(--ink);
        }

        .name {
            font-size: 2.6rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .title-role {
            margin-top: 1mm;
            font-size: 1rem;
            font-weight: 600;
            color: var(--accent);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .header-right {
            display: grid;
            grid-template-columns: repeat(2, auto);
            justify-content: end;
            gap: 1.5mm 4mm;
        }

        .contact-item {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--ink-secondary);
            display: flex;
            align-items: center;
            gap: 1.5mm;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .contact-item:hover {
            color: var(--accent);
        }

        .contact-item .label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.62rem;
            font-weight: 600;
            color: var(--ink-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            transition: color 0.15s ease;
        }

        .contact-item:hover .label {
            color: var(--accent);
            opacity: 0.8;
        }

        /* ---- Columns ---- */
        .columns {
            display: flex;
            gap: 5mm;
            margin-top: 3.5mm;
            flex: 1;
            min-height: 0;
        }

        .col-main {
            flex: 1;
            min-width: 0;
        }

        .col-side {
            width: 53mm;
            flex-shrink: 0;
        }

        /* ---- Section ---- */
        .section { margin-bottom: 2.8mm; }
        .section:last-child { margin-bottom: 0; }

        .section-title {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--ink);
            padding-bottom: 1.5mm;
            border-bottom: .2mm solid var(--border);
            margin-bottom: 2.5mm;
            display: flex;
            align-items: center;
            gap: 1.5mm;
        }

        .section-title::before {
            content: '';
            display: inline-block;
            width: 2mm;
            height: 2mm;
            background: var(--accent);
            border-radius: .4mm;
            flex-shrink: 0;
        }

        /* ---- Experience ---- */
        .exp-entry { margin-bottom: 3mm; }
        .exp-entry:last-child { margin-bottom: 0; }

        .exp-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .exp-role {
            font-size: 1.02rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .exp-period {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--ink-muted);
            white-space: nowrap;
        }

        .exp-company {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--accent);
            margin-top: 0.2mm;
        }

        .exp-meta {
            font-size: 0.78rem;
            color: var(--ink-muted);
            line-height: 1.1;
        }

        .exp-bullets {
            list-style: none;
            margin-top: 0.8mm;
        }

        .exp-bullets li {
            font-size: 0.86rem;
            color: var(--ink-secondary);
            line-height: 1.5;
            padding-left: 3mm;
            position: relative;
            margin-bottom: 0.6mm;
        }

        .exp-bullets li::before {
            content: '▸';
            position: absolute;
            left: 0;
            color: var(--accent);
            font-size: 0.75rem;
        }

        /* ---- Summary ---- */
        .summary-text {
            font-size: 0.9rem;
            color: var(--ink-secondary);
            line-height: 1.55;
        }

        /* ---- Skills ---- */
        .skill-group { margin-bottom: 1.8mm; }
        .skill-group:last-child { margin-bottom: 0; }

        .skill-group-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 1mm;
        }

        .skill-item { margin-bottom: 1mm; }
        .skill-item:last-child { margin-bottom: 0; }

        .skill-label-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .skill-label {
            font-size: 0.75rem;
            color: var(--ink-secondary);
            font-weight: 500;
        }

        /* ---- Education ---- */
        .edu-item { margin-bottom: 1.8mm; }
        .edu-item:last-child { margin-bottom: 0; }

        .edu-title {
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .edu-subtitle {
            font-size: 0.75rem;
            color: var(--ink-muted);
            margin-top: 0.1mm;
        }

        .tag {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--accent);
            background: var(--accent-soft);
            padding: 0.2mm 1.4mm;
            border-radius: 0.6mm;
            white-space: nowrap;
        }

        /* ---- Projects ---- */
        .project-compact { margin-bottom: 3.5mm; }
        .project-compact:last-child { margin-bottom: 0; }

        .project-name {
            font-size: 0.85rem;
            font-weight: 700;
        }

        .project-desc {
            font-size: 0.76rem;
            color: var(--ink-muted);
            line-height: 1.45;
            margin-top: 0.3mm;
        }

        .project-stack {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.62rem;
            color: var(--ink-muted);
            margin-top: 0.3mm;
        }

        /* ---- Interests ---- */
        .interests-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1mm;
        }

        .interest-tag {
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--ink-secondary);
            background: var(--surface-alt);
            border: .18mm solid var(--border-light);
            border-radius: 0.8mm;
            padding: 0.45mm 1.6mm;
        }

        /* ---- Print Optimization ---- */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            html {
                font-size: 12px; /* Slightly shrink to fit 1 page */
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body { background: white; margin: 0; padding: 0; }
            .page {
                margin: 0;
                box-shadow: none;
                width: 210mm;
                height: 297mm;
                padding: 6mm 8mm;
                overflow: hidden;
            }
            .page::before { display: none !important; }
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
                text-shadow: none !important;
                filter: none !important;
            }
            .no-print { display: none !important; }
            /* Use system fonts for print to avoid embedding large web fonts */
            body, .page {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
                font-variation-settings: normal;
                font-feature-settings: normal;
            }
            .section-title, .tag, .exp-period, .contact-item .label {
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace !important;
                font-variation-settings: normal;
                font-feature-settings: normal;
            }
        }

        .print-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 999;
            background: var(--ink);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-family: 'Geist', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0,0,0,.2);
            transition: transform .15s, box-shadow .15s;
        }
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 28px rgba(0,0,0,.28);
        }

        /* ---- Header Info Layout Update ---- */
        /* Group 1: Email + Location */
        /* Group 2: LinkedIn + GitHub */
        /* Group 3: Web */
        .info-col {
            display: flex;
            flex-direction: column;
            gap: 0.8mm;
        }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">{{ $t['print_btn'] }}</button>

<div class="page">
    <header class="header">
        <div>
            <h1 class="name">Timurhan Kaya</h1>
            <p class="title-role">{{ $t['role'] }}</p>
        </div>
        <div class="header-right">
            <a href="mailto:kayacekovic@gmail.com" class="contact-item">
                <span class="label">{{ $t['email_label'] }}</span> kayacekovic@gmail.com
            </a>
            <a href="https://linkedin.com/in/timurhan-kaya" target="_blank" class="contact-item">
                <span class="label">LinkedIn</span> /in/timurhan-kaya
            </a>
            <span class="contact-item">
                <span class="label" style="color: var(--ink-muted);">{{ $t['loc_label'] }}</span> {{ $t['loc_value'] }}
            </span>
            <a href="https://github.com/timurhankaya" target="_blank" class="contact-item">
                <span class="label">GitHub</span> @timurhankaya
            </a>
            <!-- Empty spot for grid to push Web down nicely if needed, or we just rely on grid -->
            <a href="https://timurhankaya.dev" target="_blank" class="contact-item" style="grid-column: 1 / -1; justify-self: end;">
                <span class="label">Web</span> timurhankaya.dev
            </a>
        </div>
    </header>

    <div class="columns">
        <div class="col-main">
            <div class="section">
                <div class="section-title">{{ $t['profile_title'] }}</div>
                <p class="summary-text">{{ $t['profile_text'] }}</p>
            </div>

            <div class="section">
                <div class="section-title">{{ $t['exp_title'] }}</div>

                @foreach($t['exp_items'] as $index => $exp)
                <div class="exp-entry" {!! $loop->last ? 'style="margin-bottom:0;"' : '' !!}>
                    <div class="exp-header">
                        <span class="exp-role">{{ $exp['role'] }}</span>
                        <span class="exp-period" {!! $index === 0 ? 'style="font-weight: 700; color: var(--ink);"' : '' !!}>{{ $exp['period'] }}</span>
                    </div>
                    <div class="exp-company">{{ $exp['company'] }}</div>
                    <div class="exp-meta">{{ $exp['meta'] }}</div>
                    <ul class="exp-bullets">
                        @foreach($exp['bullets'] as $bullet)
                        <li>{{ $bullet }}</li>
                        @endforeach
                    </ul>
                </div>
                @endforeach
            </div>

            <div class="section">
                <div class="section-title">{{ $t['projects_title'] }}</div>

                @foreach($t['projects'] as $proj)
                <div class="project-compact">
                    <span class="project-name">{{ $proj['name'] }}</span> <span class="tag">{{ $proj['tag'] }}</span>
                    <p class="project-desc">{{ $proj['desc'] }}</p>
                    <p class="project-stack">{{ $proj['stack'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <div class="col-side">
            <div class="section">
                <div class="section-title">{{ $t['skills_title'] }}</div>
                @foreach($t['skill_groups'] as $group)
                <div class="skill-group">
                    <div class="skill-group-title">{{ $group['title'] }}</div>
                    @foreach($group['items'] as $item)
                    <div class="skill-item">
                        <span class="skill-label">{{ $item['label'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>

            <div class="section">
                <div class="section-title">{{ $t['edu_title'] }}</div>
                @foreach($t['education_items'] as $edu)
                <div class="edu-item">
                    <div class="edu-title">{{ $edu['title'] }}</div>
                    <div class="edu-subtitle">{{ $edu['subtitle'] }}</div>
                </div>
                @endforeach
            </div>


            <div class="section">
                <div class="section-title">{{ $t['int_title'] }}</div>
                <div class="interests-list">
                    @foreach($t['interests'] as $interest)
                    <span class="interest-tag">{{ $interest }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
