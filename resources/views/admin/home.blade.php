@extends('layouts.app')

@section('title', 'Admin')

@section('content')
    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Content management</h1>
        <p>Choose which LotG area you want to manage.</p>
    </section>

    <section class="result-list">
        <a class="result-card result-link-block" href="{{ route('admin.laws.home') }}">
            <h2>Manage laws</h2>
            <p class="law-meta">Edit editions, laws, nodes, law Q&amp;A, and law change entries.</p>
        </a>

        <a class="result-card result-link-block" href="{{ route('admin.documents.index') }}">
            <h2>Manage documents</h2>
            <p class="law-meta">Edit supporting LotG documents such as VAR Protocol, Glossary, and Guidelines.</p>
        </a>
    </section>
@endsection
