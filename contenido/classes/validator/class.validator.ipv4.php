<?php

/**
 * This file contains the IPv4 address validator class.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Validation
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * IPv4 address validation.
 *
 * Usage:
 * <pre>
 * $validator = cValidatorFactory::getInstance('ipv4');
 * if ($validator->isValid('127.0.0.1')) {
 *     // IPv4 address is valid
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
class cValidatorIpv4 extends cValidatorAbstract
{

    /**
     * Constructor to create an instance of this class.
     *
     * Sets some predefined options.
     */
    public function __construct()
    {
    }

    /**
     * @param mixed $value
     *
     * @return bool
     * @see cValidatorAbstract::_isValid()
     *
     */
    protected function _isValid($value): bool
    {
        if (!is_string($value) || empty($value)) {
            $this->addError('Parameter must be string and not empty', 1);
            return false;
        }

        // ip pattern needed for validation
        $ipPattern = '([0-9]|1?\d\d|2[0-4]\d|25[0-5])';
        if (preg_match("/^$ipPattern\.$ipPattern\.$ipPattern\.$ipPattern?$/", $value)) {
            // IP is valid
            return true;
        } else {
            $this->addError('Invalid IPv4 address', 2);
            return false;
        }
    }

}
