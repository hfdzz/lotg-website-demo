@extends('layouts.app')

@section('title', 'Updates')

@section('content')
    <section class="hero">
        <p class="eyebrow">Changelog</p>
        <h1>Project updates</h1>
        <p>
            Published notes for content and structure changes. This stays lightweight for v0.5
            and can later evolve into edition-aware updates if needed.
        </p>
    </section>

    <section class="card">
        @forelse ($entries as $entry)
            <article class="result-card">
                <p class="eyebrow">{{ optional($entry->published_at)->format('d M Y') }} | {{ strtoupper($language) }}</p>
                <h2>{{ $entry->title }}</h2>
                <p class="law-meta">{{ $entry->body }}</p>
            </article>
        @empty
            <p class="empty-state">No published updates yet for {{ strtoupper($language) }}.</p>
        @endforelse
    </section>
@endsection
