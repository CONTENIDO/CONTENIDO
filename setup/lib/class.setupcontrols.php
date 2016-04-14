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

    protected $_sClickImage;

    protected $_sMouseoverClickImage;

    protected $_sMouseoverSrc;

    /**
     * cHTMLAlphaImage constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->setAlt("");
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     */
    public function cHTMLAlphaImage() {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct();
    }

    public function setMouseover($sMouseoverSrc) {
        $this->_sMouseoverSrc = $sMouseoverSrc;
    }

    public function setSwapOnClick($sClickSrc, $sMouseoverClickSrc) {
        $this->_sClickImage = $sClickSrc;
        $this->_sMouseoverClickImage = $sMouseoverClickSrc;
    }

    public function toHTML() {
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

    /**
     * cHTMLErrorMessageList constructor.
     */
    public function __construct() {
        $this->_oTable = new cHTMLTable();
        $this->_oTable->setWidth("100%");
        parent::__construct();
        $this->setClass("errorlist");
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     */
    public function cHTMLErrorMessageList() {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct();
    }

    public function setContent($content) {
        $this->_oTable->setContent($content);
    }

    public function toHTML() {
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

    /**
     * cHTMLFoldableErrorMessage constructor.
     * @param $title
     * @param $message
     * @param bool $icon
     * @param bool $iconText
     */
    public function __construct($title, $message, $icon = false, $iconText = false) {
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

        $this->_oTitle->setContent($title);
        $this->_oTitle->setStyle("cursor:pointer;");
        $this->_oTitle->attachEventDefinition("showhide", "onclick", "alimg = document.getElementById('" . $alphaImage->getId() . "'); aldiv = document.getElementById('" . $this->_oMessage->getId() . "'); showHideMessage(alimg, aldiv); clickHandler(alimg);");

        $this->_oMessage->setContent($message);
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
        if ($icon !== false) {
            $this->_oIconImg->setSrc($icon);

            if ($iconText !== false) {
                $this->_oIconImg->setAlt($iconText);
            }

            $this->_oIcon->setContent($this->_oIconImg);
        } else {
            $this->_oIcon->setContent("&nbsp;");
        }

        parent::__construct();
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param $title
     * @param $message
     * @param bool $icon
     * @param bool $iconText
     */
    public function cHTMLFoldableErrorMessage($title, $message, $icon = false, $iconText = false) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct($title, $message, $icon, $iconText);
    }

    public function toHTML() {
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

    /**
     * cHTMLInfoMessage constructor.
     * @param $title
     * @param $message
     */
    public function __construct($title, $message) {
        $this->_oTitle = new cHTMLTableData();
        $this->_oMessage = new cHTMLTableData();

        $this->_oTitle->setContent($title);
        $this->_oTitle->setClass("entry_nowrap");
        $this->_oTitle->setAttribute("nowrap", "nowrap");
        $this->_oTitle->setWidth(1);
        $this->_oTitle->setVerticalAlignment("top");
        $this->_oMessage->setContent($message);
        $this->_oMessage->setClass("entry_nowrap");

        parent::__construct();
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param $title
     * @param $message
     */
    public function cHTMLInfoMessage($title, $message) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct($title, $message);
    }

    public function toHTML() {
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

    /**
     * cHTMLLanguageLink constructor.
     * @param string $langcode
     * @param string $langname
     * @param string $stepnumber
     */
    public function __construct($langcode, $langname, $stepnumber) {
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

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param $langcode
     * @param $langname
     * @param $stepnumber
     */
    public function cHTMLLanguageLink($langcode, $langname, $stepnumber) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct($langcode, $langname, $stepnumber);
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

    /**
     * cHTMLButtonLink constructor.
     * @param string $href
     * @param string $title
     */
    public function __construct($href, $title) {
        parent::__construct();

        $this->setStyle("height:40px;width:180px;");

        $link = new cHTMLLink($href);
        $link->setAttribute("target", "_blank");
        $link->setClass("nav navLabel");
        $link->setContent($title . "<span>&raquo;</span>");

        $this->setContent($link->render());
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param $href
     * @param $title
     */
    function cHTMLButtonLink($href, $title) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct($href, $title);
    }

}
