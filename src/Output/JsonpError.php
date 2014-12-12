<?php
namespace Rust\Output;

/** 
 * A utility class to return a json error. It will return e.g. 
 * {500:'Unforseen fatal exception'} and the HTTP response code will also be 500
 */
class JsonpError {
    public function __construct($code=500, $msg='Unforeseen fatal exception.') {
        $resp[$code]  = @utf8_decode($msg);
        @header("HTTP/1.0 $code");
		if (!empty($_GET['callback'])) {
			@header('Content-Type: application/javascript; charset=utf-8');
			$resp= $_GET['callback'] . '(' . json_encode($resp) . ");\n";
		} else {
			@header('Content-Type: application/json');
		}
        print @json_encode($resp);
        exit;
    }
}
