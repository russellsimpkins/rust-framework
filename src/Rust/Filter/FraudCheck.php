<?php
namespace Rust\Filter;
use Rust\HTTP;
/**
 * This is an example of how you might filter a request. This will check to see
 * if the params has fraud===TRUE
 */
class FraudCheck {

    /**
     * The filter function is the entry point.
     * @param &$params - map of data
     * @return TRUE if filter passes test, array if filter fails
     */
    public function filter(&$params) {
	if (!empty($params['fraud']) && $params['fraud'] === TRUE) {
            return array(ResponseCodes::ERROR=>'Reason code: Fradulant request');
	}
        return TRUE;
    }
}