<?php
use Rust\Hash\Validator;
class testValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    // tests
    public function testMe()
    {
        $rules = '{"subscription_id" : "/([0-9]{1,15})/",
                  "!asset_type"     :"/([a-z]{1,15})/",
                  "*@coins"         :"/^([0-9]{1,15})$/",
                  "*#offer_meta_data"  : {"*PRD_ID":"/^([0-9]{0,15})$/"},
                  "@#subscription_meta_foo" : [{"*SOME_PRD_ID":"/([A-Z0-9]{0,2})/"}],
                  "@#subscription_meta_data" : [{"*SOME_PRD_ID":"/([A-Z0-9]{0,2})/"}],
                  "*views"         :"/([0-9]{1,10})/",
                  "*does_expire"   :"/(true|false)/",
                  "*offer_chain_id":"/([0-9]{1,15})/",
                  "*expires_on"    :"/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/",
                  "*expires"       :"/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/",
                  "*starts"        :"/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/",
                  "*!ignore"       :""}';
        $rules = json_decode($rules,true);
        $params = array('subscription_id'=>12345,'views'=>'yes', 'coins'=>array(12,25,21), 'subscription_meta_foo'=>array(array('SOME_PRD_ID'=>12345)));
        $res = Validator::validate($rules,$params);
        $this->assertArrayHasKey('400', $res);
        fwrite(STDOUT,"We got back: " . print_r($res,true) . "\n");
        $params = array('subscription_id'=>12345,'views'=>'yes', 'coins'=>array(-12,25,21));

        $res = Validator::validate($rules,$params);
        $this->assertArrayHasKey('400', $res);
        fwrite(STDOUT,"We got back: " . print_r($res,true) . "\n");
        $params = 12;
        $fail = Validator::validate($rules,$params);
        $this->assertNotNull($fail,'We should get an array back failure becuase we did not pass an array to the validator');
    }

}