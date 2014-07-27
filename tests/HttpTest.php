<?php
use Fol\Http\Request;
use Fol\Http\Response;

class HttpTest extends PHPUnit_Framework_TestCase
{
    public function testRequest()
    {
        $request = new Request('http://localhost/index', 'GET');

        $this->assertEquals($request->url->getPath(), '/index');
        $this->assertEquals($request->getMethod(), 'GET');
        $this->assertEquals($request->getFormat(), 'html');
        $this->assertFalse($request->isAjax());
        $this->assertEquals($request->url->getScheme(), 'http');
        $this->assertEquals($request->url->getHost(), 'localhost');

        //Change paths and formats
        $request->url->setPath('index2.XML');

        $this->assertEquals($request->url->getPath(), '/index2.xml');
        $this->assertEquals($request->url->getExtension(), 'xml');
        $this->assertEquals($request->getFormat(), 'xml');

        //Get full url
        $request->url->setPath('/index2.json');
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
        $this->assertEquals($request->getUrl(true), 'http://localhost/index2.json?param1=1&param2=2');

        //Headers
        $request->headers->set('X-Requested-With', 'xmlhttprequest');
        $this->assertTrue($request->isAjax());

        return $request;
    }

    public function testPath()
    {
        $request = new Request();

        $request->url->setPath('ola/quetal/');
        $this->assertEquals('/ola/quetal', $request->url->getPath());

        $request->url->setPath('');
        $this->assertEquals('/', $request->url->getPath());

        $request->url->setPath('ola');
        $this->assertEquals('/ola', $request->url->getPath());
        $this->assertEquals('html', $request->getFormat());

        $request->url->setPath('ola.JSON');
        $this->assertEquals('json', $request->getFormat());

        $request->url->setPath('ola/.JSON');
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
