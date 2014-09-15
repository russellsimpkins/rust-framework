<?php
namespace Rust\Output;

class StandardOut {

    public function __construct($code=500, $data) {
        @header("HTTP/1.0 $code");
        print_r($data);
    exit;
    }
}
