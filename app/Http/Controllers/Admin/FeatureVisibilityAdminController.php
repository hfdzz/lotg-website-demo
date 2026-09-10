<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Services\LotgFeatureVisibility;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'redirect',
        ]));
        $this->assertRedirectPathsPresent($validated['features'] ?? [], $validated['redirect_urls'] ?? []);

        $this->featureVisibility->storeGlobalStates(
            $validated['features'] ?? [],
            $validated['redirect_urls'] ?? []
        );

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
            'redirect_urls' => ['nullable', 'array'],
        ];

        foreach ($this->featureVisibility->keys() as $featureKey) {
            $rules['features.'.$featureKey] = ['nullable', 'in:'.implode(',', $values)];
            $rules['redirect_urls.'.$featureKey] = [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail) use ($featureKey) {
                    if (! filled($value)) {
                        return;
                    }

                    $url = trim((string) $value);

                    if ($featureKey !== LotgFeatureVisibility::FEATURE_LEGACY_UPDATES) {
                        $fail('Redirects are only supported for Law Changes.');
                    }

                    if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
                        $fail('Redirect paths must be internal paths starting with /.');
                    }

                    if (rtrim(parse_url($url, PHP_URL_PATH) ?: '', '/') === '/updates') {
                        $fail('Law Changes cannot redirect to itself.');
                    }
                },
            ];
        }

        return $rules;
    }

    protected function assertRedirectPathsPresent(array $states, array $redirectUrls): void
    {
        if (($states[LotgFeatureVisibility::FEATURE_LEGACY_UPDATES] ?? null) !== 'redirect') {
            return;
        }

        if (filled($redirectUrls[LotgFeatureVisibility::FEATURE_LEGACY_UPDATES] ?? null)) {
            return;
        }

        throw ValidationException::withMessages([
            'redirect_urls.'.LotgFeatureVisibility::FEATURE_LEGACY_UPDATES => 'Enter a redirect path for Law Changes.',
        ]);
    }
}
