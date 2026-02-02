<?php

/**
 * This file contains the XML reader class.
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
 * XML reader class
 *
 * @package    Core
 * @subpackage XML
 */
class cXmlReader extends cXmlBase
{

    /**
     * Loads a XML document from file and initializes a corresponding DOMXPath
     * instance.
     *
     * @param string $filename Path to the XML document
     * @return bool Load state (true = successfully loaded, false = not found or loaded)
     * @throws cException If file could not be loaded
     */
    public function load(string $filename): bool
    {
        if (!cFileHandler::exists($filename)) {
            return false;
        }

        // Load document via object method to avoid warning in PHP strict mode.
        $doc = new DOMDocument();
        if (!$doc->load($filename)) {
            throw new cException('Could not load file "' . $filename . '"');
        }

        $this->setDomDocument($doc);

        return $this->dom instanceof DOMDocument;
    }

    /**
     * Loads a XML document from file and initializes a corresponding DOMXPath instance.
     *
     * @param string $source Path to the XML document
     * @return bool Load state (true = successfully loaded, false = not found or loaded)
     * @throws cException If XML could not be loaded
     */
    public function loadXML(string $source): bool
    {
        // Load document via object method to avoid warning in PHP strict mode.
        $oDoc = new DOMDocument();
        if (!$oDoc->loadXML($source)) {
            throw new cException('could not load XML');
        }

        $this->dom = $oDoc;
        $this->initXpathInstance();

        return $this->dom instanceof DOMDocument;
    }

    /**
     * Returns a DOMNodeList for a given XPath expression.
     *
     * @param string $path XPath string
     * @return DOMNodeList|false|mixed
     * @throws cException if there is no XPath
     */
    public function getXpathNodeList(string $path)
    {
        if (!$this->xpath instanceof DOMXpath) {
            throw new cException('Can not execute XPath string: DOMXpath instance not found.');
        }

        return $this->xpath->query(parent::resolvePath($path));
    }

    /**
     * Returns the element of an DOMNodeList read out by a XPath string.
     *
     * @param string $path XPath string
     * @param int $nodeKey [optional, default: 0] Node key
     * @return ?DOMNode
     * @throws cException
     */
    public function getXpathNode(string $path, int $nodeKey = 0): ?DOMNode
    {
        $path = parent::getLevelXpath($path, $nodeKey);

        $domNodeList = $this->getXpathNodeList($path);
        return $domNodeList->item(0);
    }

    /**
     * Returns the value of an DOMNode read out by a XPath string.
     *
     * @param string $path XPath string
     * @param int $nodeKey [optional, default: 0] Node key
     * @return string Value of DOMNode
     * @throws cException
     */
    public function getXpathValue(string $path, int $nodeKey = 0): string
    {
        $domNode = $this->getXpathNode($path, $nodeKey);
        return $this->decode($domNode->nodeValue);
    }

    /**
     * Returns the amount of nodes in a given XPath string.
     *
     * @param string $path XPath string
     * @return int Amount of nodes in node list
     * @throws cException
     */
    public function countXpathNodes(string $path): int
    {
        $domNodeList = $this->getXpathNodeList($path);

        return cSecurity::toInteger($domNodeList->length ?? 0);
    }

    /**
     * Decodes the value if XML document has not UTF-8 encoding.
     *
     * @param string $value Value to decode
     * @return string Decoded value
     * @throws cException
     */
    protected function decode(string $value): string
    {
        if ($this->getEncoding() != 'UTF-8') {
            $value = cString::convertEncoding($value, 'ISO-8859-1');
        }

        return $value;
    }

}
