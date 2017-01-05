<?php

/**
 * This file contains the XML writer class.
 *
 * @package    Core
 * @subpackage XML
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * XML writer class
 *
 * @package    Core
 * @subpackage XML
 */
class cXmlWriter extends cXmlBase {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates the XML document.
     *
     * @param string $version [optional, default: 1.0]
     *         version of XML document
     * @param string $encoding [optional, default: UTF-8]
     *         encoding of XML document
     */
    public function __construct($version = '', $encoding = '') {
        $this->_createDocument($version, $encoding);
    }

    /**
     * Adds a new element to the XML document.
     * If no root element is given the element will be appended to the root
     * node.
     *
     * @param string $name
     *         name of the element
     * @param string $value [optional]
     *         value of the element
     * @param DOMElement $rootElement [optional]
     *         root element
     * @param array $attributes [optional]
     *         array of attributes added to this element
     * @param bool $cdata [optional]
     *         whether the value is surround by CDATA blocks
     * @return DOMElement
     *         created DOM element
     */
    public function addElement($name, $value = '', $rootElement = NULL, $attributes = array(), $cdata = false) {
        if ($value == '' || ($value != '' && $cdata == true)) {
            $element = $this->_dom->createElement($name);
            if ($value != '' && $cdata == true) {
                $element->appendChild($this->_dom->createCDATASection($value));
            }
        } else {
            $element = $this->_dom->createElement($name, $value);
        }

        $element = $this->_addElementAttributes($element, $attributes);

        if ($rootElement === NULL) {
            $this->_dom->appendChild($element);
        } else {
            $rootElement->appendChild($element);
        }

        return $element;
    }

    /**
     * Adds an array of attributes to a specific DOM element.
     *
     * @param DOMElement $element
     *         DOM element to add attributes
     * @param array $attributes [optional]
     *         array of attributes
     * @return DOMElement
     *         DOM element with assigned attributes
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
     * @return string
     *         XML tree
     */
    public function saveToString() {
        return $this->_dom->saveXML();
    }

    /**
     * Saves the XML tree into a file.
     *
     * @param string $directory
     *         path to destination directory
     * @param string $fileName
     *         name of the written file
     * @throws cException
     *         if the directory is not writable
     * @return bool
     *         state of saving process (true if file was created, false otherwise)
     */
    public function saveToFile($directory, $fileName) {
        if (is_writable($directory) === false) {
            throw new cException('Can not write XML file: Directory is not writable.');
        }

        if (cString::getPartOfString($directory, 0, -1) != '/') {
            $directory = $directory . '/';
        }

        cFileHandler::write($directory . $fileName, $this->saveToString());

        return cFileHandler::exists($directory . $fileName);
    }

}
