 /**
 * $Id: del.js,v 1.2 2007/06/24 17:45:56 bjoern.behrens Exp $
 *
 * @author Moxiecode - based on work by Andrew Tetlaw
 * @copyright Copyright � 2004-2007, Moxiecode Systems AB, All rights reserved.
 */

function preinit() {
	// Initialize
	tinyMCE.setWindowArg('mce_windowresize', false);
}

function init() {
	tinyMCEPopup.resizeToInnerSize();
	SXE.initElementDialog('del');
	if (SXE.currentAction == "update") {
		setFormValue('datetime', tinyMCE.getAttrib(SXE.updateElement, 'datetime'));
		setFormValue('cite', tinyMCE.getAttrib(SXE.updateElement, 'cite'));
		SXE.showRemoveButton();
	}
}

function setElementAttribs(elm) {
	setAllCommonAttribs(elm);
	setAttrib(elm, 'datetime');
	setAttrib(elm, 'cite');
}

function insertDel() {
	var elm = tinyMCE.getParentElement(SXE.focusElement, 'del');

	tinyMCEPopup.execCommand('mceBeginUndoLevel');
	if (elm == null) {
		var s = SXE.inst.selection.getSelectedHTML();
		if(s.length > 0) {
			tinyMCEPopup.execCommand('mceInsertContent', false, '<del id="#sxe_temp_del#">' + s + '</del>');
			var elementArray = tinyMCE.getElementsByAttributeValue(SXE.inst.getBody(), 'del', 'id', '#sxe_temp_del#');
			for (var i=0; i<elementArray.length; i++) {
				var elm = elementArray[i];
				setElementAttribs(elm);
			}
		}
	} else {
		setElementAttribs(elm);
	}
	tinyMCE.triggerNodeChange();
	tinyMCEPopup.execCommand('mceEndUndoLevel');
	tinyMCEPopup.close();
}

function removeDel() {
	SXE.removeElement('del');
	tinyMCEPopup.close();
}