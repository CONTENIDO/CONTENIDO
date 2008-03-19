/* ***** BEGIN LICENSE BLOCK *****
 * Licensed under Version: MPL 1.1/GPL 2.0/LGPL 2.1
 * Full Terms at http://mozile.mozdev.org/license.html
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Playsophy code.
 *
 * The Initial Developer of the Original Code is Playsophy
 * Portions created by the Initial Developer are Copyright (C) 2002-2003
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

/**********************************************************************************
 * mozileModify.js V0.46: modify this file to specialize Mozile. 
 * 
 * POST06:
 * - may be replaced with a configuration file/something set in preferences 
 **********************************************************************************/

/**
 * Save changes to a remote server or to a local file
 *
 * This method is called by the "save" button in the Mozile toolbar. Change this
 * method to allow users to save changes made through Mozile.
 *
 * editableArea: the topmost element in the currently selected editable area
 */
function mozileSave()
{
	// call default  
	__mozileDummySave();
	
	// one alternative: save the document as a whole.
	// __mozileSaveToFile();
}

/**
 * Dummy save just shows what in the editable area needs to be saved
 * calls alert to show what should be saved: mozile deployer should replace this 
 * with a post, ftp or XML rpc call to a CMS.
 */
function __mozileDummySave()
{
	var cssr = window.getSelection().getEditableRange();
	if(!cssr)
	{
		alert("*mozileModify.js:mozileSave: this default implementation only works if the current selection is in an editable area");
		return;
	}
		
	var editableArea = cssr.top;

	// get the id of the editable area - this would tell a remote CMS where the data
	// is coming from
	var editableAreaId = editableArea.id;

	// get the contents of the editable area
	var dataToSaveRange = document.createRange();
	dataToSaveRange.selectNodeContents(editableArea);
	var dataToSave = dataToSaveRange.cloneContents();

	// Note: despite its name, the new DOM 3 method, "saveXML" doesn't save. It 
	// returns the (X)HTML or XML information you need to save in order to persist
	// an element's contents. 
	alert("*mozileModify.js:mozileSave: replace this implementation with a CMS specific equivalent*\nData from editable element <"+ editableAreaId + "> to save/post/ftp:\n" + documentSaveXML(dataToSave));
}

/**
 * Alternative save - save document as a whole
 */
function __mozileSaveToFile()
{
	// first nix the toolbar
	ptbdisable();

	var mfp = mozilla.createFilePicker(MozFilePicker.MODE_SAVE, "save to local file");
	if(mfp)
	{
		mfp.addFilter(MozFilePicker.FILTER_HTML);
		if(mfp.promptUser())
		{
			var mf = mfp.file;
			var documentContents = document.saveXML(document);
			mf.write(documentContents); // to do: remove toolbar
		}
	}
	else
	{	
		alert("mozileSave: can't save-to-file because Mozilla doesn't allow remote scripts to launch its native file picker dialog. Either run Mozile locally or wait until it is packaged as an extension. For more information, see http://mozile.mozdev.org/use.html."); 
	}

	ptbenable();
}