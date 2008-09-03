/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido js class for handling  DHTML Calendar in class cms_date for corresponding
 * cms type. It extends DHTML Calender. A select box is added, which allows users to select
 * the format in which the selected date is displayed. For this functionality there are some
 * modifications in DHTML Calender (calender.js) which were commented in this file
 * js calendar class CmsDate.js
 * 
 * Requirements: 
 * 
 *
 * @package    Contenido Backend
 * @version    1.0.0
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.8.7
 * 
 * {@internal 
 *   created 2007-07-14 Bilal Arslan, Timo Trautmann
 *   $Id
 * }}
 * 
 */


var cal_is_open = false;

/**
 *
 *
 * @param {Object} cal is the current calendar object
 * @param {string} date format, what will be change
 */
 
// This function gets called when the end-user clicks on some date.
function selected(cal, date){
    //aEditdata is global variable defines in include.con_editcontent.php for storage
    cal.sel.innerHTML = date; // just update the date in the input field.
    aEditdata[cal.sel.id] = date;
    if (cal.dateClicked) {
        cal.callCloseHandler();
    }
}

/**
 * Destroy or close the calendar
 *
 * @param {Object} cal is the current calendar object
 */
function closeHandler(cal){
	cal.hide(); // hide the calendar
	cal.sel2.style.display = "none";
	cal.destroy();
	cal_is_open = false;
}
/**
 * The CmsDate object constructor. 
 * 
 * @param {string} id, if of input field
 * @param {date_string} format, like "b%,m%,y%"
 * @param {integer} showsTime, 12 or 24
 * @param {boolean} showsOtherMonths, true or false
 * @param {string} sDivSelectId, div id from select-box
 * @param {string} sConPath, contenido path
 * @param {string} sSelectId, select id from select-box
 */
CmsDate = function(id, format, showsTime, showsOtherMonths, sDivSelectId, sConPath, sSelectId){
    // first-time call, create the calendar.
    this.cal = new Calendar(1, null, selected, closeHandler);
    
    // inform it what input field we use  (el)
    this.cal.sel = document.getElementById(id);
    // get the div element from select box
    this.cal.sel2 = document.getElementById(sDivSelectId);
	// get the select box
	this.cal.sel3 = document.getElementById(sSelectId);
    this.format = format;
    this.showsTime = showsTime;
    this.showsOtherMonths = showsOtherMonths;
	this.cal.contenido_path = sConPath;
	this.cal.contenido_call_handler = this;
}

/**
 * This function creats a Calendar.
 */
CmsDate.prototype.showCalendar = function(){
    if (cal_is_open == false) {
	    cal_is_open = true;
	    // uncomment the following line to hide the week numbers
	    // cal.weekNumbers = false;
	    if (typeof this.showsTime == "string") {
	        this.cal.showsTime = true;
	        this.cal.time24 = (this.showsTime == "24");
	    }
	    if (this.showsOtherMonths) {
	        this.cal.showsOtherMonths = true;
	    }
	    
	    this.cal.setRange(1900, 2070); // min/max year allowed.
	   	if (this.cal.sel3.value != "" && this.cal.sel3.value != 0) {
	   		this.cal.setDateFormat(this.cal.sel3.value); // set the specified date format
		}else{
			this.cal.setDateFormat(this.format); // set the specified date format
		}
	    this.cal.create();
	    
	    this.cal.showAtElement(this.cal.sel); // show the calendar
	    // thats important! if do not this, you can not edit html in body, all thinks is disabled
	    window._dynarch_popupCalendar = null;
	    
	    //Select box name
	    this.see();
	}
    return false;
}

/**
 * This function returns position information for current Element
 *
 * @param {Object} oElement is the current node or elemen, what is required.
 */
CmsDate.prototype.getElementPostion = function(oElement){
    var iHeigth = oElement.offsetHeight;
    var iWidth = oElement.offsetWidth;
    var iTop = 0, iLeft = 0;
    while (oElement) {
        iTop += oElement.offsetTop || 0;
        iLeft += oElement.offsetLeft || 0;
        oElement = oElement.offsetParent;
    };
    return [iLeft, iTop, iHeigth, iWidth];
}

/**
 * This function is for select box. It styles (to dock) in right position below the calendar.
 *
 * @param {string} that is the id of select box
 */
CmsDate.prototype.see = function(){
    var idCal = null;
    var aDivs = document.getElementsByTagName('div');
    for (var i = 0; i < aDivs.length; i++) {
        if (aDivs[i].className == 'calendar') {
            idCal = aDivs[i];
            break;
        }
    }
    
    if (idCal) {
        var pos = this.getElementPostion(idCal);
        
        // Get the id of current select box
        var iId = this.cal.sel2;
        iId.style.position = "absolute";
        iId.style.left = pos[0] + "px";
        iId.style.top = pos[1] + pos[2] + "px";
        iId.style.display = "block";
    }
}

/**
 * Changes the specified date format, of currently calendar object.
 *
 * @param {string} format
 */
CmsDate.prototype.changeFormat = function(format){
    this.cal.setDateFormat(format); // set the specified date format
    this.cal.refresh();
}

