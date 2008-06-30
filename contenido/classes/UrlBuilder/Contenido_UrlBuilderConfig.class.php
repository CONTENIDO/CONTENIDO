<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Configure UrlBuilder URL style. Per default, configures for style index-a-1.html.
 * If you need another style, extend this class to your needs and pass it to desired UrlBuilder.
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
 *   created 2008-02-28
 *   
 *   $Id: 
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


class Contenido_UrlBuilderConfig {
    public static function getConfig() {
        return array(
                    'prefix' => 'index', 
                    'suffix' => '.html', 
                    'separator' => '-'
                    );
    }
}
?>