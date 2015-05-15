/* global Con: true, jQuery: true */

/**
 * File contains java script functions for filtering users in select areas, handling
 * short keys and submitting form. This functions are used in template
 * template.grouprights_memberselect.html
 *
 * @module     goup-association
 * @package    CONTENIDO Backend Scripts
 * @version    SVN Revision $Rev$
 * @author     Timo Trautmann
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {
//    'use strict';

    // #########################################################################
    // Some constants

    var NAME = 'goup-association';

    var SELECTOR_ACTION = 'input[name="action"]';

    /**
     * Group association class
     * @class  GroupAssociation
     * @constructor
     * @param {Object}  options  Configuration properties as follows
     * <pre>
     *    selectorForm  (String)
     *    add  (String)
     *    del  (String)
     * </pre>
     */
    Con.GroupAssociation = function(options) {

        // #####################################################################
        // Setup and private variables

        /**
         * Last pressed key
         * @property keycode
         * @type {Number}
         * @private
         */
        var keycode = 0; //
        /**
         * CONTENIDO action for adding user to group - (different fpr frontentgroups and backendgroups)
         * @property addAction
         * @type {String}
         * @private
         */
        var addAction = options.add;
        /**
         * CONTENIDO action for removing user from group - (different fpr frontentgroups and backendgroups)
         * @property deleteAction
         * @type {String}
         * @private
         */
        var deleteAction = options.del;
        /**
         * Reference to form
         * @property $form
         * @type {HTMLElement}
         * @private
         */
        var $form = $(options.selectorForm);

        // #####################################################################
        // Private functions

        /**
         * Function submits form when users were added to group or removed from group
         * @method _setAction
         * @param  {String}  actionType - CONTENIDO action string
         * @private
         */
        function _setAction(actionType) {
            var selectId = null;
            if (actionType == addAction) {
                // Case of adding new members
                selectId = '#newmember';
                $form.find(SELECTOR_ACTION).val(addAction);
            } else {
                // Case of removing existing members
                selectId = '#user_in_group';
                $form.find(SELECTOR_ACTION).val(deleteAction);
            }

            // Only submit form, if a user is selected
            if ($(selectId).prop('selectedIndex') != -1) {
                $form.submit();
            }
        }

        /**
         * Function filters entries in select box and shows only relevant users for selection
         * @method _filter
         * @param  {String}  id - id of textbox, which contains the search string
         * @private
         */
        function _filter(id) {
            //get search string ans buid regular expression
            var sFilterValue = document.getElementById(id).value;
            var oReg = new RegExp(sFilterValue, 'gi');

            //build id of corresponding select box
            var sSelectId = id.replace(/_filter_value/, '');

            //get select box and corresponding options
            var sSelectBox = document.getElementById(sSelectId);
            var oOptions = sSelectBox.getElementsByTagName('option');

            //remove all options
            var iLen = oOptions.length;
            for (var i = 0; i < iLen; i++) {
                sSelectBox.removeChild(oOptions[0]);
            }

            //get all options which where avariable in hidden select box
            var sSelectBoxAll = document.getElementById('all_'+sSelectId);
            var oOptionsAll = sSelectBoxAll.getElementsByTagName('option');

            //iterate over all hidden options
            var count = 0;
            for (var i = 0; i < oOptionsAll.length; i++) {
                //get the label of the option
                var label = oOptionsAll[i].firstChild.nodeValue;

                //if option label matches to search string
                if (label.match(oReg)) {
                    //generate new option element, fill it with the hidden values and append it to the select box which is viewable
                    var newOption = document.createElement('option');
                    newOption.value = oOptionsAll[i].value;
                    newOption.innerHTML = label;
                    newOption.disabled = false;
                    sSelectBox.appendChild(newOption);
                    count++;
                }
            }

            //if there are no options, deactivate corresponding move button
            document.getElementById(sSelectId + '_button').disabled = count == 0;
        }

        /**
         * Function is callend when user types into the filter inputs
         * @method _keyHandler
         * @param  {String}  id - id of textbox, which contains the search string
         * @private
         */
        function _keyHandler(id)  {
            //if user pressed enter key into filter input, js function filter is called
            if (keycode == 13) {
                _filter(id);
            }
        }

        /**
         * Function is callend when user presses a key
         * @method _setKeyCode
         * @param  {Event}  event  - event object
         * @private
         */
        function _setKeyCode(event) {
            if (!event) {
                event = window.event;
            }
            if (event.keyCode) {
                //for ie: store keycode, which is pressed into global variable
                keycode = event.keyCode;
            } else if (event.which) {
                //for mozilla: store keycode, which is pressed into global variable
                keycode = event.which;
            }
        }

        // #####################################################################
        // Initialize module and bind ui

        // Activate listener, which calls function _setKeyCode when user presses a key on keyboard
        document.onkeydown = _setKeyCode;

        // #####################################################################
        // Public interface

        return {

            /**
             * @method setAction
             * @param {String}  action
             */
            setAction: function(action) {
                return _setAction(action);
            },
            /**
             * @method filter
             * @param {String}  id
             */
            filter: function(id) {
                return _filter(id);
            },
            /**
             * @method keyHandler
             * @param {String}  id
             */
            keyHandler: function(id) {
                return _keyHandler(id);
            }

        };

    };

    /**
     * Goup association class
     * @class  GoupAssociation
     * @constructor
     * @param {Object}  options  Configuration properties as follows
     * <pre>
     *    selectorForm  (String)
     *    add  (String)
     *    del  (String)
     * </pre>
     * @deprecated [2015-05-15] Use GroupAssociation class instead
     */
    Con.GoupAssociation = Con.GroupAssociation;

})(Con, Con.$);
