<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Edition;
use App\Models\Law;
use App\Services\LawTreeBuilder;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LawController extends Controller
{
    public function hub(Request $request): View
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $activeEdition = Edition::current();
        $publishedEditions = Edition::query()
            ->published()
            ->orderByDesc('year_start')
            ->orderByDesc('year_end')
            ->get();

        return view('laws.index', [
            'hasActiveEdition' => (bool) $activeEdition,
            'activeEdition' => $activeEdition,
            'otherPublishedEditions' => $publishedEditions
                ->reject(fn (Edition $edition) => $activeEdition && $edition->id === $activeEdition->id)
                ->values(),
            'hubDocuments' => $activeEdition
                ? Document::query()
                    ->published()
                    ->forEdition($activeEdition->id)
                    ->with('publishedPages')
                    ->orderBy('sort_order')
                    ->get()
                : collect(),
            'language' => $language,
        ]);
    }

    public function index(Request $request): View
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $activeEdition = Edition::current();
        $publishedEditions = Edition::query()
            ->published()
            ->orderByDesc('year_start')
            ->orderByDesc('year_end')
            ->get();
        $requestedEditionId = $request->integer('edition');
        $selectedEdition = $requestedEditionId
            ? $publishedEditions->firstWhere('id', $requestedEditionId)
            : $activeEdition;
        $isArchiveEdition = (bool) $selectedEdition && (! $activeEdition || $selectedEdition->id !== $activeEdition->id);

        if ($isArchiveEdition) {
            return view('laws.archive', [
                'laws' => Law::published()
                    ->forEdition($selectedEdition?->id)
                    ->with('translations')
                    ->orderBy('sort_order')
                    ->get(),
                'hasActiveEdition' => (bool) $activeEdition,
                'activeEdition' => $activeEdition,
                'selectedEdition' => $selectedEdition,
                'language' => $language,
            ]);
        }

        $laws = Law::published()
            ->forEdition($activeEdition?->id)
            ->with('translations')
            ->orderBy('sort_order')
            ->get();

        return view('laws.list', [
            'laws' => $laws,
            'hasActiveEdition' => (bool) $activeEdition,
            'activeEdition' => $activeEdition,
            'otherPublishedEditions' => $publishedEditions
                ->reject(fn (Edition $edition) => $activeEdition && $edition->id === $activeEdition->id)
                ->values(),
            'language' => $language,
        ]);
    }

    public function editions(Request $request): View
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $activeEdition = Edition::current();
        $publishedEditions = Edition::query()
            ->published()
            ->orderByDesc('year_start')
            ->orderByDesc('year_end')
            ->get();

        return view('laws.editions', [
            'language' => $language,
            'activeEdition' => $activeEdition,
            'editions' => $publishedEditions,
        ]);
    }

    public function show(Request $request, Law $law, LawTreeBuilder $treeBuilder): View|RedirectResponse
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $law->loadMissing(['translations', 'edition']);

        if (! $law->edition || $law->status !== 'published' || $law->edition->status !== 'published') {
            return redirect()->route('laws.index', ['lang' => $language]);
        }

        $tree = $treeBuilder->build($law, $language);
        $orderedLaws = Law::published()
            ->forEdition($law->edition_id)
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'law_number', 'slug', 'sort_order', 'status']);
        $currentIndex = $orderedLaws->search(fn (Law $item) => $item->id === $law->id);
        $editionQueryId = $this->publicEditionQueryIdForLaw($law);

        return view('laws.show', [
            'law' => $law,
            'language' => $language,
            'tree' => $tree,
            'tableOfContents' => $treeBuilder->buildTableOfContents($tree),
            'jumpLaws' => $orderedLaws,
            'previousLaw' => $currentIndex !== false ? $orderedLaws->get($currentIndex - 1) : null,
            'nextLaw' => $currentIndex !== false ? $orderedLaws->get($currentIndex + 1) : null,
            'editionQueryId' => $editionQueryId,
        ]);
    }

    public function jump(Request $request): RedirectResponse
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $lawId = $request->integer('law');
        $editionId = $request->integer('edition');

        $fallbackParameters = array_filter([
            'lang' => $language,
            'edition' => $editionId ?: null,
        ], fn ($value) => $value !== null && $value !== '');

        if (! $lawId) {
            return redirect()->route('laws.list', $fallbackParameters);
        }

        $law = Law::query()
            ->published()
            ->forEdition($editionId)
            ->whereKey($lawId)
            ->whereHas('edition', fn ($query) => $query->published())
            ->first();

        if (! $law) {
            return redirect()->route('laws.list', $fallbackParameters);
        }

        return redirect()->route('laws.show', array_filter([
            'law' => $law,
            'lang' => $language,
            'edition' => $this->publicEditionQueryIdForLaw($law),
        ], fn ($value) => $value !== null && $value !== ''));
    }

    protected function publicEditionQueryIdForLaw(Law $law): ?int
    {
        if (! $law->edition_id) {
            return null;
        }

        $activeEditionId = Edition::current()?->id;

        if (! $activeEditionId || (int) $law->edition_id !== (int) $activeEditionId) {
            return (int) $law->edition_id;
        }

        return null;
    }
}
