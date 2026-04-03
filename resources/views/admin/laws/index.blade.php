@extends('layouts.app')

@section('title', 'Admin | Laws')

@section('content')
    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Manage laws</h1>
        <p>
            A minimal internal editor for laws and structured content nodes. This is intentionally
            lightweight so we can ship content workflows before building a larger admin surface.
        </p>
    </section>

    @if (session('status'))
        <div class="card surface-note">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    <section class="card">
        <form action="{{ route('admin.laws.index') }}" method="get" class="stack-form">
            <label>
                <div class="law-meta">Working edition</div>
                <select name="edition" onchange="this.form.submit()">
                    @foreach ($editions as $edition)
                        <option value="{{ $edition->id }}" @selected($selectedEdition?->id === $edition->id)>{{ $edition->name }}@if ($edition->is_active) (active) @endif</option>
                    @endforeach
                </select>
            </label>
        </form>
    </section>

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Create law</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.laws.store') }}" method="post" class="stack-form">
                @csrf
                @include('admin.partials.law-fields', ['law' => null, 'translationsByLanguage' => collect(), 'languages' => $languages, 'editions' => $editions, 'selectedEdition' => $selectedEdition])
                <button type="submit">Create law</button>
            </form>
        </div>
    </details>

    <section class="card">
        <h2>Existing laws</h2>
        <div class="result-list stack-top">
            @forelse ($laws as $law)
                <article class="result-card">
                    <p class="eyebrow">Law {{ $law->law_number }}</p>
                    <h3><a href="{{ route('admin.laws.edit', ['law' => $law, 'edition' => $selectedEdition?->id]) }}">Edit law {{ $law->law_number }}</a></h3>
                    <p class="law-meta">{{ $law->displayTitle('id') }} | Edition: {{ $law->edition?->name ?? 'None' }} | Status: {{ $law->status }} | Sort: {{ $law->sort_order }}</p>
                </article>
            @empty
                <p class="empty-state">No laws yet.</p>
            @endforelse
        </div>
    </section>
@endsection
