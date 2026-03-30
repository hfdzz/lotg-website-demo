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
        <div class="card" style="margin-bottom: 1rem;">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    <section class="card" style="margin-bottom: 1rem;">
        <h2>Law settings</h2>
        <form action="{{ route('admin.laws.update', $law) }}" method="post" style="display: grid; gap: 1rem; margin-top: 1rem;">
            @csrf
            @method('patch')
            @include('admin.partials.law-fields', ['law' => $law])
            <button type="submit">Save law</button>
        </form>
    </section>

    <section class="card" style="margin-bottom: 1rem;">
        <h2>Create node</h2>
        <form action="{{ route('admin.nodes.store', $law) }}" method="post" enctype="multipart/form-data" style="display: grid; gap: 1rem; margin-top: 1rem;">
            @csrf
            @include('admin.partials.node-fields', ['node' => null, 'translation' => null, 'parentOptions' => $parentOptions])
            <button type="submit">Create node</button>
        </form>
    </section>

    <section class="card">
        <h2>Existing nodes</h2>
        <div style="margin-top: 1rem;">
            @forelse ($nodeTree as $node)
                @include('admin.laws.partials.node-tree-item', ['law' => $law, 'node' => $node])
            @empty
                <p class="empty-state">No nodes yet for this law.</p>
            @endforelse
        </div>
    </section>
@endsection
