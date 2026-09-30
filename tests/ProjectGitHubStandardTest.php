<?php

declare(strict_types=1);

namespace Tests\OrthoCode\CodingStandards;

use OrthoCode\CodingStandards\ProjectGitHubStandard;
use OrthoCode\CodingStandards\ProjectStandard;
use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Core\Config\SyncConfig;
use OrthoCode\StandardsSync\Core\Rule\Rule;
use OrthoCode\StandardsSync\Rules\Composer\Script\ComposerScript;
use OrthoCode\StandardsSync\Testing\SyncTester;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** Checks the GitHub companion, and the seam it shares with the tier it is declared beside. */
#[CoversClass(ProjectGitHubStandard::class)]
final class ProjectGitHubStandardTest extends TestCase
{
    public function testSyncingWritesTheShippedWorkflow(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertSame(file_get_contents(__DIR__ . '/../templates/project/ci-standards.yml'), $result['./.github/workflows/standards.yml']);
    }

    // The workflow shipped as a block labelled ortho-code before, so the label the rule takes over must be that one.
    public function testSyncingTakesOverTheWorkflowTheStandardShippedAsABlock(): void
    {
        $shipped = (string) preg_replace('~^( *)- id: \S+\n *~m', '$1- ', (string) file_get_contents(__DIR__ . '/../templates/project/ci-standards.yml'));

        $result = (new SyncTester())->sync($this->config(), [
            './.github/workflows/standards.yml' => "# >>> ortho-code - managed >>>\n" . $shipped . "# <<< ortho-code <<<\n",
        ]);

        self::assertStringNotContainsString('ortho-code', $result['./.github/workflows/standards.yml']);
        self::assertMatchesRegularExpression('~^ +id: checks$~m', $result['./.github/workflows/standards.yml']);
    }

    // The companion carries CI and nothing else: everything a repository installs comes from the tier beside it, and the lock beside the config records what the workflow declares.
    public function testTheCompanionTouchesNoFileTheTierOwns(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertSame(['./.github/workflows/standards.yml', './standards-sync.lock'], array_keys($result));
    }

    // The workflow calls the script by name across a standard boundary, where nothing in the engine ties the two together at all.
    public function testTheWorkflowCallsTheScriptTheTierDeclares(): void
    {
        $aggregate = array_find(
            (new ProjectStandard($this->package()))->rules(),
            static fn(Rule $rule): bool => $rule instanceof ComposerScript && $rule->name() === 'app-checks',
        );
        self::assertInstanceOf(ComposerScript::class, $aggregate);

        $result = (new SyncTester())->sync($this->config());

        self::assertStringContainsString(sprintf('composer %s', $aggregate->name()), $result['./.github/workflows/standards.yml']);
    }

    // The runtime the workflow installs is the floor the tier requires; the two are raised together.
    public function testTheWorkflowRunsTheTiersRuntimeFloor(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertStringContainsString('php-version: \'8.2\'', $result['./.github/workflows/standards.yml']);
    }

    private function config(): SyncConfig
    {
        return SyncConfig::create()->withRuleSet(new ProjectGitHubStandard($this->package()));
    }

    private function package(): Package
    {
        return new Package(dirname(__DIR__), 'vendor/ortho-code/coding-standards');
    }
}
