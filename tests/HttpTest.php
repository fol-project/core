<?php
use Fol\Http\Request;
use Fol\Http\Response;

class HttpTest extends PHPUnit_Framework_TestCase
{
    public function testRequest()
    {
        $request = Request::create('/index', 'GET', [], []);

        $this->assertEquals($request->getPath(), '/index');
        $this->assertEquals($request->getMethod(), 'GET');
        $this->assertEquals($request->getFormat(), 'html');
        $this->assertFalse($request->isAjax());
        $this->assertEquals($request->getScheme(), 'http');
        $this->assertEquals($request->getHost(), 'localhost');

        //Change paths and formats
        $request->setPath('index2.XML');
        $this->assertEquals($request->getPath(), '/index2');
        $this->assertEquals($request->getFormat(), 'xml');

        $request->setFormat('JSON');
        $this->assertEquals($request->getFormat(), 'json');

        //Get full url
        $request->setPath('/index2.json');
        $this->assertEquals($request->getUrl(), 'http://localhost/index2.json');

        //GET params
        $request->query->set([
            'param1' => 1,
            'param2' => 2
        ]);
        $this->assertEquals($request->query->get('param1'), 1);
        $this->assertEquals($request->query->get('param2'), 2);
        $this->assertEquals($request->query->get(), [
            'param1' => 1,
            'param2' => 2
        ]);

        //Get full url with get params
        $this->assertEquals($request->getUrl(true, true, true), 'http://localhost/index2.json?param1=1&param2=2');

        //Headers
        $request->headers->set('X-Requested-With', 'xmlhttprequest');
        $this->assertTrue($request->isAjax());

        return $request;
    }

    public function testPath()
    {
        $request = new Request();

        $request->setPath('ola/quetal/');
        $this->assertEquals('/ola/quetal', $request->getPath());

        $request->setPath('');
        $this->assertEquals('/', $request->getPath());

        $request->setPath('ola');
        $this->assertEquals('/ola', $request->getPath());
        $this->assertEquals('html', $request->getFormat());

        $request->setPath('ola.JSON');
        $this->assertEquals('json', $request->getFormat());

        $request->setPath('ola/.JSON');
        $this->assertEquals('json', $request->getFormat());
    }

    /**
	 * @depends testRequest
	 */
    public function testResponse(Request $request)
    {
        $response = new Response();
        $response->prepare($request);

        $this->assertEquals($response->getStatus(), 200);
        $this->assertEquals($response->getStatus(true), 'OK');
        $this->assertEquals($response->headers->get('Content-Type'), 'text/json; charset=UTF-8');
        $this->assertEquals($response->getBody(), '');

        //Modify some response properties
        $response->setStatus(202);
        $response->setBody('Hello world');

        $this->assertEquals($response->getStatus(), 202);
        $this->assertEquals($response->getStatus(true), 'Accepted');
        $this->assertEquals($response->getBody(), 'Hello world');

        //Redirection
        $response->redirect('http://site.com');

        $this->assertEquals($response->getStatus(), 302);
        $this->assertEquals($response->headers->get('location'), 'http://site.com');
    }
}
