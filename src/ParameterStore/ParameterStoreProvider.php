<?php

declare(strict_types=1);

namespace SmartFrame\ParametersConfig\ParameterStore;

use Aws\Ssm\SsmClient;
use GuzzleHttp\Exception\ClientException;
use SmartFrame\ParametersConfig\ParameterProviderInterface;

class ParameterStoreProvider implements ParameterProviderInterface
{
    protected const APPLICATION_PATH_PREFIX = 'app';
    protected const SSM_MAX_RESULT = 10;
    protected const MAX_RETRY = 3;
    protected const SLEEP_TIME_BEFORE_RETRY = 3; //in seconds

    /**
     * @var SsmClient
     */
    private $ssmClient;

    /**
     * @var string
     */
    private $env;

    /**
     * @var string
     */
    private $pathPrefix;

    public function __construct(SsmClient $ssmClient, string $env, string $pathPrefix = self::APPLICATION_PATH_PREFIX)
    {
        $this->ssmClient = $ssmClient;
        $this->env = $env;
        $this->pathPrefix = $pathPrefix;
    }

    public function getConfig(): array
    {
        //get global application parameters
        $globalPath = sprintf('/%s/global/', $this->pathPrefix);
        $parametersGlobal = $this->getParametersByPath($globalPath);

        //get ENV specific application parameters
        $envPath = sprintf('/%s/%s/', $this->pathPrefix, $this->env);
        $parameterEnv = $this->getParametersByPath($envPath);

        //merge both parameter groups
        $parameters = array_merge($parametersGlobal, $parameterEnv);

        $config = [];
        foreach ($parameters as $parameter) {
            //remove path prefix
            $name = str_replace([$envPath, $globalPath], '', $parameter['Name']);

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
