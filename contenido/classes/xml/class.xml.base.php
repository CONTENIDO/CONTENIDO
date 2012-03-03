<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * The base XML class of CONTENIDO.
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.0
 * @author     Dominik Ziegler
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release >= 4.9.0
 */
 
abstract class ContenidoXmlBase {
	/**
	 * Creates a new XML document using DOMDocument.
	 * @access	protected 
	 * 
	 * @param	string	$sVersion	version of DOMDocument (optional, default: 1.0)
	 * @param	string	$sEncoding	encoding of DOMDocumen (optional, default: UTF-8)
	 *
	 * @return	void
	 */
	protected function _createDocument($sVersion = '', $sEncoding = '') {
		if ($sVersion == '') {
			$sVersion = '1.0';
		}
		
		if ($sEncoding == '') {
			$sEncoding = 'UTF-8';
		}
		
		$this->_dom = new DOMDocument($sVersion, $sEncoding);
	}
	
	/**
	 * Returns the encoding of the XML document.
	 * @return	string	encoding
	 */
	public function getEncoding() {
		if ($this->_dom === NULL) {
			cWarning(__FILE__, __LINE__, "Can not determine encoding: DOMDocument not found.");
			return false;
		}
		
		return $this->_dom->xmlEncoding;
	}
	
	/**
	 * Initializes a new DOMXPath instance for DOMDocument.
	 * @access	protected
	 * @return 	void
	 */
	protected function _initXpathInstance() {
		if ($this->_dom === NULL) {
			cWarning(__FILE__, __LINE__, "Can not initialize XPath instance: DOMDocument not found.");
			return;
		}
		
		$this->_xpath = new DOMXpath($this->_dom);
	}
	
	/**
	 * Resolves a given path which contains ".." statement for moving up one level in path.
	 * @static
	 *
	 * @param	string	$sPath	path to resolve
	 * 
	 * @return	string	resolved path
	 */
	static public function resolvePath($sPath) {
		if (substr($sPath, 0, 1) != '/') {
			$sPath = '/' . $sPath;
		}
		
		$aSplits = explode('/', $sPath);

		foreach ($aSplits as $i => $sSplitter) {
			if ($sSplitter == '..') {
				unset($aSplits[$i]);
				unset($aSplits[$i - 1]);
			}
		}
		
		$sPathString = implode('/', $aSplits);
		
		if (substr($sPathString, -1) == '/') {
			$sPathString = substr($sPathString, 0, -1);
		}
		
		return $sPathString;
	}
	
	/**
	 * Returns given XPath with integrad level definition.
	 * @static
	 *
	 * @param	string	$sPath	XPath to extend
	 * @param	integer	$iLevel	level
	 *
	 * @return	string	extended XPath
	 */
	static public function getLevelXpath($sPath, $iLevel) {
		$aSplits = explode('/', $sPath);
	    $iSplitCount = count($aSplits);
	    
	    if ($iSplitCount <= 1 ) {
	        return $sPath;
	    }
	    
	    $sLastElementName = $aSplits[$iSplitCount - 1];
	    unset($aSplits[$iSplitCount - 1]);
	    
	    $sReturnPath = implode('/', $aSplits);
	    $sReturnPath .= '[' . ($iLevel + 1) . ']/' . $sLastElementName;
	    
	    return $sReturnPath;
	}
}