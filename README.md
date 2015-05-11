PHP Rust Framework
======

The Rust Framework is a basic, bare bones, RestFul framework for PHP with no dependencies that provides input validation and generates api documentation in a custom format and IoDoc format.

Why use it?
------

I found a few great benefits developing an API with this framework.

  - Quick and easy to make changes. If you need to change the URL, add parameters to the url or to the post body its really quick and easy since it's just a configuration change.
  - Zero variable validation in my code. It really cleans up your code. You go from having a lot of variable checking in each method to only checking to see if stuff came in e.g. paging values don't have to be passed in, but if they are, you only check for is_set or !empty and not if it's a valid integer in range.
  - Easily add new standard out/standard error handlers without breaking everything else. When I added the "help" feature, I was able to add a straight json output in seconds.
  - Self documenting (more or less). The help and iodocs are routed for everyone. You just need to add the description and name fields to your configuration file (routes.json)

Example App
------
https://github.com/russellsimpkins/rust-example

Quick start
------
Let's assume you're using Apache to map HTTP requests to your PHP:

```
    AliasMatch ^/svc/user /some/php/user/service.php
```

The framework expects you to define routes for your API calls. Let's suppose you have the following service that gets an existing user based on their user id, e.g.

```
    /svc/user/32451.json
```

You create a **rule** to handle the service:

```
    'rule' => ';^/svc/user/([0-9]{1-10}).json$;'
```

The framework uses PHP's preg_match which expects delimiters, so we have to add ; before and after the regex, but it could another character.  That rule will have a supported action e.g. GET, PUT, POST or DELETE. You specify which method to support using the **action** field.

```
    'action' => 'GET'
```

You can have more than one request method in the action:

```
    'action' => 'GET PUT POST'
```

So, when someone requests /svc/user/321.json using GET, Rust will match the **rule** we have defined. Rust will grab /svc/user/321.json and 321 for you and add them to the map based on the values you identified in **params**

```
    'params'=>array('script_path','user_id')
```

Rust uses the params to create entries into the map of data:

```
    $data['script_path'] = '/svc/user/321.json';
    $data['user_id'] = 321;
```

Rust will create an instance of the **class** you defined and execute the **method** you defined. If you had 

```
    'class'  => 'User',
    'method' => 'Fetch'
```

Rust would do this:

```
    $c = new $class();
    $c->$method( $data );
```

You can pass constructor params as well. Let's assume your class has a data access object (DAO) and you want to be able to swap to a Mock DAO for testing. You just need to add a config entry to the route and put the parameters you want passed in a **class** entry. Here's a json example:

```

"config": {
	"class": {
		"dao": Example\\ContentDao",
		"dbhost":"host",
		"dbuser":"user,
		"dbpwd":"pass",
		"db": "my_database,
		"limit_images": true
	}
}

```

As a user, you would write your custom logic and return the results. Since you have a regex to define what "user_id" should have, there is no need to have any logic to validate the number is a number and not a string or some SQL injection attack. You simply use the data as $data['user_id']. You do whatever and return either:

```
    return array(200=>$data);
```

**or**

```
    return array(500=>"Reason for failure");
```

Rust will create an instance of the **std_out** class if the returned array has 200 or it will use the **std_err** class. The Output classes all work on that assumption, but Rust doesn't look at the data. So, you can put whatever you like in the array if you define your own standard output/standard error classes.

Let's pull it all together:

```
<?php
use Rust/Service/Controller;
use Rust/HTTP/ResponseCodes;
use Rust/Output/JsonOutput;
use Rust/Output/JsonError;

class Service {

	  __construct() {

	  }

	  function run() {
	      $c = new Controller();
		  $c->run($this->$routes);
	  }

	  $routes = array('routes' => 
	    array(
          'rule'    => ';^/svc/user/([0-9]{1-10}).json$;',
          'params'  => array('script_path','user_id'),
          'action'  => 'GET',
          'class'   => '\Namespace\User',
          'method'  => 'Fetch',
          'std_out' => 'Rest\Output\JsonOutput',
          'std_err' => 'Rest\Output\JsonError'
	  	  )
	  );
};
$s = new Service();
$s->run();
```

If you're code uses POST and has parameters, you can use **pcheck** to define variable validation. If any of the validation methods fail, Rust will terminate and create an instance of **std_err** to return the validation failure. 

If you define an **fiters**, Rust will pass the data to each of the filters and see that the filter returns true. If any filter fails, Rust will create an instance of **std_err** and pass the error message.

Routes
------  
Each route consists of the following attributes:

  * rule
  * method
  * params
  * class
  * action
  * pcheck
  * title
  * docs
  * std_out
  * std_err
  * filters

**rule**: The rule is a regular pattern expression e.g. 
```
   ;^/svc/asset/user/([0-9]{1,15})/license/count.json$;
```
You can and should name each of the parameters you wish to capture in the url using the **params** field.

**params**: the params element allows you to name the parameters in the url. e.g. ([0-9]{1,15}) is user id or age. If you omit params, you will not get them in the $data parameter passed to your method

**action**: what HTTP Request method. checks $_SERVER['REQUEST_METHOD']

**class**: this is the class file the code should create a new instance of. 

**method**: this is the method of the class to call. When the method is called, the controller will pass in the validated data as a array/hash

**title**: this is for the help feature, lets you name the service

**docs**: this is where you give a brief overview of service the class/method provides

**pcheck**: this is where you put variable validation.

**std_out**: you can define a class that handles good results

**std_err**: you can define a class that handles bad results

**filters**: you can define one or more classes to filter for basic input like valid NYT-S cookie. The controller will pass in the address of the parameters to allow filters to add additional parameters. Filters return TRUE if all is well or should pass back array(HTTP_RESPONSE_CODE=>message)


pcheck
---
pcheck defines our variable validation. Let's take a look at an example:

```
array('rule'   => ';^/svc/asset/user/([0-9]{1,15})/license/type/([a-z]{1,40})/size/([0-9]{1,10}).json$;',
                    'params'  => array('script_path','id','asset_type','views'),
                    'action'  => 'POST',
                    'class'   => 'License',
                    'method'  => 'restCreateUserLicense',
                    'std_out' => 'Response',
                    'std_err' => 'ErrorResponse',
                    'pcheck' => array('subscription_id'=>'/([0-9]{1,15})/',
                                      'asset_type'     =>'/([a-z]{1,15})/',
                                      '*views'         =>'/([0-9]{1,10})/',
                                      '*does_expire'   =>'/(true|false)/',
                                      '*offer_chain_id'=>'/([0-9]{1,15})/',
                                      '*expires_on'    =>'/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/',
                                      '*expires'       =>'/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/',
                                      '*starts'        =>'/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/',
                                      '*#subscription_meta_data' =>array('*SOME_PRD_ID'=>'/([A-Z0-9]{0,2})/'),
                                      '*#offer_meta_data'  =>array('*PRD_ID'=>'/^([0-9]{0,15})$/'),
                                      '*!ignore'       =>''
                                      )
                    ),
              
              );
```

pcheck should always be a hash (an array with named keys), even if it's empty. 
pcheck is deinfed as "variable name"=>"regex" OR "variable name"=>hash.
pcheck supports special charachters to designate rule features.

  - If you define a pcheck rule, then the hash should have a key/value pair that will be validated with the RE.
  - \* If the name starts with *, the variable is optional. If it's present, it will be validated against the RE.
  - \# If the name start #, the variable is expected to be another hash.
  - ! If the name starts with !, then the variable is ignored. You can do this to document the variable but not apply any validation
  - @ If the name starts with @, then the variable is repeated e.g. a simple array. array(3,2,6,9)

The code will look in the above order, so order is important. If you want to define an optional hash,
you would the following:

```
    "*#dimensions"=>array('height'=>[0-9]{1-3},'width'=>[0-9]{1-3})
```

std_out/std_err
---
You to define how to handle the output, good or bad, with these parameters. The contract I assume is array(200=>any) or array(500=>any). If you pass an array, the framework tests to see that the first key matches this regex: ^2 - If it does, the framework creates an instance of std_out otherwise std_err is used. The interface for std_out and std_err is to implement a constructor.

To see how the framework handles output, read the function handleOut. 
To see how to create std_out/std_err handlers, look at the classes in Rust\Output

Application Logic
------
In order for inversion of control to work, the most important piece of information to decide on was how to pass data between the framework and the code a user would write. Rather than get overly complicated, I decided to go with an array. So, if you are going to use the framework and you want to use built in standard out/standard error then your methods should **ALWAYS** return an array using HTTP Respons codes. A good response would be array(200=>any) or array(500=>any) This means that you can return any type of data in the array, but the contract is to pass the success/error in the hash result..

To illustrate what we expect on success or failure:

```
public function stubSuccess($params) {
  return array(200=>array());
}

public function stubFailure($params) {
  return array(500=>"That call failed on purpose");
}
```

What if I don't like that contract, can I still use the framework? Yes. Do **NOT** specify std_out and std_in, and then simply inspect the return value from the $run method. The framework will create an instance of your class, execute the method and pass back the results. 

The Controller
------
```
src/Rust/Service/Controller.php
```

The controller file has the logic to grab and process input data and then map requests to routes. To use the framework, you have to either extend the Controller or create an instance and execute the run method. e.g.

```
    use Rust\Service\Controller;
    /**
     * The run function normally expects no parameters, adding url and params
     * to support test frameworks like simple test.
     *
     * @param $path   - support unit tests
     * @param $params - support unit tests
     */
    public function run($path=null, $params=null) {
        $r = new Controller();
        $result = $r->run($this->routes,$path,$params);
    }
```

When executed that way, it's assumed the route defines the std_out or the std_err. 

The validator 
------

```
    src/Rust/Hash/Validator.php
```

The validator is the code that checks the parameters against the regular pattern expressions. I separated out the variable validation logic if you want to use the validates on it's own. See the documentation on **pcheck** above.


Background
------
I inherited a lot of PHP that was cumbersome to maintain because it was overly complicated to the point of obfuscation. You had to spend hours in research before you could be productive. That legacy code motivated me to create something that was simple and easy to use. I keep dependencies to a bare minimum. This framework is extremely flexible for developers and super easy to determine entry points for API calls.

This framework makes it fun to write PHP. Input validation has proven to be a huge benefit. The defensive variable checking disappears from your code and you can focus on the logic. I'm very satisfied with this project.

My friends at work named this the Rust framework and that is why it's src/Rust and not src/Rest. 

