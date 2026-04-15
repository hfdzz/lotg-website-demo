@extends('layouts.app')

@section('title', $page?->displayTitle($language) ? $page->displayTitle($language).' | '.$document->displayTitle($language) : $document->displayTitle($language))
@section('mobile_header_title', $document->displayTitle($language))

@section('content')
    <a class="back-link" href="{{ route('laws.index', ['lang' => $language]) }}">{{ __('site.laws.back') }}</a>

    <div class="lotg-hub-layout">
        @include('laws.partials.hub-nav', [
            'hubDocuments' => $hubDocuments,
            'language' => $language,
            'lawsEditionQueryId' => $editionQueryId ?? null,
            'documentEditionQueryId' => $editionQueryId ?? null,
            'currentKey' => 'document-'.$document->slug,
        ])

        <div class="lotg-hub-main">
            <section class="hero">
                <p class="eyebrow">{{ __('site.documents.eyebrow') }}</p>
                <h1>{{ $document->displayTitle($language) }}</h1>
                @if ($document->isCollection() && $page)
                    <p>{{ $page->displayTitle($language) }}</p>
                @else
                    <p>{{ __('site.documents.intro') }}</p>
                @endif
            </section>

            @if ($pages->isNotEmpty())
                <nav class="document-page-nav card">
                    <h2>{{ __('site.documents.pages') }}</h2>
                    <div class="document-page-links">
                        @foreach ($pages as $documentPage)
                            <a class="document-page-link @if ($page && $page->id === $documentPage->id) is-active @endif" href="{{ route('documents.page', array_filter(['document' => $document, 'page' => $documentPage->slug, 'lang' => $language, 'edition' => $editionQueryId ?? null], fn ($value) => $value !== null && $value !== '')) }}">
                                {{ $documentPage->displayTitle($language) }}
                            </a>
                        @endforeach
                    </div>
                </nav>
            @endif

            <article class="document-article">
                @if ($page?->displayBody($language))
                    <div class="node-body">{!! $page->displayBody($language) !!}</div>
                @else
                    <p class="law-meta">{{ __('site.documents.empty') }}</p>
                @endif
            </article>
        </div>
    </div>
@endsection
