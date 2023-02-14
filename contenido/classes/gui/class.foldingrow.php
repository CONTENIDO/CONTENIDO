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
     * Id of the row.
     *
     * @var string
     */
    private $_uuid;

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
     * @var bool
     */
    protected $_expanded;

    /**
     * @var string
     */
    protected $_caption;

    /**
     * @var mixed
     */
    protected $_helpContext;

    /**
     * @var int
     */
    protected $_indent;

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
        $this->_contentRow->updateAttributes(["id" => $uuid]);

        $this->_contentData = new cHTMLTableData();

        $this->_link = new cHTMLLink();

        $this->_linkId = $linkId;

        $this->_hiddenField = new cHTMLHiddenField("expandstate_" . $this->_contentRow->getID());

        $this->_foldingImage = new cHTMLImage();
        $this->_foldingImage->advanceID();

        $this->setExpanded(false);

        $this->addRequiredScript("parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e");
        $this->addRequiredScript("cfoldingrow.js");

        $user = new cApiUser($auth->auth["uid"]);

        if ($bExpanded === NULL) {
            // Check for expandstate
            if ($user->isLoaded()) {
                if ($user->getProperty("expandstate", $uuid) == "true") {
                    $this->setExpanded($user->getProperty("expandstate", $uuid));
                }
            }
        } else {
            $this->setExpanded(cSecurity::toBoolean($bExpanded));
        }
    }

    /**
     *
     * @param bool $expanded [optional]
     */
    public function setExpanded($expanded = false) {
        if ($expanded) {
            $this->_foldingImage->setSrc("images/widgets/foldingrow/expanded.gif");
            $this->_foldingImage->updateAttributes(["data-state" => "expanded"]);
            $this->_contentRow->setStyle("display: ;");
            $this->_hiddenField->setValue('expanded');
        } else {
            $this->_foldingImage->setSrc("images/widgets/foldingrow/collapsed.gif");
            $this->_foldingImage->updateAttributes(["data-state" => "collapsed"]);
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
    public function setContentData($content) {
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

        $imgId = $this->_foldingImage->getID();
        $rowId = $this->_contentRow->getID();
        $hiddenId = $this->_hiddenField->getID();
        $uuid = $this->_uuid;

        $this->_link->setLink("javascript:void(0);");
        $this->_link->setContent($this->_foldingImage->render() . $this->_caption);

        $this->_headerData->setContent([$this->_hiddenField, $this->_link]);
        $this->_headerRow->setContent($this->_headerData);

        $this->_contentRow->setContent($this->_contentData);

        $output = $this->_headerRow->render();
        $output .= $this->_contentRow->render();

        return <<<HTML
<!-- cGuiFoldingRow -->
{$output}
<script>
(function(Con, $) {
    $(function() {
        $("#{$this->_linkId}").click(function() {
            Con.FoldingRow.toggle("{$imgId}", "{$rowId}", "{$hiddenId}", "{$uuid}");
        });
    });
})(Con, Con.$);
</script>
<!-- /cGuiFoldingRow -->
HTML;
    }

}
