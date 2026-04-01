<?php

namespace App\Http\Controllers;

use App\Models\Law;
use App\Services\LawTreeBuilder;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LawController extends Controller
{
    public function index(): View
    {
        $laws = Law::published()
            ->orderBy('sort_order')
            ->get();

        return view('laws.index', [
            'laws' => $laws,
        ]);
    }

    public function show(Request $request, Law $law, LawTreeBuilder $treeBuilder): View
    {
        abort_unless($law->status === 'published', 404);

        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $tree = $treeBuilder->build($law, $language);
        $orderedLaws = Law::published()
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
