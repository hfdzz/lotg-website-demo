@extends('layouts.app')

@section('title', __('site.editions.title'))

@section('content')
    <section class="hero">
        <p class="eyebrow">{{ __('site.editions.eyebrow') }}</p>
        <h1>{{ __('site.editions.title') }}</h1>
        <p>{{ __('site.editions.intro') }}</p>
    </section>

    <section class="law-list-compact">
        @forelse ($editions as $edition)
            <article class="law-list-item">
                <h2>
                    <a class="result-link" href="{{ route('laws.index', ['edition' => $edition->id, 'lang' => $language]) }}">
                        {{ $edition->name }}
                        @if ($activeEdition && $edition->id === $activeEdition->id)
                            ({{ __('site.editions.current') }})
                        @endif
                    </a>
                </h2>
                <p class="law-meta">{{ __('site.editions.years', ['start' => $edition->year_start, 'end' => $edition->year_end]) }}</p>
            </article>
        @empty
            <div class="card">
                <h2>{{ __('site.editions.empty_title') }}</h2>
                <p class="law-meta">{{ __('site.editions.empty_body') }}</p>
            </div>
        @endforelse
    </section>
@endsection
