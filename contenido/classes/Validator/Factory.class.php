<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO validator factory class
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
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Validator factory
 * @package    CONTENIDO Validator
 */
class Contenido_Validator_Factory
{

    /**
     * Instantiates and returns the validator. Sets also validators default options.
     *
     * Each validator can be configured thru CONTENIDO $cfg configuration variable.
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
     * @return  Contenido_Validator_Abstract
     * @throws  InvalidArgumentException If type of validator is unknown or not available
     */
    public static function getInstance($validator, array $options = array())
    {
        global $cfg;

        $className = 'Contenido_Validator_' . ucfirst(strtolower($validator));
        $fileName = ucfirst(strtolower($validator)) . '.class.php';
        if (class_exists($className)) {
            $obj = new $className();
        } else {
            $path = str_replace('\\', '/', dirname(__FILE__)) . '/';
            if (!file_exists($path . $fileName)) { 
                throw new InvalidArgumentException("The class file of Contenido_Validator couldn't included by Contenido_Validator_Factory: " . $validator . "!"); 
            }

            require_once($path . $fileName);
            if (!class_exists($className)) {
                throw new InvalidArgumentException("The class of Contenido_Validator couldn't included by Contenido_UrlBuilderFactory: " . $validator . "!");
            }

            $obj = new $className();
        }

        $cfgName = strtolower($validator);

        // Merge passsed options with global configured options.
        if (isset($cfg['validator']) && isset($cfg['validator'][$cfgName]) && is_array($cfg['validator'][$cfgName])) {
            $options = array_merge($cfg['validator'][$cfgName], $options);
        }
        $obj->setOptions($options);

        return new $obj;
    }

}
