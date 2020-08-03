<?php

declare(strict_types=1);

namespace ConfigProvider\ParameterCache;

use SmartFrame\ParametersConfig\ParameterProviderInterface;

class ParameterCacheProvider implements ParameterProviderInterface
{
    public function getConfig(): array
    {
        return [];
    }

}
