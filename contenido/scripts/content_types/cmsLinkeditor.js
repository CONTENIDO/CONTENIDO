/* global Con: true, jQuery: true */

/**
 * This file contains the cContentTypeLinkeditor JS class.
 *
 * @module     content-type
 * @submodule  content-type-cms-linkeditor
 * @package    Core
 * @subpackage Content Type
 * @author     Fulai Zhang
 * @author     Simon Sprankel
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

(function(Con, $) {

    var NAME = 'content-type-cms-linkeditor';

    /**
     * Creates a new cContentTypeLinkeditor with the given properties.
     * You most probably want to call initialise() after creating a new object of this class.
     * @class cContentTypeLinkeditor
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
    function cContentTypeLinkeditor(frameId, imageId, pathBackend, pathFrontend, idArtLang, id, fields, prefix, session, settings) {
        // call the constructor of the parent class with the same arguments
        Con.cContentTypeAbstractTabbed.apply(this, arguments);

        /**
         * The path which has been selected by the user.
         * @property selectedPath
         * @type {String}
         */
        this.selectedPath = '';

        /**
         * Is first initialisation need a refresh for internal and dirs tabs
         * @property refreshed
         * @type {boolean}
         */
        this.refreshed = false;
    }

    // inherit from cContentTypeAbstractTabbed
    cContentTypeLinkeditor.prototype = new Con.cContentTypeAbstractTabbed();
    // correct the constructor function (it points to the cContentTypeAbstractTabbed constructor)
    cContentTypeLinkeditor.prototype.constructor = cContentTypeLinkeditor;

    /**
     * Initialises the content type by adding event handlers etc.
     * @method initialise
     * @override
     */
    cContentTypeLinkeditor.prototype.initialise = function() {
        // call the function of the parent so that it is initialised correctly
        Con.cContentTypeAbstractTabbed.prototype.initialise.call(this);
        // call custom functions that attach custom event handlers etc.
        this.addNaviActions();
        this.createMKDir();
    };

    /**
     * Loads external styles and scripts so that they are only loaded when they are
     * really needed.
     * @method loadExternalFiles
     * @override
     */
    cContentTypeLinkeditor.prototype.loadExternalFiles = function() {
        // call the function of the parent so that all general files are included
        Con.cContentTypeAbstractTabbed.prototype.loadExternalFiles.call(this);

        Con.Loader.get(
            [
                this.pathBackend + 'styles/content_types/cms_linkeditor.css',
                this.pathBackend + 'scripts/jquery/ajaxupload.js'
            ],
            cContentTypeLinkeditor.prototype.linkEditorFileUpload, this);
    };

    /**
     * Adds tabbing events to menubar of content type edit form. Lets the user
     * switch between the different tabs.
     * @method addTabbingEvents
     * @override
     */
    cContentTypeLinkeditor.prototype.addTabbingEvents = function() {
        var self = this;
        // call the function of the parent so that the standard tab functionality works
        Con.cContentTypeAbstractTabbed.prototype.addTabbingEvents.call(this);

        // show the tab of the currently active link type and hide the others
        this.setCurrentTab();

        // show the basic settings "tab" any time
        $(this.frameId + ' .tabs #basic-settings').show();
        $(this.frameId + ' .menu li').bind('click', function() {
            $(self.frameId + ' .tabs #basic-settings').show();
        });
    };

    /**
     * Adds event which fades in the edit form when editbutton is clicked.
     * @method addFrameShowEvent
     * @override
     */
    cContentTypeLinkeditor.prototype.addFrameShowEvent = function () {
        var self = this;
        $(this.imageId).css('cursor', 'pointer');
        $(this.imageId).click(function () {
            var top = $(document).scrollTop() + ($(window).height() / 2);
            self.$frame.fadeIn('normal');
            self.$frame.css({
                position: 'absolute',
                top: top,
                left: '50%'
            });

            // refresh tabs
            self.refreshTabs();
        });
    };

    /**
     * Show current selected tab by first loading
     * @method setCurrentTab
     */
    cContentTypeLinkeditor.prototype.setCurrentTab = function () {
        var linkeditorType = this.settings.linkeditor_type ? this.settings.linkeditor_type : 'external';

        $(this.frameId + ' .tabs > div').hide();
        $(this.frameId + ' .tabs #' + linkeditorType).show();
        // set the active class for the corresponding menu entry and remove it from the others
        $(this.frameId + ' .menu li').removeClass('active');
        $(this.frameId + ' .menu li.' + linkeditorType).addClass('active');
    }

    /**
     * @method loadTab
     */
    cContentTypeLinkeditor.prototype.refreshTabs = function () {
        if (this.refreshed) {
            return;
        }

        // load internal categories and articles
        this.getCategoriesList($(this.frameId + ' #internal #directoryList_' + this.id + ' em a').parent().parent(), '0', '0');
        this.getArticlesList();

        // load external dirs and files
        this.getDirsList($(this.frameId + ' #file #directoryList_' + this.id + ' em a').parent().parent(), '');
        this.getImagesList();

        this.refreshed = true;
    }

    /**
     * Adds possibility to navigate through the upload folder by:
     * - adding possibility to expand and close directories
     * - updating the file list each time a new directory is selected
     * @method addNaviActions
     */
    cContentTypeLinkeditor.prototype.addNaviActions = function() {
        var self = this;

        $(this.frameId + ' #internal #directoryList_' + this.id + ' a[class="on"]').parent('div').unbind('click')
            .bind('click', function() {
                $(self.frameId + ' div').each(function() {
                $(this).removeClass('active');
            });
            $(this).addClass('active');
            var idcat = $(this).children('a[class="on"]').attr('title');

                self.getArticlesList(idcat);
            return false;
        });

        // add possibility to expand and close directories for the article view
        $(this.frameId + ' #internal #directoryList_' + this.id + ' em a').unbind('click')
            .bind('click', function() {
            var divContainer = $(this).parent().parent();
            if (!_bindCollapsing(divContainer)) {
                var parentidcat = $(this).parent('em').parent().parent().parent().find('div a[class="on"]').attr('title');
                var level = $(this).parents('ul').length - 1;
                    self.getCategoriesList(divContainer, level, parentidcat);
                }

            return false;
        });

        $(this.frameId + ' #file #directoryList_' + this.id + ' a[class="on"]').parent('div').unbind('click')
            .bind('click', function() {
            // update the "active" class
            $(self.frameId + ' div').each(function() {
                $(this).removeClass('active');
            });
            $(this).addClass('active');
            var dirname = $(this).children('a[class="on"]').attr('title');
            if (dirname === 'upload') {
                dirname = '/';
            }

                self.getImagesList(dirname);
                return false;
            });

        // add possibility to expand and close directories for the file view
        $(this.frameId + ' #file #directoryList_' + this.id + ' em a')
            .unbind('click')
            .bind('click', function () {
                var divContainer = $(this).parent().parent();

                if (!_bindCollapsing(divContainer)) {
                    var dirname = $(this).parent('em').parent().find('a[class="on"]').attr('title');
                    self.getDirsList(divContainer, dirname);
                }

                return false;
            });
    };

    /**
     * Get current files list
     *
     * @param {string} [idcat]
     */
    cContentTypeLinkeditor.prototype.getArticlesList = function (idcat) {
        var self = this;

        var params = [
            'ajax=linkeditorarticleslist',
            'id=' + this.id,
            'idartlang=' + this.idArtLang,
            'contenido=' + this.session
        ];

        if (typeof idcat !== 'undefined') {
            params.push('idcat=' + idcat);
        }

        // update the file list each time a new directory is selected
        $.ajax({
            type: 'POST',
            url: this.pathBackend + 'ajaxmain.php',
            data: params.join('&'),
            success: function(msg) {
                if (Con.checkAjaxResponse(msg) === false) {
                    return false;
                }

                $(self.frameId + ' #internal #directoryFile_' + self.id).html(msg);
            }
        });
    }

    /**
     * Get current articles list for selected category
     *
     * @param {HTMLElement} el
     * @param {string} level
     * @param {string} parentidcat
     */
    cContentTypeLinkeditor.prototype.getCategoriesList = function (el, level, parentidcat) {
        var self = this;
        var params = [
            'ajax=linkeditordirlist',
            'level=' + level,
            'parentidcat=' + parentidcat,
            'id=' + this.id,
            'idartlang=' + this.idArtLang,
            'contenido=' + this.session
        ];

        $.ajax({
            type: 'POST',
            url: this.pathBackend + 'ajaxmain.php',
            data: params.join('&'),
            success: function(msg) {
                if (Con.checkAjaxResponse(msg) === false) {
                    return false;
                }

                el.after(msg);
                el.parent('li').removeClass('collapsed');
                self.addNaviActions();
            }
        });
    }

    /**
     * Get current images list
     *
     * @param {string} [dirname]
     */
    cContentTypeLinkeditor.prototype.getImagesList = function (dirname) {
        var self = this;
        var params = [
            'ajax=linkeditorimagelist',
            'id=' + this.id,
            'idartlang=' + this.idArtLang,
            'contenido=' + this.session
        ];
        if (typeof dirname !== 'undefined') {
            params.push('dir=' + dirname);
        }

        // update the file list each time a new directory is selected
        $.ajax({
            type: 'POST',
            url: this.pathBackend + 'ajaxmain.php',
            data: params.join('&'),
            success: function (msg) {
                if (Con.checkAjaxResponse(msg) === false) {
                    return false;
                }

                $(self.frameId + ' #file #directoryFile_' + self.id).html(msg);
            }
        });

        if (typeof dirname !== 'undefined') {
            this.showFolderPath();
        }
    }

    /**
     * Get current dirs list
     *
     * @param {HTMLElement} el
     * @param {string} dirname
     */
    cContentTypeLinkeditor.prototype.getDirsList = function (el, dirname) {
        var self = this;
        var params = [
            'ajax=dirlist',
            'dir=' + dirname,
            'id=' + this.id,
            'idartlang=' + this.idArtLang,
            'contenido=' + this.session
        ];

        $.ajax({
            type: 'POST',
            url: this.pathBackend + 'ajaxmain.php',
            data: params.join('&'),
            success: function (msg) {
                el.after(msg);
                el.parent('li').removeClass('collapsed');

                self.addNaviActions();
            }
        });
    }

    /**
     * Updates the divs in which the selected folder is displayed
     * every time a new folder is selected.
     * @method showFolderPath
     */
    cContentTypeLinkeditor.prototype.showFolderPath = function() {
        var self = this;
        // if there are no directories, set the active class for the root upload folder
        var titles = new Array();
        $(self.frameId + ' div[class="active"] a[class="on"]').each(function() {
            titles.push($(this).attr('title'));
        });
        if (titles.length < 1) {
            $(self.frameId + ' li.root>div').addClass('active');
        }

        // get the selected directory and save it
        var selectedPath = $(self.frameId + ' div[class="active"] a[class="on"]').attr('title');
        self.selectedPath = selectedPath;
        if (selectedPath !== '' && selectedPath !== 'upload') {
            selectedPath += '/';
        } else {
            selectedPath = '';
        }

        $(self.frameId + ' #caption1').text(selectedPath);
        $(self.frameId + ' #caption2').text(selectedPath);
        $(self.frameId + ' form[name="newdir"] input[name="path"]').val(selectedPath);
        $(self.frameId + ' form[name="properties"] input[name="path"]').val(selectedPath);

        self.linkEditorFileUpload();
    };

    /**
     * Uploads an image.
     * @method linkEditorFileUpload
     */
    cContentTypeLinkeditor.prototype.linkEditorFileUpload = function() {

        var self = this;
        var dirname = '';
        if (self.selectedPath !== '' && self.selectedPath !== 'upload') {
            dirname = self.selectedPath + '/';
        }

        $(self.frameId + ' input.jqueryAjaxUpload').unbind();
        $(self.frameId + ' input.jqueryAjaxUpload').fileupload({
            url: self.pathBackend + 'ajaxmain.php?ajax=upl_upload&id=' + self.id + '&idartlang=' + self.idArtLang + '&path=' + dirname + '&contenido=' + self.session,
            dataType: 'json',
            autoUpload: true,
            forceIframeTransport: true,
            multipart: true,
            start: function() {
                $(self.frameId + ' img.loading').show();
                $(self.frameId + ' input.jqueryAjaxUpload').css('visibility', 'hidden');
            },
            always: function() {
                if (dirname === 'upload' || dirname === '') {
                    dirname = '/';
                }

                $.ajax({
                    type: 'POST',
                    url: self.pathBackend + 'ajaxmain.php',
                    data: 'ajax=linkeditorimagelist&dir=' + self.selectedPath + '&id=' + self.id + '&idartlang=' + self.idArtLang + '&contenido=' + self.session,
                    success: function(msg) {
						if (Con.checkAjaxResponse(msg) === false)  {
							return false;
						}

                        $(self.frameId + ' img.loading').hide();
                        $(self.frameId + ' input.jqueryAjaxUpload').css('visibility', 'visible');
                        $(self.frameId + ' #file #directoryFile_' + self.id).html(msg);
                    }
                });
            }
        });
    };

    /**
     * Creates a new directory and updates the directory list accordingly.
     * @method createMKDir
     */
    cContentTypeLinkeditor.prototype.createMKDir = function() {
        var self = this;
        $(self.frameId + ' #file form[name="newdir"] input[type="image"]').unbind('click');
        $(self.frameId + ' #file form[name="newdir"] input[type="image"]').click(function() {
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
                success: function(msg) { //make create folder
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

                                if ($('div.cms_linkeditor .con_str_tree div.active').length === 0) {
                                    // to make sure that some element is selected. Otherwise the list wouldn't get updated
                                    $('div.cms_linkeditor div.file .con_str_tree .last div').first().click();
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
                                    $('div.cms_linkeditor .con_str_tree li div>a').each(function() {
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
     * Adds save event to the save button of content type edit form.
     * @method addSaveEvent
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
        Con.cContentTypeAbstractTabbed.prototype.addSaveEvent.call(self);
    };

    function _bindCollapsing(divContainer) {
        if (divContainer.next('ul').length > 0) {
            divContainer.next('ul').toggle(function () {
                if (divContainer.next('ul').is(':hidden')) {
                    divContainer.parent().addClass('collapsed');
                } else {
                    divContainer.parent().removeClass('collapsed');
                }
            });

            return true;
        }
        return false;
    }

    Con.cContentTypeLinkeditor = cContentTypeLinkeditor;

})(Con, Con.$);
