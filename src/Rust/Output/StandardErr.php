<?php
namespace Rust\Output;

class StandardErr {

    public function __construct($code=500, $msg='Unexpected error.') {
        @header("HTTP/1.0 $code");
        print_r($msg);
	exit;
    }
}
