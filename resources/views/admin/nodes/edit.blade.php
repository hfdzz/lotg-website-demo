@extends('layouts.app')

@section('title', 'Admin | Edit Node')

@section('content')
    <a class="back-link" href="{{ route('admin.laws.edit', $law) }}">Back to law editor</a>

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Edit node #{{ $node->id }}</h1>
        <p>
            Keep this lean: structure, one English translation, and media attachments that fit the
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
        <div class="card" style="margin-bottom: 1rem;">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    <section class="card" style="margin-bottom: 1rem;">
        <h2>Node settings</h2>
        <form action="{{ route('admin.nodes.update', [$law, $node]) }}" method="post" enctype="multipart/form-data" style="display: grid; gap: 1rem; margin-top: 1rem;">
            @csrf
            @method('patch')
            @include('admin.partials.node-fields', ['node' => $node, 'translation' => $translation, 'parentOptions' => $parentOptions])
            <button type="submit">Save node</button>
        </form>
    </section>

    <section class="card">
        <h2>Delete node</h2>
        <p class="law-meta">Deleting a node removes its descendants recursively in the application layer.</p>
        <form action="{{ route('admin.nodes.destroy', [$law, $node]) }}" method="post" style="margin-top: 1rem;">
            @csrf
            @method('delete')
            <button type="submit">Delete node</button>
        </form>
    </section>
@endsection
