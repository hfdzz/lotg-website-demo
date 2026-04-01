@extends('layouts.app')

@section('title', 'Laws of the Game')

@section('content')
    @php
        $language = \App\Support\LotgLanguage::normalize(request('lang'));
    @endphp
    <section class="hero">
        <p class="eyebrow">LotG v0.5</p>
        <h1>Laws of the Game</h1>
        <p>
            A structured publishing prototype for law pages, nested sections, images, and video examples.
            This first pass is intentionally lean so we can grow it safely.
        </p>
    </section>

    <section class="law-grid">
        @forelse ($laws as $law)
            <a class="law-link card" href="{{ route('laws.show', $law).'?lang='.$language }}">
                <span class="law-number">Law {{ $law->law_number }}</span>
                <div>
                    <h2>{{ $law->displayTitle() }}</h2>
                    <p class="law-slug">{{ $law->slug }}</p>
                </div>
                <p class="law-meta">
                    Open the law detail page to read nested sections, supporting text, and related media examples.
                </p>
                <span class="law-link-cta">View law <span aria-hidden="true">-></span></span>
            </a>
        @empty
            <div class="card">
                <h2>No laws published yet</h2>
                <p class="law-meta">Run the seed data to load the first sample law.</p>
            </div>
        @endforelse
    </section>
@endsection
