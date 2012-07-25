<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package CONTENIDO setup
 * @version 0.1
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
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
        $alphaLoader = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'%s\')';
        $imageLocations = "this.imgnormal = '%s'; this.imgover = '%s'; this.clickimgnormal = '%s'; this.clickimgover = '%s';";

        $this->appendStyleDefinition("filter", sprintf($alphaLoader, $this->_src));
        $this->attachEventDefinition("imagelocs", "onload", sprintf($imageLocations, $this->_src, $this->_sMouseoverSrc, $this->_sClickImage, $this->_sMouseoverClickImage));
        $this->attachEventDefinition("swapper", "onload", 'if (!this.init) {IEAlphaInit(this); IEAlphaApply(this, this.imgnormal); this.init = true;}');

        if ($this->_sMouseoverSrc != "") {
            if ($this->_sClickImage != "") {
                $this->attachEventDefinition("click", "onclick", "clickHandler(this);");
                $this->attachEventDefinition("mouseover", "onmouseover", "mouseoverHandler(this);");
                $this->attachEventDefinition("mouseover", "onmouseout", "mouseoutHandler(this);");
            } else {
                $sMouseScript = 'if (isMSIE) { this.style.filter = \'progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\\\'%1$s\\\');\'; } else { this.src=\'%1$s\'; }';
                $this->attachEventDefinition("mouseover", "onmouseover", sprintf($sMouseScript, $this->_sMouseoverSrc));
                $this->attachEventDefinition("mouseover", "onmouseout", sprintf($sMouseScript, $this->_src));
            }
        }

        return parent::toHTML();
    }

}
class cHTMLErrorMessageList extends cHTMLDiv {

    function cHTMLErrorMessageList() {
        $this->_oTable = new cHTMLTable();
        $this->_oTable->setWidth("100%");
        parent::__construct();
        $this->setClass("errorlist");
        $this->setStyle("width: 450px;height:218px;overflow:auto;border:1px solid black;");
    }

    function setContent($content) {
        $this->_oTable->setContent($content);
    }

    function toHTML() {
        $this->_setContent($this->_oTable->render());
        return parent::toHTML();
    }

}
class cHTMLFoldableErrorMessage extends cHTMLTableRow {

    function cHTMLFoldableErrorMessage($sTitle, $sMessage, $sIcon = false, $sIconText = false) {
        $this->_oFolding = new cHTMLTableData();
        $this->_oContent = new cHTMLTableData();
        $this->_oIcon = new cHTMLTableData();
        $this->_oIconImg = new cHTMLAlphaImage();
        $this->_oTitle = new cHTMLDiv();
        $this->_oMessage = new cHTMLDiv();

        $alphaImage = new cHTMLAlphaImage();
        $alphaImage->setClass("closer");
        $alphaImage->setStyle('margin-top:4px;');
        $alphaImage->setSrc(C_SETUP_CONTENIDO_HTML_PATH . "images/open_all.gif");
        $alphaImage->setMouseover(C_SETUP_CONTENIDO_HTML_PATH . "images/open_all.gif");
        $alphaImage->setSwapOnClick(C_SETUP_CONTENIDO_HTML_PATH . "images/close_all.gif", C_SETUP_CONTENIDO_HTML_PATH . "images/close_all.gif");
        $alphaImage->attachEventDefinition("showhide", "onclick", "aldiv = document.getElementById('" . $this->_oMessage->getId() . "');  showHideMessage(this, aldiv);");

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
class cHTMLLanguageLink extends cHTMLDiv {

    function cHTMLLanguageLink($langcode, $langname, $stepnumber) {
        parent::__construct();

        $linkImage = new cHTMLAlphaImage();
        $linkImage->setSrc(C_SETUP_CONTENIDO_HTML_PATH . "images/submit.gif");
        $linkImage->setMouseover(C_SETUP_CONTENIDO_HTML_PATH . "images/submit_hover.gif");
        $linkImage->setWidth(16);
        $linkImage->setHeight(16);

        $this->setStyle("vertical-align:center;height:40px;width:150px;");
        $link = new cHTMLLink("#");
        $link->setContent($langname);
        $link->attachEventDefinition("stepAttach", "onclick", "document.setupform.step.value = '$stepnumber';");
        $link->attachEventDefinition("languageAttach", "onclick", "document.setupform.elements.language.value = '$langcode';");
        $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");

        $link2 = new cHTMLLink("#");
        $link2->setContent($langname);
        $link2->attachEventDefinition("stepAttach", "onclick", "document.setupform.step.value = '$stepnumber';");
        $link2->attachEventDefinition("languageAttach", "onclick", "document.setupform.elements.language.value = '$langcode';");
        $link2->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");

        $link->attachEventDefinition("mouseover", "onmouseover", sprintf("mouseoverHandler(document.getElementById('%s'));", $linkImage->getId()));
        $link->attachEventDefinition("mouseout", "onmouseout", sprintf("mouseoutHandler(document.getElementById('%s'));", $linkImage->getId()));
        $link2->setContent($linkImage);

        $alignment = '<table border="0" width="100%%" cellspacing="0" cellpadding="0"><tr><td valign="middle">%s</td><td valign="middle" align="right">%s</td></tr></table>';
        $this->setContent(sprintf($alignment, $link->render(), $link2->render()));
    }

}
class cHTMLButtonLink extends cHTMLDiv {

    function cHTMLButtonLink($href, $title) {
        parent::__construct();

        $linkImage = new cHTMLAlphaImage();
        $linkImage->setSrc(C_SETUP_CONTENIDO_HTML_PATH . "images/submit.gif");
        $linkImage->setMouseover(C_SETUP_CONTENIDO_HTML_PATH . "images/submit_hover.gif");
        $linkImage->setWidth(16);
        $linkImage->setHeight(16);

        $this->setStyle("vertical-align:center;height:40px;width:165px;");
        $link = new cHTMLLink($href);
        $link->setAttribute("target", "_blank");
        $link->setContent($title);

        $link2 = new cHTMLLink($href);
        $link2->setAttribute("target", "_blank");
        $link2->setContent($title);

        $link->attachEventDefinition("mouseover", "onmouseover", sprintf("mouseoverHandler(document.getElementById('%s'));", $linkImage->getId()));
        $link->attachEventDefinition("mouseout", "onmouseout", sprintf("mouseoutHandler(document.getElementById('%s'));", $linkImage->getId()));
        $link2->setContent($linkImage);

        $alignment = '<table border="0" width="100%%" cellspacing="0" cellpadding="0"><tr><td valign="middle">%s</td><td valign="middle" align="right">%s</td></tr></table>';
        $this->setContent(sprintf($alignment, $link->render(), $link2->render()));
    }

}
