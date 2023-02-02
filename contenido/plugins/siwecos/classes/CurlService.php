<?php
declare(strict_types=1);

/**
 * Class CurlHelper.
 *
 *
 * @link http://php.net/manual/de/function.curl-setopt.php
 * @link https://support.ladesk.com/061754-How-to-make-REST-calls-in-PHP
 * @link https://stackoverflow.com/questions/9802788/call-a-rest-api-in-php
 */
class CurlService
{
    const DBG = false;

    public $error;

    /**
     * @param string $url
     * @param array  $data
     * @param array  $header
     *
     * @return \stdClass
     * @throws CurlException
     */
    public function post(string $url, array $data, array $header = [])
    {
        // define base options
        $options = [
            CURLOPT_POST => true,
            CURLOPT_URL  => $url,
        ];

        // add optional POST data
        if (!empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        $options = $this->_getCurlOptions($options, $header);

        $result = $this->_call($options);
        return is_bool($result) ? new stdClass() : json_decode($result);
    }

    /**
     * @param string $url
     * @param array  $header
     *
     * @return \stdClass
     * @throws CurlException
     */
    public function get(string $url, array $header = [])
    {
        // define base options
        $options = [
            CURLOPT_POST => false,
            CURLOPT_URL  => $url,
        ];

        $options = $this->_getCurlOptions($options, $header);

        $result = $this->_call($options);
        return is_bool($result) ? new stdClass() : json_decode($result);
    }

    private function _getCurlOptions(array $options, array $header = [])
    {
        $curlOptions = [
            //CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_VERBOSE        => true,
            CURLOPT_FILETIME       => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        if (self::DBG) {
            $additional = [
                CURLOPT_HEADER         => true,
                CURLINFO_HEADER_OUT    => true,
                CURLOPT_VERBOSE        => true,
                CURLOPT_FILETIME       => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ];
            $curlOptions = array_merge($curlOptions, $additional);
        }

        // add optional headers
        if (!empty($header)) {
            $curlOptions[CURLOPT_HTTPHEADER] = $header;
        }

        // Return with custom options
        return array_merge($curlOptions, $options);
    }


    /**
     * @param array $options
     *
     * @return string|bool
     * @throws CurlException
     */
    private function _call(array $options)
    {
        // init curl
        $curl = curl_init();
        if (false === $curl) {
            throw new CurlException('failed to init curl');
        }

        // set options
        $success = curl_setopt_array($curl, $options);
        if (false === $success) {
            throw new CurlException('failed to set curl options');
        }

        // execute call
        $resp = curl_exec($curl);
        // get info
        $info = curl_getinfo($curl);

        // close curl
        curl_close($curl);

        // check errors
        $this->error = [
            'resp' => $resp,
            'info' => $info,
        ];
        if (self::DBG) {
            error_log($info);
        }

        return $resp;
    }
}

class CurlException extends cException
{
}