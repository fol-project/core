<?php
use Fol\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $config = new Config(__DIR__.'/configtest');

        //Load configuration
        $this->assertEquals($config->get('demo[value1]'), 1);
        $this->assertEquals($config->get('demo[value2]'), 'two');
        $this->assertEquals($config->get('demo[value3][1]'), 'one');
        $this->assertEquals($config->get('demo[value3][2]'), 'two');

        //Modify configuration
        $config->set('demo', [
            'value1' => 'one',
            'value4' => 4,
        ]);

        $c = $config->get('demo');

        $this->assertEquals($c['value1'], 'one');
        $this->assertFalse(isset($c['value2']));
        $this->assertEquals($c['value4'], 4);

        //Delete and reload again
        $config->delete('demo');
        $c = $config->get('demo');

        $this->assertEquals($c['value1'], 1);
        $this->assertEquals($c['value2'], 'two');
    }
}
