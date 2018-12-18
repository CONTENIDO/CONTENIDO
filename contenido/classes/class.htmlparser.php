<?php

/**
 * This file contains the html parser class.
 *
 * @package Core
 * @subpackage Backend
 * @author Starnetsys, LLC.
 * @copyright Starnetsys, LLC.
 * @link http://starnetsys.com
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class HtmlParser.
 *
 * To use, create an instance of the class passing HTML text.
 *
 * Then invoke parse() until it's false.
 *
 * When parse() returns true, $_NodeType, $_NodeName $_NodeValue and
 * $_NodeAttributes are updated.
 *
 * Copyright (c) 2003 Starnetsys, LLC. All rights reserved.
 * Redistribution of source must retain this copyright notice.
 *
 * Starnetsys, LLC (http://starnetsys.com) specializes in
 * website design and software consulting
 *
 * @package Core
 * @subpackage Backend
 */
class HtmlParser {

    /**
     * Node type ID for elements.
     *
     * @var int
     */
    const NODE_TYPE_ELEMENT = 1;

    /**
     * Node type ID for endelements.
     *
     * @var int
     */
    const NODE_TYPE_ENDELEMENT = 2;

    /**
     * Node type ID for texts.
     *
     * @var int
     */
    const NODE_TYPE_TEXT = 3;

    /**
     * Node type ID for comments.
     *
     * @var int
     */
    const NODE_TYPE_COMMENT = 4;

    /**
     * Node type ID when done.
     *
     * @var int
     */
    const NODE_TYPE_DONE = 5;

    /**
     * Field iNodeType.
     *
     * May be one of the NODE_TYPE_* constants above.
     *
     * @var int
     */
    protected $_NodeType;

    /**
     * Field iNodeName.
     *
     * For elements, it's the name of the element.
     *
     * @var string
     */
    protected $_NodeName = "";

    /**
     * Field iNodeValue.
     *
     * For text nodes, it's the text.
     *
     * @var string
     */
    protected $_NodeValue = "";

    /**
     * Field iNodeAttributes.
     *
     * A string-indexed array containing attribute values
     * of the current node. Indexes are always lowercase.
     *
     * @var array
     */
    protected $_NodeAttributes = array();

    /**
     *
     * @var string
     */
    protected $_HtmlText = '';

    /**
     *
     * @var int
     */
    protected $_HtmlTextLength;

    /**
     *
     * @var int
     */
    protected $_HtmlTextIndex = 0;

    /**
     * Constructor to create an instance of this class.
     *
     * Constructs an HtmlParser instance with the HTML text given.
     *
     * @param string $HtmlText
     */
    public function __construct($HtmlText) {
        $this->setHtmlText($HtmlText);
        $this->setHtmlTextLength(cString::getStringLength($HtmlText));
    }

    /**
     * Old constructor.
     *
     * @deprecated [2016-02-04]
     *          This method is deprecated and is not needed any longer.
     *          Please use __construct() as constructor function.
     * @param string $HtmlText
     * @return __construct()
     */
    public function HtmlParser($HtmlText) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        return $this->__construct($HtmlText);
    }

    /**
     * Set method for HtmlText variable.
     *
     * @param string $htmlText
     * @return string
     */
    public function setHtmlText($htmlText) {
        return $this->_HtmlText = $htmlText;
    }

    /**
     * Set method for HtmlTextLength variable.
     *
     * @param int $htmlTextLength
     * @return int
     */
    public function setHtmlTextLength($htmlTextLength) {
        return $this->_HtmlTextLength = $htmlTextLength;
    }

    /**
     * Set method for HtmlTextIndex variable.
     *
     * @param int $HtmlTextIndex
     * @return int
     */
    public function setHtmlTextIndex($HtmlTextIndex) {
        return $this->_HtmlTextIndex = $HtmlTextIndex;
    }

    /**
     * Set method for NodeAttributes.
     *
     * To clear this array please use _clearAttributes function.
     *
     * @param array $NodeAttributes
     * @return bool|array
     */
    public function _setNodeAttributes($NodeAttributes) {

        if (!is_array($NodeAttributes)) {
            return false;
        }

        return $this->_NodeAttributes = $NodeAttributes;
    }

    /**
     * Get method for _HtmlText.
     *
     * @return string
     */
    public function getHtmlText() {
        return $this->_HtmlText;
    }

    /**
     * Get method for _HtmlTextLength.
     *
     * @return int
     */
    public function getHtmlTextLength() {
        return $this->_HtmlTextLength;
    }

    /**
     * Get method for _NodeType.
     *
     * @return string
     */
    public function getNodeType() {
        return $this->_NodeType;
    }

    /**
     * Get method for _NodeName.
     *
     * @return string
     */
    public function getNodeName() {
        return $this->_NodeName;
    }

    /**
     * Getmethod for _NodeAttributes.
     *
     * @return array
     */
    public function getNodeAttributesArray() {
        return $this->_NodeAttributes;
    }

    /**
     * Get method for _NodeAttributes with specific attribute.
     *
     * @param string $attribute
     * @return string
     */
    public function getNodeAttributes($attribute) {
        return $this->_NodeAttributes[$attribute];
    }

    /**
     * Get method for _HtmlTextIndex.
     *
     * @return int
     */
    public function getHtmlTextIndex() {
        return $this->_HtmlTextIndex;
    }

    /**
     * Increase HtmlTextIndex.
     *
     * @return bool
     */
    protected function increaseHtmlTextIndex() {
        return $this->_HtmlTextIndex++;
    }

    /**
     * Method parse.
     *
     * Parses the next node. Returns false only if the end of the HTML
     * text has been reached. Updates values of iNode* fields.
     *
     * @return bool
     */
    public function parse() {
        $text = $this->_skipToElement();
        if ($text != "") {
            $this->_NodeType = self::NODE_TYPE_TEXT;
            $this->_NodeName = "Text";
            $this->_NodeValue = $text;
            return true;
        }
        return $this->_readTag();
    }

    /**
     * Clear (reset) _NodeAttributes array.
     *
     * @return array
     */
    protected function _clearAttributes() {
        return $this->_NodeAttributes = array();
    }

    /**
     *
     * @return bool
     */
    protected function _readTag() {
        if ($this->_currentChar() != "<") {
            $this->_NodeType = self::NODE_TYPE_DONE;
            return false;
        }

        $this->_skipInTag(array("<"));
        $this->_clearAttributes();
        $name = $this->_skipToBlanksInTag();
        $pos = cString::findFirstPos($name, "/");

        if ($pos === 0) {
            $this->_NodeType = self::NODE_TYPE_ENDELEMENT;
            $this->_NodeName = cString::getPartOfString($name, 1);
            $this->_NodeValue = "";
        } else {
            if (!$this->_isValidTagIdentifier($name)) {
                $comment = false;
                if ($name == "!--") {
                    $rest = $this->_skipToStringInTag("-->");
                    if ($rest != "") {
                        $this->_NodeType = self::NODE_TYPE_COMMENT;
                        $this->_NodeName = "Comment";
                        $this->_NodeValue = "<" . $name . $rest;
                        $comment = true;
                    }
                }
                if (!$comment) {
                    $this->_NodeType = self::NODE_TYPE_TEXT;
                    $this->_NodeName = "Text";
                    $this->_NodeValue = "<" . $name;
                }
                return true;
            } else {
                $this->_NodeType = self::NODE_TYPE_ELEMENT;
                $this->_NodeValue = "";
                $nameLength = cString::getStringLength($name);
                if ($nameLength > 0 && cString::getPartOfString($name, $nameLength - 1, 1) == "/") {
                    $this->_NodeName = cString::getPartOfString($name, 0, $nameLength - 1);
                } else {
                    $this->_NodeName = $name;
                }
            }
        }

        while ($this->_skipBlanksInTag()) {
            $attrName = $this->_skipToBlanksOrEqualsInTag();
            $NodeAttributes = $this->getNodeAttributesArray();

            if ($attrName != "") {

                $this->_skipBlanksInTag();

                if ($this->_currentChar() == "=") {
                    $this->_skipEqualsInTag();
                    $this->_skipBlanksInTag();

                    $value = $this->_readValueInTag();

                    $NodeAttributes[cString::toLowerCase($attrName)] = $value;
                    $this->_setNodeAttributes($NodeAttributes);
                } else {
                    $NodeAttributes[cString::toLowerCase($attrName)] = "";
                    $this->_setNodeAttributes($NodeAttributes);
                }
            }
        }

        $this->_skipEndOfTag();
        return true;
    }

    /**
     *
     * @param string $name
     * @return number
     */
    protected function _isValidTagIdentifier($name) {
        return preg_match('/[A-Za-z0-9]+/', $name);
    }

    /**
     *
     * @return bool
     */
    protected function _skipBlanksInTag() {
        return "" != ($this->_skipInTag(array(
            " ",
            "\t",
            "\r",
            "\n"
        )));
    }

    /**
     *
     * @return string
     */
    protected function _skipToBlanksOrEqualsInTag() {
        return $this->_skipToInTag(array(
            " ",
            "\t",
            "\r",
            "\n",
            "="
        ));
    }

    /**
     *
     * @return string
     */
    protected function _skipToBlanksInTag() {
        return $this->_skipToInTag(array(
            " ",
            "\t",
            "\r",
            "\n"
        ));
    }

    /**
     *
     * @return string
     */
    protected function _skipEqualsInTag() {
        return $this->_skipInTag(array(
            "="
        ));
    }

    /**
     *
     * @return string
     */
    protected function _readValueInTag() {
        $ch = $this->_currentChar();
        $value = "";

        if ($ch == "\"") {
            $this->_skipInTag(array(
                "\""
            ));
            $value = $this->_skipToInTag(array(
                "\""
            ));
            $this->_skipInTag(array(
                "\""
            ));
        } else if ($ch == "\'") {
            $this->_skipInTag(array(
                "\'"
            ));
            $value = $this->_skipToInTag(array(
                "\'"
            ));
            $this->_skipInTag(array(
                "\'"
            ));
        } else {
            $value = $this->_skipToBlanksInTag();
        }

        return $value;
    }

    /**
     *
     * @return number|string
     */
    protected function _currentChar() {
        if ($this->getHtmlTextIndex() >= $this->getHtmlTextLength()) {
            return -1;
        }
        $HtmlText = $this->getHtmlText();
        return $HtmlText{$this->getHtmlTextIndex()};
    }

    /**
     *
     * @return bool
     */
    protected function _moveNext() {
        if ($this->getHtmlTextIndex() < $this->getHtmlTextLength()) {
            $this->increaseHtmlTextIndex();
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return string
     */
    protected function _skipEndOfTag() {
        $sb = "";
        if (($ch = $this->_currentChar()) !== -1) {
            $match = ($ch == ">");
            if (!$match) {
                return $sb;
            }
            $sb .= $ch;
            $this->_moveNext();
        }
        return $sb;
    }

    /**
     *
     * @param string $chars
     * @return string
     */
    protected function _skipInTag($chars) {
        $sb = "";
        while (($ch = $this->_currentChar()) !== -1) {
            if ($ch == ">") {
                return $sb;
            } else {
                $match = false;
                for ($idx = 0; $idx < count($chars); $idx++) {
                    if ($ch == $chars[$idx]) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    return $sb;
                }
                $sb .= $ch;
                $this->_moveNext();
            }
        }
        return $sb;
    }

    /**
     *
     * @param string $chars
     * @return string
     */
    protected function _skipToInTag($chars) {
        $sb = "";
        while (($ch = $this->_currentChar()) !== -1) {
            $match = $ch == ">";
            if (!$match) {
                for ($idx = 0; $idx < count($chars); $idx++) {
                    if ($ch == $chars[$idx]) {
                        $match = true;
                        break;
                    }
                }
            }
            if ($match) {
                return $sb;
            }
            $sb .= $ch;
            $this->_moveNext();
        }
        return $sb;
    }

    /**
     *
     * @return string
     */
    protected function _skipToElement() {
        $sb = "";
        while (($ch = $this->_currentChar()) !== -1) {
            if ($ch == "<") {
                return $sb;
            }
            $sb .= $ch;
            $this->_moveNext();
        }
        return $sb;
    }

    /**
     * Returns text between current position and $needle, inclusive, or
     * "" if not found.
     *
     * The current index is moved to a point after the location of
     * $needle, or not moved at all if nothing is found.
     *
     * @param string $needle
     * @return string
     */
    protected function _skipToStringInTag($needle) {
        $pos = cString::findFirstPos($this->getHtmlText(), $needle, $this->getHtmlTextIndex());
        if ($pos === false) {
            return "";
        }
        $top = $pos + cString::getStringLength($needle);
        $retvalue = cString::getPartOfString($this->getHtmlText(), $this->getHtmlTextIndex(), $top - $this->getHtmlTextIndex());
        $this->setHtmlTextIndex($top);
        return $retvalue;
    }
}