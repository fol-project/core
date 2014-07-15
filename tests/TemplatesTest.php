<?php
use Fol\Templates;

class TemplatesPlugin
{
    public function sayHello()
    {
        return 'Hello';
    }
}

class TemplatesTest extends PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $templates = new Templates(__DIR__.'/templatestest');

        //Get a template file
        $this->assertEquals(__DIR__.'/templatestest/template1.php', $templates->file('template1.php'));
        $this->assertEquals(__DIR__.'/templatestest/template1.php', $templates->file('/template1.php'));

        //Register a file with a name
        $templates->register('first', 'template1.php');
        $this->assertEquals(__DIR__.'/templatestest/template1.php', $templates->file('first'));

        //Render a file with data
        $render = $templates->render('first', ['name' => 'Manolo']);
        $this->assertEquals('<h1>Manolo</h1>', $render);

        //Save a render with a name
        $templates->saveRender('manolo', $render);

        $this->assertEquals('<h1>Manolo</h1>', $render);

        //Register a file with a name adding data
        $templates->register('manola', 'first', ['name' => 'Manola']);

        $this->assertEquals('<h1>Manola</h1>', $templates->render('manola'));

        //Add a plugin and check its functions
        $templates->loadExtension(new TemplatesPlugin);

        $this->assertEquals('Hello', $templates->sayHello());

        //Render a template with iterable data
        $render = $templates->render('first', [
            ['name' => 'Manolo'],
            ['name' => 'Manola']
        ]);

        $this->assertEquals('<h1>Manolo</h1><h1>Manola</h1>', $render);

        // start/end/wrapper methods
        $render = $templates->render('template2.php');

        $this->assertEquals('<h1>Hello world</h1><p>Hello world</p><footer>Bye</footer>', $render);
    }
}
