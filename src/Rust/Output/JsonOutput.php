<?php
namespace Rust\Output;

/**
 * A simple class that will output your data as a JSON string
 */
class JsonOutput {
    use \Rust\Traits\JsonTraits;
    public function __construct($code=200, $data) {
        @header("HTTP/1.0 $code");
        if (is_string($data)) {
            $data = json_decode($data,true);
            if (json_last_error() != 0) {
                throw new \Exception("Rust\Output\JsonOutput unable to decode the JSON data. Reason: " . $this->getJsonError());
            }
        }
        $data = @json_encode($data) . "\n";
        if (json_last_error() != 0) {
            throw new \Exception("Rust\Output\JsonOutput unable to encode data to JSON. Reason: " . $this->getJsonError());
        }
        @header('Content-Type: application/json; charset=utf-8');
        @header('Content-Length: ' . strlen($data));
        print $data;
        exit;
    }
}
