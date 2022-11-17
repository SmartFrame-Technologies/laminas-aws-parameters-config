<?php

declare(strict_types=1);

namespace SmartFrame\ParametersConfig\ParameterStore;

use Aws\Ssm\SsmClient;
use GuzzleHttp\Exception\ClientException;
use SmartFrame\ParametersConfig\ParameterProviderInterface;

class ParameterStoreProvider implements ParameterProviderInterface
{
    public const GLOBAL_ENV = 'global';
    public const DEV_ENV = 'dev';
    public const TEST_ENV = 'test';
    protected const APPLICATION_PATH_PREFIX = 'app';
    protected const SSM_MAX_RESULT = 10;
    protected const MAX_RETRY = 3;
    protected const SLEEP_TIME_BEFORE_RETRY = 3; //in seconds

    private SsmClient $ssmClient;
    private array $envs = [self::GLOBAL_ENV];
    private string $pathPrefix;

    public function __construct(
        SsmClient $ssmClient,
        array $env = [],
        string $pathPrefix = self::APPLICATION_PATH_PREFIX
    ) {
        $this->ssmClient = $ssmClient;
        $this->envs = array_values(array_unique(array_merge($this->envs, $env)));
        $this->pathPrefix = $pathPrefix;
    }

    public function addEnv(string $env): self
    {
        if (!in_array($env, $this->envs)) {
            $this->envs[] = $env;
        }

        return $this;
    }

    public function removeEnv(string $env): self
    {
        $key = array_search($env, $this->envs);
        if ($key !== false) {
            $this->envs = array_values(array_splice($this->envs, $key, 1));
        }

        return $this;
    }

    public function getConfig(): array
    {
        $config = [];

        //merge envs parameters
        foreach ($this->envs as $env) {
            $envPath = sprintf('/%s/%s/', $this->pathPrefix, $env);
            $envConfig = $this->parseConfig($this->getParametersByPath($envPath), $envPath);
            $config = array_merge($config, $envConfig);
        }

        return $config;
    }

    protected function parseConfig(array $parameters, string $envPath): array
    {
        $config = [];

        foreach ($parameters as $parameter) {
            //remove path prefix
            $name = str_replace($envPath, '', $parameter['Name']);

            switch ($parameter['Type']) {
                case 'String':
                    $config[$name] = $parameter['Value'] !== 'null' ? $parameter['Value'] : null;
                    break;
                case 'StringList':
                    $config[$name] = $parameter['Value'] !== 'null' ? explode(',', $parameter['Value']) : null;
                    break;
            }
        }

        return $config;
    }

    protected function getParametersByPath(string $path): array
    {
        $parameters = [];
        $nextToken = null;

        $errors = 0;

        do {
            $args = [
                'Path' => $path,
                'Recursive' => true,
                'MaxResults' => self::SSM_MAX_RESULT,
            ];

            if ($nextToken) {
                $args['NextToken'] = $nextToken;
            }

            try {
                $result = $this->ssmClient->getParametersByPath($args);
            } catch (ClientException $clientException) {
                if (++$errors > self::MAX_RETRY) {
                    throw $clientException;
                }
                sleep(self::SLEEP_TIME_BEFORE_RETRY * $errors);
                continue;
            }

            $parameters = array_merge($parameters, $result->get('Parameters'));
            $nextToken = $result->get('NextToken');
        } while ($nextToken !== null);

        return $parameters;
    }

}
