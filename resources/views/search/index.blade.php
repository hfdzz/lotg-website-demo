@extends('layouts.app')

@section('title', 'Search')

@section('content')
    <section class="hero">
        <p class="eyebrow">Basic search</p>
        <h1>Search the laws</h1>
        <p>
            This v0.5 search stays intentionally simple: law number, section title, and body text
            through regular SQL queries, with no external search engine required.
        </p>
    </section>

    <section class="card">
        @if ($query === '')
            <p class="empty-state">Enter a search term above to look through published law content.</p>
        @else
            <p class="law-meta">Results for "{{ $query }}" in {{ strtoupper($language) }}</p>

            @if ($lawMatches->isEmpty() && $contentMatches->isEmpty())
                <p class="empty-state" style="margin-top: 1rem;">No published matches found for "{{ $query }}".</p>
            @else
                <div style="margin-top: 1rem;">
                    <section class="result-section">
                        <h2 class="result-section-title">Law matches</h2>
                        @if ($lawMatches->isEmpty())
                            <p class="empty-state">No direct law-number matches.</p>
                        @else
                            <div class="result-list">
                                @foreach ($lawMatches as $law)
                                    <article class="result-card">
                                        <p class="eyebrow">Law {{ $law->law_number }}</p>
                                        <h3><a class="result-link" href="{{ route('laws.show', $law) }}">{{ $law->displayTitle() }}</a></h3>
                                        <p class="law-slug">{{ $law->slug }}</p>
                                        <p class="law-meta">Open the full law detail page.</p>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="result-section">
                        <h2 class="result-section-title">Content matches</h2>
                        @if ($contentMatches->isEmpty())
                            <p class="empty-state">No section title or body matches.</p>
                        @else
                            <div class="result-list">
                                @foreach ($contentMatches as $translation)
                                    <article class="result-card">
                                        <p class="eyebrow">In law {{ $translation->contentNode->law->law_number }}</p>
                                        <h3>
                                            <a class="result-link" href="{{ route('laws.show', $translation->contentNode->law) }}">
                                                {{ $translation->title ?: $translation->contentNode->law->displayTitle() }}
                                            </a>
                                        </h3>
                                        <p class="law-meta">{{ $translation->search_excerpt }}</p>
                                        <p class="law-meta">
                                            <a class="result-link" href="{{ route('laws.show', $translation->contentNode->law) }}">
                                                Go to {{ $translation->contentNode->law->displayTitle() }}
                                            </a>
                                        </p>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            @endif
        @endif
    </section>
@endsection
