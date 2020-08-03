<?php

declare(strict_types=1);

namespace SmartFrame\ParametersConfig;

interface ParameterProviderInterface
{
    public function getConfig(): array;
}
