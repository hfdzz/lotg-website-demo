@extends('layouts.app')

@section('title', 'Admin | Edit Law')

@section('content')
    <a class="back-link" href="{{ route('admin.laws.index', ['edition' => $selectedEdition]) }}">Back to admin laws</a>

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Edit law {{ $law->law_number }}</h1>
        <p>
            Update the law record, then add structured nodes beneath it. Parent selection controls
            nesting, and sort order controls sibling display.
        </p>
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

    <details class="card collapse-card" @if($errors->any()) open @endif>
        <summary class="collapse-summary">
            <h2>Law settings</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.laws.update', ['edition' => $selectedEdition, 'law' => $law]) }}" method="post" class="stack-form">
                @csrf
                @method('patch')
                @include('admin.partials.law-fields', ['law' => $law, 'translationsByLanguage' => $translationsByLanguage, 'languages' => $languages, 'selectedEdition' => $selectedEdition])
                <button type="submit">Save law</button>
            </form>

            @can('delete', $law)
                <form action="{{ route('admin.laws.destroy', ['edition' => $selectedEdition, 'law' => $law]) }}" method="post" class="stack-form stack-top" data-confirm-message="Delete Law {{ $law->law_number }}? This will also remove its nodes and Q&amp;A.">
                    @csrf
                    @method('delete')
                    <button type="submit" class="button-danger">Delete law</button>
                </form>
            @endcan
        </div>
    </details>

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Create node</h2>
        </summary>
        <div class="collapse-body">
            <p class="nav-meta">Start with sections, then add child sections or content blocks beneath them. Parent labels include node type and current sort order to make hierarchy safer to read.</p>
            <form action="{{ route('admin.nodes.store', ['edition' => $selectedEdition, 'law' => $law]) }}" method="post" enctype="multipart/form-data" class="stack-form">
                @csrf
                <input type="hidden" name="return_to_law_edit" value="1">
                @include('admin.partials.node-fields', ['node' => null, 'translationsByLanguage' => collect(), 'languages' => \App\Support\LotgLanguage::supported(), 'parentOptions' => $parentOptions])
                <button type="submit">Create node</button>
            </form>
        </div>
    </details>

    <section class="card">
        <h2>Existing nodes</h2>
        <div class="stack-top">
            @forelse ($nodeTree as $node)
                @include('admin.laws.partials.node-tree-item', ['law' => $law, 'node' => $node])
            @empty
                <p class="empty-state">No nodes yet for this law.</p>
            @endforelse
        </div>
    </section>

    <section class="card">
        <h2>Q&amp;A</h2>
        <p class="nav-meta">Q&amp;A now has a separate admin page so law content editing stays focused on nodes.</p>
        <p class="stack-top">
            <a class="result-link" href="{{ route('admin.qas.law', ['edition' => $selectedEdition, 'law' => $law]) }}">Manage Q&amp;A for this law</a>
        </p>
    </section>
@endsection
