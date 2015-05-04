<?php

/**
 * This file contains the mail validator class.
 *
 * @package    Core
 * @subpackage Validation
 * @version    SVN Revision $Rev:$
 *
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * E-Mail validation.
 *
 * Supports following options:
 * <pre>
 * - disallow_tld   (array)  Optional, list of top level domains to disallow
 * - disallow_host  (array)  Optional, list of hosts to disallow
 * - mx_check       (bool)   Optional, flag to check DNS records for MX type
 * </pre>
 *
 * Usage:
 * <pre>
 * $validator = cValidatorFactory::getInstance('email');
 * if ($validator->isValid('user@contenido.org')) {
 *     // email is valid
 * } else {
 *     $errors = $validator->getErrors();
 *     foreach ($errors as $pos => $errItem) {
 *         echo $errItem->code . ": " . $errItem->message . "\n";
 *     }
 * }
 * </pre>
 *
 * @package    Core
 * @subpackage Validation
 */
class cValidatorEmail extends cValidatorAbstract {

    /**
     * Flag about existing flter_var function
     * @var  bool
     */
    protected static $_filterVarExists;

    /**
     * Constructor function, sets some predefined options
     */
    public function __construct() {
        // Some default options to exclude tld or host
        // RFC 2606 filter (<http://tools.ietf.org/html/rfc2606>)
        $this->setOption('disallow_tld', array('.test', '.example', '.invalid', '.localhost'));
        $this->setOption('disallow_host', array('example.com', 'example.org', 'example.net'));
    }

    /**
     * Filter variable function exists setter
     * @param bool $exists
     */
    public static function setFilterVarExists($exists) {
        self::$_filterVarExists = (bool) $exists;
    }

    /**
     * Unsets filter variable function state
     */
    public static function resetFilterVarExists() {
        unset(self::$_filterVarExists);
    }

    /**
     *
     * @see cValidatorAbstract::_isValid()
     * @param mixed $value
     * @return bool
     */
    protected function _isValid($value) {
        if (!is_string($value) || empty($value)) {
            $this->addError('Invalid or empty value', 1);
            return false;
        }

        $host = substr($value, strpos($value, '@') + 1);
        $tld = strrchr($value, '.');

        // do RFC 2606 or user defined filter if configured
        if ($this->getOption('disallow_tld')) {
            if (in_array($tld, $this->getOption('disallow_tld'))) {
                $this->addError('Disallowed top level domain', 2);
                return false;
            }
        }
        if ($this->getOption('disallow_host')) {
            if (in_array($host, $this->getOption('disallow_host'))) {
                $this->addError('Disallowed host name', 3);
                return false;
            }
        }

        if (!isset(self::$_filterVarExists)) {
            self::setFilterVarExists(function_exists('filter_var'));
        }

        // Use native filter_var function, if exists
        if (self::$_filterVarExists) {
            $isValid = (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
        } else {
            // Fallback for not existing filter_var function
            // Taken over from PHP trunk ext/filter/logical_filters.c
            /*
             * The regex below is based on a regex by Michael Rushton.
             * However, it is not identical.  I changed it to only consider routeable
             * addresses as valid.  Michael's regex considers a@b a valid address
             * which conflicts with section 2.3.5 of RFC 5321 which states that:
             *
             *   Only resolvable, fully-qualified domain names (FQDNs) are permitted
             *   when domain names are used in SMTP.  In other words, names that can
             *   be resolved to MX RRs or address (i.e., A or AAAA) RRs (as discussed
             *   in Section 5) are permitted, as are CNAME RRs whose targets can be
             *   resolved, in turn, to MX or address RRs.  Local nicknames or
             *   unqualified names MUST NOT be used.
             *
             * This regex does not handle comments and folding whitespace.  While
             * this is technically valid in an email address, these parts aren't
             * actually part of the address itself.
             *
             * Michael's regex carries this copyright:
             *
             * Copyright � Michael Rushton 2009-10
             * http://squiloople.com/
             * Feel free to use and redistribute this code. But please keep this copyright notice.
             *
             */
            $regexp = "/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD";
            $isValid = (bool) preg_match($regexp, $value);
        }
        if (!$isValid) {
            $this->addError('Invalid email', 4);
            return false;
        }


        // Do DNS check for MX type
        if ($this->getOption('mx_check') && function_exists('checkdnsrr')) {
            $isValid = $this->_checkMx($host);
            if (!$isValid) {
                $this->addError('MX check failed', 5);
                return false;
            }
        }

        return true;
    }

    /**
     * Check DNS Records for MX type.
     *
     * @param string $host
     *         Host name
     * @return bool
     */
    private function _checkMx($host) {
        return checkdnsrr($host, 'MX');
    }

}
