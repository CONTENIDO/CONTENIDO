<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO class for handling cms type date. This cms type allows to select date with DHTML Calendar
 * it is also possible to select a format in which the date is displayed. It is also possible to display date
 * and time. This class is an example how to handle CONTENIDO cms type in a class. In database this class
 * is called in cms type cms_date. JavaScript handling of DHTML Calendar is handled in CONTENIDO
 * js calendar class CmsDate.js
 *
 * Requirements:
 * @con_php_req 5.0
 * @con_notice  Js class CmsDate.js required
 *
 * @package    CONTENIDO Backend
 * @version    1.0.3
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.8.7
 *
 * {@internal
 *   created 2007-07-14 Bilal Arslan, Timo Trautmann
 *   modified 2008-07-28 Bilal Arslan, added new Date format timestamp
 *   modified 2009-04-14 OliverL, added class in Edit- & Save-Link
 *   $Id
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class Cms_Date
{

   /**
    * Contains user input of cms_type
    * @access private
    */
    private $sContent;

   /**
    * The format value for the calendar
    * @access private
    */
    private $aFormat;

   /**
    * The number of cms_date[?] parameter
    * @access private
    */
    private $iNumberOfCms;

   /**
    * The output javascript
    * @access private
    */
    private $sJS;

   /**
    * The the static variable, controlls howmuch object exists
    * @access private
    */
    private static $iNumOutput;

   /**
    * The CONTENIDO global
    * @access private
    */
    private $iIdArtLang;

   /**
    * The CONTENIDO Edit link
    * @access private
    */
    private $sEditLink;

   /**
    * The CONTENIDO global
    * @access private
    */
    private $aCfg;

   /**
    * The CmsDate object for call cmsDate functions
    * @access private
    */
    private $sCalName;

    /**
     * The CONTENIDO database object
     * @access private
     */
    private $oDB;

    /**
     * Input Field Id
     * @access private
     */
    private $sEditAreaId;

   /**
    * the id of Div Element select box
    * @access private
    */
    private $sDivSelectId;

   /**
    * The id of select box
    * @access private
    */
    private $sSelectId;

   /**
    * Total count of cms_date
    * @access private
    */
    private $iTotalCount;

    /**
     * Language of CONTENIDO
     * @access private
     */
    private $sContenidoLang;

    /**
     * The Cms_Date object constructor, initializes class variables
     *
     * @param {string} $sContent
     * @param {integer} $iNumberOfCms
     * @param {integer} $iIdArtLang
     * @param {string} $sEditLink
     * @param {Object} $aCfg
     * @param {Object} $aDB
     */
    public function __construct($sContent, $iNumberOfCms, $iIdArtLang, $sEditLink, $aCfg, $aDB, $iTotalCount, $sContenidoLang)
    {
        $this->iNumberOfCms = $iNumberOfCms;
        $this->iIdArtLang = $iIdArtLang;
        $this->sEditLink = $sEditLink;
        $this->aCfg = $aCfg;

        // static number for objects
        Cms_Date::$iNumOutput++;
        $this->sCalName = 'oCalId' . Cms_Date::$iNumOutput;
        $this->oDB = $aDB;
        $this->sEditAreaId = '';
        $this->sDivSelectId = '';
        // if is empty, fill it with space character. Thats important for CONTENIDO input area!
        $this->sContent = ($sContent == '') ? '&nbsp;' : urldecode($sContent);
        $this->sSelectId = '';
        $this->iTotalCount = $iTotalCount;
        $this->sJS = '';
        $this->sContenidoLang = $sContenidoLang;
    }

    /**
     * Edit and View all Widgets. This function is calling in edit mode
     *
     * @return the all widgets
     */
    public function getAllWidgetEdit()
    {
        $this->sContent = urldecode($this->sContent);
        $this->sContent = addslashes($this->sContent);
        $this->sContent = str_replace("\\'", "'", $this->sContent);
        $this->sContent = str_replace("\$", '\\$', $this->sContent);

        // Render all Widgetes
        $this->sContent =  $this->getEditingField() .$this->getJsScript() . $this->getOkButton();
        return $this->sContent;
    }

    /**
     * This function modified cms_content before it is displayed in frontend.
     * In this case this function is a dummy.
     *
     * @return  Returns user input of cms_type
     */
    public function getAllWidgetView()
    {
        return $this->sContent;
    }

    /**
     * This function set the date format for select-box.
     * Function displays current day format.
     * For edit or add formats modified this function.
     *
     * @return the format as an array
     */
    private function getDateFormats()
    {
        $sMonthName = '';
        $sDayName = '';
        $sMonthName = getCanonicalMonth(date('m'));
        $sDayName = getCanonicalDay(date('w'));
        $iDay = date('d');
        $iMonth = date('m');
        $iYear = date('Y');
        $iYearShort = date('y');

        $this->aFormat = array(
           '0' 					=> i18n('Please choose a format'),
           'd.m.yy' 			=> date('d.m.Y'),
           'DD, d.m.yy' 		=> $sDayName . ', ' . $iDay . '.' . $iMonth . '.' . $iYear,
           'd. MM yy' 			=> $iDay . '. ' . $sMonthName . ' '. $iYear,
           'yy-m-d' 			=> date('Y-m-d'),
           'd/MM/yy' 			=> $iDay . '/' . $sMonthName . '/' . $iYear,
           'd/m/yy' 			=> date('d/m/y'),
           'MM y' 				=> $sMonthName . ' ' . $iYearShort,
           'MM-y' 				=> $sMonthName . '-' . $iYearShort,
           'd.m.yy h:m' 		=> date('d.m.Y H:i'),
           'm.d.yy h:m:s' 		=> date('d.m.Y H:i:s'),
           'h:m' 				=> date('H:i'),
           'h:m:s' 			=> date('H:i:s'),
           'h:m TT' 			=> date('h:i A'),
           '#h:m:s TT' 			=> date('h:i:s A'),
           '@' 					=> 'Timestamp'
        );

        return $this->aFormat;
    }

    private function getFormatSelect()  {
    	$options = "";
    	
    	foreach($this->getDateFormats() as $format) {	
    		$options .= sprintf('<option value="%s">%s</option>', $format[0], $format[1]);
    	}
    	
    	return '<select style=\"float:right;\" class=\"cms_date_format_select\">'.$options.'</select>';
    }
    
    /**
     * This functions given all js-script, what we need for calendar.
     * Set all js-script here
     *
     * @return (String) js-script
     */
    public function getJsScript()
    {
        $tpl = new Template();
        $path = $this->aCfg['path']['contenido_fullhtml'];
        $lang = $this->getLanguageContenido();
        
        $tpl->set('s', 'CALL_NAME', $this->sCalName);
        $tpl->set('s', 'INDEX_OF_CMS_DATE', $this->iNumberOfCms);
        $tpl->set('s', 'PATH_CONTENIDO_FULLHTML', $path);
        $tpl->set('s', 'PATH_TO_CALENDER_PIC', $path . $this->aCfg['path']['images'] . 'calendar.gif');
        //$tpl->set('s', 'SELECT_WITH_ALL_FORMATS', $this->getFormatSelect());
        $tpl->set('s', 'ID_TYPE', $this->oDB->f('idtype'));  
        //$tpl->set('s', 'ALL_OPTIONS', json_encode($this->getDateFormats()));
        //$tpl->set('s', 'LABEL_FORMAT', i18n('Formate'));
        $load = "conLoadFile('".$path."scripts/jquery/jquery.ui.datepicker-".$lang.".js', 'load2_$this->sCalName()');";
        //add timepicker language
        if($this->getLanguageContenido() != 'en') {
        	$tpl->set('s', 'LOAD_LANG', $load);
        }else{
        	$tpl->set('s', 'LOAD_LANG', '');
        }
        
       
       $this->sJS = $tpl->generate($this->aCfg['path']['contenido'] . 'templates/standard/template.cms_date.html', true);
       $this->sJS = addslashes($this->sJS);
       $this->sJS = str_replace("\\'", "'", $this->sJS);

       return $this->sJS;
    }

    /**
     * This function builds a CONTENIDO CMS Widget.
     * A Button for Calendar.
     *
     * @return (String)calendar Button widget
     */
    private function getCalendarButton()
    {
        // html link for save
        $oEditAnchor = new cHTMLLink();
        $oEditAnchor->setClass('CMS_DATE_' . ($this->iNumberOfCms) . '_EDIT CMS_LINK_EDIT');
        $oEditAnchor->setLink("javascript:setcontent('$this->iIdArtLang','" . $this->sEditLink . "');");
        // Calendar Button
        $oEditButton = new cHTMLImage();
        $oEditButton->setSrc($this->aCfg['path']['contenido_fullhtml'] . $this->aCfg['path']['images'] . 'calendar.gif');
        $oEditButton->setBorder(0);
        $oEditButton->setStyleDefinition('margin-right', '2px');
        $oEditButton->setClass('CMS_DATE_' . ($this->iNumberOfCms) . '_EDIT CMS_LINK_EDIT');
        $oEditButton->setID('trigger_start' . $this->iNumberOfCms);
       // $oEditButton->setEvent('Click', "$this->sCalName.showCalendar()");
        $oEditAnchor->setContent($oEditButton);
        $sFinalEditButton = $oEditButton->render();
        $sFinalEditButton = addslashes($sFinalEditButton);
        $sFinalEditButton = str_replace("\\'", "'", $sFinalEditButton);

        return $sFinalEditButton;
    }

    /**
     * This function builds a CONTENIDO CMS Widget.
     * A Button for Submit (OK-Button).
     *
     * @return (String)Ok Button widget
     */
    private function getOkButton()
    {
        // Ok Image
        $oSaveAnchor = new cHTMLLink();
        $oSaveAnchor->setClass('CMS_DATE_' . ($this->iNumberOfCms) . '_SAVE CMS_LINK_SAVE');
        $oSaveAnchor->setLink("javascript:setcontent('" . $this->iIdArtLang . "','0')");
        $oSaveButton = new cHTMLImage();
        $oSaveButton->setSrc($this->aCfg['path']['contenido_fullhtml'] . $this->aCfg['path']['images'] . 'but_ok.gif');
        $oSaveButton->setBorder(0);
        $oSaveAnchor->setContent($oSaveButton);
        $sFinalSaveButton = $oSaveAnchor->render();
        $sFinalSaveButton = addslashes($sFinalSaveButton);
        $sFinalSaveButton = str_replace("\\'", "'", $sFinalSaveButton);

        return $sFinalSaveButton;
    }

    /**
     * This function builds a CONTENIDO CMS Widget.
     * A Editing Field or input field what we use for calendar.
     *
     * @return (String) Editing Field widget
     */
    private function getEditingField()
    {
        // Inline Editing Field
        $oDivBox = new cHTMLDiv();
        $oDivBox->setStyleDefinition('border', '1px dashed #dddddd');
        $this->sEditAreaId  = 'DATE_' . $this->oDB->f('idtype') . '_' . $this->iNumberOfCms;
        $oDivBox->setId($this->sEditAreaId);
        #$oDivBox->updateAttributes(array ('contentEditable' => 'true'));
        $oDivBox->setContent('_REPLACEMENT_');
        $sFinalEditingDiv = $oDivBox->render();
        $sFinalEditingDiv = addslashes($sFinalEditingDiv);
        $sFinalEditingDiv = str_replace("\\'", "'", $sFinalEditingDiv);
        $sFinalEditingDiv = str_replace('_REPLACEMENT_', $this->sContent, $sFinalEditingDiv);

       
        return $sFinalEditingDiv;
    }

    /**
     * This function builds a CONTENIDO CMS Widget.
     * A Select-Box what is given date formats.
     * In auoFill we use the function getDateFormats:
     * For edit or add formats modified this function.
     *
     * @return (String)Select-Box widget
     */
    private function getSelectBox()
    {
        // Div Format SelectBox
        $oMenueDiv = new cHTMLDiv();
        $this->sDivSelectId = "menue-$this->iNumberOfCms";
        $oMenueDiv->setId($this->sDivSelectId);
        $oMenueDiv->setStyleDefinition('padding', '4px');
        $oMenueDiv->setStyleDefinition('background-color', '#ccc');
        $oMenueDiv->setStyleDefinition('display', 'none');
        $oMenueDiv->setStyleDefinition('width', '239px');

        // Select Box
        $this->sSelectId = 'select_' . $this->iNumberOfCms;
        $oSelectMenue = new cHTMLSelectElement("select-format-$this->iNumberOfCms", '', $this->sSelectId);
        $oSelectMenue->setEvent('Change', "$this->sCalName.changeFormat(value);");
        $oSelectMenue->autoFill($this->getDateFormats());
        $oSelectMenue->setStyle('font-size: 11px; width:239px; font-family: Verdana,Arial,sans-serif');
        $oMenueDiv->setContent($oSelectMenue);
        $sFinalSelectBox = $oMenueDiv->render();
        $sFinalSelectBox = addslashes($sFinalSelectBox);
        $sFinalSelectBox = str_replace("\\'", "'", $sFinalSelectBox);

        return $sFinalSelectBox;
    }

    /**
     * This function gives formatted current language shortcut
     *
     * @return (String)Current Language of CONTENIDOs
     */
    private function getLanguageContenido()
    {
        $sLang = '';
        switch ($this->sContenidoLang) {
            case'de_DE':
                $sLang = 'de';
                break;
            case'en_US':
                $sLang = 'en';
                break;
            default:
            break;
        }

        return $sLang;
    }

}

?>