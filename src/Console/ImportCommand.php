<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Support\FrontMatter;
use Ruvelo\Wiki\Wiki;
use SplFileInfo;

/**
 * Imports a folder of Markdown files: an Obsidian vault, a docs/ folder, a
 * GitHub wiki checkout, or a previous `wiki:export`.
 */
final class ImportCommand extends Command
{
    protected $signature = 'wiki:import
        {path : A folder of .md files, searched recursively}
        {--user= : ID of the user to credit the revisions to}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Import a folder of Markdown files into the wiki';

    public function handle(): int
    {
        $path = $this->argument('path');
        $root = is_string($path) ? rtrim($path, '/') : '';

        if (! is_dir($root)) {
            $this->components->error("Not a folder: {$root}");

            return self::FAILURE;
        }

        // A string from the command line, but an int when called from code.
        $userId = is_scalar($this->option('user')) ? (string) $this->option('user') : null;
        $author = $userId === null ? null : $this->author($userId);
        if ($userId !== null && $author === null) {
            $this->components->error("No user with ID {$userId}.");

            return self::FAILURE;
        }

        $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];

        foreach ($this->files($root) as $file) {
            $relative = ltrim(Str::after($file->getPathname(), $root), '/');
            [$title, $body] = $this->parse($file);

            if (Page::slugFor($title) === '') {
                $counts['skipped']++;
                $this->components->twoColumnDetail($relative, '<fg=yellow>skipped: no usable title</>');

                continue;
            }

            $page = Wiki::find($title);
            $status = match (true) {
                $page === null => 'created',
                $page->isUnchanged($title, $body) => 'unchanged',
                default => 'updated',
            };
            $counts[$status]++;

            if ($status !== 'unchanged' && ! $this->option('dry-run')) {
                Wiki::write($title, $body, "Imported from {$relative}", $author);
            }

            if ($this->output->isVerbose() || $status !== 'unchanged') {
                $this->components->twoColumnDetail($relative, $status);
            }
        }

        $this->newLine();
        $this->components->info(sprintf(
            '%s%d created, %d updated, %d unchanged, %d skipped.',
            $this->option('dry-run') ? 'Dry run: ' : '',
            ...array_values($counts),
        ));

        return self::SUCCESS;
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function files(string $root): iterable
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            /** @var SplFileInfo $file */
            $hidden = str_contains($file->getPathname(), '/.'); // .git, .obsidian, ...
            if (! $hidden && $file->isFile() && in_array(strtolower($file->getExtension()), ['md', 'markdown'], true)) {
                $files[] = $file;
            }
        }

        usort($files, fn (SplFileInfo $a, SplFileInfo $b) => strcmp($a->getPathname(), $b->getPathname()));

        return $files;
    }

    /**
     * The title comes from front matter, else a leading "# Heading" (which
     * is then dropped from the body, since the page shows its title), else
     * the file name.
     *
     * @return array{string, string}
     */
    private function parse(SplFileInfo $file): array
    {
        $markdown = str_replace("\r\n", "\n", (string) file_get_contents($file->getPathname()));
        [$meta, $body] = FrontMatter::split($markdown);

        $title = trim($meta['title'] ?? '');

        if ($title === '' && preg_match('/\A#\s+(.+)\n*/', $body, $heading) === 1) {
            $title = trim($heading[1]);
            $body = substr($body, strlen($heading[0]));
        }

        if ($title === '') {
            $name = $file->getBasename('.'.$file->getExtension());
            $title = str_contains($name, ' ') ? $name : Str::ucfirst(str_replace(['-', '_'], ' ', $name));
        }

        return [$title, rtrim($body)."\n"];
    }

    private function author(string $id): ?Authenticatable
    {
        $model = Wiki::userModel();
        $user = $model::query()->find($id);

        return $user instanceof Authenticatable ? $user : null;
    }
}
