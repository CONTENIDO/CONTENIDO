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
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

(function(Con, $) {

    const NAME = 'subnav';
    const ANCHOR_SELECTOR = '#navlist li a';
    const ANCHOR_BY_ID_SELECTOR = '#navlist li#{id} a';

    /**
     * Subnav class
     * @class Subnav
     * @static
     */
    const Subnav = {

        /**
         * Highlights the first tab by default by setting the class to 'current'.
         * Is called in subnavigation template.
         *
         * @method init
         */
        init: function () {
            $(ANCHOR_SELECTOR).first().addClass('current');
        },

        /**
         * Highlights the active tab.
         *
         * @method clicked
         * @param {?HTMLElement} element Clicked a-element, respectively the tab to highlight.
         * @param {?Boolean} [changeNow] Whether to change highlight now (default is false)
         * @todo Consider new name ("highlight"?) and rename remaining instances.
         */
        clicked: function (element, changeNow) {
            if (!element) {
                Con.log("Couldn't get menu element for unknown element", NAME, "warn");
                return;
            }

            const rightBottomFrame = Con.getFrame("right_bottom");
            const that = this;

            const tabHighlight = function () {
                // change selected tab when new tab loads
                that._getAnchors().each(function () {
                    if (this === element) {
                        $(this).addClass('current');
                    } else {
                        $(this).removeClass('current');
                    }
                });
            };

            if (changeNow !== true && typeof (rightBottomFrame) !== 'undefined') {
                // Frame error handler
                rightBottomFrame.addEventListener('error', tabHighlight);

                // Frame pagehide (unload) handler
                rightBottomFrame.addEventListener('pagehide', function () {
                    if (!rightBottomFrame.stoppedUnload) {
                        rightBottomFrame.stoppedUnload = false;
                        tabHighlight();
                    }
                });
            } else {
                // Fallback if right bottom frame does not exist
                tabHighlight();
            }
        },

        /**
         * Highlights the active tab.
         * @method clickedById
         * @param {String} id Id of the tab element to highlight.
         * @todo Consider new name ("highlight"?) and rename remaining instances.
         */
        clickedById: function (id) {
            $(ANCHOR_SELECTOR).removeClass('current');
            $(this._getIdSelector(id)).addClass('current');
        },

        /**
         * Highlights a tab by its element id. Useful for highlighting from an outer frame.
         *
         * @method highlightById
         * @param {String} id Id of the tab element to highlight.
         * @param {window|Document|null} [frame]
         *      Reference to frame holding the subnavigation:
         *      top.content.right.right_top (when there is a left/right frameset)
         *      top.content.right_top (when there is no left/right frameset)
         */
        highlightById: function (id, frame) {
            this._reset(frame);
            $(this._getIdSelector(id)).addClass('current');
        },

        /**
         * Returns list of all found anchors within sub navigation.
         *
         * @method _getAnchors
         * @param {window|Document|null} [frame] Optional, reference to frame handling the sub navigation
         * @return {jQuery|HTMLElement[]|any} List of found HTMLElement
         * @protected
         */
        _getAnchors: function (frame) {
            const obj = (frame) ? frame.document : document;

            return $(obj).find(ANCHOR_SELECTOR);
        },

        /**
         * Reset all tabs.
         *
         * @method _reset
         * @param {window|Document|null} [frame] Optional, reference to frame handling the sub navigation
         * @protected
         */
        _reset: function (frame) {
            frame = frame || null;
            this._getAnchors(frame).removeClass('current');
        },

        /**
         * Returns the selector for an anchor by id.
         *
         * @param {String} id The id of the sub navigation list item (element).
         * @return {String}
         * @protected
         */
        _getIdSelector: function (id) {
            return ANCHOR_BY_ID_SELECTOR.replace('{id}', id);
        },

        /**
         * Dummy method to avoid breakage on old/deprecated `Subnav.click()` calls.
         *
         * @method click
         * @todo Locate remaining inline calls to `Subnav.click()` and remove them
         */
        'click': function () {
            return false;
        }
    };

    Con.Subnav = Subnav;

    // Bootstrap subnav module on document load
    $(function() {
        // Register click handler for all
        $(ANCHOR_SELECTOR).on('click', function() {
            Subnav.clicked(this);
        });
    });

})(Con, Con.$);
