<?php

declare(strict_types=1);

return [
    'yiisoft/yii-runner-frankenphp/logger' => new class implements \Yiisoft\Di\ServiceProviderInterface {
        public function getDefinitions(): array
        {
            return [
                \Psr\Log\LoggerInterface::class => \Psr\Log\NullLogger::class,
            ];
        }

        public function getExtensions(): array
        {
            return [];
        }
    },
];
