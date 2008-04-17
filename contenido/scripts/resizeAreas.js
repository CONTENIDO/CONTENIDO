/*****************************************
*
* $Id: resizeArea.js,v 1.0 2008/03/05 12:43:59 timo.trautmann Exp $
*
* File      :   $RCSfile: resizeArea.js,v $
* Project   : Contenido
* Descr     : Object  ResizeScroll allows to resize two textareas
*                  When one textarea becomes smaller, the other textarea becomes greater  
*                 In this version only vertical resizing is possible
*
* Author    :   $Author: timo.trautmann$
* Modified  :   $Date: 2008/03/05 12:4359 $
*
* © four for business AG, www.4fb.de
******************************************/
//Identify IE
var bMsie = (document.all) ? true : false;

/**
 * Object ResizeScroll - Object for resizing textareas (Constructor)
 *
 * @param string topId - ID of first textarea
 * @param string bottomId - ID of second textarea
 * @param integer minHeight - Minimal width in pixel (If this height is reached, the box can not become smaller)
 */
function ResizeScroll(topId, bottomId, minHeight) {
   
    //save textarea ids
    this.topId = topId;
    this.bottomId = bottomId;
    
    //definition of Object Functions
    this.refreshMousePosition = refreshMousePosition;
    this.triggerClickOn = triggerClickOn;
    this.triggerClickOff = triggerClickOff;
    
    //init position variables
    //actual position of mouse
    this.globalX = 0;
    this.globalY = 0;

    //actual position of mouse, when mouse button is pressed and textareas are resized
    this.globalXOn = 0;
    this.globalYOn = 0;

    this.minHeight = minHeight;

    this.globalClicked = false;

    this.docEl = '';
    
    //set document element (different handling for mozilla and ie). Variable is important for getting mouse position
    if (typeof document.compatMode != "undefined" && document.compatMode != "BackCompat") {
        this.docEl = "documentElement";
    } else {
        this.docEl = "body";
    }
    
    //activate listener for mousemove and mouseup events, call methods of this object
    if(document.layers) {
        document.captureEvents(Event.MOUSEUP);
        document.captureEvents(Event.MOUSEMOVE);
    }
    
    document.onmouseup = this.triggerClickOff;
    document.onmousemove = this.refreshMousePosition;
}

/**
 * Function gets mouse position. When mouse is over the resize bar and the mousebutton is pressed this function also resizes both textareas
 *
 * @param object event - JavaScript event object
 */
function refreshMousePosition(event) {
    //if function is called outside, delegate to object refreshMousePosition
    if (this.globalClicked == null) {
        resizer.refreshMousePosition(event);
        return;
    } 

    //get mpuseposition for Firefox
    var xPos    =  event? event.pageX : window.event.x;
    var yPos    =  event? event.pageY : window.event.y;

    //get mpuseposition for Internet Explorer
    if (document.all && !document.captureEvents) {
        xPos    += document[this.docEl].scrollLeft;
        yPos    += document[this.docEl].scrollTop;
    }
    
    //set position to class variables
    this.globalX = xPos;
    this.globalY = yPos;
    
    //if mouse button is pressed
    if(this.globalClicked == true) {
        //calculate distance between last mouseevent position and this mouseevent position
        xDist = this.globalX-this.globalXOn;
        yDist = this.globalY-this.globalYOn;
        
        //get html objects in order to resize
        var top = document.getElementById('top');
        var bottom = document.getElementById('bottom');
        
        var top_box = document.getElementById('top_box');
        var bottom_box = document.getElementById('bottom_box');

        //resize only, if height does not fall below minHeight 
        if (bottom.offsetHeight-yDist > this.minHeight && top.offsetHeight+yDist > this.minHeight) {       
            //set height of top textarea
            top.style.height = top.offsetHeight+yDist+'px';
            bottom.style.height = bottom.offsetHeight-yDist+'px';
            
            //set height of bottom textarea
            top_box.style.height = top_box.offsetHeight+yDist+'px';
            bottom_box.style.height = bottom_box.offsetHeight-yDist+'px';
            
            //delete text focus
            document.getElementById('scroll').focus();
            
            //save global position (this is the last position wenn mouse button is pressed)
            this.globalXOn = xPos;
            this.globalYOn = yPos;
        }
    }
}

/**
 * Function is called, wenn user presses left mousebutton
 *
 * @param object event - JavaScript event object
 */
function triggerClickOn(e) {
    //if function is called outside, delegate to object refreshMousePosition
    if (this.globalClicked == null) {
        resizer.triggerClickOn(e);
        return;
    } 
    
    //set actual mouse position to pressed mousebutton variables
    this.globalXOn = this.globalX;
    this.globalYOn = this.globalY;
    
    //set actual mouse position to zero
    this.globalX = 0;
    this.globalY = 0;
    
    //mark globalClicked as true
    this.globalClicked = true;
}

/**
 * Function is called, wenn user lets off mousebutton
 *
 * @param object event - JavaScript event object
 */
function triggerClickOff(e) {
    //if function is called outside, delegate to object refreshMousePosition
    if (this.globalClicked == null) {
        resizer.triggerClickOff(e);
        return;
    }
    
    //set pressed mousebutton position to zero
    this.globalXOn = 0;
    this.globalYOn = 0;
    
    //mark globalClicked as false
    this.globalClicked = false;
}

//create instance for ResizeScroll 
//textarea1 id: top / textarea2 id: bottom / minHeight: 75
var resizer = new ResizeScroll('top', 'bottom', 75);

//avoid selection in safari
document.onselectstart = function(e) { 
    if (resizer.globalClicked) {
        return false; 
    } else {
        if (!bMsie) {
            routeEvent(e);
        }
    }
}