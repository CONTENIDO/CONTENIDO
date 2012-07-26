<?php
/**
 * CONTENIDO code generator factory
 *
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 * @id SVN Id $Id$
 *
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * CONTENIDO code generator factory.
 * @package Core
 * @subpackage Content Type
 */
class cCodeGeneratorFactory {

    /**
     * Returns code generator instance by it's name.
     *
     * @param  string  $name  The generator name, e. g. 'Standard' to retrieve instance of
     *                        cCodeGeneratorStandard
     * @return cCodeGeneratorAbstract
     * @throws InvalidArgumentException  If name is invalid, class file is missing or
     *                                   class isn't available
     */
    public static function getInstance($name = '') {
        global $cfg;

        if ($name == '') {
            $name = $cfg['code_generator']['name'];
        }

        if ($name == 'Factory' || $name == 'Abstract') {
            throw new InvalidArgumentException('Invalid name passed to cCodeGeneratorFactory: ' . $name . '!');
        }

        $className = 'cCodeGenerator' . $name;
        if (!class_exists($className)) {
            $fileName = $name . '.class.php';
            $path = str_replace('\\', '/', dirname(__FILE__)) . '/';
            if (!cFileHandler::exists($path . $fileName)) {
                throw new InvalidArgumentException('The classfile couldn\'t included by cCodeGeneratorFactory: ' . $name . '!');
            }

            include_once($path . $fileName);
            if (!class_exists($className)) {
                throw new InvalidArgumentException('The class isn\'t available for cCodeGeneratorFactory: ' . $name . '!');
            }
        }
        return new $className();
    }

}
