<?php
/**
 * This file contains the validator factory class.
 *
 * @package    Core
 * @subpackage Validation
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
     * @throws cInvalidArgumentException If type of validator is unknown or not available
     * @return  cValidatorAbstract
     */
    public static function getInstance($validator, array $options = array()) {
        global $cfg;

        $className = 'cValidator' . ucfirst(strtolower($validator));
        $fileName = ucfirst(strtolower($validator)) . '.class.php';
        if (class_exists($className)) {
            $obj = new $className();
        } else {
            $path = str_replace('\\', '/', dirname(__FILE__)) . '/';
            if (!cFileHandler::exists($path . $fileName)) {
                throw new cInvalidArgumentException("The class file of Contenido_Validator couldn't included by cValidatorFactory: " . $validator . "!");
            }

            require_once($path . $fileName);
            if (!class_exists($className)) {
                throw new cInvalidArgumentException("The class of Contenido_Validator couldn't included by uriBuilderFactory: " . $validator . "!");
            }

            $obj = new $className();
        }

        $cfgName = strtolower($validator);

        // Merge passed options with global configured options.
        if (isset($cfg['validator']) && isset($cfg['validator'][$cfgName]) && is_array($cfg['validator'][$cfgName])) {
            $options = array_merge($cfg['validator'][$cfgName], $options);
        }
        $obj->setOptions($options);

        return new $obj;
    }

}
