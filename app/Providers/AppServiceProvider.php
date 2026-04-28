<?php

namespace App\Providers;

use App\Models\ChangelogEntry;
use App\Models\ContentNode;
use App\Models\Document;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawQa;
use App\Models\MediaAsset;
use App\Models\Permission;
use App\Models\User;
use App\Policies\ChangelogEntryPolicy;
use App\Policies\ContentNodePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EditionPolicy;
use App\Policies\LawPolicy;
use App\Policies\LawQaPolicy;
use App\Policies\MediaAssetPolicy;
use App\Services\LotgFeatureVisibility;
use App\Services\LotgPublicCache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LotgPublicCache::class);
        $this->app->singleton(LotgFeatureVisibility::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Edition::class, EditionPolicy::class);
        Gate::policy(Law::class, LawPolicy::class);
        Gate::policy(ContentNode::class, ContentNodePolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(LawQa::class, LawQaPolicy::class);
        Gate::policy(ChangelogEntry::class, ChangelogEntryPolicy::class);
        Gate::policy(MediaAsset::class, MediaAssetPolicy::class);

        Gate::define('access-admin', function (User $user): bool {
            return $user->hasPermissionTo(Permission::ADMIN_ACCESS);
        });

        View::composer('layouts.app', function (ViewContract $view): void {
            $activeEdition = Edition::current();
            $featureVisibility = $this->app->make(LotgFeatureVisibility::class);

            $view->with('publicFeatureNav', [
                LotgFeatureVisibility::FEATURE_QAS => $featureVisibility->enabled(LotgFeatureVisibility::FEATURE_QAS, $activeEdition),
                LotgFeatureVisibility::FEATURE_LEGACY_UPDATES => $featureVisibility->availableForAnyPublishedEdition(LotgFeatureVisibility::FEATURE_LEGACY_UPDATES),
            ]);
        });
    }
}
