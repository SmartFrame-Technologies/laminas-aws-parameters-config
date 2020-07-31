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
                    ['Name' => '/app/global/mailer/transport/name', 'Value' => 'a-global'],
                    ['Name' => '/app/global/mailer/transport/host', 'Value' => 'b-global']
                ]
            ]),
            '/app/test/' => new Result([
                'Parameters' => [
                    ['Name' => '/app/test/mailer/transport/name', 'Value' => 'a-env'],
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

        self::assertCount(2, $config);
        self::assertArrayHasKey('mailer/transport/host', $config);
        self::assertArrayHasKey('mailer/transport/name', $config);
        self::assertSame($config['mailer/transport/name'], 'a-env');
        self::assertSame($config['mailer/transport/host'], 'b-global');
    }
}
