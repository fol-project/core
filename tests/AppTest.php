<?php
use Fol\App;
use Fol\Config;

class AppTest extends PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $app = new App();

        $app->setUrl('http://domain.com/www');

        $app->register('config', function () {
            return new Config(__DIR__.'/configtest');
        });

        $this->assertInstanceOf('Fol\\Config', $app->get('config'));

        $this->assertEquals('http://domain.com/www', $app->getUrl());
        $this->assertEquals('http://domain.com/www/these/more/subdirectories', $app->getUrl('these/are', '../more/', '/subdirectories'));
        $this->assertEquals('Fol', $app->getNamespace());
        $this->assertEquals('Fol\\Config', $app->getNamespace('Config'));

        $this->assertEquals(dirname(__DIR__).'/src', $app->getPath());
        $this->assertEquals(dirname(__DIR__).'/src/subdirectory', $app->getPath('subdirectory'));
        $this->assertEquals(dirname(__DIR__).'/', $app->getPath('../'));
    }
}
