<?php
use Fol\Container\Container;
use Fol\Container\ContainerException;
use Fol\Container\NotFoundException;

class ContainerTest extends PHPUnit_Framework_TestCase
{
    public function testContainer()
    {
        $container = new Container();

        $container->register('now', function () {
            return new \Datetime('now');
        });

        //Single
        $now = $container->get('now');
        $this->assertSame($now, $container->get('now'));

        //Multiple
        $container->register('yesterday', function () {
            return new \Datetime('-1 day');
        }, false);

        $yesterday = $container->get('yesterday');
        $this->assertEquals($yesterday, $container->get('yesterday'));
        $this->assertNotSame($yesterday, $container->get('yesterday'));
    }

    public function testMultipleContainer()
    {
        $container = new Container();
        $subContainer1 = new Container();
        $subContainer2 = new Container();

        $container->add($subContainer1);
        $container->add($subContainer2);

        $subContainer1->register('now', function () {
            return new \Datetime('now');
        });

        $subContainer2->register('yesterday', function () {
            return new \Datetime('-1 day');
        }, false);

        //Single
        $now = $container->get('now');
        $this->assertSame($now, $container->get('now'));

        //Multiple
        $yesterday = $container->get('yesterday');
        $this->assertEquals($yesterday, $container->get('yesterday'));
        $this->assertNotSame($yesterday, $container->get('yesterday'));
    }

    /**
     * @expectedException NotFoundException
     */
    public function notFoundException()
    {
        $container = new Container();

        $container->get('not-existing');
    }

    /**
     * @expectedException ContainerException
     */
    public function containerException()
    {
        $container = new Container();

        $container->register('fail', function () {
            return new UndefinedClass();
        });

        $container->get('fail');
    }
}
