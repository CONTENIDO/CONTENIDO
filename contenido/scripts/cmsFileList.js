/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * JavaScript CMS Type File List
 * 
 *
 * @package    CONTENIDO Content Types
 * @version    1.0.0
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.13
 * 
 * {@internal 
 *   created 2009-10-01
 *
 *   $Id$:
 * }}
 * 
 */


/**
 * Initial function which adds all javascript events to FileList edit box and coresponding
 * input elements
 *
 * @param string sFrameId
 * @param string sImageId
 * @param string sPath
 * @param string sSession
 * @param integer iFileListIdArtLang
 * @param integer iFileListId
 */
function addFileListEvents(sFrameId, sImageId, sPath, sSession, iFileListIdArtLang, iFileListId, aFileListData, bIgnoreState) {
	cmsFileList_initialize(sFrameId);
    cmsFileList_loadExternalScripts(sFrameId, sPath, iFileListId);
	cmsFileList_addTabbingEvents(sFrameId);
	cmsFileList_addFrameShowEvent(sFrameId, sImageId);
	cmsFileList_addSaveEvent(sFrameId, iFileListIdArtLang, iFileListId, aFileListData);
	cmsFileList_addFrameCloseEvents(sFrameId);
	cmsFileList_addManualFileListEvent(sFrameId);
	cmsFileList_addClickEvent(sFrameId, iFileListId);
	cmsFileList_setIgnoreExtensions(sFrameId, bIgnoreState);
}

/**
 * Appends the passed node to the end of body tag. This is necessary to have more 
 * control during positioning.
 *
 * @param string sFrameId
 */
function cmsFileList_initialize(sFrameId) {
    $(sFrameId).appendTo($('body'));
}


/**
 * Function extracts an value from FileList form an adds it as hidden to editform for submitting to CONTENIDO
 * Function is called in store proccess of FileList
 *
 * @param string sName
 * @param string sValue
 */
function cmsFileList_appendFileListValue(sName, sValue) {
	$("form[name='editcontent']").append('<input type="hidden" value="'+sValue+'" name="'+sName+'"/>');
}

/**
 *  Function adds event which fades edit form to visible when editbutton is clicked
 *
 * @param string sFrameId
 * @param string sImageId
 */
function cmsFileList_addFrameShowEvent(sFrameId, sImageId) {
	$(sImageId).css('cursor', 'pointer');
	$(sImageId).click(function () {
		$(sFrameId).fadeIn("normal");
		$(sFrameId).css('top', $(sImageId).offset().top);
		$(sFrameId).css('left', $(sImageId).offset().left+$(sImageId).width()+3);
	});
}

/**
 * Function adds tabbling events to menubar of FileList edit form
 * which switchs between the tree tabbing views
 *
 * @param string sFrameId
 */
function cmsFileList_addTabbingEvents(sFrameId) {
	$(sFrameId+" .menu li").css('cursor', 'pointer');
	//add layer click events
	$(sFrameId+" .menu li").click(function(){
        var curAction = $(this);
        
		$(sFrameId+" .menu li").css('font-weight', 'normal');
		
		$(sFrameId+" #manual").css("display", "none");
		$(sFrameId+" #general").css("display", "none");
		$(sFrameId+" #directories").css("display", "none");
		$(sFrameId+" #filter").css("display", "none");
		//add smooth animation
		curAction.css('font-weight', 'bold');
        
        if (curAction.attr('class') == 'manual') {
            $(sFrameId).animate({ 
            height: "450px",
            }, 250 , 'linear', function () {
                $(sFrameId+" #"+curAction.attr('class')).css('height', '386px');
                $(sFrameId+" #"+curAction.attr('class')).fadeIn("normal");
            });
        } else {
            $(sFrameId).animate({ 
            height: "320px",
            }, 250 , 'linear', function () {
                $(sFrameId+" #"+curAction.attr('class')).css('height', '256px');
                $(sFrameId+" #"+curAction.attr('class')).fadeIn("normal");
            });
        }
	});
}

/**
 *  Function adds save event to save button of FileListedit form
 *
 * @param string sFrameId
 * @param integer iFileListIdArtLang
 * @param integer iFileListId
 */
function cmsFileList_addSaveEvent(sFrameId, iFileListIdArtLang, iFileListId, aFileListData) {
	$(sFrameId+' .save_settings').css('cursor', 'pointer');
	$(sFrameId+' .save_settings').click(function() {
		cmsFileList_addManualFileListEntry(sFrameId);

		var sValue = '';
		//iterate over all FileList properties
		for (var i = 0; i < aFileListData.length; i++) {
		  if (aFileListData[i] == 'filelist_incl_subdirectories' || aFileListData[i] == 'filelist_manual' || aFileListData[i] == 'filelist_incl_metadata' ) {
			//special behaviour for checkboxes
			sValue = $(sFrameId+' #'+aFileListData[i]).attr('checked');
		  } else if (aFileListData[i] == 'filelist_extensions') {
			//in case of manual arts implode them use , as separator
			sValue = '';
			$(sFrameId+' #filelist_extensions option').each(function() {
				if ( $(this).attr("selected") != "" ) {
					if (sValue == '') {
						sValue = $(this).attr('value');
					} else {
						sValue = sValue+';'+$(this).attr('value');
					}
				}
			});
		  } else if (aFileListData[i] == 'filelist_manual_files') {
			sValue = '';
			$(sFrameId+' #filelist_manual_files option').each(function() {
				if (sValue == '') {
					sValue = $(this).attr('value');
				} else {
					sValue = sValue+';'+$(this).attr('value');
				}
			});
		  } else {
		    //default value for select boxes and text boxes
			sValue = $(sFrameId + ' #'+aFileListData[i]).attr('value');
		  }
		  cmsFileList_appendFileListValue(aFileListData[i], sValue);
		}
		
		sValue = '';
		$(sFrameId + ' #directories #directoryList div[class="active"]').each(function () {
			sValue = sValue+';'+$(this).find('a[class="on"]').attr('title');
		});
		cmsFileList_appendFileListValue('filelist_directories', sValue);
		
		if ( $(sFrameId + ' #filelist_extensions').attr("disabled") == true ) {
			cmsFileList_appendFileListValue('filelist_ignore_extensions', 'on');			
		} else {
			cmsFileList_appendFileListValue('filelist_ignore_extensions', 'off');
		}

		cmsFileList_appendFileListValue('filelist_action', 'store');
		cmsFileList_appendFileListValue('filelist_id', iFileListId);
		setcontent(iFileListIdArtLang,'0');
	});
}

/**
 * Function adds event for closing FileList edit window and fades box out
 *
 * @param string sFrameId
 */
function cmsFileList_addFrameCloseEvents(sFrameId) {
	//add cancel image event
	$(sFrameId+' .close').css('cursor', 'pointer');
	$(sFrameId+' .close').click(function () {
		$(sFrameId).fadeOut("normal");
	});
	
	//add cancel button event
	$(sFrameId+' .filelist_cancel').css('cursor', 'pointer');
	$(sFrameId+' .filelist_cancel').click(function () {
		$(sFrameId).fadeOut("normal");
	});
}

/**
 * Function loads external styles and jquery ui scripts for FileList dynamically so this scripts were only
 * loaded into CONTENIDO whren this FileList is really used in this article
 *
 * @param string sFrameId
 * @param string sPath
 */
function cmsFileList_loadExternalScripts(sFrameId, sPath, iFileListId) {
    if ($('#cms_filelist').length == 0) {
        $('head').append('<link rel="stylesheet" id="cms_filelist" href="'+sPath+'styles/cms_filelist.css" type="text/css" media="all" />');
	}
	
    conLoadFile(sPath+'scripts/jquery/jquery-ui.js', 'cmsFileList_loadExternalScriptsCallback(\''+sFrameId+'\');');
}

function cmsFileList_loadExternalScriptsCallback(sFrameId) {
    $(sFrameId).draggable({
        handle: '.head'
    });
    $(sFrameId+' .head').css('cursor', 'move');
}

/**
 * Function adds event to add new article to multiple select box for articles
 * Function also checks if article is already in that list
 *
 * @param string sFrameId
 */
function cmsFileList_addManualFileListEvent(sFrameId) {
	$(sFrameId+' #add_file').css('cursor', 'pointer').click(function() {
		cmsFileList_addManualFileListEntry(sFrameId);
	});
}

/**
 * Function adds new article to multiple select box for articles
 * Function also checks if article is already in that list
 *
 * @param string sFrameId
 */
function cmsFileList_addManualFileListEntry(sFrameId) {
	var oArt = $(sFrameId+' #filelist_filename');
	var sFilename = oArt.attr('value');
	var sName = '';
	var bExists = 0;
	
	if (sFilename != '') {
		$(sFrameId+' #filelist_manual_files option').each(function() {
			if (sFilename == $(this).attr('value')) {
				bExists = 1;
			}
		});
		
		$(sFrameId+' #filelist_filename option').each(function() {
			if (sFilename == $(this).attr('value')) {
				sName = $(this).html();
			}
		});
		
		if (bExists == 0) {
			$(sFrameId+' #filelist_manual_files').prepend('<option value="' + sFilename + '">' + sName + '</option>');
		}
	}
}

function cmsFileList_addNaviActions(sFrameId, iFileListId) {
	$(sFrameId+' #manual #directoryList a[class="on"]').parent('div').unbind('click');
	$(sFrameId+' #manual #directoryList a[class="on"]').parent('div').click(function () {
		var dirname = $(this).children('a[class="on"]').attr('title');
		$.ajax({
		  type: "POST",
		  url: sPath+"ajaxmain.php",
		  data: "ajax=filelist&dir=" + dirname + "&id=" + iFileListId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
		  success: function(msg){
			$(sFrameId+' #manual #filelist_filename').replaceWith(msg);
		  }
		});
		
		return false;
	});
	
	$(sFrameId+' #directories #directoryList a[class="on"]').parent('div').unbind('click');
	$(sFrameId+' #directories #directoryList a[class="on"]').parent('div').click(function () {
		if ($(this).hasClass('active')) {
			$(this).removeClass('active');
		} else {
			$(this).addClass('active');
		}
		
		return false;
	});
	
	$(sFrameId+' #directoryList em a').unbind('click');
	$(sFrameId+' #directoryList em a').click(function () {
		var divContainer 	= $(this).parent().parent();
		var dirname 		= $(this).parent('em').parent().find('a[class="on"]').attr('title');
		
		if(divContainer.next('ul').length > 0) {
			divContainer.next('ul').toggle(function () {
				if (divContainer.next('ul').css('display') == 'none') {
					divContainer.parent().addClass('collapsed');
				} else {
					divContainer.parent().removeClass('collapsed');
				}
			});
			
		} else {
			$.ajax({
			  type: "POST",
			  url: sPath+"ajaxmain.php",
			  data: "ajax=dirlist&dir=" + dirname + "&id=" + iFileListId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
			  success: function(msg){
				divContainer.after(msg);
				divContainer.parent('li').removeClass('collapsed');
				cmsFileList_addNaviActions(sFrameId, iFileListId);
			  }
			});
		}

		return false;
	});
}

/**
 * Function adds double click events to all current listed articles for manual FileList
 * in case of a double click this selected article is removed from list
 *
 * @param string sFrameId
 */
function cmsFileList_addClickEvent(sFrameId, iFileListId) {	
	cmsFileList_addNaviActions(sFrameId, iFileListId);
	cmsFileList_addExtensionActions(sFrameId, iFileListId);
	
	if ( $(sFrameId+' #filelist_manual').attr('checked') == true ) {
		$(sFrameId+' #manual_filelist_setting').css("display", "block");
	} else {
		$(sFrameId+' #manual_filelist_setting').css("display", "none");
	}

	$(sFrameId+' #filelist_manual').click(function () {
		$(sFrameId+' #manual_filelist_setting').slideToggle();
	});
	
	if ( $(sFrameId+' #filelist_incl_metadata').attr('checked') == true ) {
		$(sFrameId+' #metaDataList').css("display", "block");
	} else {
		$(sFrameId+' #metaDataList').css("display", "none");
	}
	
	$(sFrameId+' #filelist_incl_metadata').click(function () {
		$(sFrameId+' #metaDataList').slideToggle();
	});
	
	$(sFrameId+' #filelist_manual_files').dblclick(function() {
		$(sFrameId+' #filelist_manual_files option').each(function() {
			if($(this).attr('selected')) {
				$(this).remove();
			};
		});
	});
}

function cmsFileList_addExtensionActions(sFrameId, iFileListId) {
	$(sFrameId+' #filelist_all_extensions').css('cursor', 'pointer');
	$(sFrameId+' #filelist_ignore_extensions').css('cursor', 'pointer');

	$(sFrameId+' #filelist_ignore_extensions').click(function () {
		if ( $(sFrameId+' #filelist_extensions').attr("disabled") == true ) {
			cmsFileList_setIgnoreExtensions( sFrameId, 'false' );
		} else {
			cmsFileList_setIgnoreExtensions( sFrameId, 'true' );
		}
		
		return false;
	});
	
	$(sFrameId+' #filelist_all_extensions').click(function () {
		if ( $(sFrameId+' #filelist_extensions').attr("disabled") == false ) {
			$(sFrameId+' #filelist_extensions option').each(function() {
				$(this).attr("selected", "selected");
			});
		}
	});
}

function cmsFileList_setIgnoreExtensions(sFrameId, bIgnoreState) {
	if ( bIgnoreState == 'false' ) {
		$(sFrameId+' #filelist_extensions').removeAttr("disabled");
		$(sFrameId+' #filelist_ignore_extensions').css("font-weight", "normal").html(sLabelIgnoreExtensionsOff);
	} else {
		$(sFrameId+' #filelist_extensions').attr("disabled", "disabled");
		$(sFrameId+' #filelist_ignore_extensions').css("font-weight", "bold").html(sLabelIgnoreExtensionsOn);
	}
}