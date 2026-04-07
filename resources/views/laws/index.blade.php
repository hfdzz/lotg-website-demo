@extends('layouts.app')

@section('title', __('site.laws.index_title'))

@section('content')
    <div class="lotg-hub-layout">
        @include('laws.partials.hub-nav', [
            'hubDocuments' => $hubDocuments,
            'hubLaws' => $laws,
            'language' => $language,
            'currentKey' => 'laws',
        ])

        <div class="lotg-hub-main">
            <section class="hero">
                <p class="eyebrow">{{ __('site.laws.index_eyebrow') }}</p>
                <h1>{{ __('site.laws.index_title') }}</h1>
                <p>{{ __('site.laws.index_intro') }}</p>
            </section>

            <section class="law-grid">
                @if (! $hasActiveEdition)
                    <div class="card">
                        <h2>{{ __('site.laws.unavailable_title') }}</h2>
                        <p class="law-meta">{{ __('site.laws.unavailable_body') }}</p>
                    </div>
                @else
                    @forelse ($laws as $law)
                        <a class="law-link law-row-card card" href="{{ route('laws.show', $law).'?lang='.$language }}" style="--law-card-image: url('{{ $law->cardBackgroundImageUrl() }}');">
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
                                    <div class="law-link-cta-arrow" aria-hidden="true">
                                        <svg viewBox="0 0 100 24" preserveAspectRatio="none">
                                            <path d="M0 12 H95 M75 6 L100 12 L75 18"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="card">
                            <h2>{{ __('site.laws.no_laws_title') }}</h2>
                            <p class="law-meta">{{ __('site.laws.no_laws_body') }}</p>
                        </div>
                    @endforelse
                @endif
            </section>

            @if ($otherPublishedEditions->isNotEmpty())
                <div class="card edition-browser">
                    <h2>{{ __('site.editions.more_title') }}</h2>
                    <p class="law-meta">{{ __('site.editions.more_body') }}</p>
                    <p class="stack-top">
                        <a class="result-link" href="{{ route('editions.index', ['lang' => $language]) }}">{{ __('site.editions.browse_link') }}</a>
                    </p>
                </div>
            @endif
        </div>
    </div>
@endsection
