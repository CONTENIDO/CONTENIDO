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
 * @package    CONTENIDO Backend classes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class Art
{
    /**
     * Constructor of class Art.
     * @return void
     */
    public function __construct()
    {
        // empty
    }

    /**
     * @deprecated  [2011-09-03] Old constructor function for downwards compatibility
     */
    public function Art()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    /**
     * Returns a name for the given article
     *
     * @param int $iArticleId idart of article
     * @param int $iLangId idlang for article
     * @return string|null  Returns the name of the given article
     */
    public function getArtName($iArticleId, $iLangId)
    {
        $oArticle = new cApiArticleLanguage();
        $oArticle->loadByArticleAndLanguageId((int) $iArticleId, (int) $iLangId);

        $sArticleTitle = $oArticle->getField('title');
        if ($sArticleTitle != '') {
            return $sArticleTitle;
        }

        return null;
    }

    /**
     * Returns the idart based on an idcatart.
     *
     * @param int $iIdCatArt idcatart to look up
     * @return int|null  Related idart to given idcatart
     */
    public function getArtIDForCatArt($iIdCatArt) {
        $oCategoryArticle = new cApiCategoryArticle((int) $iIdCatArt);
        $iIdArt = $art->getField('idart');

        if ($iIdArt != false) {
            return $iIdArt;
        }

        return null;
    }
}

?>