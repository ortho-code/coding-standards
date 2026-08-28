<?php

declare(strict_types=1);

namespace Tests\OrthoCode\CodingStandards;

use OrthoCode\CodingStandards\PackageStandard;
use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Core\Config\ConfigLoader;
use OrthoCode\StandardsSync\Core\Config\SyncConfig;
use OrthoCode\StandardsSync\Core\Filesystem\Path;
use OrthoCode\StandardsSync\Core\Rule\Rule;
use OrthoCode\StandardsSync\Formats\Xml\XmlElementWriter;
use OrthoCode\StandardsSync\Rules\PhpUnit\PinnedAttributes\PhpUnitPinnedAttributes;
use OrthoCode\StandardsSync\Testing\FileContent;
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

    public function testSyncingCreatesAPhpUnitConfigFromTheTemplate(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./phpunit.xml', $result);
        // An org-only value proves the template landed rather than the engine skeleton.
        self::assertStringContainsString('cacheDirectory=".phpunit.cache"', $result['./phpunit.xml']);
        self::assertStringContainsString('ignoreIndirectDeprecations="true"', $result['./phpunit.xml']);
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
                XML
        );

        $result = (new SyncTester())->sync($this->config(), ['./phpunit.xml' => $existing]);

        self::assertStringContainsString('failOnRisky="true"', $result['./phpunit.xml']);
        // The template is one-shot: the project's own config survives, only the pins converge.
        self::assertStringContainsString('<directory>tests/App</directory>', $result['./phpunit.xml']);
    }

    // The shipped template must carry every pinned flag at its pinned value, or a bootstrapped repo starts looser than a converged one.
    public function testTheShippedPhpUnitTemplateCarriesEveryPinnedFlag(): void
    {
        $pins = array_find((new PackageStandard())->rules(), static fn (Rule $rule): bool => $rule instanceof PhpUnitPinnedAttributes);
        self::assertInstanceOf(PhpUnitPinnedAttributes::class, $pins);

        $template = (string) file_get_contents(__DIR__ . '/../templates/package/phpunit.xml');

        foreach ($pins->pinnedAttributes() as $pin) {
            self::assertSame($pin->value(), XmlElementWriter::readAttribute($template, 'phpunit', $pin->name()), sprintf('The template must carry %s at its pinned value.', $pin->name()));
        }
    }

    public function testSyncingCreatesAnEcsConfigRegisteringTheSharedSet(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./ecs.php', $result);
        self::assertStringContainsString('__DIR__ . \'/vendor/ortho-code/coding-standards/templates/package/ecs.php\'', $result['./ecs.php']);
    }

    public function testSyncingAddsTheSharedSetToAnExistingEcsConfig(): void
    {
        $existing = FileContent::fromString(
            <<<'PHP'
                <?php

                declare(strict_types=1);

                use Symplify\EasyCodingStandard\Config\ECSConfig;

                return ECSConfig::configure()
                    ->withPaths([
                        __DIR__ . '/src',
                    ]);
                PHP
        );

        $result = (new SyncTester())->sync($this->config(), ['./ecs.php' => $existing]);

        self::assertStringContainsString('__DIR__ . \'/vendor/ortho-code/coding-standards/templates/package/ecs.php\'', $result['./ecs.php']);
        self::assertStringContainsString('withPaths', $result['./ecs.php']);
    }

    public function testSyncingRequiresTheToolsItConfigures(): void
    {
        $result = (new SyncTester())->sync($this->config(), ['./composer.json' => '{"name": "acme/consumer"}']);

        foreach (['phpunit/phpunit', 'symplify/easy-coding-standard'] as $tool) {
            self::assertStringContainsString($tool, $result['./composer.json'], sprintf('%s must be required, or its synced config enforces nothing.', $tool));
        }
    }

    public function testSyncingDeclaresTheCheckScripts(): void
    {
        $result = (new SyncTester())->sync($this->config(), ['./composer.json' => '{"name": "acme/consumer"}']);

        self::assertArrayHasKey('./composer.json', $result);
        self::assertStringContainsString('app-checks', $result['./composer.json']);
        self::assertStringContainsString('"standards-sync sync --check"', $result['./composer.json']);
    }

    public function testSyncingSetsTheManifestSettingsTheStandardOwns(): void
    {
        $result = (new SyncTester())->sync($this->config(), ['./composer.json' => '{"name": "acme/consumer"}']);

        self::assertStringContainsString('sort-packages', $result['./composer.json']);
    }

    // Nothing ties an aggregate's "@name" reference to the script it calls, so a rename would leave it calling a script that no longer exists.
    public function testEveryScriptReferenceNamesADeclaredScript(): void
    {
        $result = (new SyncTester())->sync($this->config(), ['./composer.json' => '{"name": "acme/consumer"}']);
        $scripts = json_decode($result['./composer.json'], true, flags: JSON_THROW_ON_ERROR)['scripts'];

        foreach ($scripts as $name => $commands) {
            foreach ((array) $commands as $command) {
                if (str_starts_with($command, '@')) {
                    self::assertArrayHasKey(substr($command, 1), $scripts, sprintf('Script "%s" calls a script the standard does not declare.', $name));
                }
            }
        }
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
        return SyncConfig::create()->withRuleSet(new PackageStandard($this->package()));
    }

    // In this repo the package is composer's root, so references would render bare; consumers see the vendor path.
    private function package(): Package
    {
        return new Package(dirname(__DIR__), 'vendor/ortho-code/coding-standards');
    }
}
