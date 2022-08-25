<?php

namespace League\Flysystem\AkamaiNetStorage\Tests;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

class AkamaiNetStorageAdapterTest extends \PHPUnit\Framework\TestCase
{
    protected $key = "key";

    protected $keyName = 'keyName';

    protected $host = 'testing.akamaihd.net.example.org';

    protected $cpCode = '123456';

    protected $prefix = 'test';

    protected $workingDir = 'working-dir';

    /**
     * @var \League\Flysystem\Filesystem
     */
    private $fs;

    public function setUp(): void
    {
        if (!file_exists(__DIR__ . '/fixtures/')) {
            mkdir(__DIR__ . '/fixtures/', 0777, true);
        }

        $handler = new \League\Flysystem\AkamaiNetStorage\Handler\Authentication();
        $handler->setSigner((new \League\Flysystem\AkamaiNetStorage\Authentication())->setKey($this->key, $this->keyName));

        $stack = VcrHandler::turnOn(
            __DIR__ . '/fixtures/' . lcfirst(substr($this->getName(), 4)) . '.json'
        );

        $stack->push($handler, 'netstorage-handler');

        $client = new \Akamai\Open\EdgeGrid\Client([
            'base_uri' => $this->host,
            'handler' => $stack
        ]);

        $this->adapter = new \League\Flysystem\AkamaiNetStorage\AkamaiNetStorageAdapter($client, $this->cpCode, $this->prefix);
        $this->fs = new \League\Flysystem\Filesystem($this->adapter);

        $this->config = [];

        try {
            if (! $this->fs->fileExists($this->workingDir)) {
                $this->fs->createDirectory($this->workingDir);
            }
        } catch (\Exception $e) {
        }
    }

    // public function tearDown(): void
    // {
    //     try {
    //         $this->fs->deleteDirectory(DIRECTORY_SEPARATOR . '/' . ltrim($this->workingDir, '\\/'));
    //     } catch (\Exception $e) {

    //     }
    // }

    public function testFileExists()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);
        $this->assertTrue($this->fs->fileExists($file));
    }

    public function testFileNotExists()
    {
        $file = $this->workingDir . '/non-existent.txt';
        $this->assertFalse($this->fs->fileExists($file));
    }

    public function testWrite()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);
        $this->assertSame(__METHOD__, $this->fs->read($file));
    }

    public function testWriteExistingFile()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);

        $this->expectException(UnableToWriteFile::class);
        $this->expectErrorMessage('Unable to write file at location: ' . $file .'. File already exists!');

        $this->fs->write($file, __METHOD__);
    }

    public function testWriteStream()
    {
        $fp = fopen('php://memory', 'w+');
        fputs($fp, __METHOD__);
        fseek($fp, 0);

        $file = $this->workingDir . '/example.txt';
        $this->fs->writeStream($file, $fp);

        $this->assertTrue($this->fs->fileExists($file));
        $this->assertSame(__METHOD__, $this->fs->read($file));
    }

    public function testWriteStreamExistingFile()
    {
        $this->testWriteStream();

        $fp = fopen('php://memory', 'w+');
        fputs($fp, __METHOD__);
        fseek($fp, 0);

        $file = $this->workingDir . '/example.txt';
        $this->expectException(UnableToWriteFile::class);
        $this->expectErrorMessage('Unable to write file at location: ' . $file .'. File already exists!');

        try {
            $this->fs->writeStream($file, $fp);
        } finally {
            $this->assertSame(
                self::class . '::' . 'testWriteStream',
                $this->fs->read($file)
            );
        }
    }

    public function testRead()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);
        $this->assertSame(__METHOD__, $this->fs->read($file));
    }

    public function testReadNonExistent()
    {
        $file = $this->workingDir . '/non-existent.txt';
        $this->expectException(UnableToReadFile::class);
        $this->expectErrorMessage('Unable to read file from location: ' . $file .'.');

        $this->fs->read($file);
    }

    public function testReadStream()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);
        $this->assertSame(__METHOD__, stream_get_contents($this->fs->readStream($file)));
    }

    public function testReadStreamNonExistent()
    {
        $file = $this->workingDir . '/non-existent';
        $this->expectException(UnableToReadFile::class);
        $this->expectErrorMessage('Unable to read file from location: ' . $file .'. ');
        $this->fs->readStream($file);
    }

    // TODO:
    // public function testDelete()
    // {
    //     $file = $this->workingDir . '/example.txt';
    //     $this->fs->delete($file);
    //     $this->assertFalse($this->fs->fileExists($file));
    // }

    public function testListContents()
    {
        $file1 = $this->workingDir . '/example1.txt';
        $this->fs->write($file1, __METHOD__);

        $file2 = $this->workingDir . '/example2.txt';
        $this->fs->write($file2, __METHOD__);

        $contents = $this->fs->listContents($this->workingDir . '/', false)->toArray();
        $this->assertContainsOnlyInstancesOf(FileAttributes::class, $contents);
        $this->assertCount(2, $contents);
        $this->assertEquals('text/plain', $contents[0]->mimeType());
        $this->assertEquals('text/plain', $contents[1]->mimeType());

        $subDir = $this->workingDir . '/test2';
        $this->fs->createDirectory($subDir);

        $file3 = $subDir . '/example3.html';
        $this->fs->write($file3, '<h1>HI</h1>');

        $contents2 = $this->fs->listContents($this->workingDir . '/', true)->toArray();
        $this->assertCount(4, $contents2);
        $this->assertInstanceOf(DirectoryAttributes::class, $contents2[2]);
        $this->assertEquals(2, $contents2[2]->extraMetadata()['files']);

        $this->assertInstanceOf(FileAttributes::class, $contents2[3]);
        $this->assertEquals('text/html', $contents2[3]->mimeType());
    }

    public function testCreateDirectory()
    {
        $directory = $this->workingDir . '/test3';
        $this->fs->createDirectory($directory);
        $this->assertTrue($this->fs->fileExists($directory));
    }

    public function testMimeType()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);
        $this->assertSame('text/plain', $this->fs->mimeType($file));
    }

    public function testLastModified()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);
        $this->assertSame(1661468151, $this->fs->lastModified($file));
    }

    public function testFileSize()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);
        $this->assertSame(81, $this->fs->fileSize($file));
    }

    public function testCopy()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);

        $file2 = $this->workingDir . '/example2.txt';
        $this->fs->copy($file, $file2);

        $this->assertTrue($this->fs->fileExists($file));
        $this->assertTrue($this->fs->fileExists($file2));
    }

    public function testMove()
    {
        $file = $this->workingDir . '/example.txt';
        $this->fs->write($file, __METHOD__);

        $file2 = $this->workingDir . '/example2.txt';
        $this->fs->move($file, $file2);

        // TODO:
        // $this->assertFalse($this->fs->fileExists($file));
        $this->assertTrue($this->fs->fileExists($file2));
    }
}
