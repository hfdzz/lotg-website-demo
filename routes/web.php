<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\AdminHomeController;
use App\Http\Controllers\Admin\ChangelogAdminController;
use App\Http\Controllers\Admin\DocumentAdminController;
use App\Http\Controllers\Admin\EditionAdminController;
use App\Http\Controllers\Admin\LawAdminController;
use App\Http\Controllers\Admin\LawQaAdminController;
use App\Http\Controllers\Admin\MediaAdminController;
use App\Http\Controllers\Admin\NodeAdminController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LawController;
use App\Http\Controllers\QaController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LawController::class, 'hub'])->name('laws.index');
Route::get('/editions', [LawController::class, 'editions'])->name('editions.index');
Route::get('/laws', [LawController::class, 'index'])->name('laws.list');
Route::get('/laws/jump', [LawController::class, 'jump'])->name('laws.jump');
Route::get('/laws/{law:slug}', [LawController::class, 'show'])->name('laws.show');
Route::get('/updates', [ChangelogController::class, 'index'])->name('updates.index');
Route::get('/q-and-a', [QaController::class, 'index'])->name('qas.index');
Route::get('/q-and-a/{law:slug}', [QaController::class, 'show'])->name('qas.show');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminHomeController::class, 'index'])->name('home');
    Route::get('/editions', [EditionAdminController::class, 'index'])->name('editions.index');
    Route::get('/laws', [LawAdminController::class, 'home'])->name('laws.home');
    Route::get('/documents', [DocumentAdminController::class, 'home'])->name('documents.home');
    Route::get('/qas', [LawQaAdminController::class, 'home'])->name('qas.home');
    Route::get('/media', [MediaAdminController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaAdminController::class, 'store'])->name('media.store');
    Route::get('/media/{media}/edit', [MediaAdminController::class, 'edit'])->name('media.edit');
    Route::patch('/media/{media}', [MediaAdminController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [MediaAdminController::class, 'destroy'])->name('media.destroy');

    Route::get('/switch-edition', [EditionAdminController::class, 'go'])->name('editions.go');
    Route::post('/editions', [EditionAdminController::class, 'store'])->name('editions.store');
    Route::post('/editions/{edition}/activate', [EditionAdminController::class, 'activate'])->name('editions.activate');
    Route::post('/editions/{edition}/force-activate', [EditionAdminController::class, 'forceActivate'])->name('editions.force-activate');
    Route::patch('/editions/{edition}', [EditionAdminController::class, 'update'])->name('editions.update');
    Route::delete('/editions/{edition}', [EditionAdminController::class, 'destroy'])->name('editions.destroy');

    Route::prefix('editions/{edition}')->group(function () {
        Route::get('/', [LawAdminController::class, 'index'])->name('laws.index');
        Route::post('/laws', [LawAdminController::class, 'store'])->name('laws.store');
        Route::get('/laws/{law}/edit', [LawAdminController::class, 'edit'])->name('laws.edit');
        Route::patch('/laws/{law}', [LawAdminController::class, 'update'])->name('laws.update');
        Route::delete('/laws/{law}', [LawAdminController::class, 'destroy'])->name('laws.destroy');

        Route::get('/documents', [DocumentAdminController::class, 'index'])->name('documents.index');
        Route::post('/documents', [DocumentAdminController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}/edit', [DocumentAdminController::class, 'edit'])->name('documents.edit');
        Route::patch('/documents/{document}', [DocumentAdminController::class, 'update'])->name('documents.update');
        Route::delete('/documents/{document}', [DocumentAdminController::class, 'destroy'])->name('documents.destroy');

        Route::post('/laws/{law}/nodes', [NodeAdminController::class, 'store'])->name('nodes.store');
        Route::get('/laws/{law}/nodes/{node}/edit', [NodeAdminController::class, 'edit'])->name('nodes.edit');
        Route::patch('/laws/{law}/nodes/{node}', [NodeAdminController::class, 'update'])->name('nodes.update');
        Route::delete('/laws/{law}/nodes/{node}', [NodeAdminController::class, 'destroy'])->name('nodes.destroy');

        Route::get('/qas', [LawQaAdminController::class, 'index'])->name('qas.index');
        Route::get('/laws/{law}/qas', [LawQaAdminController::class, 'law'])->name('qas.law');
        Route::post('/laws/{law}/qas', [LawQaAdminController::class, 'store'])->name('qas.store');
        Route::get('/laws/{law}/qas/{qa}/edit', [LawQaAdminController::class, 'edit'])->name('qas.edit');
        Route::patch('/laws/{law}/qas/{qa}', [LawQaAdminController::class, 'update'])->name('qas.update');
        Route::delete('/laws/{law}/qas/{qa}', [LawQaAdminController::class, 'destroy'])->name('qas.destroy');

        Route::get('/updates', [ChangelogAdminController::class, 'index'])->name('changelog.index');
        Route::post('/updates', [ChangelogAdminController::class, 'store'])->name('changelog.store');
        Route::patch('/updates/{entry}', [ChangelogAdminController::class, 'update'])->name('changelog.update');
    });
});

Route::get('/{document:slug}/{page}', [DocumentController::class, 'page'])->name('documents.page');
Route::get('/{document:slug}', [DocumentController::class, 'show'])->name('documents.show');
