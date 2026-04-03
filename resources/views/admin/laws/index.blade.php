@extends('layouts.app')

@section('title', 'Admin | Laws')

@section('content')
    @php
        $defaultYearStart = (int) now()->format('Y');
        $defaultYearEnd = $defaultYearStart + 1;
    @endphp
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
        <form action="{{ route('admin.editions.go') }}" method="get" class="stack-form">
            <label>
                <div class="law-meta">Working edition</div>
                <select name="edition" onchange="this.form.submit()">
                    @foreach ($editions as $edition)
                        <option value="{{ $edition->slug }}" @selected($selectedEdition?->id === $edition->id)>{{ $edition->name }}@if ($edition->is_active) (active) @endif</option>
                    @endforeach
                </select>
            </label>
        </form>
    </section>

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Create edition</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.editions.store') }}" method="post" class="stack-form">
                @csrf
                <label>
                    <div class="law-meta">Name</div>
                    <input type="text" name="name" value="{{ old('name') }}">
                </label>
                <label>
                    <div class="law-meta">Year start</div>
                    <input type="number" name="year_start" value="{{ old('year_start', $defaultYearStart) }}">
                </label>
                <label>
                    <div class="law-meta">Year end</div>
                    <input type="number" name="year_end" value="{{ old('year_end', $defaultYearEnd) }}">
                </label>
                <button type="submit">Create edition</button>
            </form>
        </div>
    </details>

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Edit current edition</h2>
        </summary>
        <div class="collapse-body">
            @if ($selectedEdition)
                <form action="{{ route('admin.editions.update', $selectedEdition) }}" method="post" class="stack-form">
                    @csrf
                    @method('patch')
                    <label>
                        <div class="law-meta">Name</div>
                        <input type="text" name="name" value="{{ old('name', $selectedEdition->name) }}">
                    </label>
                    <label>
                        <div class="law-meta">Year start</div>
                        <input type="number" name="year_start" value="{{ old('year_start', $selectedEdition->year_start) }}">
                    </label>
                    <label>
                        <div class="law-meta">Year end</div>
                        <input type="number" name="year_end" value="{{ old('year_end', $selectedEdition->year_end) }}">
                    </label>
                    @if ($selectedEdition->is_active)
                        <p class="law-meta">This is the active edition.</p>
                    @else
                        <button type="submit" name="set_active" value="1">Save and set as active</button>
                    @endif
                    <button type="submit">Save edition</button>
                </form>
            @endif
        </div>
    </details>

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Create law</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.laws.store', ['edition' => $selectedEdition]) }}" method="post" class="stack-form">
                @csrf
                @include('admin.partials.law-fields', ['law' => null, 'translationsByLanguage' => collect(), 'languages' => $languages, 'selectedEdition' => $selectedEdition])
                <button type="submit">Create law</button>
            </form>
        </div>
    </details>

    <section class="card">
        <p class="law-meta"><a class="result-link" href="{{ route('admin.changelog.index', ['edition' => $selectedEdition]) }}">Manage update entries for this edition</a></p>
    </section>

    <section class="card">
        <h2>Existing laws</h2>
        <div class="result-list stack-top">
            @forelse ($laws as $law)
                <article class="result-card">
                    <p class="eyebrow">Law {{ $law->law_number }}</p>
                    <h3><a href="{{ route('admin.laws.edit', ['edition' => $selectedEdition, 'law' => $law]) }}">Edit law {{ $law->law_number }}</a></h3>
                    <p class="law-meta">{{ $law->displayTitle('id') }} | Edition: {{ $law->edition?->name ?? 'None' }} | Status: {{ $law->status }} | Sort: {{ $law->sort_order }}</p>
                </article>
            @empty
                <p class="empty-state">No laws yet.</p>
            @endforelse
        </div>
    </section>
@endsection
