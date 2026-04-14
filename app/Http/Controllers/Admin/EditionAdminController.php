<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangelogEntry;
use App\Models\Edition;
use App\Services\EditionContentCopier;
use App\Support\UniqueSlugSuffixer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EditionAdminController extends Controller
{
    public function __construct(
        protected EditionContentCopier $contentCopier
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

        return view('admin.editions.index', [
            'editions' => Edition::query()
                ->orderByDesc('is_active')
                ->orderByDesc('year_start')
                ->orderByDesc('year_end')
                ->get(),
            'selectedEdition' => $selectedEdition,
        ]);
    }

    public function go(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Edition::class);

        $edition = Edition::query()->findOrFail((int) $request->query('edition'));

        return redirect()->route('admin.laws.index', ['edition' => $edition]);
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

        if ($shouldBeActive && $validated['status'] !== 'published') {
            return back()
                ->withErrors(['status' => 'Only a published edition can be active.'])
                ->withInput();
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

    public function activate(Edition $edition): RedirectResponse
    {
        $this->authorize('activate', $edition);

        if ($edition->status !== 'published') {
            return back()->withErrors(['status' => 'Only a published edition can be active.']);
        }

        Edition::query()->update(['is_active' => false]);
        $edition->update(['is_active' => true]);

        return redirect()
            ->route('admin.editions.index')
            ->with('status', 'Edition activated.');
    }

    public function destroy(Edition $edition): RedirectResponse
    {
        $this->authorize('delete', $edition);

        if ($edition->is_active) {
            return back()->withErrors(['edition' => 'The active edition cannot be deleted.']);
        }

        if ($edition->laws()->exists() || ChangelogEntry::query()->where('edition_id', $edition->id)->exists()) {
            return back()->withErrors(['edition' => 'Only an empty edition can be deleted. Remove its laws and law changes first.']);
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
}
