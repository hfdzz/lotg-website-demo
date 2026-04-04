<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\Law;
use App\Services\LawTreeBuilder;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LawController extends Controller
{
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

        return view('laws.index', [
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

        return view('laws.show', [
            'law' => $law,
            'language' => $language,
            'tree' => $tree,
            'tableOfContents' => $treeBuilder->buildTableOfContents($tree),
            'previousLaw' => $currentIndex !== false ? $orderedLaws->get($currentIndex - 1) : null,
            'nextLaw' => $currentIndex !== false ? $orderedLaws->get($currentIndex + 1) : null,
        ]);
    }
}
