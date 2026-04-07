<div class="hub-nav-list">
    <a class="hub-nav-link @if (($currentKey ?? null) === 'laws') is-active @endif" href="{{ route('laws.index', ['lang' => $language]) }}">
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

    @if (($hubLaws ?? collect())->isNotEmpty())
        <div class="hub-nav-laws-group">
            <div class="hub-nav-group-title">{{ __('site.hub.laws_group_title') }}</div>
            <div class="hub-nav-law-list">
                @foreach ($hubLaws as $lawItem)
                    <a class="hub-nav-law-link" href="{{ route('laws.show', ['law' => $lawItem, 'lang' => $language]) }}">
                        {{ __('site.laws.law_number', ['number' => $lawItem->law_number]) }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
