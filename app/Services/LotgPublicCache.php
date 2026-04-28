<?php

namespace App\Services;

use App\Models\ChangelogEntry;
use App\Models\Document;
use App\Models\Edition;
use App\Models\Law;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LotgPublicCache
{
    protected bool $hasResolvedActiveEdition = false;

    protected ?Edition $resolvedActiveEdition = null;

    public function activeEditionId(): ?int
    {
        $editionId = Cache::rememberForever(
            $this->globalKey('active-edition-id'),
            fn () => Edition::query()
                ->active()
                ->published()
                ->orderByDesc('year_start')
                ->value('id') ?: 0
        );

        return $editionId ? (int) $editionId : null;
    }

    public function activeEdition(): ?Edition
    {
        if ($this->hasResolvedActiveEdition) {
            return $this->resolvedActiveEdition;
        }

        $this->hasResolvedActiveEdition = true;

        $editionId = $this->activeEditionId();
        $this->resolvedActiveEdition = $editionId ? Edition::query()->find($editionId) : null;

        return $this->resolvedActiveEdition;
    }

    public function publishedEditionIds(): array
    {
        return Cache::rememberForever(
            $this->globalKey('published-edition-ids'),
            fn () => Edition::query()
                ->published()
                ->orderByDesc('year_start')
                ->orderByDesc('year_end')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );
    }

    public function publishedEditions(): EloquentCollection
    {
        return $this->orderedModels(Edition::class, $this->publishedEditionIds());
    }

    public function orderedPublishedLaws(?int $editionId, array $with = []): EloquentCollection
    {
        if (! $editionId) {
            return Law::query()->newModelInstance()->newCollection();
        }

        $ids = Cache::rememberForever(
            $this->editionKey($editionId, 'published-law-ids'),
            fn () => Law::published()
                ->forEdition($editionId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );

        return $this->orderedModels(Law::class, $ids, $with);
    }

    public function orderedPublishedDocuments(?int $editionId, array $with = []): EloquentCollection
    {
        if (! $editionId) {
            return Document::query()->newModelInstance()->newCollection();
        }

        $ids = Cache::rememberForever(
            $this->editionKey($editionId, 'published-document-ids'),
            fn () => Document::published()
                ->forEdition($editionId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );

        return $this->orderedModels(Document::class, $ids, $with);
    }

    public function publishedChangelogEntries(?int $editionId, string $languageCode): EloquentCollection
    {
        if (! $editionId) {
            return ChangelogEntry::query()->newModelInstance()->newCollection();
        }

        $ids = Cache::rememberForever(
            $this->editionKey($editionId, 'published-changelog-'.$languageCode),
            fn () => ChangelogEntry::published()
                ->where('edition_id', $editionId)
                ->where('language_code', $languageCode)
                ->orderByDesc('published_at')
                ->orderBy('sort_order')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );

        return $this->orderedModels(ChangelogEntry::class, $ids);
    }

    /**
     * @param callable(): array<string, bool> $resolver
     * @return array<string, bool>
     */
    public function rememberFeatureGlobalStates(callable $resolver): array
    {
        return Cache::rememberForever(
            $this->globalKey('feature-global-states'),
            $resolver
        );
    }

    /**
     * @param callable(): array<string, bool> $resolver
     * @return array<string, bool>
     */
    public function rememberFeatureEditionStates(int $editionId, callable $resolver): array
    {
        return Cache::rememberForever(
            $this->editionKey($editionId, 'feature-edition-states'),
            $resolver
        );
    }

    /**
     * @param callable(): array<int, array<string, mixed>> $resolver
     * @return array<int, array<string, mixed>>
     */
    public function rememberLawTree(int $lawId, string $languageCode, callable $resolver): array
    {
        return Cache::rememberForever(
            $this->lawKey($lawId, 'tree-'.$languageCode),
            $resolver
        );
    }

    public function touchGlobal(): void
    {
        Cache::forever($this->versionKey('global'), $this->newVersionToken());
        $this->hasResolvedActiveEdition = false;
        $this->resolvedActiveEdition = null;
    }

    public function touchEdition(?int $editionId): void
    {
        if (! $editionId) {
            return;
        }

        Cache::forever($this->versionKey('edition-'.$editionId), $this->newVersionToken());
    }

    public function touchLaw(?int $lawId): void
    {
        if (! $lawId) {
            return;
        }

        Cache::forever($this->versionKey('law-'.$lawId), $this->newVersionToken());
    }

    /**
     * @param iterable<int> $lawIds
     */
    public function touchLaws(iterable $lawIds): void
    {
        foreach ($lawIds as $lawId) {
            $this->touchLaw((int) $lawId);
        }
    }

    protected function globalKey(string $suffix): string
    {
        return 'lotg:public:'.$suffix.':'.$this->globalVersion();
    }

    protected function editionKey(int $editionId, string $suffix): string
    {
        return 'lotg:public:edition:'.$editionId.':'.$suffix.':'.$this->globalVersion().':'.$this->editionVersion($editionId);
    }

    protected function lawKey(int $lawId, string $suffix): string
    {
        return 'lotg:public:law:'.$lawId.':'.$suffix.':'.$this->lawVersion($lawId);
    }

    protected function globalVersion(): string
    {
        return $this->versionToken('global');
    }

    protected function editionVersion(int $editionId): string
    {
        return $this->versionToken('edition-'.$editionId);
    }

    protected function lawVersion(int $lawId): string
    {
        return $this->versionToken('law-'.$lawId);
    }

    protected function versionToken(string $scope): string
    {
        return Cache::rememberForever(
            $this->versionKey($scope),
            fn () => $this->newVersionToken()
        );
    }

    protected function versionKey(string $scope): string
    {
        return 'lotg:public:version:'.$scope;
    }

    protected function newVersionToken(): string
    {
        return Str::lower(Str::random(12));
    }

    /**
     * @param class-string<Model> $modelClass
     * @param array<int, int> $ids
     * @param array<int, string|array<string, mixed>|\Closure> $with
     */
    protected function orderedModels(string $modelClass, array $ids, array $with = []): EloquentCollection
    {
        /** @var Model $model */
        $model = new $modelClass();

        if ($ids === []) {
            return $model->newCollection();
        }

        /** @var EloquentCollection<int, Model> $items */
        $items = $modelClass::query()
            ->with($with)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $model->newCollection(
            Collection::make($ids)
                ->map(fn (int $id) => $items->get($id))
                ->filter()
                ->values()
                ->all()
        );
    }
}
