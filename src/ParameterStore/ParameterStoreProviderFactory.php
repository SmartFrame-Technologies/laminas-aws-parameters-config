<?php

declare(strict_types=1);

namespace SmartFrame\ParametersConfig\ParameterStore;

use Aws\Ssm\SsmClient;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ParameterStoreProviderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ParameterStoreProvider
    {
        return new ParameterStoreProvider($container->get(SsmClient::class), [APP_ENV]);
    }

}
