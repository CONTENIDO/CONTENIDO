/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * JavaScript CMS Type LinkEditor
 *
 *
 * @package    CONTENIDO Content Types
 * @version    1.0.0
 * @author     Fulai Zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.13
 *
 * {@internal
 *   created 2011-07-19
 *
 *   $Id$:
 * }}
 *
 */


/**
 * Initial function which adds all javascript events to link edit box and coresponding
 * input elements
 *
 * @param string sFrameId
 * @param string sButtonId
 * @param string sPath
 * @param string sSession
 * @param int     iIdArtLang
 * @param int     iId
 * @param array  aLinkData
 * @param string sContent
 */
function addLinkEditorEvents(sFrameId, sButtonId, iId ,iIdArtLang) {
    loadLinkEditorExternalScripts(sFrameId, iId);//load css und js Datei
    addTabbingEvents(sFrameId, iId);//action for Tabs
    addLinkEditorFrameShowEvent(sFrameId, sButtonId);//show dialog
    addSaveEvent(sFrameId, iIdArtLang, iId);
    addLinkEditorFrameCloseEvents(sFrameId);//close dialog
    addLinkEditorNaviActions(sFrameId, iId);//select tree action
    createMKDir(sFrameId,iId);
    showFolderPath(sFrameId, iId);
}

/**
 * Function loads external styles and jquery ui scripts for Linkeditor dynamically so this scripts were only
 * loaded into CONTENIDO when this Linkeditor is really used in this article
 *
 * @param string sFrameId
 * @param string sPath
 * @param int      iId
 */
function loadLinkEditorExternalScripts(sFrameId, iId) {
    //include_once jascript und css datei
    initIncludedFiles();
    include_once(sPath+'scripts/jquery/ajaxupload.js', 'js');
    include_once(sPath+'styles/cms_linkeditor.css', 'css');
    //frame movebar

    conLoadFile(sPath+'scripts/jquery/jquery-ui.js', 'loadLinkEditorExternalScriptsCallback(\''+sFrameId+'\');');
}

function loadLinkEditorExternalScriptsCallback(sFrameId) {
    $(sFrameId).draggable({
        handle: '.head'
    });
    $(sFrameId+' .head').css('cursor', 'move');
}

/**
 * Function adds tabbling events to menubar of Linkeditor form
 * which switchs between the tree tabbing views
 *
 * @param string sFrameId
 * @param int     sId
 */
function addTabbingEvents(sFrameId, iId) {
    $(sFrameId+" .menu li").css('cursor', 'pointer');
    //add layer click events
    $(sFrameId+" .menu li").click(function(){
        var curAction = $(this);

        $(sFrameId+" .menu li").css('font-weight', 'normal');
        //init
        $(sFrameId+" #cms_linkeditor_"+iId+"_extern").css("display", "none");
        $(sFrameId+" #cms_linkeditor_"+iId+"_intern").css("display", "none");
        $(sFrameId+" #cms_linkeditor_"+iId+"_upload").css("display", "none");
        //add smooth animation
        curAction.css('font-weight', 'bold');

        $(sFrameId).animate({
        height: "auto",
        }, 150 , 'linear', function () {
            $(sFrameId+" #cms_linkeditor_"+iId+'_'+curAction.attr('class')).css('display', 'block');
            $(sFrameId+" #cms_linkeditor_"+iId+'_'+curAction.attr('class')).css('height', 'auto');
            $(sFrameId+" #cms_linkeditor_"+iId+'_'+curAction.attr('class')).fadeIn("normal");
        });

    });
    //radio
    /*$(sFrameId+" #cms_linkeditor_"+iId+"_attr input[type='radio']").click(function(){
        var curAction = $(this);
        //init
        $(sFrameId+" #cms_linkeditor_"+iId+"_attr input[name='externlink']").css("display", "none");
        $(sFrameId+" #cms_linkeditor_"+iId+"_attr div.directoryList").css("display", "none");
        $(sFrameId+" #cms_linkeditor_"+iId+"_attr div.directoryFile").css("display", "none");
        $(sFrameId).animate({
            height: "auto",
            }, 250 , 'linear', function () {
            if(curAction.attr('value') == 'extern'){
                $(sFrameId+" #cms_linkeditor_"+iId+"_attr input[name='externlink']").css("display", "block");
            } else {
                $(sFrameId+" #cms_linkeditor_"+iId+"_attr div.directoryList").css("display", "block");
                $(sFrameId+" #cms_linkeditor_"+iId+"_attr div.directoryFile").css("display", "block");
            }
        });
    });*/
}
/**
 *  Function adds event which fades edit form to visible when editbutton is clicked
 *
 * @param string sFrameId
 * @param int     sId
 */
function addLinkEditorFrameShowEvent(sFrameId, sButtonId) {
    $(sButtonId).css('cursor', 'pointer');
    $(sButtonId).click(function () {
        $(sFrameId).fadeIn("normal");
        $(sFrameId).css('top', $(sButtonId).offset().top);
        $(sFrameId).css('left', $(sButtonId).offset().left+$(sButtonId).width()+3);
    });
}

/**
 * Function adds event for closing Linkeditor window and fades box out
 *
 * @param string sFrameId
 */
function addLinkEditorFrameCloseEvents(sFrameId) {
    //add top cancel event
    $(sFrameId+' .close').css('cursor', 'pointer');
    $(sFrameId+' .close').click(function () {
        $(sFrameId).fadeOut("normal");
    });

    //add bottom cancel event
    $(sFrameId+' .filelist_cancel').css('cursor', 'pointer');
    $(sFrameId+' .filelist_cancel').click(function () {
        $(sFrameId).fadeOut("normal");
    });
}
/**
 *  Function adds save event to save button of Linkeditor form
 *
 * @param string sFrameId
 * @param int     iIdArtLang
 * @param int      iId
 */
function addSaveEvent(sFrameId, iIdArtLang, iId) {
    $(sFrameId+' .save_settings').css('cursor', 'pointer');
    $(sFrameId+' .save_settings').click(function() {
        appendValue(sFrameId, 'linkeditor_action', 'store', iId);
        appendValue(sFrameId, 'linkeditor_id', iId, iId);
        //Externer Link
        var link_ex_src = $(sFrameId+" #cms_linkeditor_"+iId+"_extern #externlink_"+iId).val();

        //Interner Link
        var idart = "";
        $(sFrameId+" #cms_linkeditor_"+iId+"_intern select#linkeditor_filename_"+iId+" option:selected").each(function () {
            idart += $(this).val();
        });

        //link
        var uploadedFilePath = "";
        $(sFrameId+" #cms_linkeditor_"+iId+"_upload select#image_filename_"+iId+" option:selected").each(function () {
            uploadedFilePath += $(this).val();
        });
        //get value from active Tab
        var activeTab = "";
        $(sFrameId+" .menu li").each(function () {
            if( $(this).css("font-weight") == "block" || $(this).css("font-weight") == 700){
                activeTab = $(this).attr('class');
            }
        });

        var link_title = $(sFrameId+" #cms_linkeditor_"+iId+"_attr #externtitle_"+iId).val();

        var link_src = "";
        switch (activeTab){
            case 'extern':
                link_src = link_ex_src;
                break;
            case 'intern':
                link_src = idart;
                break;
            default:
                link_src = uploadedFilePath;
        }
        appendValue(sFrameId, 'link_type', activeTab, iId);
        appendValue(sFrameId, 'link_src', link_src, iId);
        appendValue(sFrameId, 'link_title', link_title, iId);
        //target
        if($(sFrameId+" #checkboxlinktarget_"+iId+":checked").val()){
            appendValue(sFrameId, 'link_target', '_blank', iId);
        } else {
            appendValue(sFrameId, 'link_target', '', iId);
        }

        setcontent(iIdArtLang,'0');
    });
}
/**
 * Function extracts an value from Linkeditor form an adds it as hidden to editform for submitting to CONTENIDO
 * Function is called in store proccess of Linkeditor
 *
 * @param string sFrameId
 * @param string sName
 * @param string sValue
 * @param int    iId
 */
function appendValue(sFrameId, sName, sValue, iId) {
    $("form[name='editcontent']").append('<input type="hidden" value="'+sValue+'" id="hidden_'+sName+'_'+iId+'" name="'+sName+'"/>');
}


/**
 * Function adds event which files to visible when a directory is clicked of linkeditor form
 *
 * @param string sFrameId
 * @param int      iId
 */
function addLinkEditorNaviActions(sFrameId, iId) {
    //show actually category
    $(sFrameId+' #cms_linkeditor_'+iId+'_intern #directoryList_'+iId+' a[class="on"]').parent('div').unbind('click');
    $(sFrameId+' #cms_linkeditor_'+iId+'_intern #directoryList_'+iId+' a[class="on"]').parent('div').click(function () {
        $.each($(sFrameId+' div'), function(){
            if($(this).hasClass('active')){
                $(this).removeClass('active');
            }
        });
        if(!$(this).hasClass('active')){
            $(this).addClass('active');
        }
        var idcat = $(this).children('a[class="on"]').attr('title');

        $.ajax({
            type: "POST",
            url: sPath+"ajaxmain.php",
            data: "ajax=linkeditorfilelist&idcat=" + idcat + "&id=" + iId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
            success: function(msg){
                $(sFrameId+' #cms_linkeditor_'+iId+'_intern #directoryFile_'+iId).html(msg);
            }
        });
        return false;
    });
    //downside more category
    $(sFrameId+' #cms_linkeditor_'+iId+'_intern #directoryList_'+iId+' em a').unbind('click');
    $(sFrameId+' #cms_linkeditor_'+iId+'_intern #directoryList_'+iId+' em a').click(function () {
        var idcat         = $(this).parent('em').parent().find('a[class="on"]').attr('title');
        var parentidcat = $(this).parent('em').parent().parent().parent().find('div a[class="on"]').attr('title');
        var level         = 0;
        level = $(this).parents('ul').length-1;

        var divContainer     = $(this).parent().parent();
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
                data: "ajax=linkeditordirlist&idcat=" + idcat + "&level=" + level + "&parentidcat=" + parentidcat + "&id=" + iId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
                success: function(msg){
                    divContainer.after(msg);
                    divContainer.parent('li').removeClass('collapsed');
                    addLinkEditorNaviActions(sFrameId, iId);
                }
            });
        }
        return false;
    });

    //same as cms_image
    //show actually articles
    $(sFrameId+' #cms_linkeditor_'+iId+'_upload #directoryList_'+iId+' a[class="on"]').parent('div').unbind('click');
    $(sFrameId+' #cms_linkeditor_'+iId+'_upload #directoryList_'+iId+' a[class="on"]').parent('div').click(function () {
        $.each($(sFrameId+' div'), function(){
            if($(this).hasClass('active')){
                $(this).removeClass('active');
            }
        });
        if(!$(this).hasClass('active')){
            $(this).addClass('active');
        }
        var sDirname = $(this).children('a[class="on"]').attr('title');
        if(sDirname=='upload'){sDirname = '/';}
        $.ajax({
            type: "POST",
            url: sPath+"ajaxmain.php",
            data: "ajax=imagelist&dir=" + sDirname + "&id=" + iId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
            success: function(msg){
                $(sFrameId+' #cms_linkeditor_'+iId+'_upload #directoryFile_'+iId).html(msg);
            }
        });
        showFolderPath(sFrameId, iId);
        return false;
    });
    //downside more directory
    $(sFrameId+' #cms_linkeditor_'+iId+'_upload #directoryList_'+iId+' em a').unbind('click');
    $(sFrameId+' #cms_linkeditor_'+iId+'_upload #directoryList_'+iId+' em a').click(function () {
        var divContainer     = $(this).parent().parent();
        var sDirname         = $(this).parent('em').parent().find('a[class="on"]').attr('title');
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
                data: "ajax=dirlist&dir=" + sDirname + "&id=" + iId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
                success: function(msg){
                    divContainer.after(msg);
                    divContainer.parent('li').removeClass('collapsed');
                    addLinkEditorNaviActions(sFrameId, iId);
                }
            });
        }
        return false;
    });
}

function showFolderPath(sFrameId, iId){
    //upload datei
    aTitle = Array();
    $(sFrameId+' #cms_linkeditor_'+iId+'_upload div[class="active"] a[class="on"]').each(function(){
        aTitle.push($(this).attr('title'));
    });
    if(aTitle.length<1){
        $(sFrameId+' #cms_linkeditor_'+iId+'_upload #cms_linkeditor_'+iId+'_directories li#root>div').addClass('active');
    }
    var sDirname = $(sFrameId+' #cms_linkeditor_'+iId+'_upload div[class="active"] a[class="on"]').attr('title');
    //divContainer[iId] = $(sFrameId+' #cms_linkeditor_'+iId+'_upload div[class="active"] em a').parent().parent();
    createLinkEditorPath[iId] = sDirname;
    if(createLinkEditorPath[iId] != '' && createLinkEditorPath[iId] != 'upload'){sDirname = createLinkEditorPath[iId]+'/';} else {sDirname = '';}

    $(sFrameId+' #caption1').text(sDirname);
    $(sFrameId+' #caption2').text(sDirname);
    $(sFrameId+' form[name="newdir"] input[name="path"]').val(sDirname);
    $(sFrameId+' form[name="properties"] input[name="path"]').val(sDirname);
    setTimeout("linkEditorFileUpload(\'"+sFrameId+"\', \'"+iId+"\')",1000);
}
/**
 * Function creates a upload directory, either in filesystem or in dbfs.
 *
 * @param string sFrameId
 * @param int      iId
 */
function createMKDir(sFrameId, iId){
    $(sFrameId+' #cms_linkeditor_'+iId+'_upload form[name="newdir"] input[type="image"]').unbind('click');
    $(sFrameId+' #cms_linkeditor_'+iId+'_upload form[name="newdir"] input[type="image"]').click(function () {
        var folderName = $(sFrameId+' input[name="foldername"]').val();
        var sDirname = '';
        if(createLinkEditorPath[iId] != '' && createLinkEditorPath[iId] != 'upload'){sDirname = createLinkEditorPath[iId]+'/';}
        if(folderName != ''){
        $.ajax({
            type: "POST",
            url: sPath+"ajaxmain.php",
            data: "ajax=upl_mkdir&id=" + iId + "&idartlang=" + iIdArtLang + "&path=" + sDirname + "&foldername=" + folderName + "&contenido="+sSession,
            //url: sPath+"main.php",
            //data: "area=upl&action=upl_mkdir&frame=2&appendparameters=&path=" + sDirname + "&foldername=" + folderName + "&contenido="+sSession,
            success: function(msg){//make create folder
            if(msg!='0702'){
                $('input[name="foldername"]').val('');
                $.ajax({
                    type: "POST",
                    url: sPath+"ajaxmain.php",
                    data: "ajax=dirlist&idartlang=" + iIdArtLang + "&id=" + iId + "&dir=" + sDirname + "&contenido="+sSession,
                    success: function(msg){//create directory list
                        if(createLinkEditorPath[iId] == 'upload'){
                            var title = folderName;
                        }else{
                            var title = sDirname+folderName;
                        }
                        aTitle = Array();
                        $(sFrameId+' #cms_linkeditor_'+iId+'_upload  div a[class="on"]').each(function(){
                            aTitle.push($(this).attr('title'));
                        });

                        if(!in_array(title,aTitle)){
                            //divContainer[iId].parent('li').children('ul').remove();
                            //divContainer[iId].after(msg);
                            //divContainer[iId].parent('li').removeClass('collapsed');
                            //addLinkEditorNaviActions(sFrameId, iId);
                            $('div.cms_linkeditor #cms_linkeditor_'+iId+'_upload .con_str_tree li div>a').each(function(index){
                                if($(this).attr('title') == createLinkEditorPath[iId]){
                                    $(this).parent().parent('li:has(ul)').children('ul').remove();
                                    $(this).parent().after(msg);
                                    $(this).parent().parent('li').removeClass('collapsed');
                                    var sformName = '#'+$(this).parents('.cms_linkeditor').attr('id');
                                    addLinkEditorNaviActions(sformName, sformName.substr(16,1));
                                }
                            });
                        }
                    }
                });
            }
            }
        });
        }
        return false;
    });
}

/**
 * Function upload image.
 *
 * @param string sFrameId
 * @param int      iId
 */
function linkEditorFileUpload(sFrameId, iId){
    var folderName = $(sFrameId+' input[name="file[]"]').val();
    var sDirname = '';
    if(createLinkEditorPath[iId] != '' && createLinkEditorPath[iId] != 'upload'){sDirname = createLinkEditorPath[iId]+'/';}

    new AjaxUpload('#cms_linkeditor_m'+iId, {
        //action: sPath+"main.php?area=upl&action=upl_upload&frame=4&appendparameters=&leftframe=0&rightframe=0&file=&path=" + sDirname + "&contenido="+sSession,
        action: sPath+"ajaxmain.php?ajax=upl_upload&id=" + iId + "&idartlang=" + iIdArtLang + "&path=" + sDirname + "&contenido="+sSession,
        name: 'file[]',
        onSubmit:function(){
            $('img.loading').css('display','block');
        },
        onComplete : function(file){
            /*var aValue = Array();
            $(sFrameId+' select#image_filename_'+iId+' option').each(function(){
                aValue.push($(this).attr('value'));
            });
            $('img.loading').css('display','none');
            if(!in_array(sDirname+file,aValue)){
                $(sFrameId+' #image_filename_'+iId).append('<option id="" value="'+sDirname+file+'">'+file+'</option>');
            }*/
            if(sDirname=='upload' || sDirname==''){sDirname = '/';}
            $.ajax({
                type: "POST",
                url: sPath+"ajaxmain.php",
                data: "ajax=imagelist&dir=" + createLinkEditorPath[iId] + "&id=" + iId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
                success: function(msg){
                    $('img.loading').css('display','none');
                    $(sFrameId+' #cms_linkeditor_'+iId+'_directories #directoryFile_'+iId).html(msg);

                }
            });
        }
    });
}



//PHP base functions
function basename(path) {
    return path.replace(/\\/g,'/').replace( /.*\//, '' );
}
function dirname(path) {
    return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');;
}

//Read a page's GET URL variables and return them as an associative array.
function getUrlVars(){
  var vars = [], hash;
  var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
  for(var i = 0; i < hashes.length; i++){
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
  }
  return vars;
}

/* function to search in arrays */
function in_array(needle, haystack) {
    for (var i = 0; i < haystack.length; i++) {
        if (haystack[i] == needle) {
            return true;
        }
    }
    return false;
}

/* function to include a script */
function include(filename, filetype) {
    var html_head = document.getElementsByTagName('head').item(0);
    if(filetype=='js'){
        var js = document.createElement('script');
        js.setAttribute('language', 'javascript');
        js.setAttribute('type', 'text/javascript');
        js.setAttribute('src', filename);
        html_head.appendChild(js);
    } else if(filetype=='css'){
        var css = document.createElement('link');
        css.setAttribute('media', 'all');
        css.setAttribute('type', 'text/css');
        css.setAttribute('rel', 'stylesheet');
        css.setAttribute('href', filename);
        html_head.appendChild(css);
    }
    return true;
}

/* function checks if file is included already. if not: include() */
/* needs an array */
var afiles = new Array();
var included_files = new Array();
var included_cssfiles = new Array();

/* function */
function include_once(script_filename, filetype) {
    if(filetype=='js'){
        afiles = included_files;
    } else if(filetype=='css'){
        afiles = included_cssfiles;
    }
    if (!in_array(script_filename, afiles)) {
        afiles[afiles.length] = script_filename;
        include(script_filename, filetype);
    }
    return true;
}

/**
* function scans the document for already included files
* and puts them into the 'included_files' array
*/
function initIncludedFiles () {
    var scripts = document.getElementsByTagName('script');
    /* put them into the array if they have a 'src' attribute */
    for (var i=0; i < scripts.length; i++) {
        if(scripts[i].src) {
            if (!in_array(scripts[i].src, included_files)) {
                included_files[i] = scripts[i].src;
            }
            /**
             * why the second if?
             * if you have already double includes in your html-code,
             * there is no need to list them double in the array ...
             */
        } else {
            included_files[i] = '';
        }
    }
    var scripts = document.getElementsByTagName('link');
    /* put them into the array if they have a 'src' attribute */
    for (var i=0; i < scripts.length; i++) {
        if(scripts[i].href) {
            if (!in_array(scripts[i].href, included_cssfiles)) {
                included_cssfiles[i] = scripts[i].href;
            }
            /**
             * why the second if?
             * if you have already double includes in your html-code,
             * there is no need to list them double in the array ...
             */
        }
    }
}