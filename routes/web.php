<?php

use App\Http\Controllers\LawController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LawController::class, 'index'])->name('laws.index');
Route::get('/laws/{law:slug}', [LawController::class, 'show'])->name('laws.show');
