@extends('layouts.app')

@section('title', __('site.laws.law_number', ['number' => $law->law_number]))
@section('body_class', 'has-mobile-law-context')
@section('mobile_header_title', __('site.laws.law_number', ['number' => $law->law_number]).': '.$law->displayTitle($language))
@section('mobile_law_context', __('site.laws.law_number', ['number' => $law->law_number]).': '.$law->displayTitle($language))

@section('content')
    <a class="back-link" href="{{ route('laws.index', ['lang' => $language]) }}">{{ __('site.laws.back') }}</a>

    @section('mobile_law_prev')
        @if ($previousLaw)
            {{ route('laws.show', $previousLaw).'?lang='.$language }}
        @endif
    @endsection

    @section('mobile_law_next')
        @if ($nextLaw)
            {{ route('laws.show', $nextLaw).'?lang='.$language }}
        @endif
    @endsection

    <div class="law-detail-shell">
        <section class="hero">
            <p class="eyebrow">{{ __('site.laws.law_number', ['number' => $law->law_number]) }}</p>
            <h1>{{ $law->displayTitle($language) }}</h1>
            @if ($law->displaySubtitle($language))
                <p class="law-hero-subtitle">{{ $law->displaySubtitle($language) }}</p>
            @endif
            <p>{{ $law->displayDescription($language) ?: __('site.laws.hero_intro') }}</p>
            <div class="law-hero-nav">
                @if ($previousLaw)
                    <a class="law-nav-link" href="{{ route('laws.show', $previousLaw).'?lang='.$language }}">
                        <span class="law-nav-label">{{ __('site.laws.previous_law') }}</span>
                        <span>{{ __('site.laws.law_number', ['number' => $previousLaw->law_number]) }}</span>
                    </a>
                @endif

                @if ($nextLaw)
                    <a class="law-nav-link" href="{{ route('laws.show', $nextLaw).'?lang='.$language }}">
                        <span class="law-nav-label">{{ __('site.laws.next_law') }}</span>
                        <span>{{ __('site.laws.law_number', ['number' => $nextLaw->law_number]) }}</span>
                    </a>
                @endif
            </div>
        </section>

        @if (count($tableOfContents) > 0)
            <details class="law-detail-mobile-toc">
                <summary class="toc-summary">{{ __('site.laws.table_of_contents') }}</summary>
                <div class="stack-top">
                    @include('laws.partials.toc', ['items' => $tableOfContents])
                </div>
            </details>
        @endif

        <div class="law-detail-grid">
            @if (count($tableOfContents) > 0)
                <aside class="toc-card">
                    <div>
                        <p class="eyebrow">{{ __('site.laws.law_number', ['number' => $law->law_number]) }}</p>
                        <p class="toc-law-title">{{ $law->displayTitle($language) }}</p>
                        @if ($law->displaySubtitle($language))
                            <p class="toc-law-meta">{{ $law->displaySubtitle($language) }}</p>
                        @endif
                    </div>

                    <div>
                        <p class="eyebrow">{{ __('site.laws.contents') }}</p>
                        <h2 class="toc-title">{{ __('site.laws.on_this_page') }}</h2>
                    </div>

                    @include('laws.partials.toc', ['items' => $tableOfContents])
                </aside>
            @endif

            <section class="law-content">
                @forelse ($tree as $node)
                    @include('laws.partials.node', ['node' => $node])
                @empty
                    <p class="law-meta law-content-empty">{{ __('site.laws.no_content') }}</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
