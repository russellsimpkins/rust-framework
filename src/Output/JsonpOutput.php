<?php
namespace Rust\Output;

/**
 * A simple class that will output your data as a JSON string
 */
class JsonpOuput {

    public function __construct($code=200, $data) {
        @header("HTTP/1.0 $code");
    if (!is_string($data)) {
        $data = json_decode($data,true);
    }
    if (!empty($_GET['callback'])) {
        $data= $_GET['callback'] . '(' . json_encode($data) . ");\n";
        @header('Content-Type: application/javascript; charset=utf-8');
    } else {
        $data = @json_encode($data) . "\n";
        @header('Content-Type: application/json; charset=utf-8');
    }
    
        @header('Content-Length: ' . strlen($data));
        print $data;
    exit;
    }
}
