<?php
/**
 * This file contains the validator factory class.
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
 * Validator factory
 *
 * @package    Core
 * @subpackage Validation
 */
class cValidatorFactory {

    /**
     * Instantiates and returns the validator. Sets also validators default options.
     *
     * Each validator can be configured through CONTENIDO $cfg configuration variable.
     * Example for email validator:
     * <pre>
     * $cfg['validator']['email'] = array(
     *     // List of top level domains to disallow
     *     'disallow_tld' => array('.test', '.example', '.invalid', '.localhost'),
     *     // List of hosts to disallow
     *     'disallow_host' => array('example.com', 'example.org', 'example.net'),
     *     // Flag to check DNS records for MX type
     *     'mx_check' => false,
     * );
     * </pre>
     *
     * @param   string $validator  Validator to get
     * @param   array  $options  Options to use for the validator. Any passed option
     *                           overwrites the related option in global validator configuration.
     * @throws cInvalidArgumentException If type of validator is unknown or not available or if someone
     *                                   tries to get cValidatorFactory instance.
     * @return  cValidatorAbstract
     */
    public static function getInstance($validator, array $options = array()) {
        global $cfg;

        $name = strtolower($validator);
        $className = 'cValidator' . ucfirst($name);

        if ('factory' === $name) {
            throw new cInvalidArgumentException("Can't use validator factory '{$validator}' as validator!");
        }

        if (class_exists($className)) {
            $obj = new $className();
        } else {
            // Try to load validator class file (in this folder)
            $path = str_replace('\\', '/', dirname(__FILE__)) . '/';
            $fileName = sprintf('class.validator.%s.php', $name);
            if (!cFileHandler::exists($path . $fileName)) {
                throw new cInvalidArgumentException("The file '{$fileName}' for validator '{$validator}' couldn't included by cValidatorFactory!");
            }

            // Try to instantiate the class
            require_once($path . $fileName);
            if (!class_exists($className)) {
                throw new cInvalidArgumentException("Missing validator class '{$className}' for validator '{$validator}' !");
            }
            $obj = new $className();
        }

        // Merge passed options with global configured options.
        if (isset($cfg['validator']) && isset($cfg['validator'][$name]) && is_array($cfg['validator'][$name])) {
            $options = array_merge($cfg['validator'][$name], $options);
        }
        $obj->setOptions($options);

        return new $obj;
    }

}
