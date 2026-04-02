@extends('layouts.app')

@section('title', __('site.laws.index_title'))

@section('content')
    @php
        $language = \App\Support\LotgLanguage::normalize(request('lang'));
    @endphp
    <section class="hero">
        <p class="eyebrow">{{ __('site.laws.index_eyebrow') }}</p>
        <h1>{{ __('site.laws.index_title') }}</h1>
        <p>{{ __('site.laws.index_intro') }}</p>
    </section>

    <section class="law-grid">
        @forelse ($laws as $law)
            <a class="law-link law-row-card card" href="{{ route('laws.show', $law).'?lang='.$language }}">
                <div class="law-row-main">
                    <p class="law-number">{{ __('site.laws.law_number', ['number' => $law->law_number]) }}</p>
                    <h2>{{ $law->displayTitle($language) }}</h2>
                    @if ($law->displaySubtitle($language))
                        <p class="law-row-subtitle">{{ $law->displaySubtitle($language) }}</p>
                    @endif
                </div>

                <div class="law-link-cta">
                    <div class="law-link-cta-inner">
                        <span class="law-link-cta-label">{{ __('site.laws.view_law') }}</span>
                        <span class="law-link-cta-arrow" aria-hidden="true">-></span>
                    </div>
                </div>
            </a>
        @empty
            <div class="card">
                <h2>{{ __('site.laws.no_laws_title') }}</h2>
                <p class="law-meta">{{ __('site.laws.no_laws_body') }}</p>
            </div>
        @endforelse
    </section>
@endsection
