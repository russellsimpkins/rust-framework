<?php
function printHeaders($headers='') {
    fwrite(STDOUT, print_r($headers,true));
}

use Codeception\Util\Stub;
use Rust\Service\Controller;
class testControllerTest extends \Codeception\TestCase\Test
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;
    protected $routes;
    protected $params;
    protected function _before()
    {
        global $_SERVER;
        $_SERVER = array('REQUEST_METHOD'=>'GET');
        $this->params = array('foo'=>'bar');
        $this->routes = <<<ROUTE_DEFINITION
{
    "std_out":"Rust\\\\Output\\\\NullOut",
    "std_err":"Rust\\\\Output\\\\NullErr",
<<<<<<< HEAD
=======
    "name"   :"Unit Tests",
>>>>>>> bc0629980df6dc3777dcfdcce9f67fba1c9a7c03
    "routes":[
	{
	    "rule": ";/svc/v2/news/list.(json|xml);",
	    "params": ["script_path","mime_type"],
	    "action": "GET",
	    "class" : "Some\\\\Example\\\\Class",
	    "method": "getList",
	    "name"  : "Get List",
	    "docs"  : "This method will get a list of items.",
        "pcheck": {"subscription_id" : "/([0-9]{1,15})/",
                  "asset_type"     :"/([a-z]{1,15})/",
                  "*views"         :"/([0-9]{1,10})/",
                  "*does_expire"   :"/(true|false)/",
                  "*offer_chain_id":"/([0-9]{1,15})/",
                  "@coins"         :"([0-9]{1,15})/",
                  "*expires_on"    :"/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/",
                  "*expires"       :"/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/",
                  "*starts"        :"/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/",
                  "*#subscription_meta_data" : {"*SOME_PRD_ID":"/([A-Z0-9]{0,2})/"},
                  "*#offer_meta_data"  : {"*PRD_ID":"/^([0-9]{0,15})$/"},
                  "*!ignore"       :""
        }
	},
    {
        "rule"  : ";/svc/unit;",
        "params": ["script_path"],
        "action": "GET",
	    "class" : "Rust\\\\Service\\\\Controler",
	    "method": "unit",
	    "name"  : "Unit test method",
	    "docs"  : "This method does nothing.",
        "pcheck": {"subscription_id" : "/([0-9]{1,15})/",
                  "asset_type"     :"/([a-z]{1,15})/",
                  "*views"         :"/([0-9]{1,10})/",
                  "*does_expire"   :"/(true|false)/",
                  "*offer_chain_id":"/([0-9]{1,15})/",
                  "@coins"         :"([0-9]{1,15})/",
                  "*expires_on"    :"/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/",
                  "*expires"       :"/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/",
                  "*starts"        :"/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/",
                  "*#subscription_meta_data" : {"*SOME_PRD_ID":"/([A-Z0-9]{0,2})/"},
                  "*#offer_meta_data"  : {"*PRD_ID":"/^([0-9]{0,15})$/"},
                  "*!ignore"       :""
        }
    },
    {
        "rule"  : ";/svc/output/test.xml;",
        "params": ["script_path"],
        "action": "GET",
	    "class" : "Rust\\\\Mock\\\\DataProducer",
	    "method": "produceSampleSet",
	    "name"  : "Test valid xml",
	    "docs"  : "Testing valid xml.",
        "std_out": {"class": "Rust\\\\Output\\\\DualOutput",
                    "config": {"xml_serializer_options":{"array_names_table":{"cards":"card"},"attr_names_table":{"card":"suit"}}}},
        "std_err": {"class": "Rust\\\\Output\\\\DualOutput"}
    },
    {
        "rule"  : ";/svc/output/testError.xml;",
        "params": ["script_path"],
        "action": "GET",
	    "class" : "Rust\\\\Mock\\\\DataProducer",
	    "method": "produceErrorSet",
	    "name"  : "Test valid xml",
	    "docs"  : "Testing valid xml.",
        "std_out": {"class": "Rust\\\\Output\\\\DualOutput",
                    "config": {"xml_serializer_options":{"array_names_table":{"cards":"card"},"attr_names_table":{"card":"suit"}}}},
        "std_err": {"class": "Rust\\\\Output\\\\DualOutput"}
      }]
}
ROUTE_DEFINITION;
        $this->routes = json_decode($this->routes, true);
    }

    protected function _after()
    {
    }


    public function testMatchMethod()
    {
        $method="GET";
        $allowed= "GET PUT POST";
        $c = new Controller($method,'GET');
        $value = $c->matchAction($method, $allowed);
        $this->assertEquals($value,1,'preg_match failed');
        $allowd = "DELETE";
        $value = $c->matchAction($method, $allowed);
        $this->assertEquals($value,1,'preg_match failed');
    }

    public function testEmptyDecode() 
    {
        $empty;
        $empty = Controller::decodeInput("");
        $this->assertEquals($empty,"",'That is truely odd');
    }

    public function testJsonDecode() 
    {
        $empty;
        $empty = Controller::decodeInput('{"status":"ok"}');
        
        $this->assertArrayHasKey('status',$empty,'Failed to decode json');
    }

    public function testNotJsonDecode() 
    {
        $empty;
        $str = '<?xml version="1.0"><status>ok</status>';
        $empty = Controller::decodeInput($str);
        
        $this->assertEquals($str,$empty,'Something odd happened. These should equal.');
    }

    public function testGetSetParams()
    {
        $params = array('foo'=>'bar');
        $c = new Controller($params,'GET');
        $c->setParams($params);
        $got = $c->getParams();
        $this->assertEquals($params,$got,'Something went wrong using getter and setter for params');
    }

    public function testGetSetAction()
    {
        $params = 'GET';
        $c = new Controller($params,'GET');
        $c->setAction($params);
        $got = $c->getAction();
        $this->assertEquals($params,$got,'Something went wrong using getter and setter for action');
    }

    public function testConstructor() 
    {
        $this->assertEquals($this->routes['std_err'], 'Rust\Output\NullErr');
        
        $c = new Controller($this->params,'GET');
        $c->__construct();
        $this->assertNotNull($c);
        $this->assertEquals('GET',$c->getAction());
        
    }

    public function testRecurseParams() 
    {
         
        $c = new Controller($this->params,'GET');
        $after = $c->recurseParams($this->routes['routes'][0]['pcheck']);        
        $this->assertNotNull($after);
        
    }
    
    public function testIodoc()
    {
        $c = new Controller($this->params,'GET');
        $iodocs = $c->iodoc($this->routes);
        $this->assertNotNull($iodocs);
        //fwrite(STDOUT, print_r($iodocs,true));
    }

    public function testHelp() 
    {
        //fwrite(STDOUT, "after is this: " . print_r($after, true));
        $c = new Controller($this->params,'GET');
        $help   = $c->help($this->routes);
        $this->assertNotNull($help);
    }

    public function testRun()
    {
        $c = new Controller($this->params,'GET');
        $help   = $c->run($this->routes, '/svc/unit', $this->params,'GET');
        $this->assertNotNull($help);
    }

    /**
     * @covers Rust\Service\Controller::handleOut
     */
    public function testHandleOut() 
    {
        $c = new Controller(array(),'GET');
        $param[200] = '{"status": "ok"}';
        $out = 'Rust\Output\NullOut';
        $err = 'Rust\Output\NullErr';
        $c->handleOut($param, $out, $err);
        $param[500] = '{"status": "error"}';
        $out = 'Rust\Output\NullOut';
        $err = 'Rust\Output\NullErr';
        $c->handleOut($param, $out, $err);
        $err = 'Rust\Output\NullErrs';
        $param = 'holy moly';
        $c->handleOut($param, $out, $err);
    }

    public function testXmlHandler()
    {
        global $_SERVER;
        $_SERVER['SCRIPT_NAME'] = '/svc/output/test.xml';
        $c = new Controller(array(), 'GET');
        $this->expectOutputString('<?xml version="1.0"?>
<<<<<<< HEAD
<response><status>OK</status><results><cards><card suit="clubs"><value>2</value></card><card suit="clubs"><value>3</value></card><card suit="clubs"><value>4</value></card><card suit="clubs"><value>5</value></card><card suit="clubs"><value>6</value></card></cards></results></response>
=======
<response><results><cards><card suit="clubs"><value>2</value></card><card suit="clubs"><value>3</value></card><card suit="clubs"><value>4</value></card><card suit="clubs"><value>5</value></card><card suit="clubs"><value>6</value></card></cards></results></response>
>>>>>>> bc0629980df6dc3777dcfdcce9f67fba1c9a7c03
', 'Failed to get expected output');
        $c->run($this->routes, $_SERVER['SCRIPT_NAME'], $this->params,'GET');
    }

    public function testBadXmlHandler()
    {
        global $_SERVER;
        $_SERVER['SCRIPT_NAME'] = '/svc/output/testError.xml';
        $c = new Controller(array(), 'GET');
        header_register_callback ( 'printHeaders' );

        $this->expectOutputString('<?xml version="1.0"?>
<<<<<<< HEAD
<response><status>ERROR</status><results><cards><card suit="clubs"><value>2</value></card><card suit="clubs"><value>3</value></card><card suit="clubs"><value>4</value></card><card suit="clubs"><value>5</value></card><card suit="clubs"><value>6</value></card></cards></results></response>
=======
<response><results><cards><card suit="clubs"><value>2</value></card><card suit="clubs"><value>3</value></card><card suit="clubs"><value>4</value></card><card suit="clubs"><value>5</value></card><card suit="clubs"><value>6</value></card></cards></results></response>
>>>>>>> bc0629980df6dc3777dcfdcce9f67fba1c9a7c03
', 'Failed to get expected output');
        $c->run($this->routes, $_SERVER['SCRIPT_NAME'], $this->params,'GET');
    }
}


