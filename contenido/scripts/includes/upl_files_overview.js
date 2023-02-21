/**
 * CONTENIDO JavaScript upl_files_overview.js module
 *
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @requires   iZoom
 */
(function(Con, $) {

    var TPL_ZOOM_DIALOG = ''
        + '<div class="upl_files_zoom_dialog" title="">'
        + '    <div><a href="javascript:void(0)" data-upl-files-zoom-dialog-action="close_dialog"><img src="" alt="{text_close}" title="{text_close}"></a></div>'
        + '</div>';

    var DEFAULT_OPTIONS = {
        rootSelector: 'body',
        filesPerPageSelector: '',
        filesCheckBoxSelector: '',
        deleteSelectedSelector: '',
        text_close: 'Click to close',
        text_delete_question: 'Are you sure you want to delete the selected files?',
    };

    /**
     * Upload files overview class
     * @class  UplFilesOverview
     * @constructor
     * @param {Object}  options  Configuration properties as follows
     * <pre>
     *    rootSelector  (String)  Selector for the root element of this component
     *    filesPerPageSelector  (String)  Selector for files per page select-box
     *    filesCheckBoxSelector  (String)  Selector for the checkboxes
     *    deleteSelectedSelector  (String)  Selector for the delete element
     *    text_close  (String)
     *    text_delete_question  (String)
     * </pre>
     * @return {UplFilesOverview}
     */
    Con.UplFilesOverview = function(options) {

        /**
         * @property  {Object}  _options
         * @private
         */
        var _options = $.extend(DEFAULT_OPTIONS, options),
            /**
             * @property  {jQuery}  $_root  Root node of this component
             * @private
             */
            $_root = $(_options.rootSelector),

            /**
             * @property  {jQuery}  $_filesPerPage  Files per page select box selector
             * @private
             */
            $_filesPerPage = $_root.find(_options.filesPerPageSelector),

            /**
             * @property  {jQuery}  $_filesCheckBox  Selector for the files checkboxes
             * @private
             */
            $_filesCheckBox = $_root.find(_options.filesCheckBoxSelector),

            /**
             * @property  {jQuery}  $_filesCheckBox  Selector for the delete selected files link
             * @private
             */
            $_deleteSelectedLink = $_root.find(_options.deleteSelectedSelector);

        var $_body = $('body'),
            $_zoomDialog;

        // #####################################################################
        // Functions

        /**
         * Initialize the component
         */
        function initialize() {
            // Create and add the dialog element to the page.
            $_zoomDialog = $_body.find('.upl_files_zoom_dialog');
            if ($_zoomDialog.length < 1) {
                var html = Con.parseTemplate(TPL_ZOOM_DIALOG, {
                    text_close: _options.text_close
                });
                $_body.append(html);
                $_zoomDialog = $_body.find('.upl_files_zoom_dialog');
            }
            // Base settings for dialog
            $_zoomDialog.dialog({
                autoOpen: false,
                height: 480,
                width: 640
            });
        }

        /**
         * Register event handler
         */
        function registerEventHandler() {
            // Various click handler on elements
            $_root.find('[data-action]').live('click', function() {
                var $element = $(this),
                    action = $element.data('action');

                if (action === 'invert_selection') {
                    actionInvertSelection();
                } else if (action === 'delete_selected') {
                    return actionDeleteSelected($element);
                } else if (action === 'zoom') {
                    return actionZoom($element);
                }
            });

            // Mouseover for icons in the list
            $_root.find('[data-action-mouseover]').live('mouseover', function() {
                var $element = $(this),
                    action = $element.data('action-mouseover');

                if (action === 'zoom') {
                    actionMouseoverZoom($element);
                }
            });

            // Update both files per page select boxes if one changes
            $_filesPerPage.on('change', function(event) {
                var $this = $(event.currentTarget);
                $_filesPerPage.val($this.val());
            });

            // Handler for changes files checkboxes
            $_filesCheckBox.on('change', function() {
                actionOnFilesCheckboxChange();
            });

            $_body.find('[data-upl-files-zoom-dialog-action]').live('click', function() {
                var $element = $(this),
                    action = $element.data('upl-files-zoom-dialog-action');
                if (action === 'close_dialog') {
                    actionCloseDialog();
                }
            });

            // Initial call to update delete links
            actionOnFilesCheckboxChange();
        }

        /**
         * Returns top position relative to document
         * @param {jQuery} $element
         * @return {Number}
         */
        function getY($element) {
            var offset = $element.offset();
            return offset ? offset.top : 0;
        }

        /**
         * Returns left position relative to document
         * @param {jQuery} $element
         * @return {Number}
         */
        function getX($element) {
            var offset = $element.offset();
            return offset ? offset.left : 0;
        }

        // Hoverbox to display thumbnail preview
        function correctPosition($image, iWidth, iHeight) {
            var $previewImage = $image.parent().find('.preview');
            $previewImage.css({
                'width': iWidth,
                'height': iHeight,
                'margin-top': getY($image) + 'px',
                'margin-left': (getX($image) + 100) + 'px'
            });
        }

        // Thumbnail preview
        function actionMouseoverZoom($element) {
            correctPosition($element, $element.attr("data-width"), $element.attr("data-height"));
        }

        // Invert selection of checkboxes
        function actionInvertSelection() {
            $_filesCheckBox.each(function(pos, item) {
                $(item).prop('checked', !$(item).prop('checked'));
            });
            actionOnFilesCheckboxChange();
        }

        // Delete selected files
        function actionDeleteSelected($element) {
            if ($element.hasClass('is_disabled')) {
                return false;
            }
            Con.showConfirmation(_options.text_delete_question, function() {
                $_root.find('input[name=action]').val('upl_multidelete');
                $_root[0].submit();
            });
            return false;
        }

        // Display image in dialog
        function actionZoom($element) {
            $_zoomDialog.find('img').attr('src', $element.attr('href'));
            $_zoomDialog.dialog('open');
            return false;
        }

        // Close dialog
        function actionCloseDialog() {
            $_zoomDialog.dialog('close');
        }

        // On files checkbox change
        function actionOnFilesCheckboxChange() {
            var numChecked = $_filesCheckBox.filter(':checked').length;
            if (numChecked > 0) {
                $_deleteSelectedLink.removeClass('is_disabled').removeAttr('aria-disabled');
            } else {
                $_deleteSelectedLink.addClass('is_disabled').attr('aria-disabled', 'true');
            }
        }

        // #####################################################################
        // Initialization and event registration

        initialize();
        registerEventHandler();

        // #####################################################################
        // Public interface

        return {
            // There is nothing public
        };

    };

})(Con, Con.$);
