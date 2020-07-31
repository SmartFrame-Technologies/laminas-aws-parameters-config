<?php

declare(strict_types=1);

namespace SmartFrame\ParametersConfig;

use Aws\Ssm\SsmClient;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SsmClientFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SsmClient
    {
        return new SsmClient($container->get('config')['aws']['client']);
    }
}
