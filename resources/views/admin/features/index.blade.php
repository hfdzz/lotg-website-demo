@extends('layouts.app')

@section('title', 'Admin | Public feature visibility')

@section('content')
    @php
        $configuredGlobalFeatures = collect($globalFeatureRows)->filter(fn (array $row) => $row['global_state'] !== null)->count();
        $enabledGlobalFeatures = collect($globalFeatureRows)->filter(fn (array $row) => $row['effective_state'])->count();
        $editionFeatureOverrides = collect($editionFeatureRows)->filter(fn (array $row) => $row['edition_state'] !== null)->count();
    @endphp

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Public feature visibility</h1>
        <p>Set site-wide public visibility defaults for LotG modules. Edition-specific overrides stay on the editions page.</p>
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
            <h2>Global public feature visibility</h2>
            <p class="law-meta">{{ $enabledGlobalFeatures }} enabled effective | {{ $configuredGlobalFeatures }} custom global override{{ $configuredGlobalFeatures === 1 ? '' : 's' }}</p>
        </summary>
        <div class="collapse-body">
            <p class="law-meta">Global settings are the baseline for all editions unless an edition explicitly overrides them.</p>

            <form action="{{ route('admin.public-features.update') }}" method="post" class="stack-form">
                @csrf
                @method('patch')
                @if ($selectedEdition)
                    <input type="hidden" name="edition" value="{{ $selectedEdition->id }}">
                @endif

                @foreach ($globalFeatureRows as $featureRow)
                    <label class="card surface-note">
                        <div>
                            <strong>{{ $featureRow['label'] }}</strong>
                            <p class="law-meta">{{ $featureRow['description'] }}</p>
                            <p class="law-meta">Default code state: {{ $featureRow['default_state'] ? 'Enabled' : 'Disabled' }} | Effective public state: {{ $featureRow['effective_state'] ? 'Enabled' : 'Disabled' }}</p>
                        </div>
                        <select name="features[{{ $featureRow['key'] }}]">
                            <option value="default" @selected($featureRow['global_state'] === null)>Use default ({{ $featureRow['default_state'] ? 'enabled' : 'disabled' }})</option>
                            <option value="enabled" @selected($featureRow['global_state'] === true)>Enabled</option>
                            <option value="disabled" @selected($featureRow['global_state'] === false)>Disabled</option>
                        </select>
                    </label>
                @endforeach

                <button type="submit">Save global public feature visibility</button>
            </form>
        </div>
    </details>

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Edition-specific visibility</h2>
            <p class="law-meta">{{ $editionFeatureOverrides }} override{{ $editionFeatureOverrides === 1 ? '' : 's' }} for {{ $selectedEdition?->name ?? 'the selected edition' }}</p>
        </summary>
        <div class="collapse-body">
            <p class="law-meta">Edition overrides are managed on the editions page so they stay close to the rest of that edition’s publishing workflow.</p>

            @if ($editions->isNotEmpty())
                <form action="{{ route('admin.editions.index') }}" method="get" class="stack-form">
                    <label>
                        <div class="law-meta">Edition</div>
                        <select name="edition">
                            @foreach ($editions as $edition)
                                <option value="{{ $edition->id }}" @selected($selectedEdition?->id === $edition->id)>{{ $edition->name }}@if ($edition->is_active) (active) @endif</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit">Open edition-specific settings</button>
                </form>

                @if ($selectedEdition)
                    <p class="stack-top">
                        <a class="result-link" href="{{ route('admin.editions.index', ['edition' => $selectedEdition->id]) }}">Go straight to {{ $selectedEdition->name }} edition settings</a>
                    </p>
                @endif
            @else
                <p class="empty-state">No editions yet.</p>
            @endif
        </div>
    </details>
@endsection
