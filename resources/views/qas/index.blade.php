@extends('layouts.app')

@section('title', __('site.qas.title'))

@section('content')
    <section class="hero">
        <p class="eyebrow">{{ __('site.qas.eyebrow') }}</p>
        <h1>{{ __('site.qas.hero_title') }}</h1>
        <p>{{ __('site.qas.hero_intro') }}</p>
    </section>

    @if (! $hasActiveEdition)
        <section class="card">
            <h2>{{ __('site.qas.unavailable_title') }}</h2>
            <p class="law-meta">{{ __('site.qas.unavailable_body') }}</p>
        </section>
    @else
        <section class="result-list">
            @forelse ($laws as $law)
                <article class="result-card">
                    <h2>{{ __('site.laws.law_number', ['number' => $law->law_number]) }}: {{ $law->displayTitle($language) }}</h2>
                    @if ($law->displaySubtitle($language))
                        <p class="law-meta">{{ $law->displaySubtitle($language) }}</p>
                    @endif
                    <p class="law-meta">{{ __('site.qas.item_count', ['count' => $law->publishedQas->count()]) }}</p>
                    <p class="stack-top">
                        <a class="result-link" href="{{ route('qas.show', ['law' => $law, 'lang' => $language]) }}">{{ __('site.qas.open_law_section') }}</a>
                    </p>
                </article>
            @empty
                <article class="card">
                    <h2>{{ __('site.qas.empty_title') }}</h2>
                    <p class="law-meta">{{ __('site.qas.empty_body') }}</p>
                </article>
            @endforelse
        </section>
    @endif
@endsection
