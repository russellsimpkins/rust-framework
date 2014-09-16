<?php
namespace Rust\Traits;

trait JsonTraits {
    /**
     * This is a utility trait to share the logic for figuring out
     * what went wrong with our json.
     * Adding this ignore because the code coverage reports don't handle traits well
     * @codeCoverageIgnore
     */
    function getJsonError() {
        if (json_last_error() != 0) {
            switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded.';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch.';
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found.';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON.';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            default:
                return 'Unknown error.';
            }
        }
    }
}