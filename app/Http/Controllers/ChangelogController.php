<?php

namespace App\Http\Controllers;

use App\Models\ChangelogEntry;
use App\Models\Edition;
use App\Services\LotgFeatureVisibility;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function __construct(
        protected LotgFeatureVisibility $featureVisibility
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $activeEdition = Edition::current();
        $publishedEditions = Edition::query()
            ->published()
            ->orderByDesc('year_start')
            ->orderByDesc('year_end')
            ->get();
        $availableEditions = $publishedEditions
            ->filter(fn (Edition $edition) => $this->featureVisibility->enabled(LotgFeatureVisibility::FEATURE_LEGACY_UPDATES, $edition))
            ->values();
        $requestedEditionId = $request->integer('edition');
        $selectedEdition = $requestedEditionId
            ? $availableEditions->firstWhere('id', $requestedEditionId)
            : (($activeEdition && $this->featureVisibility->enabled(LotgFeatureVisibility::FEATURE_LEGACY_UPDATES, $activeEdition))
                ? $activeEdition
                : $availableEditions->first());

        if ($requestedEditionId && ! $selectedEdition) {
            return $this->redirectToLawListing(
                $language,
                $publishedEditions->firstWhere('id', $requestedEditionId)
            );
        }

        if (! $selectedEdition && ! $this->featureVisibility->enabled(LotgFeatureVisibility::FEATURE_LEGACY_UPDATES, $activeEdition)) {
            return $this->redirectToLawListing($language, $activeEdition);
        }

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
            'publishedEditions' => $availableEditions,
        ]);
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
