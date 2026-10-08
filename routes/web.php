<?php

use Ruvelo\Wiki\Http\Controllers\HistoryController;
use Ruvelo\Wiki\Http\Controllers\PageController;
use Ruvelo\Wiki\Http\Controllers\SearchController;
use Ruvelo\Wiki\Http\Middleware\AuthorizeEditing;
use Illuminate\Support\Facades\Route;

// Special pages live under "/_/". Slugs can never contain "_", so they can
// never collide with a page.
Route::group([
    'prefix' => config('wiki.path', 'wiki'),
    'domain' => config('wiki.domain'),
    'middleware' => config('wiki.middleware', ['web']),
    'as' => 'wiki.',
], function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/_/pages', [PageController::class, 'index'])->name('index');
    Route::get('/_/recent', [HistoryController::class, 'recent'])->name('recent');
    Route::get('/_/search', SearchController::class)->name('search');

    Route::middleware([...config('wiki.edit_middleware', ['auth']), AuthorizeEditing::class])->group(function () {
        Route::get('/_/new', [PageController::class, 'create'])->name('create');
        Route::post('/_/new', [PageController::class, 'store'])->name('store');
        Route::post('/_/preview', [PageController::class, 'preview'])->name('preview');
        Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::put('/{page}', [PageController::class, 'update'])->name('update');
        Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
        Route::post('/{page}/history/{revision}/restore', [HistoryController::class, 'restore'])
            ->scopeBindings()->name('restore');
    });

    Route::get('/{page}/history', [HistoryController::class, 'index'])->name('history');
    Route::get('/{page}/history/{revision}', [HistoryController::class, 'show'])
        ->scopeBindings()->name('revision');

    Route::get('/{slug}', [PageController::class, 'show'])->where('slug', '[^/]+')->name('show');
});
