<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawQa;
use App\Models\LawQaTranslation;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LawQaAdminController extends Controller
{
    public function edit(Edition $edition, Law $law, LawQa $qa): View
    {
        $this->authorize('update', $qa);
        $this->assertLawBelongsToEdition($edition, $law);
        $this->assertQaBelongsToLaw($law, $qa);

        $qa->load('translations');

        return view('admin.qas.edit', [
            'editions' => Edition::query()->orderByDesc('year_start')->get(),
            'selectedEdition' => $edition,
            'law' => $law,
            'qa' => $qa,
            'translationsByLanguage' => $qa->translations->keyBy('language_code'),
            'languages' => LotgLanguage::supported(),
        ]);
    }

    public function store(Request $request, Edition $edition, Law $law): RedirectResponse
    {
        $this->authorize('create', LawQa::class);
        $this->assertLawBelongsToEdition($edition, $law);
        $validated = $this->validateQa($request);

        $qa = DB::transaction(function () use ($law, $request, $validated) {
            $qa = LawQa::create([
                'law_id' => $law->id,
                'sort_order' => $this->nextSortOrder($law),
                'is_published' => $request->boolean('is_published'),
            ]);

            $this->syncTranslations($qa, $validated);

            return $qa;
        });

        return redirect()
            ->route('admin.qas.edit', ['edition' => $edition, 'law' => $law, 'qa' => $qa])
            ->with('status', 'Q&A item created.');
    }

    public function update(Request $request, Edition $edition, Law $law, LawQa $qa): RedirectResponse
    {
        $this->authorize('update', $qa);
        $this->assertLawBelongsToEdition($edition, $law);
        $this->assertQaBelongsToLaw($law, $qa);

        $validated = $this->validateQa($request, $qa);

        DB::transaction(function () use ($law, $qa, $request, $validated) {
            $requestedSortOrder = (int) ($validated['sort_order'] ?? $qa->sort_order);

            $this->normalizeSortOrders($law, $qa->id);

            $maxSortOrder = $this->nextSortOrder($law, $qa->id);
            $finalSortOrder = min(max($requestedSortOrder, 1), $maxSortOrder);

            $this->shiftForInsert($law, $finalSortOrder, $qa->id);

            $qa->update([
                'sort_order' => $finalSortOrder,
                'is_published' => $request->boolean('is_published'),
            ]);

            $this->syncTranslations($qa, $validated);
        });

        return redirect()
            ->route('admin.qas.edit', ['edition' => $edition, 'law' => $law, 'qa' => $qa])
            ->with('status', 'Q&A item updated.');
    }

    public function destroy(Edition $edition, Law $law, LawQa $qa): RedirectResponse
    {
        $this->authorize('delete', $qa);
        $this->assertLawBelongsToEdition($edition, $law);
        $this->assertQaBelongsToLaw($law, $qa);

        DB::transaction(function () use ($law, $qa) {
            $qa->delete();
            $this->normalizeSortOrders($law);
        });

        return redirect()
            ->route('admin.laws.edit', ['edition' => $edition, 'law' => $law])
            ->with('status', 'Q&A item deleted.');
    }

    protected function validateQa(Request $request, ?LawQa $qa = null): array
    {
        return $request->validate([
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'question_id' => ['required', 'string', 'max:255'],
            'answer_html_id' => ['nullable', 'string'],
            'translation_status_id' => ['required', 'in:draft,published'],
            'question_en' => ['nullable', 'string', 'max:255'],
            'answer_html_en' => ['nullable', 'string'],
            'translation_status_en' => ['required', 'in:draft,published'],
        ]);
    }

    protected function syncTranslations(LawQa $qa, array $validated): void
    {
        foreach (array_keys(LotgLanguage::supported()) as $languageCode) {
            LawQaTranslation::updateOrCreate(
                [
                    'law_qa_id' => $qa->id,
                    'language_code' => $languageCode,
                ],
                [
                    'question' => $validated['question_'.$languageCode] ?: ($validated['question_id'] ?? 'Untitled question'),
                    'answer_html' => $validated['answer_html_'.$languageCode] ?: null,
                    'status' => $validated['translation_status_'.$languageCode],
                ]
            );
        }
    }

    protected function nextSortOrder(Law $law, ?int $excludeQaId = null): int
    {
        return $this->qaQuery($law, $excludeQaId)->count() + 1;
    }

    protected function normalizeSortOrders(Law $law, ?int $excludeQaId = null): void
    {
        $qas = $this->qaQuery($law, $excludeQaId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($qas as $index => $item) {
            $targetOrder = $index + 1;

            if ((int) $item->sort_order !== $targetOrder) {
                $item->update(['sort_order' => $targetOrder]);
            }
        }
    }

    protected function shiftForInsert(Law $law, int $sortOrder, ?int $excludeQaId = null): void
    {
        $qas = $this->qaQuery($law, $excludeQaId)
            ->where('sort_order', '>=', $sortOrder)
            ->orderByDesc('sort_order')
            ->get();

        foreach ($qas as $item) {
            $item->update([
                'sort_order' => (int) $item->sort_order + 1,
            ]);
        }
    }

    protected function qaQuery(Law $law, ?int $excludeQaId = null)
    {
        return LawQa::query()
            ->where('law_id', $law->id)
            ->when($excludeQaId, fn ($query) => $query->whereKeyNot($excludeQaId));
    }

    protected function assertLawBelongsToEdition(Edition $edition, Law $law): void
    {
        abort_unless((int) $law->edition_id === (int) $edition->id, 404);
    }

    protected function assertQaBelongsToLaw(Law $law, LawQa $qa): void
    {
        abort_unless((int) $qa->law_id === (int) $law->id, 404);
    }
}
