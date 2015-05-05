<?php
/**
 * CONTENIDO code generator factory
 *
 * @package    Core
 * @subpackage ContentType
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
 * CONTENIDO code generator factory.
 * @package    Core
 * @subpackage ContentType
 */
class cCodeGeneratorFactory {

    /**
     * Returns code generator instance by it's name.
     *
     * @param  string $name
     *         The generator name, e. g. 'Standard' to retrieve instance
     *         of cCodeGeneratorStandard
     * @throws cInvalidArgumentException
     *         If name is invalid, class file is missing or class isn't available
     * @return cCodeGeneratorAbstract
     */
    public static function getInstance($name = '') {
        global $cfg;

        if ($name == '') {
            $name = $cfg['code_generator']['name'];
        }

        if ($name == 'Factory' || $name == 'Abstract') {
            throw new cInvalidArgumentException('Invalid name passed to cCodeGeneratorFactory: ' . $name . '!');
        }

        $className = 'cCodeGenerator' . $name;
        if (!class_exists($className)) {
            $fileName = $name . '.class.php';
            $path = str_replace('\\', '/', dirname(__FILE__)) . '/';
            if (!cFileHandler::exists($path . $fileName)) {
                throw new cInvalidArgumentException('The classfile couldn\'t included by cCodeGeneratorFactory: ' . $name . '!');
            }

            include_once($path . $fileName);
            if (!class_exists($className)) {
                throw new cInvalidArgumentException('The class isn\'t available for cCodeGeneratorFactory: ' . $name . '!');
            }
        }

        return new $className();
    }

}
