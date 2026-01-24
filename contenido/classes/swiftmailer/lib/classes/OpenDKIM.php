<?php

/**
 * Stub classes for php-opendkim PECL extension
 * This file provides type hints for the OpenDKIM PECL extension classes
 * Only loaded if the PECL extension is not available
 * 
 * @see https://github.com/xdecock/php-opendkim
 */

if (!extension_loaded('opendkim')) {
    /**
     * OpenDKIM class from PECL extension
     * Provides static methods for DKIM operations
     */
    class OpenDKIM
    {
        public const OPTS_FIXEDTIME = 1;

        /**
         * Set OpenDKIM options
         *
         * @param int $option The option to set
         * @param mixed $value The value to set
         *
         * @return bool
         */
        public static function setOption($option, $value)
        {
        }

        /**
         * Get OpenDKIM option
         *
         * @param int $option The option to get
         *
         * @return mixed
         */
        public static function getOption($option)
        {
        }
    }

    /**
     * OpenDKIMSign class from PECL extension
     * Handles DKIM signing operations
     */
    class OpenDKIMSign
    {
        public const ALG_RSASHA1 = 1;
        public const ALG_RSASHA256 = 2;
        public const CANON_SIMPLE = 1;
        public const CANON_RELAXED = 2;

        /**
         * Constructor
         *
         * @param string $privateKey The private key
         * @param string $selector The DKIM selector
         * @param string $domain The domain name
         * @param int $headerCanon Header canonicalization method
         * @param int $bodyCanon Body canonicalization method
         * @param int $hashAlgorithm Hash algorithm
         * @param int $bodyLength Length of the body to sign (-1 for unlimited)
         */
        public function __construct($privateKey, $selector, $domain, $headerCanon, $bodyCanon, $hashAlgorithm, $bodyLength = -1)
        {
        }

        /**
         * Set the signature margin (line length)
         *
         * @param int $margin The margin in characters
         *
         * @return bool
         */
        public function setMargin($margin)
        {
        }

        /**
         * Set the signer identity
         *
         * @param string $identity The signer identity
         *
         * @return bool
         */
        public function setSigner($identity)
        {
        }

        /**
         * Add a header to sign
         *
         * @param string $header The header line to sign
         *
         * @return bool
         */
        public function header($header)
        {
        }

        /**
         * Signal end of headers
         *
         * @return bool
         */
        public function eoh()
        {
        }

        /**
         * Add body data to sign
         *
         * @param string $data The body data
         *
         * @return bool
         */
        public function body($data)
        {
        }

        /**
         * Signal end of message
         *
         * @return bool
         */
        public function eom()
        {
        }

        /**
         * Get the signature header
         *
         * @return string|false The signature header or false on error
         */
        public function getSignatureHeader()
        {
        }

        /**
         * Get the last error message
         *
         * @return string
         */
        public function getError()
        {
        }
    }
}
