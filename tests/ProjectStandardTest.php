<?php

declare(strict_types=1);

namespace Tests\OrthoCode\CodingStandards;

use OrthoCode\CodingStandards\ProjectStandard;
use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Core\Config\SyncConfig;
use OrthoCode\StandardsSync\Core\Rule\Rule;
use OrthoCode\StandardsSync\Formats\Xml\XmlElementWriter;
use OrthoCode\StandardsSync\Rules\PhpStan\MinLevel\PhpStanMinLevel;
use OrthoCode\StandardsSync\Rules\PhpUnit\PinnedAttributes\PhpUnitPinnedAttributes;
use OrthoCode\StandardsSync\Testing\FileContent;
use OrthoCode\StandardsSync\Testing\SyncTester;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** Checks this tier's contribution: the right content wired to the right file, and the places where it deliberately differs from the package tier. */
#[CoversClass(ProjectStandard::class)]
final class ProjectStandardTest extends TestCase
{
    public function testSyncingDropsTheManagedEditorConfigBlock(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./.editorconfig', $result);
        self::assertStringContainsString('ortho-code', $result['./.editorconfig']);
        self::assertStringContainsString('insert_final_newline = true', $result['./.editorconfig']);
    }

    // An application commits its lock file, which is the whole reason this tier ships its own list.
    public function testTheGitignoreBlockLeavesTheLockFileTracked(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./.gitignore', $result);
        self::assertStringContainsString('/vendor/', $result['./.gitignore']);
        self::assertStringNotContainsString('composer.lock', $result['./.gitignore']);
    }

    public function testSyncingCreatesAPhpUnitConfigFromTheTemplate(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./phpunit.xml', $result);
        self::assertStringContainsString('<directory>tests</directory>', $result['./phpunit.xml']);
    }

    public function testSyncingRewritesAPinnedFlagTheProjectTurnedOff(): void
    {
        $existing = FileContent::fromString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <phpunit bootstrap="tests/bootstrap.php" failOnRisky="false">
                    <testsuites>
                        <testsuite name="app">
                            <directory>tests/App</directory>
                        </testsuite>
                    </testsuites>
                </phpunit>
                XML,
        );

        $result = (new SyncTester())->sync($this->config(), [
            './phpunit.xml' => $existing,
        ]);

        self::assertStringContainsString('failOnRisky="true"', $result['./phpunit.xml']);
        // The template is one-shot: the project's own config survives, only the pins converge.
        self::assertStringContainsString('<directory>tests/App</directory>', $result['./phpunit.xml']);
    }

    // The shipped template must carry every pinned flag at its pinned value, or a bootstrapped repo starts looser than a converged one.
    public function testTheShippedPhpUnitTemplateCarriesEveryPinnedFlag(): void
    {
        $pins = array_find((new ProjectStandard())->rules(), static fn(Rule $rule): bool => $rule instanceof PhpUnitPinnedAttributes);
        self::assertInstanceOf(PhpUnitPinnedAttributes::class, $pins);

        $template = (string) file_get_contents(__DIR__ . '/../templates/project/phpunit.xml');

        foreach ($pins->pinnedAttributes() as $pin) {
            self::assertSame($pin->value(), XmlElementWriter::readAttribute($template, 'phpunit', $pin->name()), sprintf('The template must carry %s at its pinned value.', $pin->name()));
        }
    }

    public function testSyncingCreatesARectorConfigRegisteringTheSharedSet(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./rector.php', $result);
        self::assertStringContainsString('__DIR__ . \'/vendor/ortho-code/coding-standards/templates/project/rector.php\'', $result['./rector.php']);
    }

    public function testSyncingCreatesAPhpStanConfigImportingTheSharedRuleset(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./phpstan.neon', $result);
        self::assertStringContainsString('vendor/ortho-code/coding-standards/templates/project/phpstan.neon', $result['./phpstan.neon']);
    }

    public function testSyncingRaisesAPhpStanLevelBelowTheFloor(): void
    {
        $existing = FileContent::fromString(
            <<<'NEON'
                parameters:
                	level: 5
                	treatPhpDocTypesAsCertain: true
                NEON,
        );

        $result = (new SyncTester())->sync($this->config(), [
            './phpstan.neon' => $existing,
        ]);

        self::assertStringContainsString('level: 9', $result['./phpstan.neon']);
        self::assertStringContainsString('treatPhpDocTypesAsCertain: false', $result['./phpstan.neon']);
    }

    // The shipped ruleset must carry the floor as its own level, or a repo riding the import lands looser than one with a written level.
    public function testTheSharedRulesetCarriesTheFloorAsItsLevel(): void
    {
        $floor = array_find((new ProjectStandard())->rules(), static fn(Rule $rule): bool => $rule instanceof PhpStanMinLevel);
        self::assertInstanceOf(PhpStanMinLevel::class, $floor);

        $ruleset = (string) file_get_contents(__DIR__ . '/../templates/project/phpstan.neon');

        self::assertStringContainsString(sprintf('level: %d', $floor->minLevel()->value()), $ruleset);
    }

    public function testSyncingRequiresTheToolsItConfigures(): void
    {
        $result = (new SyncTester())->sync($this->config(), [
            './composer.json' => '{"name": "acme/consumer"}',
        ]);

        foreach (['armin/editorconfig-cli', 'friendsofphp/php-cs-fixer', 'phpstan/phpstan', 'phpunit/phpunit', 'rector/rector', 'slevomat/coding-standard', 'squizlabs/php_codesniffer'] as $tool) {
            self::assertStringContainsString($tool, $result['./composer.json'], sprintf('%s must be required, or its synced config enforces nothing.', $tool));
        }
    }

    // The tier ships one analyser fewer than the package tier, deliberately: an application adopting it should not be handed a psalm config it never asked for.
    public function testSyncingDoesNotRequirePsalm(): void
    {
        $result = (new SyncTester())->sync($this->config(), [
            './composer.json' => '{"name": "acme/consumer"}',
        ]);

        self::assertStringNotContainsString('vimeo/psalm', $result['./composer.json']);
        self::assertArrayNotHasKey('./psalm.xml', $result);
    }

    // Declared so a developer can run it, kept out of the aggregate so CI never fails on someone else's release cadence.
    public function testTheOutdatedCheckIsDeclaredButNotAggregated(): void
    {
        $result = (new SyncTester())->sync($this->config(), [
            './composer.json' => '{"name": "acme/consumer"}',
        ]);
        $scripts = json_decode($result['./composer.json'], true, flags: JSON_THROW_ON_ERROR)['scripts'];

        self::assertArrayHasKey('app-outdated', $scripts);
        self::assertNotContains('@app-outdated', $scripts['app-checks']);
    }

    // Nothing ties an aggregate's "@name" reference to the script it calls, so a rename would leave it calling a script that no longer exists.
    public function testEveryScriptReferenceNamesADeclaredScript(): void
    {
        $result = (new SyncTester())->sync($this->config(), [
            './composer.json' => '{"name": "acme/consumer"}',
        ]);
        $scripts = json_decode($result['./composer.json'], true, flags: JSON_THROW_ON_ERROR)['scripts'];

        foreach ($scripts as $name => $commands) {
            foreach ((array) $commands as $command) {
                if (str_starts_with($command, '@') && !str_starts_with($command, '@composer ')) {
                    self::assertArrayHasKey(substr($command, 1), $scripts, sprintf('Script "%s" calls a script the standard does not declare.', $name));
                }
            }
        }
    }

    private function config(): SyncConfig
    {
        return SyncConfig::create()->withRuleSet(new ProjectStandard($this->package()));
    }

    // In this repo the package is composer's root, so references would render bare; consumers see the vendor path.
    private function package(): Package
    {
        return new Package(dirname(__DIR__), 'vendor/ortho-code/coding-standards');
    }
}
