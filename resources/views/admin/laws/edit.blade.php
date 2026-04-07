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

    <details class="card collapse-card" @open($errors->any())>
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

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Manage Q&amp;A</h2>
        </summary>
        <div class="collapse-body">
            <p class="nav-meta">Q&amp;A lives outside the main content tree and renders below the law as a compact accordion list.</p>

            <form action="{{ route('admin.qas.store', ['edition' => $selectedEdition, 'law' => $law]) }}" method="post" class="stack-form">
                @csrf
                @include('admin.partials.qa-fields', ['qa' => null, 'translationsByLanguage' => collect(), 'languages' => \App\Support\LotgLanguage::supported()])
                <button type="submit">Create Q&amp;A</button>
            </form>

            <div class="stack-top qa-admin-list">
                @forelse ($qas as $qa)
                    <article class="result-card">
                        <h3>{{ $qa->displayQuestion() }}</h3>
                        <p class="law-meta">Sort {{ $qa->sort_order }} · {{ $qa->is_published ? 'Published' : 'Draft' }}</p>
                        <p class="stack-top">
                            <a class="result-link" href="{{ route('admin.qas.edit', ['edition' => $selectedEdition, 'law' => $law, 'qa' => $qa]) }}">Edit Q&amp;A</a>
                        </p>
                    </article>
                @empty
                    <p class="empty-state">No Q&amp;A items yet for this law.</p>
                @endforelse
            </div>
        </div>
    </details>
@endsection
