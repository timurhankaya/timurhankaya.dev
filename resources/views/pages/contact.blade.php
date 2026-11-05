@php
    $years = now()->year - 2019;

    /** @var array<string, mixed> $content */
    $content = is_array($content ?? null) ? $content : [];
    $t = function (mixed $value) use ($years): string {
        $text = is_string($value) ? $value : '';

        return str_replace([':years'], [(string) $years], $text);
    };

    $contactUrl = \App\Support\Seo::route('contact');
    $homeUrl = \App\Support\Seo::route('home');

    $seo = [
        'title' => $t(data_get($content, 'contact.title')).' — '.$t(data_get($content, 'meta.title')),
        'description' => $t(data_get($content, 'contact.meta_description') ?: data_get($content, 'contact.description')),
        'canonical' => $contactUrl,
        'image' => 'og-contact.png',
        'author' => 'Timurhan Kaya',
    ];
@endphp

@extends('layouts.app')

@section('title', $t(data_get($content, 'contact.title')).' — '.$t(data_get($content, 'meta.title')))

@php
    $trail = [[__('brand.home_crumb'), $homeUrl], [$t(data_get($content, 'contact.title')), $contactUrl]];
@endphp
@push('head')
    @include('pages.partials.contact-styles')
    {!! \App\Support\StructuredData::script(
        \App\Support\StructuredData::webPage('ContactPage', $contactUrl, $t(data_get($content, 'contact.title')), $t(data_get($content, 'contact.description')), [
            'mainEntity' => ['@id' => \App\Support\StructuredData::personId()],
            'breadcrumb' => ['@id' => $contactUrl.'#breadcrumb'],
        ]),
        \App\Support\StructuredData::breadcrumbs($contactUrl, $trail),
        [...\App\Support\StructuredData::person(), 'email' => 'kayacekovic@gmail.com'],
        \App\Support\StructuredData::website(),
    ) !!}
@endpush

@section('content')
    <x-breadcrumbs :trail="$trail" />
    @php
        // The project call to action preselects its topic; a failed submit keeps what was chosen.
        $topic = old('topic', in_array(request('topic'), \App\Models\ContactRequest::TOPICS, true) ? request('topic') : 'other');
        $services = (array) old('services', []);
        $field = fn (string $name): string => $errors->has($name) ? ' aria-invalid="true" aria-describedby="'.$name.'-error"' : '';
    @endphp
    <div class="brand-contact-page">
        <header class="brand-contact-intro">
            <p class="brand-label brand-contact-eyebrow">{{ __('brand.contact_page') }} <span aria-hidden="true">/ 01</span></p>
            <h1 class="brand-display">{{ __('brand.contact') }}</h1>
            <p class="brand-contact-lead">{{ __('brand.contact_intro') }}</p>
            <span class="brand-contact-mark" aria-hidden="true"><x-arrow dir="se" /></span>
        </header>
        <form id="form" class="brand-form" method="POST" action="{{ route('contact.store') }}" data-contact-form>
            @csrf
            <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
            <input type="hidden" name="started" value="{{ encrypt(now()->timestamp) }}">
            <div class="brand-form-trap" aria-hidden="true"><label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label></div>
            <div class="brand-form-head"><h2>{{ __('brand.form_title') }}</h2><p>{{ __('brand.form_intro') }}</p></div>
            @if (session('contact_sent'))
                <div class="brand-form-sent" role="status"><p>{{ __('brand.form_sent') }}</p><p>{{ __('brand.form_sent_body') }}</p></div>
            @endif
            @if ($errors->any())
                <p class="brand-form-alert" role="alert">{{ __('brand.form_errors') }}</p>
            @endif
            <fieldset class="brand-contact-topic" @error('topic') aria-describedby="topic-error" @enderror>
                <legend class="brand-label">{{ __('brand.form_topic') }}</legend>
                <div class="brand-contact-topics">
                    @foreach (__('brand.form_topics') as $value => $label)
                        <label class="brand-chip"><input type="radio" name="topic" value="{{ $value }}" @checked($topic === $value) required><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                @error('topic')<p class="brand-form-error" id="topic-error">{{ $message }}</p>@enderror
            </fieldset>
            <div class="brand-form-row">
                <label class="brand-field"><span class="brand-label">{{ __('brand.form_name') }}</span><input type="text" name="name" value="{{ old('name') }}" maxlength="120" autocomplete="name" required{!! $field('name') !!}>@error('name')<span class="brand-form-error" id="name-error">{{ $message }}</span>@enderror</label>
                <label class="brand-field"><span class="brand-label">{{ __('brand.form_email') }}</span><input type="email" name="email" value="{{ old('email') }}" maxlength="190" autocomplete="email" required{!! $field('email') !!}>@error('email')<span class="brand-form-error" id="email-error">{{ $message }}</span>@enderror</label>
            </div>
            <label class="brand-field"><span class="brand-label">{{ __('brand.form_company') }} <small>({{ __('brand.form_optional') }})</small></span><input type="text" name="company" value="{{ old('company') }}" maxlength="120" autocomplete="organization"{!! $field('company') !!}>@error('company')<span class="brand-form-error" id="company-error">{{ $message }}</span>@enderror</label>
            <fieldset class="brand-form-project" data-project-fields @if ($topic !== 'project') hidden @endif>
                <legend class="brand-label">{{ __('brand.form_project') }} <small>({{ __('brand.form_optional') }})</small></legend>
                <p class="brand-form-question">{{ __('brand.form_services') }}</p>
                <div class="brand-form-choices">
                    @foreach (__('brand.form_service_options') as $value => $label)
                        <label class="brand-chip"><input type="checkbox" name="services[]" value="{{ $value }}" @checked(in_array($value, $services, true))><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                @error('services')<p class="brand-form-error" role="alert">{{ $message }}</p>@enderror
                @error('services.*')<p class="brand-form-error" role="alert">{{ $message }}</p>@enderror
                <div class="brand-form-row">
                    <label class="brand-field"><span class="brand-label">{{ __('brand.form_budget') }}</span><input type="text" name="budget" value="{{ old('budget') }}" maxlength="120" placeholder="{{ __('brand.form_budget_hint') }}"{!! $field('budget') !!}>@error('budget')<span class="brand-form-error" id="budget-error">{{ $message }}</span>@enderror</label>
                    <label class="brand-field"><span class="brand-label">{{ __('brand.form_timeline') }}</span><select name="timeline"{!! $field('timeline') !!}><option value="">—</option>@foreach (__('brand.form_timelines') as $value => $label)<option value="{{ $value }}" @selected(old('timeline') === $value)>{{ $label }}</option>@endforeach</select>@error('timeline')<span class="brand-form-error" id="timeline-error">{{ $message }}</span>@enderror</label>
                </div>
            </fieldset>
            <label class="brand-field"><span class="brand-label">{{ __('brand.form_message') }}</span><textarea name="message" rows="7" minlength="20" maxlength="5000" placeholder="{{ __('brand.form_message_hint') }}" required{!! $field('message') !!}>{{ old('message') }}</textarea>@error('message')<span class="brand-form-error" id="message-error">{{ $message }}</span>@enderror</label>
            <div class="brand-form-foot"><p>{{ __('brand.form_privacy') }}</p><button class="brand-button" type="submit">{{ __('brand.form_submit') }} <x-arrow /></button></div>
        </form>
    </div>
@endsection
