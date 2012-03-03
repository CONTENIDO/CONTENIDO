<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Class XML_doc
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    4fb_XML
 * @version    0.9.7
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified unknown, Martin Horwath <horwath@dayside.net>
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/** @deprecated 2011-03-03 Use ContenidoXmlReader instead */
class XML_doc extends ContenidoXmlReader {
    /** @deprecated 2011-03-03 Use ContenidoXmlReader instead. */
    function XML_doc() {
		cDeprecated("Use ContenidoXmlReader instead.");
    }
	
	/** @deprecated 2011-03-03 Use ContenidoXmlReader instead. */
	function load($sFile) {
		if (file_exists($sFile) === false) {
			return false;
		}
		
		parent::load($sFile);
		return true;
	}

    /** @deprecated 2011-03-03 Use ContenidoXmlReader instead. */
    function valueOf($xpath) {
		cDeprecated("Use ContenidoXmlReader instead.");
		$val = $this->getXpathValue('*/' . $xpath);
		
		if ($val != '') {
			return $val;
		}
		
		return $this->getXpathValue($xpath);
    }

	/** @deprecated 2011-03-03 This function is not longer supported. */
    function characterData($parser, $data) {
		cDeprecated("This function is not longer supported.");
		return false;
    }

    /** @deprecated 2011-03-03 This function is not longer supported. */
    function _translateLiteral2NumericEntities($xmlSource, $reverse = FALSE) {
        cDeprecated("This function is not longer supported.");
		return false;
    }
}
?>