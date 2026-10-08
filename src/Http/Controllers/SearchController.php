<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ruvelo\Wiki\Models\Page;
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

        $pages = Page::query()
            ->matching($query)
            ->paginate(config('wiki.per_page', 50))
            ->withQueryString();

        return response()->view('wiki::search', ['query' => $query, 'pages' => $pages]);
    }
}
