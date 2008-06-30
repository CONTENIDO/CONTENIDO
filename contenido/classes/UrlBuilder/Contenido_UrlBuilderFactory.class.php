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
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2008-02-18
 *   
 *   $Id: 
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
     * @param string $sBuilder For now, those are valid: front_content, custom, custom_path
     * @return obj
     * @throws InvalidArgumentException In case unknown type of builder is requested you'll get an Exception
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
                throw new InvalidArgumentException('This type of Contenido_UrlBuilder is unknown to Contenido_UrlBuilderFactory: '.$sBuilder.'!');
                break;
        }
    }
}
?>