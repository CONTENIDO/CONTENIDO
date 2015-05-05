<?php

/**
 * This file contains an implementation of HttpRequest using curl
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
 * Curl implementation of HttpRequest
 *
 * @package Core
 * @subpackage Core
 */
class cHttpRequestCurl extends cHttpRequest {

    /**
     * the curl instance
     *
     * @var $curl ressource
     */
    protected $curl;

    /**
     * Array for the post parameters
     *
     * @var array
     */
    protected $postArray;

    /**
     * Array for the get parameters
     *
     * @var array
     */
    protected $getArray;

    /**
     * Array for the HTTP-headers
     *
     * @var array
     */
    protected $headerArray;

    /**
     * Request URL
     *
     * @var string
     */
    protected $url;

    /**
     * Basic constructor
     *
     * @see cHttpRequest::__construct()
     * @see cHttpRequest::getHttpRequest()
     * @param string $url [optional]
     *         URL for the request
     */
    public function __construct($url = '') {
        $this->curl = curl_init(($url == '') ? NULL : $url);
        $this->setURL($url);
    }

    /**
     * Set the GET parameters
     *
     * @see cHttpRequest::setGetParams()
     * @param array $array
     *         associative array containing keys and values of the GET parameters
     * @return cHttpRequest
     */
    public function setGetParams($array) {
        $this->getArray = $array;

        return $this;
    }

    /**
     * Set the POST parameters
     *
     * @see cHttpRequest::setPostParams()
     * @param array $array
     *         associative array containing keys and values of the POST parameters
     * @return cHttpRequest
     */
    public function setPostParams($array) {
        $this->postArray = $array;

        return $this;
    }

    /**
     * Set the HTTP headers
     *
     * @see cHttpRequest::setHeaders()
     * @param array $array
     *         associative array containing the HTTP headers
     * @return cHttpRequest
     */
    public function setHeaders($array) {
        $this->headerArray = $array;

        return $this;
    }

    /**
     * Set the request URL
     *
     * @see cHttpRequest::setURL()
     * @param string $url
     *         the URL
     * @return cHttpRequest
     */
    public function setURL($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * Inserts the POST array into the headers and encodes it
     */
    protected function preparePostRequest() {
        if (is_array($this->postArray)) {
            $this->setOpt(CURLOPT_POST, 1);
            $this->setOpt(CURLOPT_POSTFIELDS, $this->postArray);
        }
    }

    /**
     * Appends the GET array to the URL
     */
    protected function prepareGetRequest() {
        if (is_array($this->getArray)) {
            if (!cString::contains($this->url, '?')) {
                $this->url .= "?";
            } else {
                $this->url .= '&';
            }
            foreach ($this->getArray as $key => $value) {
                $this->url .= urlencode($key) . '=' . urlencode($value) . '&';
            }
            $this->url = substr($this->url, 0, strlen($this->url) - 1);
        }
        $this->setOpt(CURLOPT_URL, $this->url);
    }

    /**
     * Reads all the custom headers and add them to the header string
     */
    protected function prepareHeaders() {
        $curlHeaderArray = array();
        if (!is_array($this->headerArray)) {
            return;
        }
        foreach ($this->headerArray as $key => $value) {
            $headerString = '';
            if (is_array($value)) {
                $headerString .= $value[0] . ': ' . $value[1];
            } else {
                $headerString .= $key . ': ' . $value;
            }
            array_push($curlHeaderArray, $headerString);
        }

        $this->setOpt(CURLOPT_HTTPHEADER, $curlHeaderArray);
    }

    /**
     * Send the request to the server
     *
     * @param bool $return
     *         Wether the function should return the servers response
     * @param string $method
     *         GET or POST
     * @param bool $returnHeaders
     *         Wether the headers should be included in the response
     * @return string|bool
     */
    protected function sendRequest($return, $method, $returnHeaders) {
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->setOpt(CURLOPT_HEADER, true);
        $this->setOpt(CURLOPT_URL, $this->url);

        $this->prepareHeaders();
        $this->prepareGetRequest();
        if ($method = 'POST') {
            $this->preparePostRequest();
        }

        $string = curl_exec($this->curl);

        if ($return) {
            if (!$returnHeaders) {
                $string = substr(cString::strstr($string, "\r\n\r\n"), strlen("\r\n\r\n"));
            }
            return $string;
        } else {
            return strpos(cString::strstr($string, "\r\n", true), '200') !== false || strpos(cString::strstr($string, "\r\n", true), '100') !== false;
        }
    }

    /**
     * Peform the request using POST
     *
     * @see cHttpRequest::postRequest()
     * @param bool $return [optional]
     *         If true, response of the server gets returned as string
     * @param bool $returnHeaders [optional]
     *         If true, headers will be included in the response
     * @return string|bool
     *         False on error, response otherwise
     */
    public function postRequest($return = true, $returnHeaders = false) {
        return $this->sendRequest($return, 'POST', $returnHeaders);
    }

    /**
     * Peform the request using GET
     *
     * @see cHttpRequest::getRequest()
     * @param bool $return [optional]
     *         If true, response of the server gets returned as string
     * @param bool $returnHeaders [optional]
     *         If true, headers will be included in the response
     * @return string|bool
     *         False on error, response otherwise
     */
    public function getRequest($return = true, $returnHeaders = false) {
        return $this->sendRequest($return, 'GET', $returnHeaders);
    }

    /**
     * Peform the request using POST AND append all GET parameters
     *
     * @see cHttpRequest::request()
     * @param bool $return [optional]
     *         If true, response of the server gets returned as string
     * @param bool $returnHeaders [optional]
     *         If true, headers will be included in the response
     * @return string|bool
     *         False on error, response otherwise
     */
    public function request($return = true, $returnHeaders = false) {
        return $this->sendRequest($return, 'POST', $returnHeaders);
    }

    /**
     * Sets CURL options
     *
     * @see curl_setopt()
     * @param int $curlOpt
     *         One of the CURLOPT constants
     * @param mixed $value
     *         Value for the option
     * @return cHttpRequest
     */
    public function setOpt($curlOpt, $value) {
        curl_setopt($this->curl, $curlOpt, $value);

        return $this;
    }

    /**
     * Returns the curl reference
     *
     * @return curl
     */
    public function getCurl() {
        return $this->curl;
    }
}
