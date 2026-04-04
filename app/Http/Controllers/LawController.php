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
    public function index(): View
    {
        $activeEdition = Edition::current();
        $laws = Law::published()
            ->forEdition($activeEdition?->id)
            ->with('translations')
            ->orderBy('sort_order')
            ->get();

        return view('laws.index', [
            'laws' => $laws,
            'hasActiveEdition' => (bool) $activeEdition,
        ]);
    }

    public function show(Request $request, Law $law, LawTreeBuilder $treeBuilder): View|RedirectResponse
    {
        $activeEdition = Edition::current();
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));

        if (! $activeEdition || $law->status !== 'published' || $law->edition_id !== $activeEdition->id) {
            return redirect()->route('laws.index', ['lang' => $language]);
        }

        $law->loadMissing('translations');
        $tree = $treeBuilder->build($law, $language);
        $orderedLaws = Law::published()
            ->forEdition($activeEdition?->id)
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
