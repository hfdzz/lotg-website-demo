@extends('layouts.app')

@section('title', 'Law '.$law->law_number)

@section('content')
    <a class="back-link" href="{{ route('laws.index') }}">Back to all laws</a>

    <section class="hero">
        <p class="eyebrow">Language: {{ strtoupper($language) }}</p>
        <h1>Law {{ $law->law_number }}</h1>
        <p>
            Public rendering for the self-referencing content tree. Sibling order comes from
            <code>sort_order</code>, and heading size is driven by render depth.
        </p>
    </section>

    <section class="card">
        @forelse ($tree as $node)
            @include('laws.partials.node', ['node' => $node])
        @empty
            <p class="law-meta">This law has no published content yet.</p>
        @endforelse
    </section>
@endsection
