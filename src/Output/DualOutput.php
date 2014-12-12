<?php
namespace Rust\Output;

/**
 * A class to handle output of json, jsonp with cors. This
 * class inspects the SCRIPT_NAME to see if it ends with
 * json or jsonp. The default output method is json.
 */
class DualOutput {
    var $output; 
    
    /**
     * I do most of the work right in the constructor. If data or options are NOT php arrays,
     * the constructor will throw an Exception.
     *
     * @param uint $code - int HTTP Response code
     * @param mixed $data - array/hash of what we need to write out
     * @param string $path - string, normally from $_SERVER['SCRIPT_NAME']
     * @param array $options - hash of output options.
     */
    public function __construct($code=200, $data=array(), $path='', $options=array()) {

        if (is_string($data)) {
            // errors in the rust framework cause data to be a simple string
            if (!preg_match(';^2;', $code)) {
                $data = array('message'=>$data);
            } else {
                $data = array($data);
            }
        }

        if (!is_array($options)) {
            throw new \Exception('DualOutput expects $options to be an array.');
        }

        @header("HTTP/1.0 $code");

        if (!empty($options['cors'])) {
            @header('Access-Control-Allow-Origin: '  . @$options['cors']['origin']);
            @header('Access-Control-Allow-Methods: ' . join(',', @$options['cors']['methods']));
        }

        $format = (empty($options['output_format'])) ? 'json' : $options['output_format'];

        /*
         * if we didn't get path passed to us, and $_SERVER['SCRIPT_NAME'] 
         * set the path
         */
        if (empty($path) && !empty($_SERVER['SCRIPT_NAME'])) {
            $path = strtolower($_SERVER['SCRIPT_NAME']);
        }

        if (preg_match(";(jsonp|json)$;", $path, $matches)) {
            $format = $matches[1];
        }

        if ($format == 'json') {
            @header('Content-Type: application/json; charset=UTF-8');
            $this->output = json_encode($data);
            print_r($this->output);
            return;
        }

        if ($format == 'jsonp') {
            @header("Content-Type: application/javascript; charset=UTF-8");
            $this->output = $_GET['callback'] . '(' . json_encode($data) . ');';
            print_r($this->output);
            return;
        }

        throw new \Exception("Unexpected output format: $format");
    }
}
