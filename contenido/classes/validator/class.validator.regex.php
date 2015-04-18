<?php
/**
 * This file contains the regular expression validator class.
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
 * Regular expression validation.
 *
 * Supports following options:
 * <pre>
 * - pattern  (string)  The regular expression pattern
 * </pre>
 *
 * @package    Core
 * @subpackage Validation
 */
class cValidatorRegex extends cValidatorAbstract {

    /**
     *
     * @see cValidatorAbstract::_isValid()
     * @param   mixed  $value
     * @return  bool
     */
    protected function _isValid($value) {
        if (!is_string($value)) {
            $this->addError('Invalid value', 1);
            return false;
        } elseif (!$this->getOption('pattern')) {
            $this->addError('Missing pattern', 2);
            return false;
        }

        $status = @preg_match($this->getOption('pattern'), $value);
        if (false === $status) {
            $this->addError('Pattern error', 3);
            return false;
        }

        if (!$status) {
            $this->addError('No match', 4);
            return false;
        }

        return true;
    }

}
