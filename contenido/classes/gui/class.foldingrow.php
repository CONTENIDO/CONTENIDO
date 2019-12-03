<?php

/**
 * This file contains the foldable table row GUI class.
 *
 * @package          Core
 * @subpackage       GUI
 * @author           Bjoern Behrens
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Foldable table row GUI class.
 *
 * <strong>JavaScript requirements</strong>
 * Requires the class Con.FoldingRow from cfoldingrow.js
 * as well as parameterCollector.js.
 *
 * @package    Core
 * @subpackage GUI
 */
class cGuiFoldingRow extends cHTML {

    /**
     * Table row with the header.
     *
     * @var cHTMLTableRow
     */
    protected $_headerRow;

    /**
     * Table header data.
     *
     * @var cHTMLTableHead
     */
    protected $_headerData;

    /**
     * Table row with the content.
     *
     * @var cHTMLTableRow
     */
    protected $_contentRow;

    /**
     * ID for link that triggers expandCollapse.
     *
     * @var string
     */
    protected $_linkId;

    /**
     * Link.
     *
     * @var cHTMLLink
     */
    protected $_link;

    /**
     * Table content data.
     *
     * @var cHTMLTableData
     */
    protected $_contentData;

    /**
     * @var cHTMLImage
     */
    protected $_foldingImage;

    /**
     * @var cHTMLHiddenField
     */
    protected $_hiddenField;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string    $uuid
     * @param string    $caption   [optional]
     * @param string    $linkId    [optional]
     * @param bool|NULL $bExpanded [optional]
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($uuid, $caption = "", $linkId = "", $bExpanded = NULL) {
        global $auth;

        $this->_uuid = $uuid;

        $this->setCaption($caption);

        $this->_headerRow = new cHTMLTableRow();

        $this->_headerData = new cHTMLTableHead();
        $this->_headerData->setClass("foldingrow");

        $this->_contentRow = new cHTMLTableRow();
        $this->_contentRow->updateAttributes(array("id" => $uuid));

        $this->_contentData = new cHTMLTableData();

        $this->_link = new cHTMLLink();

        $this->_linkId = $linkId;

        $this->_hiddenField = new cHTMLHiddenField("expandstate_" . $this->_contentRow->getID());

        $this->_foldingImage = new cHTMLImage();
        $this->_foldingImage->advanceID();

        $this->setExpanded(false);

        $this->addRequiredScript("parameterCollector.js");
        $this->addRequiredScript("cfoldingrow.js");

        $user = new cApiUser($auth->auth["uid"]);

        if ($bExpanded == NULL) {
            // Check for expandstate
            if ($user->isLoaded()) {
                if ($user->getProperty("expandstate", $uuid) == "true") {
                    $this->setExpanded($user->getProperty("expandstate", $uuid));
                }
            }
        } else {
            if ($bExpanded) {
                $this->setExpanded(true);
            } else {
                $this->setExpanded(false);
            }
        }
    }

    /**
     *
     * @param bool $expanded [optional]
     */
    public function setExpanded($expanded = false) {
        if ($expanded == true) {
            $this->_foldingImage->setSrc("images/widgets/foldingrow/expanded.gif");
            $this->_foldingImage->updateAttributes(array("data-state" => "expanded"));
            $this->_contentRow->setStyle("display: ;");
            $this->_hiddenField->setValue('expanded');
        } else {
            $this->_foldingImage->setSrc("images/widgets/foldingrow/collapsed.gif");
            $this->_foldingImage->updateAttributes(array("data-state" => "collapsed"));
            $this->_contentRow->setStyle("display: none;");
            $this->_hiddenField->setValue('collapsed');
        }
        $this->_expanded = $expanded;
    }

    /**
     *
     * @param string $caption
     */
    public function setCaption($caption) {
        $this->_caption = $caption;
    }

    /**
     * Unused.
     *
     * @param mixed $context [optional]
     */
    public function setHelpContext($context = false) {
        $this->_helpContext = $context;
    }

    /**
     * Unused.
     *
     * @param int $indent [optional]
     */
    public function setIndent($indent = 0) {
        $this->_indent = $indent;
    }

    /**
     *
     * @param string|object|array $content
     */
    function setContentData($content) {
        $this->_contentData->setContent($content);
    }

    /**
     * @see cHTML::render()
     * @return string
     *         Generated markup
     */
    public function render() {
        // Build the expand/collapse link
        $this->_link->setClass("foldingrow");
        if ($this->_linkId != NULL) {
            $this->_link->setID($this->_linkId);
        }

        $imgid = $this->_foldingImage->getID();
        $rowid = $this->_contentRow->getID();
        $hiddenid = $this->_hiddenField->getID();
        $uuid = $this->_uuid;

        $this->_link->setLink("javascript:void(0);");
        $this->_link->setContent($this->_foldingImage->render() . $this->_caption);

        $this->_headerData->setContent(array($this->_hiddenField, $this->_link));
        $this->_headerRow->setContent($this->_headerData);

        $this->_contentRow->setContent($this->_contentData);

        $output = $this->_headerRow->render();
        $output .= $this->_contentRow->render();

        $output = <<<HTML
<!-- cGuiFoldingRow -->
{$output}
<script type="text/javascript">
(function(Con, $) {
    $(function() {
        $("#{$this->_linkId}").click(function() {
            Con.FoldingRow.toggle("{$imgid}", "{$rowid}", "{$hiddenid}", "{$uuid}");
        });
    });
})(Con, Con.$);
</script>
<!-- /cGuiFoldingRow -->
HTML;

        return $output;
    }

}
