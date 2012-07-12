<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Foldable table row
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.12
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2004-08-04
 *
 *   $Id: class.foldingrow.php 2629 2012-07-12 12:14:35Z mischa.holz $
 * }}
 *
 */
if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cGuiFoldingRow extends cHTML
{
    /**
     * Table row with the header
     * @private array
     * @access private
     */
    private $_headerRow;

    /**
     * Table header data
     * @private array
     * @access private
     */
    private $_headerData;

    /**
     * Table row with the content
     * @private array
     * @access private
     */
    private $_contentRow;

    /**
     * ID for link that triggers expandCollapse
     * @private string
     * @access private
     */
    private $_linkId;

    /**
     * Table content data
     * @private array
     */
    protected $_contentData;

    public function __construct($uuid, $caption = "", $link_id = "", $bExpanded = null) {
        global $auth;

        $this->setCaption($caption);

        $this->_headerRow   = new cHTMLTableRow;
        $this->_headerData  = new cHTMLTableHead;
        $this->_contentRow  = new cHTMLTableRow;
        $this->_contentRow->updateAttributes(array("id" => $uuid));
        $this->_contentData = new cHTMLTableData;
        $this->_uuid        = $uuid;
        $this->_link        = new cGuiLink;
        $this->_linkId      = $link_id;

        $this->_headerData->setClass("foldingrow");

        $this->_hiddenField = new cHTMLHiddenField("expandstate_".$this->_contentRow->getID());

        $this->_foldingImage = new cHTMLImage;
        $this->_foldingImage->setStyle("margin-right: 4px;");

        $this->setExpanded(false);

        $this->addRequiredScript("parameterCollector.js");
        $this->addRequiredScript("cfoldingrow.js");

        $user = new cApiUser($auth->auth["uid"]);

        if ($bExpanded == null) {
            /* Check for expandstate */
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
        /* Build the expand/collapse link */
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

        $this->_headerData->setContent(array($this->_hiddenField,$this->_link));
        $this->_headerRow->setContent($this->_headerData);

        $this->_contentRow->setContent($this->_contentData);

        $output  = $this->_headerRow->render();
        $output .= $this->_contentRow->render();

        return ($output);
    }
}

/**
 * Old classname for downwards compatibility
 * @deprecated This class was renamed to cGuiFoldingRow
 */
class cFoldingRow extends cGuiFoldingRow {

    public function __construct($uuid, $caption = "", $link_id = "", $bExpanded = null) {
        cDeprecated("This class was renamed to cGuiFoldingRow");

        parent::__construct($uuid, $caption, $link_id, $bExpanded);
    }
}
?>