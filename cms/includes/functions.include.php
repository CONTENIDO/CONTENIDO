<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 * 
 *
 * @package    CONTENIDO Frontend
 * @subpackage Functions
 * @version    0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * 
 * 
 * {@internal 
 *   created   unknown
 *   modified  2008-07-03, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
  die('Illegal call');
}

/** @deprecated 2012-03-05 This function is not longer supported. */
function getTeaserImage ($text,$return = 'path') {
	cDeprecated("This function is not longer supported.");
	$regEx  = "/<img[^>]*?>.*?/i";
    $match  = array();
    preg_match($regEx, $text, $match);
	
	$regEx = "/(src)(=)(['\"]?)([^\"']*)(['\"]?)/i";
    $img = array();
    preg_match($regEx, $match[0], $img);
    
    if ($return == 'path') {
	    return $img[4];
    } else {
    	return $match[0];
    }
}
?>