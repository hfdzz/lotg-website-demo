<?php

namespace App\Http\Controllers;

use App\Models\ChangelogEntry;
use App\Models\Edition;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ChangelogController extends Controller
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

        $entries = ChangelogEntry::published()
            ->where('edition_id', $selectedEdition?->id)
            ->where('language_code', $language)
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->get();

        return view('updates.index', [
            'entries' => $entries,
            'language' => $language,
            'hasActiveEdition' => (bool) $activeEdition,
            'activeEdition' => $activeEdition,
            'selectedEdition' => $selectedEdition,
            'publishedEditions' => $publishedEditions,
        ]);
    }
}
