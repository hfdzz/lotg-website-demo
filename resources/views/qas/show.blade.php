@extends('layouts.app')

@section('title', __('site.qas.title').' | '.__('site.laws.law_number', ['number' => $law->law_number]))

@section('content')
    <a class="back-link" href="{{ route('qas.index', ['lang' => $language]) }}">{{ __('site.qas.back_to_law_picker') }}</a>

    <section class="hero">
        <p class="eyebrow">{{ __('site.qas.eyebrow') }}</p>
        <h1>{{ __('site.laws.law_number', ['number' => $law->law_number]) }}</h1>
        <p class="hero-edition-name">{{ $law->displayTitle($language) }}</p>
        @if ($law->displaySubtitle($language))
            <p>{{ $law->displaySubtitle($language) }}</p>
        @endif
    </section>

    @if ($qas->isEmpty())
        <section class="card">
            <h2>{{ __('site.qas.empty_title') }}</h2>
            <p class="law-meta">{{ __('site.qas.empty_law_body') }}</p>
        </section>
    @else
        <section class="law-qa-list">
            @foreach ($qas as $qa)
                @php
                    $qaOptions = $qa->optionsForDisplay($language);
                @endphp
                <details class="law-qa-item" id="law-qa-{{ $qa->id }}">
                    <summary class="law-qa-summary">
                        <span class="law-qa-question">{{ $qa->displayQuestion($language) }}</span>
                    </summary>
                    <div class="law-qa-answer node-body">
                        @if ($qa->isMultipleChoice() && $qaOptions->isNotEmpty())
                            <ol class="law-qa-options">
                                @foreach ($qaOptions as $option)
                                    <li @class(['is-correct' => $option['is_correct']])>{{ $option['text'] }}</li>
                                @endforeach
                            </ol>
                        @endif
                        @if (! ($qa->isMultipleChoice() && ! $qa->uses_custom_answer && $qaOptions->isNotEmpty()))
                            {!! $qa->displayAnswer($language) !!}
                        @endif
                    </div>
                </details>
            @endforeach
        </section>
    @endif
@endsection
