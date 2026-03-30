<?php

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

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [LawAdminController::class, 'index'])->name('laws.index');
    Route::post('/laws', [LawAdminController::class, 'store'])->name('laws.store');
    Route::get('/laws/{law}/edit', [LawAdminController::class, 'edit'])->name('laws.edit');
    Route::patch('/laws/{law}', [LawAdminController::class, 'update'])->name('laws.update');

    Route::post('/laws/{law}/nodes', [NodeAdminController::class, 'store'])->name('nodes.store');
    Route::get('/laws/{law}/nodes/{node}/edit', [NodeAdminController::class, 'edit'])->name('nodes.edit');
    Route::patch('/laws/{law}/nodes/{node}', [NodeAdminController::class, 'update'])->name('nodes.update');
    Route::delete('/laws/{law}/nodes/{node}', [NodeAdminController::class, 'destroy'])->name('nodes.destroy');
});
