<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO regular expression validator class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Validator
 * @version    0.0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created 2011-11-18
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Regular expression validation.
 *
 * Supports following options:
 * <pre>
 * - pattern  (string)  The regular expression pattern
 * </pre>
 *
 * @package    CONTENIDO Validator
 */
class cValidatorRegex extends cValidatorAbstract {

    /**
     * {@inheritdoc}
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
