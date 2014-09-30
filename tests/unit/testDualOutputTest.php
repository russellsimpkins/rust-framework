<?php

use \Rust\Output\DualOutput;
class testDualOutputTest extends \PHPUnit_Framework_TestCase
{
    var $data = array('results'=>array('related_urls'=>array('link'=>'russ',
                                                                      'age'=>40)));
    protected function setUp()
    {

    }

    protected function tearDown()
    {
    }

    /**
     * This test makes sure we write out the xml as expected
     */
    public function testXmlOutput()
    {

        global $_SERVER;
        $_SERVER['SCRIPT_NAME'] = '/svc/news/v3/content.xml';
        $options = array('url_has_format'=>true, 
                                         'xml_serializer_options'=>array('attr_names_table' => array('results' => 'news_item', 'related_urls' => 'link')));
        
        $this->expectOutputString('<?xml version="1.0"?>
<response><status>OK</status><results><related_urls link="russ"><age>40</age></related_urls></results></response>
');
        $out = new DualOutput(200, $this->data, $_SERVER['SCRIPT_NAME'], $options);
        $this->assertNotNull($out->output);
        
    }

    public function testJsonpOutput()
    {
        global $_SERVER;
        global $_GET;
        $_SERVER['SCRIPT_NAME'] = '/svc/news/v3/content.jsonp';
        $_GET['callback'] = 'callme';
        $out = new DualOutput(200, $this->data);
        $this->expectOutputString('callme({"status":"OK","results":{"related_urls":{"link":"russ","age":40}}});');
        $this->assertNotNull($out->output);
        
    }

    public function testJsonOutput()
    {
        global $_SERVER;

        $_SERVER['SCRIPT_NAME'] = '/svc/news/v3/content.json';
        $out = new DualOutput(200, $this->data);
        $this->expectOutputString('{"status":"OK","results":{"related_urls":{"link":"russ","age":40}}}');
        $this->assertNotNull($out->output);
    }

    public function testUnsupportedOutput()
    {
        global $_SERVER;

        $_SERVER['SCRIPT_NAME'] = '/svc/news/v3/content.html';

        try {
            $out = new DualOutput(200, $this->data);
        } catch (\Exception $e) {
            $this->assertEquals('Unexpected output format: html', $e->getMessage());
        }

    }

    public function testBadData()
    {
        global $_SERVER;

        $_SERVER['SCRIPT_NAME'] = '/svc/news/v3/content.json';
        $data = 'string';
        try {
            $out = new DualOutput(200, $data);
        } catch (\Exception $e) {
            $this->assertEquals('DualOutput expects $data to be an array.', $e->getMessage());
        }
    }

    public function testBadOptions()
    {
        global $_SERVER;

        $_SERVER['SCRIPT_NAME'] = '/svc/news/v3/content.json';
        try {
            $out = new DualOutput(200, $this->data, 'stuff');
        } catch (\Exception $e) {
            $this->assertEquals('DualOutput expects $options to be an array.', $e->getMessage());
        }
    }
}