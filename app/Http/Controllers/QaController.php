<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\Law;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QaController extends Controller
{
    public function index(Request $request): View
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $activeEdition = Edition::current();

        $laws = Law::query()
            ->published()
            ->forEdition($activeEdition?->id)
            ->with([
                'translations',
                'publishedQas' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'publishedQas.translations',
            ])
            ->whereHas('publishedQas')
            ->orderBy('sort_order')
            ->get();

        return view('qas.index', [
            'language' => $language,
            'hasActiveEdition' => (bool) $activeEdition,
            'activeEdition' => $activeEdition,
            'laws' => $laws,
        ]);
    }

    public function show(Request $request, Law $law): View|RedirectResponse
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $activeEdition = Edition::current();

        $law->loadMissing(['edition', 'translations']);

        if (
            ! $activeEdition
            || ! $law->edition
            || (int) $law->edition_id !== (int) $activeEdition->id
            || $law->status !== 'published'
            || $law->edition->status !== 'published'
        ) {
            return redirect()->route('qas.index', ['lang' => $language]);
        }

        $qas = $law->publishedQas()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(function ($qa) use ($language) {
                return $qa->translationFor($language)?->status === 'published';
            })
            ->values();

        return view('qas.show', [
            'language' => $language,
            'activeEdition' => $activeEdition,
            'law' => $law,
            'qas' => $qas,
        ]);
    }
}
