<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for structure information and management
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2012-03-29] This class is deprecated use cApiCategoryLanguage() or cApiCategoryArticle() instead
 *
 * {@internal
 *   created 2003
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/** @deprecated [2012-03-29] This class is deprecated use cApiCategoryLanguage() or cApiCategoryArticle() instead */
class Structure {

    /** @deprecated [2012-03-29] This class is deprecated use cApiCategoryLanguage() or cApiCategoryArticle() instead */
    function Structure() {
        cDeprecated("Use cApiArticleLanguage() or cApiCategoryArticle() instead");
    }

    /** @deprecated [2012-03-29] Use cApiCategoryLanguage() instead */
    function getStructureName($structure, $idlang) {
        cDeprecated("Use cApiCategoryLanguage() instead");
        $oCatLang = new cApiCategoryLanguage();
        if ($oCatLang->loadByCategoryIdAndLanguageId($structure, $idlang)) {
            return $oCatLang->get('name');
        } else {
            return '';
        }
    }

    /** @deprecated [2012-03-29] Use cApiCategoryArticle() instead */
    function getStructureIDForCatArt($idcatart) {
        cDeprecated("Use cApiCategoryArticle() instead");
        $oCatArt = new cApiCategoryArticle($idcatart);
        return ($oCatArt->isLoaded()) ? $oCatArt->get('idcat') : null;
    }

}

?>