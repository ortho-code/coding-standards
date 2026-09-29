<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\FinalClassFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

// The shared ECS set: evaluated by ECS inside the consumer project, never by this package.
return ECSConfig::configure()
    // What the synced .editorconfig says for PHP; a repository indenting with tabs overrides this in its own ecs.php.
    ->withSpacing(indentation: Option::INDENTATION_SPACES, lineEnding: "\n")
    ->withPreparedSets(
        arrays: true,
        casing: true,
        cleanup: true,
        comments: true,
        controlStructures: true,
        docblocks: true,
        namespaces: true,
    )
    // PER-CS 3.0 as ECS 13.3 ships it, loaded after the prepared sets: a later set re-registering a fixer resets its options, so PER-CS must come last to keep its own.
    // The set name is unversioned, so the ECS constraint is what pins it.
    ->withSets([SetList::PER_CS])
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
