@extends('layouts.app')

@section('title', 'Law '.$law->law_number)
@section('body_class', 'has-mobile-law-context')
@section('mobile_header_title', 'Law '.$law->law_number.': '.$law->displayTitle())
@section('mobile_law_context', 'Law '.$law->law_number.': '.$law->displayTitle())

@section('content')
    <a class="back-link" href="{{ route('laws.index') }}">Back to all laws</a>

    @section('mobile_law_prev')
        @if ($previousLaw)
            {{ route('laws.show', $previousLaw) }}
        @endif
    @endsection

    @section('mobile_law_next')
        @if ($nextLaw)
            {{ route('laws.show', $nextLaw) }}
        @endif
    @endsection

    <div class="law-detail-shell">
        <section class="hero">
            <p class="eyebrow">Law {{ $law->law_number }}</p>
            <h1>{{ $law->displayTitle() }}</h1>
            <p>
                Read the law in a structured format with nested sections, supporting text, diagrams,
                and related video examples where available.
            </p>
            <div class="law-detail-meta">
                <span class="law-detail-pill">Language: {{ strtoupper($language) }}</span>
                <span class="law-detail-pill">Slug: {{ $law->slug }}</span>
            </div>
        </section>

        @if (count($tableOfContents) > 0)
            <details class="law-detail-mobile-toc">
                <summary class="toc-summary">Table of contents</summary>
                <div class="stack-top">
                    @include('laws.partials.toc', ['items' => $tableOfContents])
                </div>
            </details>
        @endif

        <div class="law-detail-grid">
            @if (count($tableOfContents) > 0)
                <aside class="toc-card">
                    <div>
                        <p class="eyebrow">Contents</p>
                        <h2 class="toc-title">On this page</h2>
                    </div>

                    @include('laws.partials.toc', ['items' => $tableOfContents])
                </aside>
            @endif

            <section class="law-content">
                @forelse ($tree as $node)
                    @include('laws.partials.node', ['node' => $node])
                @empty
                    <p class="law-meta law-content-empty">This law has no published content yet.</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
