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
     * @var CurlHandle|false $curl resource The curl instance.
     */
    protected $curl;

    /**
     * @var array Array for the post parameters.
     */
    protected $postParams;

    /**
     * @var array Array for the get parameters.
     */
    protected $getParams;

    /**
     * @var array Array for the HTTP-headers.
     */
    protected $headers;

    /**
     * @var string Request URL.
     */
    protected $url;

    /**
     * @inheritDoc
     */
    public function __construct(string $url = '')
    {
        $this->curl = curl_init(($url == '') ? NULL : $url);
        $this->setURL($url);
    }

    /**
     * @inheritDoc
     * @return cHttpRequestCurl
     */
    public function setGetParams(array $getParams)
    {
        $this->getParams = $getParams;

        return $this;
    }

    /**
     * @inheritDoc
     * @return cHttpRequestCurl
     */
    public function setPostParams(array $postParams)
    {
        $this->postParams = $postParams;

        return $this;
    }

    /**
     * @inheritDoc
     * @return cHttpRequestCurl
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @inheritDoc
     * @return cHttpRequestCurl
     */
    public function setURL(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Inserts the POST array into the headers and encodes it.
     */
    protected function preparePostRequest()
    {
        if (is_array($this->postParams)) {
            $this->setOpt(CURLOPT_POST, 1);
            $this->setOpt(CURLOPT_POSTFIELDS, $this->postParams);
        }
    }

    /**
     * Appends the GET array to the URL.
     */
    protected function prepareGetRequest()
    {
        if (is_array($this->getParams)) {
            if (!cString::contains($this->url, '?')) {
                $this->url .= "?";
            } else {
                $this->url .= '&';
            }
            foreach ($this->getParams as $key => $value) {
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
        $curlHeaders = [];
        if (!is_array($this->headers)) {
            return;
        }
        foreach ($this->headers as $key => $value) {
            $headerString = '';
            if (is_array($value)) {
                $headerString .= $value[0] . ': ' . $value[1];
            } else {
                $headerString .= $key . ': ' . $value;
            }
            $curlHeaders[] = $headerString;
        }

        $this->setOpt(CURLOPT_HTTPHEADER, $curlHeaders);
    }

    /**
     * Send the request to the server.
     *
     * @param bool $return Weather the function should return the servers response
     * @param string $method GET or POST
     * @param bool $returnHeaders Weather the headers should be included in the response
     * @return string|bool
     */
    protected function sendRequest(bool $return, string $method, bool $returnHeaders)
    {
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->setOpt(CURLOPT_HEADER, true);
        $this->setOpt(CURLOPT_URL, $this->url);

        $this->prepareHeaders();
        $this->prepareGetRequest();
        if ($method == 'POST') {
            $this->preparePostRequest();
        }

        $string = curl_exec($this->curl);

        if ($return) {
            if (!$returnHeaders) {
                $string = cString::getPartOfString(
                    cString::strstr($string, "\r\n\r\n"),
                    cString::getStringLength("\r\n\r\n")
                );
            }
            return $string;
        } else {
            return cString::findFirstPos(cString::strstr($string, "\r\n", true), '200') !== false
                || cString::findFirstPos(cString::strstr($string, "\r\n", true), '100') !== false;
        }
    }

    /**
     * @inheritDoc
     */
    public function postRequest(bool $return = true, bool $returnHeaders = false)
    {
        return $this->sendRequest($return, 'POST', $returnHeaders);
    }

    /**
     * @inheritDoc
     */
    public function getRequest(bool $return = true, bool $returnHeaders = false)
    {
        return $this->sendRequest($return, 'GET', $returnHeaders);
    }

    /**
     * @inheritDoc
     */
    public function request(bool $return = true, bool $returnHeaders = false)
    {
        return $this->sendRequest($return, 'POST', $returnHeaders);
    }

    /**
     * Sets CURL options.
     *
     * @param int $curlOpt
     *         One of the CURLOPT_* constants
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
