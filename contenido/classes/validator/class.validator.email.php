<?php

/**
 * This file contains the mail validator class.
 *
 * @package    Core
 * @subpackage Validation
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
class cValidatorEmail extends cValidatorAbstract
{
    /**
     * Constructor to create an instance of this class.
     *
     * Sets some predefined options.
     */
    public function __construct()
    {
        // Some default options to exclude tld or host
        // RFC 2606 filter (<http://tools.ietf.org/html/rfc2606>)
        $this->setOption('disallow_tld', ['.test', '.example', '.invalid', '.localhost']);
        $this->setOption('disallow_host', ['example.com', 'example.org', 'example.net']);
        $this->setOption('mx_check', false);
    }

    /**
     * @see cValidatorAbstract::_isValid()
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function _isValid($value)
    {
        $filteredValue = filter_var($value, FILTER_VALIDATE_EMAIL);

        if (false === $filteredValue) {
            $this->addError('Invalid email', 4);

            return false;
        }

        $host = cString::getPartOfString($value, cString::findFirstPos($value, '@') + 1);
        // $tld  = cString::findLastOccurrence($value, '.');
        $parts = explode('.', $value);
        $tld = '.' . end($parts);

        // check for disallowed TLDs (Top Level Domains)
        if ($this->getOption('disallow_tld') && in_array($tld, $this->getOption('disallow_tld'))) {
            $this->addError('Disallowed top level domain', 2);

            return false;
        }

        // check for disallowed Second Level Domain Names
        if ($this->getOption('disallow_host') && in_array($host, $this->getOption('disallow_host'))) {
            $this->addError('Disallowed host name', 3);

            return false;
        }

        // check DNS for MX type
        if ($this->getOption('mx_check') && function_exists('checkdnsrr') && !checkdnsrr($host, 'MX')) {
            $this->addError('MX check failed', 5);

            return false;
        }

        return true;
    }
}
