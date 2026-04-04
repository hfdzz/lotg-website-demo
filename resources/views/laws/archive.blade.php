@extends('layouts.app')

@section('title', $selectedEdition?->name ? $selectedEdition->name.' | '.__('site.laws.index_title') : __('site.laws.index_title'))

@section('content')
    <section class="hero">
        <p class="eyebrow">{{ __('site.editions.archive_eyebrow') }}</p>
        <h1>{{ $selectedEdition?->name ?? __('site.editions.title') }}</h1>
        <p>{{ __('site.editions.archive_intro') }}</p>
        <p class="stack-top">
            <a class="result-link" href="{{ route('editions.index', ['lang' => $language]) }}">{{ __('site.editions.back_to_editions') }}</a>
        </p>
    </section>

    <section class="law-list-compact">
        @if (! $hasActiveEdition && ! $selectedEdition)
            <div class="card">
                <h2>{{ __('site.laws.unavailable_title') }}</h2>
                <p class="law-meta">{{ __('site.laws.unavailable_body') }}</p>
            </div>
        @elseif (! $selectedEdition)
            <div class="card">
                <h2>{{ __('site.laws.no_laws_title') }}</h2>
                <p class="law-meta">{{ __('site.laws.no_laws_body') }}</p>
            </div>
        @else
            @forelse ($laws as $law)
                <article class="law-list-item">
                    <h2>
                        <a class="result-link" href="{{ route('laws.show', $law).'?lang='.$language }}">
                            {{ __('site.laws.law_number', ['number' => $law->law_number]) }}: {{ $law->displayTitle($language) }}
                        </a>
                    </h2>
                    @if ($law->displayDescription($language))
                        <p class="law-meta">{{ $law->displayDescription($language) }}</p>
                    @endif
                </article>
            @empty
                <div class="card">
                    <h2>{{ __('site.laws.no_laws_title') }}</h2>
                    <p class="law-meta">{{ __('site.laws.no_laws_body') }}</p>
                </div>
            @endforelse
        @endif
    </section>
@endsection
