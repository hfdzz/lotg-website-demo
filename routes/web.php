<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\ChangelogAdminController;
use App\Http\Controllers\Admin\EditionAdminController;
use App\Http\Controllers\Admin\LawAdminController;
use App\Http\Controllers\Admin\NodeAdminController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\LawController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LawController::class, 'index'])->name('laws.index');
Route::get('/laws/{law:slug}', [LawController::class, 'show'])->name('laws.show');
Route::get('/updates', [ChangelogController::class, 'index'])->name('updates.index');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        $edition = \App\Models\Edition::current();
        abort_unless($edition, 404);

        return redirect()->route('admin.laws.index', ['edition' => $edition]);
    })->name('home');

    Route::get('/switch-edition', [EditionAdminController::class, 'go'])->name('editions.go');
    Route::post('/editions', [EditionAdminController::class, 'store'])->name('editions.store');
    Route::patch('/editions/{edition:slug}', [EditionAdminController::class, 'update'])->name('editions.update');

    Route::prefix('editions/{edition:slug}')->group(function () {
        Route::get('/', [LawAdminController::class, 'index'])->name('laws.index');
        Route::post('/laws', [LawAdminController::class, 'store'])->name('laws.store');
        Route::get('/laws/{law}/edit', [LawAdminController::class, 'edit'])->name('laws.edit');
        Route::patch('/laws/{law}', [LawAdminController::class, 'update'])->name('laws.update');

        Route::post('/laws/{law}/nodes', [NodeAdminController::class, 'store'])->name('nodes.store');
        Route::get('/laws/{law}/nodes/{node}/edit', [NodeAdminController::class, 'edit'])->name('nodes.edit');
        Route::patch('/laws/{law}/nodes/{node}', [NodeAdminController::class, 'update'])->name('nodes.update');
        Route::delete('/laws/{law}/nodes/{node}', [NodeAdminController::class, 'destroy'])->name('nodes.destroy');

        Route::get('/updates', [ChangelogAdminController::class, 'index'])->name('changelog.index');
        Route::post('/updates', [ChangelogAdminController::class, 'store'])->name('changelog.store');
        Route::patch('/updates/{entry}', [ChangelogAdminController::class, 'update'])->name('changelog.update');
    });
});
