@extends('layouts.app')

@section('title', $page?->title ? $page->title.' | '.$document->title : $document->title)
@section('mobile_header_title', $document->title)

@section('content')
    <a class="back-link" href="{{ route('laws.index', ['lang' => $language]) }}">{{ __('site.laws.back') }}</a>

    <div class="lotg-hub-layout">
        @include('laws.partials.hub-nav', [
            'hubDocuments' => $hubDocuments,
            'language' => $language,
            'currentKey' => 'document-'.$document->slug,
        ])

        <div class="lotg-hub-main">
            <section class="hero">
                <p class="eyebrow">{{ __('site.documents.eyebrow') }}</p>
                <h1>{{ $document->title }}</h1>
                @if ($document->isCollection() && $page)
                    <p>{{ $page->title }}</p>
                @else
                    <p>{{ __('site.documents.intro') }}</p>
                @endif
            </section>

            @if ($pages->isNotEmpty())
                <nav class="document-page-nav card">
                    <h2>{{ __('site.documents.pages') }}</h2>
                    <div class="document-page-links">
                        @foreach ($pages as $documentPage)
                            <a class="document-page-link @if ($page && $page->id === $documentPage->id) is-active @endif" href="{{ route('documents.page', ['document' => $document, 'page' => $documentPage->slug, 'lang' => $language]) }}">
                                {{ $documentPage->title }}
                            </a>
                        @endforeach
                    </div>
                </nav>
            @endif

            <article class="document-article">
                @if ($page?->body_html)
                    <div class="node-body">{!! $page->body_html !!}</div>
                @else
                    <p class="law-meta">{{ __('site.documents.empty') }}</p>
                @endif
            </article>
        </div>
    </div>
@endsection
