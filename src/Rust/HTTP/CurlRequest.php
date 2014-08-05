<?php
namespace Rust\HTTP;
use Rust\HTTP\ResponseCodes;

/**
 * This is a utility class I created to make calling other RESTFul requests easier for me.
 * I usually set an php.ini variable environment with dev, staging, production and only 
 * validate SSL certificates if were in environment==production
 */
class CurlRequest {

    /**
     * A function to encapsulate rest based calls using the curl library.
     * 
     * @params $url      - OK to have ?foo=bar
     * @params $method   - GET/PUT/POST/DELETE
     * @params $payload  - data to send in if PUT/POST
     * @return array($response_code=>$data) - the function does NOT format response data.
     */
    public function restCall($endpoint, $method='GET', $payload=null, $headers=null) {

        if (empty($endpoint)) {
            return array(ResponseCodes::MISSING_PARAM=>'Missing server endpoint. This is the URL you intended to call and it was empty.');
        }
        
        $verifySSL = get_cfg_var('environment') == 'production' ? true : false;
        $curl      = curl_init();

        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $verifySSL);
        
        /*
         * Set the header to json since we will pass json out for all calls.
         */
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        
        /*
         * Set any headers we were passed. We support a string or an array of strings.
         */
        if (!empty($headers)) {
            if (is_array($headers)) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            } else {
                curl_setopt($curl, CURLOPT_HTTPHEADER, array($headers));
            }
        }

        /*
         * Default method is GET
         */
        $method = empty($method) ? 'GET' : strtoupper($method);
        
        /*
         * Based on the method passed in, we need to set our data
         */
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }
        
        if ($method == 'PUT') {
            $fh = fopen('php://memory', 'w+');
            fwrite($fh, $payload);
            rewind($fh);
            curl_setopt($curl, CURLOPT_INFILE, $fh);
            curl_setopt($curl, CURLOPT_INFILESIZE,strlen($payload));
            curl_setopt($curl, CURLOPT_PUT, TRUE);
        }
        
        if ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if (!empty($payload)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
            }
        }

        /*
         * Execute the request
         */
        $data = trim(curl_exec($curl));
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        @fclose($fh);

        if (preg_match('/^({|\[)/', $data) && preg_match('/(}|\])$/m', $data)) {
            $data = json_encode($data,true);
        }

        return array($code=>$data);
    }
}
