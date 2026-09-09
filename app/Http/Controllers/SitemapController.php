<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\Edition;
use App\Models\Law;
use App\Services\LotgFeatureVisibility;
use App\Services\LotgPublicCache;
use App\Support\LotgLanguage;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        protected LotgFeatureVisibility $featureVisibility,
        protected LotgPublicCache $publicCache
    ) {
    }

    public function __invoke(): Response
    {
        $urls = collect();

        foreach (array_keys(LotgLanguage::supported()) as $language) {
            $urls->push($this->url(route('laws.index', ['lang' => $language]), 'daily', '1.0'));
            $urls->push($this->url(route('laws.list', ['lang' => $language]), 'daily', '0.9'));
            $urls->push($this->url(route('editions.index', ['lang' => $language]), 'weekly', '0.5'));
        }

        $publishedEditions = $this->publicCache->publishedEditions();
        $activeEditionId = Edition::current()?->id;

        foreach ($publishedEditions as $edition) {
            foreach (array_keys(LotgLanguage::supported()) as $language) {
                if ((int) $edition->id !== (int) $activeEditionId) {
                    $urls->push($this->url(route('laws.list', [
                        'edition' => $edition->id,
                        'lang' => $language,
                    ]), 'weekly', '0.6'));
                }

                $this->publicCache->orderedPublishedLaws($edition->id)
                    ->each(function (Law $law) use ($urls, $edition, $activeEditionId, $language): void {
                        $urls->push($this->url(route('laws.show', array_filter([
                            'law' => $law,
                            'edition' => (int) $edition->id !== (int) $activeEditionId ? $edition->id : null,
                            'lang' => $language,
                        ], fn ($value) => $value !== null && $value !== '')), 'weekly', '0.8', $law->updated_at));
                    });

                if ($this->featureVisibility->enabled(LotgFeatureVisibility::FEATURE_DOCUMENTS, $edition)) {
                    $this->publicCache->orderedPublishedDocuments($edition->id, ['publishedPages'])
                        ->each(function (Document $document) use ($urls, $edition, $activeEditionId, $language): void {
                            if ($document->isCollection()) {
                                $document->publishedPages->each(function (DocumentPage $page) use ($urls, $document, $edition, $activeEditionId, $language): void {
                                    $urls->push($this->url(route('documents.page', array_filter([
                                        'document' => $document,
                                        'page' => $page->slug,
                                        'edition' => (int) $edition->id !== (int) $activeEditionId ? $edition->id : null,
                                        'lang' => $language,
                                    ], fn ($value) => $value !== null && $value !== '')), 'weekly', '0.7', $page->updated_at));
                                });

                                return;
                            }

                            $urls->push($this->url(route('documents.show', array_filter([
                                'document' => $document,
                                'edition' => (int) $edition->id !== (int) $activeEditionId ? $edition->id : null,
                                'lang' => $language,
                            ], fn ($value) => $value !== null && $value !== '')), 'weekly', '0.7', $document->updated_at));
                        });
                }
            }
        }

        foreach (array_keys(LotgLanguage::supported()) as $language) {
            if ($this->featureVisibility->availableForAnyPublishedEdition(LotgFeatureVisibility::FEATURE_LEGACY_UPDATES)) {
                $urls->push($this->url(route('updates.index', ['lang' => $language]), 'weekly', '0.5'));
            }

            if ($this->featureVisibility->enabled(LotgFeatureVisibility::FEATURE_QAS, Edition::current())) {
                $urls->push($this->url(route('qas.index', ['lang' => $language]), 'weekly', '0.5'));
            }
        }

        return response()
            ->view('sitemap', ['urls' => $urls->unique('loc')->values()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    protected function url(string $loc, string $changefreq, string $priority, mixed $lastmod = null): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod?->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
