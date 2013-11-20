/* global Con: true, jQuery: true */

/**
 * This file contains the ContentTypeTeaser JS class.
 *
 * @module     content-type
 * @submodule  content-type-cms-teaser
 * @package    Core
 * @subpackage Content Type
 * @version    SVN Revision $Rev$
 * @author     Timo Trautmann, Simon Sprankel, Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {
//    'use strict';

    var NAME = 'content-type-cms-teaser';

    /**
     * Creates a new cContentTypeTeaser with the given properties.
     * You most probably want to call initialise() after creating a new object of this class.
     * @class cContentTypeTeaser
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
    function cContentTypeTeaser(frameId, imageId, pathBackend, pathFrontend, idArtLang, id, fields, prefix, session, settings) {
        // call the constructor of the parent class with the same arguments
        Con.cContentTypeAbstractTabbed.apply(this, arguments);
    }

    // inherit from cContentTypeAbstractTabbed
    cContentTypeTeaser.prototype = new Con.cContentTypeAbstractTabbed();
    // correct the constructor function (it points to the cContentTypeAbstractTabbed constructor)
    cContentTypeTeaser.prototype.constructor = cContentTypeTeaser;

    /**
     * Initialises the content type by adding event handlers etc.
     * @method initialise
     * @override
     */
    cContentTypeTeaser.prototype.initialise = function() {
        // call the function of the parent so that it is initialised correctly
        Con.cContentTypeAbstractTabbed.prototype.initialise.call(this);
        // call custom functions that attach custom event handlers etc.
        this.getArticleList();
        this.addManualTeaser();
        this.removeManualTeaser();
    };

    /**
     * Loads external styles and scripts so that they are only loaded when they are
     * really needed.
     * @method loadExternalFiles
     * @override
     */
    cContentTypeTeaser.prototype.loadExternalFiles = function() {
        // call the function of the parent so that all general files are included
        Con.cContentTypeAbstractTabbed.prototype.loadExternalFiles.call(this);

        // Dependencies to load
        var files = [];
        files.push(this.pathBackend + 'styles/content_types/cms_teaser.css');

        Con.Loader.get(files);
    };

    /**
     * Function gets new list of articles from CONTENIDO via ajax.
     * Is used in manual teaser when base category for article select
     * is changed.
     * @method getArticleList
     */
    cContentTypeTeaser.prototype.getArticleList = function() {
        var self = this;
        $(self.frameId + ' #teaser_cat_' + self.id).change(function() {
            // get new article select and replace it with default value
            $.ajax({
                type: 'POST',
                url: self.pathBackend + 'ajaxmain.php',
                data: 'ajax=artsel&name=teaser_art_' + self.id + '&contenido=' + self.session + '&idcat=' + $(this).val(),
                success: function(msg) {
                    $(self.frameId + ' #teaser_art_' + self.id).replaceWith(msg);
                }
            });
        });
    };

    /**
     * Function adds event to add new article to multiple select box for articles.
     * Function also checks if article is already in that list.
     * @method addManualTeaser
     */
    cContentTypeTeaser.prototype.addManualTeaser = function() {
        var self = this;
        $(self.frameId + ' #add_art_' + self.id).css('cursor', 'pointer');
        $(self.frameId + ' #add_art_' + self.id).click(function() {
            // call internal add function
            self.addManualTeaserEntry();
        });
    };

    /**
     * Function adds new article to multiple select box for articles
     * Function also checks if article is already in that list
     * @method addManualTeaserEntry
     */
    cContentTypeTeaser.prototype.addManualTeaserEntry = function() {
        var idArt = $(this.frameId + ' #teaser_art_' + this.id).val();
        var name = '';
        var exists = false;

        // if an article was selected
        if (idArt > 0) {
            // check if article already exists in view list
            $(this.frameId + ' #teaser_manual_art_' + this.id + ' option').each(function() {
                if (idArt == $(this).val()) {
                    exists = true;
                }
            });

            // get name of selected article
            $(this.frameId + ' #teaser_art_' + this.id + ' option').each(function() {
                if (idArt == $(this).val()) {
                    name = $(this).html();
                }
            });

            // if it is not in list, add article to list
            if (!exists) {
                $(this.frameId + ' #teaser_manual_art_' + this.id).append('<option value="' + idArt + '" selected="selected">' + name + '</option>');
            }
        }
    };

    /**
     * Function adds double click events to all current listed articles for manual teaser
     * in case of a double click this selected article is removed from list.
     * @method removeManualTeaser
     */
    cContentTypeTeaser.prototype.removeManualTeaser = function() {
        var self = this;
        $(self.frameId + ' #teaser_manual_art_' + self.id).dblclick(function() {
            $(self.frameId + ' #teaser_manual_art_' + self.id + ' option:selected').each(function() {
                $(this).remove();
            });
        });
    };

    Con.cContentTypeTeaser = cContentTypeTeaser;

    // @deprecated [2013-11-10] Assign to windows scope (downwards compatibility)
    window.cContentTypeTeaser = cContentTypeTeaser;


    $(function() {
        $("#del_art_3").click(function() {
            $("#teaser_manual_art_3 option:selected").remove();
        });
    });

})(Con, Con.$);
