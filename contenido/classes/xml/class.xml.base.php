<?php

/**
 * This file contains the base XML class.
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
 * Base XML class
 *
 * @package    Core
 * @subpackage XML
 */
abstract class cXmlBase {

    /**
     *
     * @var DOMDocument
     */
    protected $_dom = NULL;

    /**
     *
     * @var DOMXpath
     */
    protected $_xpath = NULL;

    /**
     * Creates a new XML document using DOMDocument.
     *
     * @param string $version [optional, default: 1.0]
     *         version of DOMDocument
     * @param string $encoding [optional, default: UTF-8]
     *         encoding of DOMDocument
     */
    protected function _createDocument($version = '', $encoding = '') {
        if ($version == '') {
            $version = '1.0';
        }

        if ($encoding == '') {
            $encoding = 'UTF-8';
        }

        $this->_dom = new DOMDocument($version, $encoding);
    }

    /**
     * Returns the DOMDocument object.
     * @return DOMDocument
     */
    public function getDomDocument() {
        return $this->_dom;
    }

    /**
     * Sets a current DOMDocument object to class.
     *
     * @param DOMDocument $domDocument
     *         DOMDocument object
     */
    public function setDomDocument(DOMDocument $domDocument) {
        $this->_dom = $domDocument;
        $this->_initXpathInstance();
    }

    /**
     * Returns the encoding of the XML document.
     *
     * @throws cException if there is no DOM document
     * @return string
     *     encoding
     */
    public function getEncoding() {
        if ($this->_dom === NULL) {
            throw new cException('Can not determine encoding: DOMDocument not found.');
        }

        return $this->_dom->xmlEncoding;
    }

    /**
     *
     * @param string $name
     * @param string $value
     */
    public function registerXpathNamespace($name, $value) {
        $this->_xpath->registerNamespace($name, $value);
    }

    /**
     * Initializes a new DOMXPath instance for DOMDocument.
     *
     * @throws cException if there is no valid DOM document
     */
    protected function _initXpathInstance() {
        if (!($this->_dom instanceof DOMDocument)) {
            throw new cException('Can not initialize XPath instance: DOMDocument not found.');
        }

        $this->_xpath = new DOMXpath($this->_dom);
    }

    /**
     * Resolves a given path which contains ".." statement for moving up one
     * level in path.
     *
     * @param string $path
     *         path to resolve
     * @return string
     *         resolved path
     */
    public static function resolvePath($path) {
        if (substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }

        $splits = explode('/', $path);

        foreach ($splits as $i => $sSplitter) {
            if ($sSplitter == '..') {
                unset($splits[$i]);
                unset($splits[$i - 1]);
            }
        }

        $pathString = implode('/', $splits);

        if (substr($pathString, -1) == '/') {
            $pathString = substr($pathString, 0, -1);
        }

        return $pathString;
    }

    /**
     * Returns given XPath with integrad level definition.
     *
     * @param string $path
     *         XPath to extend
     * @param int $level
     *         level
     * @return string
     *         extended XPath
     */
    public static function getLevelXpath($path, $level) {
        $splits = explode('/', $path);
        $splitCount = count($splits);

        if ($splitCount <= 1) {
            return $path;
        }

        $lastElementName = $splits[$splitCount - 1];
        unset($splits[$splitCount - 1]);

        $returnPath = implode('/', $splits);
        $returnPath .= '[' . ($level + 1) . ']/' . $lastElementName;

        return $returnPath;
    }

    /**
     * Converts an array to a SimpleXMLElement. Example:
     * array(
     *     'key1' => 'value1',
     *     'key2' => array('value21', 'value22'),
     *     'key3' => array('key31' => 'value31', 'key32' => 'value32')
     * );
     *
     * becomes
     *
     * <?/**
     * Converts an array to a SimpleXMLElement.
     * Example:
     * array(
     *     'key1' => 'value1',
     *     'key2' => array('value21', 'value22'),
     *     'key3' => array('key31' => 'value31', 'key32' => 'value32')
     * );
     *
     * becomes
     *
     * <?xml version="1.0" encoding="utf-8"?>
     * <root>
     *     <key1>value1</key1>
     *     <key2>
     *         <array_value>value21</array_value>
     *         <array_value>value22</array_value>
     *     </key2>
     *     <key3>
     *         <key31>value31</key31>
     *         <key32>value32</key32>
     *     </key3>
     * </root>
     *
     * @param array $array
     *         the array which should be converted to XML
     * @param SimpleXMLElement $xml [optional]
     *         the element to which the array should be added
     * @param string $rootTagName [optional]
     *         the root tag name which should be used - is only used when $xml is NULL!
     * @return SimpleXMLElement
     *         the array as a SimpleXMLElement
     */

    public static function arrayToXml($array, $xml = NULL, $rootTagName = 'root') {
        if ($xml == NULL) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><' . $rootTagName . '/>', LIBXML_NOCDATA);
        }

        // check whether array is associative
        if ($array !== array_values($array)) {
            // if array is associative, use the keys as well as the values
            foreach ($array as $key => $value) {
                // recursion if value is an array
                if (is_array($value)) {
                    self::arrayToXml($value, $xml->addChild($key));
                } else {
                    $child = $xml->addChild($key);
                    $node = dom_import_simplexml($child);
                    $no = $node->ownerDocument;
                    $node->appendChild($no->createCDATASection($value));
                }
            }
        } else {
            // if array is not associative, use the array values as separate xml
            // nodes
            foreach ($array as $value) {
                $child = $xml->addChild('array_value');
                $node = dom_import_simplexml($child);
                $no = $node->ownerDocument;
                $node->appendChild($no->createCDATASection($value));
            }
        }

        return $xml;
    }

    /**
     * Converts the given XML string to an array.
     * Example:
     * <?xml version="1.0" encoding="utf-8"?>
     * <root>
     * <key1>value1</key1>
     * <key2>
     * <array_value>value21</array_value>
     * <array_value>value22</array_value>
     * </key2>
     * <key3>
     * <key31>value31</key31>
     * <key32>value32</key32>
     * </key3>
     * </root>
     *
     * becomes
     *
     * array(
     * 'key1' => 'value1',
     * 'key2' => array('value21', 'value22'),
     * 'key3' => array('key31' => 'value31', 'key32' => 'value32')
     * );
     *
     * @param string $xmlString
     *         contains a valid XML structure
     * @return array
     */
    public static function xmlStringToArray($xmlString) {
        return self::xmlToArray(new SimpleXMLElement($xmlString, LIBXML_NOCDATA));
    }

    /**
     * Checks if a string is valid XML
     *
     * @param string $xmlString
     * @return bool
     *         True if the XML is valid
     */
    public static function isValidXML($xmlString) {
        $testArray = null;

        try {
            $testArray = @cXmlBase::xmlStringToArray($xmlString);
        } catch(Exception $e) {
            return false;
        }

        return is_array($testArray);
    }

    /**
     * Converts the given SimpleXMLElement object to an array.
     * Example:
     * <?xml version="1.0" encoding="utf-8"?>
     * <root>
     * <key1>value1</key1>
     * <key2>
     * <array_value>value21</array_value>
     * <array_value>value22</array_value>
     * </key2>
     * <key3>
     * <key31>value31</key31>
     * <key32>value32</key32>
     * </key3>
     * </root>
     *
     * becomes
     *
     * array(
     * 'key1' => 'value1',
     * 'key2' => array('value21', 'value22'),
     * 'key3' => array('key31' => 'value31', 'key32' => 'value32')
     * );
     *
     * @param SimpleXMLElement $xml
     * @return array
     */
    public static function xmlToArray($xml) {
        $json = json_encode($xml);
        $array = json_decode($json, true);
        $array = self::_cleanArray($array);

        return $array;
    }

    /**
     * Cleans an array by replacing all empty arrays with empty strings.
     * Additionally, the function replaces all associative arrays which have
     * only empty values with the array keys of the array.
     *
     * @param array $array
     *         the array to clean
     * @return array
     *         the cleaned array
     */
    private static function _cleanArray($array) {
        // replace empty arrays with empty strings recursively
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    $array[$key] = '';
                } else {
                    // if array contains array values, take them directly
                    if ($key == 'array_value') {
                        return $array['array_value'];
                    }
                    $array[$key] = self::_cleanArray($value);
                }
            }
        }
        // if array only contains empty values, return the array keys
        if (count(array_keys($array, '')) == count($array)) {
            return array_keys($array);
        } else if (count(array_keys($array, 'array_value')) == count($array)) {
        }

        return $array;
    }

}
