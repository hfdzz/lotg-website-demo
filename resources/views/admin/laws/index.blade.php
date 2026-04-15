@extends('layouts.app')

@section('title', 'Admin | Laws')

@section('content')
    @php
        $editionManagementOnly = $editionManagementOnly ?? false;
    @endphp
    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Manage laws</h1>
        <p>A minimal internal editor for laws and structured content within the currently selected edition.</p>
        <p><a class="result-link" href="{{ route('admin.home') }}">Back to admin</a></p>
    </section>

    @if (session('status'))
        <div class="card surface-note flash-message flash-message-success">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    @if ($errors->any())
        <div class="flash-message-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @include('admin.partials.edition-switcher', ['editions' => $editions, 'selectedEdition' => $selectedEdition])

    @if ($selectedEdition)
        <section class="card section-card">
            <p class="law-meta">Working edition: <strong>{{ $selectedEdition->name }}</strong> - <a class="result-link" href="{{ route('admin.editions.index', ['edition' => $selectedEdition->id]) }}">Manage editions</a></p>
            <p class="law-meta"><a class="result-link" href="{{ route('admin.changelog.index', ['edition' => $selectedEdition]) }}">Manage law changes for this edition</a></p>
        </section>

        <details class="card collapse-card" @if($errors->any()) open @endif>
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
            <h2>Existing laws</h2>
            <div class="result-list stack-top">
                @forelse ($laws as $law)
                    <article class="result-card">
                        <p class="eyebrow">Law {{ $law->law_number }}</p>
                        <h3><a href="{{ route('admin.laws.edit', ['edition' => $selectedEdition, 'law' => $law]) }}">Edit law {{ $law->law_number }}</a></h3>
                        <p class="law-meta">{{ $law->displayTitle('id') }} | Edition: {{ $law->edition?->name ?? 'None' }} | Status: {{ $law->status }} | Sort: {{ $law->sort_order }}</p>
                        @can('delete', $law)
                            <form action="{{ route('admin.laws.destroy', ['edition' => $selectedEdition, 'law' => $law]) }}" method="post" class="stack-top" onsubmit="return confirm('Delete Law {{ $law->law_number }}? This will also remove its nodes and Q&A.')">
                                @csrf
                                @method('delete')
                                <button type="submit">Delete law</button>
                            </form>
                        @endcan
                    </article>
                @empty
                    <p class="empty-state">No laws yet.</p>
                @endforelse
            </div>
        </section>
    @elseif ($editionManagementOnly)
        <section class="card">
            <p class="law-meta">No edition is available to work with right now. Create or activate one from <a class="result-link" href="{{ route('admin.editions.index') }}">Manage editions</a>.</p>
        </section>
    @endif
@endsection
