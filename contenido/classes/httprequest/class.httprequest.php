<?php
/**
 * This file contains the the general HttpRequest class
 * Extends this class and implement the functions to use
 * other methods of doing HTTP requests
 *
 * @package Core
 * @subpackage Core
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract HttpRequest class
 *
 * @package Core
 * @subpackage Core
 */
abstract class cHttpRequest {
    
    /**
     * Creates a new cHttpRequest object.
     * The function determines the best extension to use and returns an object accordingly
     * 
     * @param string $url URL of the HTTP request
     * @return object extends cHttpRequest
     */
    public static function getHttpRequest($url = '') {
        $className = 'cHttpRequestCurl';
        if(!function_exists('curl_exec') || CURLOPT_RETURNTRANSFER != 19913) {
            $className = 'cHttpRequestSocket';
        }
        
        return new $className($url);
    }
    
    /**
     * Basic constructor
     * 
     * @param string $url URL of the HTTP request
     */
    abstract public function __construct($url = '');
    
    /**
     * Peform the request using POST
     * 
     * @param bool $return If true, response of the server gets returned as string
     * @param bool $returnHeaders If true, headers will be included in the response
     * 
     * @return string|bool False on error, response otherwise
     */
    abstract public function postRequest($return = true, $returnHeaders = false);

    /**
     * Peform the request using GET
     *
     * @param bool $return If true, response of the server gets returned as string
     * @param bool $returnHeaders If true, headers will be included in the response
     *
     * @return string|bool False on error, response otherwise
     */
    abstract public function getRequest($return = true, $returnHeaders = false);

    /**
     * Peform the request using POST AND append all GET parameters
     *
     * @param bool $return If true, response of the server gets returned as string
     * @param bool $returnHeaders If true, headers will be included in the response
     *
     * @return string|bool False on error, response otherwise
     */
    abstract public function request($return = true, $returnHeaders = false);
    
    /**
     * Set the GET parameters
     * 
     * @param array $array An associative array containing keys and values of the GET parameters
     */
    abstract public function setGetParams($array);

    /**
     * Set the POST parameters
     *
     * @param array $array An associative array containing keys and values of the POST parameters
     */
    abstract public function setPostParams($array);
    
    /**
     * Set the HTTP headers
     * 
     * @param array $array An associative array containing the HTTP headers
     */
    abstract public function setHeaders($array);
    
    /**
     * Set the request URL
     * 
     * @param string $url the URL
     */
    abstract public function setURL($url);
}

?>