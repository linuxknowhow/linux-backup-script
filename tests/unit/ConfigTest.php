<?php

namespace Backup;

use Backup\Config;
use Backup\Domain\Source\CronSource;
use Backup\Domain\Source\LocalFolderSource;
use Backup\Domain\Source\MySqlSource;
use Backup\Domain\Storage\LocalTarget;
use Backup\Domain\Storage\S3Target;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Exception;

class ConfigTest extends TestCase {

    private string $fixturePath;
    /** @var array<int, string> */
    private array $tempFiles = [];

    protected function setUp(): void {
        $this->fixturePath = __DIR__ . '/../setup-1/config.yml';
    }

    protected function tearDown(): void {
        foreach ($this->tempFiles as $file) {
            if (is_string($file) && file_exists($file)) {
                unlink($file);
            }
        }

        $this->tempFiles = [];
    }

    public function testGetReturnsValueAndNull(): void {
        $config = new Config($this->fixturePath);

        $this->assertSame('my_backup', $config->get('name'));
        $this->assertNull($config->get('nonexistent_key'));
        $this->assertNull($config->get('sources/does_not_exist'));
    }

    public function testValidatePassesForValidConfig(): void {
        $config = new Config($this->fixturePath);

        $config->validate();

        $this->assertTrue(true);
    }

    public function testValidateFailsWhenRequiredFieldMissing(): void {
        $path = $this->writeTempConfig([
            'name' => 'broken',
            'retention_periods' => ['days' => 1],
            'advanced_settings' => ['tmp_folder' => null],
        ]);

        $config = new Config($path);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('A field in the config is missing or is empty: advanced_settings/tmp_folder');

        $config->validate();
    }

    public function testValidateFailsWhenNegativeRetention(): void {
        $path = $this->writeTempConfig([
            'name' => 'broken',
            'advanced_settings' => ['tmp_folder' => '/tmp'],
            'retention_periods' => ['days' => -1],
        ]);

        $config = new Config($path);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Param retention_periods/days cannot be 0 or lower.');

        $config->validate();
    }

    public function testTmpFolderNormalization(): void {
        $path = $this->writeTempConfig([
            'name' => 'backup',
            'advanced_settings' => ['tmp_folder' => ' /tmp/linux-backup-script/ '],
            'retention_periods' => ['days' => 1],
        ]);

        $config = new Config($path);

        $this->assertSame('/tmp/linux-backup-script', $config->getTmpFolder());
    }

    public function testSourcesAreParsed(): void {
        $config = new Config($this->fixturePath);

        $localSources = $config->getLocalFolderSources();
        $this->assertCount(2, $localSources);
        $this->assertContainsOnlyInstancesOf(LocalFolderSource::class, $localSources);
        $this->assertSame('/home/user/', $localSources[0]->getPath());
        $this->assertSame('/etc/', $localSources[1]->getPath());

        $mysqlSources = $config->getMySqlSources();
        $this->assertCount(1, $mysqlSources);
        $this->assertInstanceOf(MySqlSource::class, $mysqlSources[0]);
        $this->assertSame('127.0.0.1', $mysqlSources[0]->getHostname());
        $this->assertSame('admin', $mysqlSources[0]->getUsername());
        $this->assertSame('admin', $mysqlSources[0]->getPassword());
        $this->assertSame('utf8mb4', $mysqlSources[0]->getCharset());

        $cronSources = $config->getCronSources();
        $this->assertCount(1, $cronSources);
        $this->assertInstanceOf(CronSource::class, $cronSources[0]);
        $this->assertSame('user', $cronSources[0]->getUser());
    }

    public function testStorageTargetsAreParsed(): void {
        $config = new Config($this->fixturePath);

        $targets = $config->getStorageTargets();

        $this->assertCount(2, $targets);

        $this->assertInstanceOf(S3Target::class, $targets[0]);
        $this->assertSame('some-region-1', $targets[0]->getRegion());
        $this->assertSame('bucketname', $targets[0]->getBucket());
        $this->assertSame('some_folder', $targets[0]->getFolder());
        $this->assertSame('00000000000000000000', $targets[0]->getAccessKeyId());
        $this->assertSame('0000000000000000000000000000000000000000', $targets[0]->getSecretKey());

        $this->assertInstanceOf(LocalTarget::class, $targets[1]);
        $this->assertSame('/mnt/data/Backups', $targets[1]->getFolder());
    }

    private function writeTempConfig(array $data): string {
        $path = tempnam(sys_get_temp_dir(), 'cfg-');
        file_put_contents($path, Yaml::dump($data));

        $this->tempFiles[] = $path;

        return $path;
    }
}
