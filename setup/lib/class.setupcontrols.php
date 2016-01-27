<?php
/**
 * This file contains various classes for displaying the setup.
 *
 * @package    Setup
 * @subpackage GUI
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Alpha image class, based on cHTMLImage.
 *
 * @package Setup
 * @subpackage GUI
 * @todo  Remove the usage of this class. We dont need special alpha filter for IE anymore.
 *        The last place where it is still in use are the error lists with content toggle (+ - icons) feature!
 */
class cHTMLAlphaImage extends cHTMLImage {

    var $_sClickImage;

    var $_sMouseoverClickImage;

    var $_sMouseoverSrc;

    function cHTMLAlphaImage() {
        parent::__construct();
        $this->setAlt("");
    }

    function setMouseover($sMouseoverSrc) {
        $this->_sMouseoverSrc = $sMouseoverSrc;
    }

    function setSwapOnClick($sClickSrc, $sMouseoverClickSrc) {
        $this->_sClickImage = $sClickSrc;
        $this->_sMouseoverClickImage = $sMouseoverClickSrc;
    }

    function toHTML() {
        $imageLocations = "this.imgnormal = '%s'; this.imgover = '%s'; this.clickimgnormal = '%s'; this.clickimgover = '%s';";

        $this->attachEventDefinition("imagelocs", "onload", sprintf($imageLocations, $this->getAttribute('src'), $this->_sMouseoverSrc, $this->_sClickImage, $this->_sMouseoverClickImage));

        if ($this->_sMouseoverSrc != "") {
            if ($this->_sClickImage != "") {
                $this->attachEventDefinition("click", "onclick", "clickHandler(this);");
                $this->attachEventDefinition("mouseover", "onmouseover", "mouseoverHandler(this);");
                $this->attachEventDefinition("mouseover", "onmouseout", "mouseoutHandler(this);");
            } else {
                $sMouseScript = 'this.src=\'%1$s\';';
                $this->attachEventDefinition("mouseover", "onmouseover", sprintf($sMouseScript, $this->_sMouseoverSrc));
                $this->attachEventDefinition("mouseover", "onmouseout", sprintf($sMouseScript, $this->getAttribute('src')));
            }
        }

        return parent::toHTML();
    }

}

/**
 * Setup error message list based on cHTMLDiv.
 *
 * @package Setup
 * @subpackage GUI
 */
class cHTMLErrorMessageList extends cHTMLDiv {

    function cHTMLErrorMessageList() {
        $this->_oTable = new cHTMLTable();
        $this->_oTable->setWidth("100%");
        parent::__construct();
        $this->setClass("errorlist");
    }

    function setContent($content) {
        $this->_oTable->setContent($content);
    }

    function toHTML() {
        $this->_setContent($this->_oTable->render());
        return parent::toHTML();
    }

}

/**
 * Foldable setup error message based on cHTMLTableRow.
 *
 * @package Setup
 * @subpackage GUI
 */
class cHTMLFoldableErrorMessage extends cHTMLTableRow {

    function cHTMLFoldableErrorMessage($sTitle, $sMessage, $sIcon = false, $sIconText = false) {
        $this->_oFolding = new cHTMLTableData();
        $this->_oContent = new cHTMLTableData();
        $this->_oIcon = new cHTMLTableData();
        $this->_oIconImg = new cHTMLAlphaImage();
        $this->_oTitle = new cHTMLDiv();
        $this->_oMessage = new cHTMLDiv();
        $this->_oMessage->advanceID();

        $alphaImage = new cHTMLAlphaImage();
        $alphaImage->setClass("closer");
        $alphaImage->setStyle('margin-top:4px;');
        $alphaImage->setSrc("images/controls/open_all.gif");
        $alphaImage->setMouseover("images/controls/open_all.gif");
        $alphaImage->setSwapOnClick("images/controls/close_all.gif", "images/controls/close_all.gif");
        $alphaImage->attachEventDefinition("showhide", "onclick", "aldiv = document.getElementById('" . $this->_oMessage->getId() . "'); showHideMessage(this, aldiv);");

        $this->_oTitle->setContent($sTitle);
        $this->_oTitle->setStyle("cursor:pointer;");
        $this->_oTitle->attachEventDefinition("showhide", "onclick", "alimg = document.getElementById('" . $alphaImage->getId() . "'); aldiv = document.getElementById('" . $this->_oMessage->getId() . "'); showHideMessage(alimg, aldiv); clickHandler(alimg);");

        $this->_oMessage->setContent($sMessage);
        $this->_oMessage->setClass("entry_closed");

        $this->_oFolding->setVerticalAlignment("top");
        $this->_oFolding->setContent($alphaImage);
        $this->_oFolding->setClass("icon");

        $this->_oContent->setVerticalAlignment("top");
        $this->_oContent->setClass("entry");
        $this->_oContent->setContent(array(
            $this->_oTitle,
            $this->_oMessage
        ));

        $this->_oIcon->setClass("icon");
        $this->_oIcon->setVerticalAlignment("top");
        if ($sIcon !== false) {
            $this->_oIconImg->setSrc($sIcon);

            if ($sIconText !== false) {
                $this->_oIconImg->setAlt($sIconText);
            }

            $this->_oIcon->setContent($this->_oIconImg);
        } else {
            $this->_oIcon->setContent("&nbsp;");
        }

        parent::__construct();
    }

    function toHTML() {
        $this->setContent(array(
            $this->_oFolding,
            $this->_oContent,
            $this->_oIcon
        ));
        return parent::toHTML();
    }

}

/**
 * Setup info message based on cHTMLTableRow.
 *
 * @package Setup
 * @subpackage GUI
 */
class cHTMLInfoMessage extends cHTMLTableRow {

    function cHTMLInfoMessage($sTitle, $sMessage) {
        $this->_oTitle = new cHTMLTableData();
        $this->_oMessage = new cHTMLTableData();

        $this->_oTitle->setContent($sTitle);
        $this->_oTitle->setClass("entry_nowrap");
        $this->_oTitle->setAttribute("nowrap", "nowrap");
        $this->_oTitle->setWidth(1);
        $this->_oTitle->setVerticalAlignment("top");
        $this->_oMessage->setContent($sMessage);
        $this->_oMessage->setClass("entry_nowrap");

        parent::__construct();
    }

    function toHTML() {
        $this->setContent(array(
            $this->_oTitle,
            $this->_oMessage
        ));
        return parent::toHTML();
    }

}

/**
 * Setup language link based on cHTMLDiv, like
 * "English    ->"
 *
 * @package Setup
 * @subpackage GUI
 */
class cHTMLLanguageLink extends cHTMLDiv {

    function cHTMLLanguageLink($langcode, $langname, $stepnumber) {
        parent::__construct();

        $this->setStyle("height:40px;width:150px;");

        $link = new cHTMLLink("#");
        $link->setClass("nav navLabel");
        $link->setContent($langname . "<span>&raquo;</span>");
        $link->attachEventDefinition("stepAttach", "onclick", "document.setupform.step.value = '$stepnumber';");
        $link->attachEventDefinition("languageAttach", "onclick", "document.setupform.elements.language.value = '$langcode';");
        $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");

        $this->setContent($link->render());
    }

}

/**
 * Setup button link based on cHTMLDiv, like
 * "Backend - CMS    ->"
 *
 * @package Setup
 * @subpackage GUI
 */
class cHTMLButtonLink extends cHTMLDiv {

    function cHTMLButtonLink($href, $title) {
        parent::__construct();

        $this->setStyle("height:40px;width:180px;");

        $link = new cHTMLLink($href);
        $link->setAttribute("target", "_blank");
        $link->setClass("nav navLabel");
        $link->setContent($title . "<span>&raquo;</span>");

        $this->setContent($link->render());
    }

}
