/**
 * CONTENIDO JavaScript system_log_sysvalues.js module
 *
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */


(function(Con, $) {

    /**
     * Class SystemLogSysValues
     * @param {Object} options - Options
     * @param {jQuery} options.root - The root element jQuery object
     * @param {String} options.fileIsTooLargeMsg - Message to display for too large files
     * @constructor
     */
    function SystemLogSysValues (options) {
        this.root = options.root;
        this.fileIsTooLargeMsg = options.fileIsTooLargeMsg;
        this.select = this.root.find('select[name="logfile"]');
        this.msgNode = this.root.find('.system_log_message');
        this.textarea = this.root.find('textarea[name="log_file_content"]');
        this.numberOfLines = this.root.find('input[name="number_of_lines"]');
        this.keepLastLines = this.root.find('input[name="keep_last_lines"]');

        registerEvents(this);
        syncSelectedFileState(this);
    }

    function registerEvents(context) {
        context.root.find('[data-action]').live('click', function(event) {
            var $element = $(this),
                action = $element.data('action');
            if (action === 'show_log_file_lines') {
                event.preventDefault();
                actionShowLogfileLines(context);
            } else if (action === 'empty_log_file') {
                event.preventDefault();
                actionEmptyLogFile(context);
            } else if (action === 'delete_log_file') {
                event.preventDefault();
                actionDeleteLogFile(context);
            }
        });

        context.root.find('[data-action-change]').live('change', function() {
            var $element = $(this),
                action = $element.data('action-change');
            if (action === 'show_log_file') {
                actionShowLogfile(context);
            }
        });
    }

    /**
     * Loads a log file.
     *
     * @param {SystemLogSysValues} context
     */
    function loadLogFile (context) {
        var numberOfLines;

        if (isSelectedFileReadable(context)) {
            numberOfLines = context.numberOfLines.val();
            $.ajax({
                type : 'POST',
                url : 'ajaxmain.php',
                data : 'ajax=logfilecontent&logfile=' + context.select.val() + '&numberOfLines=' + numberOfLines,
                success : function(msg) {
                    if (Con.checkAjaxResponse(msg) === false)  {
                        return false;
                    }

                    context.textarea.val(msg);
                }
            });
        } else {
            context.textarea.val('');
        }
    }

    /**
     * Deletes the selected log file.
     *
     * @param {SystemLogSysValues} context
     */
    function actionDeleteLogFile(context) {
        var logfile = context.select.val();
        if (logfile !== '') {
            var url = 'main.php?area=system_log&frame=4&action=deletelog&logfile='
                + logfile;
            document.location.href = url;
        }
    }

    /**
     * Clears the selected log file and keeps the last X lines.
     *
     * @param {SystemLogSysValues} context
     */
    function actionEmptyLogFile(context) {
        if (isSelectedFileReadable(context)) {
            var keepLines = context.keepLastLines.val();
            var url = 'main.php?area=system_log&frame=4&action=clearlog&logfile='
                + context.select.val() + '&keepLines=' + keepLines;
            document.location.href = url;
        }
    }

    /**
     * Shows the specified number of lines of the specified log.
     *
     * @param {SystemLogSysValues} context
     */
    function actionShowLogfileLines(context) {
        if (isSelectedFileReadable(context)) {
            var numberOfLines = context.numberOfLines.val();
            var url = 'main.php?area=system_log&frame=4&action=showLog&logfile='
                + context.select.val() + '&numberOfLines=' + numberOfLines;
            document.location.href = url;
        }
    }

    /**
     * Shows the selected log file.
     *
     * @param {SystemLogSysValues} context
     */
    function actionShowLogfile(context) {
        syncSelectedFileState(context);
        loadLogFile(context);
    }

    /**
     * Synchronizes the selected file state, displays a proper message and disables some form elements if the file is not readable
     *
     * @param {SystemLogSysValues} context
     */
    function syncSelectedFileState(context) {
        if (isSelectedFileReadable(context)) {
            context.msgNode.html('');
            context.numberOfLines.removeAttr('disabled');
            context.keepLastLines.removeAttr('disabled');
        } else {
            context.msgNode.html(context.fileIsTooLargeMsg);
            context.numberOfLines.prop('disabled', true);
            context.keepLastLines.prop('disabled', true);
        }
    }

    /**
     * Checks if selected file has the non-readable state.
     *
     * @param {SystemLogSysValues} context
     * @returns {Boolean}
     */
    function isSelectedFileReadable(context) {
        return context.select.find(':selected').data('non-readable') != '1';
    }

    Con.SystemLogSysValues = SystemLogSysValues;

})(Con, Con.$);
