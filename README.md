## Akamai NetStorage Adapter for Flysystem PHP (UNOFFICIAL)

I created this package because [official akamai package](https://github.com/akamai/NetStorageKit-PHP) is not longer maintained by akamai and there not way to update to Flysystem >= 2.


# Requirement

-   PHP: ^8.1
-   Guzzle: ^7.5
-   Monolog: ^3.0
-   Flysystem: ^3.0

# Installation

```shell
$ composer require "igorkgg/flysystem-akamai-netstorage" -vvv
```

# Usage

```php

use League\Flysystem\Filesystem;
use League\Flysystem\AkamaiNetStorage\AkamaiNetStorageAdapter;
use League\Flysystem\AkamaiNetStorage\AkamaiNetStorageClientFactory;

...

$clientConfig = [
    'signer' => [
        'key' => 'key',
        'name' => 'keyName',
    ],
    'edgegrid' => [
        'base_uri' => 'testing.akamaihd.net.example.org',
        'timeout' => 300,
    ],
];

$cpCode = '123456';
$pathPrefix = 'working-dir';
$baseUrl = 'company.akamaihd.net.example.org';

$client = (new AkamaiNetStorageClientFactory($clientConfig))->getClient();

$adapter = new AkamaiNetStorageAdapter(
    $client,
    $cpCode,
    $pathPrefix,
    $baseUrl
);

$filesystem = new Filesystem($adapter);

$file = 'example.txt';
$filesystem->write($file, 'test content');
