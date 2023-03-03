/* global Con: true, jQuery: true */

/**
 * Plugin Advanced Mod Rewrite JavaScript functions.
 *
 * @module     plugin
 * @submodule  mod-rewrite
 * @requires   jQuery, Con
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

(function(Con, $) {

    var NAME = 'plugin-mod-rewrite';

    /**
     * @class ModRewrite
     * @constructor
     * @extends Con.Plugin
     * @param {String}  page
     * @param {Object}  options  Options as follows:
     * <pre>
     * options.lng  (Object)  Translation strings
     * </pre>
     */
    var ModRewrite = function(page, options) {
        /**
         * Pagename
         * @property _page
         * @type {String}
         * @private
         */
        this._page = page;

        /**
         * Translation strings
         * @property _lng
         * @type {Object}
         * @private
         */
        this._lng = $.extend({
            more_information: "More information"
        }, options.lng);

        this._initializePage();
    };

    ModRewrite.prototype = {

        /**
         * Toggles an element
         * @method toggle
         * @param {String}  id
         */
        toggle: function(id) {
            // do some animation ;-)
            $('#' + id).slideToggle("slow");
        },

        /**
         * Initializes the current page
         * @method initializePage
         */
        _initializePage: function() {
            var method = '_initializePage' + this._page.charAt(0).toUpperCase() + this._page.slice(1);
            if ('function' === $.type(this[method])) {
                this[method].call(this);
            } else {
                Con.log("Couldn't initialize page " + this.page, NAME, "error");
            }
        },

        /**
         * Initializes the settings page by registering some handler on page load
         * @method _initializePageSettings
         * @private
         */
        _initializePageSettings: function(options) {
            $("#mr_use_language").change(function() {
                if (true == $(this).attr("checked")) {
                    $("#mr_use_language_name").removeAttr("disabled");
                } else {
                    $("#mr_use_language_name").attr("disabled", "disabled");
                }
            });

            $("#mr_use_client").change(function() {
                if (true == $(this).attr("checked")) {
                    $("#mr_use_client_name").removeAttr("disabled");
                } else {
                    $("#mr_use_client_name").attr("disabled", "disabled");
                }
            });

            $("#mr_add_startart_name_to_url").change(function() {
                if (true == $(this).attr("checked")) {
                    $("#mr_default_startart_name").removeAttr("disabled")
                                                  .removeClass("disabled");
                } else {
                    $("#mr_default_startart_name").attr("disabled", "disabled")
                                                  .addClass("disabled");
                }
            });

            this._initializeTooltips();
        },

        /**
         * Initializes the expert page by registering some handler on page load
         * @method _initializePageExpert
         * @private
         */
        _initializePageExpert: function() {
            $('.mrPlugin a.mr_action_link').click(function() {
                var action = $(this).data('action'),
                    params = $(this).data('params'),
                    $form = $('#mr_expert');
                $form.find('input[name="mr_action"]').val(action);
                $form.attr('action', $form.attr('action') + '?' + params).submit();
            });

            this._initializeTooltips();
        },

        /**
         * Initializes the test page by registering some handler on page load
         * @method _initializePageTest
         * @private
         */
        _initializePageTest: function() {
            this._initializeTooltips();
        },

        /**
         * Adds title to all tooltips
         * @method _initializeTooltips
         * @private
         */
        _initializeTooltips: function() {
            var that = this;
            $(".mrPlugin a.i-link").each(function() {
                $(this).attr("href", "javascript:void(0)").attr("title", that._lng.more_information);
            });
        }

    };

    // Assign to Con.Plugin namespace
    Con.Plugin.ModRewrite = ModRewrite;

})(Con, Con.$);
