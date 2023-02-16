/* global Con: true, jQuery: true */

/**
 * Subnavigation. Handles tab highlighting. Replaces most of the rowMark.js
 * functionality.
 *
 * @module     subnav
 * @requires   jQuery, Con
 * @author     dirk.eschler
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {

    var NAME = 'subnav';

    /**
     * Subnav class
     * @class  Subnav
     * @static
     */
    var Subnav = {

        /**
         * Highlights the first tab by default by setting the class to 'current'.
         * Is called in subnavigation template.
         * @method init
         */
        init: function() {
            $('#navlist li:first a').attr('class', 'current');
        },

        /**
         * Highlights the active tab.
         * @method clicked
         * @param {Object} cElm Clicked a-element, resp. the tab to highlight.
         * @param {Boolean} Whether to change highlight now (default is false)
         * @todo Consider new name ("highlight"?) and rename remaining instances.
         */
        clicked: function(cElm, changeNow) {
            var elem = ("string" === $.type(cElm)) ? $("#" + cElm + " a")[0] : cElm;
            if (!elem) {
                Con.log("Couldn't get menu element for " + cElm, NAME, "warn");
                return;
            }

            var subnav = this;
            if (true !== changeNow
            && 'undefined' !== typeof(Con.getFrame("right_bottom"))) {
                var tabHighlight = function() {
                    // change selected tab when new tab loads
                    var anchors = subnav._getAnchors(), i;
                    for (i = 0; i < anchors.length; i++) {
                        if (anchors[i] === elem) {
                            anchors[i].className = 'current';
                        } else {
                            anchors[i].className = '';
                        }
                    }
                };

                // frame has an error
                Con.getFrame("right_bottom").onerror = tabHighlight;

                // frame changes
                Con.getFrame("right_bottom").onunload = function() {
                    if ('undefined' === typeof(Con.getFrame("right_bottom").stoppedUnload)
                    || false === Con.getFrame("right_bottom").stoppedUnload) {
                        Con.getFrame("right_bottom").stoppedUnload = false;
                        tabHighlight();
                    }
                }
            } else {
                // fallback if right bottom frame does not exist
                var anchors = this._getAnchors(), i;
                for (i = 0; i < anchors.length; i++) {
                    if (anchors[i] === elem) {
                        anchors[i].className = 'current';
                    } else {
                        anchors[i].className = '';
                    }
                }
            }
        },

        /**
         * Highlights the active tab.
         * @method clickedById
         * @param {String}
         *            cElm Clicked a-element, resp. the tab to highlight.
         * @todo Consider new name ("highlight"?) and rename remaining instances.
         */
        clickedById: function(cElm) {
            $('#navlist li a').attr('class', '');
            $('#navlist li#' + cElm + ' a').attr('class', 'current');
        },

        /**
         * Highlights a tab by its element id. Useful for highlighting from an outer
         * frame.
         * @method highlightById
         * @param {String} id  Element id of tab to highlight
         * @param {Object} frame
         *      Reference to frame holding the subnavigation:
         *      top.content.right.right_top (when there is a left/right frameset)
         *      top.content.right_top (when there is no left/right frameset)
         */
        highlightById: function(id, frame) {
            this._reset(frame);
            var elem = this._getAnchorById(id, frame);
            if (elem) {
                elem.className = 'current';
            }
        },

        /**
         * Returns list of all found anchors within sub navigation
         * @method _getAnchors
         * @param {Object} [frame]  Optional, reference to frame handling the sub
         *            navigation
         * @return {Array} List of found HTMLElement
         * @protected
         */
        _getAnchors: function(frame) {
            var obj = (frame) ? frame.document : document;
            try {
                var list = obj.getElementById("navlist").getElementsByTagName("a");
                return list;
            } catch (e) {
                return [];
            }
        },

        /**
         * Returns anchor element by its id
         * @method _getAnchorById
         * @param {String}  id
         * @param {Object}  [frame] Optional, reference to frame handling the sub
         *            navigation
         * @return {HTMLElement|null}
         * @protected
         */
        _getAnchorById: function(id, frame) {
            var obj = (frame) ? frame.document : document;
            try {
                var elem = obj.getElementById(id).getElementsByTagName('a')[0];
                return elem;
            } catch (e) {
                return null;
            }
        },

        /**
         * Reset all tabs.
         * @method _reset
         * @param {Object} [frame] Optional, reference to frame handling the sub
         *            navigation
         * @protected
         */
        _reset: function(frame) {
            frame = frame || null;
            var anchors = this._getAnchors(frame), i;
            for (i = 0; i < anchors.length; i++) {
                anchors[i].className = '';
            }
        },

        /*
         * unhighlight: function() { var ul =
         * this.frame.document.getElementById('navlist'), as =
         * ul.getElementsByTagName('a'), i; for (i=0; i<=as.length; i++) { if
         * (as[i]) { as[i].className = ''; } } },
         */

        /**
         * Dummy method to avoid breakage.
         * @method click
         * @todo Locate remaining inline calls to Subnav.click() and remove them
         */
        'click': function() {
            // console.log("remove me");
            return;
        }
    };

    Con.Subnav = Subnav;

    // Bootstrap subnav module on document load
    $(function() {
        // Register click handler for all
        $('#navlist li a').click(function() {
            Subnav.clicked(this);
        });
    });

})(Con, Con.$);
