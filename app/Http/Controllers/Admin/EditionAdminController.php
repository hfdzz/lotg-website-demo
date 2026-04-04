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
        $edition = Edition::query()->where('slug', (string) $request->query('edition'))->firstOrFail();

        return redirect()->route('admin.laws.index', ['edition' => $edition]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:editions,name'],
            'year_start' => ['required', 'integer', 'min:1900', 'max:9999'],
            'year_end' => ['required', 'integer', 'min:1900', 'max:9999', 'gte:year_start'],
        ]);

        $shouldBeActive = ! Edition::query()->active()->exists();

        if ($shouldBeActive) {
            Edition::query()->update(['is_active' => false]);
        }

        $edition = Edition::create([
            'name' => $validated['name'],
            'slug' => $this->makeSlug($validated['name'], (int) $validated['year_start'], (int) $validated['year_end']),
            'year_start' => $validated['year_start'],
            'year_end' => $validated['year_end'],
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
            'year_start' => ['required', 'integer', 'min:1900', 'max:9999'],
            'year_end' => ['required', 'integer', 'min:1900', 'max:9999', 'gte:year_start'],
        ]);

        $shouldBeActive = $request->boolean('set_active')
            || ($edition->is_active && ! Edition::query()->whereKeyNot($edition->id)->active()->exists());

        if ($shouldBeActive) {
            Edition::query()->whereKeyNot($edition->id)->update(['is_active' => false]);
        }

        $edition->update([
            'name' => $validated['name'],
            'slug' => $this->makeSlug($validated['name'], (int) $validated['year_start'], (int) $validated['year_end']),
            'year_start' => $validated['year_start'],
            'year_end' => $validated['year_end'],
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

    protected function makeSlug(string $name, int $yearStart, int $yearEnd): string
    {
        return Str::slug('edition '.$yearStart.' '.$yearEnd.' '.$name);
    }
}
