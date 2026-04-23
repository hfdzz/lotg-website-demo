@extends('layouts.app')

@section('title', 'Admin | Law Q&A')

@section('content')
    <a class="back-link" href="{{ route('admin.qas.index', ['edition' => $selectedEdition]) }}">Back to Q&amp;A laws</a>

    <section class="hero">
        <p class="eyebrow">Admin Q&amp;A</p>
        <h1>{{ __('site.laws.law_number', ['number' => $law->law_number]) }}: {{ $law->displayTitle('id') }}</h1>
        <p>Create, reorder, publish, and edit simple or multiple-choice Q&amp;A for this law.</p>
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

    @include('admin.partials.edition-switcher', ['editions' => $editions, 'selectedEdition' => $selectedEdition, 'editionSwitcherTarget' => 'qas'])

    <details class="card collapse-card" @if($errors->any()) open @endif>
        <summary class="collapse-summary">
            <h2>Create Q&amp;A</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.qas.store', ['edition' => $selectedEdition, 'law' => $law]) }}" method="post" class="stack-form" data-qa-editor>
                @csrf
                @include('admin.partials.qa-fields', ['qa' => null, 'translationsByLanguage' => collect(), 'languages' => $languages])
                <button type="submit">Create Q&amp;A</button>
            </form>
        </div>
    </details>

    <section class="card">
        <h2>Existing Q&amp;A</h2>
        <div class="stack-top qa-admin-list">
            @forelse ($qas as $qa)
                <article class="result-card">
                    <h3>{{ $qa->displayQuestion('id') }}</h3>
                    <p class="law-meta">Sort {{ $qa->sort_order }} | {{ $qa->isMultipleChoice() ? 'Multiple choice' : 'Simple' }} | {{ $qa->is_published ? 'Published' : 'Draft' }}</p>
                    <p class="stack-top">
                        <a class="result-link" href="{{ route('admin.qas.edit', ['edition' => $selectedEdition, 'law' => $law, 'qa' => $qa]) }}">Edit Q&amp;A</a>
                    </p>
                </article>
            @empty
                <p class="empty-state">No Q&amp;A items yet for this law.</p>
            @endforelse
        </div>
    </section>
@endsection
