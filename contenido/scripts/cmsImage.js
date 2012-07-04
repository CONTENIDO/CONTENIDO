/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * JavaScript CMS Type Image
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
 *   created 2009-10-27
 *     modified 2011-07-18
 *   $Id: cmsImage.js 2408 2012-06-25 23:09:32Z xmurrix $:
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
 * @param int     iImageIdArtLang
 * @param int     iImageId
 * @param array  aImageData
 * @param string sContent
 */
function addImageEvents(sImageFrameId, sImageId, sPath, sSession, iImageIdArtLang, iImageId, aImageData, sContent) {
    loadImageExternalScripts(sImageFrameId, sPath, iImageId);//load css und js Datei
    addImageTabbingEvents(sImageFrameId, iImageId);//action for Tabs
    addImageFrameShowEvent(sImageFrameId, sImageId);//show dialog
    addSaveEvent(sImageFrameId, iImageIdArtLang, iImageId, aImageData);
    addImageFrameCloseEvents(sImageFrameId);//close dialog
    addImageNaviActions(sImageFrameId, iImageId);//select tree action
    if (sContent != '') {
        //$('#directoryShow_'+iImageId).html('<div><img src="'+sContent+'"/></div>');
    }
    addSelectAction(sImageFrameId, iImageId);
    showImageFolderPath(sImageFrameId, iImageId);
    createMKDir(sImageFrameId,iImageId);
    showUrlforMeta(sImageFrameId,iImageId);
}

/**
 * Function extracts an value from Image form an adds it as hidden to editform for submitting to CONTENIDO
 * Function is called in store proccess of Image
 *
 * @param string sImageFrameId
 * @param string sName
 * @param string sValue
 * @param int    iImageId
 */
function appendImageValue(sImageFrameId, sName, sValue, iImageId) {
    if ($('#hidden_'+sName+'_'+iImageId).length > 0) {
        $('#hidden_'+sName+'_'+iImageId).remove();
    }
    $("form[name='editcontent']").append('<input type="hidden" value="'+sValue+'" id="hidden_'+sName+'_'+iImageId+'" name="'+sName+'"/>');
}

/**
 *  Function adds event which fades edit form to visible when editbutton is clicked
 *
 * @param string sImageFrameId
 * @param int     sImageId
 */
function addImageFrameShowEvent(sImageFrameId, sImageId) {
    $(sImageId).css('cursor', 'pointer');
    $(sImageId).click(function() {
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
 * @param int     sImageId
 */
function addImageTabbingEvents(sImageFrameId, iImageId) {
    $(sImageFrameId+" .menu li").css('cursor', 'pointer');
    //add layer click events
    $(sImageFrameId+" .menu li").click(function() {
        var curAction = $(this);

        $(sImageFrameId+" .menu li").css('font-weight', 'normal');

        $(sImageFrameId+" #image_"+iImageId+"_directories").css("display", "none");
        $(sImageFrameId+" #image_"+iImageId+"_meta").css("display", "none");
        $(sImageFrameId+" #image_"+iImageId+"_upload").css("display", "none");
        //add smooth animation
        curAction.css('font-weight', 'bold');

        $(sImageFrameId).animate({
            height: "auto",
        }, 250 , 'linear', function() {
            $(sImageFrameId+" #image_"+iImageId+'_'+curAction.attr('class')).css('display', 'block');
            $(sImageFrameId+" #image_"+iImageId+'_'+curAction.attr('class')).css('height', 'auto');
            $(sImageFrameId+" #image_"+iImageId+'_'+curAction.attr('class')).fadeIn("normal");
        });
        if (curAction.attr('class') == 'upload') {
            $(sImageFrameId+" #image_"+iImageId+'_directories').css('display', 'block');
            $(sImageFrameId+" #image_"+iImageId+'_directories').css('height', 'auto');
            $(sImageFrameId+" #image_"+iImageId+'_directories').fadeIn("normal");
        }
    });
}

/**
 *  Function adds save event to save button of Image edit form
 *
 * @param string sImageFrameId
 * @param int     iImageIdArtLang
 * @param int      iImageId
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
    $(sImageFrameId+' .close').click(function() {
        $(sImageFrameId).fadeOut("normal");
    });

    //add cancel button event
    $(sImageFrameId+' .filelist_cancel').css('cursor', 'pointer');
    $(sImageFrameId+' .filelist_cancel').click(function() {
        $(sImageFrameId).fadeOut("normal");
    });
}

/**
 * Function loads external styles and jquery ui scripts for Image dynamically so this scripts were only
 * loaded into CONTENIDO when this Image is really used in this article
 *
 * @param string sImageFrameId
 * @param string sPath
 * @param int      iImageId
 */
function loadImageExternalScripts(sImageFrameId, sPath, iImageId) {
    //$('head').append('<link rel="stylesheet" href="'+sPath+'styles/cms_image.css" type="text/css" media="all" />');
    //$('head').append('<script type="text/javascript" src="'+sPath+'scripts/jquery/ajaxupload.js"></script>');
    initIncludedFiles();
    include_once(sPath+'scripts/jquery/ajaxupload.js', 'js');
    include_once(sPath+'styles/cms_image.css', 'css');

    conLoadFile(sPath+'scripts/jquery/jquery-ui.js', 'loadImageExternalScriptsCallback(\''+sImageFrameId+'\');');
}

function loadImageExternalScriptsCallback(sImageFrameId) {
    $(sImageFrameId).draggable({
        handle: '.head'
    });
    $(sImageFrameId+' .head').css('cursor', 'move');
}

/**
 * Function adds event which files to visible when a directory is clicked of Image edit form
 *
 * @param string sImageFrameId
 * @param int      iImageId
 */
function addImageNaviActions(sImageFrameId, iImageId) {
    //show actually articles
    $(sImageFrameId+' #image_'+iImageId+'_directories #directoryList_'+iImageId+' a[class="on"]').parent('div').unbind('click');
    $(sImageFrameId+' #image_'+iImageId+'_directories #directoryList_'+iImageId+' a[class="on"]').parent('div').click(function () {
        $.each($(sImageFrameId+' div'), function() {
            if ($(this).hasClass('active')) {
                $(this).removeClass('active');
            }
        });
        if (!$(this).hasClass('active')) {
            $(this).addClass('active');
        }
        var dirname = $(this).children('a[class="on"]').attr('title');
        if (dirname == 'upload') {
            dirname = '/';
        }
        $.ajax({
            type: "POST",
            url: sPath+"ajaxmain.php",
            data: "ajax=imagelist&dir=" + dirname + "&id=" + iImageId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
            success: function(msg) {
                $(sImageFrameId+' #image_'+iImageId+'_directories #directoryFile_'+iImageId).html(msg);
                addSelectAction(sImageFrameId, iImageId);
            }
        });
        showImageFolderPath(sImageFrameId, iImageId);
        return false;
    });
    //downside more directory
    $(sImageFrameId+' #directoryList_'+iImageId+' em a').unbind('click');
    $(sImageFrameId+' #directoryList_'+iImageId+' em a').click(function() {
        var divContainer = $(this).parent().parent();
        var dirname      = $(this).parent('em').parent().find('a[class="on"]').attr('title');
        if (divContainer.next('ul').length > 0) {
            divContainer.next('ul').toggle(function() {
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
                success: function(msg) {
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
 * Function adds event what path to visible when one directory is clicked of Image edit form
 *
 * @param string sImageFrameId
 * @param int      iImageId
 */
function showImageFolderPath(sImageFrameId, iImageId) {
    //upload datei
    aTitle = Array();
    $(sImageFrameId+' div[class="active"] a[class="on"]').each(function() {
        aTitle.push($(this).attr('title'));
    });
    if (aTitle.length<1) {
        $(sImageFrameId+' #image_'+iImageId+'_directories li#root>div').addClass('active');
    }
    var dirname = $(sImageFrameId+' div[class="active"] a[class="on"]').attr('title');
    divContainer[iImageId] = $(sImageFrameId+' div[class="active"] em a').parent().parent();
    createPath[iImageId] = dirname;
    if (createPath[iImageId] != '' && createPath[iImageId] != 'upload') {dirname = createPath[iImageId]+'/';} else {dirname = '';}

    $(sImageFrameId+' #caption1').text(dirname);
    $(sImageFrameId+' #caption2').text(dirname);
    $(sImageFrameId+' form[name="newdir"] input[name="path"]').val(dirname);
    $(sImageFrameId+' form[name="properties"] input[name="path"]').val(dirname);
    setTimeout("imageFileUpload(\'"+sImageFrameId+"\', \'"+iImageId+"\')",1000);
}

/**
 * Function adds click events to all current listed articles for manual Image
 * in case of a click this selected article show in window
 *
 * @param string sImageFrameId
 * @param int      iImageId
 */
function addSelectAction(sImageFrameId, iImageId) {
    if (document.getElementById('image_filename_'+iImageId) && document.getElementById('image_filename_'+iImageId).length>0) {
        $(sImageFrameId+' select[class="text_medium"]').change(function () {
            var str = "";
            var wert = "";
            $("select#image_filename_"+iImageId+" option:selected").each(function () {
                str += $(this).text();
                wert += $(this).val();
            });
            //show image
            if (str == "Kein") {
                $('#directoryShow_'+iImageId).html('');
            } else {
                var surl = "";
                //surl = sUploadPath+wert;
                surl = frontend_path + 'upload/' + wert;
                //$('#directoryShow_'+iImageId).html('<div><img src="'+surl+'" style="max-height:210px;max-width:428px;"/></div>');
                $.ajax({
                    type: "POST",
                    url: sPath+"ajaxmain.php",
                    data: "ajax=scaleImage&sUrl="+surl+ "&idartlang=" + iIdArtLang + "&contenido="+sSession,
                    success: function(msg) {//show meta data
                        $('#directoryShow_'+iImageId).html('<div><img src="'+msg+'"/></div>');
                    }
                });
            }
            //upload meta data
            if (str == "Kein") {
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
                    data: "ajax=loadImageMeta&filename="+wert+"&id=" + iImageId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
                    success: function(msg) {//show meta data
                        val = msg.split('+++');
                        $('#image_medianame_'+iImageId).val(val[0]);
                        $('#image_description_'+iImageId).html(val[1]);
                        $('#image_keywords_'+iImageId).val(val[2]);
                        $('#image_internal_notice_'+iImageId).val(val[3]);
                        $('#image_copyright_'+iImageId).val(val[4]);
                        showUrlforMeta(sImageFrameId,iImageId);
                    }
                });
            }
        });
    }
}

/**
 * Function creates a upload directory, either in filesystem or in dbfs.
 *
 * @param string sImageFrameId
 * @param int      iImageId
 */
function createMKDir(sImageFrameId, iImageId) {
    $(sImageFrameId+' #image_'+iImageId+'_upload form[name="newdir"] input[type="image"]').unbind('click');
    $(sImageFrameId+' #image_'+iImageId+'_upload form[name="newdir"] input[type="image"]').click(function () {
        var folderName = $(sImageFrameId+' input[name="foldername"]').val();
        var dirname = '';
        if (createPath[iImageId] != '' && createPath[iImageId] != 'upload') {dirname = createPath[iImageId]+'/';}
        if (folderName != '') {
        $.ajax({
            type: "POST",
            url: sPath+"ajaxmain.php",
            data: "ajax=upl_mkdir&id=" + iImageId + "&idartlang=" + iIdArtLang + "&path=" + dirname + "&foldername=" + folderName + "&contenido="+sSession,
            //url: sPath+"main.php",
            //data: "area=upl&action=upl_mkdir&frame=2&appendparameters=&path=" + dirname + "&foldername=" + folderName + "&contenido="+sSession,
            success: function(msg) {//make create folder
            if (msg!='0702') {
                $('input[name="foldername"]').val('');
                $.ajax({
                    type: "POST",
                    url: sPath+"ajaxmain.php",
                    data: "ajax=dirlist&idartlang=" + iIdArtLang + "&id=" + iImageId + "&dir=" + dirname + "&contenido="+sSession,
                    success: function(msg) {//create directory list
                        if (createPath[iImageId] == 'upload') {
                            var title = folderName;
                        } else {
                            var title = dirname+folderName;
                        }
                        aTitle = Array();
                        $(sImageFrameId+' div a[class="on"]').each(function() {
                            aTitle.push($(this).attr('title'));
                        });

                        if (!in_array(title,aTitle)) {
                            //divContainer[iImageId].parent('li').children('ul').remove();
                            //divContainer[iImageId].after(msg);
                            //divContainer[iImageId].parent('li').removeClass('collapsed');
                            //addImageNaviActions(sImageFrameId, iImageId);
                            $('div.cms_image .con_str_tree li div>a').each(function(index) {
                                if ($(this).attr('title') == createPath[iImageId]) {
                                    $(this).parent().parent('li:has(ul)').children('ul').remove();
                                    $(this).parent().after(msg);
                                    $(this).parent().parent('li').removeClass('collapsed');
                                    var sformName = '#'+$(this).parents('.cms_image').attr('id');
                                    addImageNaviActions(sformName, sformName.substr(11,1));
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
 * @param string sImageFrameId
 * @param int      iImageId
 */
function imageFileUpload(sImageFrameId, iImageId) {
    var folderName = $(sImageFrameId+' input[name="file[]"]').val();
    var dirname = '';
    if (createPath[iImageId] != '' && createPath[iImageId] != 'upload') {dirname = createPath[iImageId]+'/';}

    new AjaxUpload('#cms_image_m'+iImageId, {
        //action: sPath+"main.php?area=upl&action=upl_upload&frame=4&appendparameters=&leftframe=0&rightframe=0&file=&path=" + dirname + "&contenido="+sSession,
        action: sPath+"ajaxmain.php?ajax=upl_upload&id=" + iImageId + "&idartlang=" + iIdArtLang + "&path=" + dirname + "&contenido="+sSession,
        name: 'file[]',
        onSubmit:function() {
            $('img.loading').css('display','block');
        },
        onComplete : function(file) {
            /*var aValue = Array();
            $(sImageFrameId+' select#image_filename_'+iImageId+' option').each(function() {
                aValue.push($(this).attr('value'));
            });
            $('img.loading').css('display','none');
            if (!in_array(dirname+file,aValue)) {
                $(sImageFrameId+' #image_filename_'+iImageId).append('<option id="" value="'+dirname+file+'">'+file+'</option>');
            }*/
            if (dirname=='upload'||dirname=='') {dirname = '/';}
            $.ajax({
                type: "POST",
                url: sPath+"ajaxmain.php",
                data: "ajax=imagelist&dir=" + createPath[iImageId] + "&id=" + iImageId + "&idartlang=" + iIdArtLang + "&contenido="+sSession,
                success: function(msg) {
                    $('img.loading').css('display','none');
                    $(sImageFrameId+' #image_'+iImageId+'_directories #directoryFile_'+iImageId).html(msg);
                    addSelectAction(sImageFrameId, iImageId);
                }
            });
        }
    });
}

function basename(path) {
    return path.replace(/\\/g,'/').replace( /.*\//, '' );
}
function dirname(path) {
    return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');;
}

//Read a page's GET URL variables and return them as an associative array.
function getUrlVars() {
  var vars = [], hash;
  var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
  for(var i = 0; i < hashes.length; i++) {
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
    if (filetype=='js') {
        var js = document.createElement('script');
        js.setAttribute('language', 'javascript');
        js.setAttribute('type', 'text/javascript');
        js.setAttribute('src', filename);
        html_head.appendChild(js);
    } else if (filetype=='css') {
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
    if (filetype=='js') {
        afiles = included_files;
    } else if (filetype=='css') {
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
function initIncludedFiles() {
    var scripts = document.getElementsByTagName('script');
    /* put them into the array if they have a 'src' attribute */
    for (var i=0; i < scripts.length; i++) {
        if (scripts[i].src) {
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
        if (scripts[i].href) {
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

function showUrlforMeta(sImageFrameId,iImageId) {
    var str = "";
    var wert = "";
    $(sImageFrameId+" select#image_filename_"+iImageId+" option:selected").each(function() {
        str += $(this).text();
        wert += $(this).val();
    });
    $(sImageFrameId+' #image_meta_url_'+iImageId).html(wert);
}