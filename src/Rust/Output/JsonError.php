<?php
namespace Rust\Output;

/** 
 * A utility class to return a json error. It will return e.g. 
 * {500:'Unforseen fatal exception'} and the HTTP response code will also be 500
 */
class JsonError {
    public function __construct($code=500, $msg='Unforeseen fatal exception.') {
        $resp[$code]  = @utf8_decode($msg);
        @header("HTTP/1.0 $code");
        @header('Content-Type: application/json');
        print @json_encode($resp);
        exit;
    }
}
