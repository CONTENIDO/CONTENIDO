<?php

/**
 * This file contains the general HttpRequest class
 * Extends this class and implement the functions to use
 * other methods of doing HTTP requests
 *
 * @package    Core
 * @subpackage Core
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract HttpRequest class.
 *
 * @package    Core
 * @subpackage Core
 */
abstract class cHttpRequest
{

    /**
     * Creates a new cHttpRequest object. The function determines the
     * best extension to use and returns an object accordingly.
     *
     * @param string $url URL of the HTTP request
     * @return cHttpRequestCurl|cHttpRequestSocket
     */
    public static function getHttpRequest(string $url = '')
    {
        $className = 'cHttpRequestCurl';
        if (!function_exists('curl_exec') || CURLOPT_RETURNTRANSFER != 19913) {
            $className = 'cHttpRequestSocket';
        }

        return new $className($url);
    }

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $url URL of the HTTP request
     */
    abstract public function __construct(string $url = '');

    /**
     * Perform the request using POST.
     *
     * @param bool $return If true, response of the server gets returned as string
     * @param bool $returnHeaders If true, headers will be included in the response
     * @return string|bool False on error, response otherwise
     */
    abstract public function postRequest(bool $return = true, bool $returnHeaders = false);

    /**
     * Perform the request using GET.
     *
     * @param bool $return If true, response of the server gets returned as string
     * @param bool $returnHeaders If true, headers will be included in the response
     * @return string|bool False on error, response otherwise
     */
    abstract public function getRequest(bool $return = true, bool $returnHeaders = false);

    /**
     * Perform the request using POST AND append all GET parameters.
     *
     * @param bool $return true, response of the server gets returned as string
     * @param bool $returnHeaders If true, headers will be included in the response
     * @return string|bool False on error, response otherwise
     */
    abstract public function request(bool $return = true, bool $returnHeaders = false);

    /**
     * Set the GET parameters.
     *
     * @param array $getParams Associative array containing keys and values of the GET parameters
     * @return cHttpRequest
     */
    abstract public function setGetParams(array $getParams);

    /**
     * Set the POST parameters.
     *
     * @param array $postParams Associative array containing keys and values of the POST parameters
     * @return cHttpRequest
     */
    abstract public function setPostParams(array $postParams);

    /**
     * Set the HTTP headers.
     *
     * @param array $headers Associative array containing the HTTP headers
     * @return cHttpRequest
     */
    abstract public function setHeaders(array $headers);

    /**
     * Set the request URL.
     *
     * @param string $url The URL
     * @return cHttpRequest
     */
    abstract public function setURL(string $url);
}
