<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\FinalClassFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

// The shared ECS set: evaluated by ECS inside the consumer project, never by this package.
return ECSConfig::configure()
    ->withEditorConfig() // indentation and line endings come from the consumer's own .editorconfig
    ->withPhpCsFixerSets(
        perCS30: true, // pinned; the @PER-CS alias follows the newest set
    )
    ->withPreparedSets(
        arrays: true,
        casing: true,
        cleanup: true,
        comments: true,
        controlStructures: true,
        docblocks: true,
        namespaces: true,
    )
    ->withRules([
        DeclareStrictTypesFixer::class,
        FinalClassFixer::class,
    ])
    ->withConfiguredRule(SingleQuoteFixer::class, [
        'strings_containing_single_quote_chars' => true, // escape the apostrophe, never switch quotes
    ])
    ->withSkip([
        LineLengthFixer::class, // prose runs one sentence per line
        PhpdocLineSpanFixer::class, // short docblocks stay on one line
    ]);
