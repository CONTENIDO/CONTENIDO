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
 * @version    1.0.0
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

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class Art {
	/**
     * Constructor of class Art.
     * @return void
     */
	public function __construct() {
		// empty
	}
	
    /**
     * @deprecated  [2011-09-03] Old constructor function for downwards compatibility
     */
    function Art() {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    /**
     * getArtName()
     * Returns a name for the given article
	 *
	 * @param integer $iArticleId idart of article
	 * @param integer $iLangId idlang for article
	 *
     * @return string  Returns the name of the given article
     */
    public function getArtName($iArticleId, $iLangId) {
		$oArticle = new Article($iArticleId, null, $iLangId);
		
		$sArticleTitle = $oArticle->getField('title');
		if ($sArticleTitle != '') {
			return $sArticleTitle;
		}

		return null;
    } // end function

    /**
     * getArtIDForCatArt()
     * Returns the idart based on an idcatart.
	 *
	 * @param integer $iIdCatArt idcatart to look up
	 *
     * @return integer related idart to given idcatart
     */
    public function getArtIDForCatArt($iIdCatArt) {
        $oCategoryArticle = new cApiCategoryArticle($iIdCatArt);
		$iIdArt = $art->getField('idart');

		if ($iIdArt != false) {
			return $iIdArt;
		}
		
        return null;
    } // End function
} // end class
?>
