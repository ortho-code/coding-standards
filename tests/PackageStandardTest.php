<?php

declare(strict_types=1);

namespace Tests\OrthoCode\CodingStandards;

use OrthoCode\CodingStandards\PackageStandard;
use OrthoCode\StandardsSync\Core\Config\ConfigLoader;
use OrthoCode\StandardsSync\Core\Config\SyncConfig;
use OrthoCode\StandardsSync\Core\Filesystem\Path;
use OrthoCode\StandardsSync\Testing\SyncTester;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** Checks this package's contribution: the managed blocks, carrying the templates, land in the right files. */
#[CoversClass(PackageStandard::class)]
final class PackageStandardTest extends TestCase
{
    public function testSyncingDropsTheManagedEditorConfigBlock(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./.editorconfig', $result);
        self::assertStringContainsString('ortho-code', $result['./.editorconfig']);
        self::assertStringContainsString('insert_final_newline = true', $result['./.editorconfig']);
    }

    public function testSyncingDropsTheManagedGitignoreBlock(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./.gitignore', $result);
        self::assertStringContainsString('ortho-code', $result['./.gitignore']);
        self::assertStringContainsString('/vendor/', $result['./.gitignore']);
    }

    public function testTheShippedConfigWiresTheStandard(): void
    {
        $config = (new ConfigLoader())->loadFrom(Path::fromString(__DIR__ . '/../standards-sync.php'));

        $result = (new SyncTester())->sync($config);

        self::assertArrayHasKey('./.editorconfig', $result);
        self::assertArrayHasKey('./.gitignore', $result);
    }

    private function config(): SyncConfig
    {
        return SyncConfig::create()->withRuleSet(new PackageStandard());
    }
}
