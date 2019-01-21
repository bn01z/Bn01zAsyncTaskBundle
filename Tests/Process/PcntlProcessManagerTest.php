<?php

namespace Bn01z\AsyncTask\Tests\Process;

use Bn01z\AsyncTask\Process\PcntlProcessManager;
use Bn01z\AsyncTask\Tests\TestCase;

class PcntlProcessManagerTest extends TestCase
{
    private $tmpDirPath;
    private $files;

    protected function setUp()
    {
        $this->tmpDirPath = $_ENV['TMP_DIR_PATH'] ?? './tmp';
        mkdir($this->tmpDirPath, 0777, true);
        $this->tmpDirPath = realpath($this->tmpDirPath);
        $this->files = [
            sprintf('%s/file1', $this->tmpDirPath),
            sprintf('%s/file2', $this->tmpDirPath),
            sprintf('%s/file3', $this->tmpDirPath),
        ];
    }

    protected function tearDown()
    {
        if (file_exists($this->tmpDirPath)) {
            foreach ($this->files as $testFile) {
                if (file_exists($testFile)) {
                    unlink($testFile);
                }
            }
            rmdir($this->tmpDirPath);
        }
    }

    public function testRun()
    {
        $processManager = new PcntlProcessManager();
        foreach ($this->files as $file) {
            $processManager->run(
                function () use ($file) {
                    touch($file);
                }
            );
        }
        $processManager->wait();
        foreach ($this->files as $file) {
            $this->assertFileExists($file);
        }
    }
}
