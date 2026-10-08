<?php

/*
 * Builds the read-only demo published at https://ruvelo.github.io/laravel-wiki/demo/.
 *
 * Seeds a wiki in an in-memory database, renders every page through the
 * package's real routes and views, and writes the HTML out as static files.
 * Search, the one dynamic page, is answered in the browser from an index.
 *
 *   php demo/build.php <site-dir> [<shots-dir>]
 *
 * <shots-dir>, when given, receives the pages the screenshots are taken of:
 * without the demo banner, with server-side search, and the signed-in editor.
 */

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Foundation\Application;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Models\Revision;
use Ruvelo\Wiki\WikiServiceProvider;

require __DIR__.'/../vendor/autoload.php';

const HOST = 'https://ruvelo.github.io';
const PATH = 'laravel-wiki/demo';
const REPO = 'https://github.com/Ruvelo/laravel-wiki';

$site = rtrim($argv[1] ?? __DIR__.'/../build/site', '/');
$shots = isset($argv[2]) ? rtrim($argv[2], '/') : null;

foreach ([
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'APP_URL' => HOST,
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'SESSION_DRIVER' => 'array',
    'WIKI_PATH' => PATH,
    'WIKI_NAME' => 'Halyard Handbook',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $_SERVER[$key] = $value;
}

class DemoUser extends User
{
    protected $table = 'users';

    protected $guarded = [];
}

$app = Application::create(basePath: null, options: ['extra' => ['providers' => [WikiServiceProvider::class]]]);
$app['config']->set('auth.providers.users.model', DemoUser::class);

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
$app->make(ConsoleKernel::class)->call('migrate', ['--force' => true]);

// --- Seed -------------------------------------------------------------------

$people = [];
foreach (['Maya Okafor', 'Tom Reyes', 'Inès Laurent', 'Kenji Mori'] as $name) {
    $first = strtok($name, ' ');
    $people[$first] = DemoUser::forceCreate([
        'name' => $name,
        'email' => strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $first)).'@halyard.test',
        'password' => bin2hex(random_bytes(16)),
    ]);
}

$now = Carbon::now();
$edit = function (string $ago, string $who, string $title, string $body, ?string $summary = null) use ($people, $now) {
    Carbon::setTestNow($now->copy()->modify("-{$ago}"));
    $page = Page::query()->firstOrNew(['slug' => Page::slugFor($title)]);
    $page->commit($title, trim($body)."\n", $summary, $people[$who]);
    Carbon::setTestNow();
};

foreach (require __DIR__.'/content.php' as [$ago, $who, $title, $body, $summary]) {
    $edit($ago, $who, $title, $body, $summary);
}

// --- Render -----------------------------------------------------------------

$http = $app->make(HttpKernel::class);

$fetch = function (string $path, ?User $as = null) use ($app, $http): string {
    $app['auth']->forgetGuards();
    if ($as !== null) {
        $app['auth']->guard()->setUser($as);
    }

    $url = HOST.'/'.PATH.($path === '' ? '' : '/'.$path);
    $request = Request::create($url);
    $response = $http->handle($request);
    $http->terminate($request, $response);

    // Root-relative links, so the same files work on Pages and on localhost.
    return str_replace(HOST.'/', '/', $response->getContent());
};

$banner = '<div style="background:var(--wiki-accent);color:var(--wiki-on-accent);font:500 .875rem/1.4 var(--wiki-sans);padding:.55rem 16px;text-align:center">'
    .'You’re looking at a read-only demo of <a href="'.REPO.'" style="color:inherit;font-weight:700">ruvelo/laravel-wiki</a>. '
    .'Install it to create, edit and restore pages. '
    .'<a href="/laravel-wiki/" style="color:inherit">Read the docs</a></div>';

$write = function (string $root, string $path, string $html, bool $withBanner = true) use ($banner): void {
    if ($withBanner) {
        $html = preg_replace('/<body>/', '<body>'.$banner, $html, 1);
    }
    $file = $root.'/'.($path === '' ? '' : rawurldecode($path).'/').'index.html';
    is_dir(dirname($file)) || mkdir(dirname($file), 0777, true);
    file_put_contents($file, $html);
};

$demo = $site.'/demo';
$paths = ['', '_/pages', '_/recent'];

foreach (Page::query()->orderBy('title')->get() as $page) {
    $paths[] = $page->slug;
    $paths[] = $page->slug.'/history';
    foreach ($page->revisions()->pluck('id') as $id) {
        $paths[] = "{$page->slug}/history/{$id}";
    }
}

// Red links lead to a "no page here yet" page; render those too.
$existing = Page::query()->pluck('slug')->all();
$missing = $app['db']->table(Page::linksTable())->distinct()->pluck('target_slug')->diff($existing);
foreach ($missing as $slug) {
    $paths[] = $slug;
}

foreach ($paths as $path) {
    $write($demo, $path, $fetch($path));
}

// Search runs in the browser over this index, mirroring the server's rules:
// an exact title goes straight to the page, title matches rank first.
$index = Page::query()->orderBy('title')->get()->map(fn (Page $page) => [
    'title' => $page->title,
    'slug' => $page->slug,
    'url' => '/'.PATH.'/'.rawurlencode($page->slug),
    'body' => $page->plainText(),
    'edited' => $page->updated_at->diffForHumans(),
])->values();

$search = $fetch('_/search');
$search = str_replace('</body>', '<script>window.WIKI_INDEX='.json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG).';</script>'
    .'<script>'.file_get_contents(__DIR__.'/search.js').'</script></body>', $search);
$write($demo, '_/search', $search);

if ($shots !== null) {
    $deploy = Page::query()->where('slug', 'deploy-guide')->first();
    foreach ([
        'page' => $fetch(''),
        'dark' => $fetch('deploy-guide'),
        'diff' => $fetch('deploy-guide/history/'.$deploy->revisions()->value('id')),
        'search' => $fetch('_/search?q=deploy&all=1'),
        'editor' => $fetch('deploy-guide/edit', $people['Maya']),
    ] as $name => $html) {
        $write($shots, $name, $html, false);
    }
}

$revisions = Revision::query()->count();
fwrite(STDOUT, sprintf("Built %d demo pages (%d revisions) into %s\n", count($paths) + 1, $revisions, $demo));
