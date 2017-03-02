/* global Con: true, jQuery: true */

/**
 * This file contains the cContentTypeImgeditor JS class.
 *
 * @module     content-type
 * @submodule  content-type-cms-imgeditor
 * @package    Core
 * @subpackage Content Type
 * @author     Fulai Zhang
 * @author     Simon Sprankel
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {
//    'use strict';

    var NAME = 'content-type-cms-imgeditor';

    /**
     * Creates a new cContentTypeImgeditor with the given properties.
     * You most probably want to call initialise() after creating a new object of this class.
     * @class cContentTypeImgeditor
     * @extends cContentTypeAbstractTabbed
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
    function cContentTypeImgeditor(frameId, imageId, pathBackend, pathFrontend, idArtLang, id, fields, prefix, session, settings) {

        // call the constructor of the parent class with the same arguments
        Con.cContentTypeAbstractTabbed.apply(this, arguments);

        /**
         * The path which has been selected by the user.
         * @property selectedPath
         * @type {String}
         */
        this.selectedPath = '';

        /**
         * Defines if needed js scripts were loaded
         * @property scriptLoaded
         * @type {Number}
         */
        this.scriptLoaded = 0;

    }

    // inherit from cContentTypeAbstractTabbed
    cContentTypeImgeditor.prototype = new Con.cContentTypeAbstractTabbed();
    // correct the constructor function (it points to the cContentTypeAbstractTabbed constructor)
    cContentTypeImgeditor.prototype.constructor = cContentTypeImgeditor;

    /**
     * Initialises the content type by adding event handlers etc.
     * @method initialise
     * @override
     */
    cContentTypeImgeditor.prototype.initialise = function() {
        // call the function of the parent so that it is initialised correctly
        Con.cContentTypeAbstractTabbed.prototype.initialise.call(this);
        // call custom functions that attach custom event handlers etc.
        this.addDirectoryList();
        this.addNaviActions();
        this.addSelectAction();
        this.showFolderPath();
        this.createMKDir();
        this.showUrlforMeta();
    };

    /**
     * Loads external styles and scripts so that they are only loaded when they are
     * really needed.
     * @method loadExternalFiles
     * @override
     */
    cContentTypeImgeditor.prototype.loadExternalFiles = function() {
        // call the function of the parent so that all general files are included
        Con.cContentTypeAbstractTabbed.prototype.loadExternalFiles.call(this);

        Con.Loader.get(
            [this.pathBackend + 'styles/content_types/cms_imgeditor.css', this.pathBackend + 'scripts/jquery/ajaxupload.js'],
            cContentTypeImgeditor.prototype.initUpload,
            this
        );
    };

    /**
     * Inits Upload action after needed scripts were loaded
     * @method initUpload
     */
    cContentTypeImgeditor.prototype.initUpload = function() {
        var self = this;
        self.imageFileUpload();
        self.scriptLoaded = 1;
    };

    /**
     * Adds tabbing events to menubar of content type edit form. Lets the user
     * switch between the different tabs.
     * @method addTabbingEvents
     * @override
     */
    cContentTypeImgeditor.prototype.addTabbingEvents = function() {
        var self = this;
        // call the function of the parent so that the standard tab functionality works
        Con.cContentTypeAbstractTabbed.prototype.addTabbingEvents.call(self);

        $(self.frameId + ' .menu li').click(function() {
            // if the upload tab is shown, show the directories tab, too
            if ($(this).hasClass('upload')) {
                $(self.frameId + ' .tabs #directories').show();
            }
        });
    };

    /**
     * Adds the directory list
     */
    cContentTypeImgeditor.prototype.addDirectoryList = function() {
        var self = this;
        var id = self.id;

        var dlist = $(self.frameId + ' #directoryList_' + id + ' em a');
        var divContainer = dlist.parent().parent();

        $('#cms_imgeditor_' + id).on('click', function(e) {
            $.ajax({
                type: 'POST',
                url: self.pathBackend + 'ajaxmain.php',
                data: 'ajax=dirlist&dir=&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                success: function (msg) {
                    if (Con.checkAjaxResponse(msg) === false) {
                        return false;
                    }

                    divContainer.after(msg);
                    divContainer.parent('li').removeClass('collapsed');
                    self.addNaviActions();
                }
            });
        });
        return false;
    }

    /**
     * Adds possibility to navigate through the upload folder by:
     * - adding possibility to expand and close directories
     * - updating the file list each time a new directory is selected
     * @method addNaviActions
     */
    cContentTypeImgeditor.prototype.addNaviActions = function() {
        var self = this;

        $(self.frameId + ' ul.menu li.upload').click(function() {
            if (self.scriptLoaded == 1) {
                self.imageFileUpload();
            }
        });

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
					if (Con.checkAjaxResponse(msg) === false)  {
						return false;
					}

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
        $(self.frameId + ' #directoryList_' + self.id + ' em a').click(function(e) {
        	e.preventDefault();
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
						if (Con.checkAjaxResponse(msg) === false)  {
							return false;
						}

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
     * @method showFolderPath
     */
    cContentTypeImgeditor.prototype.showFolderPath = function() {
        var self = this;
        // if there are no directories, set the active class for the root upload folder
        var directories = [];
        $(self.frameId + ' div[class="active"] a[class="on"]').each(function() {
            directories.push($(this).attr('title'));
        });
        if (directories.length < 1) {
            $(self.frameId + ' li.root>div').addClass('active');
        }

        // get the selected directory and save it
        self.selectedPath = $(self.frameId + ' div[class="active"] a[class="on"]').attr('title');
        var selectedPath = self.selectedPath;
        if (selectedPath !== '' && selectedPath !== 'upload') {
            selectedPath += '/';
        } else {
            selectedPath = '/';
        }

        // show the selected directory in the upload tab and set the form values accordingly
        $(self.frameId + ' #caption1').text(selectedPath);
        $(self.frameId + ' #caption2').text(selectedPath);
        $(self.frameId + ' form[name="newdir"] input[name="path"]').val(selectedPath);
        $(self.frameId + ' form[name="properties"] input[name="path"]').val(selectedPath);


        if (self.scriptLoaded == 1) {
            self.imageFileUpload();
        }
    };

    /**
     * Updates the image preview and the image's meta data each time a new image is selected.
     * @method addSelectAction
     */
    cContentTypeImgeditor.prototype.addSelectAction = function() {
        var self = this;
        if ($('#image_filename_' + self.id).length > 0) {
            $(self.frameId + ' select[name="image_filename"]').change(function() {
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
                            $('#directoryShow_' + self.id).html('<div><img src="' + msg + '" alt=""/></div>');
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
							if (Con.checkAjaxResponse(msg) === false)  {
								return false;
							}

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
     * @method createMKDir
     */
    cContentTypeImgeditor.prototype.createMKDir = function() {
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
					if (Con.checkAjaxResponse(msg) === false)  {
						return false;
					}

                    if (msg === '1') {
                        // reset input field
                        $('input[name="foldername"]').val('');
                        // update directory list
                        $.ajax({
                            type: 'POST',
                            url: self.pathBackend + 'ajaxmain.php',
                            data: 'ajax=dirlist&idartlang=' + self.idArtLang + '&id=' + self.id + '&dir=' + dirname + '&contenido=' + self.session,
                            success: function(msg) {
								if (Con.checkAjaxResponse(msg) === false)  {
									return false;
								}

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
                                    $(self.frameId + ' .con_str_tree li div>a').each(function(index) {
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
                    } else {
                        alert(msg);
                    }
                }
            });
            return false;
        });
    };

    /**
     * Uploads an image.
     * @method imageFileUpload
     */
    cContentTypeImgeditor.prototype.imageFileUpload = function() {
        var self = this;
        var dirname = '';
        if (self.selectedPath !== '' && self.selectedPath !== 'upload') {
            dirname = self.selectedPath + '/';
        }

        $('body > input[type=file]').remove();
        $('#cms_image_m' + self.id).unbind();


        $(self.frameId + ' input.jqueryAjaxUpload').unbind();
        $(self.frameId + ' input.jqueryAjaxUpload').fileupload({
            url: self.pathBackend + 'ajaxmain.php?ajax=upl_upload&id=' + self.id + '&idartlang=' + self.idArtLang + '&path=' + dirname + '&contenido=' + self.session,
            dataType: 'json',
            autoUpload: true,
            forceIframeTransport: true,
            multipart: true,
            start: function(e) {
                $(self.frameId + ' img.loading').show();
                $(self.frameId + ' input.jqueryAjaxUpload').css('visibility', 'hidden');
            },
            always: function(e, data) {
                if (dirname === 'upload' || dirname === '') {
                    dirname = '/';
                }
                $.ajax({
                    type: 'POST',
                    url: self.pathBackend + 'ajaxmain.php',
                    data: 'ajax=imagelist&dir=' + dirname + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                    success: function(msg) {
						if (Con.checkAjaxResponse(msg) === false)  {
							return false;
						}

                        $(self.frameId + ' img.loading').hide();
                        $(self.frameId + ' input.jqueryAjaxUpload').css('visibility', 'visible');
                        $(self.frameId + ' #directoryFile_' + self.id).html(msg);
                        self.addSelectAction();
                        if (self.scriptLoaded == 1) {
                            self.imageFileUpload();
                        }
                    }
                });
            }
        });
    };

    /**
     * Updates the filename in the meta tab.
     * @method showUrlforMeta
     */
    cContentTypeImgeditor.prototype.showUrlforMeta = function() {
        var filename = $(this.frameId + ' select#image_filename_' + this.id + ' option:selected').val();
        $(this.frameId + ' #image_meta_url_' + this.id).html(filename);
    };


    Con.cContentTypeImgeditor = cContentTypeImgeditor;

})(Con, Con.$);
