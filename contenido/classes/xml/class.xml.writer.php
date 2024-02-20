<?php

/**
 * This file contains the XML writer class.
 *
 * @package    Core
 * @subpackage XML
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * XML writer class
 *
 * @package    Core
 * @subpackage XML
 */
class cXmlWriter extends cXmlBase
{

    /**
     * Indentation to use when generating the XML.
     * The indentation has only an effect, when the format output flag of `DOMDocument` is set to `true`.
     * @var int
     */
    private $indentation = 4;

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
    public function __construct(string $version = '', string $encoding = '')
    {
        $this->_createDocument($version, $encoding);
    }

    /**
     * Sets the indentation.
     *
     * @param int $indentation Supported values are 2 or 4.
     * @return void
     */
    public function setIndentation(int $indentation)
    {
        if (in_array($indentation, [2, 4])) {
            $this->indentation = $indentation;
        }
    }

    /**
     * Adds a new element to the XML document.
     * If no root element is given the element will be appended to the root
     * node.
     *
     * @param string $name
     *         name of the element
     * @param string|int|mixed $value [optional]
     *         value of the element
     * @param DOMElement|NULL $rootElement [optional]
     *         root element
     * @param array $attributes [optional]
     *         array of attributes added to this element
     * @param bool $cdata [optional]
     *         whether the value is surrounded by CDATA blocks
     * @return DOMElement
     *         created DOM element
     * @throws DOMException
     */
    public function addElement(
        string $name, $value = '', DOMElement $rootElement = NULL, array $attributes = [], bool $cdata = false
    ): DOMElement
    {
        $isEmptyValue = in_array($value, ['', NULL]);
        if ($isEmptyValue || $cdata) {
            $element = $this->_dom->createElement($name);
            if (!$isEmptyValue && $cdata) {
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
    protected function _addElementAttributes(DOMElement $element, array $attributes = []): DOMElement
    {
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
    public function saveToString(): string
    {
        $xml = $this->_dom->saveXML();
        if (empty($xml)) {
            return '';
        }

        // Modify indentation when the formatOutput is set and indentation is > 2 (default value is 2)
        if ($this->_dom->formatOutput && $this->indentation > 2) {
            $xml = preg_replace_callback('/^( +)</m', function ($a) {
                return str_repeat(' ', intval(strlen($a[1]) / 2) * $this->indentation) . '<';
            }, $xml);
        }

        return $xml;
    }

    /**
     * Saves the XML tree into a file.
     *
     * @param string $directory
     *         path to destination directory
     * @param string $fileName
     *         name of the written file
     * @return bool
     *         state of saving process (true if file was created, false otherwise)
     * @throws cException
     *         if the directory is not writable
     */
    public function saveToFile(string $directory, string $fileName): bool
    {
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
