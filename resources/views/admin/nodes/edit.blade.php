@extends('layouts.app')

@section('title', 'Admin | Edit Node')

@section('content')
    <a class="back-link" href="{{ route('admin.laws.edit', ['law' => $law, 'edition' => request('edition', $law->edition_id)]) }}">Back to law editor</a>

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Edit node #{{ $node->id }}</h1>
        <p>
            Keep this lean: structure, Indonesian and English translations, and media attachments that fit the
            selected node type.
        </p>
        <div class="law-detail-meta">
            <span class="law-detail-pill">Type: {{ strtoupper($node->node_type) }}</span>
            <span class="law-detail-pill">Parent: {{ $currentParentLabel }}</span>
            <span class="law-detail-pill">Sort: {{ $node->sort_order }}</span>
            <span class="law-detail-pill">Public: {{ $node->is_published ? 'yes' : 'no' }}</span>
        </div>
    </section>

    @if (session('status'))
        <div class="card surface-note">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Node settings</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.nodes.update', ['law' => $law, 'node' => $node, 'edition' => request('edition', $law->edition_id)]) }}" method="post" enctype="multipart/form-data" class="stack-form">
                @csrf
                @method('patch')
                @include('admin.partials.node-fields', ['node' => $node, 'translationsByLanguage' => $translationsByLanguage, 'languages' => $languages, 'parentOptions' => $parentOptions])
                <button type="submit">Save node</button>
            </form>
        </div>
    </details>

    <section class="card">
        <h2>Delete node</h2>
        <p class="law-meta">Deleting a node removes its descendants recursively in the application layer.</p>
        <form action="{{ route('admin.nodes.destroy', ['law' => $law, 'node' => $node, 'edition' => request('edition', $law->edition_id)]) }}" method="post" class="stack-top">
            @csrf
            @method('delete')
            <button type="submit">Delete node</button>
        </form>
    </section>
@endsection
