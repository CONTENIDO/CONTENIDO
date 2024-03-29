<?php

/**
 * This file contains the abstract validator class.
 *
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
 * Abstract validator.
 *
 * @package    Core
 * @subpackage Validation
 */
abstract class cValidatorAbstract
{

    /**
     * List of options, depends on used validator
     *
     * @var array
     */
    protected $_options = [];

    /**
     * List of validations errors
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * Options setter, merges passed options with previous set options.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Single option setter.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setOption(string $name, $value)
    {
        $this->_options[$name] = $value;
    }

    /**
     * Option getter.
     *
     * @param string $name
     * @return mixed|NULL
     */
    public function getOption(string $name)
    {
        return $this->_options[$name] ?? NULL;
    }

    /**
     * Returns list of validations errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Adds a error.
     *
     * @param string $message
     * @param mixed $code
     */
    protected function addError(string $message, $code)
    {
        $this->_errors[] = (object)['message' => $message, 'code' => $code];
    }

    /**
     * Validates the passed value.
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return $this->_isValid($value);
    }

    /**
     * Abstract isValid method, which has to be implemented by children
     *
     * @param mixed $value
     * @return bool
     */
    abstract protected function _isValid($value): bool;

}
