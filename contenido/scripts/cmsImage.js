/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * JavaScript CMS Type Image
 * 
 *
 * @package    Contenido Content Types
 * @version    1.0.0
 * @author     Fulai Zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.13
 * 
 * {@internal 
 *   created 2009-10-27
 *
 *   $Id$:
 * }}
 * 
 */


/**
 * Initial function which adds all javascript events to Image edit box and coresponding
 * input elements
 *
 * @param string sImageFrameId
 * @param string sImageId
 * @param string sPath
 * @param string sSession
 * @param integer iImageIdArtLang
 * @param integer iImageId
 */
function addImageEvents(sImageFrameId, sImageId, sPath, sSession, iImageIdArtLang, iImageId, aImageData, sContent) {
	loadImageExternalScripts(sImageFrameId, sPath, iImageId);//load css und js Datei
	addImageTabbingEvents(sImageFrameId, iImageId);//action for Tabs
	addImageFrameShowEvent(sImageFrameId, sImageId);//show dialog
	addSaveEvent(sImageFrameId, iImageIdArtLang, iImageId, aImageData);
	addImageFrameCloseEvents(sImageFrameId);//close dialog
	addImageClickEvent(sImageFrameId, iImageId);//select tree action
	if(sContent!=''){
		$('#directoryShow_'+iImageId).html('<div><img src="'+sContent+'"/></div>');
	} 
	addSelectAction(sImageFrameId, iImageId);
}

/**
 * Function extracts an value from Image form an adds it as hidden to editform for submitting to Contenido
 * Function is called in store proccess of Image
 *
 * @param string sName
 * @param string sValue
 */
function appendImageValue(sImageFrameId, sName, sValue, iImageId) {
	if($('#hidden_'+sName+'_'+iImageId).length>0){
		$('#hidden_'+sName+'_'+iImageId).remove();
	}
	$("form[name='editcontent']").append('<input type="hidden" value="'+sValue+'" id="hidden_'+sName+'_'+iImageId+'" name="'+sName+'"/>');
}

/**
 *  Function adds event which fades edit form to visible when editbutton is clicked
 *
 * @param string sImageFrameId
 * @param string sImageId
 */
function addImageFrameShowEvent(sImageFrameId, sImageId) {
	$(sImageId).css('cursor', 'pointer');
	$(sImageId).click(function () {
		$(sImageFrameId).fadeIn("normal");
		$(sImageFrameId).css('top', $(sImageId).offset().top);
		$(sImageFrameId).css('left', $(sImageId).offset().left+$(sImageId).width()+3);
	});
}

/**
 * Function adds tabbling events to menubar of Image edit form
 * which switchs between the tree tabbing views
 *
 * @param string sImageFrameId
 * @param string iImageId
 */
function addImageTabbingEvents(sImageFrameId, iImageId) {
	$(sImageFrameId+" .menu li").css('cursor', 'pointer');
	//add layer click events
	$(sImageFrameId+" .menu li").click(function(){
        var curAction = $(this);
        
		$(sImageFrameId+" .menu li").css('font-weight', 'normal');
		
		$(sImageFrameId+" #image_"+iImageId+"_directories").css("display", "none");
		$(sImageFrameId+" #image_"+iImageId+"_meta").css("display", "none");
		//add smooth animation
		curAction.css('font-weight', 'bold');
        
		$(sImageFrameId).animate({ 
		height: "auto",
		}, 250 , 'linear', function () {
			$(sImageFrameId+" #image_"+iImageId+'_'+curAction.attr('class')).css('display', 'block');
			$(sImageFrameId+" #image_"+iImageId+'_'+curAction.attr('class')).css('height', 'auto');
			$(sImageFrameId+" #image_"+iImageId+'_'+curAction.attr('class')).fadeIn("normal");
		});
	});
}

/**
 *  Function adds save event to save button of Imageedit form
 *
 * @param string sImageFrameId
 * @param integer iImageIdArtLang
 * @param integer iImageId
 */
function addSaveEvent(sImageFrameId, iImageIdArtLang, iImageId, aImageData) {
	$(sImageFrameId+' .save_settings').css('cursor', 'pointer');
	$(sImageFrameId+' .save_settings').click(function() {
		var sValue = '';
		//iterate over all Image properties
		for (var i = 0; i < aImageData.length; i++) {
			//default value for select boxes and text boxes
			sValue = $(sImageFrameId + ' #'+aImageData[i]+'_'+iImageId).attr('value');
			appendImageValue(sImageFrameId, aImageData[i], sValue, iImageId);
		}
		appendImageValue(sImageFrameId, 'image_action', 'store', iImageId);
		appendImageValue(sImageFrameId, 'image_id', iImageId, iImageId);
		
		setcontent(iImageIdArtLang,'0');
	});
}

/**
 * Function adds event for closing Image edit window and fades box out
 *
 * @param string sImageFrameId
 */
function addImageFrameCloseEvents(sImageFrameId) {
	//add cancel image event
	$(sImageFrameId+' .close').css('cursor', 'pointer');
	$(sImageFrameId+' .close').click(function () {
		$(sImageFrameId).fadeOut("normal");
	});
	
	//add cancel button event
	$(sImageFrameId+' .filelist_cancel').css('cursor', 'pointer');
	$(sImageFrameId+' .filelist_cancel').click(function () {
		$(sImageFrameId).fadeOut("normal");
	});
}

/**
 * Function loads external styles and jquery ui scripts for Image dynamically so this scripts were only
 * loaded into contenido whren this Image is really used in this article
 *
 * @param string sImageFrameId
 * @param string sPath
 */
function loadImageExternalScripts(sImageFrameId, sPath, iImageId) {
	$('head').append('<link rel="stylesheet" href="'+sPath+'styles/cms_image.css" type="text/css" media="all" />');
	
	$.getScript(sPath+'scripts/jquery/jquery-ui.js', function() {
		$.getScript(sPath+'scripts/jquery/jquery-ui.js', function() {
			$(sImageFrameId).draggable({
				handle: '.head'
			});
			$(sImageFrameId+' .head').css('cursor', 'move');
		});
	});
}

function addImageNaviActions(sImageFrameId, iImageId) {	
	$(sImageFrameId+' #image_'+iImageId+'_directories #directoryList_'+iImageId+' a[class="on"]').parent('div').unbind('click');
	$(sImageFrameId+' #image_'+iImageId+'_directories #directoryList_'+iImageId+' a[class="on"]').parent('div').click(function () {
		$.each($(sImageFrameId+' div'), function(){  
			if($(this).hasClass('active')){
				$(this).removeClass('active');
			} 
		});
		if(!$(this).hasClass('active')){	
            $(this).addClass('active');
		}
		var dirname = $(this).children('a[class="on"]').attr('title');
		$.ajax({
			type: "POST",
			url: sPath+"ajaxmain.php",
			data: "ajax=imagelist&dir=" + dirname + "&id=" + iImageId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
			success: function(msg){//show filelist
				$(sImageFrameId+' #image_'+iImageId+'_directories #directoryFile_'+iImageId).html(msg);
				addSelectAction(sImageFrameId, iImageId);
			}
		});		
		return false;
	});
	
	$(sImageFrameId+' #directoryList_'+iImageId+' em a').unbind('click');
	$(sImageFrameId+' #directoryList_'+iImageId+' em a').click(function () {
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
				data: "ajax=dirlist&dir=" + dirname + "&id=" + iImageId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
				success: function(msg){//show foldername
					divContainer.after(msg);
					divContainer.parent('li').removeClass('collapsed');
					addImageNaviActions(sImageFrameId, iImageId);
				}
			});
		}
		return false;
	});
}

/**
 * Function adds double click events to all current listed articles for manual Image
 * in case of a double click this selected article is removed from list
 *
 * @param string sImageFrameId
 */
function addImageClickEvent(sImageFrameId, iImageId) {
	addImageNaviActions(sImageFrameId, iImageId);
}

function addSelectAction(sImageFrameId, iImageId){
	if(document.getElementById('image_filename_'+iImageId)&&document.getElementById('image_filename_'+iImageId).length>0){
		$('select[class="text_medium"]').change(function () {
			var str = "";
			var wert = "";
			$("select#image_filename_"+iImageId+" option:selected").each(function () {
				str += $(this).text();
				wert += $(this).val();
			});
			//show image
			if (str == "Kein"){
				$('#directoryShow_'+iImageId).html('');				
			} else {
				var surl = "";
				surl = sUploadPath+wert;		
				//$('#directoryShow_'+iImageId).html('<div><img src="'+surl+'" style="max-height:210px;max-width:428px;"/></div>');
				$.ajax({
					type: "POST",
					url: sPath+"ajaxmain.php",
					data: "ajax=scaleImage&sUrl="+surl+ "&idartlang=" + iIdArtLang + "&contenido="+sSession,
					success: function(msg){//show meta data
						$('#directoryShow_'+iImageId).html('<div><img src="'+msg+'"/></div>');
					}
				});
			}
			//upload meta data
			if (str == "Kein"){
				$('#image_medianame_'+iImageId).val('');
				$('#image_description_'+iImageId).html('');
				$('#image_keywords_'+iImageId).val('');
				$('#image_internal_notice_'+iImageId).val('');
				$('#image_copyright_'+iImageId).val('');
			} else{
				var val = '';
				$.ajax({
					type: "POST",
					url: sPath+"ajaxmain.php",
					data: "ajax=loadImageMeta&filename="+wert+"&dir=" + dirname + "&id=" + iImageId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
					success: function(msg){//show meta data
						val = msg.split('+++');
						$('#image_medianame_'+iImageId).val(val[0]);
						$('#image_description_'+iImageId).html(val[1]);
						$('#image_keywords_'+iImageId).val(val[2]);
						$('#image_internal_notice_'+iImageId).val(val[3]);
						$('#image_copyright_'+iImageId).val(val[4]);
					}
				});
			}
		})
		.change();
	}	
}

function basename(path) {
	return path.replace(/\\/g,'/').replace( /.*\//, '' );
}
function dirname(path) {
	return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');;
}

