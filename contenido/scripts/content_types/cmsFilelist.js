/* global Con: true, jQuery: true */

/**
 * This file contains the cContentTypeFilelist JS class.
 *
 * @module     content-type
 * @submodule  content-type-cms-filelist
 * @package    Core
 * @subpackage Content Type
 * @version    SVN Revision $Rev$
 * @author     Dominik Ziegler, Simon Sprankel, Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {
//    'use strict';

    var NAME = 'content-type-cms-filelist';

    /**
     * Creates a new cContentTypeFilelist with the given properties.
     * You most probably want to call initialise() after creating a new object of this class.
     * @class cContentTypeFilelist
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
    function cContentTypeFilelist(frameId, imageId, pathBackend, pathFrontend, idArtLang, id, fields, prefix, session, settings) {
        // call the constructor of the parent class with the same arguments
        Con.cContentTypeAbstractTabbed.apply(this, arguments);

        // Define some jQuery selectors here for later usage
        this.SELECTOR_FILELIST_MANUAL_FILES = $(this.frameId + ' #filelist_manual_files_' + this.id);
        this.SELECTOR_FILELIST_FILENAME = this.frameId + ' #filelist_filename_' + this.id;
        this.SELECTOR_CHK_FILELIST_MANUAL = this.frameId + ' #filelist_manual_' + this.id;
        this.SELECTOR_MANUAL_FILELIST_SETTING = this.frameId + ' #manual_filelist_setting';
        this.SELECTOR_METADATALIST = this.frameId + ' #metaDataList';
        this.SELECTOR_FILELIST_INCL_METADATA = this.frameId + ' #filelist_incl_metadata_' + this.id;
        this.SELECTOR_FILELIST_ALL_EXTENSIONS = this.frameId + ' #filelist_all_extensions';
        this.SELECTOR_FILELIST_EXTENSIONS = this.frameId + ' #filelist_extensions_' + this.id;
        this.SELECTOR_FILELIST_IGNORE_EXTENSIONS = this.frameId + ' #filelist_ignore_extensions_' + this.id;
        this.SELECTOR_SAVE_SETTINGS = this.frameId + ' .save_settings';
        this.SELECTOR_DIRLIST = this.frameId + ' #directories #directoryList_' + this.id + ' li li div';
        this.SELECTOR_DIRLIST_ACTIVE = this.frameId + ' #directories #directoryList_' + this.id + ' div[class="active"]';
        this.SELECTOR_DIRLIST_MANUAL = this.frameId + ' #manual #directoryList_' + this.id + '_manual li li div';
    }

    //inherit from cContentTypeAbstractTabbed
    cContentTypeFilelist.prototype = new Con.cContentTypeAbstractTabbed();
    //correct the constructor function (it points to the cContentTypeAbstractTabbed constructor)
    cContentTypeFilelist.prototype.constructor = cContentTypeFilelist;

    /**
     * Initialises the content type by adding event handlers etc.
     * @method initialise
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
     * @method loadExternalFiles
     * @override
     */
    cContentTypeFilelist.prototype.loadExternalFiles = function() {
        // call the function of the parent so that all general files are included
        Con.cContentTypeAbstractTabbed.prototype.loadExternalFiles.call(this);
        Con.Loader.get(this.pathBackend + 'styles/content_types/cms_filelist.css');
    };

    /**
     * Function adds event to add new article to multiple select box for articles
     * Function also checks if article is already in that list
     * @method addManualFileListEvent
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
     * @method addManualFileListEntry
     */
    cContentTypeFilelist.prototype.addManualFileListEntry = function() {
        var $selectFileList = $(this.SELECTOR_FILELIST_FILENAME),
            $selectManualFiles = $(this.SELECTOR_FILELIST_MANUAL_FILES),
            filename = $selectFileList.val(),
            name;

        if ('' === filename) {
            return;
        }

        if (!$selectManualFiles.find('option[value="' + filename + '"]')[0]) {
            // It's not in the list, add it
            name = $selectFileList.find('option:selected').html();
            $selectManualFiles.prepend('<option value="' + filename + '" selected="selected">' + name + '</option>');
        }
    };

    /**
     * Function adds double click events to all current listed articles for manual FileList
     * in case of a double click this selected article is removed from list.
     * @method addClickEvent
     */
    cContentTypeFilelist.prototype.addClickEvent = function() {
        var self = this;

        this.addNaviActions();
        this.addExtensionActions();

        // check if manual filelist settings should be shown at the beginning
        var $chk = $(this.SELECTOR_CHK_FILELIST_MANUAL),
            $settings = $(this.SELECTOR_MANUAL_FILELIST_SETTING);
        if ($chk.is(':checked')) {
            $settings.show();
        } else {
            $settings.hide();
        }

        $chk.click(function() {
            $settings.slideToggle();
        });

        // check if meta data should be shown at the beginning
        if ($(this.SELECTOR_FILELIST_INCL_METADATA).is(':checked')) {
            $(this.SELECTOR_METADATALIST).show();
        } else {
            $(this.SELECTOR_METADATALIST).hide();
        }

        $(this.SELECTOR_FILELIST_INCL_METADATA).click(function() {
            $(self.SELECTOR_METADATALIST).slideToggle();
        });

        $(this.SELECTOR_FILELIST_MANUAL_FILES).dblclick(function() {
            $(self.SELECTOR_FILELIST_MANUAL_FILES).find('option').each(function() {
                if ($(this).is(':selected')) {
                    $(this).remove();
                }
            });
            $(self.SELECTOR_FILELIST_MANUAL_FILES).find('option').prop('selected', 'selected');
        });
    };

    /**
     * Adds possibility to select all file extensions at once and
     * disables file extension select if file extensions should be ignored.
     * @method addExtensionActions
     */
    cContentTypeFilelist.prototype.addExtensionActions = function() {
        var self = this;
        // let the user select all file extensions at once
        $(this.SELECTOR_FILELIST_ALL_EXTENSIONS).css('cursor', 'pointer');
        $(this.SELECTOR_FILELIST_ALL_EXTENSIONS).click(function() {
            // only react if the extensions should not be ignored
            if ($(self.SELECTOR_FILELIST_EXTENSIONS).is(':not(:disabled)')) {
                // check if all options are selected
                var allSelected = true;
                $(self.SELECTOR_FILELIST_EXTENSIONS).find('option').each(function() {
                    if (!$(this).is(':selected')) {
                        allSelected = false;
                    }
                });
                if (allSelected) {
                    // all options are selected, so unselect them
                    $(self.SELECTOR_FILELIST_ALL_EXTENSIONS).css('font-weight', 'normal');
                    $(self.SELECTOR_FILELIST_EXTENSIONS).find('option').prop('selected', false);
                } else {
                    // some options are not selected, so select them
                    $(self.SELECTOR_FILELIST_ALL_EXTENSIONS).css('font-weight', 'bold');
                    $(self.SELECTOR_FILELIST_EXTENSIONS).find('option').prop('selected', 'selected');
                }
            }
            return false;
        });

        // disable the file extension select if file extensions should be ignored
        $(this.SELECTOR_FILELIST_IGNORE_EXTENSIONS).click(function() {
            if ($(this).is(':checked')) {
                $(self.SELECTOR_FILELIST_EXTENSIONS).attr('disabled', 'disabled');
            } else {
                $(self.SELECTOR_FILELIST_EXTENSIONS).removeAttr('disabled');
            }
        });
    };

    /**
     * Adds possibility to navigate through the upload folder by:
     * - adding possibility to expand and close directories
     * - updating the file list each time a new directory is selected
     * @method addNaviActions
     */
    cContentTypeFilelist.prototype.addNaviActions = function() {
        var self = this,
            $dirListManual, delegateSelector;

        // Click handler for directories, the regular one and the manual one...
        // Loads any available sub directories and toggles the collapsed state
        // NB: The given callback is not used at the moment but is there for future purposes!
        var _onDirectoryClick = function($context, callback) {
            callback = callback || function() {};
            var dirname = $context.find('a[class="on"]').attr('title');
            if ($context.next('ul').length > 0) {
                $context.next('ul').toggle(function() {
                    if ($context.next('ul').is(':hidden')) {
                        $context.parent().addClass('collapsed');
                    } else {
                        $context.parent().removeClass('collapsed');
                    }
                });
                callback();
            } else {
                $.ajax({
                    type: 'POST',
                    url: self.pathBackend + 'ajaxmain.php',
                    data: 'ajax=dirlist&dir=' + dirname + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                    success: function(msg) {
                        if (msg.length > 0) {
                            $context.after(msg);
                        }
                        $context.parent('li').removeClass('collapsed');
                    }
                });
                callback();
            }
        };

        // Manual directory list
        $dirListManual = $(this.SELECTOR_DIRLIST_MANUAL),
        delegateSelector = this.SELECTOR_DIRLIST_MANUAL.replace(this.frameId + ' ', '');
        $dirListManual.removeClass('active');
        $(this.frameId).delegate(delegateSelector, 'click', function() {
            _onDirectoryClick($(this));

            $dirListManual.removeClass('active');
            $(this).addClass('active');

            var dirname = $(this).find('a[class="on"]').attr('title');
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

        // Directory list
        delegateSelector = this.SELECTOR_DIRLIST.replace(this.frameId + ' ', '');
        $(this.frameId).delegate(delegateSelector, 'click', function() {
            _onDirectoryClick($(this));

            $(this).toggleClass('active');
            return false;
        });
    };

    /**
     * Adds save event to the save button of content type edit form.
     * Collects the selected directories in "directories" tab,
     * and then it calls parents
     * @method addSaveEvent
     * @override
     */
    cContentTypeFilelist.prototype.addSaveEvent = function() {
        var self = this;

        $(this.SELECTOR_SAVE_SETTINGS).click(function() {
            // The chosen directory is no form field, so add it to the editform manually
            var value = [],
                item;
            // Loop through all selected (active) directories and collect their names
            $(self.SELECTOR_DIRLIST_ACTIVE).each(function() {
                item = $(this).find('a[class="on"]');
                if (item.attr('title').length) {
                    value.push(item.attr('title'));
                }
            });

            self.appendFormField('filelist_array_directories', value.join(','));
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
