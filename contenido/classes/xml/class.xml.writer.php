<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * The base XML writer class of CONTENIDO.
 * This class extends DOMDocument to provide its functionality.
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
 
class ContenidoXmlWriter extends DOMDocument {
	/**
	 * Class constructor of ContenidoXmlWriter
	 *
	 * @param string $sVersion version of XML document (optional, default: 1.0)
	 * @param string $sEncoding encoding of XML document (optional, default: UTF-8)
	 *
	 * @return void
	 */
	public function __construct($sVersion = '', $sEncoding = '') {
		if ($sVersion == '') {
			$sVersion = '1.0';
		}
		
		if ($sEncoding == '') {
			$sEncoding = 'UTF-8';
		}
		
		parent::__construct($sVersion, $sEncoding);
	}
	
	/**
	 * Adds a new element to XML document.
	 * If no root element is given the element will be appended to the root node.
	 *
	 * @param string $sName name of the element
	 * @param string $sValue value of the element (optional)
	 * @param DOMElement $oRootElement root element (optional)
	 * @param array $aAttributes array of attributes added to this element (optional)
	 * 
	 * @return DOMElement created DOM element
	 */
	public function addElement($sName, $sValue = '', $oRootElement = null, $aAttributes = array()) {
		$oElement = $this->createElement($sName, $sValue);
		
		$oElement = $this->_addElementAttributes($oElement, $aAttributes);
		
		if ($oRootElement === null) {
			$this->appendChild($oElement);
		} else {
			$oRootElement->appendChild($oElement);
		}

		return $oElement;
	}
	
	/**
	 * Adds an array of attributes to a specific DOM element.
	 * @access protected
	 *
	 * @param DOMElement $oElement DOM element to add attributes
	 * @param array $aAttributes array of attributes
	 *
	 * @return DOMElement DOM element with assigned attributes
	 */
	protected function _addElementAttributes(DOMElement $oElement, array $aAttributes = array()) {
		if (count($aAttributes) == 0) {
			return;
		}
		
		foreach ($aAttributes as $sAttributeName => $sAttributeValue) {
			$oElement->setAttribute($sAttributeName, $sAttributeValue);
		}
		
		return $oElement
	}
	
	/**
	 * Returns the complete XML tree as string.
	 * @return string XML tree
	 */
	public function saveToString() {
		return $this->saveXML();
	}
	
	/**
	 * Saves the XML tree into a file.
	 * 
	 * @param string $sDirectory path to destination directory
	 * @param string $sFileName name of the written file
	 * 
	 * @return boolean state of saving process (true if file was created, false otherwise)
	 */
	public function saveToFile($sDirectory, $sFileName) {
		if (is_writable($sDirectory) === false) {
			return false;
		}
		
		file_put_contents($sDirectory . $sFileName, $this->saveToString());
		
		return file_exists($sDirectory . $sFileName);
	}
}