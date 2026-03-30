<?php

use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\LawController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LawController::class, 'index'])->name('laws.index');
Route::get('/laws/{law:slug}', [LawController::class, 'show'])->name('laws.show');
Route::get('/updates', [ChangelogController::class, 'index'])->name('updates.index');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
