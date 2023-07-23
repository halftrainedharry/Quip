<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */

use xPDO\xPDO;

try {
    // Add the package and model classes
    $modx->addPackage('Quip\\Model\\', $namespace['path'] . 'src/', null, 'Quip\\');

    // $modx->services->add('quip', function($c) use ($modx) {
    //     return new Quip\Quip($modx);
    // });
}
catch (\Throwable $t) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, $t->getMessage());
}