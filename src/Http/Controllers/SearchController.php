<?php

namespace Ruvelo\Wiki\Http\Controllers;

use Ruvelo\Wiki\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->view('wiki::search', ['query' => '', 'pages' => null]);
        }

        // An exact title match goes straight to the page, like a wiki "Go".
        $exact = Page::query()->where('slug', Page::slugFor($query))->first();
        if ($exact !== null && ! $request->boolean('all')) {
            return redirect()->route('wiki.show', $exact);
        }

        // "!" rather than backslash: SQLite has no default escape character
        // and MySQL treats backslash specially, but "!" means the same on all.
        $term = '%'.strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
        $like = fn (string $column) => "{$column} like ? escape '!'";

        $pages = Page::query()
            ->where(fn ($where) => $where->whereRaw($like('title'), [$term])->orWhereRaw($like('body'), [$term]))
            ->orderByRaw('case when '.$like('title').' then 0 else 1 end', [$term])
            ->orderBy('title')
            ->paginate(config('wiki.per_page', 50))
            ->withQueryString();

        return response()->view('wiki::search', ['query' => $query, 'pages' => $pages]);
    }
}
