<?php

declare(strict_types=1);

namespace SmartFrame\ParametersConfig;

use Aws\Ssm\SsmClient;
use SmartFrame\ParametersConfig\ParameterStore\ParameterStoreProvider;
use SmartFrame\ParametersConfig\ParameterStore\ParameterStoreProviderFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                SsmClient::class => SsmClientFactory::class,
                ParameterStoreProvider::class => ParameterStoreProviderFactory::class
            ],
        ];
    }
}
