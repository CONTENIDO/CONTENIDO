<?php
/**
 * This file contains an implementation of HttpRequest using fsockopen
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
 * fsockopen implementation of HttpRequest
 *
 * @package Core
 * @subpackage Core
 */
class cHttpRequestSocket extends cHttpRequest {

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
     * Boundary for the multipart from-data
     * 
     * @var string
     */
    protected $boundary;
    
    /**
     * The HTTP header
     * 
     * @var string
     */
    protected $header;
    
    /**
     * The HTTP body
     * 
     * @var string
     */
    protected $body;

    /**
     * Basic constructor
     *
     * @param string $url URL for the request
     * @see cHttpRequest::__construct()
     * @see cHttpRequest::getHttpRequest()
     */
    public function __construct($url = '') {
        $this->url = $url;
    }
    
    /**
     * @see cHttpRequest::setURL()
     */
    public function setURL($url) {
        $this->url = $url;
        
        return $this;
    }

    /**
     * @see cHttpRequest::setGetParams()
     */
    public function setGetParams($array) {
        $this->getArray = $array;
        
        return $this;
    }

    /**
     * @see cHttpRequest::setPostParams()
     */
    public function setPostParams($array) {
        $this->postArray = $array;
        
        return $this;
    }

    /**
     * @see cHttpRequest::setHeaders()
     */
    public function setHeaders($array) {
        $this->headerArray = $array;
        
        return $this;
    }

    /**
     * Inserts the custom headers into the header string
     */
    protected function prepareHeaders() {
        $this->header = '';
        if(!is_array($this->headerArray)) {
            return;
        }
        foreach($this->headerArray as $key => $value) {
            $headerString = '';
            if(is_array($value)) {
                $headerString .= $value[0] . ': ' . $value[1];
            } else {
                $headerString .= $key . ': ' . $value;
            }
            $this->header .= $headerString . "\r\n";
        }
    }

    /**
     * Appends teh GET array to the URL
     */
    protected function prepareGetRequest() {
        if(is_array($this->getArray)) {
            if(!cString::contains($this->url, '?')) {
                $this->url .= '?';
            } else {
                $this->url .= '&';
            }
            foreach($this->getArray as $key => $value) {
                $this->url .= urlencode($key) . '=' . urlencode($value) . '&';
            }
            $this->url = substr($this->url, 0, strlen($this->url) - 1);
        }
    }

    /**
     * Prepares the headers to send a POST request and encodes the data
     */
    protected function preparePostRequest() {
        $this->boundary = md5(time()) . md5(time() * rand());
        $this->headerArray['Content-Type'] = 'multipart/form-data; boundary=' . $this->boundary;
        $this->boundary = '--' . $this->boundary;
        
        $this->body = $this->boundary . "\r\n";
        foreach($this->postArray as $key => $value) {
            $this->body .= 'Content-Disposition: form-data; name="' . $key . "\"\r\n\r\n";
            $this->body .= $value . "\r\n";
            $this->body .= $this->boundary . "\r\n";
        }
        $this->headerArray['Content-Length'] = strlen($this->body);
    }

    /**
     * Send the request to the server
     *
     * @param bool $return Wether the function should return the servers response
     * @param string $method GET or PUT
     * @param bool $returnHeaders Wether the headers should be included in the response
     * @return string|boolean
     */
    protected function sendRequest($return, $method, $returnHeaders = false) {
        if(!(strpos($this->url, 'http') === 0)) {
            $this->url = 'http://' . $this->url;
        }
        
        $urlInfo = @parse_url($this->url);
        $scheme = '';
        if($urlInfo['port'] == '') {
            if($urlInfo['scheme'] == 'https') {
                $urlInfo['port'] = 443;
                $scheme = 'ssl://';
            } else {
                $urlInfo['port'] = 80;
            }
        }
        
        $this->headerArray['Host'] = ($this->headerArray['Host'] != '') ? $this->headerArray['Host'] : $urlInfo['host'];
        $this->headerArray['Connection'] = ($this->headerArray['Connection'] != '') ? $this->headerArray['Host'] : 'close';
        $this->headerArray['Accept'] = ($this->headerArray['Accept'] != '') ? $this->headerArray['Host'] : '*/*';

        $this->prepareHeaders();
        
        $handle = @fsockopen($scheme . $urlInfo['host'], $urlInfo['port']);
        if(!$handle) {
            return false;
        }

        $request = $method . ' ';
        $request .= $urlInfo['path'] . '?' . $urlInfo['query'] . ' HTTP/1.1' . "\r\n";
        $request .= $this->header . "\r\n";
        $request .= $this->body;
        
        fwrite($handle, $request);
        
        $ret = '';
        while(!feof($handle)) {
            $ret .= fgets($handle);
        }
        
        fclose($handle);
        
        if($return) {
            if(!$returnHeaders) {
                $ret = substr(strstr($ret, "\r\n\r\n"), strlen("\r\n\r\n"));
            }
            return $ret;
        } else {
            return strpos(strstr($ret, '\r\n', true), '200') !== false;
        }
    }

    /**
     * @see cHttpRequest::postRequest()
     */
    public function postRequest($return = true, $returnHeaders = false) {
        $this->preparePostRequest();
        
        return $this->sendRequest($return, 'POST', $returnHeaders);
    }

    /**
     * @see cHttpRequest::getRequest()
     */
    public function getRequest($return = true, $returnHeaders = false) {
        $this->prepareGetRequest();
        
        return $this->sendRequest($return, 'GET', $returnHeaders);
    }

    /**
     * @see cHttpRequest::request()
     */
    public function request($return = true, $returnHeaders = false) {
        $this->prepareGetRequest();
        $this->preparePostRequest();
        
        return $this->sendRequest($return, 'POST', $returnHeaders);
    }
}

?>
