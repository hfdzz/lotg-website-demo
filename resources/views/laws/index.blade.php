@extends('layouts.app')

@section('title', 'Laws of the Game')

@section('content')
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
            <a class="law-link card" href="{{ route('laws.show', $law) }}">
                <p class="eyebrow">Law {{ $law->law_number }}</p>
                <h2>Law {{ $law->law_number }}</h2>
                <p class="law-meta">Slug: {{ $law->slug }}</p>
            </a>
        @empty
            <div class="card">
                <h2>No laws published yet</h2>
                <p class="law-meta">Run the seed data to load the first sample law.</p>
            </div>
        @endforelse
    </section>
@endsection
