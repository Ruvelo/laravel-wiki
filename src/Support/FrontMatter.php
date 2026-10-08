<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Support;

/**
 * The `key: value` subset of YAML front matter that Markdown tools write.
 * Enough for titles and dates without pulling in a YAML parser.
 */
final class FrontMatter
{
    /**
     * @return array{array<string, string>, string} the fields, and the body without them
     */
    public static function split(string $markdown): array
    {
        if (preg_match('/\A---\n(.*?)\n---\n?/s', $markdown, $match) !== 1) {
            return [[], $markdown];
        }

        $fields = [];
        foreach (explode("\n", $match[1]) as $line) {
            if (preg_match('/^([A-Za-z0-9_-]+):\s*(.*)$/', $line, $pair) === 1) {
                $fields[strtolower($pair[1])] = self::unquote(trim($pair[2]));
            }
        }

        return [$fields, ltrim(substr($markdown, strlen($match[0])), "\n")];
    }

    /**
     * @param  array<string, string>  $fields
     */
    public static function join(array $fields, string $body): string
    {
        $lines = array_map(fn (string $key, string $value) => $key.': '.self::quote($value), array_keys($fields), $fields);

        return "---\n".implode("\n", $lines)."\n---\n\n".rtrim($body)."\n";
    }

    private static function quote(string $value): string
    {
        return preg_match('/^[\p{L}\p{N} ._()\/-]+$/u', $value) === 1 && trim($value) === $value
            ? $value
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private static function unquote(string $value): string
    {
        if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
            $decoded = json_decode($value);

            return is_string($decoded) ? $decoded : trim($value, '"');
        }

        return strlen($value) >= 2 && $value[0] === "'" && str_ends_with($value, "'")
            ? str_replace("''", "'", substr($value, 1, -1))
            : $value;
    }
}
