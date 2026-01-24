<?php

/**
 * This file contains an implementation of HttpRequest using fsockopen
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
 * fsockopen implementation of HttpRequest.
 *
 * @package    Core
 * @subpackage Core
 */
class cHttpRequestSocket extends cHttpRequest
{

    /**
     * Array for the post parameters.
     *
     * @var array
     */
    protected $postParams;

    /**
     * Array for the get parameters.
     *
     * @var array
     */
    protected $getParams;

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
     * Boundary for the multipart from-data.
     *
     * @var string
     */
    protected $boundary;

    /**
     * The HTTP header.
     *
     * @var string
     */
    protected $header;

    /**
     * The HTTP body.
     *
     * @var string
     */
    protected $body;

    /**
     * @inheritDoc
     */
    public function __construct(string $url = '')
    {
        $this->url = $url;
    }

    /**
     * @inheritDoc
     * @return cHttpRequestSocket
     */
    public function setURL(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @inheritDoc
     * @return cHttpRequestSocket
     */
    public function setGetParams(array $getParams)
    {
        $this->getParams = $getParams;

        return $this;
    }

    /**
     * @inheritDoc
     * @return cHttpRequestSocket
     */
    public function setPostParams(array $postParams)
    {
        $this->postParams = $postParams;

        return $this;
    }

    /**
     * @inheritDoc
     * @return cHttpRequestSocket
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Inserts the custom headers into the header string.
     */
    protected function prepareHeaders()
    {
        $this->header = '';
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
            $this->header .= $headerString . "\r\n";
        }
    }

    /**
     * Appends teh GET array to the URL.
     */
    protected function prepareGetRequest()
    {
        if (is_array($this->getParams)) {
            if (!cString::contains($this->url, '?')) {
                $this->url .= '?';
            } else {
                $this->url .= '&';
            }
            foreach ($this->getParams as $key => $value) {
                $this->url .= urlencode($key) . '=' . urlencode($value) . '&';
            }
            $this->url = cString::getPartOfString($this->url, 0, cString::getStringLength($this->url) - 1);
        }
    }

    /**
     * Prepares the headers to send a POST request and encodes the data.
     */
    protected function preparePostRequest()
    {
        $this->boundary = md5(time()) . md5(time() * rand());
        $this->headers['Content-Type'] = 'multipart/form-data; boundary=' . $this->boundary;
        $this->boundary = '--' . $this->boundary;

        $this->body = $this->boundary . "\r\n";
        foreach ($this->postParams as $key => $value) {
            $this->body .= 'Content-Disposition: form-data; name="' . $key . "\"\r\n\r\n";
            $this->body .= $value . "\r\n";
            $this->body .= $this->boundary . "\r\n";
        }
        $this->headers['Content-Length'] = cString::getStringLength($this->body);
    }

    /**
     * Send the request to the server.
     *
     * @param bool $return Weather the function should return the servers response
     * @param string $method GET or PUT
     * @param bool $returnHeaders Weather the headers should be included in the response
     * @return string|bool
     */
    protected function sendRequest(bool $return, string $method, bool $returnHeaders = false)
    {
        if (!(cString::findFirstPos($this->url, 'http') === 0)) {
            $this->url = 'http://' . $this->url;
        }

        $urlInfo = @parse_url($this->url);
        $scheme = '';
        if (empty($urlInfo['port'])) {
            if ($urlInfo['scheme'] == 'https') {
                $urlInfo['port'] = 443;
                $scheme = 'ssl://';
            } else {
                $urlInfo['port'] = 80;
            }
        }

        $this->headers['Host'] = !empty($this->headers['Host']) ? $this->headers['Host'] : $urlInfo['host'];
        $this->headers['Connection'] = !empty($this->headers['Connection']) ? $this->headers['Host'] : 'close';
        $this->headers['Accept'] = !empty($this->headers['Accept']) ? $this->headers['Host'] : '*/*';

        $this->prepareHeaders();

        $handle = @fsockopen($scheme . $urlInfo['host'], $urlInfo['port']);
        if (!$handle) {
            return false;
        }

        $request = $method . ' ';
        $request .= $urlInfo['path'] . '?' . $urlInfo['query'] . ' HTTP/1.1' . "\r\n";
        $request .= $this->header . "\r\n";
        $request .= $this->body;

        fwrite($handle, $request);

        $ret = '';
        while (!feof($handle)) {
            $ret .= fgets($handle);
        }

        fclose($handle);

        if ($return) {
            if (!$returnHeaders) {
                $ret = cString::getPartOfString(cString::strstr($ret, "\r\n\r\n"), cString::getStringLength("\r\n\r\n"));
            }
            return $ret;
        } else {
            return cString::findFirstPos(cString::strstr($ret, '\r\n', true), '200') !== false;
        }
    }

    /**
     * @inheritDoc
     */
    public function postRequest(bool $return = true, bool $returnHeaders = false)
    {
        $this->preparePostRequest();

        return $this->sendRequest($return, 'POST', $returnHeaders);
    }

    /**
     * @inheritDoc
     */
    public function getRequest(bool $return = true, bool $returnHeaders = false)
    {
        $this->prepareGetRequest();

        return $this->sendRequest($return, 'GET', $returnHeaders);
    }

    /**
     * @inheritDoc
     */
    public function request(bool $return = true, bool $returnHeaders = false)
    {
        $this->prepareGetRequest();
        $this->preparePostRequest();

        return $this->sendRequest($return, 'POST', $returnHeaders);
    }
}
