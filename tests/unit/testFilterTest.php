<?php
use Rust\Filter\FraudCheck;

class testFilterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    // tests
    public function testFraudCheck()
    {
        $f = new FraudCheck();
        $p = array();
        $v = $f->filter($p);
        $this->assertEquals(TRUE,$v);
        $p['fraud']=TRUE;
        $v = $f->filter($p);
        $this->assertArrayHasKey('500',$v);
    }

}