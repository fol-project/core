<?php
use Fol\Http\Router\RegexRoute;
use Fol\Http\Request;

class RouterTest extends PHPUnit_Framework_TestCase
{
    public function testRegexRoute()
    {
        $route = new RegexRoute('one', [
            'path' => '/people/{id},{name}'
        ], 'ola');

        $request = Request::create('/people/34,Manolo');

        $this->assertTrue($route->match($request));

        $url = $route->generate(['id' => 45, 'name' => 'Luisa']);

        $this->assertEquals('/people/45,Luisa', $url);
    }
}
