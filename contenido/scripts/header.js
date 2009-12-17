/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Header frame related JavaScript
 *
 *
 * @package    Contenido Header menu
 * @version    1.1.0
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @requires   jQuery JavaScript Framework
 *
 * {@internal
 *   created  2004-03-22
 *   modified 2009-12-17  Murat Purc, Redesign of header menu handling, takeover menu code from template, added ContenidoRegistry, HeaderMenu, HeaderClickMenu, HeaderDelayMenu and Firebug Emulation
 *
 *   $Id$:
 * }}
 *
 */

var active_main;
var active_sub;
var active_link;
var active_sub_link;

function show(id, slink)
{
    $("#sub_0").css("display", "none");

    if (active_main) {
        hide(active_main);
    }

    if (active_link) {
        active_link.className = "main";
    }

    $("#"+id).css("display", "block");
    $("#head_nav1 span a").css("color", "#000000");
    if (slink) {
        if (typeof slink == "object") {
            $(slink).css("color", "#0060B1");
        } else {
            $("#"+slink).css("color", "#0060B1");
        }
    }
    active_main = id;
}

function hide(id)
{
    $("#"+id).css("display", "none");
    active_main = 0;
}


/**
 * Switches the backend language, by reloading top frame with new langugage.
 *
 * Uses global variable "sid"!
 *
 * @param    {Integer}  idlang
 */
function changeContenidoLanguage(idlang)
{
    var url = "index.php?contenido="+sid+"&changelang="+idlang;
    parent.frames.top.location.href = url;
}


/**
 * Resets the header menu.
 */
function resetHeaderMenu()
{
    var menu = ContenidoRegistry.get("headerMenu");
    menu.reset();

    $("#submenus a").attr("class", "sub");

    var oThis = this;
    show.apply(oThis, [menu.getActiveSubMenu(), menu.getActiveMenu()]);
}


// #################################################################################################


/**
 * Registry class
 *
 * @class  ContenidoRegistry
 * @static
 */
ContenidoRegistry = {
    _instances: [],

    set: function(key, value) {
        this._instances[key] = value;
    },

    get: function(key) {
        if (this._instances[key] == "undefined") {
            throw("No entry is registered for key '"+key+"'");
        }
        return this._instances[key];
    },

    isRegistered: function(key) {
        return (this._instances[key] == "undefined");
    },

    remove: function(key) {
        this._instances = this._instances.splice(this._instances.indexOf(key), 1);
    }
}


// #################################################################################################


/**
 * Header timer class to control the mouseout mouseover delay
 *
 * @class  HeaderTimer
 * @static
 */
var HeaderTimer = {

    /**
     * Mouseout timeout handler
     *
     * @type  {Integer}
     */
    out: null,

    /**
     * Mouseover timeout handler
     *
     * @type  {Integer}
     */
    over: null,

    /**
     * Clear a existing mouseout handler
     */
    resetOut: function() {
        if (this.out) {
            clearTimeout(this.out);
        }
    },

    /**
     * Clear a existing mouseover handler
     */
    resetOver: function() {
        if (this.over) {
            clearTimeout(this.over);
        }
    }
};


// #################################################################################################


/**
 * Base header menu class. Clickmenu or Delaymenu should extend this (se below)!
 *
 * @class    HeaderMenu
 * @static
 */
var HeaderMenu = {

    DEFAULT_MENU_ID:    "main_0",
    DEFAULT_SUBMENU_ID: "sub_0",
    _currentActiveMenuId:     null,
    _currentActiveSubmenusId: null,

    /**
     * Menu initialization
     *
     * @param  {Object}  options  Option object
     * @abstract
     */
    initialize: function(options) {
        throw("Abstract function: must be overwritten by child");
    },

    /**
     * Resets the stored menu ids (main menu and sub menu)
     */
    reset: function() {
        this.setActiveMenu(this.DEFAULT_MENU_ID);
        this.setActiveSubMenu(this.DEFAULT_SUBMENU_ID);
    },

    /**
     * Getter for active main menu
     *
     * @returns  {String}
     */
    getActiveMenu: function() {
        return this._currentActiveMenuId;
    },

    /**
     * Setter for active main menu
     *
     * @param  {String}  menuId
     */
    setActiveMenu: function(menuId) {
        this._currentActiveMenuId = menuId;
    },

    /**
     * Getter for active sub menu
     *
     * @returns  {String}
     */
    getActiveSubMenu: function() {
        return this._currentActiveSubmenusId;
    },

    /**
     * Setter for active sub menu
     *
     * @param  {String}  subMenuId
     */
    setActiveSubMenu: function(subMenuId) {
        this._currentActiveSubmenusId = subMenuId;
    },

    /**
     * Returns the superior main menu id of passed sub menu id
     *
     * @param    {String}  subMenuId
     * @returns  {String}
     */
    getMenuIdBySubMenuId: function(subMenuId) {
        return subMenuId.replace("sub_", "main_");
    },

    /**
     * Returns the inferior sub menu id of passed main menu id
     *
     * @param    {String}  menuId
     * @returns  {String}
     */
    getSubMenuIdByMenuId: function(menuId) {
        return menuId.replace("main_", "sub_");
    },

    /**
     * Activates a menue.
     *
     * @param  {Object}  obj  Main menu item object
     * @abstract
     */
    activate: function(obj) {
       throw("Abstract function: must be overwritten by child");
     },

    /**
     * Deactivates a menu.
     *
     * @param  {Object}  obj  Main menu item object
     * @abstract
     */
    deactivate: function(obj) {
       throw("Abstract function: must be overwritten by child");
     },

    /**
     * Marks menu item as active menu when a anchor of one of its subitems is clicked.
     *
     * @param  {Object}  obj  Anchor item object
     */
    markActive: function(obj) {
        // reset color for all links
        $("#submenus a").attr("class", "sub");

        $(obj).attr("class", "activemenu");

        // remember name for clicked
        var curElement = $(obj).parent().attr("id");

        // If we are here this means a submenu item was clicked
        // Now find out the related menu item on level 1 and store it
        // We need to do this for restoring highlighting of the current active menu on mouseout of hover menu
        this.setActiveMenu(this.getMenuIdBySubMenuId(curElement));
        this.setActiveSubMenu(curElement);
    }
}


// #################################################################################################


/**
 * Header click menu class. Switches the menu by click.
 *
 * @class    HeaderClickMenu
 * @extends  HeaderMenu
 * @static
 */
var HeaderClickMenu = jQuery.extend(true, {}, HeaderMenu);

HeaderClickMenu.initialize = function(options) {

    if (typeof options == "undefined") {
        options = {};
    }
    if (typeof options.menuId == "undefined") {
        options.menuId = this.DEFAULT_MENU_ID;
    }
    if (typeof options.subMenuId == "undefined") {
        options.subMenuId = this.DEFAULT_SUBMENU_ID;
    }

    this.setActiveMenu(options.menuId);
    this.setActiveSubMenu(options.subMenuId);

    $("#head_nav1 a").click(function (){
        HeaderClickMenu.activate(this);
    });
    $("#submenus a").click(function (){
        HeaderClickMenu.markActive(this);
    });
};

/**
 * Activates a menue.
 *
 * @param  {Object}  obj  Main menu item object
 */
HeaderClickMenu.activate = function(obj) {
    this._currentActiveMenuId     = $(obj).attr("id");
    this._currentActiveSubmenusId = this.getSubMenuIdByMenuId(this._currentActiveMenuId);
    show(this._currentActiveSubmenusId, obj);
};

/**
 * Empty function.
 *
 * @param  {Object}  obj  Main menu item object
 */
HeaderClickMenu.deactivate = function(obj) {
    // donut
};


// #################################################################################################


/**
 * Header delay menu class. Switches the menu by mouseover/-out.
 *
 * @class    HeaderDelayMenu
 * @extends  HeaderMenu
 * @static
 */
var HeaderDelayMenu = jQuery.extend(true, {}, HeaderMenu);

HeaderDelayMenu.initialize = function(options) {
    this._mouseOverDelay = 300;
    this._mouseOutDelay  = 1000;

    if (typeof options == "undefined") {
        options = {};
    }
    if (typeof options.menuId == "undefined") {
        options.menuId = this.DEFAULT_MENU_ID;
    }
    if (typeof options.subMenuId == "undefined") {
        options.subMenuId = this.DEFAULT_SUBMENU_ID;
    }
    if (typeof options.mouseOverDelay == "undefined" || isNaN(options.mouseOverDelay)) {
        options.mouseOverDelay = this._mouseOverDelay;
    }
    if (typeof options.mouseOutDelay == "undefined" || isNaN(options.mouseOutDelay)) {
        options.mouseOutDelay = this._mouseOutDelay;
    }

    this._mouseOverDelay = options.mouseOverDelay;
    this._mouseOutDelay  = options.mouseOutDelay;

    this.setActiveMenu(options.menuId);
    this.setActiveSubMenu(options.subMenuId);

    $("#head_nav1 a").mouseover(function (){
        HeaderDelayMenu.activate(this);
    }).mouseout(function (){
        HeaderDelayMenu.deactivate(this);
    });

    $("#submenus a").click(function (){
        HeaderDelayMenu.markActive(this);
    }).mouseover(function (){
        HeaderTimer.resetOut();
    }).mouseout(function (){
        HeaderDelayMenu.deactivate(this);
    });
};


HeaderDelayMenu.activate = function(obj) {
    HeaderTimer.resetOut();
    var ident = this.getSubMenuIdByMenuId($(obj).attr("id"));
    HeaderTimer.resetOver();
    var that = this;
    HeaderTimer.over = setTimeout(function() {
        show(ident, obj);
    }, that._mouseOverDelay);
};

/**
 * Deactivates a menu by hiding its submenu using a defined delay time.
 *
 * @param  {Object}  obj  Main menu item object
 */
HeaderDelayMenu.deactivate = function(obj) {
    HeaderTimer.resetOut();
    var that = this;
    HeaderTimer.out = setTimeout(function() {
        show.apply(obj, [that.getActiveSubMenu(), that.getActiveMenu()]);
    }, that._mouseOutDelay);
};


// #################################################################################################

/**
 * Firebug emulation, to prevent errors if firebug is not available
 */
if (!("console" in window) || !("firebug" in console)) {
(function(){
    window.console = {
        log:   function(){},
        debug: function(){},
        info:  function(){},
        warn:  function(){},
        error: function(){}
    }
})();
}
