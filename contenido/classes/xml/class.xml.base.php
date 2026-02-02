<?php

/**
 * This file contains the base XML class.
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
 * Base XML class
 *
 * @package    Core
 * @subpackage XML
 */
abstract class cXmlBase
{

    /**
     * @var ?DOMDocument
     */
    protected $dom = NULL;

    /**
     * @var ?DOMXpath
     */
    protected $xpath = NULL;

    /**
     * Creates a new XML document using DOMDocument.
     *
     * @param string $version [optional, default: 1.0] Version of DOMDocument
     * @param string $encoding [optional, default: UTF-8] Encoding of DOMDocument
     */
    protected function createDocument(string $version = '', string $encoding = '')
    {
        if ($version == '') {
            $version = '1.0';
        }

        if ($encoding == '') {
            $encoding = 'UTF-8';
        }

        $this->dom = new DOMDocument($version, $encoding);
    }

    /**
     * Returns the DOMDocument object.
     * @return DOMDocument
     */
    public function getDomDocument(): DOMDocument
    {
        return $this->dom;
    }

    /**
     * Sets a current DOMDocument object to class.
     *
     * @param DOMDocument $domDocument
     * @throws cException
     */
    public function setDomDocument(DOMDocument $domDocument)
    {
        $this->dom = $domDocument;
        $this->initXpathInstance();
    }

    /**
     * Returns the encoding of the XML document.
     *
     * @throws cException if there is no DOM document
     */
    public function getEncoding(): string
    {
        if (!$this->dom instanceof DOMDocument) {
            throw new cException('Can not determine encoding: DOMDocument not found.');
        }

        return $this->dom->xmlEncoding;
    }

    public function registerXpathNamespace(string $name, string $value)
    {
        $this->xpath->registerNamespace($name, $value);
    }

    /**
     * Initializes a new DOMXPath instance for DOMDocument.
     *
     * @throws cException if there is no valid DOM document
     */
    protected function initXpathInstance()
    {
        if (!($this->dom instanceof DOMDocument)) {
            throw new cException('Can not initialize XPath instance: DOMDocument not found.');
        }

        $this->xpath = new DOMXpath($this->dom);
    }

    /**
     * Resolves a given path which contains ".." statement for moving up one
     * level in path.
     *
     * @param string $path Path to resolve
     * @return string Resolved path
     */
    public static function resolvePath(string $path): string
    {
        if (cString::getPartOfString($path, 0, 1) != '/') {
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

        if (cString::getPartOfString($pathString, -1) == '/') {
            $pathString = cString::getPartOfString($pathString, 0, -1);
        }

        return $pathString;
    }

    /**
     * Returns given XPath with integrated level definition.
     *
     * @param string $path XPath to extend
     * @param int $level level
     * @return string Extended XPath
     */
    public static function getLevelXpath(string $path, int $level): string
    {
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
     * Converts an array to a SimpleXMLElement.
     *
     * Example:
     * <pre>
     * [
     *     'key1' => 'value1',
     *     'key2' => ['value21', 'value22'],
     *     'key3' => ['key31' => 'value31', 'key32' => 'value32'],
     * ];
     * </pre>
     *
     * becomes
     *
     * <pre>
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
     * </pre>
     *
     * @param array $array The array which should be converted to XML
     * @param ?SimpleXMLElement $xml The element to which the array should be added
     * @param string $rootTagName The root tag name which should be used - is only used when $xml is NULL!
     * @return SimpleXMLElement The array as a SimpleXMLElement
     * @throws Exception
     */
    public static function arrayToXml(
        array $array,
        ?SimpleXMLElement $xml = NULL,
        string $rootTagName = 'root'
    ): SimpleXMLElement {
        if (!$xml instanceof SimpleXMLElement) {
            $xml = new SimpleXMLElement(
                '<?xml version="1.0" encoding="utf-8"?><' . $rootTagName . '/>',
                LIBXML_NOCDATA
            );
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
     *
     * Example:
     * <pre>
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
     * </pre>
     *
     * becomes
     *
     * <pre>
     * [
     *      'key1' => 'value1',
     *      'key2' => ['value21', 'value22'],
     *      'key3' => ['key31' => 'value31', 'key32' => 'value32'],
     * ];
     * </pre>
     *
     * @param string $xmlString Contains a valid XML structure
     * @throws Exception
     */
    public static function xmlStringToArray(string $xmlString): array
    {
        return self::xmlToArray(new SimpleXMLElement($xmlString, LIBXML_NOCDATA));
    }

    /**
     * Checks if a string is valid XML
     *
     * @param string $xmlString
     * @return bool True if the XML is valid
     */
    public static function isValidXML(string $xmlString): bool
    {
        if (empty($xmlString) || is_numeric($xmlString)) {
            return false;
        }

        try {
            $testArray = @cXmlBase::xmlStringToArray($xmlString);
        } catch (Exception $e) {
            return false;
        }

        return is_array($testArray);
    }

    /**
     * Converts the given SimpleXMLElement object to an array.
     *
     * @see cXmlBase::xmlStringToArray()
     */
    public static function xmlToArray(SimpleXMLElement $xml): array
    {
        $json = json_encode($xml);
        $array = json_decode($json, true);
        return self::cleanArray($array);
    }

    /**
     * Cleans an array by replacing all empty arrays with empty strings.
     * Additionally, the function replaces all associative arrays which have
     * only empty values with the array keys of the array.
     *
     * @param array $array The array to clean
     * @return array The cleaned array
     */
    private static function cleanArray(array $array): array
    {
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
                    $array[$key] = self::cleanArray($value);
                }
            }
        }
        // if array only contains empty values, return the array keys
        if (count(array_keys($array, '')) === count($array)) {
            return array_keys($array);
        } /** @noinspection PhpStatementHasEmptyBodyInspection */
        elseif (count(array_keys($array, 'array_value')) === count($array)) {
        }

        return $array;
    }

}
