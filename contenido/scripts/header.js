/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Header frame related JavaScript
 *
 * @module     header
 * @version    SVN Revision $Rev:$
 * @requires   jQuery
 * @package    CONTENIDO Header menu
 * @version    1.1.0
 * @author     Timo Hummel, Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @requires   jQuery JavaScript Framework
 */

(function(Con, $) {
    'use strict';

    var NAME = 'header';

    var MENU_ID = 'main_0';
    var SUBMENU_ID = 'sub_0';

    /**
     * Header class
     * @class Header
     * @static
     */
    var Header = {

        /**
         * @property _activeMain
         * @type {String}
         * @private
         */
        _activeMain: null,

        /**
         * @property _activeSub
         * @type {String}
         * @private
         * @TODO Seems not to be in use
         */
        _activeSub: null,

        /**
         * @property _activeLink
         * @type {HTMLElement}
         * @private
         * @TODO Seems not to be in use
         */
        _activeLink: null,

        /**
         * @property _activeSubLink
         * @type {HTMLElement}
         * @private
         * @TODO Seems not to be in use
         */
        _activeSubLink: null,

        /**
         * @method show
         * @param {String} id
         * @param {String|HTMLElement} slink
         */
        show: function(id, slink) {
            $("#" + SUBMENU_ID).css("display", "none");

            if (Header._activeMain) {
                Header.hide(Header._activeMain);
            }

            if (Header._activeLink) {
                Header._activeLink.className = "main";
            }

            $("#" + id).css("display", "block");
            $(this.SELECTOR_MENUES).css("color", "#000000");
            if (slink) {
                if (typeof slink == "object") {
                    $(slink).css("color", "#0060B1");
                } else {
                    $("#" + slink).css("color", "#0060B1");
                }
            }
            Header._activeMain = id;
        },

        /**
         * @method hide
         * @param {String} id
         */
        hide: function(id) {
            $("#" + id).css("display", "none");
            Header._activeMain = 0;
        },

        /**
         * Switches the backend language, by reloading top frame with new langugage.
         * @method changeContenidoLanguage
         * @param  {Number}  idlang
         */
        changeContenidoLanguage: function(idlang) {
            var frame;

            frame = Con.getFrame('left_top');
            if (frame) {
                frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {changelang: idlang});
            }

            frame = Con.getFrame('left_bottom');
            if (frame) {
                frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {changelang: idlang});
            }

            frame = Con.getFrame('right_top');
            if (frame) {
                // remove the action parameter, so that actions are not executed in the other language
                var href = Con.UtilUrl.replaceParams(frame.location.href, {action: null, changelang: idlang});
                frame.location.href = href;
            }

            frame = Con.getFrame('right_bottom');
            if (frame) {
                // remove the action parameter, so that actions are not executed in the other language
                var href = Con.UtilUrl.replaceParams(frame.location.href, {action: null, changelang: idlang, frame: 4});
                frame.location.href = href;
            }

            frame = Con.getFrame('header');
            if (frame) {
                frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {changelang: idlang});
            }
        },

        /**
         * Switches the backend client, by reloading top frame with new client.
         * @method changeContenidoClient
         * @param  {Number}  idclient
         */
        changeContenidoClient: function(idclient) {
            parent.window.document.location.href = Con.UtilUrl.replaceParams(parent.window.document.location.href, {changeclient: idclient});
            return;

            var frame;

            // TODO when the startup process has been reworked, it should be possible to reload the frames individually, so that the current page stays the same
            frame = Con.getFrame('left_top');
            if (frame) {
                frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {changeclient: idclient});
            }

            frame = Con.getFrame('left_bottom');
            if (frame) {
                frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {changeclient: idclient});
            }

            frame = Con.getFrame('right_top');
            if (frame) {
                // remove the action parameter, so that actions are not executed in the other language
                var href = Con.UtilUrl.replaceParams(frame.location.href, {action: null, changeclient: idclient});
                frame.location.href = href;
            }

            frame = Con.getFrame('right_bottom');
            if (frame) {
                // remove the action parameter, so that actions are not executed in the other language
                var href = Con.UtilUrl.replaceParams(frame.location.href, {action: null, changeclient: idclient});
                frame.location.href = href;
            }

            frame = Con.getFrame('header');
            if (frame) {
                frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {changeclient: idclient});
            }
        },

        /**
         * Resets the header menu.
         * @method resetHeaderMenu
         */
        resetHeaderMenu: function() {
            var menu = Con.Registry.get("headerMenu");
            menu.reset();

            $(this.SELECTOR_SUBMENUES).attr("class", "sub");

            var self = this;
            Header.show.apply(self, [menu.getActiveSubMenu(), menu.getActiveMenu()]);
        }
    };

    Con.Header = Header;


    // ########################################################################

    /**
     * Header timer class to control the mouseout mouseover delay
     *
     * @class  HeaderTimer
     * @static
     */
    var HeaderTimer = {
        /**
         * Mouseout timeout handler
         * @property out
         * @type  {Number}
         */
        out: null,
        /**
         * Mouseover timeout handler
         * @property over
         * @type  {Number}
         */
        over: null,
        /**
         * Clear a existing mouseout handler
         * @method resetOut
         */
        resetOut: function() {
            if (this.out) {
                clearTimeout(this.out);
            }
        },
        /**
         * Clear a existing mouseover handler
         * @method resetOver
         */
        resetOver: function() {
            if (this.over) {
                clearTimeout(this.over);
            }
        }
    };

    Con.HeaderTimer = HeaderTimer;


    // ########################################################################


    /**
     * Base header menu class. Clickmenu or Delaymenu should extend this (se below)!
     *
     * @class    HeaderMenu
     * @static
     */
    var HeaderMenu = {

        _currentActiveMenuId: null,
        _currentActiveSubmenusId: null,

        SELECTOR_MENUES: '#head_nav1 span a',
        SELECTOR_SUBMENUES: '#submenus a',

        /**
         * Menu initialization
         * @method initialize
         * @param  {Object}  options  Option object
         * @abstract
         */
        initialize: function(options) {
            throw("Abstract function: must be overwritten by child");
        },

        /**
         * Resets the stored menu ids (main menu and sub menu)
         * @method reset
         */
        reset: function() {
            this.setActiveMenu(MENU_ID);
            this.setActiveSubMenu(SUBMENU_ID);
        },

        /**
         * Getter for active main menu
         * @method getActiveMenu
         * @return  {String}
         */
        getActiveMenu: function() {
            return this._currentActiveMenuId;
        },

        /**
         * Setter for active main menu
         * @method setActiveMenu
         * @param  {String}  menuId
         */
        setActiveMenu: function(menuId) {
            this._currentActiveMenuId = menuId;
        },

        /**
         * Getter for active sub menu
         * @method getActiveSubMenu
         * @return  {String}
         */
        getActiveSubMenu: function() {
            return this._currentActiveSubmenusId;
        },

        /**
         * Setter for active sub menu
         * @method setActiveSubMenu
         * @param  {String}  subMenuId
         */
        setActiveSubMenu: function(subMenuId) {
            this._currentActiveSubmenusId = subMenuId;
        },

        /**
         * Returns the superior main menu id of passed sub menu id
         * @method getMenuIdBySubMenuId
         * @param    {String}  subMenuId
         * @return  {String}
         */
        getMenuIdBySubMenuId: function(subMenuId) {
            return subMenuId.replace("sub_", "main_");
        },

        /**
         * Returns the inferior sub menu id of passed main menu id
         * @method getSubMenuIdByMenuId
         * @param    {String}  menuId
         * @return  {String}
         */
        getSubMenuIdByMenuId: function(menuId) {
            return menuId.replace("main_", "sub_");
        },

        /**
         * Activates a menue.
         * @method activate
         * @param  {Object}  obj  Main menu item object
         * @abstract
         */
        activate: function(obj) {
            throw("Abstract function: must be overwritten by child");
        },

        /**
         * Deactivates a menu.
         * @method deactivate
         * @param  {Object}  obj  Main menu item object
         * @abstract
         */
        deactivate: function(obj) {
            throw("Abstract function: must be overwritten by child");
        },

        /**
         * Marks menu item as active menu when a anchor of one of its subitems is clicked.
         * @method markActive
         * @param  {Object}  obj  Anchor item object
         */
        markActive: function(obj) {
            // reset color for all links
            $(this.SELECTOR_SUBMENUES).attr("class", "sub");

            $(obj).attr("class", "activemenu");

            // remember name for clicked
            var curElement = $(obj).parent().attr("id");

            // If we are here this means a submenu item was clicked
            // Now find out the related menu item on level 1 and store it
            // We need to do this for restoring highlighting of the current active menu on mouseout of hover menu
            this.setActiveMenu(this.getMenuIdBySubMenuId(curElement));
            this.setActiveSubMenu(curElement);
        }
    };


    Con.HeaderMenu = HeaderMenu;


    // ########################################################################


    /**
     * Header click menu class. Switches the menu by click.
     *
     * @class    HeaderClickMenu
     * @extends  HeaderMenu
     * @static
     */
    var HeaderClickMenu = $.extend(true, HeaderMenu, {

        /**
         * @method initialize
         * @param  {Object}  [options={}]
         */
        initialize: function(options) {
            if (typeof options === "undefined") {
                options = {};
            }
            if (typeof options.menuId === "undefined") {
                options.menuId = MENU_ID;
            }
            if (typeof options.subMenuId === "undefined") {
                options.subMenuId = SUBMENU_ID;
            }

            this.setActiveMenu(options.menuId);
            this.setActiveSubMenu(options.subMenuId);

            $(this.SELECTOR_MENUES).click(function() {
                HeaderClickMenu.activate(this);
            });
            $(this.SELECTOR_SUBMENUES).click(function() {
                HeaderClickMenu.markActive(this);
            });
        },

        /**
         * Activates a menue.
         * @method activate
         * @param  {Object}  obj  Main menu item object
         */
        activate: function(obj) {
            this._currentActiveMenuId = $(obj).attr("id");
            this._currentActiveSubmenusId = this.getSubMenuIdByMenuId(this._currentActiveMenuId);
            Header.show(this._currentActiveSubmenusId, obj);
        },

        /**
         * Empty function.
         * @method deactivate
         * @param  {Object}  obj  Main menu item object
         */
        deactivate: function(obj) {
            // donut
        }

    });


    Con.HeaderClickMenu = HeaderClickMenu;


    // ########################################################################


    /**
     * Header delay menu class. Switches the menu by mouseover/-out.
     *
     * @class    HeaderDelayMenu
     * @extends  HeaderMenu
     * @static
     */
    var HeaderDelayMenu = $.extend(true, HeaderMenu, {

        DEFAULT_MOUSEOVER_DELAY: 300,
        DEFAULT_MOUSEOUT_DELAY: 1000,

        /**
         * @method initialize
         * @param  {Object}  [options={}]
         */
        initialize: function(options) {
            this._mouseOverDelay = null;
            this._mouseOutDelay = null;

            if (typeof options === "undefined") {
                options = {};
            }
            if (typeof options.menuId === "undefined") {
                options.menuId = MENU_ID;
            }
            if (typeof options.subMenuId === "undefined") {
                options.subMenuId = SUBMENU_ID;
            }
            if (typeof options.mouseOverDelay === "undefined" || isNaN(options.mouseOverDelay)) {
                options.mouseOverDelay = this.DEFAULT_MOUSEOVER_DELAY;
            }
            if (typeof options.mouseOutDelay === "undefined" || isNaN(options.mouseOutDelay)) {
                options.mouseOutDelay = this.DEFAULT_MOUSEOUT_DELAY;
            }

            this._mouseOverDelay = options.mouseOverDelay;
            this._mouseOutDelay = options.mouseOutDelay;

            this.setActiveMenu(options.menuId);
            this.setActiveSubMenu(options.subMenuId);

            $(this.SELECTOR_MENUES).mouseover(function() {
                HeaderDelayMenu.activate(this);
            }).mouseout(function() {
                HeaderDelayMenu.deactivate(this);
            });

            $(this.SELECTOR_SUBMENUES).click(function() {
                HeaderDelayMenu.markActive(this);
            }).mouseover(function() {
                HeaderTimer.resetOut();
            }).mouseout(function() {
                HeaderDelayMenu.deactivate(this);
            });
        },

        /**
         * Activates a menue.
         * @method activate
         * @param  {Object}  obj  Main menu item object
         */
        activate: function(obj) {
            HeaderTimer.resetOut();
            var ident = this.getSubMenuIdByMenuId($(obj).attr("id"));
            HeaderTimer.resetOver();
            var that = this;
            HeaderTimer.over = setTimeout(function() {
                Header.show(ident, obj);
            }, that._mouseOverDelay);
        },

        /**
         * Deactivates a menu by hiding its submenu using a defined delay time.
         * @method deactivate
         * @param  {Object}  obj  Main menu item object
         */
        deactivate: function(obj) {
            HeaderTimer.resetOut();
            var that = this;
            HeaderTimer.out = setTimeout(function() {
                Header.show.apply(obj, [that.getActiveSubMenu(), that.getActiveMenu()]);
            }, that._mouseOutDelay);
        }
    });


    Con.HeaderDelayMenu = HeaderDelayMenu;


    // ########################################################################


    // Bootstrap header on document load
    // @TODO  Move this to a separate file...
    $(function() {
        $('#changeclient').click(function() {
            $('#chosenclient').hide();
            $('#cClientSelect').show();
            $(this).hide();
        });
    });


    // ########################################################################

    // @deprecated [2013-11-13] Assign to windows scope (downwards compatibility)
    window.conMultiLink = Con.multiLink;
    window.active_main = Con.Header._activeMain;
    window.active_sub = Con.Header._activeSub;
    window.active_link = Con.Header._activeLink;
    window.active_sub_link = Con.Header._activeSubLink;
    window.show = Con.Header.show;
    window.hide = Con.Header.hide;
    window.changeContenidoLanguage = Con.Header.changeContenidoLanguage;
    window.changeContenidoClient = Con.Header.changeContenidoClient;
    window.resetHeaderMenu = Con.Header.resetHeaderMenu;
    window.HeaderTimer = Con.HeaderTimer;
    window.HeaderMenu = Con.HeaderMenu;
    window.HeaderClickMenu = Con.HeaderClickMenu;
    window.HeaderDelayMenu = Con.HeaderDelayMenu;

})(Con, Con.$);



/**
 * @deprecated [2013-10-17] Use Con.UtilUrl.replaceParams instead
 */
function replaceQueryString(url, param, value) {
    Con.log("replaceQueryString: Deprecated, use Con.UtilUrl.replaceParams instead", "header.js", "warn");
    var o = {};
    o[param] = value;
    return Con.UtilUrl.replaceParams(url, o);
}
