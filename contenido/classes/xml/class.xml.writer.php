<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * The XML writer class of CONTENIDO.
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0.0
 * @author Dominik Ziegler
 * @copyright four for business AG <info@contenido.org>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release >= 4.9.0
 */
class cXmlWriter extends cXmlBase {

    /**
     * Class constructor of cXmlWriter.
     * Creates the XML document.
     *
     * @param string $version version of XML document (optional, default: 1.0)
     * @param string $encoding encoding of XML document (optional, default:
     *            UTF-8)
     *
     * @return void
     */
    public function __construct($version = '', $encoding = '') {
        $this->_createDocument($version, $encoding);
    }

    /**
     * Adds a new element to the XML document.
     * If no root element is given the element will be appended to the root
     * node.
     *
     * @param string $name name of the element
     * @param string $value value of the element (optional)
     * @param DOMElement $rootElement root element (optional)
     * @param array $attributes array of attributes added to this element
     *            (optional)
     *
     * @return DOMElement created DOM element
     */
    public function addElement($name, $value = '', $rootElement = null, $attributes = array()) {
        $element = $this->_dom->createElement($name, $value);

        $element = $this->_addElementAttributes($element, $attributes);

        if ($rootElement === null) {
            $this->_dom->appendChild($element);
        } else {
            $rootElement->appendChild($element);
        }

        return $element;
    }

    /**
     * Adds an array of attributes to a specific DOM element.
     *
     * @access protected
     *
     * @param DOMElement $element DOM element to add attributes
     * @param array $attributes array of attributes
     *
     * @return DOMElement DOM element with assigned attributes
     */
    protected function _addElementAttributes(DOMElement $element, array $attributes = array()) {
        if (count($attributes) == 0) {
            return $element;
        }

        foreach ($attributes as $attributeName => $attributeValue) {
            $element->setAttribute($attributeName, $attributeValue);
        }

        return $element;
    }

    /**
     * Returns the complete XML tree as string.
     *
     * @return string XML tree
     */
    public function saveToString() {
        return $this->_dom->saveXML();
    }

    /**
     * Saves the XML tree into a file.
     *
     * @param string $directory path to destination directory
     * @param string $fileName name of the written file
     *
     * @return boolean state of saving process (true if file was created, false
     *         otherwise)
     */
    public function saveToFile($directory, $fileName) {
        if (is_writable($directory) === false) {
            cWarning(__FILE__, __LINE__, "Can not write XML file: Directory is not writable.");
            return false;
        }

        if (substr($directory, 0, -1) != '/') {
            $directory = $directory . '/';
        }

        cFileHandler::write($directory . $fileName, $this->saveToString());

        return cFileHandler::exists($directory . $fileName);
    }

}