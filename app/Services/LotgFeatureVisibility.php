<?php

namespace App\Services;

use App\Models\Edition;
use App\Models\FeatureVisibility;

class LotgFeatureVisibility
{
    public const FEATURE_DOCUMENTS = 'documents';
    public const FEATURE_QAS = 'qas';
    public const FEATURE_LEGACY_UPDATES = 'legacy_updates';

    /**
     * @var array<string, array{label: string, description: string, default: bool}>
     */
    protected array $definitions;

    /**
     * @var array<string, bool>|null
     */
    protected ?array $globalStates = null;

    /**
     * @var array<int, array<string, bool>>
     */
    protected array $editionStates = [];

    public function __construct(
        protected LotgPublicCache $publicCache
    )
    {
        /** @var array<string, array{label: string, description: string, default: bool}> $definitions */
        $definitions = config('lotg.public_features', []);

        $this->definitions = $definitions;
    }

    /**
     * @return array<string, array{label: string, description: string, default: bool}>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->definitions);
    }

    public function enabled(string $featureKey, Edition|int|null $edition = null): bool
    {
        $default = $this->defaultValue($featureKey);

        if ($edition !== null) {
            $editionId = $edition instanceof Edition ? $edition->id : (int) $edition;
            $editionState = $this->editionOverrideState($featureKey, $editionId);

            if ($editionState !== null) {
                return $editionState;
            }
        }

        $globalState = $this->globalState($featureKey);

        return $globalState ?? $default;
    }

    public function availableForAnyPublishedEdition(string $featureKey): bool
    {
        $publishedEditionIds = collect($this->publicCache->publishedEditionIds());

        if ($publishedEditionIds->isEmpty()) {
            return $this->enabled($featureKey, null);
        }

        foreach ($publishedEditionIds as $editionId) {
            if ($this->enabled($featureKey, (int) $editionId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, bool|null>
     */
    public function globalOverrideStates(): array
    {
        $states = [];

        foreach ($this->keys() as $featureKey) {
            $states[$featureKey] = $this->globalState($featureKey);
        }

        return $states;
    }

    /**
     * @return array<string, bool|null>
     */
    public function editionOverrideStates(Edition|int $edition): array
    {
        $editionId = $edition instanceof Edition ? $edition->id : (int) $edition;
        $states = [];

        foreach ($this->keys() as $featureKey) {
            $states[$featureKey] = $this->editionOverrideState($featureKey, $editionId);
        }

        return $states;
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     default_state: bool,
     *     global_state: bool|null,
     *     edition_state: bool|null,
     *     effective_state: bool
     * }>
     */
    public function adminRows(?Edition $edition = null): array
    {
        $rows = [];

        foreach ($this->definitions() as $featureKey => $definition) {
            $globalState = $this->globalState($featureKey);
            $editionState = $edition ? $this->editionOverrideState($featureKey, $edition->id) : null;

            $rows[] = [
                'key' => $featureKey,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'default_state' => (bool) $definition['default'],
                'global_state' => $globalState,
                'edition_state' => $editionState,
                'effective_state' => $this->enabled($featureKey, $edition),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $states
     */
    public function storeGlobalStates(array $states): void
    {
        foreach ($this->keys() as $featureKey) {
            $normalized = $this->normalizeGlobalStateInput($states[$featureKey] ?? null);
            $attributes = [
                'feature_key' => $featureKey,
                'scope_type' => FeatureVisibility::SCOPE_GLOBAL,
                'edition_id' => null,
            ];

            if ($normalized === null) {
                FeatureVisibility::query()->where($attributes)->delete();
                continue;
            }

            FeatureVisibility::query()->updateOrCreate($attributes, [
                'is_enabled' => $normalized,
            ]);
        }

        $this->flushCache();
        $this->publicCache->touchGlobal();
    }

    /**
     * @param array<string, mixed> $states
     */
    public function storeEditionOverrideStates(Edition $edition, array $states): void
    {
        foreach ($this->keys() as $featureKey) {
            $normalized = $this->normalizeEditionStateInput($states[$featureKey] ?? null);
            $attributes = [
                'feature_key' => $featureKey,
                'scope_type' => FeatureVisibility::SCOPE_EDITION,
                'edition_id' => $edition->id,
            ];

            if ($normalized === null) {
                FeatureVisibility::query()->where($attributes)->delete();
                continue;
            }

            FeatureVisibility::query()->updateOrCreate($attributes, [
                'is_enabled' => $normalized,
            ]);
        }

        $this->flushCache();
        $this->publicCache->touchEdition($edition->id);
    }

    protected function defaultValue(string $featureKey): bool
    {
        return (bool) ($this->definitions[$featureKey]['default'] ?? false);
    }

    protected function globalState(string $featureKey): ?bool
    {
        $states = $this->loadGlobalStates();

        return $states[$featureKey] ?? null;
    }

    protected function editionOverrideState(string $featureKey, int $editionId): ?bool
    {
        $states = $this->loadEditionStates($editionId);

        return $states[$featureKey] ?? null;
    }

    /**
     * @return array<string, bool>
     */
    protected function loadGlobalStates(): array
    {
        if ($this->globalStates !== null) {
            return $this->globalStates;
        }

        $this->globalStates = $this->publicCache->rememberFeatureGlobalStates(
            fn () => FeatureVisibility::query()
                ->where('scope_type', FeatureVisibility::SCOPE_GLOBAL)
                ->pluck('is_enabled', 'feature_key')
                ->map(fn ($value) => (bool) $value)
                ->all()
        );

        return $this->globalStates;
    }

    /**
     * @return array<string, bool>
     */
    protected function loadEditionStates(int $editionId): array
    {
        if (array_key_exists($editionId, $this->editionStates)) {
            return $this->editionStates[$editionId];
        }

        $this->editionStates[$editionId] = $this->publicCache->rememberFeatureEditionStates(
            $editionId,
            fn () => FeatureVisibility::query()
                ->where('scope_type', FeatureVisibility::SCOPE_EDITION)
                ->where('edition_id', $editionId)
                ->pluck('is_enabled', 'feature_key')
                ->map(fn ($value) => (bool) $value)
                ->all()
        );

        return $this->editionStates[$editionId];
    }

    protected function normalizeGlobalStateInput(mixed $value): ?bool
    {
        return match ($value) {
            true, 'true', 1, '1', 'enabled' => true,
            false, 'false', 0, '0', 'disabled' => false,
            default => null,
        };
    }

    protected function normalizeEditionStateInput(mixed $value): ?bool
    {
        return match ($value) {
            true, 'true', 1, '1', 'enabled' => true,
            false, 'false', 0, '0', 'disabled' => false,
            default => null,
        };
    }

    protected function flushCache(): void
    {
        $this->globalStates = null;
        $this->editionStates = [];
    }
}
