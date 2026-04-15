<div class="hub-nav-list">
    <a class="hub-nav-link @if (($currentKey ?? null) === 'laws') is-active @endif" href="{{ route('laws.list', array_filter(['lang' => $language, 'edition' => $lawsEditionQueryId ?? null], fn ($value) => $value !== null && $value !== '')) }}">
        {{ __('site.hub.laws_entry') }}
    </a>

    @foreach ($hubDocuments as $document)
        @php
            $documentRouteParameters = array_filter([
                'document' => $document,
                'lang' => $language,
                'edition' => $documentEditionQueryId ?? null,
            ], fn ($value) => $value !== null && $value !== '');
            $firstPage = $document->firstPublishedPage();
            $targetUrl = $document->isCollection() && $firstPage
                ? route('documents.page', array_merge($documentRouteParameters, ['page' => $firstPage->slug]))
                : route('documents.show', $documentRouteParameters);
        @endphp
        <a class="hub-nav-link @if (($currentKey ?? null) === 'document-'.$document->slug) is-active @endif" href="{{ $targetUrl }}">
            {{ $document->displayTitle($language) }}
        </a>
    @endforeach
</div>
