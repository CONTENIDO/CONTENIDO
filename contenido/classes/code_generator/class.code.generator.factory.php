<?php

/**
 * CONTENIDO code generator factory
 *
 * @package    Core
 * @subpackage ContentType
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * CONTENIDO code generator factory.
 *
 * @package    Core
 * @subpackage ContentType
 */
class cCodeGeneratorFactory
{

    /**
     * Returns code generator instance by its name.
     *
     * @param string $name [optional] The generator name, e.g. 'Standard' to retrieve instance of
     *      cCodeGeneratorStandard.
     * @return cCodeGeneratorAbstract|cCodeGeneratorStandard|object
     * @throws cInvalidArgumentException If name is invalid, class file is missing or class isn't available.
     */
    public static function getInstance(string $name = '')
    {
        if ($name == '') {
            $cfg = cRegistry::getConfig();
            $name = $cfg['code_generator']['name'];
        }

        // `cCodeGeneratorFactory` and `cCodeGeneratorAbstract` are not allowed!
        if ($name === 'Factory' || $name === 'Abstract') {
            throw new cInvalidArgumentException(sprintf('%s: Invalid name "%s"!', __CLASS__, $name));
        }

        $className = 'cCodeGenerator' . $name;
        if (!class_exists($className)) {
            $fileName = $name . '.class.php';
            $path = str_replace('\\', '/', __DIR__ ) . '/';
            if (!cFileHandler::exists($path . $fileName)) {
                throw new cInvalidArgumentException(sprintf('%s: Could not include file for class "%s"!', __CLASS__, $fileName));
            }

            include_once($path . $fileName);
            if (!class_exists($className)) {
                throw new cInvalidArgumentException(sprintf('%s: Class "%s" does not exists!', __CLASS__, $className));
            }
        }

        return new $className();
    }

}
