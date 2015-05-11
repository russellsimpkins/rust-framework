<?php
namespace Rust\Output;
class NYTD_Rust_Output_StandardOut {

    public function __construct($code=500, $data, $options = array()) {
        @header("HTTP/1.0 $code");

        if (isset($options['cors']) && $options['cors']) {
            header('Access-Control-Allow-Origin: ' . $options['cors']['origin']);
            header('Access-Control-Allow-Methods: ' . join(',', $options['cors']['methods']));
        }

        if (isset($options['output_format']) && $options['output_format'] == 'json') {
            $response_head = array(
                                'status' => 'OK'
                                );
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array_merge($response_head, $data));
        } else if (isset($options['output_format']) && $options['output_format'] == 'jsonp') {
            $response_head = array(
                                'status' => 'OK'
                                );
            header("Content-Type: application/javascript; charset=UTF-8");
            echo $_GET['callback'] . '(' . json_encode(array_merge($response_head, $data)) . ');';
        } else {
            print_r($data);
        }
    }
}
