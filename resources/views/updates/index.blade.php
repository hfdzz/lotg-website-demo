@extends('layouts.app')

@section('title', __('site.updates.title'))

@section('content')
    <section class="hero">
        <p class="eyebrow">{{ __('site.updates.eyebrow') }}</p>
        <h1>{{ __('site.updates.hero_title') }}</h1>
        <p>{{ __('site.updates.hero_intro') }}</p>
    </section>

    @if ($publishedEditions->isNotEmpty())
        <section class="card edition-browser">
            <form action="{{ route('updates.index') }}" method="get" class="edition-selector-form">
                <input type="hidden" name="lang" value="{{ $language }}">
                <label>
                    <span class="law-meta">{{ __('site.editions.browse_updates_label') }}</span>
                    <select name="edition" onchange="this.form.submit()">
                        @foreach ($publishedEditions as $edition)
                            <option value="{{ $edition->id }}" @selected($selectedEdition?->id === $edition->id)>
                                {{ $edition->name }}@if ($activeEdition && $edition->id === $activeEdition->id) ({{ __('site.editions.current') }}) @endif
                            </option>
                        @endforeach
                    </select>
                </label>
            </form>
        </section>
    @endif

    <section class="card">
        @if (! $hasActiveEdition && ! $selectedEdition)
            <h2>{{ __('site.updates.unavailable_title') }}</h2>
            <p class="law-meta">{{ __('site.updates.unavailable_body') }}</p>
        @elseif (! $selectedEdition)
            <p class="empty-state">{{ __('site.updates.empty') }}</p>
        @else
            @forelse ($entries as $entry)
                <article class="result-card">
                    <p class="eyebrow">{{ optional($entry->published_at)->format('d M Y') }}</p>
                    <h2>{{ $entry->title }}</h2>
                    <p class="law-meta">{{ $entry->body }}</p>
                </article>
            @empty
                <p class="empty-state">{{ __('site.updates.empty') }}</p>
            @endforelse
        @endif
    </section>
@endsection
