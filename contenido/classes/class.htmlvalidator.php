<?php
/**
 * This file contains the html validator class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author timo.hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class validates HTML.
 *
 * @package Core
 * @subpackage Backend
 */
class cHTMLValidator {

    /**
     *
     * @var array
     */
    protected $_doubleTags = array(
        "form",
        "head",
        "body",
        "html",
        "td",
        "tr",
        "table",
        "a",
        "tbody",
        "title",
        "container",
        "span",
        "div"
    );

    /**
     *
     * @var array
     */
    public $missingNodes = array();

    /**
     *
     * @var string
     * @deprecated not used anymore
     */
    public $iNodeName;

    /**
     *
     * @var string
     */
    protected $_html;

    /**
     *
     * @var array
     */
    protected $_nestingLevel = array();

    /**
     *
     * @var array
     */
    protected $_nestingNodes = array();

    /**
     *
     * @var array
     */
    protected $_existingTags = array();

    /**
     *
     * @param string $html
     */
    public function validate($html) {
        $nestingLevel = 0;

        // Clean up HTML first from any PHP scripts, and clean up line breaks
        $this->_html = $this->_cleanHTML($html);

        $htmlParser = new HtmlParser($this->_html);

        while ($htmlParser->parse()) {
            $this->_existingTags[] = $htmlParser->iNodeName;
            // Check if we found a double tag
            if (in_array($htmlParser->iNodeName, $this->_doubleTags)) {
                if (!array_key_exists($htmlParser->iNodeName, $this->_nestingLevel)) {
                    $this->_nestingLevel[$htmlParser->iNodeName] = 0;
                }

                if (!array_key_exists($htmlParser->iNodeName, $this->_nestingNodes)) {
                    $this->_nestingNodes[$htmlParser->iNodeName][intval($this->_nestingLevel[$htmlParser->iNodeName])] = array();
                }

                // Check if it's a start tag
                if ($htmlParser->iNodeType == HtmlParser::NODE_TYPE_ELEMENT) {
                    // Push the current element to the stack, remember ID and
                    // Name, if possible
                    $nestingLevel++;

                    $this->_nestingNodes[$htmlParser->iNodeName][intval($this->_nestingLevel[$htmlParser->iNodeName])]["name"] = $htmlParser->iNodeAttributes["name"];
                    $this->_nestingNodes[$htmlParser->iNodeName][intval($this->_nestingLevel[$htmlParser->iNodeName])]["id"] = $htmlParser->iNodeAttributes["id"];
                    $this->_nestingNodes[$htmlParser->iNodeName][intval($this->_nestingLevel[$htmlParser->iNodeName])]["level"] = $nestingLevel;
                    $this->_nestingNodes[$htmlParser->iNodeName][intval($this->_nestingLevel[$htmlParser->iNodeName])]["char"] = $htmlParser->iHtmlTextIndex;
                    $this->_nestingLevel[$htmlParser->iNodeName]++;
                }

                if ($htmlParser->iNodeType == HtmlParser::NODE_TYPE_ENDELEMENT) {
                    // Check if we've an element of this type on the stack
                    if ($this->_nestingLevel[$htmlParser->iNodeName] > 0) {
                        unset($this->_nestingNodes[$htmlParser->iNodeName][$this->_nestingLevel[$htmlParser->iNodeName]]);
                        $this->_nestingLevel[$htmlParser->iNodeName]--;

                        if ($this->_nestingNodes[$htmlParser->iNodeName][intval($this->_nestingLevel[$htmlParser->iNodeName])]["level"] != $nestingLevel) {
                            // Todo: Check for the wrong nesting level
                        }

                        $nestingLevel--;
                    }
                }
            }
        }

        // missingNodes should be an empty array by default
        $this->missingNodes = array();

        // Collect all missing nodes
        foreach ($this->_nestingLevel as $key => $value) {
            // One or more missing tags found
            if ($value > 0) {
                // Step trough all missing tags
                for ($i = 0; $i < $value; $i++) {
                    $node = $this->_nestingNodes[$key][$i];

                    list($line, $char) = $this->_getLineAndCharPos($node["char"]);
                    $this->missingNodes[] = array(
                        "tag" => $key,
                        "id" => $node["id"],
                        "name" => $node["name"],
                        "line" => $line,
                        "char" => $char
                    );

                    $this->missingTags[$line][$char] = true;
                }
            }
        }
    }

    /**
     *
     * @param string $tag
     * @return boolean
     */
    public function tagExists($tag) {
        if (in_array($tag, $this->_existingTags)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param string $html
     * @return mixed
     */
    protected function _cleanHTML($html) {
        // remove all php code from layout
        $resultingHTML = preg_replace('/<\?(php)?((.)|(\s))*?\?>/i', '', $html);

        // We respect only \n, but we need to take care of windows (\n\r) and
        // other systems (\r)
        $resultingHTML = str_replace("\r\n", "\n", $resultingHTML);
        $resultingHTML = str_replace("\r", "\n", $resultingHTML);

        return $resultingHTML;
    }

    /**
     *
     * @return string
     * @deprecated not used anymore
     */
    protected function _returnErrorMap() {
        $html = "<pre>";

        $chunks = explode("\n", $this->_html);

        foreach ($chunks as $key => $value) {
            $html .= ($key + 1) . " ";

            for ($i = 0; $i < strlen($value); $i++) {
                $char = substr($value, $i, 1);

                if (is_array($this->missingTags[$key + 1])) {
                    // echo ($key+1) . " ". $i."<br>";
                    if (array_key_exists($i + 2, $this->missingTags[$key + 1])) {
                        $html .= "<u><b>" . conHtmlSpecialChars($char) . "</b></u>";
                    } else {
                        $html .= conHtmlSpecialChars($char);
                    }
                } else {
                    $html .= conHtmlSpecialChars($char);
                }
            }

            $html .= "<br>";
        }

        return $html;
    }

    /**
     *
     * @param int $charpos
     * @return array
     */
    protected function _getLineAndCharPos($charpos) {
        $mangled = substr($this->_html, 0, $charpos);

        $line = substr_count($mangled, "\n") + 1;
        $char = $charpos - strrpos($mangled, "\n");

        return array(
            $line,
            $char
        );
    }
}
