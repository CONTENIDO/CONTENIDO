<?php

/**
 * This file contains the html validator class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     timo.hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class validates HTML.
 *
 * @package    Core
 * @subpackage Backend
 */
class cHTMLValidator
{

    /**
     * @var array
     */
    protected $_doubleTags = [
        'form',
        'head',
        'body',
        'html',
        'td',
        'tr',
        'table',
        'a',
        'tbody',
        'title',
        'container',
        'span',
        'div',
    ];

    /**
     * @var array
     * @deprecated [2024-02-11] Since 4.10.2, use {@see cHTMLValidator::getMissingNodes()} instead
     */
    public $missingNodes = [];

    /**
     * @var array
     * @deprecated [2024-02-11] Since 4.10.2, use {@see cHTMLValidator::getMissingTags()} instead
     */
    public $missingTags = [];

    /**
     * @var array
     */
    protected $_missingNodes = [];

    /**
     * @var array
     */
    protected $_missingTags = [];

    /**
     * @deprecated [2019-10-30] not used anymore
     * @var string
     */
    public $iNodeName;

    /**
     * @var string
     */
    protected $_html;

    /**
     * @var array
     */
    protected $_nestingLevel = [];

    /**
     * @var array
     */
    protected $_nestingNodes = [];

    /**
     * @var array
     */
    protected $_existingTags = [];

    /**
     * @param string $html
     */
    public function validate($html)
    {
        if (!is_string($html)) {
            try {
                cDeprecated('Parameter $html is not of type string');
            } catch (cInvalidArgumentException $e) {
            }
            return;
        }

        $this->_reset();

        // Clean up HTML first from any PHP scripts, and clean up line breaks
        $this->_html = $this->_cleanHTML($html);

        $this->_parseHTML();

        // Collect all missing nodes and tags
        foreach ($this->_nestingLevel as $key => $value) {
            // One or more missing tags found
            if ($value > 0) {
                // Step through all missing tags
                for ($i = 0; $i < $value; $i++) {
                    $node = $this->_nestingNodes[$key][$i];

                    list($line, $char) = $this->_getLineAndCharPos($node['char']);
                    $this->_missingNodes[] = [
                        'tag' => $key,
                        'id' => $node['id'],
                        'name' => $node['name'],
                        'line' => $line,
                        'char' => $char,
                    ];

                    $this->_missingTags[$line][$char] = true;
                }
            }
        }

        // Fallback in case the old public properties are still accessed somewhere
        $this->missingTags = $this->_missingTags;
        $this->missingNodes = $this->_missingNodes;
    }

    /**
     * @param string $tag
     * @return bool
     */
    public function tagExists($tag): bool
    {
        return in_array($tag, $this->_existingTags);
    }

    /**
     * @return array
     * @since CONTENIDO 4.10.2
     */
    public function getMissingNodes(): array
    {
        return $this->_missingNodes;
    }

    /**
     * @return array
     * @since CONTENIDO 4.10.2
     */
    public function getMissingTags(): array
    {
        return $this->_missingTags;
    }

    /**
     * @param string $html
     *
     * @return string
     */
    protected function _cleanHTML($html): string
    {
        // Remove all php code from layout
        $resultingHTML = preg_replace('/<\?(php)?((.)|(\s))*?\?>/i', '', $html);

        // We respect only \n, but we need to take care of windows (\n\r) and other systems (\r)
        $resultingHTML = str_replace("\r\n", "\n", $resultingHTML);
        return str_replace("\r", "\n", $resultingHTML);
    }

    /**
     * @return string
     * @deprecated not used anymore
     */
    protected function _returnErrorMap(): string
    {
        $html = '<pre>';

        $chunks = explode("\n", $this->_html);

        foreach ($chunks as $key => $value) {
            $html .= ($key + 1) . ' ';

            for ($i = 0; $i < cString::getStringLength($value); $i++) {
                $char = cString::getPartOfString($value, $i, 1);

                if (is_array($this->_missingTags[$key + 1])) {
                    if (array_key_exists($i + 2, $this->_missingTags[$key + 1])) {
                        $html .= '<u><b>' . conHtmlSpecialChars($char) . '</b></u>';
                    } else {
                        $html .= conHtmlSpecialChars($char);
                    }
                } else {
                    $html .= conHtmlSpecialChars($char);
                }
            }

            $html .= '<br>';
        }

        return $html;
    }

    /**
     * @param int $charpos
     * @return array
     */
    protected function _getLineAndCharPos($charpos): array
    {
        $mangled = cString::getPartOfString($this->_html, 0, $charpos);
        $line = cString::countSubstring($mangled, "\n") + 1;
        $char = $charpos - cString::findLastPos($mangled, "\n");

        return [$line, $char];
    }

    /**
     * Resets instance properties.
     *
     * @return void
     */
    protected function _reset()
    {
        $this->_missingNodes = [];
        $this->_missingTags = [];
        $this->_html = '';
        $this->_nestingLevel = [];
        $this->_nestingNodes = [];
        $this->_existingTags = [];
    }

    protected function _parseHTML()
    {
        $nestingLevel = 0;

        $htmlParser = new HtmlParser($this->_html);

        while ($htmlParser->parse()) {
            $nodeName = $htmlParser->getNodeName();
            $this->_existingTags[] = $nodeName;

            // Check if we found a double tag
            if (in_array($nodeName, $this->_doubleTags)) {
                if (!isset($this->_nestingLevel[$nodeName])) {
                    $this->_nestingLevel[$nodeName] = 0;
                }
                $nestingLevelIndex = intval($this->_nestingLevel[$nodeName]);

                if (!isset($this->_nestingNodes[$nodeName][$nestingLevelIndex])) {
                    $this->_nestingNodes[$nodeName][$nestingLevelIndex] = [];
                }

                // Check if it's a start tag
                if ($htmlParser->getNodeType() == HtmlParser::NODE_TYPE_ELEMENT) {
                    // Push the current element to the stack, remember ID and Name, if possible
                    $nestingLevel++;

                    $this->_nestingNodes[$nodeName][$nestingLevelIndex] = [
                        'name' => $htmlParser->getNodeAttributes('name'),
                        'id' => $htmlParser->getNodeAttributes('id'),
                        'level' => $nestingLevel,
                        'char' => $htmlParser->getHtmlTextIndex(),
                    ];
                    $this->_nestingLevel[$nodeName]++;
                }

                if ($htmlParser->getNodeType() == HtmlParser::NODE_TYPE_ENDELEMENT) {
                    // Check if we've an element of this type on the stack
                    if ($this->_nestingLevel[$nodeName] > 0) {
                        unset($this->_nestingNodes[$nodeName][$this->_nestingLevel[$nodeName]]);
                        $this->_nestingLevel[$nodeName]--;

                        // TODO check for the wrong nesting level
                        // if ($this->_nestingNodes[$nodeName][$nestingLevelIndex]["level"] != $nestingLevel) {
                        // }

                        $nestingLevel--;
                    }
                }
            }
        }
    }

}
