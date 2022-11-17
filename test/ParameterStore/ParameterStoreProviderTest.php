<?php

declare(strict_types=1);

namespace SmartFrameTest\ParametersConfig\ParameterStore;

use Aws\Result;
use Aws\Ssm\SsmClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SmartFrame\ParametersConfig\ParameterStore\ParameterStoreProvider;

class ParameterStoreProviderTest extends TestCase
{

    public function testGetConfig(): void
    {
        $ssmClientMock = $this->preparePartialMockedSsmClient();
        $parameterStoreProvider = new ParameterStoreProvider($ssmClientMock, [getenv('APP_ENV')]);

        $config = $parameterStoreProvider->getConfig();

        self::assertCount(4, $config);
        self::assertArrayHasKey('mailer/transport/host', $config);
        self::assertArrayHasKey('mailer/transport/name', $config);
        self::assertArrayHasKey('string-list', $config);
        self::assertArrayHasKey('string-list-incomplete', $config);
        self::assertSame('a-env', $config['mailer/transport/name']);
        self::assertSame('b-global', $config['mailer/transport/host']);
        self::assertIsArray($config['string-list']);
        self::assertCount(3, $config['string-list']);
        self::assertEquals('a', $config['string-list'][0]);
        self::assertEquals('b', $config['string-list'][1]);
        self::assertEquals('c', $config['string-list'][2]);
        self::assertIsArray($config['string-list-incomplete']);
        self::assertCount(1, $config['string-list-incomplete']);
    }

    public function testAddRemoveEnv()
    {
        $ssmClientMock = $this->preparePartialMockedSsmClient();
        $parameterStoreProvider = new ParameterStoreProvider($ssmClientMock, []);

        $globalConfig = $parameterStoreProvider->getConfig();
        self::assertCount(2, $globalConfig);

        $parameterStoreProvider->addEnv(ParameterStoreProvider::TEST_ENV);
        $globalAndTestConfig = $parameterStoreProvider->getConfig();
        self::assertCount(4, $globalAndTestConfig);

        $parameterStoreProvider->removeEnv(ParameterStoreProvider::TEST_ENV);
        $nextGlobalConfig = $parameterStoreProvider->getConfig();
        self::assertCount(2, $nextGlobalConfig);
        self::assertSame($globalConfig, $nextGlobalConfig);
    }

    protected function preparePartialMockedSsmClient(): MockObject
    {
        $ssmClientMock = $this->createMock(SsmClient::class);

        $result = [
            '/app/'.ParameterStoreProvider::GLOBAL_ENV.'/' => new Result(
                [
                    'Parameters' => [
                        ['Name' => '/app/global/mailer/transport/name', 'Value' => 'a-global', 'Type' => 'String'],
                        ['Name' => '/app/global/mailer/transport/host', 'Value' => 'b-global', 'Type' => 'String']
                    ]
                ]
            ),
            '/app/'.ParameterStoreProvider::TEST_ENV.'/' => new Result(
                [
                    'Parameters' => [
                        ['Name' => '/app/test/mailer/transport/name', 'Value' => 'a-env', 'Type' => 'String'],
                        ['Name' => '/app/test/string-list', 'Value' => 'a,b,c', 'Type' => 'StringList'],
                        ['Name' => '/app/test/string-list-incomplete', 'Value' => 'd', 'Type' => 'StringList'],
                    ]
                ]
            ),
        ];

        $ssmClientMock
            ->method('__call')
            ->with('getParametersByPath')
            ->willReturnCallback(static function ($method, $args) use ($result) {
                return $result[$args[0]['Path']];
            });

        return $ssmClientMock;
    }
}
