/*****************************************
*
* $Id: group_association.js,v 1.0 2008/02/15 13:41:52 timo.trautmann Exp $
*
* File      :   $RCSfile: group_association.js,v $
* Project   : Contenido
* Descr     : File contains java script functions for filtering users in select areas, handling short keys and submitting form
*                This functions are used in template template.grouprights_memberselect.html
*
* Author    :   $Author: timo.trautmann$
* Modified  :   $Date: 2008/03/13 16:37:44 $
*
* © four for business AG, www.4fb.de
******************************************/


var keycode = 0; //last pressed key
var addAction = ''; //contenidoaction for adding user to group - (different fpr frontentgroups and backendgroups)
var deleteAction = ''; //contenidoaction for removing user from group - (different fpr frontentgroups and backendgroups)

/**
 * Initialization of previous defined variables 
 * 
 * @param string add - adding user contenido action
 * @param string del - removing user contenido action
 * 
 */
function init(add, del) {
    addAction = add;
    deleteAction = del;
}

/**
 * Function submits form when users were added to group or removed from group
 * 
 * @param string isAdded - contenido action string
 * 
 */
function setAction(isAdded) {
    var selectId = null;
    //case of adding new members
    if (isAdded == addAction) { 
        selectId = 'newmember';
        document.group_properties.action.value = addAction;
    //case of removing existing members
    } else {
        selectId = 'user_in_group';
        document.group_properties.action.value = deleteAction;
    }

    var sSelectBox = document.getElementById(selectId);
    //only submit form, if a user is selected
    if (sSelectBox.selectedIndex != -1) {
        document.group_properties.submit();
    }
}

/**
 * Function filters entries in select box and shows only relevant users for selection
 * 
 * @param string id - id of textbox, which contains the search string
 * 
 */
function filter (id) {
    //get search string ans buid regular expression
    var sFilterValue = document.getElementById(id).value;
    var oReg = new RegExp(sFilterValue,"gi");
    
    //build id of corresponding select box
    var sSelectId = id.replace(/_filter_value/, '');
    
    //get select box and corresponding options
    var sSelectBox = document.getElementById(sSelectId);
    var oOptions = sSelectBox.getElementsByTagName('option');
   
    //remove all options
    var iLen = oOptions.length;
    for (var i=0; i <iLen; i++) {
        sSelectBox.removeChild(oOptions[0]);
    }
    
    //get all options which where avariable in hidden select box
    var sSelectBoxAll = document.getElementById('all_'+sSelectId);
    var oOptionsAll = sSelectBoxAll.getElementsByTagName('option');
    
    //iterate over all hidden options
    var count = 0;
    for (var i=0; i<oOptionsAll.length; i++) {
        //get the label of the option
        var label = oOptionsAll[i].firstChild.nodeValue;
        
        //if option label matches to search string
        if (label.match(oReg)) {
            //generate new option element, fill it with the hidden values and append it to the select box which is viewable
            var newOption = document.createElement('option');
            newOption.value = oOptionsAll[i].value;
            newOption.innerHTML = label;
            newOption.disabled = false;
            sSelectBox.appendChild(newOption);
            count++;
        }
    }
    
    //if there are no options, deactivate corresponding move button
    if(count == 0) {
        document.getElementById(sSelectId+'_button').disabled = true;
    } else {
        document.getElementById(sSelectId+'_button').disabled = false;
    }   
}

/**
 * Function is callend when user types into the filter inputs
 * 
 * @param string id - id of textbox, which contains the search string
 * 
 */
function keyHandler(id)  {
    //if user pressed enter key into filter input, js function filter is called
    if (keycode == 13) {
        filter(id);
    }
}

/**
 * Function is callend when user presses a key
 * 
 * @param object event - event object
 * 
 */
function setKeyCode (event) {
    if (!event)
        event = window.event;
    if (event.keyCode) {
        //for ie: store keycode, which is pressed into global variable
        keycode = event.keyCode;
    } else if (event.which) {
        //for mozilla: store keycode, which is pressed into global variable
        keycode = event.which;
    }
}

//Activate listener, which calls function setKeyCode when user presses a key on keyboard
document.onkeydown = setKeyCode;