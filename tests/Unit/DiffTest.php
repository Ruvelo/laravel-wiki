<?php

namespace Ruvelo\Wiki\Tests\Unit;

use Ruvelo\Wiki\Support\Diff;
use PHPUnit\Framework\TestCase;

class DiffTest extends TestCase
{
    public function test_it_marks_added_removed_and_kept_lines(): void
    {
        $ops = Diff::lines("one\ntwo\nthree\nfour", "one\n2\nthree\nfour\nfive");

        $this->assertSame([
            [' ', 'one'],
            ['-', 'two'],
            ['+', '2'],
            [' ', 'three'],
            [' ', 'four'],
            ['+', 'five'],
        ], $ops);

        $this->assertSame(['added' => 2, 'removed' => 1], Diff::stats($ops));
    }

    public function test_a_first_version_is_all_additions(): void
    {
        $this->assertSame([['+', 'a'], ['+', 'b']], Diff::lines('', "a\nb"));
    }

    public function test_identical_text_has_no_changes(): void
    {
        $this->assertSame(['added' => 0, 'removed' => 0], Diff::stats(Diff::lines("a\nb", "a\nb")));
    }

    public function test_long_unchanged_stretches_collapse_to_a_marker(): void
    {
        $old = implode("\n", range(1, 20));
        $new = str_replace("\n10\n", "\nten\n", $old);

        $this->assertSame([
            ['…', 6],
            [' ', '7'], [' ', '8'], [' ', '9'],
            ['-', '10'], ['+', 'ten'],
            [' ', '11'], [' ', '12'], [' ', '13'],
            ['…', 7],
        ], Diff::collapse(Diff::lines($old, $new)));
    }

    public function test_huge_rewrites_fall_back_to_a_block_replacement(): void
    {
        $old = implode("\n", range(1, 1500));
        $new = implode("\n", array_map(fn ($n) => "x$n", range(1, 1500)));

        $this->assertSame(['added' => 1500, 'removed' => 1500], Diff::stats(Diff::lines($old, $new)));
    }
}
