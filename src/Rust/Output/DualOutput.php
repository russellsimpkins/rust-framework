<?php
namespace Rust\Output;

/**
 * A class to handle output of xml, json, jsonp with cors. This
 * class inspects the SCRIPT_NAME to see if it ends with
 * xml, json or jsonp. The default output method is json.
 */
class DualOutput {
    var $output; 
    
    /**
     * I do most of the work right in the constructor. If data or options are NOT php arrays,
     * the constructor will throw an Exception.
     *
     * @param - code - int HTTP Response code
     * @param - data - array/hash of what we need to write out
     * @param - path - string, normally from $_SERVER['SCRIPT_NAME']
     * @param - options - hash of output options.
     */
    public function __construct($code=200, $data=array(), $path='', $options=array()) {

        if (!is_array($data)) {
            // errors in the rust framework cause data to be a simple string
            if (!preg_match(';^2;', $code)) {
                $data = array('message'=>$data);
            } else {
                throw new \Exception('DualOutput expects $data to be an array.');
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

        if (preg_match(';^2;', $code)) {
            $status = 'OK';
        } else {
            $status = 'ERROR';
        }

        $response_head = array('status'=>$status);
        $format        = (empty($options['output_format'])) ? 'json' : $options['output_format'];

        /*
         * if we didn't get path passed to us, and $_SERVER['SCRIPT_NAME'] 
         * set the path
         */
        if (empty($path) && !empty($_SERVER['SCRIPT_NAME'])) {
            $path = strtolower($_SERVER['SCRIPT_NAME']);
        }

        if (preg_match(";(xml|jsonp|json)$;", $path, $matches)) {
            $format = $matches[0];
        }

        if ($format == 'json') {
            @header('Content-Type: application/json; charset=UTF-8');
            $this->output = json_encode(array_merge($response_head, $data));
            print_r($this->output);
            return;
        }

        if ($format == 'jsonp') {
            @header("Content-Type: application/javascript; charset=UTF-8");
            $this->output = $_GET['callback'] . '(' . json_encode(array_merge($response_head, $data)) . ');';
            print_r($this->output);
            return;
        }
        
        if ($format == 'xml') {
            $data = array_merge($response_head, $data);
            $this->output = $this->toXml($data, $options);
            print_r($this->output);
            return;
        }

        throw new \Exception("Unexpected output format: $format");
    }

    /**
     * Serializes the response packet as XML DomNode objects.  (You will still
     * need to explicity add the resulting top node to the DomDocument before
     * writing out the XML.)
     * @param DomDocument the document object used to create nodes.  If none
     * specified, a new one is used.
     * @return a DomNode instance
     */
    function toDomXml($doc = NULL, $data=array(), $options=array()) {
        if (is_null($doc)) {
            $doc = new \DOMDocument();
        }
        $node = $doc->createElement('response');
        
        if ( array_key_exists('top_attributes', $options) ) {
            $keys     = array_keys($options['top_attributes']);
            $num_keys = count($options['top_attributes']);

            for ($i = 0; $i < $num_keys; $i++) {
                $top_attribute  = $doc->createAttribute($keys[$i]);
                $top_attrib_txt = $doc->createTextNode($options['top_attributes'][$keys[$i]]);
                $top_attribute->appendChild($top_attrib_txt);
                $node->appendChild($top_attribute);
            } 
        }
        
        $xml = new XmlSerializer($options['xml_serializer_options']);
        $xml->serialize($data, $node);
        return $node;
    }

    /**
     * Serializes the response packet as XML text
     * @return string
     */
    public function toXml($data, $options) {
        $node = $this->toDomXml(null, $data, $options);
        $doc = $node->ownerDocument;
        $doc->appendChild($node);
        $xml = $doc->saveXml();
        return $xml;
    }
    
}