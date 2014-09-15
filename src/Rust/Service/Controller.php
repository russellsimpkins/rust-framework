<?php
namespace Rust\Service;

use Rust\HTTP\ResponseCodes;
use Rust\Hash\Validator;
use Rust\Output\JsonOutput;
use Rust\Output\JsonError;
use Rust\Output\StandardOut;
use Rust\Output\StandardErr;

/**
 * This service controller provides the functionality to control which class methods
 * are executed. This class checks for json
 * {foo:{},bar:{}} 
 * 
 * You may extend this class or instanciate it. Either approach should work equally well.
 * It is best to create your own Service controller where you define your routes and 
 * implement your run method. If you extend this class, then you would use your route 
 * definition and pass it to the parent::run or if you instanciate this class you would
 * instead pass the routes to $obj->run
 *
 * function run($routes) {
 *     parent::run($routes);
 * ....
 *
 * function run($routes) {
 *     $r = new Controller();
 *     $r->run($routes);
 *
 */
class Controller {
    
    public static $docs = array(
        array('rule'    => ';^.*help.json$;',
              'params'  => array('script_path'),
              'action'  => 'GET',
              'class'   => 'Rust\Service\Controller',
              'method'  => 'help',
              'name'    => 'Help method',
              'docs'    => 'Help method describes all of the features the api supports.',
              'std_out' => 'Rust\Output\JsonOutput',
              'std_err' => 'Rust\Output\JsonError',
              'pcheck'  => array()
              ),
        array('rule'    => ';^.*iodoc.json$;',
              'params'  => array('script_path'),
              'action'  => 'GET',
              'class'   => 'Rust\Service\Controller',
              'method'  => 'iodoc',
              'name'    => 'iodocs method',
              'docs'    => 'iodcs method describes all of the features the api supports in iodoc format.',
              'std_out' => 'Rust\Output\JsonOutput',
              'std_err' => 'Rust\Output\JsonError',
              'pcheck'  => array(),
              ));
    
    /**
     * Our constructor takes on the task of defining the action and params
     *
     * @param $params - if this is set, we will not look at the REQUEST_METHOD and expect $action to be passed as well
     * @param $action - set this when setting $params
     */
    public function __construct($params=null, $action='GET') {
        
        if (empty($params)) {
            switch (@$_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $this->action = 'POST';
                $this->params = empty($_POST) ? self::decodeInput(file_get_contents('php://input')) : self::decodeInput($_POST);
                break;
            case 'GET':
                $this->action = 'GET';
                $this->params = self::decodeInput($_GET);
                break;
            case 'PUT':
                $this->action = 'PUT';
                $this->params = empty($_POST) ? self::decodeInput(file_get_contents('php://input')) : self::decodeInput($_POST);
                break;
            case 'DELETE': 
                $this->action = 'DELETE';
                $this->params = empty($_POST) ? self::decodeInput(file_get_contents('php://input')) : self::decodeInput($_POST);
                break;
            default : 
                $this->params = $params;
                $this->action = $action;
                break;
            }
        } else {
            $this->action = $action;
            $this->params = self::decodeInput($params);
        }
    }

    /**
     * The run function normally expects no parameters, adding url and params
     * to support testing with simple test.
     *
     * @param $routes  - the routes supported, intended to be sent from classes extending this class
     * @param $path    - support unit tests
     * @param $params  - support unit tests
     * @return hash    - e.g. array(200=>$result) or array(404=>'NOT FOUND');
     */
    public function run(&$routes, $path=null, $params=array()) {
        
        $notfound     = array(ResponseCodes::NOTFOUND=>'NOT FOUND.');
        $notsupported = array(ResponseCodes::NOTSUPPORTED=>'NOT SUPPORTED.');
        $found        = false;
        $out          = empty($routes['std_out']) ? null    : $routes['std_out'];
        $err          = empty($routes['std_err']) ? null    : $routes['std_err'];
        $filters      = empty($routes['filters']) ? array() : $routes['filters'];
        $this->routes = $routes;

        if (empty($path) && !empty($_SERVER['SCRIPT_NAME'])) {
            $path = $_SERVER['SCRIPT_NAME'];
        }

        if (!empty($params)) {
            $this->params = $params;
        } elseif (empty($this->params)) {
            $this->params=array();
        }

        if (!empty($routes['routes'])) {
            $allroutes = array_merge($routes['routes'], self::$docs);
            $this->routes['routes'] = array_merge($routes['routes'], self::$docs);
        } else {
            $allroutes = array_merge($routes, self::$docs);
            $this->routes = array_merge($routes, self::$docs);
        }

        foreach ($allroutes as $route) {
            if (empty($route['rule'])) {
                continue;
            }
            /*
             * only execute if the uri matches the rule and the action
             */
            if (preg_match($route['rule'], $path, $matches)) {
                $found = true;

                if ($this->action <> $route['action']) {
                    continue;
                }

                $out = empty($route['std_out']) ? $out : $route['std_out'];
                $err = empty($route['std_err']) ? $err : $route['std_err'];

                if (!empty($route['filters'])) {
                    $filters = array_merge($filters,$route['filters']);
                }

                /*
                 * Run any filters. Filters support pre-processing such as
                 * validating the cookies. Passing in $this->params permits
                 * the filters to set variables, like getting the id from the
                 * NYT-S cookie.
                 */
                if (!empty($filters)) {
                    // class is a reserved word
                    foreach ($filters as $clazz) {
                        $filter = new $clazz;
                        $ok     = $filter->filter($this->params);

                        /*
                         * a filter will return TRUE, nothing if all went well
                         */
                        if ($ok !== TRUE) {
                            return self::handleOut($ok, $out, $err);
                        }
                    }
                }

                /*
                 * Add name/value pairs for the parameters passed in the URL,
                 * but don't override existing params.
                 */
                $index = 0;
                if (!empty($route['params'])) {
                    foreach ($route['params'] as $param) {
                        if (isset($this->params[$param])) {
                            $index++;
                        } else { 
                            $this->params[$param] = $matches[$index++];
                        }
                    }
                }

                /*
                 * The validator returns TRUE if ok, otherwise an array
                 */
                if (!empty($route['pcheck'])) {
                    $valid = Validator::validate($route['pcheck'], $this->params);
                    if (is_array($valid)) {
                        return self::handleOut($valid, $out, $err);
                    }
                }

                /*
                 * Call the handler function
                 */
                $hclass  = $route['class'];
                $method  = $route['method'];
                $handler = new $hclass;

                if (($method == 'help' || $method == 'iodoc') && $hclass == 'Rust\Service\Controller') {
                    /*
                     * a special case to spit out the route data sharing what services are provided
                     */
                    $result = $handler->$method($this->routes);
                } else {
                    $result = $handler->$method($this->params);
                }
                return self::handleOut($result, $out, $err);
            }
        }

        /*
         * If we found a matching URL but no supported method, send 405 else 404
         */
        if ($found) {
            return self::handleOut($notsupported, $out, $err);
        }

        $out='Rust\Output\JsonOutput';
        $err='Rust\Output\JsonError';
        return self::handleOut($notfound, $out, $err);
    }
            
    /**
     * One function to encapsulate handling the success or failure.
     * If we don't get an array as input, we just use $out.
     *
     * @param &$result array following array(200=>any) or array([0-9]{1,3}=>any)
     * @param &$out class name for success
     * @param &$err class name for error
     * @return array or null - will write to out or err if not null
     */
    public static function handleOut(&$result, &$out, &$err) {
        if ($out!=null && $err!=null) {
            try {
                if (is_array($result)) {
                    /*
                     * Any 2xx series is a valid success.
                     */
                    foreach ($result as $code=>$block) {
                        if (preg_match(';^2;', $code)) {
                            $res = new $out($code, $block);
                            return $result;
                        }
                        $res = new $err($code,$block);
                        return $result;
                    }
                }
                $res = new $out($code,$block);
            } catch (Exception $e) {
                print("EXCEPTION: More than likely there is a class naming issue in your route: $e");
            }
            
        }
        return $result;
    }

    /**
     * One function to handle the incoming data. Our primary mechanism
     * is to pass json. So, it made sense to decode the input in one spot.
     * This code will check to see if the input is a string that 
     * starts with { and ends with } or [ and ] and will decode
     * the json if true. 
     *
     * @param $input - variant. Something that came in as GET/POST data.
     */
    public static function decodeInput($input) {
        /*
         * No conversion if any checks are true
         */
        if (empty($input) || is_array($input) || is_object($input)) {
            return $input;
        }

        $input = trim($input);

        /*
         * Is it json?
         */
        if (is_string($input) && 
            preg_match('/^({|\[)/',$input) && 
            preg_match('/(}|\])$/m',$input)) {
            return @json_decode($input, true);
        }
        
        return $input;
    }

    /**
     * Creates documentation from the route(s)
     *
     */
    public function help($routes) {
        $data = array();
        foreach ($routes['routes'] as $route) {
            unset($item);
            if (empty($route['rule'])) {
                continue;
            }
            $item['uri']      = $route['rule'];
            $item['method']   = $route['action'];
            $item['name']     = empty($route['name']) ? 'Name not set in the route.' : $route['name'];
            $item['provides'] = empty($route['docs']) ? 'To lazy add documentation to the route.' : $route['docs'];
            
            /*
             * Get the regex patterns from the path urls. Path urls let you define variables in the url
             * e.g. /some/( valid re )/svc/([a-z]{1,20})/example.json 
             * The next block looks to parse out the ( ) and build up the expressions in $pathres. $pathres
             * variables get named in the route field "params". Users should ONLY name path params in "params"
             * $pathres == path regex patterns
             */
            $end     = strlen($item['uri']);
            $pathres = array();
            $addre   = false;
            $elem    = '';
            foreach (str_split($item['uri'],1) as $c) {
                if ($c == '(') {
                    $addre = true;
                    $elem .= $c;
                    continue;
                }
                if ($c == ')') {
                    $addre     = false;
                    $elem     .= $c;
                    $pathres[] = $elem;
                    $elem      = '';
                    continue;
                }
                if ($addre) {
                    $elem .= $c;
                }
            }

            /*
             * Paths can have params that can have names. We want to grab all
             * but the first path parameter names since the first is just a place
             * holder given that preg_match returns the entire string in position 0
             * when there is a match.
             */
            if (isset($route['params']) && 
                is_array($route['params'])) {
                $end                     = count($route['params']);
                $item['path_parameters'] = array();
                for ($i = 1; $i < $end; ++$i) {
                    $item['path_parameters'][$route['params'][$i]] = array( 'name'        =>$route['params'][$i], 
                                                                            'regex'       =>@$pathres[($i-1)], 
                                                                            'is_required' =>true, 
                                                                            'is_validated'=>true);
                }

                if (count($item['path_parameters']) == 0) {
                    unset($item['path_parameters']);
                }
            }

            if (isset($route['pcheck'])) {
                $item[strtolower($item['method']).'_parameters'] = $this->recurseParams($route['pcheck']);
            }

            $data[] = $item;
        }
        return array(ResponseCodes::GOOD=>$data);
    }

    /**
     * Creates documentation from the route(s)
     *
     */
    public function iodoc($routes) {
        $data = array();

        $data['name']           = empty($routes['name']) ? 'UNNAMED NYTimes.com REST Service' : $routes['name'];
        $data['protocol']       = empty($routes['protocol']) ? 'http' : $routes['protocol'];
        $data['baseURL']        = empty($routes['baseUrl']) ? 'internal.du.nytimes.com' : $routes['baseUrl'];
        $data['publicPath']     = '';
        $data['privatePath']    = '';
        $data['auth']           = '';
        $data['methods']        = array();

        foreach ($routes['routes'] as $route) {
            unset($item);
            $item['URI']                = empty($route['rule']) ? 'UNKNOWN' : $route['rule'];
            $item['HTTPMethod']         = empty($route['action']) ? 'UNKNOWN' : $route['action'];
            $item['MethodName']         = empty($route['name']) ? 'UNNAMED!' : $route['name'];
            $item['Synopsis']           = empty($route['docs']) ? 'UNDOCUMENTED!.' : $route['docs'];
            $item['RequiresOAuth']      = 'N';

            /*
             * Get the regex patterns from the path urls. Path urls let you define variables in the url
             * e.g. /some/( valid re )/svc/([a-z]{1,20})/example.json 
             * The next block looks to parse out the ( ) and build up the expressions in $pathres. $pathres
             * variables get named in the route field "params". Users should ONLY name path params in "params"
             * $pathres == path regex patterns
             */
            $end     = strlen($item['URI']);
            $pathres = array();
            $addre   = false;
            $elem    = '';
            foreach (str_split($item['URI'],1) as $c) {
                if ($c == '(') {
                    $addre = true;
                    $elem .= $c;
                    continue;
                }
                if ($c == ')') {
                    $addre     = false;
                    $elem     .= $c;
                    $pathres[] = $elem;
                    $elem      = '';
                    continue;
                }
                if ($addre) {
                    $elem .= $c;
                }
            }

            /*
             * Paths can have params that can have names. We want to grab all
             * but the first path parameter names since the first is just a place
             * holder given that preg_match returns the entire string in position 0
             * when there is a match.
             */
            $item['parameters'] = array();
            if (isset($route['params']) && 
                is_array($route['params'])) {
                $end = count($route['params']);
                
                for ($i = 1; $i < $end; ++$i) {
                    $item['parameters'][] = array( 'Name'        =>$route['params'][$i],
                                                   'Type'        =>"string", 
                                                   'Description' =>'The parameter is expected in the URL. Valid values defined as a php regex: '. $pathres[($i-1)],
                                                   'Required'    =>'Y');
                }
            }
        
            if (isset($route['pcheck'])) {
                $params = $this->recurseParams($route['pcheck']);
                foreach ($params as $param) {
                    $parm = array('Name' => $param['name'],
                                  'Required' => $param['is_required'] ? 'Y' : 'N'
                                  );
            
                    if (!empty($param['elements']) && is_array($param['elements'])) {
                        $parm['Description'] = 'JSON Object: ' . json_encode($param['elements']);
                    } else {
                        if ($param['is_validated']) {
                            $parm['Description'] = 'Valid values defined as a php regex: ' . $param['regex'];
                        } else {
                            $parm['Description'] = 'The value is unchecked. See the method documentation for notes.';
                        }
                    }
                    if ($param['is_repeated']) {
                        $parm['Description'] .= ' This value is repeated.';
                    }
                    $item['parameters'][] = $parm;
                    unset($parm);
                }
            }
            $data['methods'][] = $item;
        }
        return array(\Rust\HTTP\ResponseCodes::GOOD=>$data);
    }

    /**
     * probably a bad name. This method is meant to recursivly call itself to 
     * build up parameter data for documentation on pcheck data. In the routes
     * we let you document nested hash validation with a #. So, if we find that
     * character, we need to traverse the next array
     */
    public function recurseParams($params) {
        $data = array();
        foreach($params as $key=>$val) {
            $item      = array();
            $c         = substr($key,0,1);
            $missingOk = false;
            $hasRule   = true;
            $repeats   = false;

            if ($c == '*') {
                $key       = substr($key,1);
                $c         = substr($key,0,1);
                $missingOk = true;
            }

            if ($c == '!') {
                $hasRule = false;
                $key     = substr($key,1);
                $c       = substr($key,0,1);
            }

            if ($c == '@') {
                $repeats = true;
                $key     = substr($key,1);
                $c       = substr($key,0,1);
            }

            if ($c == '#') {
                $key     = substr($key,1);
                $data[$key]['name']         = $key;
                $data[$key]['is_required']  = $missingOk ? FALSE : TRUE;
                $data[$key]['is_validated'] = $hasRule ? TRUE : FALSE;
                $data[$key]['is_repeated']  = $repeats;
                $data[$key]['elements']     = $this->recurseParams($val);
                continue;
            }

            $item['name']         = $key;
            $item['is_required']  = $missingOk ? FALSE : TRUE;
            $item['is_validated'] = $hasRule ? TRUE : FALSE;
            $item['is_repeated']  = $repeats;
            
            if ($hasRule) {
                $item['regex']    = $val;
            }

            $data[$key] = $item;
        }

        return $data;
    }

    /**
     * Stock setter
     * @param $params - array of data
     */
    public function setParams($params) {
        $this->params = $params;
    }
    
    /**
     * Stock getter
     * @return value of $this->params
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Stock setter
     * @param $action - GET/PUT/POST/DELETE
     */
    public function setAction($action) {
        $this->action = action;
    }

    /**
     * Stock getter
     * @return String GET|PUT|POST|DELETE
     */
    public function getAction() {
        return $this->action;
    }
}
