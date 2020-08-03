<?php

declare(strict_types=1);

namespace SmartFrame\ParametersConfig\ParameterFile;

use SmartFrame\ParametersConfig\ParameterProviderInterface;

class ParameterFileProvider implements ParameterProviderInterface
{

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getConfig(): array
    {
        if (!\is_array($this->config)) {
            throw new \InvalidArgumentException('Wrong parameter file config');
        }

        return $this->config;
    }

}
