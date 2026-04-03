<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_active')) {
            Edition::query()->update(['is_active' => false]);
        }

        $edition = Edition::create([
            'name' => $validated['name'],
            'slug' => $this->makeSlug((int) $validated['year_start'], (int) $validated['year_end']),
            'year_start' => $validated['year_start'],
            'year_end' => $validated['year_end'],
            'is_active' => $request->boolean('is_active'),
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
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_active')) {
            Edition::query()->whereKeyNot($edition->id)->update(['is_active' => false]);
        }

        $edition->update([
            'name' => $validated['name'],
            'slug' => $this->makeSlug((int) $validated['year_start'], (int) $validated['year_end']),
            'year_start' => $validated['year_start'],
            'year_end' => $validated['year_end'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.laws.index', ['edition' => $edition])
            ->with('status', 'Edition updated.');
    }

    protected function makeSlug(int $yearStart, int $yearEnd): string
    {
        return 'edition_'.$yearStart.'_'.$yearEnd;
    }
}
