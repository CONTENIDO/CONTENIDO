<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO code generator factory
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Classes
 * @version    0.0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-08-11
 *
 *   $Id$:
 * }}
 *
 */


if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * CONTENIDO code generator factory.
 * @package    CONTENIDO Backend Classes
 */
class cCodeGeneratorFactory
{
    /**
     * Returns code generator instance by it's name.
     *
     * @param  string  $name  The generator name, e. g. 'Standard' to retrieve instance of
     *                        Contenido_CodeGenerator_Standard
     * @return cCodeGeneratorAbstract
     * @throws InvalidArgumentException  If name is invalid, class file is missing or
     *                                   class isn't available
     */
    public static function getInstance($name = '')
    {
        global $cfg;

        if ($name == '') {
            $name = $cfg['code_generator']['name'];
        }

        if ($name == 'Factory' || $name == 'Abstract') {
            throw new InvalidArgumentException('Invalid name passed to cCodeGeneratorFactory: '.$name.'!');
        }

        $className = 'Contenido_CodeGenerator_' . $name;
        if (!class_exists($className)) {
            $fileName = $name . '.class.php';
            $path     = str_replace('\\', '/', dirname(__FILE__)) . '/';
            if (!cFileHandler::exists($path . $fileName)) {
                throw new InvalidArgumentException('The classfile couldn\'t included by cCodeGeneratorFactory: '.$name.'!');
            }

            include_once($path . $fileName);
            if (!class_exists($className)) {
                throw new InvalidArgumentException('The class isn\'t available for cCodeGeneratorFactory: '.$name.'!');
            }
        }
        return new $className();
    }
}
