@extends('layouts.app')

@section('title', __('site.laws.index_title'))

@section('content')
    <section class="hero">
        <p class="eyebrow">{{ __('site.laws.index_eyebrow') }}</p>
        <h1>{{ __('site.laws.index_title') }}</h1>
        <p>{{ __('site.laws.index_intro') }}</p>
    </section>

    <section class="lotg-landing-list">
        @if (! $hasActiveEdition)
            <div class="card">
                <h2>{{ __('site.laws.unavailable_title') }}</h2>
                <p class="law-meta">{{ __('site.laws.unavailable_body') }}</p>
            </div>
        @else
            <a class="card lotg-landing-link" href="{{ route('laws.list', ['lang' => $language]) }}">
                <h2>{{ __('site.hub.laws_entry') }}</h2>
                <p class="law-meta">{{ __('site.hub.laws_intro') }}</p>
            </a>

            @foreach ($hubDocuments as $document)
                @php
                    $firstPage = $document->firstPublishedPage();
                    $targetUrl = $document->isCollection() && $firstPage
                        ? route('documents.page', ['document' => $document, 'page' => $firstPage->slug, 'lang' => $language])
                        : route('documents.show', ['document' => $document, 'lang' => $language]);
                @endphp

                @if ($document->isCollection() && $document->publishedPages->isNotEmpty())
                    <details class="card lotg-landing-item">
                        <summary class="lotg-landing-summary">
                            <div>
                                <h2>{{ $document->title }}</h2>
                                <p class="law-meta">{{ __('site.documents.collection_intro') }}</p>
                            </div>
                        </summary>
                        <div class="lotg-landing-children">
                            @foreach ($document->publishedPages as $documentPage)
                                <a class="hub-nav-law-link" href="{{ route('documents.page', ['document' => $document, 'page' => $documentPage->slug, 'lang' => $language]) }}">
                                    {{ $documentPage->title }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @else
                    <a class="card lotg-landing-link" href="{{ $targetUrl }}">
                        <h2>{{ $document->title }}</h2>
                        <p class="law-meta">{{ __('site.documents.single_intro') }}</p>
                    </a>
                @endif
            @endforeach
        @endif
    </section>

    @if ($otherPublishedEditions->isNotEmpty())
        <div class="card edition-browser">
            <h2>{{ __('site.editions.more_title') }}</h2>
            <p class="law-meta">{{ __('site.editions.more_body') }}</p>
            <p class="stack-top">
                <a class="result-link" href="{{ route('editions.index', ['lang' => $language]) }}">{{ __('site.editions.browse_link') }}</a>
            </p>
        </div>
    @endif
@endsection
