<?php

/**
 * This file contains the date validator class.
 *
 * @package    Core
 * @subpackage Validation
 * @author     Viktor Lehmann <info@tone2tone.com>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
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
     * @see cValidatorAbstract::_isValid()
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function _isValid($date)
    {
        $format = "Y-m-d H:i:s";
        // if $date is just a date, not datetime, simulate datetime, as it is only necessary to check validity
        if (strlen($date) == 10)  $date = $date . " 00:00:00";

        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) == $date) {
            return true;
        } else {
            $this->addError('Invalid date', 1);
            return false;
        }
        
    }
  
}
