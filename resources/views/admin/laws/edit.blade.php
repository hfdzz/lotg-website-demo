@extends('layouts.app')

@section('title', 'Admin | Edit Law')

@section('content')
    <a class="back-link" href="{{ route('admin.laws.index') }}">Back to admin laws</a>

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Edit law {{ $law->law_number }}</h1>
        <p>
            Update the law record, then add structured nodes beneath it. Parent selection controls
            nesting, and sort order controls sibling display.
        </p>
    </section>

    @if (session('status'))
        <div class="card surface-note">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Law settings</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.laws.update', $law) }}" method="post" class="stack-form">
                @csrf
                @method('patch')
                @include('admin.partials.law-fields', ['law' => $law])
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
            <form action="{{ route('admin.nodes.store', $law) }}" method="post" enctype="multipart/form-data" class="stack-form">
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
@endsection
