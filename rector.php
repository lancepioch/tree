<?php

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rector): void {
    $rector->paths([
        __DIR__.'/app',
    ]);

    $rector->skip([
        NullToStrictStringFuncCallArgRector::class,
    ]);

    $rector->sets([
        LevelSetList::UP_TO_PHP_82,
    ]);
};
