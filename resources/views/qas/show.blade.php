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
                    $answerHtml = $qa->displayAnswer($language);
                @endphp
                <article class="law-qa-item" id="law-qa-{{ $qa->id }}">
                    <h2 class="law-qa-question">{{ $qa->displayQuestion($language) }}</h2>

                    @if ($qa->isMultipleChoice() && $qaOptions->isNotEmpty())
                        <ul class="law-qa-options">
                            @foreach ($qaOptions as $option)
                                <li><span class="law-qa-option-label">{{ $option['label'] }}.</span> {{ $option['text'] }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if (filled($answerHtml))
                        <details class="law-qa-answer-toggle">
                            <summary class="law-qa-answer-summary">
                                <span class="law-qa-answer-summary-show">{{ __('site.qas.show_answer') }}</span>
                                <span class="law-qa-answer-summary-hide">{{ __('site.qas.hide_answer') }}</span>
                            </summary>
                            <div class="law-qa-answer node-body">
                                {!! $answerHtml !!}
                            </div>
                        </details>
                    @endif
                </article>
            @endforeach
        </section>
    @endif
@endsection
