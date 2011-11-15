<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Category management class
 *
 * Requirements:
 * @con_php_req 5.0
 * @con_notice Status: Test. Not for production use
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2011-11-15] Use classes in contenido/classes/contenido/class.category.php
 *                          - Use cApiCategoryCollection instead of CategoryCollection
 *                          - Use cApiCategory instead of CategoryItem
 *                          and use classes in contenido/classes/contenido/class.categorylanguage.php
 *                          - Use cApiCategoryLanguageCollection instead of CategoryLanguageCollection
 *                          - Use cApiCategoryLanguage instead of CategoryLanguageItem
 *
 * {@internal
 *   created  unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-11-15, Murat Purc, removed in favor of normalizing the API
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

?>