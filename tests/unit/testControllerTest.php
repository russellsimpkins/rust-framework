<?php
use Codeception\Util\Stub;
use Rust\Service\Controller;
class testControllerTest extends \Codeception\TestCase\Test
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
     * @covers Rust\Service\Controller
     */
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

    public function testRecurseParams() 
    {
         $routes = <<<ROUTE_DEFINITION
{
    "std_out":"Rust\\\\Output\\\\NullOut",
    "std_err":"Rust\\\\Output\\\\NullErr",
    "routes":[
	{
	    "rule": ";/svc/v2/news/list.(json|xml);",
	    "params": ["script_path","mime_type"],
	    "action": "GET",
	    "class" : "NYTD\\\\RecentNews\\\\List",
	    "method": "getList",
	    "name"  : "Get List",
	    "docs"  : "This method will get a list of recent news."
	}]
}
ROUTE_DEFINITION;
        $routes = json_decode($routes, true);
        $this->assertEquals($routes['std_err'], 'Rust\Output\NullErr');
        $params = array('foo'=>'bar');
        $c = new Controller($params,'GET');
        $c->run($routes,'/svc/help.json',$params);
    }
}