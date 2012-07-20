/**
 * This file contains the cContentTypeImgEditor JS class.
 *
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 *
 * @author Fulai Zhang, Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * Creates a new cContentTypeImgEditor with the given properties.
 * You most probably want to call initialise() after creating a new object of this class.
 * 
 * @constructor
 * @property {String} frameId The ID of the frame in which the content type can be set up.
 * @property {String} imageId The ID of the button on which one clicks in order to edit the content type.
 * @property {String} pathBackend The path to the CONTENIDO backend.
 * @property {String} pathFrontend The path to the CONTENIDO frontend.
 * @property {Number} idArtLang The idArtLang of the article which is currently being edited.
 * @property {Number} id The ID of the content type, e.g. 3 if CMS_TEASER[3] is used.
 * @property {Array} fields Form element names which are used by this content type.
 * @property {String} prefix The prefix of the content type.
 * @property {String} session The session ID of the admin user.
 * @property {Object|String} settings The settings of this content type.
 */
function cContentTypeImgEditor(frameId, imageId, pathBackend, pathFrontend, idArtLang, id, fields, prefix, session, settings) {

    // call the constructor of the parent class with the same arguments
    cContentTypeAbstractTabbed.apply(this, arguments);

    /**
     * The path which has been selected by the user.
     * 
     * @type String
     */
    this.selectedPath = '';

}

// inherit from cContentTypeAbstractTabbed
cContentTypeImgEditor.prototype = new cContentTypeAbstractTabbed();
// correct the constructor function (it points to the cContentTypeAbstractTabbed constructor)
cContentTypeImgEditor.prototype.constructor = cContentTypeImgEditor;

/**
 * Initialises the content type by adding event handlers etc.
 * 
 * @override
 */
cContentTypeImgEditor.prototype.initialise = function() {
    // call the function of the parent so that it is initialised correctly
    cContentTypeAbstractTabbed.prototype.initialise.call(this);
    // call custom functions that attach custom event handlers etc.
    this.addNaviActions();
    this.addSelectAction();
    this.showFolderPath();
    this.createMKDir();
    this.showUrlforMeta();
};

/**
 * Loads external styles and scripts so that they are only loaded when they are
 * really needed.
 * 
 * @override
 */
cContentTypeImgEditor.prototype.loadExternalFiles = function() {
    // call the function of the parent so that all general files are included
    cContentTypeAbstractTabbed.prototype.loadExternalFiles.call(this);
    if ($('#cms_imgeditor_styles').length === 0) {
        $('head').append('<link rel="stylesheet" id="cms_imgeditor_styles" href="' + this.pathBackend + 'styles/content_types/cms_imgeditor.css" type="text/css" media="all" />');
    }
    conLoadFile(this.pathBackend + 'scripts/jquery/ajaxupload.js');
};

/**
 * Adds tabbing events to menubar of content type edit form. Lets the user
 * switch between the different tabs.
 * 
 * @override
 */
cContentTypeImgEditor.prototype.addTabbingEvents = function() {
    var self = this;
    // call the function of the parent so that the standard tab functionality works
    cContentTypeAbstractTabbed.prototype.addTabbingEvents.call(self);

    $(self.frameId + ' .menu li').click(function() {
        // if the upload tab is shown, show the directories tab, too
        if ($(this).hasClass('upload')) {
            $(self.frameId + ' .tabs #directories').show();
        }
    });
};

/**
 * Adds possibility to navigate through the upload folder by:
 * - adding possibility to expand and close directories
 * - updating the file list each time a new directory is selected
 */
cContentTypeImgEditor.prototype.addNaviActions = function() {
    var self = this;
    $(self.frameId + ' #directoryList_' + self.id + ' a[class="on"]').parent('div').unbind('click');
    $(self.frameId + ' #directoryList_' + self.id + ' a[class="on"]').parent('div').click(function() {
        // update the "active" class
        $.each($(self.frameId + ' div'), function() {  
            $(this).removeClass('active');
        });
        $(this).addClass('active');
        var dirname = $(this).children('a[class="on"]').attr('title');
        if (dirname === 'upload') {
            dirname = '/';
        }
        // update the file list each time a new directory is selected
        $.ajax({
            type: 'POST',
            url: self.pathBackend + 'ajaxmain.php',
            data: 'ajax=imagelist&dir=' + dirname + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
            success: function(msg) {
                $(self.frameId + ' #directoryFile_' + self.id).html(msg);
                // the items of the file select element have been changed, so add the event handlers again
                self.addSelectAction();
            }
        });
        self.showFolderPath();
        return false;
    });
    // add possibility to expand and close directories
    $(self.frameId + ' #directoryList_' + self.id + ' em a').unbind('click');
    $(self.frameId + ' #directoryList_' + self.id + ' em a').click(function() {
        var divContainer = $(this).parent().parent();
        var dirname = $(this).parent('em').parent().find('a[class="on"]').attr('title');        
        if (divContainer.next('ul').length > 0) {
            divContainer.next('ul').toggle(function() {
                if (divContainer.next('ul').css('display') === 'none') {
                    divContainer.parent().addClass('collapsed');
                } else {
                    divContainer.parent().removeClass('collapsed');
                }
            });            
        } else {
            $.ajax({
                type: 'POST',
                url: self.pathBackend + 'ajaxmain.php',
                data: 'ajax=dirlist&dir=' + dirname + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                success: function(msg) {
                    divContainer.after(msg);
                    divContainer.parent('li').removeClass('collapsed');
                    self.addNaviActions();
                }
            });
        }
        return false;
    });
};

/**
 * Updates the directory names in the upload tab.
 */
cContentTypeImgEditor.prototype.showFolderPath = function() {
    var self = this;
    // if there are no directories, set the active class for the root upload folder
    var directories = [];
    $(self.frameId + ' div[class="active"] a[class="on"]').each(function() {
        directories.push($(this).attr('title'));
    });
    if (directories.length < 1) {
        $(self.frameId + ' li#root>div').addClass('active');
    }

    // get the selected directory and save it
    self.selectedPath = $(self.frameId + ' div[class="active"] a[class="on"]').attr('title');
    var selectedPath = self.selectedPath;
    if (selectedPath !== '' && selectedPath !== 'upload') {
        selectedPath += '/';
    } else {
        selectedPath = '';
    }

    // show the selected directory in the upload tab and set the form values accordingly
    $(self.frameId + ' #caption1').text(selectedPath);
    $(self.frameId + ' #caption2').text(selectedPath);
    $(self.frameId + ' form[name="newdir"] input[name="path"]').val(selectedPath);        
    $(self.frameId + ' form[name="properties"] input[name="path"]').val(selectedPath);

    setTimeout(function() {
        self.imageFileUpload();
    }, 1000);
};

/**
 * Updates the image preview and the image's meta data each time a new image is selected.
 */
cContentTypeImgEditor.prototype.addSelectAction = function() {
    var self = this;
    if ($('#image_filename_' + self.id).length > 0) {
        $(self.frameId + ' select[class="text_medium"]').change(function() {
            var filename = $('select#image_filename_' + self.id + ' option:selected').val();
            // update the image preview element with the new selected image
            if (filename === '') {
                $('#directoryShow_' + self.id).html('');                
            } else {
                var url = self.pathFrontend + 'upload/' + filename;
                $.ajax({
                    type: 'POST',
                    url: self.pathBackend + 'ajaxmain.php',
                    data: 'ajax=scaleImage&url=' + url + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                    success: function(msg) {
                        $('#directoryShow_' + self.id).html('<div><img src="' + msg + '"/></div>');
                    }
                });
            }
            // update image meta data
            if (filename === '') {
                $('#image_medianame_' + self.id).val('');
                $('#image_description_' + self.id).val('');
                $('#image_keywords_' + self.id).val('');
                $('#image_internal_notice_' + self.id).val('');
                $('#image_copyright_' + self.id).val('');
            } else {
                $.ajax({
                    type: 'POST',
                    url: self.pathBackend + 'ajaxmain.php',
                    data: 'ajax=loadImageMeta&filename=' + filename + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                    success: function(msg) {
                        var imageMeta = $.parseJSON(msg);
                        $('#image_medianame_' + self.id).val(imageMeta.medianame);
                        $('#image_description_' + self.id).val(imageMeta.description);
                        $('#image_keywords_' + self.id).val(imageMeta.keywords);
                        $('#image_internal_notice_' + self.id).val(imageMeta.internal_notice);
                        $('#image_copyright_' + self.id).val(imageMeta.copyright);
                        self.showUrlforMeta();
                    }
                });
            }
        });
    }
};

/**
 * Creates a new directory and updates the directory list accordingly.
 */
cContentTypeImgEditor.prototype.createMKDir = function() {
    var self = this;
    $(self.frameId + ' #upload form[name="newdir"] input[type="image"]').unbind('click');
    $(self.frameId + ' #upload form[name="newdir"] input[type="image"]').click(function() {
        var folderName = $(self.frameId + ' input[name="foldername"]').val();
        // if folder name is empty, do nothing
        if (folderName === '') {
            return false;
        }
        var dirname = '';
        if (self.selectedPath !== '' && self.selectedPath !== 'upload') {
            dirname = self.selectedPath + '/';
        }
        // create folder
        $.ajax({
            type: 'POST',
            url: self.pathBackend + 'ajaxmain.php',
            data: 'ajax=upl_mkdir&id=' + self.id + '&idartlang=' + self.idArtLang + '&path=' + dirname + '&foldername=' + folderName + '&contenido=' + self.session,
            success: function(msg) {
                if (msg !== '0702') {
                    // reset input field
                    $('input[name="foldername"]').val('');
                    // update directory list
                    $.ajax({
                        type: 'POST',
                        url: self.pathBackend + 'ajaxmain.php',
                        data: 'ajax=dirlist&idartlang=' + self.idArtLang + '&id=' + self.id + '&dir=' + dirname + '&contenido=' + self.session,
                        success: function(msg) {
                            var title;
                            if (self.selectedPath === 'upload') {
                                title = folderName;
                            } else {
                                title = dirname + folderName;
                            }
                            var titles = [];
                            $(self.frameId + ' div a[class="on"]').each(function() {
                                titles.push($(this).attr('title'));
                            });

                            if ($.inArray(title, titles) === -1) {
                                $('div.cms_image .con_str_tree li div>a').each(function(index) {
                                    if ($(this).attr('title') === self.selectedPath) {
                                        $(this).parent().parent('li:has(ul)').children('ul').remove();
                                        $(this).parent().after(msg);
                                        $(this).parent().parent('li').removeClass('collapsed');
                                        self.addNaviActions();
                                    }                                    
                                });                    
                            }
                        }
                    }); 
                }
            }
        });
        return false;
    });
};

/**
 * Uploads an image.
 */
cContentTypeImgEditor.prototype.imageFileUpload = function() {
    var self = this;
    var dirname = '';
    if (self.selectedPath !== '' && self.selectedPath !== 'upload') {
        dirname = self.selectedPath + '/';
    }

    new AjaxUpload('#cms_image_m' + self.id, {
        action: self.pathBackend + 'ajaxmain.php?ajax=upl_upload&id=' + self.id + '&idartlang=' + self.idArtLang + '&path=' + dirname + '&contenido=' + self.session,
        name: 'file[]',
        onSubmit: function() {
            $('img.loading').show();
        },
        onComplete: function(file) {
            if (dirname === 'upload' || dirname === '') {
                dirname = '/';
            }
            $.ajax({
                type: 'POST',
                url: self.pathBackend + 'ajaxmain.php',
                data: 'ajax=imagelist&dir=' + self.selectedPath + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                success: function(msg) {
                    $('img.loading').hide();
                    $(self.frameId + ' #directoryFile_' + self.id).html(msg);
                    self.addSelectAction();
                }
            });    
        }
    });
};

/**
 * Updates the filename in the meta tab.
 */
cContentTypeImgEditor.prototype.showUrlforMeta = function() {
    var filename = $(this.frameId + ' select#image_filename_' + this.id + ' option:selected').val();
    $(this.frameId + ' #image_meta_url_' + this.id).html(filename);
};
