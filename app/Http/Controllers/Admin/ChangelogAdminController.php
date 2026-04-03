<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangelogEntry;
use App\Models\Edition;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChangelogAdminController extends Controller
{
    public function index(Edition $edition): View
    {
        return view('admin.changelog.index', [
            'edition' => $edition,
            'entries' => ChangelogEntry::query()
                ->where('edition_id', $edition->id)
                ->orderByDesc('published_at')
                ->orderBy('sort_order')
                ->get(),
            'languages' => LotgLanguage::supported(),
        ]);
    }

    public function store(Request $request, Edition $edition): RedirectResponse
    {
        $validated = $this->validateEntry($request);

        ChangelogEntry::create([
            'edition_id' => $edition->id,
            'language_code' => $validated['language_code'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'sort_order' => $validated['sort_order'],
            'published_at' => $request->boolean('is_published') ? now() : null,
        ]);

        return redirect()
            ->route('admin.changelog.index', ['edition' => $edition])
            ->with('status', 'Update entry created.');
    }

    public function update(Request $request, Edition $edition, ChangelogEntry $entry): RedirectResponse
    {
        abort_unless((int) $entry->edition_id === (int) $edition->id, 404);

        $validated = $this->validateEntry($request, $entry);

        $entry->update([
            'language_code' => $validated['language_code'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'sort_order' => $validated['sort_order'],
            'published_at' => $request->boolean('is_published') ? ($entry->published_at ?? now()) : null,
        ]);

        return redirect()
            ->route('admin.changelog.index', ['edition' => $edition])
            ->with('status', 'Update entry updated.');
    }

    protected function validateEntry(Request $request, ?ChangelogEntry $entry = null): array
    {
        return $request->validate([
            'language_code' => ['required', 'in:'.implode(',', array_keys(LotgLanguage::supported()))],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
    }
}
