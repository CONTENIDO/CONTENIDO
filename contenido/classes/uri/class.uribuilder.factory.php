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
 * @package    CONTENIDO Backend Classes
 * @version    1.1.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2008-02-18
 *   modified 2008-09-29, Murat Purc, add instantiation of userdefined UriBuilder
 *   modified 2008-12-22, Murat Purc, fixed file exists check of userdefined UriBuilder
 *   modified 2009-01-01, Murat Purc, changed call of call_user_func to support php previous to 5.2.3
 *
 *   $Id: class.uriBuilder.Factory.php 2755 2012-07-25 20:10:28Z xmurrix $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cUriBuilderFactory
{
    /**
     * Returns desired UriBuilder object.
     *
     * @param   string  $sBuilder For now, those are valid: front_content, custom, custom_path or a
     *                            Userdefined UriBuilder name. The name must be a subpart of the
     *                            UriBuilder class, e. g. 'MyUriBuilder' for cUriBuilderMyUriBuilder.
     *                            The classfile must be named like class.uribuilder.myuribuilder.php
     *                            and it must be reside in /contenido/classes/uri/ folder.
     * @return  cUriBuilder
     * @throws  InvalidArgumentException In case unknown type of builder is requested you'll get an Exception
     */
    public static function getUriBuilder($sBuilder)
    {
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
                    $sFileName  = 'class.uribuilder' . $sBuilder . '.php';
                    $sPath      = str_replace('\\', '/', dirname(__FILE__)) . '/';
                    if (!cFileHandler::exists($sPath . $sFileName)) {
                        throw new InvalidArgumentException('The classfile of cUriBuilder couldn\'t included by Contenido_UriBuilderFactory: '.$sBuilder.'!');
                    }
                    cInclude('classes', 'UriBuilderFileName');
                    if (!class_exists($sClassName)) {
                        throw new InvalidArgumentException('The classfile of cUriBuilder couldn\'t included by Contenido_UriBuilderFactory: '.$sBuilder.'!');
                    }
                    return call_user_func(array($sClassName, 'getInstance'));
                }

                throw new InvalidArgumentException('Invalid/Empty cUriBuilder passed to cUriBuilderFactory: '.$sBuilder.'!');
                break;
        }
    }
}

?>