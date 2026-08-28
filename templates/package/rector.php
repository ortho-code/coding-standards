<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\CodingStyle\Rector\String_\SimplifyQuoteEscapeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\FunctionLike\NarrowWideUnionReturnTypeRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

// The shared Rector set: evaluated by Rector inside the consumer project, never by this package.
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::PHP_85,
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->rules([
        ClassPropertyAssignToConstructorPromotionRector::class,
        DeclareStrictTypesRector::class,
    ]);

    $rectorConfig->skip([
        CatchExceptionNameMatchingTypeRector::class, // catch variables are named for their use
        LocallyCalledStaticMethodToNonStaticRector::class, // stateless private helpers stay static
        NarrowWideUnionReturnTypeRector::class, // a wide return type is a contract
        NewlineAfterStatementRector::class, // blank lines belong to ECS
        NewlineBeforeNewAssignSetRector::class, // blank lines belong to ECS
        NewlineBetweenClassLikeStmtsRector::class, // blank lines belong to ECS
        SimplifyQuoteEscapeRector::class, // contradicts single quotes always
        SortCallLikeNamedArgsRector::class, // argument order is the author's
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
    $rectorConfig->removeUnusedImports();
    $rectorConfig->reportUnusedSkips();
};
