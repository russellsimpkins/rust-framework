<?php
namespace Rust\Hash;

use Rust\HTTP\ResponseCodes;

/**
 * This class encapsulates the logic used to do input validation. Input validation is 
 * done using regular patterns and preg_match.
 * Here are the validation rules:
 * - Parameters can be required or optional. 
 * - If you don't define a validation rule for each parameter, undefined
 *   parameters aren't validated and aren't prevented.
 *
 * - If you define a validation rule and the parameter is not passed, the
 *   validation fails unless it is marked as optional
 *   - an optional parameter rule begins with * e.g. *user_id=[0-9]{5-10} 
 *     user_id is optional, but if it's passed must be a digit 5 - 10 in length
 *
 * - If the validation rule starts with #, the validation rule is for an 
 *   array. *# means an optional array
 *
 * - If the validation rule starts with !, the validation rule is ignored. 
 *   This allows you to define what you expect
 *
 * - If the validation rule starts with @, then the variable is treated as an 
 *   array e.g. [1,3,6] and we validate each entry in the array
 */
class Validator {
    
    /**
     * A utility function to validate input parameters.
     *
     * @param &$rules  - hash of name value pairs where the value is a regex
     * @param &$params - the data to validate in name value pairs.
     * @returns        - nothing but will throw errors if validation fails
     */
    public static function validate(&$rules, &$params) {

        if (!empty($params) && !is_array($params)) { 
            return array(ResponseCodes::MISSING_PARAM=>'The validate method expects the params passed in to be an array, but it\'s not.');
        }

        foreach ($rules as $rule=>&$re) {
            $missingOk = false;
            $repeating = false;

            if (substr($rule,0,1) == '*') {
                $missingOk = true;
                $rule      = substr($rule,1);
            }

            $c = substr($rule,0,1);

            /*
             * Do not validate if next char is !
             */
            if ($c == '!') {
                continue;
            }

            /*
             * The # means "check the nested array." So, lets make sure they passed in
             * an array and then recursivly call validateParams with the nested array
             */
            if ($c == '#') {
                $rule  = substr($rule,1);
                $valid = self::validate($re, @$params[$rule]);
                if ($valid !== true) {
                    return false;
                }
                continue;
            }
            
            /*
             * @ signifies repeating value
             */
            if ($c == '@') {
                $rule      = substr($rule,1);
                $repeating = true;
                $c         = substr($rule,0,1);
                /*
                 * If its a hash, we want to loop through the repeating values to check
                 */
                if ($c == '#') {
                    $repeating = false;
                    $rule      = substr($rule,1);
                    if (!empty($params[$rule])) {
                        foreach ($params[$rule] as $data) {
                            $valid = self::validate($re, $data);
                            if ($valid !== true) {
                                return $valid;
                            }
                        }
                    } else {
                        if ($missingOk == false) {
                            return array(ResponseCodes::MISSING_PARAM=>"Parameter <{$rule}> value was missing and is required for this api call.");
                        }
                    }
                    continue;
                }
            }

            /*
             * If param is empty and its missing go to next element
             */
            if ($missingOk && !isset($params[$rule])) {
                continue;
            }

            /*
             * If we require the missing param then fail
             */
            if (!isset($params[$rule])) {
                return array(ResponseCodes::MISSING_PARAM=>"Parameter <{$rule}> value was missing and is required for this api call.");
            }
            
            if ($repeating) {
                
                if (!is_array($params[$rule])) {
                    $data = array($params[$rule]);
                } else {
                    $data = $params[$rule];
                }

                foreach ($data as $val) {
                    if (preg_match($re,$val)==0) {
                        return array(ResponseCodes::MISSING_PARAM=>"Parameter <{$rule}> value failed to match validation rule:: <<{$re}>>.Since this rule supports repeating values, be sure to check all values passed in for <${rule}>.");
                    }
                }
                continue;
            }

            /*
             * Fail if the param does not pass the validation check
             */
            if (preg_match($re,$params[$rule]) == 0) {
                return array(ResponseCodes::MISSING_PARAM=>"Parameter <{$rule}> value failed to match validation rule:: <<{$re}>>.");
            }

            /*
             * Fail if someone configured the check wrong.
             */
            if (preg_match($re,$params[$rule]) === FALSE) {
                return array(ResponseCodes::BAD_RE=>"API has code bug because of an invalid validation regex. rule:: <{$rule}>  pattern:: <<{$re}>>.");
            }
        }
        return TRUE;
    }
}
