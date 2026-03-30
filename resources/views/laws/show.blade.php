@extends('layouts.app')

@section('title', 'Law '.$law->law_number)

@section('content')
    <a class="back-link" href="{{ route('laws.index') }}">Back to all laws</a>

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

        <section class="card law-content">
            @forelse ($tree as $node)
                @include('laws.partials.node', ['node' => $node])
            @empty
                <p class="law-meta">This law has no published content yet.</p>
            @endforelse
        </section>
    </div>
@endsection
