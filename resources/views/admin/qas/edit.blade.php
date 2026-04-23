@extends('layouts.app')

@section('title', 'Admin | Edit Q&A')

@section('content')
    <a class="back-link" href="{{ route('admin.qas.law', ['edition' => $selectedEdition, 'law' => $law]) }}">Back to law Q&amp;A</a>

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Edit Q&amp;A</h1>
        <p>Update question, answer, order, and published state for this law-specific Q&amp;A item.</p>
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

    <section class="card">
        <h2>{{ __('site.laws.law_number', ['number' => $law->law_number]) }}: {{ $law->displayTitle() }}</h2>
        <p class="law-meta">{{ $qa->displayQuestion() }}</p>

        <form action="{{ route('admin.qas.update', ['edition' => $selectedEdition, 'law' => $law, 'qa' => $qa]) }}" method="post" class="stack-form" data-qa-editor>
            @csrf
            @method('patch')
            @include('admin.partials.qa-fields', ['qa' => $qa, 'translationsByLanguage' => $translationsByLanguage, 'languages' => $languages])
            <button type="submit">Save Q&amp;A</button>
        </form>

        <form action="{{ route('admin.qas.destroy', ['edition' => $selectedEdition, 'law' => $law, 'qa' => $qa]) }}" method="post" class="stack-top" data-confirm-message="Delete this Q&amp;A item?">
            @csrf
            @method('delete')
            <button type="submit" class="button-danger">Delete Q&amp;A</button>
        </form>
    </section>
@endsection
