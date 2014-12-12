<?php
namespace Rust\Output;

/**
 * A class to handle output of RSS. This
 *
 */
class RSSOutput {

    /**
     * Constructor takes the HTTP reponse code an an array
     * of data to write to stdout as RSS
     *
     * @param \unit $code - HTTP response code
     * @param \array|map $data - Array of data
     * @return null - writes to stdout
     */
    public function __construct($code=200, &$data) {

        if (!is_array($data)) {
            throw new \Exception('RSSOutput expects $data to be an array.');
        }

        @header("HTTP/1.0 $code");
        
        $this->output = $this->toRss($data, $options);
        print_r($this->output);
        return;
    }

    /**
     * Creates RSS from the data
     * 
     * @param \mixed $data - map/object/array of data
     * @param \array $options - naming options for the data
     */
    public function toRss(&$data) {
        $rss = new RSS();
        return $rss->write($data);
    }
}