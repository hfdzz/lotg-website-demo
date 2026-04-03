@extends('layouts.app')

@section('title', 'Admin | Updates')

@section('content')
    <a class="back-link" href="{{ route('admin.laws.index', ['edition' => $edition]) }}">Back to admin laws</a>

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Update entries for {{ $edition->name }}</h1>
        <p>Manage the public changelog entries for the current working edition.</p>
    </section>

    @if (session('status'))
        <div class="card surface-note">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Create update entry</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.changelog.store', ['edition' => $edition]) }}" method="post" class="stack-form">
                @csrf
                <label>
                    <div class="law-meta">Language</div>
                    <select name="language_code">
                        @foreach ($languages as $languageCode => $languageLabel)
                            <option value="{{ $languageCode }}">{{ strtoupper($languageCode) }} - {{ $languageLabel }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <div class="law-meta">Title</div>
                    <input type="text" name="title" value="{{ old('title') }}">
                </label>
                <label>
                    <div class="law-meta">Body</div>
                    <textarea name="body" rows="5">{{ old('body') }}</textarea>
                </label>
                <label>
                    <div class="law-meta">Sort order</div>
                    <input type="number" min="0" name="sort_order" value="{{ old('sort_order', 0) }}">
                </label>
                <label>
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true))>
                    Publish immediately
                </label>
                <button type="submit">Create update entry</button>
            </form>
        </div>
    </details>

    <section class="card">
        <h2>Existing update entries</h2>
        <div class="stack-top">
            @forelse ($entries as $entry)
                <details class="result-card collapse-card">
                    <summary class="collapse-summary">
                        <h3>{{ $entry->title }} ({{ strtoupper($entry->language_code) }})</h3>
                    </summary>
                    <div class="collapse-body">
                        <form action="{{ route('admin.changelog.update', ['edition' => $edition, 'entry' => $entry]) }}" method="post" class="stack-form">
                            @csrf
                            @method('patch')
                            <label>
                                <div class="law-meta">Language</div>
                                <select name="language_code">
                                    @foreach ($languages as $languageCode => $languageLabel)
                                        <option value="{{ $languageCode }}" @selected($entry->language_code === $languageCode)>{{ strtoupper($languageCode) }} - {{ $languageLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <div class="law-meta">Title</div>
                                <input type="text" name="title" value="{{ $entry->title }}">
                            </label>
                            <label>
                                <div class="law-meta">Body</div>
                                <textarea name="body" rows="5">{{ $entry->body }}</textarea>
                            </label>
                            <label>
                                <div class="law-meta">Sort order</div>
                                <input type="number" min="0" name="sort_order" value="{{ $entry->sort_order }}">
                            </label>
                            <label>
                                <input type="checkbox" name="is_published" value="1" @checked($entry->published_at)>
                                Published
                            </label>
                            <button type="submit">Save update entry</button>
                        </form>
                    </div>
                </details>
            @empty
                <p class="empty-state">No update entries yet for this edition.</p>
            @endforelse
        </div>
    </section>
@endsection
