/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * JavaScript CMS Type Teaser
 * 
 *
 * @package    Contenido Content Types
 * @version    1.0.0
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.12
 * 
 * {@internal 
 *   created 2009-04-08
 *
 *   $Id$:
 * }}
 * 
 */


/**
 * Initial function which adds all javascript events to teaser edit box and coresponding
 * input elements
 *
 * @param string sFrameId
 * @param string sImageId
 * @param string sPath
 * @param string sSession
 * @param integer iIdArtLang
 * @param integer iId
 */
function addTeaserEvents(sFrameId, sImageId, sPath, sSession, iIdArtLang, iId) {
	loadExternalScripts(sFrameId, sPath);
	addTabbingEvents(sFrameId);
	addFrameShowEvent(sFrameId, sImageId);
	addSaveEvent(sFrameId, iIdArtLang, iId);
	addFrameCloseEvents(sFrameId);
	addAjaxGetArticleListEvent(sFrameId, sPath, sSession);
	addManualTeaserEvent(sFrameId);
	addClickEvent(sFrameId);
}

/**
 * Function extracts an value from teaser form an adds it as hidden to editform for submitting to Contenido
 * Function is called in store proccess of teaser
 *
 * @param string sName
 * @param string sValue
 */
function appendTeaserValue(sName, sValue) {
	$("form[name='editcontent']").append('<input type="hidden" value="'+sValue+'" name="'+sName+'"/>');
}

/**
 *  Function adds event which fades edit form to visible when editbutton is clicked
 *
 * @param string sFrameId
 * @param string sImageId
 */
function addFrameShowEvent(sFrameId, sImageId) {
	$(sImageId).css('cursor', 'pointer');
	$(sImageId).click(function () {
		$(sFrameId).fadeIn("normal");
		$(sFrameId).css('top', $(sImageId).offset().top);
		$(sFrameId).css('left', $(sImageId).offset().left+$(sImageId).width()+3);
	});
}

/**
 * Function adds tabbling events to menubar of teaser edit form
 * which switchs between the tree tabbing views
 *
 * @param string sFrameId
 */
function addTabbingEvents(sFrameId) {
	$(sFrameId+" .menu li").css('cursor', 'pointer');
	//add layer click events
	$(sFrameId+" .menu li").click(function(){
		$(sFrameId+" .menu li").css('font-weight', 'normal');
		
		$(sFrameId+" #manual").css("display", "none");
		$(sFrameId+" #advanced").css("display", "none");
		$(sFrameId+" #general").css("display", "none");
		//add smooth animation
		$(sFrameId+" #"+$(this).attr('class')).fadeIn("normal");
		$(this).css('font-weight', 'bold');
	});
}

/**
 *  Function adds save event to save button of teaseredit form
 *
 * @param string sFrameId
 * @param integer iIdArtLang
 * @param integer iId
 */
function addSaveEvent(sFrameId, iIdArtLang, iId) {
	$(sFrameId+' .save_settings').css('cursor', 'pointer');
	$(sFrameId+' .save_settings').click(function() {
		addManualTeaserEntry(sFrameId);
		var sValue = '';
		//iterate over all teaser properties
		for (var i = 0; i < aData.length; i++) {
		  if (aData[i] == 'teaser_start' || aData[i] == 'teaser_manual') {
			//special behaviour for checkboxes
			sValue = $(sFrameId+' #'+aData[i]).attr('checked');
		  } else if (aData[i] == 'teaser_manual_art') {
			//in case of manual arts implode them use , as separator
			sValue = '';
			$(sFrameId+' #teaser_manual_art option').each(function() {
				if (sValue == '') {
					sValue = $(this).attr('value');
				} else {
					sValue = sValue+';'+$(this).attr('value');
				}
			});
		  } else {
		    //default value for select boxes and text boxes
			sValue = $(sFrameId+' #'+aData[i]).attr('value');
		  }
		  appendTeaserValue(aData[i], sValue);
		}
		appendTeaserValue('teaser_action', 'store');
		appendTeaserValue('teaser_id', iId);
		setcontent(iIdArtLang,'0');
	});
}

/**
 * Function adds event for closing teaser edit window and fades box out
 *
 * @param string sFrameId
 */
function addFrameCloseEvents(sFrameId) {
	//add cancel image event
	$(sFrameId+' .close').css('cursor', 'pointer');
	$(sFrameId+' .close').click(function () {
		$(sFrameId).fadeOut("normal");
	});
	
	//add cancel button event
	$(sFrameId+' .teaser_cancel').css('cursor', 'pointer');
	$(sFrameId+' .teaser_cancel').click(function () {
		$(sFrameId).fadeOut("normal");
	});
}

/**
 * Function gets new list of articles from contenido via ajax
 * is used in manual teaser when base category for article select
 * is changed
 *
 * @param string sFrameId
 * @param string sPath
 * @param string sSession
 */
function addAjaxGetArticleListEvent(sFrameId, sPath, sSession) {
	$(sFrameId+' #teaser_cat').change(function() {
		//get new article select and replace it with default value
		$.ajax({
		  type: "POST",
		  url: sPath+"ajaxmain.php",
		  data: "ajax=artsel&name=teaser_art&contenido="+sSession+"&idcat="+$(this).attr('value'),
		  success: function(msg){
			$(sFrameId+' #teaser_art').replaceWith(msg);
		  }
		});
	});
}

/**
 * Function loads external styles and jquery ui scripts for teaser dynamically so this scripts were only
 * loaded into contenido whren this teaser is really used in this article
 *
 * @param string sFrameId
 * @param string sPath
 */
function loadExternalScripts(sFrameId, sPath) {
	$('head').append('<link rel="stylesheet" href="'+sPath+'styles/cms_teaser.css" type="text/css" media="all" />');
	
	$.getScript(sPath+'scripts/jquery/jquery-ui.js', function() {
		$.getScript(sPath+'scripts/jquery/jquery-ui.js', function() {
			$(sFrameId).draggable({handle: '.head'});
			$(sFrameId+' .head').css('cursor', 'move');
		});
	});
}

/**
 * Function adds event to add new article to multiple select box for articles
 * Function also checks if article is already in that list
 *
 * @param string sFrameId
 */
function addManualTeaserEvent(sFrameId) {
	$(sFrameId+' #add_art').css('cursor', 'pointer');
		$(sFrameId+' #add_art').click(function() {
			//call internal add function
			addManualTeaserEntry(sFrameId);
		});
}

/**
 * Function adds new article to multiple select box for articles
 * Function also checks if article is already in that list
 *
 * @param string sFrameId
 */
function addManualTeaserEntry(sFrameId) {
	var oArt = $(sFrameId+' #teaser_art');
	var iIdArt = oArt.attr('value');
	var sName = '';
	var bExists = 0;
	
	//if an article was selected
	if (iIdArt > 0) {
		//check if article already exists in view list
		$(sFrameId+' #teaser_manual_art option').each(function() {
			if (iIdArt == $(this).attr('value')) {
				bExists = 1;
			}
		});
		
		//get name of selected article
		$(sFrameId+' #teaser_art option').each(function() {
			if (iIdArt == $(this).attr('value')) {
				sName = $(this).html();
			}
		});
		
		//if it is not in list, add article to list
		if (bExists == 0) {
			$(sFrameId+' #teaser_manual_art').append('<option value="'+iIdArt+'">'+sName+'</option>');
		}
	}
}

/**
 * Function adds double click events to all current listed articles for manual teaser
 * in case of a double click this selected article is removed from list
 *
 * @param string sFrameId
 */
function addClickEvent(sFrameId) {
	$(sFrameId+' #teaser_manual_art').dblclick(function() {
		$(sFrameId+' #teaser_manual_art option').each(function() {
			if($(this).attr('selected')) {
				$(this).remove();
			};
		});
	});
	
}