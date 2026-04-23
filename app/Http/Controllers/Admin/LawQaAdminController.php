<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawQa;
use App\Models\LawQaOption;
use App\Models\LawQaOptionTranslation;
use App\Models\LawQaTranslation;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LawQaAdminController extends Controller
{
    public function home(): View|RedirectResponse
    {
        $this->authorize('viewAny', LawQa::class);

        $activeEdition = Edition::current();

        if ($activeEdition) {
            return redirect()->route('admin.qas.index', ['edition' => $activeEdition]);
        }

        $fallbackEdition = Edition::query()->orderByDesc('year_start')->orderByDesc('year_end')->first();

        if ($fallbackEdition) {
            return redirect()->route('admin.qas.index', ['edition' => $fallbackEdition]);
        }

        return redirect()->route('admin.editions.index');
    }

    public function index(Edition $edition): View
    {
        $this->authorize('viewAny', LawQa::class);

        return view('admin.qas.index', [
            'laws' => Law::query()
                ->with('translations')
                ->withCount('qas')
                ->forEdition($edition->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'editions' => Edition::query()->orderByDesc('year_start')->get(),
            'selectedEdition' => $edition,
        ]);
    }

    public function law(Edition $edition, Law $law): View
    {
        $this->authorize('viewAny', LawQa::class);
        $this->assertLawBelongsToEdition($edition, $law);

        $law->load([
            'translations',
            'qas.translations',
            'qas.options.translations',
        ]);

        return view('admin.qas.law', [
            'editions' => Edition::query()->orderByDesc('year_start')->get(),
            'selectedEdition' => $edition,
            'law' => $law,
            'qas' => $law->qas->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])->values(),
            'languages' => LotgLanguage::supported(),
        ]);
    }

    public function edit(Edition $edition, Law $law, LawQa $qa): View
    {
        $this->authorize('update', $qa);
        $this->assertLawBelongsToEdition($edition, $law);
        $this->assertQaBelongsToLaw($law, $qa);

        $qa->load(['translations', 'options.translations']);

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

        DB::transaction(function () use ($law, $request, $validated) {
            $requestedSortOrder = (int) ($validated['sort_order'] ?? $this->nextSortOrder($law));
            $this->normalizeSortOrders($law);
            $finalSortOrder = min(max($requestedSortOrder, 1), $this->nextSortOrder($law));
            $this->shiftForInsert($law, $finalSortOrder);

            $qa = LawQa::create([
                'law_id' => $law->id,
                'qa_type' => $validated['qa_type'],
                'sort_order' => $finalSortOrder,
                'is_published' => $request->boolean('is_published'),
                'uses_custom_answer' => $this->usesCustomAnswer($validated),
            ]);

            $this->syncOptions($qa, $validated);
            $this->syncTranslations($qa, $validated);
        });

        return redirect()
            ->route('admin.qas.law', ['edition' => $edition, 'law' => $law])
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
                'qa_type' => $validated['qa_type'],
                'sort_order' => $finalSortOrder,
                'is_published' => $request->boolean('is_published'),
                'uses_custom_answer' => $this->usesCustomAnswer($validated),
            ]);

            $this->syncOptions($qa, $validated);
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
            ->route('admin.qas.law', ['edition' => $edition, 'law' => $law])
            ->with('status', 'Q&A item deleted.');
    }

    protected function validateQa(Request $request, ?LawQa $qa = null): array
    {
        $validator = Validator::make($request->all(), [
            'qa_type' => ['required', 'in:'.LawQa::TYPE_SIMPLE.','.LawQa::TYPE_MULTIPLE_CHOICE],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'question_id' => ['required', 'string', 'max:255'],
            'answer_html_id' => ['nullable', 'string'],
            'translation_status_id' => ['required', 'in:draft,published'],
            'question_en' => ['nullable', 'string', 'max:255'],
            'answer_html_en' => ['nullable', 'string'],
            'translation_status_en' => ['required', 'in:draft,published'],
            'custom_answer' => ['nullable', 'boolean'],
            'options' => ['nullable', 'array'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.is_correct' => ['nullable', 'boolean'],
            'options.*.text_id' => ['nullable', 'string', 'max:255'],
            'options.*.text_en' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->input('qa_type') !== LawQa::TYPE_MULTIPLE_CHOICE) {
                return;
            }

            $options = $this->normalizedOptionPayloads($request->input('options', []));

            if ($options->count() < 2) {
                $validator->errors()->add('options', 'Multiple choice Q&A needs at least two options.');
            }

            if (! $options->contains(fn (array $option) => (bool) ($option['is_correct'] ?? false))) {
                $validator->errors()->add('options', 'Multiple choice Q&A needs at least one correct option.');
            }

            foreach ($options as $index => $option) {
                if (! filled($option['text_id'] ?? null)) {
                    $validator->errors()->add('options.'.($index + 1), 'Each multiple choice option needs Indonesian text.');
                }
            }
        });

        return $validator->validate();
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
                    'answer_html' => $this->answerHtmlForLanguage($validated, $languageCode),
                    'status' => $validated['translation_status_'.$languageCode],
                ]
            );
        }
    }

    protected function syncOptions(LawQa $qa, array $validated): void
    {
        if (($validated['qa_type'] ?? LawQa::TYPE_SIMPLE) !== LawQa::TYPE_MULTIPLE_CHOICE) {
            $qa->options()->delete();

            return;
        }

        $existingOptions = $qa->options()->get()->keyBy('id');
        $keptOptionIds = [];

        foreach ($this->normalizedOptionPayloads($validated['options'] ?? []) as $index => $optionPayload) {
            $optionId = (int) ($optionPayload['id'] ?? 0);
            $option = $optionId && $existingOptions->has($optionId)
                ? $existingOptions->get($optionId)
                : new LawQaOption(['law_qa_id' => $qa->id]);

            $option->fill([
                'law_qa_id' => $qa->id,
                'sort_order' => $index + 1,
                'is_correct' => (bool) ($optionPayload['is_correct'] ?? false),
            ]);
            $option->save();

            $keptOptionIds[] = $option->id;

            foreach (array_keys(LotgLanguage::supported()) as $languageCode) {
                $text = trim((string) ($optionPayload['text_'.$languageCode] ?? ''));

                if ($languageCode !== LotgLanguage::default() && $text === '') {
                    $text = trim((string) ($optionPayload['text_'.LotgLanguage::default()] ?? ''));
                }

                LawQaOptionTranslation::updateOrCreate(
                    [
                        'option_id' => $option->id,
                        'language_code' => $languageCode,
                    ],
                    ['text' => $text]
                );
            }
        }

        $qa->options()
            ->when($keptOptionIds !== [], fn ($query) => $query->whereKeyNot($keptOptionIds))
            ->delete();
    }

    protected function answerHtmlForLanguage(array $validated, string $languageCode): ?string
    {
        $qaType = $validated['qa_type'] ?? LawQa::TYPE_SIMPLE;

        if ($qaType !== LawQa::TYPE_MULTIPLE_CHOICE || $this->usesCustomAnswer($validated)) {
            return $validated['answer_html_'.$languageCode] ?: null;
        }

        return null;
    }

    protected function usesCustomAnswer(array $validated): bool
    {
        return ($validated['qa_type'] ?? LawQa::TYPE_SIMPLE) === LawQa::TYPE_MULTIPLE_CHOICE
            && (bool) ($validated['custom_answer'] ?? false);
    }

    protected function normalizedOptionPayloads(mixed $options)
    {
        return collect(is_array($options) ? $options : [])
            ->filter(function ($option) {
                if (! is_array($option)) {
                    return false;
                }

                return filled($option['id'] ?? null)
                    || filled($option['text_id'] ?? null)
                    || filled($option['text_en'] ?? null)
                    || (bool) ($option['is_correct'] ?? false);
            })
            ->values();
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

        // TODO: This is intentionally simple but can issue N update queries. Consider a bulk renumber if Q&A counts grow.
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

        // TODO: This can be replaced with a bulk increment query if Q&A sort-order writes become a bottleneck.
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
