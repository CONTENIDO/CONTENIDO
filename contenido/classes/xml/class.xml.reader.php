<?php

/**
 * This file contains the XML reader class.
 *
 * @package    Core
 * @subpackage XML
 * @version    SVN Revision $Rev:$
 *
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * XML reader class
 *
 * @package    Core
 * @subpackage XML
 */
class cXmlReader extends cXmlBase {

    /**
     * Loads a XML document from file and initializes a corresponding DOMXPath
     * instance.
     *
     * @param string $filename
     *         path to the XML document
     * @throws cException
     *         if file could not be loaded
     * @return bool
     *         load state (true = successfully loaded, false = not found or loaded)
     */
    public function load($filename) {

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
     * @param string $sFilename
     *         path to the XML document
     * @throws cException
     *         if XML could not be loaded
     * @return bool
     *         load state (true = successfully loaded, false = not found or loaded)
     */
    public function loadXML($sXml) {
        // Load document via object method to avoid warning in PHP strict mode.
        $oDoc = new DOMDocument();
        if (false === $oDoc->loadXML($sXml)) {
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
     * @throws cException if there is no xpath
     * @return DOMNodeList
     */
    public function getXpathNodeList($path) {
        if ($this->_xpath === NULL) {
            throw new cException('Can not execute XPath string: DOMXpath instance not found.');
        }

        return $this->_xpath->query(parent::resolvePath($path));
    }

    /**
     * Returns the element of an DOMNodeList read out by a xpath string.
     *
     * @param string $path
     *         xpath string
     * @param int $nodeKey [optional, default: 0]
     *         node key
     * @return DOMNode
     */
    public function getXpathNode($path, $nodeKey = 0) {
        $path = parent::getLevelXpath($path, $nodeKey);

        $domNodeList = $this->getXpathNodeList($path);
        return $domNodeList->item(0);
    }

    /**
     * Returns the value of an DOMNode read out by a xpath string.
     *
     * @param string $path
     *         xpath string
     * @param int $nodeKey [optional, default: 0]
     *         node key
     * @return string
     *         value of DOMNode
     */
    public function getXpathValue($path, $nodeKey = 0) {

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
     */
    public function countXpathNodes($path) {
        $domNodeList = $this->getXpathNodeList($path);

        if (isset($domNodeList->length)) {
            $length = (int) $domNodeList->length;
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
     */
    protected function _decode($value) {

        if ($this->getEncoding() != 'UTF-8') {
            $value = utf8_decode($value);
        }

        return $value;
    }

}
