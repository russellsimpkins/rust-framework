<?php
namespace Rust\HTTP;

/**
 * In this class I am putting references names to http response codes for 
 * use in the code - just to act as a point of reference
 */
class ResponseCodes {

    const GOOD          = 200;
    const MISSING_PARAM = 400;
    const NOTFOUND      = 404;
    const NOTSUPPORTED  = 405;
    const ERROR         = 500;
    const BAD_RE        = 509;


    // HTTP 1.1 Response Codes (RFC2616)
    const CONTINE                       = 100;
    const SWITCHING_PROTOCOLS           = 101;

    const OK                            = 200;
    const CREATED                       = 201;
    const ACCEPTED                      = 202;
    const NON_AUTHORIZED_INFORMATION    = 203;
    const NO_CONTENT                    = 204;
    const RESET_CONTENT                 = 205;
    const PARTIAL_CONTENT               = 206;

    const MULTIPLE_CHOICES              = 300;
    const MOVED_PERMANENTLY             = 301;
    const MOVED_TEMPORARILY             = 302;
    const SEE_OTHER                     = 303;
    const NOT_MODIFIED                  = 304;
    const USE_PROXY                     = 305;
    const TEMPORARY_REDIRECT            = 307;

    const BAD_REQUEST                   = 400;
    const UNAUTHORIZED                  = 401;
    const PAYMENT_REQUIRED              = 402;
    const FORBIDDEN                     = 403;
    const NOT_FOUND                     = 404;
    const METHOD_NOT_ALLOWED            = 405;
    const NOT_ACCEPTABLE                = 406;
    const PROXY_AUTHENICATION_REQUIRED  = 407;
    const REQUEST_TIME_OUT              = 408;
    const CONFLICT                      = 409;
    const GONE                          = 410;
    const LENGTH_REQUIRED               = 411;
    const PRECONDITION_FAILED           = 412;
    const REQUEST_ENTITY_TOO_LARGE      = 413;
    const REQUEST_URI_TOO_LONG          = 414;
    const UNSUPPORTED_MEDIA_TYPE        = 415;
    const EXPECTATION_FAILED            = 417;

    const INTERNAL_SERVER_ERROR         = 500;
    const NOT_IMPLEMENTED               = 501;
    const BAD_GATEWAY                   = 502;
    const SERVICE_UNAVAILABLE           = 503;
    const GATEWAY_TIME_OUT              = 504;
    const HTTP_VERSION_NOT_SUPPORTED    = 505;

}
