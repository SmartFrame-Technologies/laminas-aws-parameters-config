<?php

declare(strict_types=1);

namespace SmartFrameTest\ParametersConfig\ParameterStore;

use Aws\Result;
use Aws\Ssm\SsmClient;
use PHPUnit\Framework\TestCase;
use SmartFrame\ParametersConfig\ParameterStore\ParameterStoreProvider;

class ParameterStoreProviderTest extends TestCase
{

    public function testGetConfig(): void
    {
        $ssmClientMock = $this->createPartialMock(SsmClient::class, ['getParametersByPath']);

        $result = [
            '/app/global/' => new Result([
                'Parameters' => [
                    ['Name' => '/app/global/mailer/transport/name', 'Value' => 'a-global', 'Type' => 'String'],
                    ['Name' => '/app/global/mailer/transport/host', 'Value' => 'b-global', 'Type' => 'String']
                ]
            ]),
            '/app/test/' => new Result([
                'Parameters' => [
                    ['Name' => '/app/test/mailer/transport/name', 'Value' => 'a-env', 'Type' => 'String'],
                    ['Name' => '/app/test/string-list', 'Value' => 'a,b,c', 'Type' => 'StringList'],
                    ['Name' => '/app/test/string-list-incomplete', 'Value' => 'd', 'Type' => 'StringList'],
                ]
            ]),
        ];

        $ssmClientMock
            ->expects(self::exactly(2))
            ->method('getParametersByPath')
            ->willReturnCallback(static function ($args) use ($result) {
                return $result[$args['Path']];
            });

        $parameterStoreProvider = new ParameterStoreProvider($ssmClientMock, getenv('APP_ENV'));

        $config = $parameterStoreProvider->getConfig();

        self::assertCount(4, $config);
        self::assertArrayHasKey('mailer/transport/host', $config);
        self::assertArrayHasKey('mailer/transport/name', $config);
        self::assertArrayHasKey('string-list', $config);
        self::assertArrayHasKey('string-list-incomplete', $config);
        self::assertSame($config['mailer/transport/name'], 'a-env');
        self::assertSame($config['mailer/transport/host'], 'b-global');
        self::assertIsArray($config['string-list']);
        self::assertCount(3, $config['string-list']);
        self::assertEquals('a', $config['string-list'][0]);
        self::assertEquals('b', $config['string-list'][1]);
        self::assertEquals('c', $config['string-list'][2]);
        self::assertIsArray($config['string-list-incomplete']);
        self::assertCount(1, $config['string-list-incomplete']);
    }
}
