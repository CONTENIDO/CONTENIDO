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
     * @param string $filename
     *         path to the XML document
     * @return bool
     *         load state (true = successfully loaded, false = not found or loaded)
     * @throws cException
     *         if file could not be loaded
     */
    public function load(string $filename): bool
    {
        if (cFileHandler::exists($filename) === false) {
            return false;
        }

        // Load document via object method to avoid warning in PHP strict mode.
        $doc = new DOMDocument();
        if (false === $doc->load($filename)) {
            throw new cException('Could not load file "' . $filename . '"');
        }

        $this->setDomDocument($doc);

        return $this->_dom instanceof DOMDocument;
    }

    /**
     * Loads a XML document from file and initializes a corresponding DOMXPath
     * instance.
     *
     * @param string $source
     *         path to the XML document
     * @return bool
     *         load state (true = successfully loaded, false = not found or loaded)
     * @throws cException
     *         if XML could not be loaded
     */
    public function loadXML(string $source): bool
    {
        // Load document via object method to avoid warning in PHP strict mode.
        $oDoc = new DOMDocument();
        if (false === $oDoc->loadXML($source )) {
            throw new cException('could not load XML');
        }

        $this->_dom = $oDoc;
        $this->_initXpathInstance();

        return $this->_dom instanceof DOMDocument;
    }

    /**
     * Returns a DOMNodeList for a given XPath expression.
     *
     * @param string $path
     *         xpath string
     * @return DOMNodeList|false|mixed
     * @throws cException if there is no xpath
     */
    public function getXpathNodeList(string $path)
    {
        if ($this->_xpath === NULL) {
            throw new cException('Can not execute XPath string: DOMXpath instance not found.');
        }

        return $this->_xpath->query(parent::resolvePath($path));
    }

    /**
     * Returns the element of an DOMNodeList read out by a xpath string.
     *
     * @param string $path
     *        xpath string
     * @param int $nodeKey [optional, default: 0]
     *        node key
     * @return DOMNode|null
     * @throws cException
     */
    public function getXpathNode(string $path, int $nodeKey = 0)
    {
        $path = parent::getLevelXpath($path, $nodeKey);

        $domNodeList = $this->getXpathNodeList($path);
        return $domNodeList->item(0);
    }

    /**
     * Returns the value of an DOMNode read out by a xpath string.
     *
     * @param string $path
     *        xpath string
     * @param int $nodeKey [optional, default: 0]
     *        node key
     * @return string
     *         value of DOMNode
     * @throws cException
     */
    public function getXpathValue(string $path, int $nodeKey = 0): string
    {
        $domNode = $this->getXpathNode($path, $nodeKey);
        return $this->_decode($domNode->nodeValue);
    }

    /**
     * Returns the amount of nodes in a given XPath string.
     *
     * @param string $path
     *         XPath string
     * @return int
     *         amount of nodes in node list
     * @throws cException
     */
    public function countXpathNodes(string $path): int
    {
        $domNodeList = $this->getXpathNodeList($path);

        if (isset($domNodeList->length)) {
            $length = (int)$domNodeList->length;
        } else {
            $length = 0;
        }

        return $length;
    }

    /**
     * Decodes the value if XML document has not UTF-8 encoding.
     *
     * @param string $value
     *         value to decode
     * @return string
     *         decoded value
     * @throws cException
     */
    protected function _decode(string $value): string
    {
        if ($this->getEncoding() != 'UTF-8') {
            $value = @utf8_decode($value);
        }

        return $value;
    }

}
