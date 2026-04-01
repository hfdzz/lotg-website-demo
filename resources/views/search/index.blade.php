@extends('layouts.app')

@section('title', __('site.search.title'))

@section('content')
    <section class="hero">
        <p class="eyebrow">{{ __('site.search.eyebrow') }}</p>
        <h1>{{ __('site.search.hero_title') }}</h1>
        <p>{{ __('site.search.hero_intro') }}</p>
    </section>

    <section class="card">
        @if ($query === '')
            <p class="empty-state">{{ __('site.search.empty_prompt') }}</p>
        @else
            <p class="law-meta">{{ __('site.search.results_for', ['query' => $query]) }}</p>

            @if ($lawMatches->isEmpty() && $contentMatches->isEmpty())
                <p class="empty-state stack-top">{{ __('site.search.no_results', ['query' => $query]) }}</p>
            @else
                <div class="stack-top">
                    <section class="result-section">
                        <h2 class="result-section-title">{{ __('site.search.law_matches') }}</h2>
                        @if ($lawMatches->isEmpty())
                            <p class="empty-state">{{ __('site.search.no_law_matches') }}</p>
                        @else
                            <div class="result-list">
                                @foreach ($lawMatches as $law)
                                    <article class="result-card">
                                        <p class="eyebrow">{{ __('site.laws.law_number', ['number' => $law->law_number]) }}</p>
                                        <h3><a class="result-link" href="{{ route('laws.show', $law).'?lang='.$language }}">{{ $law->displayTitle() }}</a></h3>
                                        <p class="law-meta">{{ __('site.search.open_law') }}</p>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="result-section">
                        <h2 class="result-section-title">{{ __('site.search.content_matches') }}</h2>
                        @if ($contentMatches->isEmpty())
                            <p class="empty-state">{{ __('site.search.no_content_matches') }}</p>
                        @else
                            <div class="result-list">
                                @foreach ($contentMatches as $translation)
                                    <article class="result-card">
                                        <p class="eyebrow">{{ __('site.search.in_law', ['number' => $translation->contentNode->law->law_number]) }}</p>
                                        <h3>
                                            <a class="result-link" href="{{ route('laws.show', $translation->contentNode->law).'?lang='.$language }}">
                                                {{ $translation->title ?: $translation->contentNode->law->displayTitle() }}
                                            </a>
                                        </h3>
                                        <p class="law-meta">{{ $translation->search_excerpt }}</p>
                                        <p class="law-meta">
                                            <a class="result-link" href="{{ route('laws.show', $translation->contentNode->law).'?lang='.$language }}">
                                                {{ __('site.search.go_to_law', ['title' => $translation->contentNode->law->displayTitle()]) }}
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
