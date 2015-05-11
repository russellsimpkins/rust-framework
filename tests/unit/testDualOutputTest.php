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
<<<<<<< HEAD
<response><status>OK</status><results><related_urls link="russ"><age>40</age></related_urls></results></response>
=======
<response><results><related_urls link="russ"><age>40</age></related_urls></results></response>
>>>>>>> bc0629980df6dc3777dcfdcce9f67fba1c9a7c03
');
        $out = new DualOutput(200, $this->data, $_SERVER['SCRIPT_NAME'], $options);
        $this->assertNotNull($out->output);
        
    }

<<<<<<< HEAD
=======
    /**
     * This test makes sure we write out the xml as expected
     */
    public function testRssOutput()
    {

        global $_SERVER;
        $_SERVER['SCRIPT_NAME'] = '/svc/news/v3/content.rss';
        $options = array('url_has_format'=>true, 
                                         'xml_serializer_options'=>array('attr_names_table' => array('results' => 'news_item', 'related_urls' => 'link')));
        /*
         * I need better data to verify output. For now I guess its good assuming it doesn't break
        $this->expectOutputString('<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:nyt="http://www.nytimes.com/namespaces/rss/2.0" xmlns:media="http://search.yahoo.com/mrss/" version="2.0"><channel><title>NYT &gt; CODE API HERE</title><link>missing/script/unit/test</link><language>en-us</language><copyright>missing copyright</copyright><lastBuildDate>Fri, 7 Nov 2014 18:51:15 GMT</lastBuildDate><image><title>NYT &gt; CODE API HERE</title><url>http://graphics.nytimes.com/images/section/NytSectionHeader.gif</url><link>http://www.nytimes.com/pages/index.html?partner=rss</link></image></channel></rss>
');
        */
        $out = new DualOutput(200, $this->data, $_SERVER['SCRIPT_NAME'], $options);
        $this->assertNotNull($out->output);
        
    }

>>>>>>> bc0629980df6dc3777dcfdcce9f67fba1c9a7c03
    public function testJsonpOutput()
    {
        global $_SERVER;
        global $_GET;
        $_SERVER['SCRIPT_NAME'] = '/svc/news/v3/content.jsonp';
        $_GET['callback'] = 'callme';
        $out = new DualOutput(200, $this->data);
<<<<<<< HEAD
        $this->expectOutputString('callme({"status":"OK","results":{"related_urls":{"link":"russ","age":40}}});');
=======
        $this->expectOutputString('callme({"results":{"related_urls":{"link":"russ","age":40}}});');
>>>>>>> bc0629980df6dc3777dcfdcce9f67fba1c9a7c03
        $this->assertNotNull($out->output);
        
    }

    public function testJsonOutput()
    {
        global $_SERVER;

        $_SERVER['SCRIPT_NAME'] = '/svc/news/v3/content.json';
        $out = new DualOutput(200, $this->data);
<<<<<<< HEAD
        $this->expectOutputString('{"status":"OK","results":{"related_urls":{"link":"russ","age":40}}}');
=======
        $this->expectOutputString('{"results":{"related_urls":{"link":"russ","age":40}}}');
>>>>>>> bc0629980df6dc3777dcfdcce9f67fba1c9a7c03
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