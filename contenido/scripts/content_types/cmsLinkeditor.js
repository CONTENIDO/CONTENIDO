/**
 * This file contains the cContentTypeAbstractTabbed JS class.
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
 * Creates a new cContentTypeLinkeditor with the given properties.
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
function cContentTypeLinkeditor(frameId, imageId, pathBackend, pathFrontend, idArtLang, id, fields, prefix, session, settings) {

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
cContentTypeLinkeditor.prototype = new cContentTypeAbstractTabbed();
// correct the constructor function (it points to the cContentTypeAbstractTabbed constructor)
cContentTypeLinkeditor.prototype.constructor = cContentTypeLinkeditor;

/**
 * Initialises the content type by adding event handlers etc.
 *
 * @override
 */
cContentTypeLinkeditor.prototype.initialise = function() {
    // call the function of the parent so that it is initialised correctly
    cContentTypeAbstractTabbed.prototype.initialise.call(this);
    // call custom functions that attach custom event handlers etc.
    this.addNaviActions();
};

/**
 * Loads external styles and scripts so that they are only loaded when they are
 * really needed.
 *
 * @override
 */
cContentTypeLinkeditor.prototype.loadExternalFiles = function() {
    // call the function of the parent so that all general files are included
    cContentTypeAbstractTabbed.prototype.loadExternalFiles.call(this);
    if ($('#cms_linkeditor_styles').length === 0) {
        $('head').append('<link rel="stylesheet" id="cms_linkeditor_styles" href="' + this.pathBackend + 'styles/content_types/cms_linkeditor.css" type="text/css" media="all" />');
    }
    conLoadFile(this.pathBackend + 'scripts/jquery/fileuploader.js');
};

/**
 * Adds tabbing events to menubar of content type edit form. Lets the user
 * switch between the different tabs.
 *
 * @override
 */
cContentTypeLinkeditor.prototype.addTabbingEvents = function() {
    var self = this;
    // call the function of the parent so that the standard tab functionality works
    cContentTypeAbstractTabbed.prototype.addTabbingEvents.call(self);

    // show the tab of the currently active link type and hide the others
    $(self.frameId + ' .tabs > div').hide();
    var linkeditorType = self.settings.linkeditor_type;
    if (!linkeditorType) {
        linkeditorType = 'external';
    }
    $(self.frameId + ' .tabs #' + linkeditorType).show();
    // set the active class for the corresponding menu entry and remove it from the others
    $(self.frameId + ' .menu li').removeClass('active');
    $(self.frameId + ' .menu li.' + linkeditorType).addClass('active');

    // show the basic settings "tab" any time
    $(self.frameId + ' .tabs #basic-settings').show();
    $(self.frameId + ' .menu li').click(function() {
        $(self.frameId + ' .tabs #basic-settings').show();
    });
};

/**
 * Adds possibility to navigate through the upload folder by:
 * - adding possibility to expand and close directories
 * - updating the file list each time a new directory is selected
 */
cContentTypeLinkeditor.prototype.addNaviActions = function() {
    var self = this;
    $(self.frameId + ' #directoryList_' + self.id + ' a[class="on"]').parent('div').unbind('click');
    $(self.frameId + ' #directoryList_' + self.id + ' a[class="on"]').parent('div').click(function() {
        // update the "active" class
        $.each($(self.frameId + ' div'), function() {
            $(this).removeClass('active');
        });
        $(this).addClass('active');
        var idcat = $(this).children('a[class="on"]').attr('title');

        // update the file list each time a new directory is selected
        $.ajax({
            type: 'POST',
            url: self.pathBackend + 'ajaxmain.php',
            data: 'ajax=linkeditorfilelist&idcat=' + idcat + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
            success: function(msg) {
                $(self.frameId + ' #directoryFile_' + self.id).html(msg);
            }
        });
        return false;
    });

    // add possibility to expand and close directories
    $(self.frameId + ' #internal #directoryList_' + self.id + ' em a').unbind('click');
    $(self.frameId + ' #internal #directoryList_' + self.id + ' em a').click(function() {
        var parentidcat = $(this).parent('em').parent().parent().parent().find('div a[class="on"]').attr('title');
        var level = $(this).parents('ul').length - 1;
        var divContainer = $(this).parent().parent();
        if (divContainer.next('ul').length > 0) {
            divContainer.next('ul').toggle(function() {
                if (divContainer.next('ul').is(':hidden')) {
                    divContainer.parent().addClass('collapsed');
                } else {
                    divContainer.parent().removeClass('collapsed');
                }
            });
        } else {
            $.ajax({
                type: 'POST',
                url: self.pathBackend + 'ajaxmain.php',
                data: 'ajax=linkeditordirlist&level=' + level + '&parentidcat=' + parentidcat + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                success: function(msg) {
                    divContainer.after(msg);
                    divContainer.parent('li').removeClass('collapsed');
                    self.addNaviActions();
                }
            });
        }
        return false;
    });

    $(self.frameId + ' #file #directoryList_' + self.id + ' a[class="on"]').parent('div').unbind('click');
    $(self.frameId + ' #file #directoryList_' + self.id + ' a[class="on"]').parent('div').click(function() {
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
            data: 'ajax=linkeditorimagelist&dir=' + dirname + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
            success: function(msg) {
                $(self.frameId + ' #file #directoryFile_' + self.id).html(msg);
            }
        });
        self.showFolderPath();
        return false;
    });
    // add possibility to expand and close directories
    $(self.frameId + ' #file #directoryList_' + self.id + ' em a').unbind('click');
    $(self.frameId + ' #file #directoryList_' + self.id + ' em a').click(function() {
        var divContainer = $(this).parent().parent();
        var dirname = $(this).parent('em').parent().find('a[class="on"]').attr('title');
        if (divContainer.next('ul').length > 0) {
            divContainer.next('ul').toggle(function() {
                if (divContainer.next('ul').is(':hidden')) {
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
 * Updates the divs in which the selected folder is displayed
 * every time a new folder is selected.
 */
cContentTypeLinkeditor.prototype.showFolderPath = function() {
    var self = this;
    // if there are no directories, set the active class for the root upload folder
    titles = Array();
    $(self.frameId + ' div[class="active"] a[class="on"]').each(function() {
        titles.push($(this).attr('title'));
    });
    if (titles.length < 1){
        $(self.frameId + ' li#root>div').addClass('active');
    }

    // get the selected directory and save it
    var selectedPath = $(self.frameId + ' div[class="active"] a[class="on"]').attr('title');
    self.selectedPath = selectedPath;
    if (selectedPath != '' && selectedPath != 'upload') {
        selectedPath += '/';
    } else {
        selectedPath = '';
    }

    $(self.frameId + ' #caption1').text(selectedPath);
    $(self.frameId + ' #caption2').text(selectedPath);
    $(self.frameId + ' form[name="newdir"] input[name="path"]').val(selectedPath);
    $(self.frameId + ' form[name="properties"] input[name="path"]').val(selectedPath);

    setTimeout(function() {
        self.linkEditorFileUpload();
    }, 1000);
};

/**
 * Uploads an image.
 */
cContentTypeLinkeditor.prototype.linkEditorFileUpload = function() {
    var self = this;
    var dirname = '';
    if (self.selectedPath != '' && self.selectedPath != 'upload') {
        dirname = self.selectedPath + '/';
    }

    new qq.FileploaderBasic('#cms_linkeditor_m' + self.id, {
        action: self.pathBackend + 'ajaxmain.php?ajax=upl_upload&id=' + self.id + '&idartlang=' + self.idArtLang + '&path=' + dirname + '&contenido=' + self.session,
        inputName: 'file[]',
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
                data: 'ajax=linkeditorimagelist&dir=' + self.selectedPath + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                success: function(msg) {
                    $('img.loading').hide();
                    $(self.frameId + ' #directoryFile_' + self.id).html(msg);
                }
            });
        }
    });
};

/**
 * Creates a new directory and updates the directory list accordingly.
 */
cContentTypeLinkeditor.prototype.createMKDir = function() {
    var self = this;
    $(self.frameId + ' #upload form[name="newdir"] input[type="image"]').unbind('click');
    $(self.frameId + ' #upload form[name="newdir"] input[type="image"]').click(function() {
        var folderName = $(self.frameId + ' input[name="foldername"]').val();
        // if folder name is empty, do nothing
        if (folderName === '') {
            return false;
        }
        var dirname = '';
        if (self.selectedPath != '' && self.selectedPath != 'upload') {
            dirname = self.selectedPath + '/';
        }
        // create folder
        $.ajax({
            type: 'POST',
            url: self.pathBackend + 'ajaxmain.php',
            data: 'ajax=upl_mkdir&id=' + self.id + '&idartlang=' + self.idArtLang + '&path=' + dirname + '&foldername=' + folderName + '&contenido=' + self.session,
            success: function(msg) {//make create folder
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
                            if (self.selectedPath === 'upload'){
                                title = folderName;
                            } else {
                                title = dirname + folderName;
                            }
                            var titles = [];
                            $(self.frameId + ' div a[class="on"]').each(function() {
                                titles.push($(this).attr('title'));
                            });

                            if ($.inArray(title, titles) === -1) {
                                $('div.cms_linkeditor .con_str_tree li div>a').each(function(index) {
                                    if ($(this).attr('title') == self.selectedPath) {
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
 * Adds save event to the save button of content type edit form.
 *
 * @override
 */
cContentTypeLinkeditor.prototype.addSaveEvent = function() {
    var self = this;
    $(self.frameId + ' .save_settings').click(function() {
        // the link type is no form field, so add it to the editform manually
        var type = '';
        $(self.frameId + ' .menu li').each(function() {
            // if this is the active tab, extract the tab name
            if ($(this).hasClass('active')) {
                var cssClass = $(this).attr('class');
                type = $.trim(cssClass.replace('active', ''));
            }
        });
        self.appendFormField('linkeditor_type', type);
        // remove the linkeditor_type item from the fields so that it is not set again
        self.fields = $.grep(self.fields, function(e) {
            return e !== 'linkeditor_type';
        });
    });

    // call the function of the parent so that the standard save functionality works
    cContentTypeAbstractTabbed.prototype.addSaveEvent.call(self);
};
