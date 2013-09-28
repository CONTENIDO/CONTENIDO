/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * File contains functions for tinymce to handle it as an insight-editor
 *
 *
 * @package    CONTENIDO Backend includes
 * @version    1.0
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.9
 *
 * {@internal
 *   created 2008-09-05
 *   modified 2009-01-23, Ortwin Pinke, BUG-Fix in setFocus first parameter for execInstanceCommand has to be the Id of Tinyobject, not the object itself
 *
 *   $Id$:
 * }}
 *
 */

var first = true;

function myCustomSetupContent(editor_id, body, doc) {
    tinyMCE.get(editor_id).setContent(tinyMCE.get(editor_id).getContent());
}


/**
 * Callback function for tiny which gets a selected image in CONTENIDO
 * image browser, close browser and set this selected image in tiny
 */
function updateImageFilebrowser() {
    //error handling
    if (!fb_handle.left) {
        return;
    }

    if (!fb_handle.left.left_top) {
        return;
    }

    if (!fb_handle.left.left_top.document.getElementById("selectedfile")) {
        return;
    }

    if (fb_handle.left.left_top.document.getElementById("selectedfile").value != "") {
        //get selected image from popup and close it
        fb_win.document.forms[0].elements[fb_fieldname].value = fb_handle.left.left_top.document.getElementById("selectedfile").value;

        fb_handle.close();
        window.clearInterval(fb_intervalhandle);

        //set this selected image in tiny
        if (fb_win.ImageDialog != null && fb_win.ImageDialog.showPreviewImage) {
            fb_win.ImageDialog.showPreviewImage(fb_win.document.forms[0].elements[fb_fieldname].value);
        }
    }
}


/**
 * Function converts a given content string (callback of tiny)
 *
 * @param string type - type of content
 * @param string value - string of content
 *
 * @return string - converted content
 */
function CustomCleanupContent(type, value) {
    switch (type) {
        case "get_from_editor":
        case "insert_to_editor":
            // Remove xhtml styled tags
            value = value.replace(/[\s]*\/>/g,'>');
            break;
    }

    return value;
}

/**
 * Function stores content of current opened tiny into global var aEditdata
 * this content is later stored by submitting setcontent()
 * Notice: Global js vars were defined in include.con_editcontent.php
 */
function storeCurrentTinyContent() {
    //store last tiny changes if tiny is still open
    var editor = tinyMCE.getInstanceById(active_id);

    if (editor) {
        var content          = editor.getContent();
        content              = content.replace(frontend_path, '');
        aEditdata[active_id] = content;
    }
}

/**
 * Function gets all content stored in aEditdata and sends it as string to server
 * for storage it into database
 * Notice: Global js vars were defined in include.con_editcontent.php
 *
 * @param integer idartlang - idartlang of article which is currently edited
 * @param string act - actionurl of form (optional)
 */
function setcontent(idartlang, act) {
    //do not ask user for storage
    bCheckLeave = false;

    //check if there is still a tiny open and get its content
    storeCurrentTinyContent();

    var str = '';

    //forach content in js array aEditdata
    for (var sId in aEditdata) {
        //check if content has changed, if it has serialize it to string
        if (aEditdataOrig[sId] != aEditdata[sId]) {
            var data = sId.split("_");

            // data[0] is the fieldname * needed
            // data[1] is the idtype
            // data[2] is the typeid * needed

            // build the string which will be send
            str += buildDataEntry(idartlang , data[0] , data[2] , prepareString(aEditdata[sId]));
        }
    }

    // set the string
    document.forms.editcontent.data.value = str + document.forms.editcontent.data.value;

    // set the action string
    if (act != 0) {
        document.forms.editcontent.action = act;
    }

    // submit the form
    document.forms.editcontent.submit();
}

/**
 * Function escapes chars in content for inserting into submit string.
 * An empty content &nbsp; is replaced by %$%EMPTY%$%
 * | were seperators in string and were replaced by %$%SEPERATOR%$%
 *
 * @param string aContent - content which should be escaped
 * @return string - string with escaped chars
 */
function prepareString(aContent) {
    if (aContent == "&nbsp;" || aContent == "") {
        aContent = "%$%EMPTY%$%";
    } else {
        // if there is an | in the text set a replacement chr because we use it later as isolator
        while (aContent.search(/\|/) != -1) {
            aContent = aContent.replace(/\|/,"%$%SEPERATOR%$%");
        }
    }

    return aContent;
}

/**
 * Function serializes given args to string and return it. Seperator is |
 *
 * @param integer idartlang - idartlang of article which is currently edited
 * @param string type - type name of content (CMS_HTML)
 * @param integer typeid - id of content (CMS_HTML[4] => 4)
 * @param string value - value of content
 * @return string - serialized vars
 */
function buildDataEntry(idartlang, type, typeid, value) {

    return idartlang +'|'+ type +'|'+ typeid +'|'+ value +'||';

}

/**
 * Function adds a custom content type to submit strings, adds all other content
 * information and submits it to server using setcontent()
 *
 * @param integer idartlang - idartlang of article which is currently edited
 * @param string type - type name of content (CMS_HTML)
 * @param integer typeid - id of content (CMS_HTML[4] => 4)
 * @param string value - value of content
 */
function addDataEntry(idartlang, type, typeid, value) {

    document.forms.editcontent.data.value = (buildDataEntry(idartlang, type, typeid, prepareString(value)));

    setcontent(idartlang, '0');
}

/**
  * Function closses currently opened tiny
  *
  */
function closeTiny() {
    //check if tiny is currently open
    if (active_id && tinyMCE.getInstanceById(active_id)) {
        //save current tiny content to js var
        storeCurrentTinyContent();

        //if content was empty set div height. Empty divs were ignored by most browsers
        if (aEditdata[active_id] == '') {
            //document.getElementById(active_id).style.height = '15px';
        }

        //close current open tiny and set active vars to null
        var tmpId = active_id;
        setTimeout(function() {
            if (tmpId) {
                tinyMCE.execCommand('mceRemoveControl', false, tmpId);
            }
        }, 0);

        active_id     = null;
        active_object = null;
    }
}

/**
 * Function swaps tiny to a content editable div. If tiny is already open on
 * another div, this tiny was swapped to current div by closing it first
 * tiny swaps on click
 * Notice: Global js vars were defined in include.con_editcontent.php
 *
 * @param object obj - div object which was clicked
 */
function swapTiny(obj) {
    //check if tiny is currently open
    closeTiny();

    //rest tinymce configs defined in include.con_editcontent.php
    tinyMCE.settings = tinymceConfigs;

    //set clicked object as active object
    active_id     = obj.id;
    active_object = obj;

    //show thiny and focus it
    if (active_id) {

        tinyMCE.execCommand('mceAddControl', false, active_id);
        setFocus();

        //remove height information of clicked div
        document.getElementById(active_id).style.height = '';
    }
}

/**
 * Function sets focus on toggled editor if its loading proccess was completed
 *
 */
function setFocus() {
    var activeTinyId = tinyMCE.getInstanceById(active_id);

    if (!activeTinyId) {
        window.setTimeout('setFocus()', 50);
    } else {
        tinyMCE.execInstanceCommand(activeTinyId, 'mceFocus', false);
    }
}

/**
 * Callback function of Tiny which opens CONTENIDO file browser in popup
 * Notice: Global js vars were defined in include.con_editcontent.php
 * (image_url, file_url, flash_url, media_url)
 *
 * @param string field_name - Name of relevant HTML field
 * @param string url - Tiny default but not used in function
 * @param string type - Type of content to add (image, file, ..)
 * @param Object win - Corresponding window object
 */
function myCustomFileBrowser(field_name, url, type, win) {

    switch (type) {
        case "image":
            fb_handle         = window.open(image_url, "filebrowser", "dialog=yes,resizable=yes");
            fb_fieldname      = field_name;
            fb_win            = win;
            fb_intervalhandle = window.setInterval("updateImageFilebrowser()", 250);
            break;
        case "file":
            fb_handle         = window.open(file_url, "filebrowser", "dialog=yes,resizable=yes");
            fb_fieldname      = field_name;
            fb_win            = win;
            fb_intervalhandle = window.setInterval("updateImageFilebrowser()", 250);
            break;
        case "flash":
            fb_handle         = window.open(flash_url, "filebrowser", "dialog=yes,resizable=yes");
            fb_fieldname      = field_name;
            fb_win            = win;
            fb_intervalhandle = window.setInterval("updateImageFilebrowser()", 250);
            break;
        case "media":
            fb_handle         = window.open(media_url, "filebrowser", "dialog=yes,resizable=yes");
            fb_fieldname      = field_name;
            fb_win            = win;
            fb_intervalhandle = window.setInterval("updateImageFilebrowser()", 250);
            break;
        default:
            alert(type);
            break;
    }

}

/**
 * Function like storeCurrentTinyContent() which stores original content to
 * global array aEditdataOrig for a later decision if content has changed
 *
 * @param string sContent - original content string
 */
function updateContent(sContent) {
    //if original content was already set do not overwrite
    //this happens if tiny is reopened on same content
    if (aEditdataOrig[active_id] == undefined) {
        sContent = sContent.replace(frontend_path, '');
        aEditdataOrig[active_id] = sContent;
    }
}

// @deprecated  Use leaveCheck()
function leave_check() {
    leaveCheck();
}

/**
 * Function checks if content has changed if user leaves page.
 * Then he has the possiblity to save this content. So there is no
 * guess, that changes get lost.
 * Notice: Global js vars were defined in include.con_editcontent.php
 * (aEditdata, aEditdataOrig, sQuestion, iIdartlang)
 */
function leaveCheck() {
    //If tiny is still open store its content
    storeCurrentTinyContent();

    //Check if any content in aEditdata was changed
    var bAsk = false;
    for (var sId in aEditdata) {
        if (aEditdataOrig[sId] != aEditdata[sId]) {
            bAsk = true;
        }
    }

    //If content was changed and global var bCheckLeave is set to true
    //ask user if he wants to save content
    //ex bCheckLeave is false when user clicks save button. This is also
    //a case in which he leaves this page but by pressing save button he
    //also saves all changes
    if (bAsk && bCheckLeave) {
        check = confirm(sQuestion);
        //If he wants to save content call function setcontent();

        if (check == true) {
            setcontent(iIdartlang, '0');
        }
    }
}