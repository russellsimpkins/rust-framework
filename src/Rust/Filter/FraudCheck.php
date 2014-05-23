<?php
namespace Rust\Filter;

/**
 * This is an example to show how you might filter a request. This will check to see
 * if the params has fraud=true or fraud=false.
 */
class FraudCheck {

    /**
     * The filter function is the entry point.
     * @param &$params - map of data
     * @return TRUE if filter passes test, array if filter fails
     */
    public function filter(&$params) {
	if (!empty($params['fraud']) && $params['fraud'] === TRUE) {
            return array(NYTD_Rust_HTTP_ResponseCodes::ERROR=>'Reason code: Fradulant request');
	}
        return TRUE;
    }
}