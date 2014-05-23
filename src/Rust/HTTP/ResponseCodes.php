<?php
namespace Rust\HTTP;

/**
 * In this class I am putting references names to http response codes for 
 * use in the code - just to act as a point of reference
 */
class ResponseCodes {

    const MISSING_PARAM = 400;
    const NOTFOUND      = 404;
    const NOTSUPPORTED  = 405;
    const BAD_RE        = 509;
    const GOOD          = 200;
    const ERROR         = 500;
}
