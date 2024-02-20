<?php

/**
 * This file contains an implementation of HttpRequest using curl
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
 * Curl implementation of HttpRequest.
 *
 * @package    Core
 * @subpackage Core
 */
class cHttpRequestCurl extends cHttpRequest
{

    /**
     * The curl instance.
     *
     * @var $curl resource
     */
    protected $curl;

    /**
     * Array for the post parameters.
     *
     * @var array
     */
    protected $postArray;

    /**
     * Array for the get parameters.
     *
     * @var array
     */
    protected $getArray;

    /**
     * Array for the HTTP-headers.
     *
     * @var array
     */
    protected $headerArray;

    /**
     * Request URL.
     *
     * @var string
     */
    protected $url;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $url [optional]
     *         URL for the request
     * @see cHttpRequest::getHttpRequest()
     * @see cHttpRequest::__construct()
     */
    public function __construct($url = '')
    {
        $this->curl = curl_init(($url == '') ? NULL : $url);
        $this->setURL($url);
    }

    /**
     * Set the GET parameters.
     *
     * @param array $array
     *         associative array containing keys and values of the GET parameters
     * @return cHttpRequest
     * @see cHttpRequest::setGetParams()
     */
    public function setGetParams($array)
    {
        $this->getArray = $array;

        return $this;
    }

    /**
     * Set the POST parameters.
     *
     * @param array $array
     *         associative array containing keys and values of the POST parameters
     * @return cHttpRequest
     * @see cHttpRequest::setPostParams()
     */
    public function setPostParams($array)
    {
        $this->postArray = $array;

        return $this;
    }

    /**
     * Set the HTTP headers.
     *
     * @param array $array
     *         associative array containing the HTTP headers
     * @return cHttpRequest
     * @see cHttpRequest::setHeaders()
     */
    public function setHeaders($array)
    {
        $this->headerArray = $array;

        return $this;
    }

    /**
     * Set the request URL.
     *
     * @param string $url
     *         the URL
     * @return cHttpRequest
     * @see cHttpRequest::setURL()
     */
    public function setURL($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Inserts the POST array into the headers and encodes it.
     */
    protected function preparePostRequest()
    {
        if (is_array($this->postArray)) {
            $this->setOpt(CURLOPT_POST, 1);
            $this->setOpt(CURLOPT_POSTFIELDS, $this->postArray);
        }
    }

    /**
     * Appends the GET array to the URL.
     */
    protected function prepareGetRequest()
    {
        if (is_array($this->getArray)) {
            if (!cString::contains($this->url, '?')) {
                $this->url .= "?";
            } else {
                $this->url .= '&';
            }
            foreach ($this->getArray as $key => $value) {
                $this->url .= urlencode($key) . '=' . urlencode($value) . '&';
            }
            $this->url = cString::getPartOfString($this->url, 0, cString::getStringLength($this->url) - 1);
        }
        $this->setOpt(CURLOPT_URL, $this->url);
    }

    /**
     * Reads all the custom headers and add them to the header string.
     */
    protected function prepareHeaders()
    {
        $curlHeaderArray = [];
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
     * Send the request to the server.
     *
     * @param bool $return
     *         Wether the function should return the servers response
     * @param string $method
     *         GET or POST
     * @param bool $returnHeaders
     *         Wether the headers should be included in the response
     * @return string|bool
     */
    protected function sendRequest($return, $method, $returnHeaders)
    {
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
                $string = cString::getPartOfString(cString::strstr($string, "\r\n\r\n"), cString::getStringLength("\r\n\r\n"));
            }
            return $string;
        } else {
            return cString::findFirstPos(cString::strstr($string, "\r\n", true), '200') !== false || cString::findFirstPos(cString::strstr($string, "\r\n", true), '100') !== false;
        }
    }

    /**
     * Perform the request using POST.
     *
     * @param bool $return [optional]
     *         If true, response of the server gets returned as string
     * @param bool $returnHeaders [optional]
     *         If true, headers will be included in the response
     * @return string|bool
     *         False on error, response otherwise
     * @see cHttpRequest::postRequest()
     */
    public function postRequest($return = true, $returnHeaders = false)
    {
        return $this->sendRequest($return, 'POST', $returnHeaders);
    }

    /**
     * Perform the request using GET.
     *
     * @param bool $return [optional]
     *         If true, response of the server gets returned as string
     * @param bool $returnHeaders [optional]
     *         If true, headers will be included in the response
     * @return string|bool
     *         False on error, response otherwise
     * @see cHttpRequest::getRequest()
     */
    public function getRequest($return = true, $returnHeaders = false)
    {
        return $this->sendRequest($return, 'GET', $returnHeaders);
    }

    /**
     * Perform the request using POST AND append all GET parameters.
     *
     * @param bool $return [optional]
     *         If true, response of the server gets returned as string
     * @param bool $returnHeaders [optional]
     *         If true, headers will be included in the response
     * @return string|bool
     *         False on error, response otherwise
     * @see cHttpRequest::request()
     */
    public function request($return = true, $returnHeaders = false)
    {
        return $this->sendRequest($return, 'POST', $returnHeaders);
    }

    /**
     * Sets CURL options.
     *
     * @param int $curlOpt
     *         One of the CURLOPT constants
     * @param mixed $value
     *         Value for the option
     * @return cHttpRequest
     * @see curl_setopt()
     */
    public function setOpt($curlOpt, $value)
    {
        curl_setopt($this->curl, $curlOpt, $value);

        return $this;
    }

    /**
     * Returns the curl reference.
     *
     * @return resource
     */
    public function getCurl()
    {
        return $this->curl;
    }
}
