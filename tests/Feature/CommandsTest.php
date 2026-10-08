<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Tests\Feature;

use Illuminate\Support\Facades\File;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Tests\TestCase;
use Ruvelo\Wiki\Wiki;

class CommandsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/wiki-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->dir.'/guides');
        File::ensureDirectoryExists($this->dir.'/.obsidian');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_import_reads_titles_from_front_matter_headings_and_file_names(): void
    {
        File::put($this->dir.'/Deploy guide.md', "Ship it. See [[Ops#Paging]].\n");
        File::put($this->dir.'/guides/ops.md', "---\ntitle: \"Ops: the basics\"\ntags: x\n---\n\n## Paging\n\nCall us.\n");
        File::put($this->dir.'/guides/on-call_rota.md', "# On-call rota\n\nMonday: Ada\n");
        File::put($this->dir.'/.obsidian/workspace.md', 'ignored');
        File::put($this->dir.'/notes.txt', 'ignored');

        $user = $this->user();

        $this->artisan('wiki:import', ['path' => $this->dir, '--user' => $user->id])
            ->expectsOutputToContain('3 created, 0 updated, 0 unchanged, 0 skipped')
            ->assertSuccessful();

        $this->assertSame(['Deploy guide', 'On-call rota', 'Ops: the basics'], Page::query()->orderBy('title')->pluck('title')->all());
        $this->assertSame("Monday: Ada\n", Wiki::find('On-call rota')->body);
        $this->assertSame('Imported from guides/ops.md', Wiki::find('Ops: the basics')->revisions()->value('summary'));
        $this->assertSame((string) $user->id, Wiki::find('Deploy guide')->revisions()->value('user_id'));
    }

    public function test_import_again_only_saves_what_changed(): void
    {
        File::put($this->dir.'/a.md', "One\n");
        File::put($this->dir.'/b.md', "Two\n");
        $this->artisan('wiki:import', ['path' => $this->dir])->assertSuccessful();

        File::put($this->dir.'/b.md', "Two, edited\n");

        $this->artisan('wiki:import', ['path' => $this->dir, '--dry-run' => true])
            ->expectsOutputToContain('Dry run: 0 created, 1 updated, 1 unchanged')
            ->assertSuccessful();
        $this->assertSame("Two\n", Wiki::find('B')->body);

        $this->artisan('wiki:import', ['path' => $this->dir])->assertSuccessful();
        $this->assertSame("Two, edited\n", Wiki::find('B')->body);
        $this->assertSame(1, Wiki::find('A')->revisions()->count());
    }

    public function test_import_rejects_unknown_folders_and_users(): void
    {
        $this->artisan('wiki:import', ['path' => $this->dir.'/nope'])->assertFailed();
        $this->artisan('wiki:import', ['path' => $this->dir, '--user' => '999'])->assertFailed();
    }

    public function test_export_then_import_round_trips(): void
    {
        Wiki::create('Ops: the basics', "## Paging\n\nCall [[Deploy guide|us]].");
        Wiki::create('Café crème', 'Grind fine.');

        $out = $this->dir.'/export';
        $this->artisan('wiki:export', ['path' => $out])->expectsOutputToContain('Wrote 2 pages')->assertSuccessful();

        $this->assertFileExists($out.'/café-crème.md');
        $this->assertStringStartsWith("---\ntitle: \"Ops: the basics\"\n", File::get($out.'/ops-the-basics.md'));

        $this->artisan('wiki:export', ['path' => $out])->expectsOutputToContain('kept 2 existing files')->assertSuccessful();

        Page::query()->get()->each->delete();
        $this->artisan('wiki:import', ['path' => $out])->expectsOutputToContain('2 created')->assertSuccessful();

        $this->assertSame("## Paging\n\nCall [[Deploy guide|us]].\n", Wiki::find('Ops: the basics')->body);
        $this->assertSame('Café crème', Wiki::find('café-crème')->title);
    }
}
