<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Edition;
use App\Services\LotgFeatureVisibility;
use App\Services\LotgPublicCache;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        protected LotgFeatureVisibility $featureVisibility,
        protected LotgPublicCache $publicCache
    ) {
    }

    public function show(Request $request, Document $document): View|RedirectResponse
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $document->load(['translations', 'publishedPages.translations', 'publishedPages.mediaAssets', 'edition']);

        if (
            ! $document->edition
            || ! $this->featureVisibility->enabled(LotgFeatureVisibility::FEATURE_DOCUMENTS, $document->edition)
            || $document->status !== 'published'
            || $document->edition->status !== 'published'
        ) {
            return $this->redirectToLawListing($language, $document->edition);
        }

        $activeEditionId = Edition::current()?->id;
        $editionQueryId = (int) $document->edition_id !== (int) $activeEditionId
            ? $document->edition_id
            : null;

        if ($document->isCollection()) {
            $firstPage = $document->firstPublishedPage();

            if (! $firstPage) {
                return redirect()->route('laws.index', ['lang' => $language]);
            }

            return redirect()->route('documents.page', [
                ...array_filter([
                    'document' => $document,
                    'page' => $firstPage->slug,
                    'lang' => $language,
                    'edition' => $editionQueryId,
                ], fn ($value) => $value !== null && $value !== ''),
            ]);
        }

        return view('documents.show', [
            'document' => $document,
            'page' => $document->firstPublishedPage(),
            'pages' => collect(),
            'language' => $language,
            'editionQueryId' => $editionQueryId,
            ...$this->hubData($document->edition),
        ]);
    }

    public function page(Request $request, Document $document, string $page): View|RedirectResponse
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $document->load(['translations', 'publishedPages.translations', 'publishedPages.mediaAssets', 'edition']);

        if (
            ! $document->edition
            || ! $this->featureVisibility->enabled(LotgFeatureVisibility::FEATURE_DOCUMENTS, $document->edition)
            || $document->status !== 'published'
            || $document->edition->status !== 'published'
        ) {
            return $this->redirectToLawListing($language, $document->edition);
        }

        $activeEditionId = Edition::current()?->id;
        $editionQueryId = (int) $document->edition_id !== (int) $activeEditionId
            ? $document->edition_id
            : null;

        if (! $document->isCollection()) {
            return redirect()->route('documents.show', array_filter([
                'document' => $document,
                'lang' => $language,
                'edition' => $editionQueryId,
            ], fn ($value) => $value !== null && $value !== ''));
        }

        $selectedPage = $document->publishedPages->firstWhere('slug', $page);

        if (! $selectedPage) {
            return redirect()->route('documents.show', array_filter([
                'document' => $document,
                'lang' => $language,
                'edition' => $editionQueryId,
            ], fn ($value) => $value !== null && $value !== ''));
        }

        return view('documents.show', [
            'document' => $document,
            'page' => $selectedPage,
            'pages' => $document->publishedPages,
            'language' => $language,
            'editionQueryId' => $editionQueryId,
            ...$this->hubData($document->edition),
        ]);
    }

    protected function hubData(Edition $edition): array
    {
        return [
            'hubDocuments' => $this->featureVisibility->enabled(LotgFeatureVisibility::FEATURE_DOCUMENTS, $edition)
                ? $this->publicCache->orderedPublishedDocuments($edition->id, ['translations', 'publishedPages.translations'])
                : collect(),
        ];
    }

    protected function redirectToLawListing(string $language, ?Edition $edition): RedirectResponse
    {
        $activeEditionId = Edition::current()?->id;

        if ($edition && (! $activeEditionId || (int) $edition->id !== (int) $activeEditionId)) {
            return redirect()->route('laws.list', [
                'lang' => $language,
                'edition' => $edition->id,
            ]);
        }

        return redirect()->route('laws.index', ['lang' => $language]);
    }
}
