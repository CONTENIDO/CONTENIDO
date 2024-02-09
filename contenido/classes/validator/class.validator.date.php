<?php

/**
 * This file contains the date validator class.
 *
 * @package    Core
 * @subpackage Validation
 * @author     Viktor Lehmann <info@tone2tone.com>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Date validation.
 *
 *
 * Usage:
 * <pre>
 * $validator = cValidatorFactory::getInstance('date');
 * if ($validator->isValid('2021-10-08 00:00:00')) {
 *     // date is valid, not NULL, not 0000-00-00 00:00:00
 *    // can be Y-m-d too, needs no time stamp
 * } else {
 *     $errors = $validator->getErrors();
 *     foreach ($errors as $pos => $errItem) {
 *         echo $errItem->code . ": " . $errItem->message . "\n";
 *     }
 * }
 * </pre>
 *
 * @since      CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Validation
 */
class cValidatorDate extends cValidatorAbstract
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
            $this->addError('Parameter must be string and not empty', 2);
            return false;
        }

        $format = "Y-m-d H:i:s";
        // if $date is just a date, not datetime, simulate datetime, as it is only necessary to check validity
        if (strlen($value) == 10) $value = $value . " 00:00:00";

        $d = DateTime::createFromFormat($format, $value);
        if ($d && $d->format($format) == $value) {
            return true;
        } else {
            $this->addError('Invalid date', 1);
            return false;
        }
    }

}
