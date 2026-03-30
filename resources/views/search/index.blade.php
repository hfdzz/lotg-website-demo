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

            <div class="result-list" style="margin-top: 1rem;">
                @forelse ($lawMatches as $law)
                    <article class="result-card">
                        <p class="eyebrow">Law match</p>
                        <h2><a href="{{ route('laws.show', $law) }}">Law {{ $law->law_number }}</a></h2>
                        <p class="law-meta">Matched by law number.</p>
                    </article>
                @empty
                @endforelse

                @forelse ($contentMatches as $translation)
                    <article class="result-card">
                        <p class="eyebrow">Content match</p>
                        <h3>
                            <a href="{{ route('laws.show', $translation->contentNode->law) }}">
                                Law {{ $translation->contentNode->law->law_number }}
                            </a>
                        </h3>
                        @if ($translation->title)
                            <h4>{{ $translation->title }}</h4>
                        @endif
                        <p class="law-meta">{{ $translation->search_excerpt }}</p>
                    </article>
                @empty
                @endforelse

                @if ($lawMatches->isEmpty() && $contentMatches->isEmpty())
                    <p class="empty-state">No published matches found for "{{ $query }}".</p>
                @endif
            </div>
        @endif
    </section>
@endsection
