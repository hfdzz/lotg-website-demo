<div class="hub-nav-list">
    @php
        $lawListParameters = array_filter([
            'lang' => $language,
            'edition' => $lawsEditionQueryId ?? null,
        ], fn ($value) => $value !== null && $value !== '');
        $hasLawChildren = isset($hubLaws) && $hubLaws->isNotEmpty();
    @endphp

    <div class="hub-nav-item">
        <div class="hub-nav-row">
            <a class="hub-nav-link @if (($currentKey ?? null) === 'laws') is-active @endif" href="{{ route('laws.index', $lawListParameters) }}">
                {{ __('site.hub.laws_entry') }}
            </a>

            @if ($hasLawChildren)
                <button
                    type="button"
                    class="hub-nav-expand-button"
                    aria-expanded="false"
                    aria-label="{{ __('site.hub.expand_section', ['title' => __('site.hub.laws_entry')]) }}"
                    data-hub-nav-toggle
                >
                    <span aria-hidden="true">+</span>
                </button>
            @endif
        </div>

        @if ($hasLawChildren)
            <div class="hub-nav-children" hidden data-hub-nav-panel>
                @foreach ($hubLaws as $hubLaw)
                    <a class="hub-nav-law-link @if (($currentLawId ?? null) === $hubLaw->id) is-active @endif" href="{{ route('laws.show', array_filter(['law' => $hubLaw, 'lang' => $language, 'edition' => $lawsEditionQueryId ?? null], fn ($value) => $value !== null && $value !== '')) }}">
                        {{ __('site.laws.law_number', ['number' => $hubLaw->law_number]) }}: {{ $hubLaw->displayTitle($language) }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

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
            $documentPages = $document->publishedPages ?? collect();
            $hasDocumentChildren = $document->isCollection() && $documentPages->isNotEmpty();
        @endphp

        <div class="hub-nav-item">
            <div class="hub-nav-row">
                <a class="hub-nav-link @if (($currentKey ?? null) === 'document-'.$document->slug) is-active @endif" href="{{ $targetUrl }}">
                    {{ $document->displayTitle($language) }}
                </a>

                @if ($hasDocumentChildren)
                    <button
                        type="button"
                        class="hub-nav-expand-button"
                        aria-expanded="false"
                        aria-label="{{ __('site.hub.expand_section', ['title' => $document->displayTitle($language)]) }}"
                        data-hub-nav-toggle
                    >
                        <span aria-hidden="true">+</span>
                    </button>
                @endif
            </div>

            @if ($hasDocumentChildren)
                <div class="hub-nav-children" hidden data-hub-nav-panel>
                    @foreach ($documentPages as $documentPage)
                        <a class="hub-nav-law-link @if (($currentKey ?? null) === 'document-'.$document->slug && ($currentDocumentPageSlug ?? null) === $documentPage->slug) is-active @endif" href="{{ route('documents.page', array_merge($documentRouteParameters, ['page' => $documentPage->slug])) }}">
                            {{ $documentPage->displayTitle($language) }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
