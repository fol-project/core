<?php

class AppTest extends PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $app = new Fol();

        $app->setUrl('http://domain.com/www');

        $app['config'] = function () {
            return [1];
        };

        $this->assertSame([1], $app->get('config'));

        $this->assertEquals('http://domain.com/www', $app->getUrl());
        $this->assertEquals('http://domain.com/www/these/more/subdirectories', $app->getUrl('these/are', '../more/', '/subdirectories'));
        $this->assertEquals('', $app->getNamespace());
        $this->assertEquals('Config', $app->getNamespace('Config'));

        $this->assertEquals(dirname(__DIR__).'/src', $app->getPath());
        $this->assertEquals(dirname(__DIR__).'/src/subdirectory', $app->getPath('subdirectory'));
        $this->assertEquals(dirname(__DIR__).'/', $app->getPath('../'));
    }

    public function testContainer()
    {
        $app = new Fol();
        $now = new Datetime('now');

        $app['now'] = function () use ($now) {
            return $now;
        };

        //Single
        $now2 = $app->get('now');
        $this->assertSame($now, $now2);
    }

    public function testMultipleContainer()
    {
        $app = new Fol();
        $app1 = new Fol();
        $app2 = new Fol();

        $app->add($app1);
        $app->add($app2);

        $app1['now'] = function () {
            return new Datetime('now');
        };

        $app2['yesterday'] = function () {
            return new Datetime('-1 day');
        };

        $app['now-yesterday'] = function ($app) {
            return $app->get('now')->getTimestamp() - $app->get('yesterday')->getTimestamp();
        };

        //Single
        $now = $app->get('now');
        $this->assertInstanceOf('Datetime', $now);
        $this->assertSame(time(), $now->getTimestamp());

        //Multiple
        $yesterday = $app->get('yesterday');
        $this->assertInstanceOf('Datetime', $yesterday);
        $this->assertSame(strtotime('-1 day'), $yesterday->getTimestamp());

        //Combined
        $substract = $app->get('now-yesterday');
        $this->assertEquals(3600 * 24, $substract);
    }

    /**
     * @expectedException NotFoundException
     */
    public function notFoundException()
    {
        $app = new Fol();

        $app->get('not-existing');
    }

    /**
     * @expectedException ContainerException
     */
    public function containerException()
    {
        $app = new Fol();

        $app->register('fail', function () {
            return new UndefinedClass();
        });

        $app->get('fail');
    }
}
