<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangelogEntry;
use App\Models\Edition;
use App\Services\EditionContentCopier;
use App\Services\EditionReadinessChecker;
use App\Services\LotgFeatureVisibility;
use App\Support\UniqueSlugSuffixer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditionAdminController extends Controller
{
    public function __construct(
        protected EditionContentCopier $contentCopier,
        protected EditionReadinessChecker $readinessChecker,
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
            ->with([
                'laws.translations',
                'laws.contentNodes.translations',
            ])
            ->orderByDesc('is_active')
            ->orderByDesc('year_start')
            ->orderByDesc('year_end')
            ->get();

        return view('admin.editions.index', [
            'editions' => $editions,
            'selectedEdition' => $selectedEdition,
            'editionFeatureRows' => $selectedEdition ? $this->featureVisibility->adminRows($selectedEdition) : [],
            'readinessReports' => $editions->mapWithKeys(
                fn (Edition $edition) => [$edition->id => $this->readinessChecker->check($edition)]
            ),
        ]);
    }

    public function go(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Edition::class);

        $edition = Edition::query()->findOrFail((int) $request->query('edition'));
        $target = (string) $request->query('target', 'laws');

        return match ($target) {
            'documents' => redirect()->route('admin.documents.index', ['edition' => $edition]),
            'editions' => redirect()->route('admin.editions.index', ['edition' => $edition->id]),
            'qas' => redirect()->route('admin.qas.index', ['edition' => $edition]),
            default => redirect()->route('admin.laws.index', ['edition' => $edition]),
        };
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Edition::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:editions,name'],
            'code' => ['nullable', 'string', 'max:255', 'unique:editions,code'],
            'year_start' => ['required', 'integer', 'min:1900', 'max:9999'],
            'year_end' => ['required', 'integer', 'min:1900', 'max:9999', 'gte:year_start'],
            'status' => ['required', 'in:draft,published'],
            'copy_from_edition_id' => ['nullable', 'integer', 'exists:editions,id'],
        ]);

        $edition = DB::transaction(function () use ($validated) {
            $shouldBeActive = $validated['status'] === 'published' && ! Edition::query()->active()->published()->exists();

            if ($shouldBeActive) {
                Edition::query()->update(['is_active' => false]);
            }

            $edition = Edition::create([
                'name' => $validated['name'],
                'code' => $this->makeCode($validated['code'] ?? null, $validated['name']),
                'year_start' => $validated['year_start'],
                'year_end' => $validated['year_end'],
                'status' => $validated['status'],
                'is_active' => $shouldBeActive,
            ]);

            if (! empty($validated['copy_from_edition_id'])) {
                $sourceEdition = Edition::query()->find($validated['copy_from_edition_id']);

                if ($sourceEdition) {
                    $this->contentCopier->copy($sourceEdition, $edition);
                }
            }

            if ($validated['status'] === 'published') {
                $this->assertReadyForPublication($edition);
            }

            return $edition;
        });

        return redirect()
            ->route('admin.editions.index')
            ->with('status', 'Edition created.');
    }

    public function update(Request $request, Edition $edition): RedirectResponse
    {
        $this->authorize('update', $edition);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:editions,name,'.$edition->id],
            'code' => ['nullable', 'string', 'max:255', 'unique:editions,code,'.$edition->id],
            'year_start' => ['required', 'integer', 'min:1900', 'max:9999'],
            'year_end' => ['required', 'integer', 'min:1900', 'max:9999', 'gte:year_start'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $shouldBeActive = $request->boolean('set_active')
            || ($edition->is_active && ! Edition::query()->whereKeyNot($edition->id)->active()->exists());

        $requiresReadiness = ($edition->status !== 'published' && $validated['status'] === 'published')
            || (! $edition->is_active && $shouldBeActive);

        if ($shouldBeActive && $validated['status'] !== 'published') {
            return back()
                ->withErrors(['status' => 'Only a published edition can be active.'])
                ->withInput();
        }

        if ($requiresReadiness) {
            $this->assertReadyForPublication($edition);
        }

        if ($shouldBeActive) {
            Edition::query()->whereKeyNot($edition->id)->update(['is_active' => false]);
        }

        $edition->update([
            'name' => $validated['name'],
            'code' => $this->makeCode($validated['code'] ?? null, $validated['name'], $edition),
            'year_start' => $validated['year_start'],
            'year_end' => $validated['year_end'],
            'status' => $validated['status'],
            'is_active' => $shouldBeActive,
        ]);

        return redirect()
            ->route('admin.editions.index')
            ->with('status', 'Edition updated.');
    }

    public function updateEditionFeatures(Request $request, Edition $edition): RedirectResponse
    {
        $this->authorize('update', $edition);

        $validated = $request->validate($this->featureVisibilityRules([
            'inherit',
            'enabled',
            'disabled',
        ]));

        $this->featureVisibility->storeEditionOverrideStates($edition, $validated['features'] ?? []);

        return redirect()
            ->route('admin.editions.index', ['edition' => $edition->id])
            ->with('status', 'Edition feature overrides updated.');
    }

    public function activate(Edition $edition): RedirectResponse
    {
        $this->authorize('activate', $edition);

        if ($edition->status !== 'published') {
            return back()->withErrors(['status' => 'Only a published edition can be active.']);
        }

        $report = $this->readinessChecker->check($edition);

        if (! $report['is_ready']) {
            return redirect()
                ->route('admin.editions.index', ['edition' => $edition->id])
                ->withErrors(['edition' => $this->readinessFailureMessage($report)]);
        }

        Edition::query()->update(['is_active' => false]);
        $edition->update(['is_active' => true]);

        return redirect()
            ->route('admin.editions.index')
            ->with('status', 'Edition activated.');
    }

    public function forceActivate(Edition $edition): RedirectResponse
    {
        $this->authorize('forceActivate', $edition);

        if ($edition->status !== 'published') {
            return back()->withErrors(['status' => 'Only a published edition can be active.']);
        }

        Edition::query()->update(['is_active' => false]);
        $edition->update(['is_active' => true]);

        return redirect()
            ->route('admin.editions.index')
            ->with('status', 'Edition force-activated. Blocking completeness checks were bypassed.');
    }

    public function destroy(Edition $edition): RedirectResponse
    {
        $this->authorize('delete', $edition);

        if ($edition->is_active) {
            return back()->withErrors(['edition' => 'The active edition cannot be deleted.']);
        }

        if (
            $edition->laws()->exists()
            || $edition->documents()->exists()
            || ChangelogEntry::query()->where('edition_id', $edition->id)->exists()
        ) {
            return back()->withErrors(['edition' => 'Only an empty edition can be deleted. Remove its laws, documents, and law changes first.']);
        }

        $edition->delete();

        return redirect()
            ->route('admin.editions.index')
            ->with('status', 'Edition deleted.');
    }

    protected function makeCode(?string $code, string $name, ?Edition $edition = null): string
    {
        if (filled($code)) {
            return Str::slug($code);
        }

        return UniqueSlugSuffixer::ensureUnique($name, function (string $candidate) use ($edition) {
            return Edition::query()
                ->when($edition, fn ($query) => $query->whereKeyNot($edition->id))
                ->where('code', $candidate)
                ->exists();
        });
    }

    protected function assertReadyForPublication(Edition $edition): void
    {
        $report = $this->readinessChecker->check($edition);

        if (! $report['is_ready']) {
            throw ValidationException::withMessages([
                'status' => $this->readinessFailureMessage($report),
            ]);
        }
    }

    /**
     * @param array{summary: string} $report
     */
    protected function readinessFailureMessage(array $report): string
    {
        return 'Edition is not ready to publish or activate yet. '.$report['summary'];
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
