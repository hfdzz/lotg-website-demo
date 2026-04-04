<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EditionAdminController extends Controller
{
    public function go(Request $request): RedirectResponse
    {
        $edition = Edition::query()->findOrFail((int) $request->query('edition'));

        return redirect()->route('admin.laws.index', ['edition' => $edition]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:editions,name'],
            'code' => ['nullable', 'string', 'max:255', 'unique:editions,code'],
            'year_start' => ['required', 'integer', 'min:1900', 'max:9999'],
            'year_end' => ['required', 'integer', 'min:1900', 'max:9999', 'gte:year_start'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $shouldBeActive = ! Edition::query()->active()->exists();

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

        return redirect()
            ->route('admin.laws.index', ['edition' => $edition])
            ->with('status', 'Edition created.');
    }

    public function update(Request $request, Edition $edition): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:editions,name,'.$edition->id],
            'code' => ['nullable', 'string', 'max:255', 'unique:editions,code,'.$edition->id],
            'year_start' => ['required', 'integer', 'min:1900', 'max:9999'],
            'year_end' => ['required', 'integer', 'min:1900', 'max:9999', 'gte:year_start'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $shouldBeActive = $request->boolean('set_active')
            || ($edition->is_active && ! Edition::query()->whereKeyNot($edition->id)->active()->exists());

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
            ->route('admin.laws.index', ['edition' => $edition])
            ->with('status', 'Edition updated.');
    }

    public function activate(Edition $edition): RedirectResponse
    {
        Edition::query()->update(['is_active' => false]);
        $edition->update(['is_active' => true]);

        return redirect()
            ->route('admin.laws.index', ['edition' => $edition])
            ->with('status', 'Edition activated.');
    }

    protected function makeCode(?string $code, string $name, ?Edition $edition = null): string
    {
        if (filled($code)) {
            return Str::slug($code);
        }

        $baseCode = Str::slug($name) ?: 'edition';
        $candidate = $baseCode;
        $suffix = 1;

        while (
            Edition::query()
                ->when($edition, fn ($query) => $query->whereKeyNot($edition->id))
                ->where('code', $candidate)
                ->exists()
        ) {
            $candidate = $baseCode.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
