<div class="hub-nav-list">
    <a class="hub-nav-link @if (($currentKey ?? null) === 'laws') is-active @endif" href="{{ route('laws.list', ['lang' => $language]) }}">
        {{ __('site.hub.laws_entry') }}
    </a>

    @foreach ($hubDocuments as $document)
        @php
            $firstPage = $document->firstPublishedPage();
            $targetUrl = $document->isCollection() && $firstPage
                ? route('documents.page', ['document' => $document, 'page' => $firstPage->slug, 'lang' => $language])
                : route('documents.show', ['document' => $document, 'lang' => $language]);
        @endphp
        <a class="hub-nav-link @if (($currentKey ?? null) === 'document-'.$document->slug) is-active @endif" href="{{ $targetUrl }}">
            {{ $document->title }}
        </a>
    @endforeach
</div>
