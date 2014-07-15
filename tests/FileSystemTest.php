<?php
use Fol\FileSystem;

class FileSystemTest extends PHPUnit_Framework_TestCase
{
    public function testFileSystem()
    {
        $parentDir = dirname(__DIR__);

        $filesystem = new FileSystem($parentDir);
        $this->assertEquals($filesystem->getPath(), $parentDir);

        $filesystem->cd('tests');
        $this->assertEquals($filesystem->getPath(), __DIR__);

        //Make a new temporary directory
        $filesystem->mkdir('tmp');
        $info = $filesystem->getInfo('tmp');

        $this->assertEquals($info->getPathname(), __DIR__.'/tmp');
        $this->assertTrue($info->isDir());
        $this->assertTrue($info->isReadable());
        $this->assertTrue($info->isWritable());

        //Copy a file in the directory
        $filesystem->copy('http://lorempixum.com/50/50', 'tmp/image.jpg');
        $info = $filesystem->getInfo('tmp/image.jpg');

        $this->assertTrue($info->isFile());
        $this->assertTrue($info->isReadable());
        $this->assertTrue($info->isWritable());

        //Explore the directory
        $iterator = $filesystem->getIterator('tmp');

        foreach ($iterator as $file) {
            $this->assertEquals($file->getFilename(), 'image.jpg');
        }

        //Remove the directory and its content
        $filesystem->remove('tmp');
        $info = $filesystem->getInfo('tmp/image.jpg');
        $this->assertFalse($info->isFile());
    }
}
