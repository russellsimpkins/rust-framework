<?php

class testTraitsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    use Rust\Traits\JsonTraits;


    public function testGetJsonError()
    {
        $foo = json_decode('{"stuff": ""}');
        $mes = $this->getJsonError();

        $foo = json_decode("bogus json",true);
        $mes = $this->getJsonError();
        $this->assertNotNull($mes, 'there should be an error if we pass in "bogus json"');
        $baddata = pack("H*" ,'c32e');
        $foo = json_decode($baddata);
        $mes = $this->getJsonError();
        $this->assertNotNull($mes,'there should be an error message if we pass in invalid utf8 charachter');
        $foo = json_decode('{"stuff": "ôstuff"}');
        $mes = $this->getJsonError();
        $this->assertNotNull($mes,'There should be a message for control char');

        $foo = json_decode('{"stuff": {"bar"}');
        $mes = $this->getJsonError();
        $this->assertNotNull($mes, 'There should be an error for missing brace');
         
    }

}