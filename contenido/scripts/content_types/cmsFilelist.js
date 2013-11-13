/* global Con: true, jQuery: true */

/**
 * This file contains the cContentTypeFilelist JS class.
 *
 * @module  content-type
 * @submodule  content-type-cms-filelist
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 *
 * @author Dominik Ziegler, Simon Sprankel, Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

(function(Con, $) {
//    'use strict';

    var NAME = 'content-type-cms-filelist';

    /**
     * Creates a new cContentTypeFilelist with the given properties.
     * You most probably want to call initialise() after creating a new object of this class.
     * @class cContentTypeFilelist
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
    function cContentTypeFilelist(frameId, imageId, pathBackend, pathFrontend, idArtLang, id, fields, prefix, session, settings) {
        // call the constructor of the parent class with the same arguments
        Con.cContentTypeAbstractTabbed.apply(this, arguments);
    }

    //inherit from cContentTypeAbstractTabbed
    cContentTypeFilelist.prototype = new Con.cContentTypeAbstractTabbed();
    //correct the constructor function (it points to the cContentTypeAbstractTabbed constructor)
    cContentTypeFilelist.prototype.constructor = cContentTypeFilelist;

    /**
     * Initialises the content type by adding event handlers etc.
     *
     * @override
     */
    cContentTypeFilelist.prototype.initialise = function() {
        // call the function of the parent so that it is initialised correctly
        Con.cContentTypeAbstractTabbed.prototype.initialise.call(this);
        // call custom functions that attach custom event handlers etc.
        this.addManualFileListEvent();
        this.addClickEvent();
    };

    /**
     * Loads external styles and scripts so that they are only loaded when they are
     * really needed.
     *
     * @override
     */
    cContentTypeFilelist.prototype.loadExternalFiles = function() {
        // call the function of the parent so that all general files are included
        Con.cContentTypeAbstractTabbed.prototype.loadExternalFiles.call(this);
        if ($('#cms_filelist_styles').length === 0) {
            $('head').append('<link rel="stylesheet" id="cms_filelist_styles" href="' + this.pathBackend + 'styles/content_types/cms_filelist.css" type="text/css" media="all" />');
        }
    };

    /**
     * Function adds event to add new article to multiple select box for articles
     * Function also checks if article is already in that list
     */
    cContentTypeFilelist.prototype.addManualFileListEvent = function() {
        var self = this;
        $(self.frameId + ' #add_file').css('cursor', 'pointer').click(function() {
            self.addManualFileListEntry();
        });
    };

    /**
     * Function adds new article to multiple select box for articles
     * Function also checks if article is already in that list
     */
    cContentTypeFilelist.prototype.addManualFileListEntry = function() {
        var filename = $(this.frameId + ' #filelist_filename_' + this.id).val();
        var name = '';
        var exists = false;

        if (filename === '') {
            return;
        }

        $(this.frameId + ' #filelist_manual_files_' + this.id + ' option').each(function() {
            if (filename === $(this).val()) {
                exists = true;
            }
        });

        $(this.frameId + ' #filelist_filename_' + this.id + ' option:selected').each(function() {
            name = $(this).html();
        });

        if (!exists) {
            $(this.frameId + ' #filelist_manual_files_' + this.id).prepend('<option value="' + filename + '" selected="selected">' + name + '</option>');
        }
    };

    /**
     * Function adds double click events to all current listed articles for manual FileList
     * in case of a double click this selected article is removed from list
     */
    cContentTypeFilelist.prototype.addClickEvent = function() {
        var self = this;
        self.addNaviActions();
        self.addExtensionActions();

        // check if manual filelist settings should be shown at the beginning
        if ($(self.frameId + ' #filelist_manual_' + self.id).is(':checked')) {
            $(self.frameId + ' #manual_filelist_setting').show();
        } else {
            $(self.frameId + ' #manual_filelist_setting').hide();
        }

        $(self.frameId + ' #filelist_manual_' + self.id).click(function() {
            $(self.frameId + ' #manual_filelist_setting').slideToggle();
        });

        // check if meta data should be shown at the beginning
        if ($(self.frameId + ' #filelist_incl_metadata_' + self.id).is(':checked')) {
            $(self.frameId + ' #metaDataList').show();
        } else {
            $(self.frameId + ' #metaDataList').hide();
        }

        $(self.frameId + ' #filelist_incl_metadata_' + self.id).click(function() {
            $(self.frameId + ' #metaDataList').slideToggle();
        });

        $(self.frameId + ' #filelist_manual_files_' + self.id).dblclick(function() {
            $(self.frameId + ' #filelist_manual_files_' + self.id + ' option').each(function() {
                if ($(this).is(':selected')) {
                    $(this).remove();
                };
            });
            $(self.frameId + ' #filelist_manual_files_' + self.id + ' option').prop('selected', 'selected');
        });
    };

    /**
     * Adds possibility to select all file extensions at once and
     * disables file extension select if file extensions should be ignored.
     */
    cContentTypeFilelist.prototype.addExtensionActions = function() {
        var self = this;
        // let the user select all file extensions at once
        $(self.frameId + ' #filelist_all_extensions').css('cursor', 'pointer');
        $(self.frameId + ' #filelist_all_extensions').click(function() {
            // only react if the extensions should not be ignored
            if ($(self.frameId + ' #filelist_extensions_' + self.id).is(':not(:disabled)')) {
                // check if all options are selected
                var allSelected = true;
                $(self.frameId + ' #filelist_extensions_' + self.id + ' option').each(function() {
                    if (!$(this).is(':selected')) {
                        allSelected = false;
                    }
                });
                if (allSelected) {
                    // all options are selected, so unselect them
                    $(self.frameId + ' #filelist_all_extensions').css('font-weight', 'normal');
                    $(self.frameId + ' #filelist_extensions_' + self.id + ' option').prop('selected', false);
                } else {
                    // some options are not selected, so select them
                    $(self.frameId + ' #filelist_all_extensions').css('font-weight', 'bold');
                    $(self.frameId + ' #filelist_extensions_' + self.id + ' option').prop('selected', 'selected');
                }
            }
            return false;
        });

        // disable the file extension select if file extensions should be ignored
        $(self.frameId + ' #filelist_ignore_extensions_' + self.id).click(function() {
            if ($(this).is(':checked')) {
                $(self.frameId + ' #filelist_extensions_' + self.id).attr('disabled', 'disabled');
            } else {
                $(self.frameId + ' #filelist_extensions_' + self.id).removeAttr('disabled');
            }
        });
    };

    /**
     * Adds possibility to navigate through the upload folder by:
     * - adding possibility to expand and close directories
     * - updating the file list each time a new directory is selected
     */
    cContentTypeFilelist.prototype.addNaviActions = function() {
        var self = this;
        $(self.frameId + ' #manual #directoryList_' + self.id + '_manual a[class="on"]').parent('div').unbind('click');
        $(self.frameId + ' #manual #directoryList_' + self.id + '_manual a[class="on"]').parent('div').click(function() {
            var dirname = $(this).children('a[class="on"]').attr('title');
            $.ajax({
                type: 'POST',
                url: self.pathBackend + 'ajaxmain.php',
                data: 'ajax=filelist&dir=' + dirname + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                success: function(msg) {
                    $(self.frameId + ' #manual #filelist_filename_' + self.id).replaceWith(msg);
                }
            });

            return false;
        });

        $(self.frameId + ' #directories #directoryList_' + self.id + ' a[class="on"]').parent('div').unbind('click');
        $(self.frameId + ' #directories #directoryList_' + self.id + ' a[class="on"]').parent('div').click(function() {
            if ($(this).hasClass('active')) {
                $(this).removeClass('active');
            } else {
                $(this).addClass('active');
            }

            return false;
        });

        $(self.frameId + ' .directoryList em a').unbind('click');
        $(self.frameId + ' .directoryList em a').click(function() {
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
     * Adds save event to the save button of content type edit form.
     *
     * @override
     */
    cContentTypeFilelist.prototype.addSaveEvent = function() {
        var self = this;
        $(self.frameId + ' .save_settings').click(function() {
            // the chosen directory is no form field, so add it to the editform manually
            var value = '';
            $(self.frameId + ' #directories #directoryList_' + self.id + ' div[class="active"]').each(function() {
                if (value === '') {
                    value = $(this).find('a[class="on"]').attr('title');
                } else {
                    value += ',' + $(this).find('a[class="on"]').attr('title');
                }
            });
            self.appendFormField('filelist_array_directories', value);
            // remove the filelist_directories item from the fields so that it is not set again
            self.fields = $.grep(self.fields, function(e) {
                return e !== 'filelist_directories';
            });
        });

        // call the function of the parent so that the standard save functionality works
        Con.cContentTypeAbstractTabbed.prototype.addSaveEvent.call(this);
    };


    Con.cContentTypeFilelist = cContentTypeFilelist;

    // @deprecated [2013-11-10] Assign to windows scope (downwards compatibility)
    window.cContentTypeFilelist = cContentTypeFilelist;

})(Con, Con.$);
