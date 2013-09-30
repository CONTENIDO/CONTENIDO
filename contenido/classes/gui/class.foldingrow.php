<?php
/**
 * This file contains the foldable table row GUI class.
 *
 * @package          Core
 * @subpackage       GUI
 * @version          SVN Revision $Rev:$
 *
 * @author           Bjoern Behrens
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Foldable table row GUI class
 *
 * @package    Core
 * @subpackage GUI
 */
class cGuiFoldingRow extends cHTML {

    /**
     * Table row with the header
     * @var cHTMLTableRow
     */
    protected $_headerRow;

    /**
     * Table header data
     * @var cHTMLTableHead
     */
    protected $_headerData;

    /**
     * Table row with the content
     * @var cHTMLTableRow
     */
    protected $_contentRow;

    /**
     * ID for link that triggers expandCollapse
     * @var string
     */
    protected $_linkId;

    /**
     * Link
     * @var cHTMLLink
     */
    protected $_link;

    /**
     * Table content data
     * @private cHTMLTableData
     */
    protected $_contentData;

    public function __construct($uuid, $caption = "", $linkId = "", $bExpanded = NULL) {
        global $auth;

        $this->setCaption($caption);

        $this->_headerRow = new cHTMLTableRow();
        $this->_headerData = new cHTMLTableHead();
        $this->_contentRow = new cHTMLTableRow();
        $this->_contentRow->updateAttributes(array("id" => $uuid));
        $this->_contentData = new cHTMLTableData();
        $this->_uuid = $uuid;
        $this->_link = new cHTMLLink();
        $this->_linkId = $linkId;

        $this->_headerData->setClass("foldingrow");

        $this->_hiddenField = new cHTMLHiddenField("expandstate_" . $this->_contentRow->getID());

        $this->_foldingImage = new cHTMLImage();
        $this->_foldingImage->setStyle("margin-right: 4px;");

        $this->setExpanded(false);

        $this->addRequiredScript("parameterCollector.js");
        $this->addRequiredScript("cfoldingrow.js");

        $user = new cApiUser($auth->auth["uid"]);

        if ($bExpanded == NULL) {
            // Check for expandstate
            if (!$user->virgin) {
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

    public function setExpanded($expanded = false) {
        if ($expanded == true) {
            $this->_foldingImage->setSrc("images/widgets/foldingrow/expanded.gif");
            $this->_foldingImage->updateAttributes(array("data" => "expanded"));
            $this->_contentRow->setStyle("display: ;");
            $this->_hiddenField->setValue('expanded');
        } else {
            $this->_foldingImage->setSrc("images/widgets/foldingrow/collapsed.gif");
            $this->_foldingImage->updateAttributes(array("data" => "collapsed"));
            $this->_contentRow->setStyle("display: none;");
            $this->_hiddenField->setValue('collapsed');
        }
        $this->_expanded = $expanded;
    }

    public function setCaption($caption) {
        $this->_caption = $caption;
    }

    public function setHelpContext($context = false) {
        $this->_helpContext = $context;
    }

    public function setIndent($indent = 0) {
        $this->_indent = $indent;
    }

    function setContentData($content) {
        $this->_contentData->setContent($content);
    }

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

        $this->_link->setLink("javascript:cGuiFoldingRow_expandCollapse('$imgid', '$rowid', '$hiddenid', '$uuid');");
        $this->_link->setContent($this->_foldingImage->render() . $this->_caption);

        $this->_headerData->setContent(array($this->_hiddenField, $this->_link));
        $this->_headerRow->setContent($this->_headerData);

        $this->_contentRow->setContent($this->_contentData);

        $output = $this->_headerRow->render();
        $output .= $this->_contentRow->render();

        return ($output);
    }

}

?>