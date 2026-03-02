<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\FrankenPHP\Tests;

use Yiisoft\Yii\Runner\FrankenPHP\FrankenPHPApplicationRunner;

final class FrankenPHPApplicationRunnerTest extends TestCase
{
    public function testInstantiation(): void
    {
        $runner = new FrankenPHPApplicationRunner(__DIR__, false);
        $this->assertInstanceOf(FrankenPHPApplicationRunner::class, $runner);
    }
}
