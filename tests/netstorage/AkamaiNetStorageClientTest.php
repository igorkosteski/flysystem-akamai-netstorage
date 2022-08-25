<?php

namespace League\Flysystem\AkamaiNetStorage\Tests;

use League\Flysystem\AkamaiNetStorage\AkamaiNetStorageClient;
use Exception;

class AkamaiNetStorageClientTest extends \PHPUnit\Framework\TestCase
{
    public function testValidGetClient()
    {
        $config = [
            'signer' => [
                'key' => 'netstorage-key',
                'name' => 'key-name',
            ],
            'edgegrid' => [
                'base_uri' => 'testing.akamaihd.net.example.org',
                'timeout' => 300,
            ]
        ];

        $client = new AkamaiNetStorageClient($config);

        $this->assertInstanceOf(AkamaiNetStorageClient::class, $client);
    }

    /**
     * @dataProvider invalidConfigProvider
     */
    public function testInvalidGetClient(Exception $excepton, array $config)
    {
        $this->expectExceptionObject($excepton);

        new AkamaiNetStorageClient($config);
    }

    public function invalidConfigProvider()
    {
        return [
            [
                new Exception('The signer key is not set.'),
                [
                    'edgegrid' => [
                        'base_uri' => 'testing.akamaihd.net.example.org',
                    ],
                ]
            ],
            [
                new Exception('The signer key is not set.'),
                [
                    'signer' => [
                        'name' => 'key-name',
                    ],
                    'edgegrid' => [
                        'base_uri' => 'testing.akamaihd.net.example.org',
                    ],
                ]
            ],
            [
                new Exception('The signer name is not set.'),
                [
                    'signer' => [
                        'key' => 'netstorage-key',
                    ],
                    'edgegrid' => [
                        'base_uri' => 'testing.akamaihd.net.example.org',
                    ],
                ]
            ],
            [
                new Exception('The edgegrid base_uri is not set.'),
                [
                    'signer' => [
                        'key' => 'netstorage-key',
                        'name' => 'key-name',
                    ],
                    'edgegrid' => [],
                ]
            ],
            [
                new Exception('The edgegrid base_uri is not set.'),
                [
                    'signer' => [
                        'key' => 'netstorage-key',
                        'name' => 'key-name',
                    ],
                ]
            ],
        ];
    }
}
