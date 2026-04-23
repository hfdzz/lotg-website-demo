@extends('layouts.app')

@section('title', 'Admin')

@section('content')
    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Content management</h1>
        <p>Choose which LotG area you want to manage.</p>
    </section>

    <section class="result-list">
        <a class="result-card result-link-block" href="{{ route('admin.editions.index') }}">
            <h2>Manage editions</h2>
            <p class="law-meta">Create, activate, and prepare LotG editions before editing their law content.</p>
        </a>

        <a class="result-card result-link-block" href="{{ route('admin.laws.home') }}">
            <h2>Manage laws</h2>
            <p class="law-meta">
                @if ($activeEdition)
                    Edit laws, nodes, Q&amp;A, and law change entries for the current working edition: {{ $activeEdition->name }}.
                @else
                    Edit laws, nodes, Q&amp;A, and law change entries for the selected working edition.
                @endif
            </p>
        </a>

        <a class="result-card result-link-block" href="{{ route('admin.documents.home') }}">
            <h2>Manage documents</h2>
            <p class="law-meta">
                @if ($activeEdition)
                    Edit supporting LotG documents for the current working edition: {{ $activeEdition->name }}.
                @else
                    Edit supporting LotG documents for the selected working edition.
                @endif
            </p>
        </a>

        <a class="result-card result-link-block" href="{{ route('admin.qas.home') }}">
            <h2>Manage Q&amp;A</h2>
            <p class="law-meta">
                @if ($activeEdition)
                    Create and edit law-specific simple or multiple-choice Q&amp;A for: {{ $activeEdition->name }}.
                @else
                    Create and edit law-specific simple or multiple-choice Q&amp;A for the selected working edition.
                @endif
            </p>
        </a>

        @if ($canManageMedia)
            <a class="result-card result-link-block" href="{{ route('admin.media.index') }}">
                <h2>Manage media</h2>
                <p class="law-meta">Maintain reusable image and video assets so nodes can select shared media instead of creating duplicates.</p>
            </a>
        @endif
    </section>
@endsection
