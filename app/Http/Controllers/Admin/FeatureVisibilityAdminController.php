<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Services\LotgFeatureVisibility;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeatureVisibilityAdminController extends Controller
{
    public function __construct(
        protected LotgFeatureVisibility $featureVisibility
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Edition::class);

        $selectedEdition = $request->filled('edition')
            ? Edition::query()->find($request->integer('edition'))
            : null;

        $selectedEdition = $selectedEdition
            ?? Edition::current()
            ?? Edition::query()
                ->orderByDesc('year_start')
                ->orderByDesc('year_end')
                ->first();

        $editions = Edition::query()
            ->orderByDesc('is_active')
            ->orderByDesc('year_start')
            ->orderByDesc('year_end')
            ->get();

        return view('admin.features.index', [
            'editions' => $editions,
            'selectedEdition' => $selectedEdition,
            'globalFeatureRows' => $this->featureVisibility->adminRows(),
            'editionFeatureRows' => $selectedEdition ? $this->featureVisibility->adminRows($selectedEdition) : [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Edition::class);

        $validated = $request->validate($this->featureVisibilityRules([
            'default',
            'enabled',
            'disabled',
        ]));

        $this->featureVisibility->storeGlobalStates($validated['features'] ?? []);

        return redirect()
            ->route('admin.public-features.index', array_filter([
                'edition' => $request->integer('edition') ?: null,
            ], fn ($value) => $value !== null && $value !== ''))
            ->with('status', 'Global public feature visibility updated.');
    }

    /**
     * @param array<int, string> $values
     * @return array<string, array<int, string>>
     */
    protected function featureVisibilityRules(array $values): array
    {
        $rules = [
            'features' => ['required', 'array'],
        ];

        foreach ($this->featureVisibility->keys() as $featureKey) {
            $rules['features.'.$featureKey] = ['nullable', 'in:'.implode(',', $values)];
        }

        return $rules;
    }
}
