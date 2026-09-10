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

    protected ?array $globalRows = null;

    protected array $editionRows = [];

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

    public function redirectUrl(string $featureKey, Edition|int|null $edition = null): ?string
    {
        if ($edition !== null) {
            $editionId = $edition instanceof Edition ? $edition->id : (int) $edition;
            $editionRedirectUrl = $this->editionOverrideRedirectUrl($featureKey, $editionId);

            if ($editionRedirectUrl !== null) {
                return $editionRedirectUrl;
            }

            if ($this->editionOverrideState($featureKey, $editionId) !== null) {
                return null;
            }
        }

        return $this->globalRedirectUrl($featureKey);
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
            $globalRedirectUrl = $this->globalRedirectUrl($featureKey);
            $editionRedirectUrl = $edition ? $this->editionOverrideRedirectUrl($featureKey, $edition->id) : null;
            $effectiveRedirectUrl = $this->redirectUrl($featureKey, $edition);

            $rows[] = [
                'key' => $featureKey,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'default_state' => (bool) $definition['default'],
                'global_state' => $globalState,
                'global_redirect_url' => $globalRedirectUrl,
                'edition_state' => $editionState,
                'edition_redirect_url' => $editionRedirectUrl,
                'effective_state' => $this->enabled($featureKey, $edition),
                'effective_redirect_url' => $effectiveRedirectUrl,
                'supports_redirect' => $featureKey === self::FEATURE_LEGACY_UPDATES,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $states
     */
    public function storeGlobalStates(array $states, array $redirectUrls = []): void
    {
        foreach ($this->keys() as $featureKey) {
            $state = $states[$featureKey] ?? null;
            $normalized = $this->normalizeGlobalStateInput($state);
            $redirectUrl = $state === 'redirect'
                ? $this->normalizeRedirectUrl($redirectUrls[$featureKey] ?? null)
                : null;
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
                'redirect_url' => $normalized ? $redirectUrl : null,
            ]);
        }

        $this->flushCache();
        $this->publicCache->touchGlobal();
    }

    /**
     * @param array<string, mixed> $states
     */
    public function storeEditionOverrideStates(Edition $edition, array $states, array $redirectUrls = []): void
    {
        foreach ($this->keys() as $featureKey) {
            $state = $states[$featureKey] ?? null;
            $normalized = $this->normalizeEditionStateInput($state);
            $redirectUrl = $state === 'redirect'
                ? $this->normalizeRedirectUrl($redirectUrls[$featureKey] ?? null)
                : null;
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
                'redirect_url' => $normalized ? $redirectUrl : null,
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
        $states = $this->loadGlobalRows();

        return isset($states[$featureKey]) ? (bool) $states[$featureKey]['is_enabled'] : null;
    }

    protected function editionOverrideState(string $featureKey, int $editionId): ?bool
    {
        $states = $this->loadEditionRows($editionId);

        return isset($states[$featureKey]) ? (bool) $states[$featureKey]['is_enabled'] : null;
    }

    protected function globalRedirectUrl(string $featureKey): ?string
    {
        $states = $this->loadGlobalRows();

        return $this->redirectUrlFromRow($states[$featureKey] ?? null);
    }

    protected function editionOverrideRedirectUrl(string $featureKey, int $editionId): ?string
    {
        $states = $this->loadEditionRows($editionId);

        return $this->redirectUrlFromRow($states[$featureKey] ?? null);
    }

    /**
     * @return array<string, array{is_enabled: bool, redirect_url: string|null}>
     */
    protected function loadGlobalRows(): array
    {
        if ($this->globalRows !== null) {
            return $this->globalRows;
        }

        $this->globalRows = $this->publicCache->rememberFeatureGlobalStates(
            fn () => FeatureVisibility::query()
                ->where('scope_type', FeatureVisibility::SCOPE_GLOBAL)
                ->get(['feature_key', 'is_enabled', 'redirect_url'])
                ->mapWithKeys(fn (FeatureVisibility $visibility) => [
                    $visibility->feature_key => [
                        'is_enabled' => (bool) $visibility->is_enabled,
                        'redirect_url' => $visibility->redirect_url,
                    ],
                ])
                ->all()
        );

        return $this->globalRows;
    }

    /**
     * @return array<string, array{is_enabled: bool, redirect_url: string|null}>
     */
    protected function loadEditionRows(int $editionId): array
    {
        if (array_key_exists($editionId, $this->editionRows)) {
            return $this->editionRows[$editionId];
        }

        $this->editionRows[$editionId] = $this->publicCache->rememberFeatureEditionStates(
            $editionId,
            fn () => FeatureVisibility::query()
                ->where('scope_type', FeatureVisibility::SCOPE_EDITION)
                ->where('edition_id', $editionId)
                ->get(['feature_key', 'is_enabled', 'redirect_url'])
                ->mapWithKeys(fn (FeatureVisibility $visibility) => [
                    $visibility->feature_key => [
                        'is_enabled' => (bool) $visibility->is_enabled,
                        'redirect_url' => $visibility->redirect_url,
                    ],
                ])
                ->all()
        );

        return $this->editionRows[$editionId];
    }

    protected function normalizeGlobalStateInput(mixed $value): ?bool
    {
        return match ($value) {
            true, 'true', 1, '1', 'enabled', 'redirect' => true,
            false, 'false', 0, '0', 'disabled' => false,
            default => null,
        };
    }

    protected function normalizeEditionStateInput(mixed $value): ?bool
    {
        return match ($value) {
            true, 'true', 1, '1', 'enabled', 'redirect' => true,
            false, 'false', 0, '0', 'disabled' => false,
            default => null,
        };
    }

    protected function normalizeRedirectUrl(mixed $value): ?string
    {
        $url = trim((string) $value);

        if ($url === '' || str_starts_with($url, '//') || ! str_starts_with($url, '/')) {
            return null;
        }

        return $url;
    }

    protected function redirectUrlFromRow(?array $row): ?string
    {
        if (! $row || ! ($row['is_enabled'] ?? false)) {
            return null;
        }

        $redirectUrl = trim((string) ($row['redirect_url'] ?? ''));

        return $redirectUrl !== '' ? $redirectUrl : null;
    }

    protected function flushCache(): void
    {
        $this->globalRows = null;
        $this->editionRows = [];
    }
}
