@extends('layouts.app')

@section('title', __('site.updates.title'))

@section('content')
    <section class="hero">
        <p class="eyebrow">{{ __('site.updates.eyebrow') }}</p>
        <h1>{{ __('site.updates.hero_title') }}</h1>
        <p>{{ __('site.updates.hero_intro') }}</p>
    </section>

    <section class="card">
        @forelse ($entries as $entry)
            <article class="result-card">
                <p class="eyebrow">{{ optional($entry->published_at)->format('d M Y') }}</p>
                <h2>{{ $entry->title }}</h2>
                <p class="law-meta">{{ $entry->body }}</p>
            </article>
        @empty
            <p class="empty-state">{{ __('site.updates.empty') }}</p>
        @endforelse
    </section>
@endsection
