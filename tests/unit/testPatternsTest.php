<?php
use Rust\Hash\RegexPatterns;
class testPatternsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    // tests
    public function testGetPatterns()
    {

        $r = new RegexPatterns();
        $p = $r->getPatterns();
        $q = json_encode($p,true);
        $this->assertNotNull($q,'Something is bad in the patterns as this did not encode');
    }

}