<?php
namespace Rust\Traits;

trait JsonTraits {
    function getJsonError() {
        if (json_last_error() != 0) {
            switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return 'No error.';
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