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
use OrthoCode\StandardsSync\Rules\Composer\Script\ComposerScript;
use OrthoCode\StandardsSync\Rules\PhpStan\MinLevel\PhpStanMinLevel;
use OrthoCode\StandardsSync\Rules\PhpUnit\PinnedAttributes\PhpUnitPinnedAttributes;
use OrthoCode\StandardsSync\Rules\Psalm\LoosestErrorLevel\PsalmErrorLevel;
use OrthoCode\StandardsSync\Rules\Psalm\LoosestErrorLevel\PsalmLoosestErrorLevel;
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
        $pins = array_find((new PackageStandard())->rules(), static fn(Rule $rule): bool => $rule instanceof PhpUnitPinnedAttributes);
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
                PHP,
        );

        $result = (new SyncTester())->sync($this->config(), [
            './ecs.php' => $existing,
        ]);

        self::assertStringContainsString('__DIR__ . \'/vendor/ortho-code/coding-standards/templates/package/ecs.php\'', $result['./ecs.php']);
        self::assertStringContainsString('withPaths', $result['./ecs.php']);
    }

    public function testSyncingCreatesARectorConfigRegisteringTheSharedSet(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./rector.php', $result);
        self::assertStringContainsString('__DIR__ . \'/vendor/ortho-code/coding-standards/templates/package/rector.php\'', $result['./rector.php']);
    }

    public function testSyncingCreatesAPhpStanConfigImportingTheSharedRuleset(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./phpstan.neon', $result);
        self::assertStringContainsString('vendor/ortho-code/coding-standards/templates/package/phpstan.neon', $result['./phpstan.neon']);
    }

    public function testSyncingRaisesAPhpStanLevelBelowTheFloorAndRewritesThePinnedValue(): void
    {
        $existing = FileContent::fromString(
            <<<'NEON'
                parameters:
                	level: 2
                	treatPhpDocTypesAsCertain: true
                NEON,
        );

        $result = (new SyncTester())->sync($this->config(), [
            './phpstan.neon' => $existing,
        ]);

        self::assertStringContainsString('level: 6', $result['./phpstan.neon']);
        self::assertStringContainsString('treatPhpDocTypesAsCertain: false', $result['./phpstan.neon']);
    }

    // The shipped ruleset must carry the floor as its own level, or a repo riding the import lands looser than one with a written level.
    public function testTheSharedRulesetCarriesTheFloorAsItsLevel(): void
    {
        $floor = array_find((new PackageStandard())->rules(), static fn(Rule $rule): bool => $rule instanceof PhpStanMinLevel);
        self::assertInstanceOf(PhpStanMinLevel::class, $floor);

        $ruleset = (string) file_get_contents(__DIR__ . '/../templates/package/phpstan.neon');

        self::assertStringContainsString(sprintf('level: %d', $floor->minLevel()->value()), $ruleset);
    }

    public function testSyncingCreatesAPsalmConfigFromTheTemplate(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./psalm.xml', $result);
        // psalm defaults findUnusedCode to true, which flags a library's whole public API; the template turns it off.
        self::assertStringContainsString('findUnusedCode="false"', $result['./psalm.xml']);
    }

    public function testSyncingTightensALooserPsalmErrorLevelWithoutReseeding(): void
    {
        $existing = FileContent::fromString(
            <<<'XML'
                <?xml version="1.0"?>
                <psalm errorLevel="8">
                    <projectFiles>
                        <directory name="app" />
                    </projectFiles>
                </psalm>
                XML,
        );

        $result = (new SyncTester())->sync($this->config(), [
            './psalm.xml' => $existing,
        ]);

        self::assertStringContainsString('errorLevel="2"', $result['./psalm.xml']);
        // The template is one-shot: the project's own config survives, only the level converges.
        self::assertStringContainsString('<directory name="app" />', $result['./psalm.xml']);
    }

    // The shipped template must carry a level at or stricter than the declared limit, or a bootstrapped repo starts looser than the standard allows.
    public function testTheShippedPsalmTemplateCarriesALevelAtOrStricterThanTheLimit(): void
    {
        $limit = array_find((new PackageStandard())->rules(), static fn(Rule $rule): bool => $rule instanceof PsalmLoosestErrorLevel);
        self::assertInstanceOf(PsalmLoosestErrorLevel::class, $limit);

        $template = (string) file_get_contents(__DIR__ . '/../templates/package/psalm.xml');
        $written = XmlElementWriter::readAttribute($template, 'psalm', 'errorLevel');

        self::assertNotNull($written, 'The template must write its errorLevel explicitly.');
        self::assertTrue(PsalmErrorLevel::fromConfigValue($written)->isAtMost($limit->loosest()));
    }

    public function testSyncingCreatesAnAnnotatedRenovateConfigWhenTheProjectHasNone(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./renovate.json5', $result);
        self::assertStringContainsString('"local>ortho-code/coding-standards:renovate-package-preset" // ortho-code coding standards; sync re-adds this entry', $result['./renovate.json5']);
    }

    public function testSyncingAddsTheExtendsEntryToAnExistingRenovateConfig(): void
    {
        $existing = FileContent::fromString(
            <<<'JSON'
                {
                  "extends": [
                    "config:recommended"
                  ]
                }
                JSON,
        );

        $result = (new SyncTester())->sync($this->config(), [
            './renovate.json' => $existing,
        ]);

        self::assertStringContainsString('"local>ortho-code/coding-standards:renovate-package-preset"', $result['./renovate.json']);
        self::assertStringContainsString('"config:recommended"', $result['./renovate.json']);
        self::assertArrayNotHasKey('./renovate.json5', $result);
    }

    // The preset the extends entry points at must parse, or every consumer's renovate run breaks at once.
    public function testTheShippedRenovatePresetIsValidStrictJson(): void
    {
        $preset = json_decode((string) file_get_contents(__DIR__ . '/../renovate-package-preset.json'), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($preset);
        self::assertArrayHasKey('extends', $preset);
    }

    public function testSyncingDropsTheManagedWorkflowBlock(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./.github/workflows/standards.yml', $result);
        self::assertStringContainsString('ortho-code', $result['./.github/workflows/standards.yml']);
    }

    // The workflow calls the script by name and nothing in the engine ties them together, so a rename would leave CI running nothing.
    public function testTheWorkflowCallsTheScriptTheStandardDeclares(): void
    {
        $aggregate = array_find(
            (new PackageStandard())->rules(),
            static fn(Rule $rule): bool => $rule instanceof ComposerScript && $rule->name() === 'app-checks',
        );
        self::assertInstanceOf(ComposerScript::class, $aggregate);

        $result = (new SyncTester())->sync($this->config());

        self::assertStringContainsString(sprintf('composer %s', $aggregate->name()), $result['./.github/workflows/standards.yml']);
    }

    public function testSyncingDropsTheManagedReleaseWorkflowBlock(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./.github/workflows/release.yml', $result);
        self::assertStringContainsString('ortho-code', $result['./.github/workflows/release.yml']);
        // The notes are the changelog section for the tag, so the workflow must read that file.
        self::assertStringContainsString('CHANGELOG.md', $result['./.github/workflows/release.yml']);
    }

    public function testSyncingRequiresThePhpVersionTheWorkflowRuns(): void
    {
        $result = (new SyncTester())->sync($this->config(), [
            './composer.json' => '{"name": "acme/consumer"}',
        ]);

        self::assertStringContainsString('"php"', $result['./composer.json']);
    }

    public function testSyncingRequiresTheToolsItConfigures(): void
    {
        $result = (new SyncTester())->sync($this->config(), [
            './composer.json' => '{"name": "acme/consumer"}',
        ]);

        foreach (['phpstan/phpstan', 'phpunit/phpunit', 'rector/rector', 'symplify/easy-coding-standard', 'vimeo/psalm'] as $tool) {
            self::assertStringContainsString($tool, $result['./composer.json'], sprintf('%s must be required, or its synced config enforces nothing.', $tool));
        }
    }

    public function testSyncingDeclaresTheCheckScripts(): void
    {
        $result = (new SyncTester())->sync($this->config(), [
            './composer.json' => '{"name": "acme/consumer"}',
        ]);

        self::assertArrayHasKey('./composer.json', $result);
        self::assertStringContainsString('app-checks', $result['./composer.json']);
        self::assertStringContainsString('"standards-sync sync --check"', $result['./composer.json']);
    }

    public function testSyncingSetsTheManifestSettingsTheStandardOwns(): void
    {
        $result = (new SyncTester())->sync($this->config(), [
            './composer.json' => '{"name": "acme/consumer"}',
        ]);

        self::assertStringContainsString('sort-packages', $result['./composer.json']);
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
