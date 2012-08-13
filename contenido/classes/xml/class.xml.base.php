<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * The base XML class of CONTENIDO.
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
abstract class cXmlBase {

    protected $_dom = null;

    protected $_xpath = null;

    /**
     * Creates a new XML document using DOMDocument.
     *
     * @access protected
     *
     * @param string $version version of DOMDocument (optional, default: 1.0)
     * @param string $encoding encoding of DOMDocumen (optional, default: UTF-8)
     *
     * @return void
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
     * Returns the encoding of the XML document.
     *
     * @return string encoding
     */
    public function getEncoding() {
        if ($this->_dom === NULL) {
            cWarning(__FILE__, __LINE__, 'Can not determine encoding: DOMDocument not found.');
            return false;
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
     * @access protected
     * @return void
     */
    protected function _initXpathInstance() {
        if (!($this->_dom instanceof DOMDocument)) {
            cWarning(__FILE__, __LINE__, 'Can not initialize XPath instance: DOMDocument not found.');
            return;
        }

        $this->_xpath = new DOMXpath($this->_dom);
    }

    /**
     * Resolves a given path which contains ".." statement for moving up one
     * level in path.
     *
     * @static
     *
     *
     * @param string $path path to resolve
     *
     * @return string resolved path
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
     * @static
     *
     *
     * @param string $path XPath to extend
     * @param integer $level level
     *
     * @return string extended XPath
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
     * 'key1' => 'value1',
     * 'key2' => array('value21', 'value22'),
     * 'key3' => array('key31' => 'value31', 'key32' => 'value32')
     * );
     *
     * becomes
     *
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
     * @param array $array the array which should be converted to XML
     * @param SimpleXMLElement $xml [optional] the element to which the array
     *            should be added
     * @param string $rootTagName [optional] the root tag name which should be
     *            used - is only used when $xml is null!
     * @return SimpleXMLElement the array as a SimpleXMLElement
     */

    public static function arrayToXml($array, $xml = null, $rootTagName = 'root') {
        if ($xml == null) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><' . $rootTagName . '/>');
        }

        // check whether array is associative
        if ($array !== array_values($array)) {
            // if array is associative, use the keys as well as the values
            foreach ($array as $key => $value) {
                // recursion if value is an array
                if (is_array($value)) {
                    self::arrayToXml($value, $xml->addChild($key));
                } else {
                    $xml->addChild($key, $value);
                }
            }
        } else {
            // if array is not associative, use the array values as separate xml
            // nodes
            foreach ($array as $value) {
                $xml->addChild('array_value', $value);
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
     * @param string $xmlString contains a valid XML structure
     */

    public static function xmlStringToArray($xmlString) {
        return self::xmlToArray(new SimpleXMLElement($xmlString));
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
     * @param array $array the array to clean
     * @return array the cleaned array
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

abstract class ContenidoXmlBase extends cXmlBase {

    /**
     *
     * @deprecated [2012-07-24] class was renamed to cXmlBase
     */
    public function __construct() {
        cDeprecated('Class was renamed to cXmlBase.');
    }

}