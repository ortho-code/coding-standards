<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\FinalClassFixer;
use PhpCsFixer\Fixer\Operator\NewWithParenthesesFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

// The shared ECS set: evaluated by ECS inside the consumer project, never by this package.
return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->sets([
        SetList::PSR_12,
        SetList::CLEAN_CODE,
    ]);

    $ecsConfig->rules([
        ArrayIndentationFixer::class,
        DeclareStrictTypesFixer::class,
        FinalClassFixer::class,
    ]);

    // Single quotes always: a string containing an apostrophe escapes it rather than switching to double quotes.
    $ecsConfig->ruleWithConfiguration(SingleQuoteFixer::class, [
        'strings_containing_single_quote_chars' => true,
    ]);

    // Named classes take parentheses, anonymous ones do not — where php-cs-fixer's own default moves in v4.
    $ecsConfig->ruleWithConfiguration(NewWithParenthesesFixer::class, [
        'anonymous_class' => false,
    ]);
};
