<?php
use Fol\Bag;

class BagTest extends PHPUnit_Framework_TestCase
{
    public function testOne()
    {
        $bag = new Bag();

        //basic
        $bag->set('un', 1);

        $this->assertEquals(1, $bag['un']);
        $this->assertEquals(1, $bag->get('un'));

        $this->assertTrue($bag->has('un'));
        $this->assertNull($bag->get('dous'));
        $this->assertFalse($bag->has('dous'));

        //array
        $bag->set([
            'dous' => 2,
            'tres' => 3
        ]);

        $this->assertEquals(2, $bag['dous']);
        $this->assertEquals(2, $bag->get('dous'));

        $this->assertTrue($bag->has('dous'));
        $this->assertNull($bag->get('catro'));
        $this->assertFalse($bag->has('catro'));

        $this->assertEquals(3, $bag['tres']);
        $this->assertEquals(3, $bag->get('tres'));

        $this->assertTrue($bag->has('tres'));
        $this->assertNull($bag->get('catro'));
        $this->assertFalse($bag->has('catro'));

        //nested
        $bag->set('catro[cinco]', 45);

        $this->assertEquals(45, $bag['catro']['cinco']);
        $this->assertEquals(45, $bag->get('catro')['cinco']);
        $this->assertEquals(45, $bag->get('catro[cinco]'));

        $this->assertTrue($bag->has('catro'));
        $this->assertTrue($bag->has('catro[cinco]'));
        $this->assertNull($bag->get('cinco'));
        $this->assertFalse($bag->has('cinco'));

        //nested array
        $bag->set([
            'cinco[seis]' => 56,
            'seis[sete]' => 67
        ]);

        $this->assertEquals(56, $bag['cinco']['seis']);
        $this->assertEquals(56, $bag->get('cinco[seis]'));
        $this->assertEquals(67, $bag['seis']['sete']);
        $this->assertEquals(67, $bag->get('seis[sete]'));

        $this->assertTrue($bag->has('seis'));
        $this->assertTrue($bag->has('seis[sete]'));
        $this->assertNull($bag->get('sete'));
        $this->assertFalse($bag->has('sete'));

        $this->assertEquals($bag->get(), [
            'un' => 1,
            'dous' => 2,
            'tres' => 3,
            'catro' => ['cinco' => 45],
            'cinco' => ['seis' => 56],
            'seis' => ['sete' => 67],
        ]);

        $this->assertCount(6, $bag);

        //Delete
        $bag->delete('un');

        $this->assertFalse($bag->has('un'));
        $this->assertNull($bag->get('un'));

        $bag->delete('catro');

        $this->assertFalse($bag->has('catro'));
        $this->assertNull($bag->get('catro'));

        $bag->delete('cinco[seis]');

        $this->assertTrue($bag->has('cinco'));
        $this->assertEquals([], $bag->get('cinco'));

        $this->assertFalse($bag->has('cinco[seis]'));
        $this->assertNull($bag->get('cinco[seis]'));

        $this->assertEquals($bag->get(), [
            'dous' => 2,
            'tres' => 3,
            'cinco' => [],
            'seis' => ['sete' => 67],
        ]);

        $this->assertEquals(json_encode($bag), '{"dous":2,"tres":3,"cinco":[],"seis":{"sete":67}}');

        $bag->delete();
        $this->assertCount(0, $bag->get());
        $this->assertEquals(json_encode($bag), '[]');
        $this->assertEquals((string)$bag, json_encode($bag));
    }
}
