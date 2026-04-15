@extends('layouts.app')

@section('title', 'Admin | Editions')

@section('content')
    @php
        $defaultYearStart = (int) now()->format('Y');
        $defaultYearEnd = $defaultYearStart + 1;
        $copyEditionDefault = old('copy_from_edition_id', $editions->first()?->id);
    @endphp

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Manage editions</h1>
        <p>Create, activate, and prepare editions. New editions can optionally copy laws, nodes, and Q&amp;A from an existing edition so only changed content needs follow-up edits.</p>
        <p><a class="result-link" href="{{ route('admin.home') }}">Back to admin</a></p>
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

    <details class="card collapse-card" open>
        <summary class="collapse-summary">
            <h2>All editions</h2>
        </summary>
        <div class="collapse-body">
            <div class="result-list">
                @forelse ($editions as $edition)
                    <article class="result-card @if ($edition->is_active) active-edition @endif">
                        <h3>{{ $edition->name }}</h3>
                        <p class="law-meta">Code: {{ $edition->code }} | Years: {{ $edition->year_start }}/{{ $edition->year_end }} | Publication: {{ $edition->status }} | Active: {{ $edition->is_active ? 'yes' : 'no' }}</p>
                        <p class="law-meta">
                            <a class="result-link" href="{{ route('admin.laws.index', ['edition' => $edition]) }}">Manage laws in this edition</a>
                            | <a class="result-link" href="{{ route('admin.editions.index', ['edition' => $edition->id]) }}">Edit this edition</a>
                        </p>
                        @php
                            $readiness = $readinessReports[$edition->id] ?? null;
                            $readinessStatus = match ($readiness['overall_status'] ?? 'fail') {
                                'pass' => 'ready',
                                'warn' => 'warn',
                                default => 'blocked',
                            };
                        @endphp
                        @if ($readiness)
                            <details class="edition-readiness">
                                <summary class="edition-readiness-summary edition-readiness-summary-{{ $readinessStatus }}">
                                    <span class="edition-readiness-heading">Edition Completeness</span>
                                    <span class="edition-readiness-pill edition-readiness-pill-{{ $readinessStatus }}">
                                        @if (($readiness['overall_status'] ?? null) === 'pass')
                                            Complete
                                        @elseif (($readiness['overall_status'] ?? null) === 'warn')
                                            {{ $readiness['warning_count'] }} warning{{ $readiness['warning_count'] === 1 ? '' : 's' }}
                                        @else
                                            {{ $readiness['blocking_count'] }} missing / blocking
                                        @endif
                                    </span>
                                    <span class="edition-readiness-copy">{{ $readiness['summary'] }}</span>
                                </summary>
                                <div class="edition-readiness-body">
                                    <p class="law-meta">{{ $readiness['readiness_note'] }}</p>
                                    <div class="edition-readiness-checks">
                                        @foreach ($readiness['checks'] as $check)
                                            <article class="edition-readiness-check" data-status="{{ $check['status'] }}">
                                                <div class="edition-readiness-check-header">
                                                    <strong>{{ $check['label'] }}</strong>
                                                    <span class="edition-readiness-check-status">{{ strtoupper($check['status']) }}</span>
                                                </div>
                                                <p class="law-meta">{{ $check['summary'] }}</p>
                                                @if (! empty($check['details']))
                                                    <ul class="edition-readiness-details">
                                                        @foreach ($check['details'] as $detail)
                                                            <li>{{ $detail }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif
                        @if (! $edition->is_active)
                            <form action="{{ route('admin.editions.activate', $edition) }}" method="post" class="inline-form">
                                @csrf
                                <button type="submit">Set as active</button>
                            </form>
                            @if (($readiness['blocking_count'] ?? 0) > 0)
                                @can('forceActivate', $edition)
                                    <form action="{{ route('admin.editions.force-activate', $edition) }}" method="post" class="inline-form" data-confirm-message="Force activate this edition and bypass blocking completeness checks?">
                                        @csrf
                                        <button type="submit" class="button-secondary">Force set as active</button>
                                    </form>
                                @endcan
                            @endif
                        @endif
                    </article>
                @empty
                    <p class="empty-state">No editions yet.</p>
                @endforelse
            </div>
        </div>
    </details>

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Create edition</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.editions.store') }}" method="post" class="stack-form">
                @csrf
                <label>
                    <div class="law-meta">Name</div>
                    <input type="text" name="name" value="{{ old('name') }}" data-edition-name-input>
                </label>
                <label>
                    <div class="law-meta">Code</div>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="Auto: edition-name" data-edition-code-input>
                </label>
                <label>
                    <div class="law-meta">Year start</div>
                    <input type="number" name="year_start" value="{{ old('year_start', $defaultYearStart) }}">
                </label>
                <label>
                    <div class="law-meta">Year end</div>
                    <input type="number" name="year_end" value="{{ old('year_end', $defaultYearEnd) }}">
                </label>
                <label>
                    <div class="law-meta">Status</div>
                    <select name="status">
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                        <option value="published" @selected(old('status') === 'published')>Published</option>
                    </select>
                </label>
                <label>
                    <div class="law-meta">Copy content from edition</div>
                    <select name="copy_from_edition_id">
                        <option value="">Do not copy</option>
                        @foreach ($editions as $edition)
                            <option value="{{ $edition->id }}" @selected((string) $copyEditionDefault === (string) $edition->id)>{{ $edition->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit">Create edition</button>
            </form>
        </div>
    </details>

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>
                @if ($selectedEdition)
                    Edit edition: {{ $selectedEdition->name }} (code: {{ $selectedEdition->code }})
                @else
                    Edit edition
                @endif
            </h2>
        </summary>
        <div class="collapse-body">
            @if ($selectedEdition)
                <form action="{{ route('admin.editions.index') }}" method="get" class="stack-form">
                    <label>
                        <div class="law-meta">Edition</div>
                        <select name="edition" onchange="this.form.submit()">
                            @foreach ($editions as $edition)
                                <option value="{{ $edition->id }}" @selected($selectedEdition?->id === $edition->id)>{{ $edition->name }}@if ($edition->is_active) (active) @endif</option>
                            @endforeach
                        </select>
                    </label>
                </form>

                <form action="{{ route('admin.editions.update', $selectedEdition) }}" method="post" class="stack-form">
                    @csrf
                    @method('patch')
                    <label>
                        <div class="law-meta">Name</div>
                        <input type="text" name="name" value="{{ old('name', $selectedEdition->name) }}" data-edition-name-input>
                    </label>
                    <label>
                        <div class="law-meta">Code</div>
                        <input type="text" name="code" value="{{ old('code', $selectedEdition->code) }}" placeholder="Auto: {{ \Illuminate\Support\Str::slug($selectedEdition->name) ?: 'edition' }}" data-edition-code-input>
                    </label>
                    <label>
                        <div class="law-meta">Year start</div>
                        <input type="number" name="year_start" value="{{ old('year_start', $selectedEdition->year_start) }}">
                    </label>
                    <label>
                        <div class="law-meta">Year end</div>
                        <input type="number" name="year_end" value="{{ old('year_end', $selectedEdition->year_end) }}">
                    </label>
                    <label>
                        <div class="law-meta">Status</div>
                        <select name="status">
                            <option value="draft" @selected(old('status', $selectedEdition->status) === 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $selectedEdition->status) === 'published')>Published</option>
                        </select>
                    </label>
                    @if ($selectedEdition->is_active)
                        <p class="law-meta">This is the active edition.</p>
                    @else
                        <button type="submit" name="set_active" value="1">Save and set as active</button>
                    @endif
                    <button type="submit">Save edition</button>
                </form>

                @if (! $selectedEdition->is_active)
                    <form action="{{ route('admin.editions.destroy', $selectedEdition) }}" method="post" class="stack-top" data-confirm-message="Delete this edition? This only works for empty editions.">
                        @csrf
                        @method('delete')
                        <button type="submit" class="button-danger">Delete edition</button>
                    </form>
                @endif
            @else
                <p class="empty-state">No edition selected yet.</p>
            @endif
        </div>
    </details>
@endsection
