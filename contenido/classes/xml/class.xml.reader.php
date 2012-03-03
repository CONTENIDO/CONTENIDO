<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * The XML reader class of CONTENIDO.
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
 
class ContenidoXmlReader extends ContenidoXmlBase {
	protected $_xpath = null;
	protected $_dom = null;
	
	/**
	 * Loads a XML document and the corresponding DOMXPath instance.
	 *
	 * @param string $sFilename path to the XML document
	 *
	 * @return void 
	 */
	public function load($sFilename) {
		$this->_dom = DOMDocument::load($sFilename);
		$this->_initXpathInstance();
	}
	
	/**
	 * Returns a DOMNodeList for a given XPath expression.
	 * 
	 * @param	string	$sPath	xpath string
	 * 
	 * @return	DOMNodeList
	 */
	public function getXpathNodeList($sPath) {
		if ($this->_xpath === NULL) {
			cWarning(__FILE__, __LINE__, "Can not execute XPath string: DOMXpath instance not found.");
			return new DOMNodeList();
		}
		
		return $this->_xpath->query(parent::resolvePath($sPath));
	}
	
	/**
	 * Returns the element of an DOMNodeList read out by a xpath string.
	 * 
	 * @param	string	$sPath		xpath string
	 * @param	integer	$iNodeKey	node key (optional, default: 0)
	 * 
	 * @return	DOMNode
	 */
	public function getXpathNode($sPath, $iNodeKey = 0) {
		$sPath = parent::getLevelXpath($sPath, $iNodeKey);
		
		$oDomNodeList = $this->getXpathNodeList($sPath);
        return $oDomNodeList->item(0);
	}

	/**
	 * Returns the value of an DOMNode read out by a xpath string.
	 * 
	 * @param	string	$sPath		xpath string
	 * @param	integer	$iNodeKey	node key (optional, default: 0)
	 * 
	 * @return	string	value of DOMNode
	 */	
	public function getXpathValue($sPath, $iNodeKey = 0) {
		$oDomNode = $this->getXpathNode($sPath, $iNodeKey);
		return $this->_decode($oDomNode->nodeValue);
	}
	
	/**
	 * Returns the amount of nodes in a given XPath string.
	 * 
	 * @param	string	$sPath	XPath string
	 * 
	 * @return	integer	amount of nodes in node list
	 */
	public function countXpathNodes($sPath) {
		$oDomNodeList = $this->getXpathNodeList($sPath);
		
		if (isset($oDomNodeList->length)) {
			$iLength = (int) $oDomNodeList->length;
		} else {
			$iLength = 0;
		}
		
		return $iLength;
	}
	
	/**
	 * Decodes the value if XML document has not UTF-8 encoding.
	 *
	 * @param	string	$sValue	value to decode
	 * 
	 * @return	string	decoded value
	 */
	protected function _decode($sValue) {
		if ($this->getEncoding() != 'UTF-8') {
			$sValue = utf8_decode($sValue);
		}
		
		return $sValue;
	}
}