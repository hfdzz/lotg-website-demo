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
    </section>
@endsection
