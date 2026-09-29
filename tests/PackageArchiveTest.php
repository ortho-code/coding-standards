<?php

declare(strict_types=1);

namespace Tests\OrthoCode\CodingStandards;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/** Checks what composer installs of this package: the zip git archive builds, which leaves out every export-ignored path. */
#[CoversNothing]
final class PackageArchiveTest extends TestCase
{
    // Every consumer's sync reads the templates from its own install, so one export-ignored template breaks them all while this repository, which has the file, stays green.
    public function testNoTemplateIsExportIgnored(): void
    {
        $root = escapeshellarg(dirname(__DIR__));

        exec(sprintf('git -C %s ls-files templates', $root), $templates, $listed);
        self::assertSame(0, $listed);
        self::assertNotEmpty($templates);

        exec(sprintf('git -C %s check-attr export-ignore -- %s', $root, implode(' ', array_map(escapeshellarg(...), $templates))), $attributes, $checked);
        self::assertSame(0, $checked);

        self::assertSame([], array_values(array_filter($attributes, static fn(string $line): bool => str_ends_with($line, ': export-ignore: set'))));
    }
}
