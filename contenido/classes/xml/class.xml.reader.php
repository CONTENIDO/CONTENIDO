<?php
/**
 * Project: CONTENIDO Content Management System
 * Description: The XML reader class of CONTENIDO.
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
class cXmlReader extends cXmlBase {

    /**
     * Loads a XML document from file and initializes a corresponding DOMXPath
     * instance.
     *
     * @param string $filename path to the XML document
     * @throws Exception if file could not be loaded
     * @return boolean load state (true = successfully loaded, false = not found
     *         or loaded)
     */
    public function load($filename) {
        if (!cFileHandler::exists($filename)) {
            return false;
        }

        // Load document via object method to avoid warning in PHP strict mode.
        $doc = new DOMDocument();
        if (false === $doc->load($filename)) {
            throw new Exception('could not load file "' . $filename . '"');
        }

        $this->_dom = $doc;
        $this->_initXpathInstance();

        return ($this->_dom instanceof DOMDocument);
    }

    /**
     * Loads a XML document from file and initializes a corresponding DOMXPath
     * instance.
     *
     * @param string $sFilename path to the XML document
     * @throws Exception if XML could not be loaded
     * @return boolean load state (true = successfully loaded, false = not found
     *         or loaded)
     */
    public function loadXML($sXml) {
        // Load document via object method to avoid warning in PHP strict mode.
        $oDoc = new DOMDocument();
        if (false === $oDoc->loadXML($sXml)) {
            throw new Exception('could not load XML');
        }

        $this->_dom = $oDoc;
        $this->_initXpathInstance();

        return ($this->_dom instanceof DOMDocument);
    }

    /**
     * Returns a DOMNodeList for a given XPath expression.
     *
     * @param string $path xpath string
     * @return DOMNodeList
     */
    public function getXpathNodeList($path) {
        if ($this->_xpath === NULL) {
            cWarning(__FILE__, __LINE__, 'Can not execute XPath string: DOMXpath instance not found.');
            return new DOMNodeList();
        }

        return $this->_xpath->query(parent::resolvePath($path));
    }

    /**
     * Returns the element of an DOMNodeList read out by a xpath string.
     *
     * @param string $path xpath string
     * @param integer $nodeKey node key (optional, default: 0)
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
     * @param string $path xpath string
     * @param integer $nodeKey node key (optional, default: 0)
     * @return string value of DOMNode
     */
    public function getXpathValue($path, $nodeKey = 0) {
        $domNode = $this->getXpathNode($path, $nodeKey);
        return $this->_decode($domNode->nodeValue);
    }

    /**
     * Returns the amount of nodes in a given XPath string.
     *
     * @param string $path XPath string
     * @return integer amount of nodes in node list
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
     * @param string $value value to decode
     * @return string decoded value
     */
    protected function _decode($value) {
        if ($this->getEncoding() != 'UTF-8') {
            $value = utf8_decode($value);
        }

        return $value;
    }

}
class ContenidoXmlReader extends cXmlReader {

    /**
     *
     * @deprecated [2012-07-24] class was renamed to cXmlReader
     */
    public function __construct() {
        cDeprecated('Class was renamed to cXmlReader.');
    }

}