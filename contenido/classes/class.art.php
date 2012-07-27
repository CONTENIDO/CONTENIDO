<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for article information and management
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2012-03-29] This class is deprecated use cApiArticleLanguage() or cApiCategoryArticle() instead
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/** @deprecated [2012-03-29] This class is deprecated use cApiArticleLanguage() or cApiCategoryArticle() instead */
class Art {

    /** @deprecated [2012-03-29] This class is deprecated use cApiArticleLanguage() or cApiCategoryArticle() instead */
    public function __construct() {
        cDeprecated("Use cApiArticleLanguage() or cApiCategoryArticle() instead");
    }

    /** @deprecated  [2011-09-03] Old constructor function for downwards compatibility */
    public function Art() {
        cDeprecated("Use __construct()");
        $this->__construct();
    }

    /** @deprecated [2012-03-29] Use cApiArticleLanguage() instead */
    public function getArtName($iArticleId, $iLangId) {
        cDeprecated("Use cApiArticleLanguage() instead");
        $oArticle = new cApiArticleLanguage();
        $oArticle->loadByArticleAndLanguageId((int) $iArticleId, (int) $iLangId);
        $sArticleTitle = $oArticle->getField('title');
        if ($sArticleTitle != '') {
            return $sArticleTitle;
        }
        return null;
    }

    /** @deprecated [2012-03-29] Use cApiCategoryArticle() instead */
    public function getArtIDForCatArt($iIdCatArt) {
        cDeprecated("Use cApiCategoryArticle() instead");
        $oCategoryArticle = new cApiCategoryArticle((int) $iIdCatArt);
        $iIdArt = $oCategoryArticle->getField('idart');
        if ($iIdArt != false) {
            return $iIdArt;
        }
        return null;
    }

}

?>