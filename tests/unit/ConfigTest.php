<?php

namespace Backup;

use Backup\Config;
use PHPUnit\Framework\TestCase;
use Exception;

class ConfigTest extends TestCase {

    private $config;

    protected function setUp(): void {
        $this->config = new Config(__DIR__ . '/../setup-1/config.yml');
    }

    public function testConstructor(): void {
        $this->assertInstanceOf(Config::class, $this->config);
    }

    public function testGetExistingKey(): void {
        $this->assertEquals('my_backup', $this->config->get('name'));
    }

    public function testGetNonExistingKey(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Incorrect config key: 'nonexistent_key'");

        $this->config->get('nonexistent_key');
    }

}
