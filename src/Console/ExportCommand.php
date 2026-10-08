<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Console;

use Illuminate\Console\Command;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Support\FrontMatter;

/**
 * Writes every page to {slug}.md with its title in front matter, so the
 * folder can be versioned, edited elsewhere, and read back by `wiki:import`.
 */
final class ExportCommand extends Command
{
    protected $signature = 'wiki:export
        {path : Folder to write the .md files to (created if missing)}
        {--force : Overwrite files that already exist}';

    protected $description = 'Export every page to a folder of Markdown files';

    public function handle(): int
    {
        $root = $this->pathArgument();

        if (! is_dir($root) && ! mkdir($root, 0777, true) && ! is_dir($root)) {
            $this->components->error("Couldn't create {$root}");

            return self::FAILURE;
        }

        $written = $kept = 0;

        Page::query()->orderBy('title')->lazy()->each(function (Page $page) use ($root, &$written, &$kept): void {
            $file = "{$root}/{$page->slug}.md";

            if (is_file($file) && ! $this->option('force')) {
                $kept++;
                $this->components->twoColumnDetail("{$page->slug}.md", '<fg=yellow>exists, kept (use --force)</>');

                return;
            }

            file_put_contents($file, FrontMatter::join([
                'title' => $page->title,
                'updated' => $page->updated_at->toIso8601String(),
            ], $page->body));
            $written++;
        });

        $this->components->info("Wrote {$written} pages to {$root}".($kept ? ", kept {$kept} existing files." : '.'));

        return self::SUCCESS;
    }

    private function pathArgument(): string
    {
        $path = $this->argument('path');

        return is_string($path) ? rtrim($path, '/') : '';
    }
}
