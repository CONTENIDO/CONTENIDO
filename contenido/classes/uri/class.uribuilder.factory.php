<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Factory for retrieving required cUriBuilder object
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.1.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cUriBuilderFactory {

    /**
     * Returns desired UriBuilder object.
     *
     * @param string $sBuilder For now, those are valid: front_content, custom,
     *            custom_path or a
     *        Userdefined UriBuilder name. The name must be a subpart of the
     *        UriBuilder class, e. g. 'MyUriBuilder' for
     *            cUriBuilderMyUriBuilder.
     *        The classfile must be named like class.uribuilder.myuribuilder.php
     *        and it must be reside in /contenido/classes/uri/ folder.
     * @return cUriBuilder
     * @throws cInvalidArgumentException In case unknown type of builder is
     *         requested you'll get an Exception
     */
    public static function getUriBuilder($sBuilder) {
        switch ($sBuilder) {
            case 'front_content':
                return cUriBuilderFrontcontent::getInstance();
                break;
            case 'custom':
                return cUriBuilderCustom::getInstance();
                break;
            case 'custom_path':
                return cUriBuilderCustomPath::getInstance();
                break;
            default:
                if ((string) $sBuilder !== '') {
                    $sClassName = 'cUriBuilder' . $sBuilder;
                    $sFileName = 'class.uribuilder.' . strtolower($sBuilder) . '.php';
                    $sPath = str_replace('\\', '/', dirname(__FILE__)) . '/';
                    if (!cFileHandler::exists($sPath . $sFileName)) {
                        throw new cInvalidArgumentException('The classfile of cUriBuilder couldn\'t included by Contenido_UriBuilderFactory: ' . $sBuilder . '!');
                    }
                    cInclude('classes', 'uri/' . $sFileName);
                    if (!class_exists($sClassName)) {
                        throw new cInvalidArgumentException('The classfile of cUriBuilder couldn\'t included by Contenido_UriBuilderFactory: ' . $sBuilder . '!');
                    }
                    return call_user_func(array($sClassName, 'getInstance'));
                }

                throw new cInvalidArgumentException('Invalid/Empty cUriBuilder passed to cUriBuilderFactory: ' . $sBuilder . '!');
                break;
        }
    }

}
