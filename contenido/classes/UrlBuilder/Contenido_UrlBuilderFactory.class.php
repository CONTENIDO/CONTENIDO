<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Factory for retrieving required Contenido_UrlBuilder object
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.1.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created  2008-02-18
 *   modified 2008-09-29, Murat Purc, add instantiation of userdefined UrlBuilder
 *   modified 2008-12-22, Murat Purc, fixed file exists check of userdefined UrlBuilder
 *   modified 2009-01-01, Murat Purc, changed call of call_user_func to support php previous to 5.2.3
 *   
 *   $Id: Contenido_UrlBuilderFactory.class.php 930 2009-01-01 20:44:32Z xmurrix $: 
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


class Contenido_UrlBuilderFactory {
    /**
     * Returns desired UrlBuilder object.
     *
     * @param   string  $sBuilder For now, those are valid: front_content, custom, custom_path or a 
     *                            Userdefined UrlBuilder name. The name must be a subpart of the 
     *                            UrlBuilder class, e. g. 'MyUrlBuilder' for Contenido_UrlBuilder_MyUrlBuilder.
     *                            The classfile must be named like Contenido_UrlBuilder_MyUrlBuilder.class.php
     *                            and it must be reside in /contenido/classes/UrlBuilder/ folder.
     * @return  Contenido_UrlBuilder
     * @throws  InvalidArgumentException In case unknown type of builder is requested you'll get an Exception
     */
    public static function getUrlBuilder($sBuilder) {
        switch($sBuilder) {
            case 'front_content':
                cInclude('classes', 'UrlBuilder/Contenido_UrlBuilder_Frontcontent.class.php');
                return Contenido_UrlBuilder_Frontcontent::getInstance();
                break;
            case 'custom':
                cInclude('classes', 'UrlBuilder/Contenido_UrlBuilder_Custom.class.php');
                return Contenido_UrlBuilder_Custom::getInstance();
                break;
            case 'custom_path':
                cInclude('classes', 'UrlBuilder/Contenido_UrlBuilder_CustomPath.class.php');
                return Contenido_UrlBuilder_CustomPath::getInstance();
                break;
            default:
                if ((string) $sBuilder !== '') {
                    $sClassName = 'Contenido_UrlBuilder_' . $sBuilder;
                    $sFileName  = 'Contenido_UrlBuilder_' . $sBuilder . '.class.php';
                    $sPath      = str_replace('\\', '/', dirname(__FILE__)) . '/';
                    if (!file_exists($sPath . $sFileName)) { 
                        throw new InvalidArgumentException('The classfile of Contenido_UrlBuilder couldn\'t included by Contenido_UrlBuilderFactory: '.$sBuilder.'!'); 
                    }
                    cInclude('classes', 'UrlBuilder/' . $sFileName);
                    if (!class_exists($sClassName)) {
                        throw new InvalidArgumentException('The classfile of Contenido_UrlBuilder couldn\'t included by Contenido_UrlBuilderFactory: '.$sBuilder.'!');
                    }
                    return call_user_func(array($sClassName, 'getInstance'));
                }

                throw new InvalidArgumentException('Invalid/Empty Contenido_UrlBuilder passed to Contenido_UrlBuilderFactory: '.$sBuilder.'!');
                break;
        }
    }
}
?>