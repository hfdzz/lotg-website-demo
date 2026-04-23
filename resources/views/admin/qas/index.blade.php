@extends('layouts.app')

@section('title', 'Admin | Q&A')

@section('content')
    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Manage Q&amp;A</h1>
        <p>Choose a law, then manage its simple or multiple-choice Q&amp;A items.</p>
        <p><a class="result-link" href="{{ route('admin.home') }}">Back to admin</a></p>
    </section>

    @if (session('status'))
        <div class="card surface-note flash-message flash-message-success">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    @include('admin.partials.edition-switcher', ['editions' => $editions, 'selectedEdition' => $selectedEdition, 'editionSwitcherTarget' => 'qas'])

    <section class="card section-card">
        <p class="law-meta">Working edition: <strong>{{ $selectedEdition->name }}</strong></p>
    </section>

    <section class="card">
        <h2>Laws</h2>
        <div class="result-list stack-top">
            @forelse ($laws as $law)
                <article class="result-card">
                    <p class="eyebrow">Law {{ $law->law_number }}</p>
                    <h3>{{ $law->displayTitle('id') }}</h3>
                    <p class="law-meta">{{ $law->qas_count }} Q&amp;A item{{ $law->qas_count === 1 ? '' : 's' }} | Status: {{ $law->status }}</p>
                    <p class="stack-top">
                        <a class="result-link" href="{{ route('admin.qas.law', ['edition' => $selectedEdition, 'law' => $law]) }}">Manage Q&amp;A</a>
                    </p>
                </article>
            @empty
                <p class="empty-state">No laws yet for this edition.</p>
            @endforelse
        </div>
    </section>
@endsection
