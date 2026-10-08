<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Ruvelo\Wiki\Http\Controllers\Api\PageController;
use Ruvelo\Wiki\Http\Controllers\Api\RevisionController;
use Ruvelo\Wiki\Http\Middleware\AuthorizeEditing;

// The JSON API. Off by default: set WIKI_API=true (or wiki.api.enabled).
Route::group([
    'prefix' => config('wiki.api.prefix', 'api/wiki'),
    'domain' => config('wiki.domain'),
    'middleware' => config('wiki.api.middleware', ['api', 'auth:sanctum']),
    'as' => 'wiki.api.',
], function () {
    Route::get('pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('pages/{page}', [PageController::class, 'show'])->name('pages.show');
    Route::get('pages/{page}/revisions', [RevisionController::class, 'index'])->name('revisions.index');
    Route::get('pages/{page}/revisions/{revision}', [RevisionController::class, 'show'])->scopeBindings()->name('revisions.show');

    Route::middleware(AuthorizeEditing::class)->group(function () {
        Route::post('pages', [PageController::class, 'store'])->name('pages.store');
        Route::patch('pages/{page}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
        Route::post('pages/{page}/revisions/{revision}/restore', [RevisionController::class, 'restore'])->scopeBindings()->name('revisions.restore');
    });
});
